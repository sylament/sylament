<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;

it('heads a root section at the second level and each section nested under a heading one level deeper', function (): void {
    $render = $this->renderSchemaComponent(Section::make('Customer')->components([
        Section::make('Address')->components([Text::make('a')]),
        Section::make()->components([Section::make('Contact')->components([Text::make('b')])]),
    ]));

    expect($render)
        ->toContainElement('h2', 'Customer')
        ->toContainElement('h3', 'Address')
        ->toContainElement('h3', 'Contact');
});

it('heads a section nested past the sixth level with a paragraph', function (): void {
    $section = Section::make('Sixth')->components([Section::make('Seventh')->components([Text::make('a')])]);

    foreach (['Fifth', 'Fourth', 'Third', 'Second'] as $heading) {
        $section = Section::make($heading)->components([$section]);
    }

    expect($this->renderSchemaComponent($section))
        ->toContainElement('h2', 'Second')
        ->toContainElement('h6', 'Sixth')
        ->toContainElement('p.sy-section-header-heading', 'Seventh');
});
