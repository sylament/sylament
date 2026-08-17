<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\Toggle;
use Sylament\Support\Twig\ComponentAttributeBag;

it('has no extra input attributes by default', function (): void {
    expect(TextInput::make('title')->getExtraInputAttributes())->toBe([]);
});

it('replaces extra input attributes by default and appends when merging', function (): void {
    $replaced = TextInput::make('title')
        ->extraInputAttributes(['data-one' => '1'])
        ->extraInputAttributes(['data-two' => '2']);

    expect($replaced->getExtraInputAttributes())->toBe(['data-two' => '2']);

    $merged = TextInput::make('title')
        ->extraInputAttributes(['data-one' => '1'])
        ->extraInputAttributes(['data-two' => '2'], merge: true);

    expect($merged->getExtraInputAttributes())->toEqualCanonicalizing(['data-one' => '1', 'data-two' => '2']);
});

it('resolves closure extra input attributes through evaluate', function (): void {
    expect(TextInput::make('title')->extraInputAttributes(fn (): array => ['data-lazy' => 'yes'])->getExtraInputAttributes())
        ->toBe(['data-lazy' => 'yes']);
});

it('exposes the extra input attributes as a bag', function (): void {
    $bag = TextInput::make('title')
        ->extraInputAttributes(['class' => 'custom'])
        ->getExtraInputAttributeBag();

    expect($bag)->toBeInstanceOf(ComponentAttributeBag::class)
        ->and($bag->getAttributes())->toBe(['class' => ['custom']]);
});

it('is composed on every field that renders a control, one or one per option', function (string $class): void {
    expect(method_exists($class, 'extraInputAttributes'))->toBeTrue();
})->with([
    TextInput::class,
    Textarea::class,
    Select::class,
    Checkbox::class,
    Toggle::class,
    Radio::class,
]);
