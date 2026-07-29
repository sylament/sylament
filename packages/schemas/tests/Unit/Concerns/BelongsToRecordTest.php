<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('returns the bound instance and its class', function (): void {
    $record = new RecordFixture();
    $schema = Schema::make(new StateHostFixture())->record($record);

    expect($schema->getRecord())->toBe($record)
        ->and($schema->getRecordClass())->toBe(RecordFixture::class)
        ->and($schema->getRecordInstance())->toBe($record);
});

it('declares a class without binding an instance', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(RecordFixture::class);

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBe(RecordFixture::class)
        ->and($schema->getRecordInstance())->toBeInstanceOf(RecordFixture::class);
});

it('treats an array record as class-less', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(['title' => 'Hello']);

    expect($schema->getRecord())->toBe(['title' => 'Hello'])
        ->and($schema->getRecordClass())->toBeNull();
});

it('replaces the previous binding on a rebind', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(new RecordFixture())->record(RecordFixture::class);

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBe(RecordFixture::class);
});

it('memoizes a closure-bound record at the root tier', function (): void {
    $calls = 0;
    $schema = Schema::make(new StateHostFixture())->record(function () use (&$calls): RecordFixture {
        $calls++;

        return new RecordFixture();
    });

    $schema->getRecordClass();
    $schema->getRecord();

    expect($calls)->toBe(1);
});

it('returns null everywhere when nothing is bound', function (): void {
    $schema = Schema::make(new StateHostFixture());

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBeNull()
        ->and($schema->getRecordInstance())->toBeNull();
});

it('injects the record into schema closures by name and by type', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Bound');

    $schema = Schema::make(new StateHostFixture())->record($record);

    expect($schema->evaluate(fn ($record): string => $record->getTitle()))->toBe('Bound')
        ->and($schema->evaluate(fn (RecordFixture $article): string => $article->getTitle()))->toBe('Bound');
});
