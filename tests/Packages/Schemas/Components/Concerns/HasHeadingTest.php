<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Twig\Markup;

it('shows its heading in its header, text as text and markup as markup, and no header for a blank one', function (): void {
    expect($this->renderSchemaComponent(Section::make()->heading('<em>Shipping</em>')->components([Text::make('Body')])))
        ->toContainElement('section header h2', '<em>Shipping</em>')
        ->not->toContainElement('h2 em')
        ->and($this->renderSchemaComponent(Section::make()->heading(new Markup('<em>Shipping</em>', 'UTF-8'))->components([Text::make('Body')])))
        ->toContainElement('section header h2 em', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make()->heading('   ')->components([Text::make('Body')])))
        ->toContainElement('section', 'Body')
        ->not->toContainElement('header');
});
