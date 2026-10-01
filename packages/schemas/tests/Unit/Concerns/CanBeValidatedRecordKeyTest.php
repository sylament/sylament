<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('validates the record key by default', function (): void {
    expect(Schema::make(new StateHostFixture())->isRecordKeyValidated())->toBeTrue();
});

it('stops validating the record key once switched off, and starts again once switched on', function (): void {
    $schema = Schema::make(new StateHostFixture())->validateRecordKey(false);

    expect($schema->isRecordKeyValidated())->toBeFalse()
        ->and($schema->validateRecordKey()->isRecordKeyValidated())->toBeTrue();
});

it('evaluates the switch with the schema injected', function (): void {
    $schema = Schema::make(new StateHostFixture());
    $received = null;

    $schema->validateRecordKey(function (Schema $schema) use (&$received): bool {
        $received = $schema;

        return false;
    });

    expect($schema->isRecordKeyValidated())->toBeFalse()
        ->and($received)->toBe($schema);
});

it('reaches every schema made inside a configureUsing() scope as a default the schema itself overrides', function (): void {
    $switched = Schema::configureUsing(
        static function (Schema $schema): void {
            $schema->validateRecordKey(false);
        },
        during: static fn (): array => [
            Schema::make(new StateHostFixture())->isRecordKeyValidated(),
            Schema::make(new StateHostFixture())->validateRecordKey()->isRecordKeyValidated(),
        ],
    );

    expect($switched)->toBe([false, true])
        ->and(Schema::make(new StateHostFixture())->isRecordKeyValidated())->toBeTrue();
});
