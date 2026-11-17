<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("renders its extra window attributes on its modal's window, a later call replacing them unless it merges", function (): void {
    $modal = fn (Action $action) => $this->renderTwigComponent('SylamentActions:ActionModal', ['action' => $action, 'id' => 'archive-modal'])->crawler();

    expect($modal(Action::make('archive')->extraModalWindowAttributes(['data-first' => '1'])->extraModalWindowAttributes(static fn (): array => ['data-second' => '2'], merge: true)))
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '[data-first="1"][data-second="2"]')
        ->and($modal(Action::make('archive')->extraModalWindowAttributes(['data-first' => '1'])->extraModalWindowAttributes(['data-second' => '2'])))
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '[data-second="2"]:not([data-first])');
});
