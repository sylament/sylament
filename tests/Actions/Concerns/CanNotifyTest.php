<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;

it('toasts its success title when the action succeeds', function (): void {
    $this->createLiveComponent(ToolbarHost::class)
        ->callAction('archive')
        ->assertNotified(Notification::make()->success()->title('Archived'));
});

it('toasts a persistent danger notification with its failure title when the action fails', function (): void {
    $this->createLiveComponent(ToolbarHost::class)
        ->callAction('restore')
        ->assertNotified(Notification::make()->danger()->persistent()->title('Could not restore'));
});
