<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\Translation\TranslatableMessage;

it('renders the title as the toast heading', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Saved')))->toContainElement(Selectors::TITLE, 'Saved');
});

it('renders a title message translated, with its parameters', function (): void {
    expect($this->renderViewComponent(Notification::make()->title(new TranslatableMessage('synced', ['label' => 'Feed'], 'fixtures-notifications'))))
        ->toContainElement(Selectors::TITLE, 'Feed synced');
});
