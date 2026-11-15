<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('draws a spinner hidden from assistive technology', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:LoadingIndicator')->crawler())
        ->toContainElement('svg.sy.sy-loading-indicator[aria-hidden="true"]');
});

it('sizes the spinner on its own scale, medium unless sized, and an unknown size not at all', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:LoadingIndicator')->crawler())
        ->toContainElement('.sy-loading-indicator' . SupportSelectors::MEDIUM . ':not(' . SupportSelectors::ICON . ')')
        ->and($this->renderTwigComponent('SylamentSupport:LoadingIndicator', ['size' => '2xl'])->crawler())
        ->toContainElement('.sy-loading-indicator.sy-size-2xl')
        ->and($this->renderTwigComponent('SylamentSupport:LoadingIndicator', ['size' => 'huge'])->crawler())
        ->toContainElement('.sy-loading-indicator:not([class*="sy-size-"])');
});
