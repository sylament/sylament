<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Actions\ViewAction;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithParentRecord;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Contracts\InteractsWithTable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Twig\Markup;

use function Sylament\Support\filled;

abstract class Page extends BasePage implements InteractsWithActions
{
    use HasActions {
        getAction as private getCachedOrDeclaredAction;
    }
    use InteractsWithParentRecord;

    /**
     * @var class-string<Resource>
     */
    protected static string $resource;

    protected static ?string $breadcrumb = null;

    /**
     * The panel this page was opened in. A live request reaches ux's own route,
     * which carries no `_sylament_panel` default, so the prop restores it —
     * without it every URL a re-render mints, from the trail to a record link,
     * would answer for the *default* panel. Minted by the getter dehydration
     * reads rather than in a mount, because a page's mount signature is the
     * page's own.
     */
    #[LiveProp]
    public ?string $panelId = null;

    private ?ResourceRegistry $resourceRegistry = null;

    private ?CurrentPanel $currentPanel = null;

    private ?RequestStack $requests = null;

    /**
     * @var array<Action|ActionGroup>|null
     */
    private ?array $cachedHeaderActions = null;

    #[Required]
    public function setResourceRegistry(ResourceRegistry $resourceRegistry): void
    {
        $this->resourceRegistry = $resourceRegistry;
    }

    #[Required]
    public function setPanelContext(CurrentPanel $currentPanel, RequestStack $requests): void
    {
        $this->currentPanel = $currentPanel;
        $this->requests = $requests;
    }

    public function getPanelId(): ?string
    {
        return $this->panelId ??= $this->currentPanel?->get()?->getId();
    }

    #[PostHydrate]
    public function stampPanelRequestAttribute(): void
    {
        if ($this->panelId === null) {
            return;
        }

        $this->requests?->getCurrentRequest()?->attributes->set('_sylament_panel', $this->panelId);
    }

    /**
     * @return class-string<Resource>
     */
    public static function getResource(): string
    {
        return static::$resource;
    }

    public static function route(string $path): PageRegistration
    {
        return new PageRegistration(static::class, $path);
    }

    public function getResourceInstance(): Resource
    {
        return $this->getResourceRegistry()->get(static::getResource());
    }

    protected function getResourceRegistry(): ResourceRegistry
    {
        return $this->resourceRegistry
            ?? throw new LogicException(sprintf('The resource page [%s] was built outside the container, so it holds no resource registry; register it as an autowired service.', static::class));
    }

    public function getBreadcrumb(): string|Markup
    {
        return static::$breadcrumb ?? $this->getTitle();
    }

    /**
     * @return array<int|string, string|Markup>
     */
    public function getBreadcrumbs(): array
    {
        return [
            ...$this->getResourceBreadcrumbs(),
            $this->getBreadcrumb(),
        ];
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return true;
    }

    /**
     * The trail above this page's own crumb: for every ancestor, root-first, the
     * resource's own listing and then the record itself, and last this
     * resource's listing. A crumb whose URL the trail already spends renders
     * unlinked rather than replacing the earlier one — a nested resource with no
     * listing of its own is reached through its parent's record page, which is
     * the crumb directly above it.
     *
     * @return array<int|string, string|Markup>
     */
    public function getResourceBreadcrumbs(): array
    {
        if (! $this->hasResourceBreadcrumbs()) {
            return [];
        }

        $breadcrumbs = [];
        $parameters = [];

        foreach ($this->getResolvedParentRecords() as $link) {
            $ancestorResource = $link['resource'];
            $ancestorRecord = $link['record'];

            $breadcrumbs = $this->appendBreadcrumb($breadcrumbs, $ancestorResource->getIndexUrl($parameters), $ancestorResource->getBreadcrumb());
            $breadcrumbs = $this->appendBreadcrumb(
                $breadcrumbs,
                $this->getRecordBreadcrumbUrl($ancestorResource, $parameters, $ancestorRecord),
                $ancestorResource->hasRecordTitle() ? $ancestorResource->getRecordTitle($ancestorRecord) : $ancestorResource->getTitleCaseRecordLabel(),
            );

            $parameters[$link['parameter']] = $ancestorResource->getRecordRouteKey($ancestorRecord);
        }

        $resource = $this->getResourceInstance();

        return $this->appendBreadcrumb($breadcrumbs, $resource->getIndexUrl($parameters), $resource->getBreadcrumb());
    }

    /**
     * A string key is the crumb's URL and renders as a link; an int key renders
     * as plain text, which is what a URL the trail already spends becomes.
     *
     * @param array<int|string, string|Markup> $breadcrumbs
     *
     * @return array<int|string, string|Markup>
     */
    protected function appendBreadcrumb(array $breadcrumbs, ?string $url, string|Markup $label): array
    {
        if ($url === null || array_key_exists($url, $breadcrumbs)) {
            $breadcrumbs[] = $label;

            return $breadcrumbs;
        }

        $breadcrumbs[$url] = $label;

        return $breadcrumbs;
    }

