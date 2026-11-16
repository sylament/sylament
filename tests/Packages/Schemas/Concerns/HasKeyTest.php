<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;

it('keys its components under its own key, joined under the key the component around it passes on, and strips what would break out of an attribute', function (): void {
    $section = Section::make('Customer')->components(
        Schema::make()->key("in\"ner<\x01")->components([Fieldset::make('Street')->key('street')->components([Text::make('a')])]),
    );

    expect($this->renderSchemaComponent($section))->toContainElement('fieldset[id="schema.inner.street"] legend', 'Street');
});
