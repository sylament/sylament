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
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

abstract class Page extends BasePage implements InteractsWithActions
{
    use HasActions;

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

    public function getDefaultActionUrl(Action $action): ?string
    {
        $resource = $this->getResourceInstance();

        if ($action instanceof CreateAction && $resource->hasPage('create')) {
            return $resource->getUrl('create');
        }

        if ($action instanceof EditAction && $resource->hasPage('edit') && ! $this instanceof EditRecord && $action->getRecord() !== null) {
            return $resource->getUrl('edit', ['record' => $action->getRecord()]);
        }

        return null;
    }
}
