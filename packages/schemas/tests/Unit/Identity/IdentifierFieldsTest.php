<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierFields;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tariff;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;

it('reads the identifier fields in declaration order, typing an association by its target', function (): void {
    $entityManager = EntityManagerFactory::create();

    $translation = IdentifierFields::of($entityManager, PostTranslation::class);
    $seal = IdentifierFields::of($entityManager, PostSeal::class);
    $placement = IdentifierFields::of($entityManager, Placement::class);

    expect($translation->names)->toBe(['post', 'locale'])
        ->and($translation->associations)->toBe([true, false])
        ->and($translation->types)->toBe(['integer', 'string'])
        ->and($translation->isComposite())->toBeTrue()
        ->and($seal->names)->toBe(['post'])
        ->and($seal->types)->toBe(['integer'])
        ->and($seal->isComposite())->toBeFalse()
        ->and($placement->names)->toBe(['zone', 'slot'])
        ->and($placement->types)->toBe(['string', 'integer'])
        ->and(IdentifierFields::of($entityManager, ListingNote::class)->types)->toBe(['sku', 'string'])
        ->and(IdentifierFields::of($entityManager, Ticket::class)->types)->toBe(['guid', 'bigint']);
});

it('types an association by following it to the scalar its target is keyed on', function (): void {
    $chained = IdentifierFields::of(EntityManagerFactory::create(), PostSealNote::class);

    expect($chained->names)->toBe(['seal', 'code'])
        ->and($chained->associations)->toBe([true, false])
        ->and($chained->types)->toBe(['integer', 'string']);
});

it('prints an identifier value one way, whichever side of a key it arrives from', function (mixed $value, ?string $expected): void {
    expect(IdentifierFields::printable($value))->toBe($expected);
})->with([
    'an integer' => [5, '5'],
    'a string' => ['en/GB', 'en/GB'],
    'a stringable' => [new Sku('ab-123'), 'AB-123'],
    'a backed enum' => [Zone::Footer, 'footer'],
    'true' => [true, '1'],
    'false, which a plain cast would print blank' => [false, '0'],
    'a blank string' => ['  ', null],
    'bytes that are not UTF-8' => ["\xC5", null],
    'a NUL byte, which a text parameter would truncate at' => ["abc\0xyz", null],
    'null' => [null, null],
    'an array' => [['en'], null],
    'an object with no string form' => [new stdClass(), null],
]);

it('joins a composite key over an alphabet that needs no encoding anywhere it travels', function (string ...$components): void {
    $entityManager = EntityManagerFactory::create();
    $fields = IdentifierFields::of($entityManager, Redirect::class);

    $key = $fields->join(array_values($components));

    expect($key)->toMatch('/^[A-Za-z0-9_=~-]+$/')
        ->and(mb_substr_count($key, '~'))->toBe(1)
        ->and(is_numeric($key))->toBeFalse()
        ->and($fields->split($key, $entityManager->getConnection()->getDatabasePlatform()))->toBe(array_values($components));
})->with([
    'plain' => ['plain', 'about'],
    'dot and slash' => ['www.example.com', '/blog/2026'],
    'the separator and the escape' => ['a~b', 'c=d'],
    'a percent-encoded slash' => ['a%2Fb', '%'],
    'what a data-model directive reserves' => ['a|b(c)', 'd,e [f]'],
    'what a state path strips' => ['<a>"b"', "c'd`e"],
    'unicode' => ['Žofia', 'сложный путь'],
    'digits only' => ['5', '7'],
    'components longer than a backtracking match can hold' => [str_repeat('a', 100_000), str_repeat('/', 100_000)],
]);

it('spells the hostile components out', function (): void {
    $fields = IdentifierFields::of(EntityManagerFactory::create(), Redirect::class);

    expect($fields->join(['www.example.com', '/blog/a b']))->toBe('www=2Eexample=2Ecom~=2Fblog=2Fa=20b')
        ->and($fields->join(['Ž', '~']))->toBe('=C5=BD~=7E');
});

it('refuses every spelling the encoder never emits', function (string $key): void {
    $entityManager = EntityManagerFactory::create();

    expect(IdentifierFields::of($entityManager, Redirect::class)->split($key, $entityManager->getConnection()->getDatabasePlatform()))->toBeNull();
})->with([
    'one component' => ['plain'],
    'three components' => ['a~b~c'],
    'an empty leading component' => ['~b'],
    'an empty trailing component' => ['a~'],
    'a lowercase escape' => ['a~=2f'],
    'a truncated escape' => ['a~=2'],
    'an escaped safe byte' => ['a~=41'],
    'a raw space' => ['a~b c'],
    'a raw dot' => ['a~b.c'],
    'a raw slash' => ['a~b/c'],
    'a percent escape' => ['a~%2F'],
    'invalid UTF-8' => ['a~=C5'],
    'blank' => [''],
    'whitespace' => ['  '],
    'a raw trailing newline' => ["a~b\n"],
]);

