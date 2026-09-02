<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Closure;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Concerns\HasTabs;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Twig\Markup;

use function Sylament\Support\filled;

abstract class ListRecords extends Page implements InteractsWithSchemas, InteractsWithTable
{
    use HasSchemas;
    /*
     * The alias stays ungrouped with the trait it renames: Rector splits a
     * grouped `use` and drops the adaptation block with it.
     */
    use HasTable {
        HasTable::makeTable as makeBaseTable;
    }
    use HasTabs;

    protected ?string $template = '@SylamentPanels/partials/ListRecords/index.html.twig';

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(array $parentRecords = []): void
    {
        $this->parentRecords = $parentRecords;
    }

    /**
     * The browsing state this page carries in its own URL. Every name is
     * Filament's, and only the page parameter takes the host identifier, so two
     * listings reached through one route never share a page number.
     */
    protected function getTableUrlMapping(string $prop): ?UrlMapping
    {
        $identifier = $this->getTableQueryStringIdentifier();

        return match ($prop) {
            'page' => new UrlMapping(as: $identifier === null ? 'page' : $identifier . 'Page'),
            'sort' => new UrlMapping(as: 'sort'),
            'search' => new UrlMapping(as: 'search'),
            'filters' => new UrlMapping(as: 'filters'),
            'tab' => new UrlMapping(as: 'tab'),
            default => null,
        };
    }

    /**
     * The active tab scopes the query before the resource configures anything,
     * and the row is seeded to open the record: an action carrying no URL of
     * its own becomes the row's click target, one carrying a URL becomes the
     * row's link, and a resource page answers where neither does.
     */
    protected function makeTable(): Table
    {
        $table = $this->makeBaseTable()
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->recordAction(static function (object $record, Table $table): ?string {
                foreach (['view', 'edit'] as $name) {
                    $action = $table->getAction($name);

                    if (!$action instanceof Action) {
                        continue;
                    }

                    $action = static::stampRecordOnActionChain($action, $record);

                    if ($action->isHidden() || filled($action->getUrl())) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            });

        if (!$table->hasCustomRecordUrl()) {
            $table->recordUrl(function (object $record, Table $table): ?string {
                foreach (['view', 'edit'] as $name) {
                    $action = $table->getAction($name);

                    if (!$action instanceof Action) {
                        continue;
                    }

                    $action = static::stampRecordOnActionChain($action, $record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (filled($url)) {
                        return $url;
                    }
                }

                $resource = $this->getResourceInstance();

                foreach (['view', 'edit'] as $page) {
                    if ($resource->hasPage($page)) {
                        return $resource->getUrl($page, [...$this->parentRecords, 'record' => $record]);
                    }
                }

                return null;
            });
        }

        return $table;
    }

    /**
     * The record an action is asked about rides its whole group chain: a hidden
     * or URL arm reading the record through its group otherwise answers for the
     * row the chain last carried.
     */
    protected static function stampRecordOnActionChain(Action $action, object $record): Action
    {
        $clone = $action->getClone();
        $clone->record($record);

        $group = $clone->getGroup();

        while ($group instanceof ActionGroup) {
            $group->record($record);

            $group = $group->getGroup();
        }

        return $clone;
    }

    /**
     * A nested list shows one parent's records: the scoped query is rooted at
     * the same `record` alias the plain one is, so the resource's columns,
     * sorts and searches are the same either way.
     */
    public function table(Table $table): Table
    {
        $resource = $this->getResourceInstance();
        $parentRecord = $this->getParentRecord();

        return $resource->table($table
            ->query($parentRecord === null
                ? $resource->getRecordQuery(...)
                : fn (): QueryBuilder => $resource->getParentScopedRecordQuery($parentRecord))
            ->recordLabel($resource->getRecordLabel(...))
            ->pluralRecordLabel($resource->getPluralRecordLabel(...))
            ->recordTitleAttribute($resource->getRecordTitleAttribute(...))
            ->recordTitle($resource->getRecordTitle(...)));
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? $this->getResourceInstance()->getTitleCasePluralRecordLabel();
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? 'List';
    }

    public function form(Schema $schema): Schema
    {
        return $this->getResourceInstance()->form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return $this->getResourceInstance()->infolist($schema);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => $this->form(...),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema)),
            default => null,
        };
    }

    /**
     * The page's own actions carry no table, so the resource titles them here.
     * A row action reaches its table's title first and never arrives. Null hands
     * `Action::getRecordTitle()` back its record-label fallback, which is also
     * what a resource naming no title attribute wants.
     */
    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        $record = $action->getRecord();
        $resource = $this->getResourceInstance();

        if (! is_object($record) || ! $resource->hasRecordTitle()) {
            return null;
        }

        return $resource->getRecordTitle($record);
    }

    /**
     * @return object|array<string, mixed>|string|null
     */
    protected function getMountedActionSchemaRecord(): object|array|string|null
    {
        return $this->getResourceInstance()->getRecordClass();
    }

    /**
     * The filters form is cache-only and a list page declares no form of its
     * own — so the discovery list is empty rather than the default `schema`,
     * which would demand a `schema()` method on every dehydrate. `form()` and
     * `infolist()` exist for the action schema resolver and are not discovered.
     *
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }
}
