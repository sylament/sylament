<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\RepeaterHost;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Symfony\Component\Validator\Constraints\Count;

it('refuses a submission with fewer items than minItems() or more than maxItems(), a closure bound included', function (array $items): void {
    $this->createLiveComponent(RepeaterHost::class, ['data' => ['bounded' => $items]])
        ->call('save')
        ->assertHasFormErrors(['bounded' => Count::class]);
})->with([
    'one too few' => [['a' => ['name' => 'Ada']]],
    'one too many' => [['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy'], 'd' => ['name' => 'Joan']]],
]);

it('accepts a submission within the bounds', function (): void {
    $this->createLiveComponent(RepeaterHost::class, ['data' => ['bounded' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('leaves no constraint behind once a bound is unset', function (): void {
    $this->createLiveComponent(RepeaterHost::class, ['data' => ['unbounded' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]])
        ->call('save')
        ->assertHasNoFormErrors();
});

describe('getItemsCount()', function (): void {
    it('counts the items the state holds, so a repeater at its maximum offers no add button', function (): void {
        $repeater = static fn (): Repeater => Repeater::make('lines')->maxItems(2)->components([TextInput::make('name')]);

        expect($this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada']]]))->toContainElement(FormsSelectors::REPEATER_ADD_BUTTON, 'Add to lines');

        $full = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        expect($full->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(2)
            ->and($full->filter(FormsSelectors::REPEATER_ADD))->toHaveCount(0);
    });
});
