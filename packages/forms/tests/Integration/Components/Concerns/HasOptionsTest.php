<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use stdClass;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;

/*
 * `getOptionComparisonState()` reads real state, so every case here binds the
 * field into a `data`-bag host: the normalization is what an option template
 * compares against, and comparing against a hand-set property would skip the
 * cast chain the state actually travels.
 */

/**
 * @param array<string|int, string|array<string|int, string>>|string $options
 */
function wireOptionsField(array|string $options, mixed $rawState): FixtureOptionsField
{
    $field = FixtureOptionsField::make('status')->options($options);

    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    $field->rawState($rawState);

    return $field;
}

it('unwraps a backed enum state to its option key', function (): void {
    expect(wireOptionsField(OptionStatus::class, OptionStatus::Published)->getOptionComparisonState())
        ->toBe('published');
});

it('coerces a boolean state to its int option key', function (): void {
    expect(wireOptionsField([1 => 'Yes', 0 => 'No'], true)->getOptionComparisonState())->toBe(1)
        ->and(wireOptionsField([1 => 'Yes', 0 => 'No'], false)->getOptionComparisonState())->toBe(0);
});

it('maps an iterable state item by item and drops what cannot be compared', function (): void {
    $options = ['draft' => 'Draft', 'published' => 'Published'];

    expect(wireOptionsField($options, [OptionStatus::Draft, 'published', new stdClass()])->getOptionComparisonState())
        ->toBe(['draft', 'published']);
});

it('passes scalars through and nulls what has no option vocabulary', function (): void {
    expect(wireOptionsField(['a' => 'A'], 'a')->getOptionComparisonState())->toBe('a')
        ->and(wireOptionsField(['a' => 'A'], 7)->getOptionComparisonState())->toBe(7)
        ->and(wireOptionsField(['a' => 'A'], null)->getOptionComparisonState())->toBeNull()
        ->and(wireOptionsField(['a' => 'A'], new stdClass())->getOptionComparisonState())->toBeNull();
});
