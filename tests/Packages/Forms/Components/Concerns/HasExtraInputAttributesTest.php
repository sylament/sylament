<?php

declare(strict_types=1);

use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Infrastructure\ComponentRegistry;
use Symfony\Component\DomCrawler\Crawler;

describe('extraInputAttributes()', function (): void {
    it('puts its attributes on the control, a second call replacing them unless told to merge, a closure answering nothing adding none', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')->extraInputAttributes(['data-first' => 'a'])->extraInputAttributes(['data-second' => 'b'])))
            ->toContainElement('input[name="code"][data-second="b"]:not([data-first])')
            ->and($this->renderSchemaComponent(TextInput::make('code')
                ->extraInputAttributes(['data-first' => 'a'])
                ->extraInputAttributes(['data-second' => 'b'], merge: true)
                ->extraInputAttributes(static fn (): null => null, merge: true)))
            ->toContainElement('input[name="code"][data-first="a"][data-second="b"]');
    });

    it('lets its attributes win over those the field computes, its classes appending, while the controller keeps its target', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('amount')->numeric()->disabled()->extraInputAttributes(['inputmode' => 'email', 'disabled' => false, 'class' => 'wide'])))
            ->toContainElement('input' . Selectors::INPUT . '.wide[name="amount"][inputmode="email"]:not([disabled])')
            ->and($this->renderSchemaComponent(TextInput::make('code')->copyable()->extraInputAttributes(['data-sylament--forms--text-input-target' => 'other'])))
            ->toContainElement('input[name="code"]' . Selectors::TEXT_INPUT_TARGET);
    });
});

it('puts its extra input attributes on every control a field renders and on nothing else, where they outrank the ones it computes', function (string $field): void {
    $component = $field::make('a')->disabled()->extraInputAttributes(['data-probe' => 'extra', 'disabled' => false]);

    if (method_exists($component, 'options')) {
        $component->options(['x' => 'X', 'y' => 'Y']);
    }

    $render = $this->renderSchemaComponent($component);
    $controls = $render->filter('input, select, textarea');

    expect($controls->count())->toBeGreaterThan(0)
        ->and($controls->each(static fn (Crawler $control): ?string => $control->attr('data-probe')))->toBe(array_fill(0, $controls->count(), 'extra'))
        ->and($render->filter('[data-probe]'))->toHaveCount($controls->count())
        ->and($controls->filter('[disabled]'))->toHaveCount(0);
})->with(fn (): array => array_filter(
    ComponentRegistry::componentsUsing(HasExtraInputAttributes::class),
    // A table column renders in a table's cell, which tables' own files drive.
    static fn (string $class): bool => is_subclass_of($class, Field::class),
));
