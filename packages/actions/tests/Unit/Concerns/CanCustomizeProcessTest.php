<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanCustomizeProcessFixture;

it('runs the default closure when no using() closure is registered', function (): void {
    expect(new CanCustomizeProcessFixture()->process(fn (): string => 'default'))->toBe('default');
});

it('returns null when neither a using() nor a default closure is given', function (): void {
    expect(new CanCustomizeProcessFixture()->process(null))->toBeNull();
});

it('runs the using() closure when no default is given', function (): void {
    expect(new CanCustomizeProcessFixture()->using(fn (): string => 'custom')->process(null))->toBe('custom');
});

it('replaces the default wholesale rather than wrapping it', function (): void {
    $default = false;

    $result = new CanCustomizeProcessFixture()
        ->using(fn (): string => 'custom')
        ->process(function () use (&$default): string {
            $default = true;

            return 'default';
        });

    expect($result)->toBe('custom')
        ->and($default)->toBeFalse();
});

it('passes its parameters to whichever closure runs', function (): void {
    $fixture = new CanCustomizeProcessFixture();

    expect($fixture->process(fn (string $record): string => "default-{$record}", ['record' => 'song']))
        ->toBe('default-song')
        ->and($fixture->using(fn (string $record): string => "custom-{$record}")->process(null, ['record' => 'song']))
        ->toBe('custom-song');
});

it('clears the using() closure when given null', function (): void {
    expect(new CanCustomizeProcessFixture()->using(fn (): string => 'custom')->using(null)->process(fn (): string => 'default'))
        ->toBe('default');
});
