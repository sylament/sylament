<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Utilities;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('reads state by a path relative to the component', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Read me'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('title'))->toBe('Read me');
});

it('reads state by an absolute path', function (): void {
    $host = new StateHostFixture();
    $host->data = ['title' => 'Read me'];

    $field = Schema::make($host)->statePath('data')->components([StateField::make('title')])->getComponents()[0];

    $get = $field->makeGetUtility();

    expect($get('/data.title'))->toBe('Read me')
        ->and($get('data.title', isAbsolute: true))->toBe('Read me');
});
