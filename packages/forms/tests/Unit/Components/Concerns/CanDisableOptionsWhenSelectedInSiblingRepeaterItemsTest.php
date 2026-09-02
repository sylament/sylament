<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\ToggleButtons;

it('installs live and a disabled-option predicate', function (): void {
    $field = Select::make('topic')->options(['a' => 'A'])->disableOptionsWhenSelectedInSiblingRepeaterItems();

    expect($field->isLive())->toBeTrue()
        ->and($field->hasDisabledOptions())->toBeTrue()
        ->and($field->hasDynamicDisabledOptions())->toBeTrue();
});

it('is composed on the four option-bearing fields', function (string $class): void {
    expect(method_exists($class, 'disableOptionsWhenSelectedInSiblingRepeaterItems'))->toBeTrue();
})->with([CheckboxList::class, Radio::class, Select::class, ToggleButtons::class]);
