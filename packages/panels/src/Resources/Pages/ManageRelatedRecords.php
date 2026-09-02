<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use BackedEnum;
use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Concerns\InteractsWithRelationshipTable;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord;
use Sylament\Panels\Twig\PanelsIconAlias;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;
use Sylament\Support\Icons\IconManager;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Twig\Markup;

/**
 * One owner record's association as a page of its own: the routed sibling of a
 * relation manager, and the listing a nested resource's records are reached
 * through.
 *
 * The registration key this page is mounted under **must** be the kebab-cased
 * relationship name — `'tasks' => ManageProjectTasks::route('/{record}/tasks')`
 * for a `tasks` association. {@see Resource::getIndexUrl()} looks the page up on
 * the parent resource by exactly that key, so a page registered under any other
 * one is never the listing its related resource answers with: the URL falls
 * through to the parent's view or edit page instead.
 */
abstract class ManageRelatedRecords extends Page implements InteractsWithTable
{
    use HasTable;
    use InteractsWithRecord;
    /*
     * `getDefaultActionUrl` needs no exclusion here: `HasActions` arrives through
     * the parent class, where a trait on this one wins by ordinary precedence.
     * The insert stays ungrouped because Rector splits a grouped one and drops
     * the `insteadof` with it, which turns the composition into a fatal.
     */
    use InteractsWithRelationshipTable {
        InteractsWithRelationshipTable::makeTable insteadof HasTable;
        InteractsWithRelationshipTable::getTableQueryStringIdentifier insteadof HasTable;
    }

    protected ?string $template = '@SylamentPanels/partials/ManageRelatedRecords/index.html.twig';

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);
    }

    /**
     * The browsing state this page carries in its own URL — the routed sibling
     * of the relation manager, which maps nothing and stays unlinkable. Only
     * the page parameter takes the tier's identifier, so a second listing on
     * this route never shares a page number with it.
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

    public function getOwnerRecord(): object
    {
        return $this->getRecord();
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::getHumanizedRelationshipName();
    }

    public static function getNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$navigationIcon
            ?? static fn (IconManager $icons): string|BackedEnum|Markup => $icons->resolve(PanelsIconAlias::RESOURCES_PAGES_MANAGE_RELATED_RECORDS_NAVIGATION_ITEM) ?? 'heroicons:rectangle-stack';
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? sprintf('Manage %s %s', $this->getRecordTitle(), $this->getRelationshipTitle());
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? $this->getRelationshipTitle();
    }

    /**
     * The page's own record is the owner, so a frame carrying one of the listed
     * records — a row frame, or a create writing a new one — takes none of it.
     *
     * @return object|array<string, mixed>|null
     */
    public function getDefaultActionRecord(Action $action): object|array|null
    {
        if ($action instanceof CreateAction || $action->getTable() instanceof Table) {
            return null;
        }

        return $this->getRecord();
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return $action instanceof CreateAction
            ? $this->getTable()->getRecordClass()
            : parent::getDefaultActionRecordClass($action);
    }

    public function getDefaultActionRecordLabel(Action $action): ?string
    {
        return $action instanceof CreateAction
            ? $this->getTable()->getRecordLabel()
            : parent::getDefaultActionRecordLabel($action);
    }

    /**
     * The owner titles the frames that carry it, and only those: a create writes
     * a listed record and a table frame is titled by the table itself.
     */
    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        if ($action instanceof CreateAction || $action->getTable() instanceof Table) {
            return null;
        }

        return $this->getRecordTitle();
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
     * A record created here joins the association this page lists, not the one
     * the page's own resource hangs under.
     */
    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        return $action instanceof CreateAction ? $this->getRelationship() : null;
    }

    /**
     * @return list<string>
     */
    public function getPageClasses(): array
    {
        return [
            'sy-pa-resource-manage-related-records-page',
            'sy-pa-resource-' . str_replace('/', '-', $this->getResourceInstance()->getSlug()),
            'sy-pa-resource-record-' . $this->getRecordKey(),
        ];
    }
}
