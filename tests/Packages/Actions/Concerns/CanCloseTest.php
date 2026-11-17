<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('dismisses its modal in the browser in place of mounting, unless told not to close', function (Closure $look, string $selector): void {
    $closing = $this->renderViewComponent($look(Action::make('cancel')->close()));
    $open = $this->renderViewComponent($look(Action::make('cancel')->close(false)));

    expect($closing)->toContainElement(Selectors::TRIGGER . $selector . Selectors::DISMISS . Selectors::CLOSE_MARKER)
        ->and($closing->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
        ->and($open)->toContainElement(Selectors::TRIGGER . $selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'mountAction'))
        ->and($open->filter(Selectors::DISMISS))->toHaveCount(0)
        ->and($open->filter(Selectors::CLOSE_MARKER))->toHaveCount(0);
})->with([
    'a badge' => [static fn (Action $action): Action => $action->badge(), SupportSelectors::BADGE],
    'a button' => [static fn (Action $action): Action => $action, SupportSelectors::BUTTON],
    'a dropdown item' => [static fn (Action $action): Action => $action->grouped(), SupportSelectors::DROPDOWN_ITEM],
    'an icon button' => [static fn (Action $action): Action => $action->iconButton(), SupportSelectors::ICON_BUTTON],
    'a link' => [static fn (Action $action): Action => $action->link(), SupportSelectors::LINK],
]);
