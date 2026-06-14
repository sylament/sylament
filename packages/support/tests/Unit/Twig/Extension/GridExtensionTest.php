<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Enum\GridDirection;
use Sylament\Support\Twig\Extension\GridExtension;

// The common path (row direction, numeric breakpoints → grid classes/styles) is
// covered end-to-end by the schemas SchemaRenderTest. These unit tests pin the
// branches that a Schema render never exercises: the column direction, breakpoint
// name sanitization, and null filtering.

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

it('drops null breakpoints before building attributes', function (): void {
    $attributes = new GridExtension()->getGridAttributes(['md' => 2, 'xl' => null]);

    expect($attributes['class'])->toBe(['sy-grid', 'md:sy-grid-cols', 'sy-grid-cols'])
        ->and($attributes['style'])->toBe([
            '--cols-md: repeat(2, minmax(0, 1fr))',
            '--cols-default: repeat(1, minmax(0, 1fr))',
        ]);
});
