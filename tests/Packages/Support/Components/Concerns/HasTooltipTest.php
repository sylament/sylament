<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;
use Symfony\Component\Translation\TranslatableMessage;

it('shows its tooltip as the title of what it draws, and no title without one', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->tooltip('Starred')))
        ->toContainElement('svg[title="Starred"]', 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')))
        ->toContainElement('svg:not([title])', 'Star');
});

it('renders a translated tooltip message', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->tooltip(new TranslatableMessage('greeting', ['name' => 'Ada'], 'fixtures-support'))))
        ->toContainElement('svg[title="Hello Ada"]', 'Star');
});
