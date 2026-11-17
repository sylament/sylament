<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Notifications\NotificationOverrideHost;
use Sylament\Tests\Fixtures\Tables\PartlyFailingBulkTable;

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

describe('success notification', function (): void {
    it('toasts the notification it is given in place of the one it builds, or what its closure makes of the built one', function (string $action, Closure $notification): void {
        $this->createLiveComponent(NotificationOverrideHost::class)
            ->callAction($action)
            ->assertNotified($notification());
    })->with([
        'a notification' => ['replaced', static fn (): Notification => Notification::make()->info()->title('Filed')],
        'a closure re-titling it' => ['retitled', static fn (): Notification => Notification::make()->success()->title('Saved twice')->body('Both copies')],
        'a closure answering none' => ['kept', static fn (): Notification => Notification::make()->success()->title('Saved')],
        'a closure titling an untitled one' => ['titled', static fn (): Notification => Notification::make()->success()->title('Saved at last')],
    ]);

    it('toasts nothing when it has no title, its notification is switched off, or its closure takes the title away', function (string $action): void {
        $this->createLiveComponent(NotificationOverrideHost::class)
            ->callAction($action)
            ->assertNotNotified();
    })->with([
        'no title' => ['untitled'],
        'switched off' => ['silenced'],
        'its title taken away' => ['untitledByClosure'],
    ]);
});

describe('failure notification', function (): void {
    it('toasts the failure notification it is given in place of the one it builds, or what its closure makes of the built one', function (string $action, Closure $notification): void {
        $this->createLiveComponent(NotificationOverrideHost::class)
            ->callAction($action)
            ->assertNotified($notification());
    })->with([
        'a notification' => ['failureReplaced', static fn (): Notification => Notification::make()->warning()->title('Filed for later')],
        'a closure re-titling it' => ['failureRetitled', static fn (): Notification => Notification::make()->danger()->title('Not saved twice')->persistent()->body('Neither copy')],
        'a closure answering none' => ['failureKept', static fn (): Notification => Notification::make()->danger()->title('Not saved')->persistent()],
    ]);

    it('toasts no failure notification when it has no title or its notification is switched off', function (string $action): void {
        $this->createLiveComponent(NotificationOverrideHost::class)
            ->callAction($action)
            ->assertNotNotified();
    })->with([
        'no title' => ['failureUntitled'],
        'switched off' => ['failureSilenced'],
    ]);

    it('titles a bulk failure and its notification from the counts of the selected records, warning when some succeeded', function (string $action, Closure $notification): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), $rug = new Product('Rug'));

        $this->createLiveComponent(PartlyFailingBulkTable::class)
            ->selectTableRecords([$lamp, $vase, $rug])
            ->callAction(TestAction::make($action)->table()->bulk())
            ->assertNotified($notification());
    })->with([
        'some succeeded' => ['archive', static fn (): Notification => Notification::make()->warning()->title('2 of 3 not done')->persistent()->body('1 done and 2 not, of 3')],
        'none succeeded' => ['purge', static fn (): Notification => Notification::make()->danger()->title('None of 3 done')->persistent()->body('0 done and 3 not, of 3, every one failed')],
    ]);

    it('toasts danger for a failure that reports a successful count without reading selected records', function (): void {
        $this->createLiveComponent(NotificationOverrideHost::class)
            ->callAction('partlyFailed')
            ->assertNotified(Notification::make()->danger()->title('Partly saved')->persistent());
    });
});
