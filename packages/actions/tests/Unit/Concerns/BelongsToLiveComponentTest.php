<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Actions\Tests\Fixtures\Concerns\SchemasOnlyHostFixture;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;

it('throws when no live component is set and no schema binding exists', function (): void {
    Action::make('save')->getLiveComponent();
})->throws(RuntimeException::class, 'Action is not part of LiveComponent');

it('returns the explicitly set live component over any schema binding', function (): void {
    $explicit = new BothContractsHostFixture();
    $viaSchema = new BothContractsHostFixture();

    $action = Action::make('save')
        ->schemaContainer(Schema::make($viaSchema))
        ->liveComponent($explicit);

    expect($action->getLiveComponent())->toBe($explicit);
});

it('resolves the live component through the schema container', function (): void {
    $host = new BothContractsHostFixture();

    $action = Action::make('save')->schemaContainer(Schema::make($host));

    expect($action->getLiveComponent())->toBe($host);
});

it('resolves the live component through the schema component', function (): void {
    $host = new BothContractsHostFixture();
    $section = Section::make('Box')->container(Schema::make($host));

    $action = Action::make('save')->schemaComponent($section);

    expect($action->getLiveComponent())->toBe($host);
});

it('peeks the live component through getLiveComponentOrNull() without throwing', function (): void {
    $host = new BothContractsHostFixture();

    expect(Action::make('save')->liveComponent($host)->getLiveComponentOrNull())->toBe($host)
        ->and(Action::make('save')->getLiveComponentOrNull())->toBeNull();
});

it('throws when the schema-bound host does not implement the actions contract', function (): void {
    Action::make('save')
        ->schemaContainer(Schema::make(new SchemasOnlyHostFixture()))
        ->getLiveComponent();
})->throws(RuntimeException::class, 'Action is not part of LiveComponent');

it('resolves nothing from an unbound schema container', function (): void {
    Action::make('save')
        ->schemaContainer(Schema::make())
        ->getLiveComponent();
})->throws(RuntimeException::class, 'Action is not part of LiveComponent');
