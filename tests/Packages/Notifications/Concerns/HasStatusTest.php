<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('shows the icon of its status in the status colour', function (string $status, string $icon): void {
    expect($this->renderNotification(Notification::make()->title('Check the feed')->{$status}()))
        ->toContainElement(Selectors::ICON . Selectors::color($status), $icon);
})->with([
    'danger' => ['danger', 'x-circle'],
    'info' => ['info', 'information-circle'],
    'success' => ['success', 'check-circle'],
    'warning' => ['warning', 'exclamation-circle'],
]);

it('shows no icon without a status', function (): void {
    expect($this->renderNotification(Notification::make()->title('Check the feed')))->not->toContainElement(Selectors::ICON);
});
