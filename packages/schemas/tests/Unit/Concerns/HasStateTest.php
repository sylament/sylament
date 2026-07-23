<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\MultiSchemaHostFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('fills the bag and null-fills unset leaves', function (): void {
    $host = new StateHostFixture();

    $host->fill(['title' => 'Hi']);

    expect($host->data)->toBe(['title' => 'Hi', 'subtitle' => null]);
});

it('assembles the dehydrated bag from getState()', function (): void {
    $host = new StateHostFixture();
    $host->fill(['title' => 'Hi', 'subtitle' => 'Yo']);

    expect($host->getSchema()->getState())->toBe(['title' => 'Hi', 'subtitle' => 'Yo']);
});

it('binds multiple schemas on one host to independent state paths', function (): void {
    $host = new MultiSchemaHostFixture();

    $host->getSchema('editForm')->fill(['title' => 'Edit me']);
    $host->getSchema('filterForm')->fill(['search' => 'query']);

    expect($host->editData)->toBe(['title' => 'Edit me'])
        ->and($host->filterData)->toBe(['search' => 'query']);
});

it('drops a component key when dehydrated(false)', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->dehydrated(false),
    ]);

    $schema->fill(['title' => 'Hi', 'secret' => 'shh']);

    expect($schema->getState())->toBe(['title' => 'Hi']);
});
