<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Notifications\NotifyingHost;
use Sylament\Tests\Fixtures\Notifications\Selectors as NotificationsSelectors;
use Sylament\Tests\Fixtures\Panels\Pages\MistypedWidthPage;
use Sylament\Tests\Fixtures\Panels\Pages\ThirdLayoutPage;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

describe('document', function (): void {
    it("renders an ordinary page inside the panel's shell, at the panel's default width", function (): void {
        $page = $this->visit('/panel-routing/settings');

        expect($page->filter(Selectors::NAVIGATION)->count())->toBe(1)
            ->and($page->filter(Selectors::SIMPLE_LAYOUT)->count())->toBe(0)
            ->and($page->filter(Selectors::contentWidth('7xl') . ' ' . Selectors::PAGE)->count())->toBe(1);
    });

    it("carries a simple page's attributes on the document body", function (): void {
        expect($this->visit('/panel-routing/sign-in')->filter('body')->attr('data-screen'))->toBe('sign-in');
    });

    it('stamps the theme from storage in a head script, the one script the document writes', function (): void {
        $page = $this->visit('/panel-routing');

        expect($page->filter('head script')->reduce(fn ($script): bool => str_contains($script->text(), 'sylament.theme'))->count())->toBe(1)
            ->and($page->filter('body script')->count())->toBe(0);
    });

    it('writes the theme script ahead of the stylesheet, so the first paint is already themed', function (): void {
        $this::bootKernel(['environment' => 'registered_colors']);

        $head = $this->visit('/panel-routing')->filter('head > script, head > style')->each(
            fn (Crawler $element): string => $element->nodeName() === 'style' ? 'style' : (str_contains($element->text(), 'sylament.theme') ? 'theme' : 'other script'),
        );

        expect(array_slice($head, 0, 2))->toBe(['theme', 'style']);
    });

    it("shows a toast flashed by an earlier request in the document's outlet beside the shell, once", function (): void {
        $client = $this::getContainer()->get('test.client');

        $this->createLiveComponent(NotifyingHost::class, client: $client)->call('saveAndLeave');

        expect($this->visit('/admin/articles', client: $client))->toContainElement('body > ' . NotificationsSelectors::STACK . ' ' . NotificationsSelectors::TITLE, 'Saved')
            ->and($this->visit('/admin/articles', client: $client)->filter(NotificationsSelectors::TOAST)->count())->toBe(0);
    });
});

describe('props', function (): void {
    it("hands the page its route's own parameters, never the loader's defaults", function (): void {
        $this->seed($article = new Article('Draft'));

        $page = $this->visit("/admin/articles/{$article->id}/edit");

        expect($this->renderedLiveProps($page))->toHaveKey('record', (string) $article->id)
            ->and($page->filter(Selectors::PAGE)->attr('_sylament_page'))->toBeNull()
            ->and($page->filter(Selectors::PAGE)->attr('_sylament_panel'))->toBeNull();
    });
});

describe('refusals', function (): void {
    it('answers an HTTP exception the page throws at mount as itself, and any other as a server error', function (string $uri, int $status): void {
        $client = $this::getContainer()->get('test.client');
        $client->request('GET', $uri);

        expect($client->getResponse()->getStatusCode())->toBe($status);
    })->with([
        'a record no row holds' => ['/admin/articles/999999/edit', 404],
        'a failure with no HTTP meaning' => ['/panel-branded/faulty', 500],
    ]);

    it('refuses a page that names a third layout or a width of no shipped type', function (string $uri, string $message): void {
        expect(fn () => $this->visit($uri))->toThrow(LogicException::class, $message);
    })->with([
        'a third layout' => ['/panel-routing/kiosk', sprintf('The page %s names the layout "App:KioskLayout"; only SylamentPanels:Layout and SylamentPanels:SimpleLayout ship', ThirdLayoutPage::class)],
        'a width of no shipped type' => ['/panel-routing/mistyped-width', sprintf('The page %s names a int under `maxContentWidth` in its layout data;', MistypedWidthPage::class)],
    ]);

    it('refuses a page that is no registered service, naming the registration to add', function (): void {
        $unregistered = new class extends Page {
        };
        $request = new Request(attributes: ['_sylament_panel' => 'panel-routing', '_sylament_page' => $unregistered::class]);
        $this::getContainer()->get('request_stack')->push($request);

        expect(fn () => $this::getContainer()->get(PageController::class)($request))
            ->toThrow(LogicException::class, sprintf('The page %s is not a registered service — register it in the application (or bundle) service configuration', $unregistered::class));
    });
});
