<?php

declare(strict_types=1);

use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Tests\Fixtures\Notifications\NotificationsPageController;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('renders a polite live region that takes the toasts a live request delivers', function (): void {
    $this->startSessionRequest();

    expect($this->renderTwigComponent('SylamentNotifications:Notifications')->crawler())
        ->toContainElement(Selectors::STACK_RECEIVING_TOASTS);
});

it('aligns the stack to the right unless given an alignment, as an enum or its value', function (): void {
    $this->startSessionRequest();

    expect($this->renderTwigComponent('SylamentNotifications:Notifications')->crawler())
        ->toContainElement(Selectors::aligned('right'))
        ->and($this->renderTwigComponent('SylamentNotifications:Notifications', ['alignment' => Alignment::Center])->crawler())
        ->toContainElement(Selectors::aligned('center'))
        ->and($this->renderTwigComponent('SylamentNotifications:Notifications', ['alignment' => 'left'])->crawler())
        ->toContainElement(Selectors::aligned('left'));
});

it('starts the stack at the top unless given a vertical alignment, as an enum or its value', function (): void {
    $this->startSessionRequest();

    expect($this->renderTwigComponent('SylamentNotifications:Notifications')->crawler())
        ->toContainElement(Selectors::verticallyAligned('start'))
        ->and($this->renderTwigComponent('SylamentNotifications:Notifications', ['verticalAlignment' => VerticalAlignment::End])->crawler())
        ->toContainElement(Selectors::verticallyAligned('end'))
        ->and($this->renderTwigComponent('SylamentNotifications:Notifications', ['verticalAlignment' => 'center'])->crawler())
        ->toContainElement(Selectors::verticallyAligned('center'));
});

it('renders the toasts sent before the page renders, once however often the page renders the stack', function (): void {
    $page = $this->visit(NotificationsPageController::URL);

    expect($page->filter(Selectors::STACK . ' ' . Selectors::TITLE)->each(fn (Crawler $title): string => $title->text()))->toBe(['Welcome']);
});
