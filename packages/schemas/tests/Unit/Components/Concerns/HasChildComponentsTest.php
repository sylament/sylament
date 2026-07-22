<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
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
