<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\ProductTable;

it('matches a sent notification by its configuration, and consumes what it read', function (): void {
    $this->seed($gone = new Product('Gone'));

    $this->createLiveComponent(ProductTable::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($gone))
        ->assertNotified(Notification::make()->success()->title('Deleted'))
        ->assertNotNotified();
});

it('fails asserting a notification when none was sent', function (): void {
    expect(fn () => $this->createLiveComponent(ProductTable::class)->assertNotified())
        ->toThrow(AssertionFailedError::class, 'A notification was expected but none were sent.');
});

it('fails asserting a notification that was not sent', function (Notification|string $notification): void {
    $this->seed($gone = new Product('Gone'));

    $tester = $this->createLiveComponent(ProductTable::class)->callAction(TestAction::make(DeleteAction::class)->table($gone));

    expect(fn () => $tester->assertNotified($notification))
        ->toThrow(AssertionFailedError::class, 'A notification was not sent');
})->with([
    'a title' => ['Saved'],
    'a configuration' => [fn (): Notification => Notification::make()->danger()->title('Deleted')],
]);

it('fails asserting that no notification was sent', function (Notification|string|null $notification, string $message): void {
    $this->seed($gone = new Product('Gone'));

    $tester = $this->createLiveComponent(ProductTable::class)->callAction(TestAction::make(DeleteAction::class)->table($gone));

    expect(fn () => $tester->assertNotNotified($notification))
        ->toThrow(AssertionFailedError::class, $message);
})->with([
    'any' => [null, 'No notification was expected but at least one was sent.'],
    'a title' => ['Deleted', 'The notification with the given title was sent'],
    'a configuration' => [fn (): Notification => Notification::make()->success()->title('Deleted'), 'The notification with the given configuration was sent'],
]);