    /**
     * Where a record crumb points: the resource's view page, else its edit page,
     * else nowhere — Filament's arms with the authorization checks left out.
     *
     * @param array<string, string> $parameters the ancestor keys above the record
     */
    protected function getRecordBreadcrumbUrl(Resource $resource, array $parameters, object|string $record): ?string
    {
        foreach (['view', 'edit'] as $pageKey) {
            if ($resource->hasPage($pageKey)) {
                return $resource->getUrl($pageKey, [...$parameters, 'record' => $record]);
            }
        }

        return null;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    /**
     * The header list every page template renders, each entry bound to this page
     * and registered under its own name, so an action built inline in the
     * declaration reaches the host hooks its trigger renders from.
     *
     * @return array<Action|ActionGroup>
     */
    public function getCachedHeaderActions(): array
    {
        if ($this->cachedHeaderActions !== null) {
            return $this->cachedHeaderActions;
        }

        $this->cachedHeaderActions = [];
        $headerActions = $this->getHeaderActions();

        foreach ($headerActions as $headerAction) {
            if ($headerAction instanceof ActionGroup) {
                $headerAction->liveComponent($this);

                foreach ($headerAction->getFlatActions() as $groupedAction) {
                    $this->cacheAction($groupedAction);
                }

                continue;
            }

            $this->cacheAction($headerAction);
        }

        return $this->cachedHeaderActions = $headerActions;
    }

    public function getAction(string $name): ?Action
    {
        $action = $this->getCachedOrDeclaredAction($name);

        if ($action instanceof Action || $this->cachedHeaderActions !== null) {
            return $action;
        }

        $this->getCachedHeaderActions();

        return $this->getCachedOrDeclaredAction($name);
    }

    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return $this->getResourceInstance()->getRecordClass();
    }

    public function getDefaultActionRecordLabel(Action $action): ?string
    {
        return $this->getResourceInstance()->getRecordLabel();
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        $resource = $this->getResourceInstance();
        $recordClass = $action->getRecordClass();
        $ownsRecordClass = $recordClass === null || $recordClass === $resource->getRecordClass();

        if ($action instanceof CreateAction && $resource->hasPage('create') && $ownsRecordClass) {
            return $resource->getUrl('create', [...$this->parentRecords]);
        }

        if ($action instanceof EditAction && $resource->hasPage('edit') && ! $this instanceof EditRecord && $ownsRecordClass && $action->getRecord() !== null) {
            return $resource->getUrl('edit', [...$this->parentRecords, 'record' => $action->getRecord()]);
        }

        if ($action instanceof ViewAction && $resource->hasPage('view') && $ownsRecordClass && $action->getRecord() !== null) {
            return $resource->getUrl('view', [...$this->parentRecords, 'record' => $action->getRecord()]);
        }

        return null;
    }

    /**
     * A record created on a nested page joins the parent it was created under —
     * the relationship a `CreateAction`'s own write flushes it through.
     */
    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        $parentRecord = $this->getParentRecord();

        if (! $action instanceof CreateAction || $parentRecord === null) {
            return null;
        }

        return new Relationship($parentRecord, $this->getResourceInstance()->getParentRelationshipName());
    }

    /**
     * A resource page resolves a frame's record key through the resource's
     * scoped binding, never a bare `find()` — after the trait's own guards. The
     * descriptor's key is client-supplied and carries no checksum, so on a
     * nested page it is narrowed to the parent exactly as a route key is.
     *
     * @param array{name: string, arguments: array<string, mixed>, context: array<string, mixed>} $descriptor
     */
    protected function resolveMountedActionRecord(array $descriptor): mixed
    {
        $recordKey = $descriptor['context']['recordKey'] ?? null;

        if ($recordKey === null || filled($descriptor['context']['schemaComponent'] ?? null)) {
            return null;
        }

        if ($this instanceof InteractsWithTable && filled($descriptor['context']['table'] ?? null)) {
            return null;
        }

        if (! is_scalar($recordKey)) {
            throw new ActionNotResolvableException(sprintf('Record key of type [%s] is not a valid identifier.', get_debug_type($recordKey)));
        }

        return $this->getResourceInstance()->resolveRecordRouteBinding((string) $recordKey, $this->getParentRecordScope())
            ?? throw new NotFoundHttpException(sprintf('No record matching [%s] exists for the resource [%s].', (string) $recordKey, static::getResource()));
    }

    /**
     * Filament's convention hook: `beforeSave`, `afterCreate`, … run when the
     * page declares a method of that name, and are silent otherwise.
     */
    protected function callHook(string $hook): void
    {
        if (! method_exists($this, $hook)) {
            return;
        }

        $this->{$hook}();
    }
}
