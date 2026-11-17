<?php

declare(strict_types=1);

use Sylament\Forms\Components\KeyValue;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;

describe('get()', function (): void {
    it('reads the rows as a map, dropping a blank key and keeping the last of a duplicate', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(KeyValue::make('pairs'));
        $host->data = ['pairs' => [
            ['key' => 'colour', 'value' => 'red'],
            ['key' => '', 'value' => 'orphan'],
            ['key' => 'colour', 'value' => 'blue'],
            ['key' => 'size', 'value' => 'L'],
        ]];

        expect($schema->getState())->toBe(['pairs' => ['colour' => 'blue', 'size' => 'L']]);
    });
});

describe('set()', function (): void {
    it('fills a map in as rows, dropping a blank key', function (): void {
        $host = new RecordMappingHost();
        $host->mapping(KeyValue::make('pairs'))->fill(['pairs' => ['colour' => 'red', '' => 'orphan', 'size' => 'L']]);

        expect($host->data['pairs'])->toBe([['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']]);
    });
});

describe('normalize', function (): void {
    it('reads a JSON string of a map, or a blank state as no rows', function (string $pairs, array $rows): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(KeyValue::make('pairs'));
        $schema->fill(['pairs' => $pairs]);

        expect($host->data['pairs'])->toBe($rows);
    })->with([
        'a JSON map' => ['{"colour":"red","size":"L"}', [['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']]],
        'a blank string' => ['', [['key' => '', 'value' => '']]],
    ]);

    it('refuses a state it cannot read', function (string $pairs, string $exception, string $message): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(KeyValue::make('pairs'));

        expect(fn (): Schema => $schema->fill(['pairs' => $pairs]))->toThrow($exception, $message);
    })->with([
        'malformed JSON' => ['{"colour":', JsonException::class, 'Syntax error'],
        'the JSON of a scalar' => ['5', LogicException::class, 'KeyValueStateCast met a int state; a key-value state is a list of rows, a map, or the JSON string of either.'],
    ]);
});
