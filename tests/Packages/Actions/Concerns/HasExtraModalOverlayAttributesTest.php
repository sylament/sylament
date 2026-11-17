<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("renders its extra overlay attributes on its modal's dialog, a later call replacing them unless it merges", function (): void {
    $modal = fn (Action $action) => $this->renderTwigComponent('SylamentActions:ActionModal', ['action' => $action, 'id' => 'archive-modal'])->crawler();

    expect($modal(Action::make('archive')->extraModalOverlayAttributes(['data-first' => '1'])->extraModalOverlayAttributes(static fn (): array => ['data-second' => '2'], merge: true)))
        ->toContainElement(SupportSelectors::MODAL . '#archive-modal[data-first="1"][data-second="2"]')
        ->and($modal(Action::make('archive')->extraModalOverlayAttributes(['data-first' => '1'])->extraModalOverlayAttributes(['data-second' => '2'])))
        ->toContainElement(SupportSelectors::MODAL . '#archive-modal[data-second="2"]:not([data-first])');
});
