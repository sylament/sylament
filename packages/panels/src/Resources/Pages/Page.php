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
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Tables\Contracts\InteractsWithTable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\filled;

abstract class Page extends BasePage implements InteractsWithActions
{
    use HasActions {
        getAction as private getCachedOrDeclaredAction;
    }

    /**
     * @var class-string<Resource>
     */
    protected static string $resource;

    protected static ?string $breadcrumb = null;

    private ?ResourceRegistry $resourceRegistry = null;

    private bool $hasCachedHeaderActions = false;

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
        $registry = $this->resourceRegistry
            ?? throw new LogicException(sprintf('The resource page [%s] was built outside the container, so it holds no resource registry; register it as an autowired service.', static::class));

        return $registry->get(static::getResource());
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
        $resource = $this->getResourceInstance();

        return [
            $resource->getIndexUrl() => $resource->getTitleCasePluralRecordLabel(),
            $this->getBreadcrumb(),
        ];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    public function getAction(string $name): ?Action
    {
        $action = $this->getCachedOrDeclaredAction($name);

        if ($action instanceof Action || $this->hasCachedHeaderActions) {
            return $action;
        }

        $this->hasCachedHeaderActions = true;

        foreach ($this->getHeaderActions() as $headerAction) {
            if ($headerAction instanceof ActionGroup) {
                foreach ($headerAction->getFlatActions() as $groupedAction) {
                    $this->cacheAction($groupedAction);
                }

                continue;
            }

            $this->cacheAction($headerAction);
        }

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
            return $resource->getUrl('create');
        }

        if ($action instanceof EditAction && $resource->hasPage('edit') && ! $this instanceof EditRecord && $ownsRecordClass && $action->getRecord() !== null) {
            return $resource->getUrl('edit', ['record' => $action->getRecord()]);
        }

        if ($action instanceof ViewAction && $resource->hasPage('view') && $ownsRecordClass && $action->getRecord() !== null) {
            return $resource->getUrl('view', ['record' => $action->getRecord()]);
        }

        return null;
    }

    /**
     * A resource page resolves a frame's record key through the resource's
     * scoped binding, never a bare `find()` — after the trait's own guards.
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

        return $this->getResourceInstance()->resolveRecordRouteBinding((string) $recordKey)
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
