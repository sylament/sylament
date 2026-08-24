<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;

it('finds the repeater and the item index from a field inside an item', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a'], ['name' => 'b']]];
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();

    $items = array_values($repeater->getItems());
    $field = $items[1]->getComponents()[0];

    expect($field->getParentRepeater())->toBe($repeater)
        ->and($field->getParentRepeaterItem())->toBe($items[1])
        ->and($field->getParentRepeaterItemIndex())->toBe(1);
});
