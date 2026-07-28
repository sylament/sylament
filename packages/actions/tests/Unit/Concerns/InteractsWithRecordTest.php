<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\InteractsWithRecordFixture;

it('has no record by default', function (): void {
    expect(new InteractsWithRecordFixture()->getRecord())->toBeNull();
});

it('round-trips a record value', function (): void {
    expect(new InteractsWithRecordFixture()->record('Ada')->getRecord())->toBe('Ada');
});

it('evaluates a record closure', function (): void {
    expect(new InteractsWithRecordFixture()->record(fn (): string => 'Grace')->getRecord())->toBe('Grace');
});

it('reports record presence', function (): void {
    expect(new InteractsWithRecordFixture()->hasRecord())->toBeFalse()
        ->and(new InteractsWithRecordFixture()->record('Ada')->hasRecord())->toBeTrue();
});
