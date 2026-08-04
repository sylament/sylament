<?php

declare(strict_types=1);

use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Sylament\Schemas\Tests\Fixtures\State\TaggedPost;
use Sylament\Schemas\Tests\Fixtures\State\TypedRecord;

it('reports a non-nullable scalar as non-nullable', function (): void {
    $target = new RecordTypeReader()->describeProperty(TypedRecord::class, 'slug');

    expect($target)->not->toBeNull()
        ->and($target->isNullable)->toBeFalse()
        ->and($target->objectClass)->toBeNull()
        ->and($target->isCollection)->toBeFalse();
});

it('reports a nullable scalar as nullable', function (): void {
    $target = new RecordTypeReader()->describeProperty(Post::class, 'title');

    expect($target->isNullable)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('reports the class to construct for a nullable object property', function (): void {
    $target = new RecordTypeReader()->describeProperty(Post::class, 'author');

    expect($target->objectClass)->toBe(Author::class)
        ->and($target->isNullable)->toBeTrue()
        ->and($target->isCollection)->toBeFalse();
});

it('follows the mutator when the getter disagrees about nullability', function (): void {
    $target = new RecordTypeReader()->describeProperty(TypedRecord::class, 'vivifying');

    expect($target->isNullable)->toBeTrue()
        ->and($target->objectClass)->toBe(Author::class);
});

it('flags an array property as a collection and offers no class to construct', function (): void {
    $target = new RecordTypeReader()->describeProperty(Post::class, 'meta');

    expect($target->isCollection)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('flags a Traversable object property as a collection', function (): void {
    $target = new RecordTypeReader()->describeProperty(TypedRecord::class, 'tags');

    expect($target->isCollection)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('does not treat a plain array as an object collection', function (): void {
    $target = new RecordTypeReader()->describeProperty(Post::class, 'meta');

    expect($target->isCollection)->toBeTrue()
        ->and($target->isObjectCollection)->toBeFalse();
});

it('treats a Traversable object property as an object collection', function (): void {
    $target = new RecordTypeReader()->describeProperty(TypedRecord::class, 'tags');

    expect($target->isCollection)->toBeTrue()
        ->and($target->isObjectCollection)->toBeTrue();
});

it('reports an adder-shaped collection as an object collection', function (): void {
    $target = new RecordTypeReader()->describeProperty(TaggedPost::class, 'tags');

    expect($target)->not->toBeNull()
        ->and($target->isCollection)->toBeTrue()
        ->and($target->isObjectCollection)->toBeTrue()
        ->and($target->objectClass)->toBeNull();
});

it('offers no class to construct for an enum property', function (): void {
    $target = new RecordTypeReader()->describeProperty(TypedRecord::class, 'status');

    expect($target->objectClass)->toBeNull()
        ->and($target->isCollection)->toBeFalse()
        ->and($target->isNullable)->toBeTrue();
});

it('walks a dotted path through object segments', function (): void {
    $reader = new RecordTypeReader();

    $scalarLeaf = $reader->describe(Post::class, 'author.name');
    $nonNullableLeaf = $reader->describe(TypedRecord::class, 'money.amount');

    // Each answer is reachable only by descending a segment: the first segment
    // of either path reports an objectClass and a nullable type, so a walk that
    // stopped there would answer the opposite in both cases.
    expect($scalarLeaf)->not->toBeNull()
        ->and($scalarLeaf->objectClass)->toBeNull()
        ->and($nonNullableLeaf)->not->toBeNull()
        ->and($nonNullableLeaf->isNullable)->toBeFalse()
        ->and($nonNullableLeaf->objectClass)->toBeNull();
});

it('returns null for an unreadable property', function (): void {
    expect(new RecordTypeReader()->describeProperty(Post::class, 'nope'))->toBeNull();
});

it('returns null when a path traverses a non-object segment', function (): void {
    expect(new RecordTypeReader()->describe(Post::class, 'title.nope'))->toBeNull();
});
