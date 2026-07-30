<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use LogicException;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Sylament\Schemas\Tests\Fixtures\State\UninitializedPost;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

function accessor(): PropertyAccessor
{
    return new PropertyAccessor(PropertyAccess::createPropertyAccessor());
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

it('throws LogicException when a null object intermediate cannot take the vivified array', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'author.name', 'Ada'))
        ->toThrow(LogicException::class, 'author.name');
});

it('throws LogicException when an uninitialized object intermediate cannot take the vivified array', function (): void {
    expect(fn () => accessor()->setOnRecord(new UninitializedPost(), 'author.name', 'Ada'))
        ->toThrow(LogicException::class, 'author.name');
});

it('surfaces NoSuchPropertyException for a missing leaf setter on a record write', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'nope', 'x'))
        ->toThrow(NoSuchPropertyException::class);
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
