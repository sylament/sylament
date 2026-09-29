<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Account;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Bay;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Dock;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Microcode;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Mooring;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Parcel;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pier;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Receipt;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tier;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Triplet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Truck;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * One record of every identifier shape the codec supports by construction, keyed
 * the same way every time: a dock (id 1) first, then the record the shape names.
 */
final class KeyShapeRig
{
    public const string UUID = '0190b8f6-1c2d-7e3f-8a4b-5c6d7e8f9a0b';

    public const string ULID = '01J2WFG72D7RZ8M8TKTYDXZ6GB';

    public const string OTHER_ULID = '01J2WFG72D7RZ8M8TKTYDXZ6GC';

    /**
     * @return class-string
     */
    public static function seed(EntityManager $entityManager, string $shape): string
    {
        $dock = new Dock();
        $dock->setName('North');

        $entityManager->persist($dock);
        $entityManager->flush();

        $record = self::build($entityManager, $shape);
        EntityManagerFactory::seed($entityManager, $record);

        return $record::class;
    }

    /**
     * A new, unpersisted record of the shape, carrying the key seed() stored.
     */
    public static function build(EntityManager $entityManager, string $shape): object
    {
        $dock = $entityManager->find(Dock::class, 1) ?? throw new LogicException('Seed a key shape before building a second record of it.');

        return match ($shape) {
            'three scalar fields' => self::triplet(),
            'an association in the middle' => self::mooring($dock),
            'an int-backed enum half' => self::bay(),
            'ids on a mapped superclass' => self::account(),
            'a joined hierarchy under a composite root' => self::truck(),
            'a single identifier association' => self::pier($dock),
            'smallint and ascii_string' => self::microcode(),
            "symfony's uuid beside an int" => self::parcel(),
            "symfony's ulid alone" => self::receipt(),
            default => throw new LogicException(sprintf('No key shape is named [%s].', $shape)),
        };
    }

    private static function triplet(): Triplet
    {
        $triplet = new Triplet();
        $triplet->setRegion('eu');
        $triplet->setSlot(1);
        $triplet->setCode('c');
        $triplet->setLabel('triplet');

        return $triplet;
    }

    private static function mooring(Dock $dock): Mooring
    {
        $mooring = new Mooring();
        $mooring->setLane('L1');
        $mooring->setDock($dock);
        $mooring->setSeq(3);

        return $mooring;
    }

    private static function bay(): Bay
    {
        $bay = new Bay();
        $bay->setTier(Tier::High);
        $bay->setSlot(2);
        $bay->setLabel('bay');

        return $bay;
    }

    private static function account(): Account
    {
        $account = new Account();
        $account->setTenant('acme');
        $account->setCode('x-1');
        $account->setLabel('account');

        return $account;
    }

    private static function truck(): Truck
    {
        $truck = new Truck();
        $truck->setFleet('north');
        $truck->setNumber(7);
        $truck->setAxles(3);

        return $truck;
    }

    private static function pier(Dock $dock): Pier
    {
        $pier = new Pier();
        $pier->setDock($dock);
        $pier->setNote('pier');

        return $pier;
    }

    private static function microcode(): Microcode
    {
        $microcode = new Microcode();
        $microcode->setA(5);
        $microcode->setB('ab');

        return $microcode;
    }

    private static function parcel(): Parcel
    {
        $parcel = new Parcel();
        $parcel->setBatch(Uuid::fromString(self::UUID));
        $parcel->setSeq(1);
        $parcel->setLabel('parcel');

        return $parcel;
    }

    private static function receipt(): Receipt
    {
        $receipt = new Receipt();
        $receipt->setId(Ulid::fromString(self::ULID));
        $receipt->setLabel('receipt');

        return $receipt;
    }
}
