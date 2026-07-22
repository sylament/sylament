<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Enum\GridDirection;
use Sylament\Support\Twig\Extension\GridExtension;

// The common paths (row direction, numeric breakpoints → grid classes/styles;
// child spans/starts/orders → grid-column classes/styles) are covered end-to-end
// by the schemas SchemaRenderTest. These unit tests pin the branches that a
// Schema render never exercises: the column direction, breakpoint name
// sanitization, null filtering, and gridColumn's scalar/full/hidden handling.

it('marks the column direction with a class and bare column styles', function (): void {
    $attributes = new GridExtension()->getGridAttributes(2, GridDirection::Column);

    expect($attributes['class'])->toBe(['sy-grid', 'sy-grid-direction-col', 'lg:sy-grid-cols', 'sy-grid-cols'])
        ->and($attributes['style'])->toBe([
            '--cols-lg: 2',
            '--cols-default: 1',
        ]);
});

it('sanitizes container and important markers in breakpoint names', function (): void {
    $attributes = new GridExtension()->getGridAttributes(['@md' => 2, '!sm' => 3]);

    expect($attributes['style'])->toBe([
        '--cols-cmd: repeat(2, minmax(0, 1fr))',
        '--cols-nsm: repeat(3, minmax(0, 1fr))',
        '--cols-default: repeat(1, minmax(0, 1fr))',
    ]);
});

it('stringifies a numeric-string breakpoint key instead of raising a TypeError', function (): void {
    // A numeric-string key is an int at runtime, which the string-typed breakpoint
    // helpers would reject under strict_types; the array is annotated as the public
    // signature promises so the coercion is what production hits.
    /** @var array<string, ?int> $columns */
    $columns = ['0' => 2];

    $attributes = new GridExtension()->getGridAttributes($columns);

    expect($attributes['style'])->toContain('--cols-0: repeat(2, minmax(0, 1fr))');
});

it('drops null breakpoints before building attributes', function (): void {
    $attributes = new GridExtension()->getGridAttributes(['md' => 2, 'xl' => null]);

    expect($attributes['class'])->toBe(['sy-grid', 'md:sy-grid-cols', 'sy-grid-cols'])
        ->and($attributes['style'])->toBe([
            '--cols-md: repeat(2, minmax(0, 1fr))',
            '--cols-default: repeat(1, minmax(0, 1fr))',
        ]);
});

it('maps scalar span, start, and order onto the lg breakpoint', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(2, 3, 4);

    expect($attributes['class'])->toBe(['sy-grid-col', 'lg:sy-grid-col-span', 'lg:sy-grid-col-start', 'lg:sy-grid-col-order'])
        ->and($attributes['style'])->toBe([
            '--col-span-lg: span 2 / span 2',
            '--col-start-lg: 3',
            '--col-order-lg: 4',
        ]);
});

it('emits no span class for the default breakpoint but unprefixed start and order classes', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(['default' => 2], ['default' => 3], ['default' => 4]);

    expect($attributes['class'])->toBe(['sy-grid-col', 'sy-grid-col-start', 'sy-grid-col-order'])
        ->and($attributes['style'])->toBe([
            '--col-span-default: span 2 / span 2',
            '--col-start-default: 3',
            '--col-order-default: 4',
        ]);
});

it('converts a full span into an edge-to-edge grid-column value', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(['default' => 'full']);

    expect($attributes['class'])->toBe(['sy-grid-col'])
        ->and($attributes['style'])->toBe(['--col-span-default: 1 / -1']);
});

it('marks a hidden span value with the hidden class', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(['default' => 'hidden']);

    // The nonsense span variable mirrors Filament; it is inert under display: none.
    expect($attributes['class'])->toBe(['sy-grid-col', 'sy-hidden'])
        ->and($attributes['style'])->toBe(['--col-span-default: span hidden / span hidden']);
});

it('marks an explicitly hidden component with the hidden class', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(isHidden: true);

    expect($attributes['class'])->toBe(['sy-grid-col', 'sy-hidden'])
        ->and($attributes['style'])->toBe([]);
});

it('sanitizes container and important markers in grid-column breakpoint names', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(['@md' => 2], ['!sm' => 3]);

    expect($attributes['class'])->toBe(['sy-grid-col', '@md:sy-grid-col-span', '!sm:sy-grid-col-start'])
        ->and($attributes['style'])->toBe([
            '--col-span-cmd: span 2 / span 2',
            '--col-start-nsm: 3',
        ]);
});

it('drops null grid-column breakpoints before building attributes', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(['md' => 2, 'xl' => null]);

    expect($attributes['class'])->toBe(['sy-grid-col', 'md:sy-grid-col-span'])
        ->and($attributes['style'])->toBe(['--col-span-md: span 2 / span 2']);
});

it('drops blank breakpoint keys from the grid', function (): void {
    $attributes = new GridExtension()->getGridAttributes([' ' => 3, 'md' => 2]);

    expect($attributes['class'])->toBe(['sy-grid', 'md:sy-grid-cols', 'sy-grid-cols'])
        ->and($attributes['style'])->toBe(['--cols-md: repeat(2, minmax(0, 1fr))', '--cols-default: repeat(1, minmax(0, 1fr))']);
});

it('drops blank breakpoint keys from a grid column', function (): void {
    $attributes = new GridExtension()->getGridColumnAttributes(span: ['' => 4, 'md' => 2]);

    expect($attributes['class'])->toBe(['sy-grid-col', 'md:sy-grid-col-span'])
        ->and($attributes['style'])->toBe(['--col-span-md: span 2 / span 2']);
});
