<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSeverity;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Twig\Markup;

it('has no tooltip map at all by default', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->getTooltips())->toBeNull()
        ->and($field->getTooltip('draft'))->toBeNull();
});

it('keeps a literal tooltip map', function (): void {
    $markup = new Markup('<b>Everyone sees it</b>', 'UTF-8');

    $field = FixtureOptionsField::make('status')->tooltips([
        'draft' => 'Only you see it',
        'published' => $markup,
    ]);

    expect($field->getTooltip('draft'))->toBe('Only you see it')
        ->and($field->getTooltip('published'))->toBe($markup)
        ->and($field->getTooltip('archived'))->toBeNull();
});

it('evaluates a tooltips closure', function (): void {
    expect(FixtureOptionsField::make('status')->tooltips(fn (): array => ['draft' => 'Only you see it'])->getTooltips())
        ->toBe(['draft' => 'Only you see it']);
});

it('derives no tooltips from an enum', function (): void {
    $field = FixtureOptionsField::make('severity')->enum(OptionSeverity::class);

    expect($field->getTooltips())->toBeNull()
        ->and($field->getTooltip('critical'))->toBeNull();
});

it('memoises the tooltip map', function (): void {
    $calls = 0;

    $field = FixtureOptionsField::make('status')->tooltips(function () use (&$calls): array {
        $calls++;

        return ['draft' => 'Only you see it'];
    });

    $field->getTooltips();
    $field->getTooltips();

    expect($calls)->toBe(1);
});

it('clears the memoised map when the tooltips are set again', function (): void {
    $field = FixtureOptionsField::make('status')->tooltips(['draft' => 'Only you see it']);

    expect($field->getTooltips())->toBe(['draft' => 'Only you see it']);

    $field->tooltips(null);

    expect($field->getTooltips())->toBeNull();
});
