<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Closure;
use DateTimeImmutable;
use LogicException;
use ReflectionClass;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Category;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post as PostEntity;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\ConstructorSetRecord;
use Sylament\Schemas\Tests\Fixtures\State\Money;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Sylament\Schemas\Tests\Fixtures\State\ProxiedCategory;
use Sylament\Schemas\Tests\Fixtures\State\TaggedPost;
use Sylament\Schemas\Tests\Fixtures\State\TypedRecord;
use Sylament\Schemas\Tests\Fixtures\State\UninitializedPost;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Uid\Uuid;

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

it('refuses an object-collection segment and names relationship()', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags.0.name', 'Ada'))
        ->toThrow(LogicException::class, 'Declare relationship() on the component bound to this property');
});

it('refuses an object-collection leaf', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags', ['a']))
        ->toThrow(LogicException::class, 'Declare relationship() on the component bound to this property');
});

it('points an object-collection refusal at relationship() and getData()', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags', ['a']))
        ->toThrow(LogicException::class, 'the Repeater maps plain-array items otherwise')
        ->and(fn () => accessor()->setOnRecord(new TypedRecord(), 'tags', ['a']))
        ->toThrow(LogicException::class, 'map it by hand after getData()');
});

it('refuses a leaf write onto an adder-shaped collection property', function (): void {
    expect(fn () => accessor()->setOnRecord(new TaggedPost(), 'tags', ['php']))
        ->toThrow(LogicException::class, '"tags" is an object collection');
});

it('refuses to traverse an initialized collection intermediate', function (): void {
    expect(fn () => accessor()->setOnRecord(new TaggedPost(), 'tags.0.name', 'php'))
        ->toThrow(LogicException::class, '"tags" is an object collection');
});

it('refuses to construct an intermediate whose constructor requires arguments', function (): void {
    expect(fn () => accessor()->setOnRecord(new TypedRecord(), 'money.amount', 5))
        ->toThrow(LogicException::class, 'requires constructor arguments');
});

it('surfaces NoSuchPropertyException for a missing leaf setter on a record write', function (): void {
    expect(fn () => accessor()->setOnRecord(new Post(), 'nope', 'x'))
        ->toThrow(NoSuchPropertyException::class);
});

/**
 * A record whose constructor set every property no setter can change, each
 * holding a value the leaf rule compares; `$rank` and `$active` take the `0`
 * and the `false` a blank write must not match.
 */
function constructorSetRecord(?Category $section = null, int $rank = 5, bool $active = true, string $host = 'example.com', array $codes = [1, 2]): ConstructorSetRecord
{
    return new ConstructorSetRecord(
        host: $host,
        path: '/home',
        secret: 'sesame',
        rank: $rank,
        active: $active,
        publishedAt: new DateTimeImmutable('2026-10-01 12:00:00'),
        token: Uuid::fromString('0e6f2c1a-7b2d-4c3e-9f10-1a2b3c4d5e6f'),
        price: new Money(500, 'EUR'),
        section: $section ?? new Category(),
        codes: $codes,
    );
}

function leafWriteRefusal(Closure $write): string
{
    try {
        $write();
    } catch (LogicException $logicException) {
        return $logicException->getMessage();
    }

    test()->fail('Expected the write to be refused with a LogicException.');
}

function heldValueRefusal(string $path, string $held, string $written): string
{
    return sprintf("Cannot map state path \"%s\" onto %s: no setter can change that property, and it already holds another value (the record holds [%s], the write [%s]). Hand the constructor the form's value as it is, make the field's state match what the constructor keeps with dehydrateStateUsing(), or leave the field out of the form.", $path, ConstructorSetRecord::class, $held, $written);
}

it('writes a writable leaf through its setter even when it already holds the value', function (): void {
    $record = constructorSetRecord();

    accessor()->setOnRecord($record, 'title', 'Same');
    accessor()->setOnRecord($record, 'title', 'Same');

    expect($record->getTitle())->toBe('Same')
        ->and($record->getTitleWrites())->toBe(2);
});

