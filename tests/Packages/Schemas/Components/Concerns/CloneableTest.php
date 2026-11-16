<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('runs its after-clone hooks on the copy, given the copy and the original', function (): void {
    $field = TextInput::make('city')->afterCloned(function (TextInput $clone, TextInput $original): void {
        $clone->label('Copy of ' . $original->getLabel());
    });

    $clone = $field->getClone();

    expect($this->renderSchemaComponent($clone))->toContainElement('label', 'Copy of City')
        ->and($this->renderSchemaComponent($field))->toContainElement('label', 'City');
});

it('answers the state path and id of its new place once cloned, though the original had read its own', function (): void {
    $field = TextInput::make('city');

    expect($this->renderSchemaComponent(Group::make([$field])->statePath('home')))
        ->toContainElement('input[id="schema.home.city"]' . Selectors::boundTo('data.home.city'));

    expect($this->renderSchemaComponent(Group::make([$field->getClone()])->statePath('work')))
        ->toContainElement('input[id="schema.work.city"]' . Selectors::boundTo('data.work.city'));
});
