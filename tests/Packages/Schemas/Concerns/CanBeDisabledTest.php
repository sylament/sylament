<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;

it('disables every component of a disabled schema', function (): void {
    $render = $this->renderSchemaComponent(Group::make([
        Group::make(Schema::make()->disabled()->components([TextInput::make('first'), TextInput::make('second')])),
        TextInput::make('outside'),
    ]));

    expect($render)
        ->toContainElement('input[name="first"][disabled]')
        ->toContainElement('input[name="second"][disabled]')
        ->toContainElement('input[name="outside"]:not([disabled])');
});
