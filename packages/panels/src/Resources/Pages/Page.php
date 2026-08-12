<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Pages\Page as BasePage;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Symfony\Contracts\Service\Attribute\Required;

abstract class Page extends BasePage
{
    /**
     * @var class-string<Resource>
     */
    protected static string $resource;

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

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [];
    }
}
