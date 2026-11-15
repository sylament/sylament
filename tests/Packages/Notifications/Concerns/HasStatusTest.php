<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('shows the icon of its status in the status colour', function (string $status, string $icon): void {
    expect($this->renderViewComponent(Notification::make()->title('Check the feed')->{$status}()))
        ->toContainElement(Selectors::ICON . SupportSelectors::color($status), $icon);
})->with([
    'danger' => ['danger', 'x-circle'],
    'info' => ['info', 'information-circle'],
    'success' => ['success', 'check-circle'],
    'warning' => ['warning', 'exclamation-circle'],
]);

it('shows no icon without a status', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Check the feed')))->not->toContainElement(Selectors::ICON);
});
