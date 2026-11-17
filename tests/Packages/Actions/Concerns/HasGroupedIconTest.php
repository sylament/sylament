<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('shows its grouped icon only as a dropdown item, and only when it has no icon of its own', function (): void {
    expect($this->renderViewComponent(Action::make('save')->groupedIcon('support:heart')->grouped()))->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'HeartSave')
        ->and($this->renderViewComponent(Action::make('save')->groupedIcon('support:heart')))->toContainElement(SupportSelectors::BUTTON, 'Save')
        ->and($this->renderViewComponent(Action::make('save')->icon('support:star')->groupedIcon('support:heart')->grouped()))->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'StarSave');
});
