<?php

declare(strict_types=1);

namespace Sylament\Panels\Controllers;

use LogicException;
use Psr\Container\ContainerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Pages\SimplePage;
use Sylament\Support\Enums\Width;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;

final readonly class PageController
{
    public function __construct(
        private CurrentPanel $currentPanel,
        private ContainerInterface $pages,
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $panel = $this->currentPanel->get()
            ?? throw new LogicException('Panel page routes must carry a `_sylament_panel` default that resolves to a registered panel.');

        $pageClass = $request->attributes->get('_sylament_page');

        if (! is_string($pageClass) || ! is_subclass_of($pageClass, Page::class)) {
            throw new LogicException('Panel page routes must carry a `_sylament_page` default naming a Page subclass.');
        }

        if (! $this->pages->has($pageClass)) {
            throw new LogicException(sprintf('The page %s is not a registered service — register it in the application (or bundle) service configuration so the panels compiler pass can see it.', $pageClass));
        }

        $page = $this->pages->get($pageClass);

        if (! $page instanceof Page) {
            throw new LogicException(sprintf('The page locator returned a non-page service for %s.', $pageClass));
        }

        $layout = $page->getLayout();

        if (! in_array($layout, [Page::LAYOUT, SimplePage::LAYOUT], true)) {
            throw new LogicException(sprintf('The page %s names the layout "%s"; only %s and %s ship — a third layout is a Document template override.', $pageClass, $layout, Page::LAYOUT, SimplePage::LAYOUT));
        }

        $isSimple = $layout === SimplePage::LAYOUT;
        $layoutData = $page->getLayoutData();
        $declaredWidth = $layoutData['maxContentWidth'] ?? null;
        $maxContentWidth = ($declaredWidth instanceof Width || is_string($declaredWidth) ? $declaredWidth : null)
            ?? $page->getMaxContentWidth()
            ?? ($isSimple
                ? ($panel->getSimplePageMaxContentWidth() ?? Width::Large)
                : ($panel->getMaxContentWidth() ?? Width::SevenExtraLarge));

        try {
            $document = $this->twig->render('@SylamentPanels/partials/Document/index.html.twig', [
                'panel' => $panel,
                'pageTitle' => $page->getTitle(),
                'layout' => $layout,
                'maxContentWidthClass' => $this->resolveMaxContentWidthClass($maxContentWidth),
                'hasTopbar' => (bool) ($layoutData['hasTopbar'] ?? true),
                'bodyAttributes' => $page->getExtraBodyAttributes(),
                'componentName' => $pageClass::getComponentName(),
                'componentProps' => $this->getComponentProps($request),
            ]);
        } catch (RuntimeError $runtimeError) {
            $previous = $runtimeError->getPrevious();

            throw $previous instanceof HttpExceptionInterface ? $previous : $runtimeError;
        }

        return new Response($document);
    }

    /**
     * A token becomes its ladder class; a string that is no token is the
     * consumer's own class, emitted as it is — Filament's escape hatch.
     */
    private function resolveMaxContentWidthClass(Width|string $width): string
    {
        if (is_string($width)) {
            $width = Width::tryFrom($width) ?? $width;
        }

        return $width instanceof Width ? 'sy-width-' . $width->value : $width;
    }

    /**
     * @return array<string, mixed>
     */
    private function getComponentProps(Request $request): array
    {
        $parameters = $request->attributes->get('_route_params');

        if (! is_array($parameters)) {
            return [];
        }

        $parentParams = $parameters['_sylament_parent_params'] ?? null;

        if (is_array($parentParams)) {
            $parentRecords = [];

            foreach ($parentParams as $name) {
                if (is_string($name) && is_scalar($parameters[$name] ?? null)) {
                    $parentRecords[$name] = (string) $parameters[$name];
                    unset($parameters[$name]);
                }
            }

            $parameters['parentRecords'] = $parentRecords;
        }

        return array_filter(
            $parameters,
            static fn (int|string $name): bool => is_string($name) && ! str_starts_with($name, '_'),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
