<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

it('keys each filled item by a fresh v4 uuid by default', function (): void {
    $host = new RecordMappingHost();
    $host->mapping(Repeater::make('lines')->components([TextInput::make('name')]))->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);
    $keys = array_keys($host->data['lines']);

    expect(array_map(static fn (int|string $key): bool => Uuid::fromString((string) $key) instanceof UuidV4, $keys))->toBe([true, true])
        ->and(array_unique($keys))->toHaveCount(2);
});

it('keys each filled item by what a generator closure returns', function (): void {
    $minted = 0;
    $host = new RecordMappingHost();
    $host->mapping(Repeater::make('lines')->generateUuidUsing(static function () use (&$minted): string {
        return 'line-' . ++$minted;
    })->components([TextInput::make('name')]))->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

    expect($host->data['lines'])->toBe(['line-1' => ['name' => 'Ada'], 'line-2' => ['name' => 'Grace']]);
});

it('keeps filled items positional once generation is off', function (): void {
    $host = new RecordMappingHost();
    $host->mapping(Repeater::make('lines')->generateUuidUsing(false)->components([TextInput::make('name')]))->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

    expect($host->data['lines'])->toBe([['name' => 'Ada'], ['name' => 'Grace']]);
});

it('mints a real uuid for true', function (): void {
    $repeater = Repeater::make('lines')->generateUuidUsing(false)->generateUuidUsing(true);

    expect(Uuid::fromString((string) $repeater->generateUuid()))->toBeInstanceOf(UuidV4::class);
});

it('keeps every repeater positional under fake(), until the closure it returns restores generation', function (): void {
    $restore = Repeater::fake();

    expect(Repeater::make('lines')->generateUuid())->toBeNull();

    $restore();

    expect(Uuid::fromString((string) Repeater::make('lines')->generateUuid()))->toBeInstanceOf(UuidV4::class);
});
