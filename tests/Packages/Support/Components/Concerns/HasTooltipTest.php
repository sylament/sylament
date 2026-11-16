<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;

it('shows its tooltip as the title of what it draws, and no title without one', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->tooltip('Starred')))
        ->toContainElement('svg[title="Starred"]', 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')))
        ->toContainElement('svg:not([title])', 'Star');
});