it('leaves a property no setter can change alone when it already holds the value', function (string $path, mixed $value): void {
    $record = constructorSetRecord();
    $held = accessor()->getFromRecord($record, $path);

    accessor()->setOnRecord($record, $path, $value);

    expect(accessor()->getFromRecord($record, $path))->toBe($held);
})->with([
    'a public readonly string' => ['host', 'example.com'],
    'a private string behind a getter' => ['path', '/home'],
    'an int written as its numeric string' => ['rank', '5'],
    'an identical bool' => ['active', true],
    'an equal date' => ['publishedAt', new DateTimeImmutable('2026-10-01 12:00')],
    'an equal Uid' => ['token', Uuid::fromString('0e6f2c1a-7b2d-4c3e-9f10-1a2b3c4d5e6f')],
    'an equal value object' => ['price', new Money(500, 'EUR')],
    'an array of the same numbers written as numeric strings' => ['codes', ['1', '2']],
]);

it('takes an empty string and a null for each other on a property no setter can change', function (): void {
    $record = new ConstructorSetRecord(host: '', path: '/home', secret: 'sesame');

    accessor()->setOnRecord($record, 'rank', '');
    accessor()->setOnRecord($record, 'host', null);

    expect($record->rank)->toBeNull()
        ->and($record->host)->toBe('');
});

it('leaves a held number alone when the write is the same number in another scalar type', function (mixed $value): void {
    $record = constructorSetRecord(rank: 0);

    accessor()->setOnRecord($record, 'rank', $value);

    expect($record->rank)->toBe(0);
})->with([
    'a float' => [0.0],
    'a numeric string' => ['0'],
]);

it('compares a bool strictly and a null only with an empty string, where loose equality would match', function (string $path, int $rank, bool $active, mixed $value, string $held, string $written): void {
    expect(leafWriteRefusal(fn () => accessor()->setOnRecord(constructorSetRecord(rank: $rank, active: $active), $path, $value)))
        ->toBe(heldValueRefusal($path, $held, $written));
})->with([
    'null over a held 0' => ['rank', 0, true, null, 'int', 'null'],
    'null over a held false' => ['active', 5, false, null, 'bool', 'null'],
    'a non-empty string over a held true' => ['active', 5, true, 'x', 'bool', 'string'],
    'false over a held 0' => ['rank', 0, true, false, 'int', 'bool'],
]);

it('refuses another value on a property no setter can change, naming the two types and never the values', function (string $path, mixed $value, string $held, string $written, array $printed, ?ConstructorSetRecord $record = null): void {
    $message = leafWriteRefusal(fn () => accessor()->setOnRecord($record ?? constructorSetRecord(), $path, $value));

    expect($message)->toBe(heldValueRefusal($path, $held, $written));

    foreach ($printed as $fragment) {
        expect($message)->not->toContain($fragment);
    }
})->with([
    'another string' => ['host', 'other.org', 'string', 'string', ['example.com', 'other.org']],
    'another date' => ['publishedAt', new DateTimeImmutable('2026-10-02'), DateTimeImmutable::class, DateTimeImmutable::class, ['2026-10-01', '2026-10-02']],
    'a string over a date' => ['publishedAt', '2026-10-01 12:00:00', DateTimeImmutable::class, 'string', ['2026-10-01']],
    'another value object' => ['price', new Money(600, 'EUR'), Money::class, Money::class, ['500', '600', 'EUR']],
    'null over an int' => ['rank', null, 'int', 'null', ['5']],
    'null over a value object, as the nullable-branch pass writes it' => ['price', null, Money::class, 'null', ['500', 'EUR']],
    'another numeric string, which PHP would compare as a number' => ['host', '7', 'string', 'string', ['007'], constructorSetRecord(host: '007')],
    'an array holding another numeric string' => ['codes', ['007'], 'array', 'array', ['007'], constructorSetRecord(codes: ['7'])],
    'an array holding 0 over a held false' => ['codes', [0], 'array', 'array', [], constructorSetRecord(codes: [false])],
    'an array holding one element more' => ['codes', [1, 2, 3], 'array', 'array', [], constructorSetRecord()],
]);

it('refuses a property no setter can change that holds no value yet', function (string $path): void {
    $record = new ReflectionClass(ConstructorSetRecord::class)->newInstanceWithoutConstructor();

    expect(fn () => accessor()->setOnRecord($record, $path, 'example.com'))
        ->toThrow(LogicException::class, sprintf("Cannot map state path \"%s\" onto %s: no setter can change that property, and it holds no value yet, so only the record's constructor can give it one. Build the record with that value before the form is mapped onto it, or leave the field out of the form.", $path, ConstructorSetRecord::class));
})->with([
    'public readonly' => ['host'],
    'private behind a getter' => ['path'],
]);

