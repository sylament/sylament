<?php

declare(strict_types=1);

use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('aligns the actions row inside its container, the alignment class on the row alone', function (Alignment|string $alignment): void {
    $header = $this->renderTwigComponent('SylamentPanels:Header', ['heading' => 'Orders', 'actionsAlignment' => $alignment], blocks: ['actions' => '<button type="button">Export</button>'])->crawler();

    expect($header->filter(Selectors::HEADER_ACTIONS_CONTAINER . ' ' . Selectors::HEADER_ACTIONS . SupportSelectors::align('end'))->text())->toBe('Export')
        ->and($header->filter(Selectors::HEADER_ACTIONS_CONTAINER . SupportSelectors::NO_ALIGN))->toHaveCount(1);
})->with([
    'an alignment' => [Alignment::End],
    'its value' => ['end'],
]);

it('leaves an unaligned actions row without an alignment class', function (): void {
    $header = $this->renderTwigComponent('SylamentPanels:Header', ['heading' => 'Orders'], blocks: ['actions' => '<button type="button">Export</button>'])->crawler();

    expect($header->filter(Selectors::HEADER_ACTIONS . SupportSelectors::NO_ALIGN)->text())->toBe('Export');
});

it('marks a header with breadcrumbs, and renders no actions container without actions', function (): void {
    $header = $this->renderTwigComponent('SylamentPanels:Header', ['breadcrumbs' => ['/orders' => 'Orders', 'List']])->crawler();

    expect($header->filter(Selectors::HEADER_WITH_BREADCRUMBS . ' ' . SupportSelectors::BREADCRUMB)->each(fn ($crumb): string => $crumb->text()))->toBe(['Orders', 'List'])
        ->and($header->filter(Selectors::HEADER_ACTIONS_CONTAINER))->toHaveCount(0);
});

it('renders no headings wrapper with nothing to head, and no mark without breadcrumbs', function (): void {
    $header = $this->renderTwigComponent('SylamentPanels:Header', blocks: ['actions' => '<button type="button">Export</button>'])->crawler();

    expect($header->filter(Selectors::HEADER_ACTIONS)->text())->toBe('Export')
        ->and($header->filter('.sy-pa-header-headings'))->toHaveCount(0)
        ->and($header->filter(Selectors::HEADER_WITH_BREADCRUMBS))->toHaveCount(0);
});
