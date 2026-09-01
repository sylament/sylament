<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Closure;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Twig\Markup;

abstract class ListRecords extends Page implements InteractsWithSchemas, InteractsWithTable
{
    use HasSchemas;
    use HasTable;

    protected ?string $template = '@SylamentPanels/partials/ListRecords/index.html.twig';

    /**
     * Nothing here resolves anything, and the parent keys are still taken as a
     * mount argument rather than left to the property assignment that follows a
     * mount: a page that scopes its own query cannot depend on that order.
     *
     * @param array<string, string> $parentRecords
     */
    public function mount(array $parentRecords = []): void
    {
        $this->parentRecords = $parentRecords;
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