it('compares a record association by identity, never by its fields', function (): void {
    $section = new Category();
    $record = constructorSetRecord($section);

    accessor()->setOnRecord($record, 'section', $section);

    expect($record->section)->toBe($section)
        ->and(fn () => accessor()->setOnRecord($record, 'section', new Category()))
        ->toThrow(LogicException::class, heldValueRefusal('section', Category::class, Category::class));
});

it('refuses a lazy record without loading it', function (): void {
    $reflection = new ReflectionClass(Category::class);
    $ghost = $reflection->newLazyGhost(static function (Category $category): void {
        $category->setName('loaded');
    });

    expect(fn () => accessor()->setOnRecord(constructorSetRecord(), 'section', $ghost))
        ->toThrow(LogicException::class, heldValueRefusal('section', Category::class, Category::class))
        ->and($reflection->isUninitializedLazyObject($ghost))->toBeTrue();
});

it('reads a proxy subclass as the record it extends', function (): void {
    $record = new ConstructorSetRecord(host: 'example.com', path: '/home', secret: 'sesame', section: new ProxiedCategory());

    expect(fn () => accessor()->setOnRecord($record, 'section', new ProxiedCategory()))
        ->toThrow(LogicException::class, heldValueRefusal('section', ProxiedCategory::class, ProxiedCategory::class));
});

it('still surfaces NoSuchPropertyException for a property it cannot read, whatever it holds', function (string $path, string $value): void {
    expect(fn () => accessor()->setOnRecord(constructorSetRecord(), $path, $value))
        ->toThrow(NoSuchPropertyException::class, sprintf('Could not determine access type for property "%s"', $path));
})->with([
    'a private readonly property with no getter, written the value it holds' => ['secret', 'sesame'],
    'a misspelled property' => ['nope', 'x'],
]);

it('skips an association write of the record a property no setter can change already holds', function (): void {
    $section = new Category();
    $record = constructorSetRecord($section);

    accessor()->setAssociationOnRecord($record, 'section', $section);

    expect($record->section)->toBe($section);
});

it('refuses an association write of another record or null over the record a property no setter can change holds', function (?Category $value, string $written): void {
    expect(fn () => accessor()->setAssociationOnRecord(constructorSetRecord(), 'section', $value))
        ->toThrow(LogicException::class, sprintf('Cannot write association "section" onto %s: no setter can change that property, and it already holds another value (the record holds [%s], the write [%s]). Build the record around the record it is attached to, or give the property a setter.', ConstructorSetRecord::class, Category::class, $written));
})->with([
    'another record' => [new Category(), Category::class],
    'null, as a detach writes it' => [null, 'null'],
]);

it('refuses an association write onto a property no setter can change that holds no record yet', function (): void {
    $record = new ReflectionClass(ConstructorSetRecord::class)->newInstanceWithoutConstructor();

    expect(fn () => accessor()->setAssociationOnRecord($record, 'section', new Category()))
        ->toThrow(LogicException::class, sprintf("Cannot write association \"section\" onto %s: no setter can change that property, and it holds no value yet, so only the record's constructor can give it one. Build the record with its related record before attaching it.", ConstructorSetRecord::class));
});

it('writes a single related record onto a writable association as before', function (): void {
    $record = constructorSetRecord();
    $category = new Category();

    accessor()->setAssociationOnRecord($record, 'category', $category);

    expect($record->getCategory())->toBe($category);
});

it('writes a list raw, through the adder or Symfony refusal, never through the leaf rule', function (): void {
    $category = new Category();
    $post = new PostEntity();

    accessor()->setAssociationOnRecord($category, 'posts', [$post]);

    expect($category->getPosts()->toArray())->toBe([$post])
        ->and(fn () => accessor()->setAssociationOnRecord(constructorSetRecord(), 'section', []))
        ->toThrow(NoSuchPropertyException::class, 'is a promoted readonly property');
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

it('answers the native type a record declares at a path', function (): void {
    expect(accessor()->getBuiltinTypeOnRecord(TypedRecord::class, 'slug'))->toBe('string')
        ->and(accessor()->getBuiltinTypeOnRecord(TypedRecord::class, 'money.amount'))->toBe('int');
});

it('answers no native type for an object leaf or a path it cannot read', function (): void {
    expect(accessor()->getBuiltinTypeOnRecord(TypedRecord::class, 'status'))->toBeNull()
        ->and(accessor()->getBuiltinTypeOnRecord(Post::class, 'meta.seo'))->toBeNull();
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
