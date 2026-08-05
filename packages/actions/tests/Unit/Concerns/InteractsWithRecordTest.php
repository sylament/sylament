<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Stringable;
use Sylament\Actions\Action;
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

it('resolves record keys from arrays, ids and Stringable ids', function (): void {
    $action = Action::make('edit');

    expect($action->resolveRecordKey(['__key' => 5]))->toBe('5')
        ->and($action->resolveRecordKey(new class {
            public int $id = 7;
        }))->toBe('7')
        ->and($action->resolveRecordKey(new class {
            public function getId(): string
            {
                return 'abc';
            }
        }))->toBe('abc')
        ->and($action->resolveRecordKey(new class {
            public function getId(): Stringable
            {
                return new class implements Stringable {
                    public function __toString(): string
                    {
                        return 'ada';
                    }
                };
            }
        }))->toBe('ada');
});

it('resolves no record key from an unreadable, blank or non-record value', function (): void {
    $action = Action::make('edit');

    expect($action->resolveRecordKey(new class {}))->toBeNull()
        ->and($action->resolveRecordKey(new class {
            public string $id = '';
        }))->toBeNull()
        ->and($action->resolveRecordKey(null))->toBeNull()
        ->and($action->resolveRecordKey('scalar'))->toBeNull();
});

it('throws for an array record without the key entry', function (): void {
    Action::make('edit')->resolveRecordKey(['title' => 'x']);
})->throws(LogicException::class, '[__key]');
