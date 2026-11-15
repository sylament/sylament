<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('renders the body as the toast text', function (): void {
    expect($this->renderViewComponent(Notification::make()->body('All changes are live.')))
        ->toContainElement(Selectors::BODY, 'All changes are live.');
});
