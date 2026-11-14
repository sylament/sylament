<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Notifications\Selectors;

it('shows the icon an application registers for a toast alias', function (?string $status, string $alias, string $icon): void {
    $this::getContainer()->get(IconManager::class)->register([$alias => 'notifications:bell']);

    expect($this->renderNotification(Notification::make()->title('Saved')->status($status)))->toContainElement($icon, 'Bell');
})->with([
    'the close button' => [null, 'notification.close-button', Selectors::CLOSE_BUTTON . ' svg'],
    'danger' => ['danger', 'notification.danger', Selectors::ICON],
    'info' => ['info', 'notification.info', Selectors::ICON],
    'success' => ['success', 'notification.success', Selectors::ICON],
    'warning' => ['warning', 'notification.warning', Selectors::ICON],
]);
