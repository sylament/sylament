<?php

declare(strict_types=1);

namespace Sylament\Panels\Routing;

use LogicException;
use ReflectionClass;
use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class PanelRouteLoader implements RouteLoaderInterface
{
    public function __construct(
        private PanelRegistry $registry,
        private ResourceRegistry $resources,
    ) {
    }

    public function __invoke(): RouteCollection
    {
        $routes = new RouteCollection();
        $owners = [];

        foreach ($this->registry->all() as $panel) {
            $panelPath = '/' . mb_trim($panel->getPath(), '/');

            foreach ($panel->getPages() as $pageClass) {
                if (! is_subclass_of($pageClass, Page::class)) {
                    throw new LogicException(sprintf('The pages() entry "%s" on panel "%s" is not a %s subclass.', $pageClass, $panel->getId(), Page::class));
                }

                $path = '/' . mb_trim($panelPath . $pageClass::getRoutePath(), '/');

                $this->addRoute($routes, $owners, $pageClass::getRouteName($panel), $path, $panel, $pageClass, sprintf('page %s', $pageClass));
                $this->trackClassFile($routes, $pageClass);
            }

            foreach ($panel->getResources() as $resourceClass) {
                if (! is_subclass_of($resourceClass, Resource::class)) {
                    throw new LogicException(sprintf('The resources() entry "%s" on panel "%s" is not a %s subclass.', $resourceClass, $panel->getId(), Resource::class));
                }

                $resource = $this->resources->get($resourceClass);
                $routeBaseName = $resource->getRouteBaseName($panel);
                $resourcePath = $panelPath . '/' . mb_trim($resource->getSlug(), '/');

                foreach ($resource->getPages() as $key => $registration) {
                    if (! is_subclass_of($registration->page, Page::class)) {
                        throw new LogicException(sprintf('The getPages() entry "%s" on resource "%s" is not a %s subclass.', $registration->page, $resourceClass, Page::class));
                    }

                    $path = '/' . mb_trim($resourcePath . $registration->path, '/');

                    $this->addRoute($routes, $owners, $routeBaseName . '.' . $key, $path, $panel, $registration->page, sprintf('resource page %s (%s)', $registration->page, $resourceClass));
                    $this->trackClassFile($routes, $registration->page);
                }

                $this->trackClassFile($routes, $resourceClass);
            }

            $this->trackClassFile($routes, $this->registry->getProviderClass($panel->getId()));
        }

        return $routes;
    }

    /**
     * @param array<string, string> $owners    route name => the mount that claimed it, accumulated across panels
     * @param class-string<Page>    $pageClass
     */
    private function addRoute(RouteCollection $routes, array &$owners, string $name, string $path, Panel $panel, string $pageClass, string $ownerDescription): void
    {
        if (isset($owners[$name])) {
            throw new LogicException(sprintf('The route name "%s" is already mounted by %s — %s derives the same name.', $name, $owners[$name], $ownerDescription));
        }

        $owners[$name] = $ownerDescription;

        $routes->add($name, new Route($path, [
            '_controller' => PageController::class,
            '_sylament_panel' => $panel->getId(),
            '_sylament_page' => $pageClass,
        ], methods: ['GET']));
    }

    /**
     * @param class-string $class
     */
    private function trackClassFile(RouteCollection $routes, string $class): void
    {
        $file = new ReflectionClass($class)->getFileName();

        if ($file !== false) {
            $routes->addResource(new FileResource($file));
        }
    }
}
