<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Utilities;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('writes state by a path relative to the component', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $set = $field->makeSetUtility();
    $get = $field->makeGetUtility();

    $returned = $set('title', 'Bye');

    expect($returned)->toBe('Bye')
        ->and($host->data['title'])->toBe('Bye')
        ->and($get('title'))->toBe('Bye');
});

it('evaluates a closure value before writing it', function (): void {
    $host = new StateHostFixture();
    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $set = $field->makeSetUtility();

    $set('title', fn (): string => 'from closure');

    expect($host->data['title'])->toBe('from closure');
});
