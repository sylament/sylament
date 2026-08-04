<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use LogicException;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Sylament\Schemas\Tests\Fixtures\State\TaggedPost;
use Sylament\Schemas\Tests\Fixtures\State\TypedRecord;
use Sylament\Schemas\Tests\Fixtures\State\UninitializedPost;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

function accessor(): PropertyAccessor
{
    return new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
}

it('reads a nested value by dotted path (dot to bracket)', function (): void {
    $host = new class {
        public array $data = ['address' => ['city' => 'Prague']];
    };

    expect(accessor()->get($host, 'data.address.city'))->toBe('Prague');
});

it('returns null for a missing index without throwing', function (): void {
    $host = new class {
        public array $data = [];
    };

    expect(accessor()->get($host, 'data.missing'))->toBeNull();
});

it('returns null past a null intermediate', function (): void {
    $host = new class {
        public array $data = ['address' => null];
    };

    expect(accessor()->get($host, 'data.address.city'))->toBeNull();
});

it('returns null past a scalar intermediate', function (): void {
    $host = new class {
        public array $data = ['address' => 'Prague'];
    };

    expect(accessor()->get($host, 'data.address.city'))->toBeNull();
});

it('returns null past a null property on a record', function (): void {
    $record = new class {
        public ?object $author = null;
    };

    expect(accessor()->get($record, 'author.name'))->toBeNull();
});

it('writes the whole bag on a single-segment path', function (): void {
    $host = new class {
        public array $data = [];
    };

    accessor()->set($host, 'data', ['title' => 'Hi']);

    expect($host->data)->toBe(['title' => 'Hi']);
});

it('auto-vivifies nested arrays on write', function (): void {
    $host = new class {
        public array $data = [];
    };

    accessor()->set($host, 'data.address.city', 'Brno');

    expect($host->data)->toBe(['address' => ['city' => 'Brno']]);
});

it('vivifies a null intermediate on write', function (): void {
    $host = new class {
        public array $data = ['address' => null];
    };

    accessor()->set($host, 'data.address.city', 'Brno');

    expect($host->data)->toBe(['address' => ['city' => 'Brno']]);
});

it('clobbers a scalar intermediate on write', function (): void {
    $host = new class {
        public array $data = ['address' => 'Prague'];
    };

    accessor()->set($host, 'data.address.city', 'Brno');

    expect($host->data)->toBe(['address' => ['city' => 'Brno']]);
});

it('reads a detached nested array by dotted path, null when absent', function (): void {
    $state = ['a' => ['b' => 'deep']];

    expect(accessor()->getFromArray($state, 'a.b'))->toBe('deep')
        ->and(accessor()->getFromArray($state, 'a.missing'))->toBeNull()
        ->and(accessor()->getFromArray($state, 'a.b.past-scalar'))->toBeNull();
});

it('writes into a detached nested array, auto-vivifying intermediates', function (): void {
    $state = [];

    accessor()->setInArray($state, 'a.b.c', 1);

    expect($state)->toBe(['a' => ['b' => ['c' => 1]]]);
});

it('clobbers a non-array intermediate on write', function (): void {
    $state = ['a' => 'scalar'];

    accessor()->setInArray($state, 'a.b', 2);

    expect($state)->toBe(['a' => ['b' => 2]]);
});

it('forgets only the leaf, leaving the emptied parent behind', function (): void {
    $state = ['a' => ['b' => 1]];

    accessor()->forgetFromArray($state, 'a.b');

    expect($state)->toBe(['a' => []]);
});

it('forgets a list of paths and no-ops on a missing path', function (): void {
    $state = ['a' => 1, 'b' => 2, 'c' => 3];

    accessor()->forgetFromArray($state, ['a', 'c', 'x.y']);

    expect($state)->toBe(['b' => 2]);
});

it('hops nested objects by property on a record read', function (): void {
    $post = new Post();
    $post->setAuthor(new Author(name: 'Ada'));

    expect(accessor()->getFromRecord($post, 'author.name'))->toBe('Ada');
});

it('returns null past a null object intermediate on a record read', function (): void {
    expect(accessor()->getFromRecord(new Post(), 'author.name'))->toBeNull();
});

it('returns null past a scalar intermediate on a record read', function (): void {
    $record = new class {
        public string $author = 'not-an-object';
    };

    expect(accessor()->getFromRecord($record, 'author.name'))->toBeNull();
});

it('crosses an object-to-array boundary on a record read', function (): void {
    $post = new Post();
    $post->setMeta(['seo' => ['title' => 'Hi']]);

    expect(accessor()->getFromRecord($post, 'meta.seo.title'))->toBe('Hi');
});

it('returns null for a missing array key on a record read', function (): void {
    $post = new Post();
    $post->setMeta([]);

    expect(accessor()->getFromRecord($post, 'meta.missing'))->toBeNull();
});

it('returns a leaf object as-is on a record read', function (): void {
    $author = new Author();
    $post = new Post();
    $post->setAuthor($author);

    expect(accessor()->getFromRecord($post, 'author'))->toBe($author);
});

it('propagates AccessException for an unreadable record property', function (): void {
    $record = new class {
        protected string $secret = 'x';
    };

    expect(fn (): mixed => accessor()->getFromRecord($record, 'secret'))
        ->toThrow(AccessException::class);
});

it('propagates AccessException for an uninitialized record property read', function (): void {
    $record = new class {
        public string $title;
    };

    expect(fn (): mixed => accessor()->getFromRecord($record, 'title'))
        ->toThrow(AccessException::class);
});

