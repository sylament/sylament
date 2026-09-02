<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Validation\Distinct;
use Sylament\Schemas\Schema;

/**
 * @return list<Distinct>
 */
function distinctConstraintsOfIndistinctItem(Select $field): array
{
    $forget = Repeater::fake();

    $host = new FieldRenderHost();
    $host->data = ['rows' => [['topic' => 'a'], ['topic' => 'b']]];

    $repeater = Repeater::make('rows')->components([$field]);
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();
    $repeater->hydrateItems();

    $item = $repeater->getRootContainer()->getComponentByStatePath('data.rows.0.topic');

    $forget();

    return array_values(array_filter(
        $item?->getValidationConstraints() ?? [],
        static fn (object $constraint): bool => $constraint instanceof Distinct,
    ));
}

it('installs distinct and live', function (): void {
    $field = Select::make('topic')->options(['a' => 'A', 'b' => 'B'])->fixIndistinctState();

    expect($field->isLive())->toBeTrue()
        ->and(distinctConstraintsOfIndistinctItem($field))->toHaveCount(1);
});

it('carries the condition into both installs', function (): void {
    $field = Select::make('topic')->options(['a' => 'A', 'b' => 'B'])->fixIndistinctState(false);

    expect($field->isLive())->toBeFalse()
        ->and(distinctConstraintsOfIndistinctItem($field))->toBe([]);
});

it('evaluates a closure condition', function (): void {
    $field = Select::make('topic')->options(['a' => 'A'])->fixIndistinctState(fn (): bool => false);

    expect($field->isLive())->toBeFalse();
});

it('is composed on the six option-bearing fields', function (string $class): void {
    expect(method_exists($class, 'fixIndistinctState'))->toBeTrue();
})->with([Checkbox::class, CheckboxList::class, Radio::class, Select::class, Toggle::class, ToggleButtons::class]);
