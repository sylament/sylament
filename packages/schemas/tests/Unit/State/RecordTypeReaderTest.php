<?php

declare(strict_types=1);

use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Tests\Fixtures\Records\Article;
use Sylament\Schemas\Tests\Fixtures\Records\Profile;
use Sylament\Schemas\Tests\Fixtures\Records\WithCollections;

it('reports a non-nullable scalar as non-nullable', function (): void {
    $target = new RecordTypeReader()->describeProperty(Profile::class, 'name');

    expect($target)->not->toBeNull()
        ->and($target->isNullable)->toBeFalse()
        ->and($target->objectClass)->toBeNull()
        ->and($target->isCollection)->toBeFalse();
});

it('reports a nullable scalar as nullable', function (): void {
    $target = new RecordTypeReader()->describeProperty(Profile::class, 'bio');

    expect($target->isNullable)->toBeTrue();
});

it('reports the class to construct for a nullable object property', function (): void {
    $target = new RecordTypeReader()->describeProperty(Article::class, 'author');

    expect($target->objectClass)->toBe(Profile::class)
        ->and($target->isNullable)->toBeTrue();
});

it('follows the mutator when the getter disagrees about nullability', function (): void {
    $target = new RecordTypeReader()->describeProperty(WithCollections::class, 'vivifying');

    expect($target->isNullable)->toBeTrue()
        ->and($target->objectClass)->toBe(Profile::class);
});

it('flags an array property as a collection and offers no class to construct', function (): void {
    $target = new RecordTypeReader()->describeProperty(WithCollections::class, 'profiles');

    expect($target->isCollection)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('flags a Traversable object property as a collection', function (): void {
    $target = new RecordTypeReader()->describeProperty(WithCollections::class, 'tags');

    expect($target->isCollection)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('walks a dotted path through object segments', function (): void {
    $target = new RecordTypeReader()->describe(Article::class, 'author.name');

    expect($target->class)->toBe(Profile::class)
        ->and($target->property)->toBe('name')
        ->and($target->isNullable)->toBeFalse();
});

it('returns null for an unreadable property', function (): void {
    expect(new RecordTypeReader()->describeProperty(Profile::class, 'nope'))->toBeNull();
});

it('returns null when a path traverses a non-object segment', function (): void {
    expect(new RecordTypeReader()->describe(Article::class, 'title.nope'))->toBeNull();
});
