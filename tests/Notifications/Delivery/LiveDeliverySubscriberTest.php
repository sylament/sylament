<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Notifications\NotificationsPageController;
use Sylament\Tests\Fixtures\Notifications\NotifyingHost;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('hands the stack the toasts a live action sends in its own response, once', function (): void {
    $tester = $this->createLiveComponent(NotifyingHost::class)->call('save');

    expect($this->deliveredToasts($tester)->filter(Selectors::TITLE)->each(fn (Crawler $title): string => $title->text()))->toBe(['Saved', 'Synced'])
        ->and($this->deliveredToasts($tester->refresh()))->not->toContainElement(Selectors::TOAST);
});

it("keeps a redirecting action's toast for the page it lands on", function (): void {
    $client = $this::getContainer()->get('test.client');

    $this->createLiveComponent(NotifyingHost::class, client: $client)
        ->call('saveAndLeave')
        ->assertRedirect(NotificationsPageController::URL);

    expect($client->followRedirect())->toContainElement(Selectors::STACK . ' ' . Selectors::TITLE, 'Saved');
});

it('hands a toast sent while the host renders to the next response', function (): void {
    $tester = $this->createLiveComponent(NotifyingHost::class, ['notifiesWhileRendering' => true]);

    expect($this->deliveredToasts($tester))->not->toContainElement(Selectors::TOAST)
        ->and($this->deliveredToasts($tester->refresh()))->toContainElement(Selectors::TITLE, 'Rendered');
});
