<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\LazyKeyValueForm;

it('binds the input as its modifiers ask', function (Component $field, string $dataModel): void {
    expect($this->renderSchemaComponent($field)->filter('input')->attr('data-model'))->toBe($dataModel);
})->with([
    'not live' => [fn (): TextInput => TextInput::make('title'), 'norender|data.title'],
    'live' => [fn (): TextInput => TextInput::make('title')->live(), 'data.title'],
    'reactive' => [fn (): TextInput => TextInput::make('title')->reactive(), 'data.title'],
    'live, condition false' => [fn (): TextInput => TextInput::make('title')->live(condition: false), 'norender|data.title'],
    'lazy' => [fn (): TextInput => TextInput::make('title')->lazy(), 'on(change)|data.title'],
    'debounced' => [fn (): TextInput => TextInput::make('title')->debounce(), 'debounce(500)|data.title'],
    'live, debounce spelled as a string' => [fn (): TextInput => TextInput::make('title')->live(debounce: '1s'), 'debounce(1s)|data.title'],
    'live on blur and debounced' => [fn (): TextInput => TextInput::make('title')->live(onBlur: true, debounce: 300), 'on(change)|data.title'],
]);

it('binds a field that sets no liveness of its own as its container does', function (Component $container, string $dataModel): void {
    expect($this->renderSchemaComponent($container)->filter('input')->attr('data-model'))->toBe($dataModel);
})->with([
    'a live parent' => [fn (): Section => Section::make()->live()->components([TextInput::make('title')]), 'data.title'],
    'a lazy parent' => [fn (): Section => Section::make()->lazy()->components([TextInput::make('title')]), 'on(change)|data.title'],
    'a debounced parent' => [fn (): Section => Section::make()->debounce(300)->components([TextInput::make('title')]), 'debounce(300)|data.title'],
    'a lazy schema' => [fn (): Section => Section::make()->components(Schema::make()->lazy()->components([TextInput::make('title')])), 'on(change)|data.title'],
    'its own live() inside a lazy parent' => [fn (): Section => Section::make()->lazy()->components([TextInput::make('title')->live()]), 'data.title'],
]);

it('binds each key and value cell at the liveness of the field', function (): void {
    $crawler = $this->createLiveComponent(LazyKeyValueForm::class, ['data' => ['pairs' => [['key' => 'colour', 'value' => 'red']]]])
        ->render()
        ->crawler();

    expect($crawler)
        ->toContainElement('input[aria-label="Key"][data-model="on(change)|data.pairs.0.key"][value="colour"]')
        ->toContainElement('input[aria-label="Value"][data-model="on(change)|data.pairs.0.value"][value="red"]');
});

it('reports a debounce set on the field or inherited from its container, never one on blur', function (): void {
    $lazy = TextInput::make('lazy')->lazy();
    $debounced = TextInput::make('debounced')->debounce(300);
    $live = TextInput::make('live')->live();
    $inherits = TextInput::make('inherits');

    $this->renderSchemaComponent(Section::make()->components([$lazy, $debounced, $live]));
    $this->renderSchemaComponent(Section::make()->debounce(300)->components([$inherits]));

    expect([$lazy->isLiveDebounced(), $debounced->isLiveDebounced(), $live->isLiveDebounced(), $inherits->isLiveDebounced()])
        ->toBe([false, true, false, true]);
});
