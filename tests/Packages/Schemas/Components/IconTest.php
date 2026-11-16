<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Twig\Markup;

it('shows a markup tooltip as plain text in its title', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->tooltip(new Markup('<b>Starred</b>', 'UTF-8'))))
        ->toContainElement(Selectors::ICON . '[title="Starred"]', 'Star');
});
