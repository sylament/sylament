<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\CipherStrongbox;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\HexCodedSwatch;
use Sylament\Tests\Fixtures\Entity\HexCodedTile;
use Sylament\Tests\Fixtures\Entity\HostPathRedirect;
use Sylament\Tests\Fixtures\Entity\RackTier;
use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Sylament\Tests\Fixtures\Entity\RawUuidPallet;
use Sylament\Tests\Fixtures\Entity\ShelfZone;
use Sylament\Tests\Fixtures\Entity\SubtypedVoucher;
use Sylament\Tests\Fixtures\Schemas\Doctrine\Cipher;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CipherType;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Uid\Uuid;

describe('printable', function (): void {
    it('prints an identifier value one way on both sides of a key', function (mixed $value, string $key): void {
        $em = $this->entityManager();

        expect(RecordKey::fromValues($em, CodeKeyedSlate::class, [$value]))->toBe($key)
            ->and(RecordKey::fromValues($em, HostPathRedirect::class, [$value, 'x']))->toBe("{$key}~x");
    })->with([
        'a string-backed enum, as its backing value' => [ShelfZone::Footer, 'footer'],
        'an int-backed enum, as its backing value' => [RackTier::Top, '3'],
        'a value object, as its string' => [new Cipher('omega'), 'omega'],
        'true' => [true, '1'],
        'false' => [false, '0'],
        'an integer' => [7, '7'],
    ]);

    it('refuses a value with no printed form', function (mixed $value): void {
        expect(fn (): string => RecordKey::fromValues($this->entityManager(), CodeKeyedSlate::class, [$value]))
            ->toThrow(LogicException::class, sprintf('The identifier of [%s] is not a printable value.', CodeKeyedSlate::class));
    })->with([
        'null' => [null],
        'an array' => [['a']],
        'an object that does not print' => [new ArrayObject()],
        'invalid UTF-8' => ["\xFF"],
        'blank' => ['  '],
        'a NUL byte' => ["a\0b"],
    ]);

    it('names a value object it refuses by what it prints', function (): void {
        expect(fn (): string => RecordKey::fromValues($this->entityManager(), CodeKeyedSlate::class, [new Cipher('  ')]))
            ->toThrow(LogicException::class, sprintf('The identifier of [%s] is not a printable value. It holds code "  ".', CodeKeyedSlate::class));
    });

    it('decodes no component carrying a NUL byte', function (): void {
        expect(RecordKey::toIdentifier($this->entityManager(), HostPathRedirect::class, 'a=00b~c'))->toBeNull()
            ->and(RecordKey::toIdentifier($this->entityManager(), HostPathRedirect::class, 'a=01b~c'))->toBe(['host' => "a\x01b", 'path' => 'c']);
    });
});

