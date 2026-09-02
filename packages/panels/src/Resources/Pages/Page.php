<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Panel;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithParentRecord;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Contracts\InteractsWithTable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\filled;

abstract class Page extends BasePage
{
    use InteractsWithParentRecord;

    /**
     * @var class-string<Resource>
     */
    protected static string $resource;

    protected static ?string $breadcrumb = null;

    private ?ResourceRegistry $resourceRegistry = null;

    #[Required]
    public function setResourceRegistry(ResourceRegistry $resourceRegistry): void
    {
        $this->resourceRegistry = $resourceRegistry;
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
     * A URL of this page's own resource, the record filled in from the page when
     * it carries one and the parent keys threaded as every other URL arm does.
     *
     * @param array<string, mixed> $parameters
     */
    public function getResourceUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, Panel|string|null $panel = null, bool $shouldGuessMissingParameters = true): string
    {
        if (filled($name) && $name !== 'index' && method_exists($this, 'getRecordKey')) {
            $parameters['record'] ??= $this->getRecordKey();
        }

        return $this->getResourceInstance()->getUrl($name, [...$this->parentRecords, ...$parameters], $isAbsolute, $panel, $shouldGuessMissingParameters);
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
     * The page static still wins on a resource page; the resource is what
     * answers underneath it, rather than the panel directly.
     */
    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::$subNavigationPosition ?? $this->getResourceInstance()->getSubNavigationPosition();
    }

    /**
     * The band item for a sibling page of a resource: its own resource mints the
     * URL, keyed by the registration the page is mounted under and pathed like
     * every other navigation item panels emit. A page the resource registers
     * nowhere contributes nothing — Filament's `hasPage()` gates skip it just as
     * silently.
     *
     * @param class-string<BasePage> $pageClass
     * @param array<string, mixed>   $parameters
     *
     * @return array<NavigationItem>
     */
    protected function generateResourcePageNavigationItems(string $pageClass, array $parameters): array
    {
        if (! is_subclass_of($pageClass, self::class)) {
            return [];
        }

        $resource = $this->getResourceRegistry()->get($pageClass::getResource());
        $pageKey = null;

        foreach ($resource->getPages() as $key => $registration) {
            if ($registration->page === $pageClass) {
                $pageKey = (string) $key;

                break;
            }
        }

        if ($pageKey === null) {
            return [];
        }

        return [
            NavigationItem::make($pageClass::getNavigationLabel())
                ->group($pageClass::getNavigationGroup())
                ->icon($pageClass::getNavigationIcon())
                ->activeIcon($pageClass::getActiveNavigationIcon())
                ->sort($pageClass::getNavigationSort())
                ->badge($pageClass::getNavigationBadge(), color: $pageClass::getNavigationBadgeColor())
                ->url($resource->getUrl($pageKey, $parameters, isAbsolute: false, shouldGuessMissingParameters: true))
                ->isActiveWhen(fn (): bool => $pageClass === static::class),
        ];
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
}
