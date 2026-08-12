<?php

declare(strict_types=1);

namespace Sylament\Panels\Routing;

use LogicException;
use ReflectionClass;
use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\PanelRegistry;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class PanelRouteLoader implements RouteLoaderInterface
{
    public function __construct(
        private PanelRegistry $registry,
    ) {
    }

    public function __invoke(): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->registry->all() as $panel) {
            $panelPath = '/' . mb_trim($panel->getPath(), '/');

            foreach ($panel->getPages() as $pageClass) {
                if (! is_subclass_of($pageClass, Page::class)) {
                    throw new LogicException(sprintf('The pages() entry "%s" on panel "%s" is not a %s subclass.', $pageClass, $panel->getId(), Page::class));
                }

                $path = mb_rtrim($panelPath . $pageClass::getRoutePath(), '/') ?: '/';

                $routes->add($pageClass::getRouteName($panel), new Route($path, [
                    '_controller' => PageController::class,
                    '_sylament_panel' => $panel->getId(),
                    '_sylament_page' => $pageClass,
                ], methods: ['GET']));

                $this->trackClassFile($routes, $pageClass);
            }

            $this->trackClassFile($routes, $this->registry->getProviderClass($panel->getId()));
        }

        return $routes;
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
