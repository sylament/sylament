<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('composes its key under the keys and state paths of its ancestors, a section heading naming only the section, and strips what would break out of an attribute', function (): void {
    $render = $this->renderSchemaComponent(Section::make('Customer')->components([
        Group::make([
            Group::make([Fieldset::make('Street')->key('street')->components([Text::make('a')])])->statePath('address'),
        ])->key('billing'),
        Fieldset::make('Notes')->key('no"tes')->components([Text::make('b')]),
    ]));

    expect($render)
        ->toContainElement(Selectors::SECTION . '[id="schema.customer::section"]')
        ->toContainElement('fieldset[id="schema.billing.address.street"] legend', 'Street')
        ->toContainElement('fieldset[id="schema.notes"] legend', 'Notes');
});
