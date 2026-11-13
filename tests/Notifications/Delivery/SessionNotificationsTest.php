<?php

declare(strict_types=1);

use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

it("skips a flashed entry that is not a toast's array and delivers the rest", function (): void {
    $flashBag = $this->startSessionRequest()->getFlashBag();
    $flashBag->add(SessionNotifications::FLASH_KEY, 'Saved');
    $flashBag->add(SessionNotifications::FLASH_KEY, ['Saved']);
    Notification::make()->title('Kept')->send();

    $stack = $this->renderTwigComponent('SylamentNotifications:Notifications')->crawler();

    expect($stack->filter(Selectors::TITLE)->each(fn (Crawler $title): string => $title->text()))->toBe(['Kept']);
});

it('refuses a session without a flash bag, naming its class', function (): void {
    $session = $this->startSessionRequest($this->createStub(SessionInterface::class));

    expect(fn (): Notification => Notification::make()->title('Saved')->send())
        ->toThrow(LogicException::class, sprintf('Notifications need a flash-bag-aware session, got "%s".', $session::class));
});