it('answers the PHP values a key names, and nothing for a spelling the record side never mints', function (string $class, string $key, ?array $expected): void {
    $entityManager = EntityManagerFactory::create();

    expect(IdentifierFields::of($entityManager, $class)->toPhpValues($entityManager, $key))->toEqual($expected);
})->with([
    'a single integer' => [Author::class, '5', [5]],
    'a negative integer' => [Author::class, '-5', [-5]],
    'zero' => [Author::class, '0', [0]],
    'the largest integer' => [Author::class, (string) PHP_INT_MAX, [PHP_INT_MAX]],
    'letters' => [Author::class, 'abc', null],
    'a leading zero' => [Author::class, '05', null],
    'a trailing letter' => [Author::class, '5abc', null],
    'a leading space' => [Author::class, ' 5', null],
    'a trailing newline' => [Author::class, "5\n", null],
    'a plus sign' => [Author::class, '+5', null],
    'a decimal' => [Author::class, '5.0', null],
    'an exponent' => [Author::class, '1e3', null],
    'negative zero' => [Author::class, '-0', null],
    'an integer the cast would saturate' => [Author::class, '99999999999999999999', null],
    'a decimal identifier' => [Tariff::class, '10.50', ['10.50']],
    'a decimal of letters' => [Tariff::class, 'abc', null],
    'a decimal with a leading space' => [Tariff::class, ' 10.50', null],
    'a decimal with a trailing newline' => [Tariff::class, "10.50\n", null],
    'a decimal with leading zeros' => [Tariff::class, '0010.50', null],
    'a decimal with a plus sign' => [Tariff::class, '+10.50', null],
    'a raw NUL' => [Shortlink::class, "abc\0xyz", null],
    'an escaped NUL' => [Redirect::class, 'a~b=00', null],
    'an association typed by its target' => [PostSeal::class, 'abc', null],
    'a derived identity' => [PostTranslation::class, '1~en', [1, 'en']],
    'an escaped trailing newline' => [PostTranslation::class, '1=0A~en', null],
    'a derived identity with a non-integer target' => [PostTranslation::class, 'one~en', null],
    'an enum pair' => [Placement::class, 'header~1', ['header', 1]],
    'a strict value object' => [Listing::class, 'AB-123', [new Sku('AB-123')]],
    'a value the strict type refuses' => [Listing::class, 'not-a-sku', null],
    'a spelling the strict type would print back differently' => [Listing::class, 'ab-123', null],
    'a guid and a bigint' => [Ticket::class, IdentityRig::TICKET_ID . '~7', [IdentityRig::TICKET_ID, 7]],
    'a bigint beyond a PHP integer stays a string' => [Ticket::class, IdentityRig::TICKET_ID . '~99999999999999999999', [IdentityRig::TICKET_ID, '99999999999999999999']],
    'a malformed bigint' => [Ticket::class, IdentityRig::TICKET_ID . '~abc', null],
    'a bigint beyond a PHP integer with a trailing newline' => [Ticket::class, IdentityRig::TICKET_ID . '~99999999999999999999=0A', null],
    'a bigint beyond a PHP integer with a leading zero' => [Ticket::class, IdentityRig::TICKET_ID . '~099999999999999999999', null],
]);

/*
 * A guid is only a UUID where the platform says so. PostgreSQL and SQL Server
 * declare a type of their own, and a component that is not 8-4-4-4-12 hex reaches
 * one as the syntax error the shape check exists to keep out of a bind; everywhere
 * else the declaration is a plain CHAR(36), the column holds whatever a record put
 * in it, and refusing that spelling would refuse a key the record side minted. The
 * PostgreSQL platform answers without a server, so this costs no connection.
 */
it('shapes a guid component only where the platform declares a guid type of its own', function (string $key, array $storedAsText): void {
    $postgres = EntityManagerFactory::createForPostgres();
    $sqlite = EntityManagerFactory::create();

    expect(IdentifierFields::of($postgres, Ticket::class)->toPhpValues($postgres, $key))->toBeNull()
        ->and(IdentifierFields::of($sqlite, Ticket::class)->toPhpValues($sqlite, $key))->toEqual($storedAsText);
})->with([
    'a malformed guid' => ['not-a-guid~7', ['not-a-guid', 7]],
    'a guid with a trailing newline' => [IdentityRig::TICKET_ID . '=0A~7', [IdentityRig::TICKET_ID . "\n", 7]],
]);

it('answers the values the database holds, for binding', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(IdentifierFields::of($entityManager, Listing::class)->toDatabaseValues($entityManager, 'AB-123'))->toBe(['ab-123'])
        ->and(IdentifierFields::of($entityManager, ListingNote::class)->toDatabaseValues($entityManager, 'AB-123~n1'))->toBe(['ab-123', 'n1'])
        ->and(IdentifierFields::of($entityManager, Listing::class)->toDatabaseValues($entityManager, 'not-a-sku'))->toBeNull();
});

it('leaves a single string identifier verbatim, separator and all', function (): void {
    $entityManager = EntityManagerFactory::create();
    $fields = IdentifierFields::of($entityManager, Shortlink::class);
    $platform = $entityManager->getConnection()->getDatabasePlatform();

    expect($fields->isComposite())->toBeFalse()
        ->and($fields->join(['a~b=zz/.']))->toBe('a~b=zz/.')
        ->and($fields->split('a~b=zz/.', $platform))->toBe(['a~b=zz/.'])
        ->and($fields->split('', $platform))->toBeNull()
        ->and($fields->split('  ', $platform))->toBeNull();
});
