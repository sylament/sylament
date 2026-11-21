<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\Translation\TranslatableMessage;

it('renders the body as the toast text', function (): void {
    expect($this->renderViewComponent(Notification::make()->body('All changes are live.')))
        ->toContainElement(Selectors::BODY, 'All changes are live.');
});

it('renders a body message translated', function (): void {
    expect($this->renderViewComponent(Notification::make()->body(new TranslatableMessage('saved', [], 'fixtures-notifications'))))
        ->toContainElement(Selectors::BODY, 'Saved from the catalogue');
});
