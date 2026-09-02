<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\CanBeCopiedFixture;

it('is not copyable by default', function (): void {
    expect(new CanBeCopiedFixture()->isCopyable('Sylament'))->toBeFalse()
        ->and(new CanBeCopiedFixture()->hasCopyable())->toBeFalse();
});

it('turns copying on', function (): void {
    expect(new CanBeCopiedFixture()->copyable()->isCopyable('Sylament'))->toBeTrue()
        ->and(new CanBeCopiedFixture()->copyable()->hasCopyable())->toBeTrue();
});

it('evaluates the copyable condition against the state', function (): void {
    $fixture = new CanBeCopiedFixture()->copyable(fn (mixed $state): bool => $state !== null);

    expect($fixture->isCopyable('Sylament'))->toBeTrue()
        ->and($fixture->isCopyable(null))->toBeFalse();
});

it('reports a closure condition as copyable but a literal false as not', function (): void {
    expect(new CanBeCopiedFixture()->copyable(false)->hasCopyable())->toBeFalse()
        ->and(new CanBeCopiedFixture()->copyable(fn (): bool => false)->hasCopyable())->toBeTrue();
});

it('has no copyable state of its own by default', function (): void {
    expect(new CanBeCopiedFixture()->getCopyableState('Sylament'))->toBeNull();
});

it('evaluates the copyable state against the state', function (): void {
    $fixture = new CanBeCopiedFixture()->copyableState(fn (mixed $state): string => "copy: {$state}");

    expect($fixture->getCopyableState('Sylament'))->toBe('copy: Sylament')
        ->and(new CanBeCopiedFixture()->copyableState('literal')->getCopyableState('Sylament'))->toBe('literal');
});

it('falls back to the Copied message and a two-second duration', function (): void {
    expect(new CanBeCopiedFixture()->getCopyMessage('Sylament'))->toBe('Copied')
        ->and(new CanBeCopiedFixture()->getCopyMessageDuration('Sylament'))->toBe(2000);
});

it('evaluates the copy message and duration against the state', function (): void {
    $fixture = new CanBeCopiedFixture()
        ->copyMessage(fn (mixed $state): string => "Copied {$state}")
        ->copyMessageDuration(fn (mixed $state): int => is_string($state) ? mb_strlen($state) : 0);

    expect($fixture->getCopyMessage('Sylament'))->toBe('Copied Sylament')
        ->and($fixture->getCopyMessageDuration('Sylament'))->toBe(8);
});

it('falls back when a message or duration closure answers null', function (): void {
    $fixture = new CanBeCopiedFixture()
        ->copyMessage(fn (): ?string => null)
        ->copyMessageDuration(fn (): ?int => null);

    expect($fixture->getCopyMessage('Sylament'))->toBe('Copied')
        ->and($fixture->getCopyMessageDuration('Sylament'))->toBe(2000);
});
