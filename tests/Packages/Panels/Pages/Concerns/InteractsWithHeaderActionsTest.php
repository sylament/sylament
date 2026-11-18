<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Panels\Pages\OrphanPage;
use Sylament\Tests\Fixtures\Panels\Pages\SettingsPage;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it("renders the page's declared header actions in its header, each bound to the page, so one takes the page's default URL", function (): void {
    $header = $this->visit('/pages/settings-page')->filter(Selectors::HEADER_ACTIONS);

    expect($header->filter(ActionsSelectors::trigger('archive')))->toHaveCount(1)
        ->and($header->filter(ActionsSelectors::trigger('ping')))->toHaveCount(1)
        ->and($header->filter(ActionsSelectors::TRIGGER . '[href]')->each(fn (Crawler $link): array => [$link->text(), $link->attr('href')]))->toBe([['Notes', '/pages/notes-page'], ['Help', 'https://example.com/help']])
        ->and($header->filter(SupportSelectors::DROPDOWN . ' ' . ActionsSelectors::trigger('share')))->toHaveCount(1);
});

it('finds a header action a live request mounts before any render, declared by its method, inline or in a group', function (): void {
    $this->createLiveComponent(SettingsPage::class, ['panelId' => 'pages'])
        ->mountAction('archive')
        ->assertNotified('Archived')
        ->mountAction('export')
        ->assertNotified('Exported')
        ->mountAction('ping')
        ->assertNotified('Pong');
});

it('opens a header group with no placement of its own under the end of its trigger, and keeps a placement it names', function (): void {
    $header = $this->visit('/pages/settings-page')->filter(Selectors::HEADER_ACTIONS);

    expect($header->filter(SupportSelectors::DROPDOWN_CONTROLLER)->each(fn (Crawler $dropdown): ?string => $dropdown->attr('data-sylament--support--dropdown-placement-value')))->toBe(['bottom-end', 'top-start']);
});

it("aligns the header actions row as the page declares, never the row's container", function (): void {
    $page = $this->visit('/pages/settings-page');

    expect($page->filter(Selectors::HEADER_ACTIONS . SupportSelectors::align('end')))->toHaveCount(1)
        ->and($page->filter(Selectors::HEADER_ACTIONS_CONTAINER . SupportSelectors::NO_ALIGN))->toHaveCount(1);
});

it('renders a header whose declaration looks an action up by name before the list is bound', function (): void {
    expect($this->createLiveComponent(OrphanPage::class)->render()->crawler()->filter(Selectors::HEADER_ACTIONS . ' ' . ActionsSelectors::trigger('publish')))->toHaveCount(1);
});
