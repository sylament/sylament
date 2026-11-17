<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('draws its icon after its label when positioned after, and before it otherwise', function (Closure $action, string $look): void {
    expect($this->renderViewComponent($action()->iconPosition(IconPosition::After)))->toContainElement($look, 'SaveStar')
        ->and($this->renderViewComponent($action()))->toContainElement($look, 'StarSave');
})->with([
    'a badge' => [fn (): Action => Action::make('save')->icon('support:star')->badge(), SupportSelectors::BADGE],
    'a button' => [fn (): Action => Action::make('save')->icon('support:star'), SupportSelectors::BUTTON],
    'a link' => [fn (): Action => Action::make('save')->icon('support:star')->link(), SupportSelectors::LINK],
]);
