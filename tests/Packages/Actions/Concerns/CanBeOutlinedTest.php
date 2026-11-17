<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders the outlined button when outlined', function (): void {
    expect($this->renderViewComponent(Action::make('save')->outlined()))->toContainElement(SupportSelectors::BUTTON . '.sy-outlined', 'Save')
        ->and($this->renderViewComponent(Action::make('save')))->toContainElement(SupportSelectors::BUTTON . ':not(.sy-outlined)', 'Save');
});
