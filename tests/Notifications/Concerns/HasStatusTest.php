<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('shows the icon of its status in the status colour, and no icon without a status', function (): void {
    expect($this->renderNotification(Notification::make()->title('Check the feed')->warning()))
        ->toContainElement(Selectors::ICON . Selectors::color('warning'))
        ->and($this->renderNotification(Notification::make()->title('Check the feed')))
        ->not->toContainElement(Selectors::ICON);
});
