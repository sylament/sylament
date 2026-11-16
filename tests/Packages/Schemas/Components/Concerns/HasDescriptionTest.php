<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Twig\Markup;

it('shows its description under its heading, text as text and markup as markup, and none when blank', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->description('<em>Where it goes</em>')->components([Text::make('Body')])))
        ->toContainElement('section header', 'Shipping <em>Where it goes</em>')
        ->toContainElement('section header p', '<em>Where it goes</em>')
        ->not->toContainElement('p em')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->description(new Markup('<em>Where it goes</em>', 'UTF-8'))->components([Text::make('Body')])))
        ->toContainElement('section header p em', 'Where it goes')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->description('   ')->components([Text::make('Body')])))
        ->toContainElement('section header h2', 'Shipping')
        ->not->toContainElement('section header p');
});
