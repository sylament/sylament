<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\Concerns\KeyedChildSchemasFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('clears a keyed slot with a null value', function (): void {
    $host = new Component();
    Schema::make(new HasSchemasFixture())->components([$host])->getComponents();

    $host->childComponents([new Component()], 'slot');

    expect($host->getChildSchema('slot'))->not->toBeNull();

    $host->childComponents(null, 'slot');

    expect($host->getChildSchema('slot'))->toBeNull();
});

it('still yields the configured empty default schema after components(null)', function (): void {
    $host = new Component();
    Schema::make(new HasSchemasFixture())->components([$host])->getComponents();

    $host->components(null);

    expect($host->getChildSchema())->toBeInstanceOf(Schema::class)
        ->and($host->getChildSchema()?->getComponents())->toBe([]);
});

it('materializes an array-valued slot on a component whose schema has no live component', function (): void {
    $action = Action::make('save');
    $section = Section::make('Profile')->headerActions([$action]);
    Schema::make()->components([$section])->getComponents();

    expect($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY)?->getComponents())->toBe([$action]);
});

it('answers an unknown key from the cached default child schemas', function (): void {
    $host = new KeyedChildSchemasFixture();
    $host->itemKeys = ['abc', 'def'];

    $abc = $host->getChildSchema('abc');

    expect($abc)->toBeInstanceOf(Schema::class)
        ->and($host->getChildSchema('abc'))->toBe($abc)
        ->and($host->getChildSchema('def'))->not->toBe($abc)
        ->and($host->getChildSchema('nope'))->toBeNull()
        ->and($host->defaultChildSchemaBuilds)->toBe(1);
});

it('memoizes the default child schema until the cache is cleared', function (): void {
    $host = new Component()->components([new Component()]);

    $first = $host->getChildSchemas()[0];

    expect($host->getChildSchemas()[0])->toBe($first);

    $host->clearCachedChildSchemas();

    expect($host->getChildSchemas()[0])->not->toBe($first);
});

it('rebuilds the default child schemas while the cache reports itself stale', function (): void {
    $host = new KeyedChildSchemasFixture();
    $host->itemKeys = ['abc'];
    $host->isCacheFresh = false;

    $first = $host->getChildSchema('abc');

    expect($host->getChildSchema('abc'))->not->toBe($first)
        ->and($host->defaultChildSchemaBuilds)->toBe(2);
});

it('materializes no child schemas for a component with none', function (): void {
    expect(new Component()->getChildSchemas())->toBe([]);
});

it('never hands a clone the child schemas cached by the original', function (): void {
    $host = new Component()->components([new Component()->statePath('name')]);

    $original = $host->getChildSchemas()[0];

    expect($host->getClone()->getChildSchemas()[0])->not->toBe($original)
        ->and($host->getChildSchemas()[0])->toBe($original);
});

it('invalidates the cached default child schemas when child components are set', function (): void {
    $host = new Component()->components([new Component()]);

    $first = $host->getChildSchemas()[0];

    $host->components([new Component()]);

    expect($host->getChildSchemas()[0])->not->toBe($first);
});
