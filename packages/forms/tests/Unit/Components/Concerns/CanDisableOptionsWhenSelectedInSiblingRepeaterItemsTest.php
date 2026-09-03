<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Validation\Distinct;
use Sylament\Schemas\Schema;

/**
 * The field of a two-item repeater, so the `distinct()` the concern installs has
 * a sibling to build its constraint from.
 *
 * @return list<Distinct>
 */
function distinctConstraintsOfSiblingDisabledItem(Select $field): array
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

it('installs distinct, live and a disabled-option predicate', function (): void {
    $field = Select::make('topic')->options(['a' => 'A'])->disableOptionsWhenSelectedInSiblingRepeaterItems();

    expect($field->isLive())->toBeTrue()
        ->and($field->hasDisabledOptions())->toBeTrue()
        ->and($field->hasDynamicDisabledOptions())->toBeTrue()
        ->and(distinctConstraintsOfSiblingDisabledItem($field))->toHaveCount(1);
});

it('is composed on the four option-bearing fields', function (string $class): void {
    expect(method_exists($class, 'disableOptionsWhenSelectedInSiblingRepeaterItems'))->toBeTrue();
})->with([CheckboxList::class, Radio::class, Select::class, ToggleButtons::class]);
