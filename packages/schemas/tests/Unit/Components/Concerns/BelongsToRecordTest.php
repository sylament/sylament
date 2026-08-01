<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('falls back to the container record and class', function (): void {
    $record = new RecordFixture();
    $field = Schema::make(new StateHostFixture())
        ->record($record)
        ->components([StateField::make('title')])
        ->getComponents()[0];

    expect($field->getRecord())->toBe($record)
        ->and($field->getRecordClass())->toBe(RecordFixture::class)
        ->and($field->getRecordInstance())->toBe($record);
});

it('prefers its own record over the container one', function (): void {
    $own = new RecordFixture();
    $field = Schema::make(new StateHostFixture())
        ->record(new RecordFixture())
        ->components([StateField::make('title')->record($own)])
        ->getComponents()[0];

    expect($field->getRecord())->toBe($own);
});

it('stops at the component when asked not to walk the container', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->record(new RecordFixture())
        ->components([StateField::make('title')])
        ->getComponents()[0];

    expect($field->getRecord(withContainerRecord: false))->toBeNull();
});

it('re-evaluates a closure-bound record per call at the component tier', function (): void {
    $calls = 0;
    $field = Schema::make(new StateHostFixture())
        ->components([StateField::make('title')->record(function () use (&$calls): RecordFixture {
            $calls++;

            return new RecordFixture();
        })])
        ->getComponents()[0];

    $field->getRecordClass();
    $field->getRecord();

    expect($calls)->toBe(2);
});

it('returns null from every getter on a detached component', function (): void {
    $field = StateField::make('title');

    expect($field->getRecord())->toBeNull()
        ->and($field->getRecordClass())->toBeNull()
        ->and($field->getRecordInstance())->toBeNull();
});

it('keeps detached-component typed-closure evaluation working', function (): void {
    $field = StateField::make('title');

    expect($field->evaluate(fn (StateField $component): StateField => $component))->toBe($field);
});

it('injects the record and typed record into component closures', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Bound');

    $field = Schema::make(new StateHostFixture())
        ->record($record)
        ->components([StateField::make('title')])
        ->getComponents()[0];

    expect($field->evaluate(fn ($record): string => $record->getTitle()))->toBe('Bound')
        ->and($field->evaluate(fn (RecordFixture $r): string => $r->getTitle()))->toBe('Bound');
});

it('injects Get and Set by type under non-mapped parameter names', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')])
        ->getComponents()[0];

    expect($field->evaluate(fn (Get $reader): bool => $reader instanceof Get))->toBeTrue()
        ->and($field->evaluate(fn (Set $writer): bool => $writer instanceof Set))->toBeTrue();
});

it('has no record state path when the component carries no state path', function (): void {
    $section = Schema::make(new StateHostFixture())
        ->components([Section::make('Profile')])
        ->getComponents()[0];

    expect($section->getRecordStatePath())->toBeNull();
});

it('has no record state path when a path-less component sits under a root state path', function (): void {
    $section = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([Section::make('Profile')])
        ->getComponents()[0];

    expect($section->getStatePath())->toBe('data')
        ->and($section->getRecordStatePath())->toBeNull();
});

it('strips the root schema state path prefix', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('author.name')])
        ->getComponents()[0];

    expect($field->getStatePath())->toBe('data.author.name')
        ->and($field->getRecordStatePath())->toBe('author.name');
});

it('returns the whole path when the root schema has no state path', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->components([StateField::make('author.name')])
        ->getComponents()[0];

    expect($field->getRecordStatePath())->toBe('author.name');
});

it('returns the whole path when it does not sit under the root prefix', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->components([StateField::make('title')]);
    $field = $schema->getComponents()[0];

    $field->getStatePath();

    $schema->statePath('data');

    expect($field->getStatePath())->toBe('title')
        ->and($schema->getStatePath())->toBe('data')
        ->and($field->getRecordStatePath())->toBe('title');
});
