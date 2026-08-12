<?php

declare(strict_types=1);

namespace Sylament\Panels\Controllers;

use LogicException;
use Psr\Container\ContainerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Pages\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

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

        return new Response($this->twig->render('@SylamentPanels/partials/Document/index.html.twig', [
            'panel' => $panel,
            'pageTitle' => $page->getTitle(),
            'componentName' => $pageClass::getComponentName(),
        ]));
    }
}
