<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('renders the title as the toast heading', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Saved')))->toContainElement(Selectors::TITLE, 'Saved');
});
