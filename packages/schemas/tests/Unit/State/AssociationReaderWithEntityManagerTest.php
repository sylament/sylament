<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Symfony\Component\PropertyAccess\PropertyAccess;

/*
 * withEntityManager(): the reader bound to another entity manager, as a clone —
 * its class, its property accessor and whatever a subclass carries kept, and
 * the reader it was called on left bound as it was. Nothing here runs a
 * statement beyond the rigs' own seeding, so it reads the same on every
 * platform.
 */

function withEntityManagerBoundTo(AssociationReader $reader): ?EntityManagerInterface
{
    $manager = new ReflectionProperty(AssociationReader::class, 'entityManager')->getValue($reader);

    return $manager instanceof EntityManagerInterface ? $manager : null;
}

it('answers a clone bound to the manager and leaves the reader it was called on unbound', function (): void {
    $manager = EntityManagerFactory::create();
    $reader = new AssociationReader();
    $rebound = $reader->withEntityManager($manager);

    expect($rebound)->not->toBe($reader)
        ->and($rebound->hasEntityManager())->toBeTrue()
        ->and(withEntityManagerBoundTo($rebound))->toBe($manager)
        ->and($reader->hasEntityManager())->toBeFalse();
});

it('keeps the property accessor the reader holds', function (): void {
    $accessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    $rebound = new AssociationReader(propertyAccessor: $accessor)->withEntityManager(EntityManagerFactory::create());

    expect(new ReflectionProperty(AssociationReader::class, 'propertyAccessor')->getValue($rebound))->toBe($accessor);
});

it('keeps a subclass and the argument its own constructor took', function (): void {
    $log = new ArrayObject();
    $reader = new class ($log) extends AssociationReader {
        /**
         * @param ArrayObject<int, string> $log
         */
        public function __construct(public readonly ArrayObject $log)
        {
            parent::__construct();
        }
    };

    $manager = EntityManagerFactory::create();
    $rebound = $reader->withEntityManager($manager);

    expect($rebound)->toBeInstanceOf($reader::class)
        ->and($rebound->log)->toBe($log)
        ->and(withEntityManagerBoundTo($rebound))->toBe($manager)
        ->and(withEntityManagerBoundTo($reader))->toBeNull();
});

it('rebinds a reader that is already bound', function (): void {
    $first = EntityManagerFactory::create();
    $second = EntityManagerFactory::create();
    $bound = new AssociationReader($first);

    expect(withEntityManagerBoundTo($bound->withEntityManager($second)))->toBe($second)
        ->and(withEntityManagerBoundTo($bound->withEntityManager($second)->withEntityManager($first)))->toBe($first)
        ->and(withEntityManagerBoundTo($bound))->toBe($first);
});

it('reads through the manager it is rebound to, refusing a key moved on a record only that manager keeps', function (): void {
    $first = IdentifierGuardRig::create();
    $second = IdentifierGuardRig::create();
    $callsign = IdentifierGuardRig::callsign($second);
    $callsign->setCode('OK2');

    $reader = new AssociationReader($first);

    expect($reader->revertIdentifierChanges($callsign))->toBe([])
        ->and($callsign->getCode())->toBe('OK2')
        ->and($reader->withEntityManager($second)->revertIdentifierChanges($callsign))->toBe(['code' => 'This value cannot be changed.'])
        ->and($callsign->getCode())->toBe(IdentifierGuardRig::CALLSIGN);
});