describe('decode through the writer', function (): void {
    it("decodes a raw-bytes uuid key through the type's writer, alone and beside a string", function (object $record, string $key, array $identifier): void {
        $this->seed($record);

        $em = $this->entityManager();
        $decoded = RecordKey::toIdentifier($em, $record::class, $key);

        expect($decoded)->toEqual($identifier)
            ->and(RecordKey::of($em, $em->find($record::class, $decoded ?? []) ?? throw new LogicException('No record found.')))->toBe($key);
    })->with([
        'a single uuid' => fn (): array => [new RawUuidCrate(Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')), 'a1b2c3d4-0000-4000-8000-000000000001', ['id' => Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')]],
        'a uuid half' => fn (): array => [new RawUuidPallet(Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north'), 'b1b2c3d4-0000-4000-8000-000000000001~north', ['batch' => Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'lane' => 'north']],
    ]);

    it('refuses every spelling of a raw-bytes uuid but the one its value prints, without a statement', function (string $key): void {
        $query = $this->entityManager()->createQueryBuilder()->select('c')->from(RawUuidCrate::class, 'c');

        expect(IdentifierConstraint::equals($query, 'c', RawUuidCrate::class, $key, 'key'))->toBeNull()
            ->and($this->statementsRun())->toBeEmpty();
    })->with([
        'upper case' => ['A1B2C3D4-0000-4000-8000-000000000001'],
        'thirty-two hex digits' => ['a1b2c3d4000040008000000000000001'],
        'braces' => ['{a1b2c3d4-0000-4000-8000-000000000001}'],
        'a urn prefix' => ['urn:uuid:a1b2c3d4-0000-4000-8000-000000000001'],
        'base58' => [Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')->toBase58()],
        'base32' => [Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')->toBase32()],
        'the raw bytes' => [Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')->toBinary()],
        'garbage' => ['not-a-uuid'],
    ]);

    it("decodes a key its type's reader misreads through the type's writer, and no other spelling", function (string $class, string $key, ?array $identifier): void {
        expect(RecordKey::toIdentifier($this->entityManager(), $class, $key))->toBe($identifier);
    })->with([
        'a single hex code' => [HexCodedSwatch::class, 'ff', ['code' => 'ff']],
        'a hex code behind an association' => [HexCodedTile::class, 'ff~north', ['swatch' => 'ff', 'lane' => 'north']],
        'upper case, which the writer refuses' => [HexCodedSwatch::class, 'FF', null],
        'a leading zero, which prints back without it' => [HexCodedSwatch::class, '0ff', null],
        'a sign' => [HexCodedSwatch::class, '+ff', null],
    ]);

    it('reaches a refusing writer once for a component its reader refused', function (): void {
        $em = $this->entityManager();
        $em->getConnection();

        $type = Type::getType(CipherType::NAME);
        assert($type instanceof CipherType);
        $writes = $type->writes;

        expect(RecordKey::toIdentifier($em, CipherStrongbox::class, 'omega'))->toBeNull()
            ->and($type->writes - $writes)->toBe(1);
    });
});

describe('shape checks', function (): void {
    it('decodes a key of a type extending a shape-checked family, and refuses a malformed component of it', function (string $key, bool $decodes): void {
        expect(RecordKey::toIdentifier($this->entityManager(), SubtypedVoucher::class, $key) !== null)->toBe($decodes);
    })->with([
        'well formed' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~1', true],
        'a bigint that is not a number' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~abc~12=2E25~1', false],
        'a bigint with a leading zero' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~07~12=2E25~1', false],
        'a decimal in exponent form' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~1e3~1', false],
        'a decimal with a trailing point' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E~1', false],
        'a boolean spelled as a word' => ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~true', false],
    ]);

    it('checks a boolean component as 0 or 1 before a PostgreSQL writer can refuse it', function (TestPlatform $platform): void {
        $em = $this->platformEntityManager($platform);

        expect(RecordKey::toIdentifier($em, SubtypedVoucher::class, 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~maybe'))->toBeNull()
            ->and(RecordKey::toIdentifier($em, SubtypedVoucher::class, 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7~12=2E25~0'))->not->toBeNull();
    })->with([
        'PostgreSQL' => [TestPlatform::PostgreSql],
        'SQLite' => [TestPlatform::Sqlite],
    ]);

    it('shapes a guid component only where the platform declares a guid type of its own', function (TestPlatform $platform, bool $decodes): void {
        $em = $this->platformEntityManager($platform);

        expect(RecordKey::toIdentifier($em, SubtypedVoucher::class, 'not-a-uuid~7~12=2E25~1') !== null)->toBe($decodes);
    })->with([
        'PostgreSQL, a native uuid' => [TestPlatform::PostgreSql, false],
        'MySQL, a fixed string' => [TestPlatform::MySql, true],
        'SQLite, a fixed string' => [TestPlatform::Sqlite, true],
    ]);

    it('resolves a guid identifier the platform stores as plain text', function (): void {
        $this->seed(new SubtypedVoucher('legacy-token', 7, '12.25', true, note: 'kept'));

        $em = $this->entityManager();
        $key = RecordKey::of($em, $em->getRepository(SubtypedVoucher::class)->findAll()[0]);
        $query = $em->createQueryBuilder()->select('v')->from(SubtypedVoucher::class, 'v');
        $query->where(IdentifierConstraint::equals($query, 'v', SubtypedVoucher::class, $key, 'key'));

        expect($key)->toBe('legacy-token~7~12=2E25~1')
            ->and(array_map(fn (SubtypedVoucher $voucher): string => $voucher->note, $query->getQuery()->getResult()))->toBe(['kept']);
    })->skip(TestPlatform::current() === TestPlatform::PostgreSql, 'PostgreSQL declares a native uuid type, so a guid column there cannot hold a non-UUID value.');
});