it('writes through nested objects and mutates them in place', function (): void {
    $author = new Author();
    $post = new Post();
    $post->setAuthor($author);

    accessor()->setOnRecord($post, 'author.name', 'Ada');

    expect($post->getAuthor())->toBe($author)
        ->and($author->getName())->toBe('Ada');
});

it('writes a single-segment record path directly', function (): void {
    $post = new Post();

    accessor()->setOnRecord($post, 'title', 'Hello');

    expect($post->getTitle())->toBe('Hello');
});

it('vivifies a null array boundary on a record write', function (): void {
    $post = new Post();

    accessor()->setOnRecord($post, 'meta.seo', 'Hi');

    expect($post->getMeta())->toBe(['seo' => 'Hi']);
});

it('preserves unmanaged sibling keys across an array-boundary write', function (): void {
    $post = new Post();
    $post->setMeta(['keep' => 1]);

    accessor()->setOnRecord($post, 'meta.seo', 'Hi');

    expect($post->getMeta())->toBe(['keep' => 1, 'seo' => 'Hi']);
});

it('writes deep array remainders past the boundary', function (): void {
    $post = new Post();

    accessor()->setOnRecord($post, 'meta.seo.title', 'Hi');

    expect($post->getMeta())->toBe(['seo' => ['title' => 'Hi']]);
});

it('clobbers a scalar under the array boundary like setInArray', function (): void {
    $post = new Post();
    $post->setMeta(['seo' => 'scalar']);

    accessor()->setOnRecord($post, 'meta.seo.title', 'Hi');

    expect($post->getMeta())->toBe(['seo' => ['title' => 'Hi']]);
});

it('constructs a missing singular intermediate before writing a leaf', function (): void {
    $record = new Post();

    accessor()->setOnRecord($record, 'author.name', 'Ada');

    expect($record->getAuthor())->toBeInstanceOf(Author::class)
        ->and($record->getAuthor()?->getName())->toBe('Ada');
});

it('constructs an intermediate declared non-nullable and never assigned', function (): void {
    $record = new UninitializedPost();

    accessor()->setOnRecord($record, 'author.name', 'Ada');

    expect($record->getAuthor()->getName())->toBe('Ada');
});

it('mutates an existing intermediate in place rather than replacing it', function (): void {
    $record = new Post();
    $existing = new Author();
    $record->setAuthor($existing);

    accessor()->setOnRecord($record, 'author.name', 'Ada');

    expect($record->getAuthor())->toBe($existing);
});

it('still writes a plain array branch as nested array data', function (): void {
    $record = new Post();

    accessor()->setOnRecord($record, 'meta.seo.title', 'Hi');

    expect($record->getMeta())->toBe(['seo' => ['title' => 'Hi']]);
});

it('throws LogicException when a scalar intermediate cannot take the vivified array', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'title.nope', 'Ada'))
        ->toThrow(LogicException::class, 'title.nope');
});

it('names the object that rejected the array, not the root record', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'author.name.deep', 'Ada'))
        ->toThrow(LogicException::class, Author::class);
});

it('refuses an object-collection segment and names the repeater epic', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags.0.name', 'Ada'))
        ->toThrow(LogicException::class, 'Repeater epic');
});

it('refuses an object-collection leaf', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags', ['a']))
        ->toThrow(LogicException::class, 'Repeater epic');
});

it('refuses a leaf write onto an adder-shaped collection property', function (): void {
    expect(fn () => accessor()->setOnRecord(new TaggedPost(), 'tags', ['php']))
        ->toThrow(LogicException::class, '"tags" is a collection');
});

it('refuses to construct an intermediate whose constructor requires arguments', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'money.amount', 5))
        ->toThrow(LogicException::class, 'requires constructor arguments');
});

it('surfaces NoSuchPropertyException for a missing leaf setter on a record write', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'nope', 'x'))
        ->toThrow(NoSuchPropertyException::class);
});

it('reports a nullable record property as accepting null', function (): void {
    expect(accessor()->acceptsNullOnRecord(Post::class, 'author'))->toBeTrue();
});

it('reports a non-nullable record property as rejecting null', function (): void {
    expect(accessor()->acceptsNullOnRecord(UninitializedPost::class, 'author'))->toBeFalse();
});

it('reports a record path it cannot read as accepting null', function (): void {
    expect(accessor()->acceptsNullOnRecord(Post::class, 'meta.seo'))->toBeTrue();
});

it('reads nullability through an object hop rather than off the root', function (): void {
    expect(accessor()->acceptsNullOnRecord(UninitializedPost::class, 'author.name'))->toBeTrue()
        ->and(accessor()->acceptsNullOnRecord(TypedRecord::class, 'money.amount'))->toBeFalse();
});

it('reports a present leaf in a detached array', function (): void {
    expect(accessor()->hasInArray(['author' => ['name' => 'Ada']], 'author.name'))->toBeTrue();
});

it('reports a present-but-null leaf in a detached array', function (): void {
    expect(accessor()->hasInArray(['author' => ['name' => null]], 'author.name'))->toBeTrue();
});

it('reports an absent path in a detached array', function (): void {
    expect(accessor()->hasInArray(['author' => []], 'author.name'))->toBeFalse();
});

it('reports false past a scalar intermediate in a detached array', function (): void {
    expect(accessor()->hasInArray(['author' => 'Ada'], 'author.name'))->toBeFalse();
});
