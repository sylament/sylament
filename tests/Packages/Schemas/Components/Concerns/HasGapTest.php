<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;

describe('gap', function (): void {
    it('spaces the items of its content unless told not to', function (): void {
        expect($this->renderSchemaComponent(Section::make('Spaced')->components([Text::make('a')])))
            ->toContainElement(Selectors::SECTION_CONTENT . Selectors::GAP)
            ->and($this->renderSchemaComponent(Section::make('Tight')->gap(false)->components([Text::make('a')])))
            ->toContainElement(Selectors::SECTION_CONTENT . ':not(' . Selectors::GAP . ')')
            ->not->toContainElement(Selectors::SECTION_CONTENT . Selectors::GAP);
    });

    it('inherits the gap of the component around it, and turns it back on with no argument', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Outer')->gap(false)->components([
            Section::make('Inheriting')->components([Text::make('a')]),
            Section::make('Respaced')->gap()->components([Text::make('b')]),
        ]));

        expect($render)
            ->toContainElement('[id="schema.inheriting::section-content"] ' . Selectors::SECTION_CONTENT . ':not(' . Selectors::GAP . ')')
            ->toContainElement('[id="schema.respaced::section-content"] ' . Selectors::SECTION_CONTENT . Selectors::GAP);
    });
});

describe('dense', function (): void {
    it('packs the items of its content densely when told, and turns it on with no argument', function (): void {
        expect($this->renderSchemaComponent(Section::make('Packed')->dense()->components([Text::make('a')])))
            ->toContainElement(Selectors::SECTION_CONTENT . Selectors::DENSE)
            ->and($this->renderSchemaComponent(Section::make('Loose')->components([Text::make('a')])))
            ->toContainElement(Selectors::SECTION_CONTENT . ':not(' . Selectors::DENSE . ')')
            ->not->toContainElement(Selectors::SECTION_CONTENT . Selectors::DENSE);
    });

    it('inherits the density of the component around it, unless it says otherwise', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Outer')->dense()->components([
            Section::make('Inheriting')->components([Text::make('a')]),
            Section::make('Loosened')->dense(false)->components([Text::make('b')]),
        ]));

        expect($render)
            ->toContainElement('[id="schema.inheriting::section-content"] ' . Selectors::SECTION_CONTENT . Selectors::DENSE)
            ->toContainElement('[id="schema.loosened::section-content"] ' . Selectors::SECTION_CONTENT . ':not(' . Selectors::DENSE . ')');
    });
});
