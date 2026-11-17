<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('dismisses its modal in the browser in place of mounting, unless told not to close', function (Closure $look, string $selector): void {
    $closing = $this->renderViewComponent($look(Action::make('cancel')->close()));
    $open = $this->renderViewComponent($look(Action::make('cancel')->close(false)));

    expect($closing)->toContainElement(Selectors::TRIGGER . $selector . SupportSelectors::MODAL_DISMISS . Selectors::CLOSE_MARKER)
        ->and($closing->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
        ->and($open)->toContainElement(Selectors::TRIGGER . $selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'mountAction'))
        ->and($open->filter(SupportSelectors::MODAL_DISMISS))->toHaveCount(0)
        ->and($open->filter(Selectors::CLOSE_MARKER))->toHaveCount(0);
})->with('trigger looks');
