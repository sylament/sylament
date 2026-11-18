<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Panels\Pages\CardSimplePage;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('shell', function (): void {
    it("answers its route with a document it titles, rendered in the simple shell where an ordinary page of its panel gets the panel's", function (): void {
        $card = $this->visit('/simple-shapes/card-simple-page');
        $notes = $this->visit('/simple-shapes/notes-page');

        expect($card->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Welcome back')
            ->and($card->filter(Selectors::SIMPLE_LAYOUT . ' ' . Selectors::SIMPLE_PAGE))->toHaveCount(1)
            ->and($card->filter(Selectors::NAVIGATION))->toHaveCount(0)
            ->and($card->filter(Selectors::TOPBAR))->toHaveCount(0)
            ->and($notes->filter(Selectors::NAVIGATION))->toHaveCount(1)
            ->and($notes->filter(Selectors::TOPBAR))->toHaveCount(1)
            ->and($notes->filter(Selectors::SIMPLE_LAYOUT))->toHaveCount(0);
    });

    it('stays out of the sidebar its panel draws for an ordinary page', function (): void {
        $labels = $this->visit('/simple-shapes/notes-page')->filter(Selectors::NAVIGATION_ITEM . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text());

        expect($labels)->toContain('Notes Page')->not->toContain('Welcome back');
    });
});

describe('card', function (): void {
    it('heads its card with its heading and subheading above the content it builds', function (): void {
        $page = $this->visit('/simple-shapes/card-simple-page')->filter(Selectors::SIMPLE_PAGE);

        expect($page->filter(Selectors::SIMPLE_HEADER . ' ' . Selectors::PAGE_HEADING)->text())->toBe('Welcome back')
            ->and($page->filter(Selectors::SIMPLE_HEADER . ' p')->text())->toBe('Sign in to continue.')
            ->and($page->filter('.sy-pa-simple-page-content')->text())->toContain('Your account is ready.');
    });

    it('heads its card with the brand logo even with no heading, and renders no header with no logo and nothing to head', function (): void {
        $card = $this->visit('/simple-knobs/card-simple-page');
        $logoOnly = $this->visit('/simple-knobs/token-width-simple-page');
        $bare = $this->visit('/simple-knobs/bare-simple-page');

        expect($card->filter(Selectors::SIMPLE_HEADER . ' ' . Selectors::LOGO))->toHaveCount(2)
            ->and($logoOnly->filter(Selectors::SIMPLE_HEADER . ' ' . Selectors::LOGO))->toHaveCount(2)
            ->and($logoOnly->filter(Selectors::PAGE_HEADING))->toHaveCount(0)
            ->and($bare->filter(Selectors::SIMPLE_PAGE))->toHaveCount(1)
            ->and($bare->filter(Selectors::SIMPLE_HEADER))->toHaveCount(0)
            ->and($bare->filter(Selectors::LOGO))->toHaveCount(0);
    });

    it('hosts the actions its content declares, opening their modal inside the card page', function (): void {
        $page = $this->createLiveComponent(CardSimplePage::class, ['panelId' => 'simple-shapes'])
            ->mountAction(TestAction::make('resend')->schemaComponent(schema: 'content'));

        expect($page->render()->crawler()->filter(Selectors::SIMPLE_PAGE . ' ' . ActionsSelectors::mountedModal(0) . ActionsSelectors::OPEN))->toHaveCount(1);

        $page->callMountedAction()->assertNotified('Link sent');
    });
});

describe('unsaved changes', function (): void {
    it("guards its panel's unsaved action changes from an element inside its live root, which keeps its own controller", function (): void {
        $guarded = $this->visit('/simple-knobs/card-simple-page');
        $unguarded = $this->visit('/simple-shapes/card-simple-page');

        expect($this->unsavedChangesGuard($guarded->filter(Selectors::SIMPLE_PAGE)))->toBe(['savedHash' => '', 'dataModel' => '', 'guardActions' => 'true'])
            ->and($guarded->filter(Selectors::SIMPLE_PAGE)->attr('data-controller'))->toBe('live')
            ->and($unguarded->filter(Selectors::SIMPLE_PAGE))->toHaveCount(1)
            ->and($this->unsavedChangesGuard($unguarded))->toBeNull();
    });
});
