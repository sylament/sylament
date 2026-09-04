<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\CanMutateRecordDataFixture;
use Sylament\Actions\Tests\Fixtures\Concerns\SchemasOnlyHostFixture;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Sylament\Schemas\Schema;
use TypeError;

function mutateRecordDataSchema(): Schema
{
    return Schema::make(new SchemasOnlyHostFixture())
        ->components([
            StateFieldFixture::make('title'),
            StateFieldFixture::make('body'),
        ]);
}

function mutateRecordDataRecord(): object
{
    return new class () {
        public string $title = 'Alpha';

        public ?string $body = 'Beta';
    };
}

it("reads the record as the schema's array and applies the mutator", function (): void {
    $action = CanMutateRecordDataFixture::make('fill')
        ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'title' => mb_strtoupper($data['title'])]);

    expect($action->readRecordDataForFill(mutateRecordDataSchema(), mutateRecordDataRecord()))
        ->toBe(['title' => 'ALPHA', 'body' => 'Beta']);
});

it('drops the excluded keys before mutating', function (): void {
    $seen = null;

    $action = CanMutateRecordDataFixture::make('fill')
        ->mutateRecordDataUsing(function (array $data) use (&$seen): array {
            $seen = $data;

            return $data;
        });

    expect($action->readRecordDataForFill(mutateRecordDataSchema(), mutateRecordDataRecord(), ['body']))
        ->toBe(['title' => 'Alpha'])
        ->and($seen)->toBe(['title' => 'Alpha']);
});

it('hands an array record through without reading the schema', function (): void {
    $action = CanMutateRecordDataFixture::make('fill');

    expect($action->readRecordDataForFill(mutateRecordDataSchema(), ['title' => 'Raw', 'extra' => 1]))
        ->toBe(['title' => 'Raw', 'extra' => 1]);
});

it('returns the data untouched when no mutator is registered', function (): void {
    expect(CanMutateRecordDataFixture::make('fill')->runMutateRecordData(['title' => 'Alpha']))
        ->toBe(['title' => 'Alpha']);
});

it('refuses a mutator that returns a non-array', function (): void {
    $action = CanMutateRecordDataFixture::make('fill')
        ->mutateRecordDataUsing(fn (): string => 'nope');

    expect(fn (): array => $action->runMutateRecordData(['title' => 'Alpha']))
        ->toThrow(TypeError::class, 'mutateRecordDataUsing() must return an array');
});

it('refuses a mutator whose array is not keyed by field name', function (): void {
    $action = CanMutateRecordDataFixture::make('fill')
        ->mutateRecordDataUsing(fn (array $data): array => array_values($data));

    expect(fn (): array => $action->runMutateRecordData(['title' => 'Alpha']))
        ->toThrow(TypeError::class, 'mutateRecordDataUsing() must return an array');
});

it('answers an object or an array record for the fill and refuses anything else', function (): void {
    $record = mutateRecordDataRecord();

    expect(CanMutateRecordDataFixture::make('fill')->record($record)->readRecordForFill())->toBe($record)
        ->and(CanMutateRecordDataFixture::make('fill')->record(['title' => 'Raw'])->readRecordForFill())->toBe(['title' => 'Raw'])
        ->and(fn (): object|array => CanMutateRecordDataFixture::make('fill')->record('7')->readRecordForFill())
        ->toThrow(LogicException::class, 'fills its form from a record but none is bound')
        ->and(fn (): object|array => CanMutateRecordDataFixture::make('fill')->readRecordForFill())
        ->toThrow(LogicException::class, 'fills its form from a record but none is bound');
});

it('answers only an object for the write', function (): void {
    $record = mutateRecordDataRecord();

    expect(CanMutateRecordDataFixture::make('write')->record($record)->readRecordOrFail())->toBe($record)
        ->and(fn (): object => CanMutateRecordDataFixture::make('write')->record(['title' => 'Raw'])->readRecordOrFail())
        ->toThrow(LogicException::class, 'writes a record but no entity is bound')
        ->and(fn (): object => CanMutateRecordDataFixture::make('write')->readRecordOrFail())
        ->toThrow(LogicException::class, 'writes a record but no entity is bound');
});
