<?php

declare(strict_types=1);

use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Panels\Pages\MonthlyReportPage;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;
use Sylament\Tests\Fixtures\Panels\Pages\OrphanPage;
use Sylament\Tests\Fixtures\Panels\Pages\SettingsPage;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

describe('title and heading', function (): void {
    it('titles the document and heads the page with its humanised class name', function (): void {
        $page = $this->visit('/pages/notes-page');

        expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Notes Page')
            ->and($page->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page')
            ->and($page->filter('p.sy-pa-header-subheading'))->toHaveCount(0);
    });

    it('replaces both with a title, and only the heading with a heading of its own, under which a subheading reads', function (): void {
        $report = $this->visit('/pages/reports/monthly');
        $settings = $this->visit('/pages/settings-page');

        expect($report->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Monthly report')
            ->and($report->filter(Selectors::PAGE_HEADING)->text())->toBe('Monthly report')
            ->and($settings->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Site settings')
            ->and($settings->filter(Selectors::PAGE_HEADING)->text())->toBe('Configure the site')
            ->and($settings->filter('p.sy-pa-header-subheading')->text())->toBe('Changes apply to every visitor.');
    });
});

describe('breadcrumbs', function (): void {
    it('renders no trail by default, and the crumbs a page returns, a string key as a link', function (): void {
        $notes = $this->visit('/pages/notes-page');
        $settings = $this->visit('/pages/settings-page');

        expect($notes->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page')
            ->and($notes->filter(SupportSelectors::BREADCRUMBS))->toHaveCount(0)
            ->and($notes->filter(Selectors::HEADER_WITH_BREADCRUMBS))->toHaveCount(0)
            ->and($settings->filter(SupportSelectors::LINKED_BREADCRUMB)->each(fn (Crawler $crumb): array => [$crumb->text(), $crumb->attr('href')]))->toBe([['Pages', '/pages/notes-page']])
            ->and($settings->filter(SupportSelectors::UNLINKED_BREADCRUMB)->each(fn (Crawler $crumb): string => $crumb->text()))->toBe(['Settings'])
            ->and($settings->filter(Selectors::HEADER_WITH_BREADCRUMBS))->toHaveCount(1);
    });
});

describe('navigation', function (): void {
    it('shows a page on its sidebar item by its label, icon, sort and badge', function (): void {
        $notes = $this->visit('/pages/notes-page');
        $settings = $this->visit('/pages/settings-page');
        $report = $this->visit('/pages/reports/monthly');

        $item = fn (Crawler $page, string $href): Crawler => $page->filter(Selectors::NAVIGATION . " a[href=\"{$href}\"]");
        $ungrouped = $notes->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP . ':not([data-group-label]) ' . Selectors::NAVIGATION_ITEM_LABEL);

        expect($ungrouped->each(fn (Crawler $label): string => $label->text()))->toBe(['Notes Page', 'Reports', 'Templated Page'])
            ->and($item($notes, '/pages/settings-page')->filter(Selectors::NAVIGATION_ITEM_LABEL)->text())->toBe('Site settings')
            ->and($item($notes, '/pages/settings-page')->filter('svg')->text())->toBe('home')
            ->and($item($settings, '/pages/settings-page')->filter('svg')->text())->toBe('rectangle-stack')
            ->and($item($report, '/pages/reports/monthly')->filter('svg')->text())->toBe('home')
            ->and($item($notes, '/pages/notes-page')->filter('svg'))->toHaveCount(0)
            ->and($item($notes, '/pages/settings-page')->filter(SupportSelectors::BADGE . SupportSelectors::color('danger'))->text())->toBe('3')
            ->and($item($notes, '/pages/notes-page')->filter(SupportSelectors::BADGE))->toHaveCount(0);
    });

    it('puts a page naming a navigation group under that group, and one naming none among the ungrouped', function (): void {
        $page = $this->visit('/pages/notes-page');

        expect($page->filter(Selectors::navigationGroup('Configuration') . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text()))->toBe(['Site settings'])
            ->and($page->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP . ':not([data-group-label]) ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text()))->toContain('Notes Page')->not->toContain('Site settings');
    });

    it('leaves a page that opts out of navigation off the sidebar and a sub-navigation band, while its route still answers', function (): void {
        $settings = $this->visit('/pages/settings-page');
        $labels = fn (string $scope): array => $settings->filter($scope . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text());

        expect($labels(Selectors::NAVIGATION))->toContain('Notes Page', 'Site settings')->not->toContain('Standalone Page')
            ->and($labels(Selectors::SUB_NAVIGATION))->toBe(['Notes Page'])
            ->and($this->visit('/pages/standalone-page')->filter(Selectors::PAGE))->toHaveCount(1);
    });

    it("links a page's item to its route in the panel showing it, marking it current only on that page", function (): void {
        $notes = $this->visit('/pages/notes-page');
        $settings = $this->visit('/pages/settings-page');
        $shared = $this->visit('/pages-shared/notes-page');

        expect($notes->filter(Selectors::ACTIVE_NAVIGATION_ITEM)->attr('href'))->toBe('/pages/notes-page')
            ->and($settings->filter(Selectors::ACTIVE_NAVIGATION_ITEM)->attr('href'))->toBe('/pages/settings-page')
            ->and($settings->filter(Selectors::NAVIGATION . ' a[href="/pages/notes-page"]:not([aria-current])'))->toHaveCount(1)
            ->and($shared->filter(Selectors::ACTIVE_NAVIGATION_ITEM)->attr('href'))->toBe('/pages-shared/notes-page');
    });
});

describe('routes', function (): void {
    it('routes a page at its kebab-cased class name, or at its slug, slash and all', function (): void {
        expect($this->visit('/pages/notes-page')->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page')
            ->and($this->visit('/pages/reports/monthly')->filter(Selectors::PAGE_HEADING)->text())->toBe('Monthly report');
    });

    it("names a page's route after its panel and its slug, a slash read as a dot", function (): void {
        $router = $this::getContainer()->get(UrlGeneratorInterface::class);
        $panels = $this::getContainer()->get(PanelRegistry::class);

        expect($router->generate('sylament.pages.pages.reports.monthly'))->toBe('/pages/reports/monthly')
            ->and($router->generate(MonthlyReportPage::getRouteName($panels->get('pages'))))->toBe('/pages/reports/monthly')
            ->and($router->generate(NotesPage::getRouteName($panels->get('pages-shared'))))->toBe('/pages-shared/notes-page');
    });
});

describe('body', function (): void {
    it('renders through the page chrome, or through a template of its own extending it', function (): void {
        $notes = $this->visit('/pages/notes-page');
        $templated = $this->visit('/pages/templated-page');

        expect($notes->filter(Selectors::PAGE . ' .sy-pa-page-header-main-ctn ' . Selectors::PAGE_CONTENT))->toHaveCount(1)
            ->and($templated->filter(Selectors::PAGE . ' .sy-pa-page-header-main-ctn ' . Selectors::PAGE_CONTENT)->text())->toBe('The Spring edition');
    });

    it("fills a child template's header, content and footer blocks from the page and the child's own variables", function (): void {
        $notes = $this->visit('/pages/notes-page');
        $templated = $this->visit('/pages/templated-page');
        $chrome = $templated->filter(Selectors::PAGE . ' .sy-pa-page-header-main-ctn');

        expect($chrome->filter('p')->first()->text())->toBe('Welcome to The Spring edition')
            ->and($chrome->filter(Selectors::PAGE_CONTENT)->text())->toBe('The Spring edition')
            ->and($chrome->filter('footer')->text())->toBe('Read Templated Page again next season.')
            ->and($templated->filter(Selectors::PAGE_HEADING))->toHaveCount(0)
            ->and($notes->filter(Selectors::PAGE . ' header.sy-pa-header'))->toHaveCount(1)
            ->and($notes->filter(Selectors::PAGE . ' footer'))->toHaveCount(0);
    });

    it('renders its default header only when it has something to head', function (): void {
        $page = $this->visit('/pages/standalone-page');

        expect($page->filter(Selectors::PAGE . ' ' . Selectors::PAGE_CONTENT))->toHaveCount(1)
            ->and($page->filter(Selectors::PAGE . ' header.sy-pa-header'))->toHaveCount(0);
    });

    it('renders the schema its content() builds inside the content area, which is empty otherwise', function (): void {
        expect($this->visit('/pages/settings-page')->filter(Selectors::PAGE . ' ' . Selectors::PAGE_CONTENT)->text())->toBe('Every setting applies to the whole site.')
            ->and($this->visit('/pages/notes-page')->filter(Selectors::PAGE . ' ' . Selectors::PAGE_CONTENT)->text())->toBe('');
    });

    it('builds its content only to render it, so a live request that redirects without rendering builds none', function (): void {
        $this->visit('/pages/settings-page');
        $onArrival = $this::getContainer()->get(HookLog::class)->hooks;

        $this->createLiveComponent(SettingsPage::class, ['panelId' => 'pages'])
            ->mountAction('leave')
            ->assertRedirect('/pages/notes-page');

        expect($onArrival)->toBe(['content'])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(['leave']);
    });

    it('puts the classes it declares on its root beside the shared ones', function (): void {
        expect($this->visit('/pages/settings-page')->filter(Selectors::PAGE . '.sy-pa-settings-page'))->toHaveCount(1)
            ->and($this->visit('/pages/notes-page')->filter(Selectors::PAGE)->attr('class'))->toBe('sy sy-pa-page');
    });

    it('puts the attributes it declares on the document body', function (): void {
        expect($this->visit('/pages/settings-page')->filter('body[data-page="settings"]'))->toHaveCount(1)
            ->and($this->visit('/pages/notes-page')->filter('body')->attr('data-page'))->toBeNull();
    });
});

describe('shell', function (): void {
    it('renders in the panel shell, and in the simple shell when it names it', function (): void {
        $notes = $this->visit('/pages/notes-page');
        $standalone = $this->visit('/pages/standalone-page');

        expect($notes->filter(Selectors::NAVIGATION))->toHaveCount(1)
            ->and($notes->filter(Selectors::TOPBAR))->toHaveCount(1)
            ->and($notes->filter(Selectors::SIMPLE_LAYOUT))->toHaveCount(0)
            ->and($standalone->filter('body ' . Selectors::SIMPLE_LAYOUT . ' ' . Selectors::PAGE))->toHaveCount(1)
            ->and($standalone->filter(Selectors::NAVIGATION))->toHaveCount(0);
    });

    it('hands its layout the data it declares, a simple shell without its topbar', function (): void {
        $page = $this->visit('/pages/standalone-page');

        expect($page->filter(Selectors::SIMPLE_LAYOUT))->toHaveCount(1)
            ->and($page->filter(Selectors::USER_MENU_TRIGGER))->toHaveCount(0)
            ->and($this->visit('/pages/notes-page')->filter(Selectors::USER_MENU_TRIGGER))->toHaveCount(1);
    });

    it("widens its content to its own width over the panel's, a width token or a class of its own", function (): void {
        expect($this->visit('/pages/notes-page')->filter(Selectors::contentWidth('5xl')))->toHaveCount(1)
            ->and($this->visit('/pages/settings-page')->filter(Selectors::contentWidth('4xl')))->toHaveCount(1)
            ->and($this->visit('/pages/templated-page')->filter(Selectors::MAIN . '.reading-width')->attr('class'))->not->toContain('sy-width-');
    });
});

describe('hosts', function (): void {
    it('calls a header action a plain page declares, and shows what it sent', function (): void {
        $this->createLiveComponent(SettingsPage::class, ['panelId' => 'pages'])
            ->mountAction('ping')
            ->assertNotified('Pong');
    });

    it('round-trips a page that declares no schema, which has none to fill', function (): void {
        $page = $this->createLiveComponent(NotesPage::class)->call('refresh');

        expect($page->render()->crawler()->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page')
            ->and(fn () => $page->fillForm(['title' => 'Draft']))->toThrow(LogicException::class, 'declares no schema to test');
    });

    it('redraws unchanged on refresh, called or dispatched, keeping a mounted action', function (): void {
        $refreshed = $this->createLiveComponent(SettingsPage::class, ['panelId' => 'pages'])->mountAction('reset')->call('refresh')->render()->crawler();

        expect($refreshed->filter(Selectors::PAGE_HEADING)->text())->toBe('Configure the site')
            ->and($refreshed->filter(Selectors::HEADER_ACTIONS . ' ' . ActionsSelectors::trigger('reset')))->toHaveCount(1)
            ->and($refreshed->filter(ActionsSelectors::mountedModal(0) . ActionsSelectors::OPEN))->toHaveCount(1)
            ->and($this->createLiveComponent(NotesPage::class)->emit('refresh-page')->render()->crawler()->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page');
    });
});

describe('panel', function (): void {
    it('answers for the panel it was opened in, else the default panel', function (): void {
        $subheading = fn (array $data): string => $this->createLiveComponent(OrphanPage::class, $data)->render()->crawler()->filter('p.sy-pa-header-subheading')->text();

        expect($subheading([]))->toBe('Opened in the admin panel')
            ->and($subheading(['panelId' => 'pages']))->toBe('Opened in the pages panel');
    });

    it('names its class when built outside the container, with no panel to answer for', function (): void {
        expect(fn (): Panel => new NotesPage()->getPanel())->toThrow(LogicException::class, 'The page [Sylament\Tests\Fixtures\Panels\Pages\NotesPage] was built outside the container');
    });

    it('carries the panel it was opened in, so a mounted action rebuilds the header for that panel', function (): void {
        $page = $this->createLiveComponent(SettingsPage::class, ['panelId' => 'pages'])->mountAction('reset')->call('refresh');

        expect($page->render()->crawler()->filter(Selectors::HEADER_ACTIONS . ' a')->attr('href'))->toBe('/pages/notes-page')
            ->and($this->renderedLiveProps($this->visit('/pages/templated-page'))['panelId'])->toBe('pages');
    });
});
