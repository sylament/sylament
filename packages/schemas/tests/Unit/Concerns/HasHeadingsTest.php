<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('defaults the root heading level to 2', function (): void {
    expect(Schema::make()->getRootHeadingLevel())->toBe(2);
});

it('returns the configured root heading level', function (): void {
    expect(Schema::make()->rootHeadingLevel(4)->getRootHeadingLevel())->toBe(4);
});

it('delegates to the parent component instead of its own root level when nested', function (): void {
    $component = new Component();

    Schema::make(new HasSchemasFixture())
        ->rootHeadingLevel(4)
        ->components([$component])
        ->getComponents();

    expect($component->getChildSchema()->rootHeadingLevel(6)->getRootHeadingLevel())->toBe(4);
});
