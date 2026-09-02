<?php

declare(strict_types=1);

namespace Sylament\Panels\Routing;

use LogicException;
use ReflectionClass;
use ReflectionParameter;
use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\ParentResourceRegistration;
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

                $prefix = $panelPath;
                $parentParams = [];

                foreach ($this->getAncestorLinks($resource) as [$parentResource, $childResource]) {
                    if (! in_array($parentResource::class, $panel->getResources(), true)) {
                        throw new LogicException(sprintf('Resource "%s" nests under "%s", which panel "%s" does not register — register the parent on the panel.', $childResource::class, $parentResource::class, $panel->getId()));
                    }

                    $param = $childResource->getParentRouteParameterName();

                    if ($param === 'record' || in_array($param, $parentParams, true)) {
                        throw new LogicException(sprintf('Nested resource "%s" derives the route parameter "{%s}", which collides with %s.', $childResource::class, $param, $param === 'record' ? 'the record parameter' : 'an ancestor parameter'));
                    }

                    $parentParams[] = $param;
                    $prefix .= '/' . mb_trim($parentResource->getSlug(), '/') . '/{' . $param . '}';
                }

                $resourcePath = $prefix . '/' . mb_trim($resource->getSlug(), '/');
                $extraDefaults = $parentParams === [] ? [] : ['_sylament_parent_params' => $parentParams];

                foreach ($resource->getPages() as $key => $registration) {
                    if (! is_subclass_of($registration->page, Page::class)) {
                        throw new LogicException(sprintf('The getPages() entry "%s" on resource "%s" is not a %s subclass.', $registration->page, $resourceClass, Page::class));
                    }

                    $path = '/' . mb_trim($resourcePath . '/' . mb_ltrim($registration->path, '/'), '/');

                    $this->addRoute($routes, $owners, $routeBaseName . '.' . $key, $path, $panel, $registration->page, sprintf('resource page %s (%s)', $registration->page, $resourceClass), $extraDefaults);
                    $this->trackClassFile($routes, $registration->page);
                }

                $this->trackClassFile($routes, $resourceClass);
            }

            $this->trackClassFile($routes, $this->registry->getProviderClass($panel->getId()));
        }

        return $routes;
    }

    /**
     * The parent → child links above a resource, root-first: every level of the
     * nesting a path has to walk down before it reaches the resource's own slug.
     *
     * @return list<array{Resource, Resource}>
     */
    private function getAncestorLinks(Resource $resource): array
    {
        $links = [];
        $child = $resource;

        while (($registration = $child->getParentResourceRegistration()) instanceof ParentResourceRegistration) {
            $parent = $this->resources->get($registration->parentResource);

            array_unshift($links, [$parent, $child]);

            $child = $parent;
        }

        return $links;
    }

    /**
     * @param array<string, string>                            $owners        route name => the mount that claimed it, accumulated across panels
     * @param class-string<Page>                               $pageClass
     * @param array{_sylament_parent_params?: list<string>}     $extraDefaults
     */
    private function addRoute(RouteCollection $routes, array &$owners, string $name, string $path, Panel $panel, string $pageClass, string $ownerDescription, array $extraDefaults = []): void
    {
        if (isset($owners[$name])) {
            throw new LogicException(sprintf('The route name "%s" is already mounted by %s — %s derives the same name.', $name, $owners[$name], $ownerDescription));
        }

        $owners[$name] = $ownerDescription;

        $this->assertPagePlaceholdersMount($path, $pageClass, $extraDefaults['_sylament_parent_params'] ?? []);

        $routes->add($name, new Route($path, [
            '_controller' => PageController::class,
            '_sylament_panel' => $panel->getId(),
            '_sylament_page' => $pageClass,
            ...$extraDefaults,
        ], methods: ['GET']));
    }

    /**
     * Every placeholder of a mounted path has to reach the page: through a
     * `mount()` parameter, through a public property the component hydrates, or
     * as a parent key the loader folds in itself. Otherwise ux paints the
     * leftover onto the live root as a stray HTML attribute and nothing reports
     * it.
     *
     * @param class-string<Page> $pageClass
     * @param list<string>       $parentParams the keys this mount threads itself
     */
    private function assertPagePlaceholdersMount(string $path, string $pageClass, array $parentParams): void
    {
        if (preg_match_all('/\{([^}]+)\}/', $path, $matches) === 0) {
            return;
        }

        $reflection = new ReflectionClass($pageClass);
        $mountParameters = $reflection->hasMethod('mount')
            ? array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $reflection->getMethod('mount')->getParameters())
            : [];

        foreach ($matches[1] as $parameter) {
            if (in_array($parameter, $parentParams, true)) {
                continue;
            }

            if (in_array($parameter, $mountParameters, true)) {
                continue;
            }

            if ($reflection->hasProperty($parameter) && $reflection->getProperty($parameter)->isPublic()) {
                continue;
            }

            throw new LogicException(sprintf('Page "%s" is mounted at "%s" with the route parameter "{%s}" its mount() does not declare and no public property of that name accepts.', $pageClass, $path, $parameter));
        }
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
