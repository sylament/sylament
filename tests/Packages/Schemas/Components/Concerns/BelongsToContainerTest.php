<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Twig\Error\RuntimeError;

it('reaches the root schema from inside nested schemas', function (): void {
    $field = TextInput::make('city')->label(static fn (TextInput $component): string => "In {$component->getContainer()->getKey()}, root {$component->getRootContainer()->getKey()}");

    expect($this->renderSchemaComponent(Section::make('Address')->components([Group::make([$field])->statePath('address')])))
        ->toContainElement('label', 'In schema.address, root schema');
});

it('refuses to read the state of a field whose schema is bound to no live host', function (): void {
    expect(fn () => $this->renderViewComponent(Schema::make()->components([TextInput::make('city')])))
        ->toThrow(RuntimeError::class, 'Schema is not part of LiveComponent');
});
