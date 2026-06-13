<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Tests\Fixtures\Concerns\BelongsToLiveComponentFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('has no live component by default', function (): void {
    expect(new BelongsToLiveComponentFixture()->getLiveComponent())->toBeNull();
});

it('sets the live component', function (): void {
    $liveComponent = new HasSchemasFixture();
    $fixture = new BelongsToLiveComponentFixture()->liveComponent($liveComponent);

    expect($fixture->getLiveComponent())->toBe($liveComponent);
});

it('returns itself from liveComponent() for chaining', function (): void {
    $fixture = new BelongsToLiveComponentFixture();

    expect($fixture->liveComponent(new HasSchemasFixture()))->toBe($fixture);
});

it('clears the live component when called with no argument', function (): void {
    $fixture = new BelongsToLiveComponentFixture()
        ->liveComponent(new HasSchemasFixture())
        ->liveComponent();

    expect($fixture->getLiveComponent())->toBeNull();
});
