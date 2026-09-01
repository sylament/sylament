<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\RelationManagers;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Concerns\InteractsWithRelationshipTable;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

use function Sylament\Support\blank;

/**
 * One owner record's association, as a live component of its own: a table
 * scoped to the association, the actions that write it, and the modals those
 * actions open. It is mounted by an embed on the owning page's content schema,
 * never routed to.
 */
abstract class RelationManager implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;
    /*
     * One `use` statement per trait, and the exclusions naming traits inserted
     * above: Rector's `SeparateMultiUseImportsRector` splits a grouped insert and
     * drops every `insteadof` with it, which turns the composition into a fatal.
     */
    use InteractsWithRelationshipTable {
        InteractsWithRelationshipTable::makeTable insteadof HasTable;
        InteractsWithRelationshipTable::makeTable as makeBaseRelationshipTable;
        InteractsWithRelationshipTable::getDefaultActionUrl insteadof HasActions;
    }

    /**
     * The owner's route key, never the record itself — the wire carries a
     * scalar and {@see getOwnerRecord()} resolves it through the page's
     * resource.
     */
    #[LiveProp(dehydrateWith: 'dehydrateOwnerRecord')]
    public ?string $ownerRecord = null;

    /**
     * @var class-string<Page>|null
     */
    #[LiveProp(dehydrateWith: 'dehydratePageClass')]
    public ?string $pageClass = null;

    protected static ?string $title = null;

    protected static string|BackedEnum|null $icon = null;

    protected static IconPosition $iconPosition = IconPosition::Before;

    protected static ?string $badge = null;

    protected static ?string $badgeColor = null;

    protected static string|Markup|null $badgeTooltip = null;

    protected string $template = '@SylamentPanels/partials/RelationManager/index.html.twig';

    private ?object $cachedOwnerRecord = null;

    private ?ResourceRegistry $resourceRegistry = null;

    #[Required]
    public function setResourceRegistry(ResourceRegistry $resourceRegistry): void
    {
        $this->resourceRegistry = $resourceRegistry;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function make(array $properties = []): RelationManagerConfiguration
    {
        return new RelationManagerConfiguration(static::class, $properties);
    }

    public static function getComponentName(): string
    {
        return str_replace('\\', ':', static::class);
    }

    public static function getTitle(object $ownerRecord, string $pageClass): string
    {
        return static::$title ?? static::getHumanizedRelationshipName();
    }

    public static function getIcon(object $ownerRecord, string $pageClass): string|BackedEnum|Markup|null
    {
        return static::$icon;
    }

    public static function getIconPosition(object $ownerRecord, string $pageClass): IconPosition
    {
        return static::$iconPosition;
    }

    public static function getBadge(object $ownerRecord, string $pageClass): ?string
    {
        return static::$badge;
    }

    public static function getBadgeColor(object $ownerRecord, string $pageClass): ?string
    {
        return static::$badgeColor;
    }

    public static function getBadgeTooltip(object $ownerRecord, string $pageClass): string|Markup|null
    {
        return static::$badgeTooltip;
    }

    public static function getTabComponent(object $ownerRecord, string $pageClass): Tab
    {
        return Tab::make(static::getTitle($ownerRecord, $pageClass))
            ->badge(static::getBadge($ownerRecord, $pageClass))
            ->badgeColor(static::getBadgeColor($ownerRecord, $pageClass))
            ->badgeTooltip(static::getBadgeTooltip($ownerRecord, $pageClass))
            ->icon(static::getIcon($ownerRecord, $pageClass))
            ->iconPosition(static::getIconPosition($ownerRecord, $pageClass));
    }

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    /**
     * The mount props every registration of this manager carries. Empty here —
     * Filament's only entry is the lazy flag, which does not port.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        return [];
    }

    /**
     * The wire value is the route key the property holds, not the entity
     * `PropertyAccessor` reads out of the same-named `getOwnerRecord()`.
     */
    public function dehydrateOwnerRecord(mixed $value): ?string
    {
        return $this->ownerRecord;
    }

    /**
     * @return class-string<Page>|null
     */
    public function dehydratePageClass(mixed $value): ?string
    {
        return $this->pageClass;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return class-string<Page>
     */
    public function getPageClass(): string
    {
        return $this->pageClass
            ?? throw new LogicException(sprintf('The relation manager [%s] carries no page class — mount it with the class of the page that embeds it.', static::class));
    }

    public function getOwnerRecord(): object
    {
        if ($this->cachedOwnerRecord !== null) {
            return $this->cachedOwnerRecord;
        }

        $key = $this->ownerRecord;

        if (blank($key)) {
            throw new NotFoundHttpException(sprintf('The relation manager [%s] carries no owner record key to resolve.', static::class));
        }

        $pageClass = $this->getPageClass();

        if (! is_subclass_of($pageClass, Page::class)) {
            throw new NotFoundHttpException(sprintf('The relation manager [%s] was mounted against [%s], which is not a resource page.', static::class, $pageClass));
        }

        $resourceClass = $pageClass::getResource();
        $record = $this->getResourceRegistry()->get($resourceClass)->resolveRecordRouteBinding($key);

        if ($record === null) {
            throw new NotFoundHttpException(sprintf('No record matching [%s] exists for the resource [%s].', $key, $resourceClass));
        }

        return $this->cachedOwnerRecord = $record;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction, $action instanceof ViewAction => $this->form(...),
            default => null,
        };
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        return $action instanceof CreateAction ? $this->getRelationship() : null;
    }

    /**
     * The manager declares no named schema of its own: an action modal's schema
     * is minted per mount and cached, so discovery has nothing to walk.
     *
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseRelationshipTable()
            ->heading(fn (): string => static::getTitle($this->getOwnerRecord(), $this->getPageClass()));
    }

    protected function getResourceRegistry(): ResourceRegistry
    {
        return $this->resourceRegistry
            ?? throw new LogicException(sprintf('The relation manager [%s] was built outside the container, so it holds no resource registry; register it as an autowired service.', static::class));
    }
}
