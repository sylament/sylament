<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\EmbeddedSchema;

it('holds the schema name', function (): void {
    expect(EmbeddedSchema::make('form')->getName())->toBe('form');
});

it('evaluates a name closure with the component injection', function (): void {
    $embed = EmbeddedSchema::make('form');

    expect($embed->name(fn (EmbeddedSchema $component): string => $component->getKey(isAbsolute: false) ?? '')->key('content')->getName())
        ->toBe('content');
});
