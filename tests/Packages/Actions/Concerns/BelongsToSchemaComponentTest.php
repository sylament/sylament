<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\State\Get;
use Sylament\Tests\Fixtures\Actions\Selectors;

it("reads its schema's state through the component it belongs to, its schema's or its group's", function (Closure $place): void {
    $action = Action::make('rename')->label(static fn (Get $get): string => "Rename {$get('title')}");

    expect($this->renderSchemaComponent($place($action), ['title' => 'Draft']))->toContainElement(Selectors::TRIGGER, 'Rename Draft');
})->with([
    'its own component' => [static fn (Action $action): Section => Section::make('Post')->headerActions([$action])->components([TextInput::make('title')])],
    "its schema's component" => [static fn (Action $action): Fieldset => Fieldset::make('Post')->components([TextInput::make('title'), $action])],
    "its group's component" => [static fn (Action $action): Section => Section::make('Post')->headerActions([ActionGroup::make([$action])])->components([TextInput::make('title')])],
]);

it('refuses $get to a group outside a schema, by name or by type', function (Closure $closure): void {
    expect(fn (): mixed => ActionGroup::make([])->evaluate($closure))->toThrow(LogicException::class, ActionGroup::class);
})->with([
    '$get' => [static fn ($get): mixed => $get],
    'a Get' => [static fn (Get $lookup): Get => $lookup],
]);
