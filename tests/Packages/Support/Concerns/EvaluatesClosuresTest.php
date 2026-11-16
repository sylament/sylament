<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;

it('injects the component into a closure by its evaluation identifier and by its own type', function (): void {
    $html = Html::make(fn (Html $typed, mixed $component): string => "<p>{$typed->getKey(isAbsolute: false)} and {$component->getKey(isAbsolute: false)}</p>")->key('greeting');

    expect($this->renderSchemaComponent($html))->toContainElement('p', 'greeting and greeting');
});

it('returns a value that is not a closure as it is, a function name included', function (): void {
    expect($this->renderSchemaComponent(Html::make('date'))->text())->toBe('date');
});

it('resolves a container service into a closure of a make()-built component, and nothing for a new-built one', function (): void {
    $content = fn (StaticInstanceRegistry $registry): string => '<p>Resolved</p>';

    expect($this->renderSchemaComponent(Html::make($content)))->toContainElement('p', 'Resolved')
        ->and(fn (): mixed => new Html()->content($content)->getContent())
        ->toThrow(RuntimeException::class, 'An attempt was made to evaluate a closure for [Sylament\Schemas\Components\Html], but [$registry] was unresolvable.');
});
