<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionPriority;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSize;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStage;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Twig\Markup;

it('describes no option by default', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->getDescriptions())->toBe([])
        ->and($field->hasDescription('draft'))->toBeFalse()
        ->and($field->getDescription('draft'))->toBeNull();
});

it('keeps a literal description map', function (): void {
    $markup = new Markup('<b>Everyone can see it</b>', 'UTF-8');

    $field = FixtureOptionsField::make('status')->descriptions([
        'draft' => 'Only you can see it',
        'published' => $markup,
    ]);

    expect($field->hasDescription('draft'))->toBeTrue()
        ->and($field->getDescription('draft'))->toBe('Only you can see it')
        ->and($field->getDescription('published'))->toBe($markup);
});

it('evaluates a descriptions closure', function (): void {
    expect(FixtureOptionsField::make('status')->descriptions(fn (): array => ['draft' => 'Only you can see it'])->getDescriptions())
        ->toBe(['draft' => 'Only you can see it']);
});

it('derives descriptions from an enum implementing HasDescription', function (): void {
    $field = FixtureOptionsField::make('priority')->enum(OptionPriority::class);

    expect($field->getDescriptions())->toBe(['low' => 'Handled when there is time'])
        ->and($field->hasDescription('low'))->toBeTrue()
        ->and($field->hasDescription('high'))->toBeFalse()
        ->and($field->getDescription('high'))->toBeNull();
});

it('keys a described pure UnitEnum by case name', function (): void {
    expect(FixtureOptionsField::make('stage')->enum(OptionStage::class)->getDescriptions())
        ->toBe(['Draft' => 'Still being written', 'Live' => 'Published to the world']);
});

it('derives nothing from an enum without the description contract', function (): void {
    expect(FixtureOptionsField::make('size')->enum(OptionSize::class)->getDescriptions())->toBe([]);
});

it('lets a literal description map win over the enum', function (): void {
    expect(FixtureOptionsField::make('priority')->enum(OptionPriority::class)->descriptions(['high' => 'Do it now'])->getDescriptions())
        ->toBe(['high' => 'Do it now']);
});

it('memoises the description map', function (): void {
    $calls = 0;

    $field = FixtureOptionsField::make('status')->descriptions(function () use (&$calls): array {
        $calls++;

        return ['draft' => 'Only you can see it'];
    });

    $field->getDescriptions();
    $field->getDescriptions();

    expect($calls)->toBe(1);
});

it('clears the memoised map when the descriptions are set again', function (): void {
    $field = FixtureOptionsField::make('status')->descriptions(['draft' => 'Only you can see it']);

    expect($field->getDescriptions())->toBe(['draft' => 'Only you can see it']);

    $field->descriptions(['published' => 'Everyone can see it']);

    expect($field->getDescriptions())->toBe(['published' => 'Everyone can see it']);
});
