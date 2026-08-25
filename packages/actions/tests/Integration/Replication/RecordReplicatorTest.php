<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Replication;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use LogicException;
use Sylament\Actions\Replication\RecordReplicator;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Address;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Tagged;

/*
 * The replicator reads its rules off Doctrine metadata, so every case here runs
 * against the fixture entity manager rather than a hand-built double: what a
 * mapping means is the thing under test.
 */

function replicator(): RecordReplicator
{
    return new RecordReplicator(test()->getContainer()->get(EntityManagerInterface::class));
}

/**
 * A flushed `Tagged` whose version has been bumped by a second flush, so a case
 * can tell a copied version field from the replica's declared default.
 */
function seededTagged(string $slug = 'alpha', string $street = 'Main St'): Tagged
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $note = new Note();
    $note->setTitle('linked');

    $tagged = new Tagged();
    $tagged->setSlug('draft');
    $tagged->setAddress(new Address($street));
    $tagged->setNote($note);

    $entityManager->persist($note);
    $entityManager->persist($tagged);
    $entityManager->flush();

    $tagged->setSlug($slug);
    $tagged->getNotes()->add($note);
    $entityManager->flush();

    return $tagged;
}

it('copies every mapped field except the identifier, the version and the excluded names', function (): void {
    $tagged = seededTagged('alpha');

    $replica = replicator()->replicate($tagged);

    expect($replica)->toBeInstanceOf(Tagged::class)
        ->and($replica)->not->toBe($tagged)
        ->and($replica->getSlug())->toBe('alpha')
        ->and($replica->getId())->toBeNull()
        ->and($tagged->getVersion())->toBe(2)
        ->and($replica->getVersion())->toBe(1);

    expect(replicator()->replicate($tagged, ['slug'])->getSlug())->toBe('');
});

it('shares a to-one reference and starts every collection empty', function (): void {
    $tagged = seededTagged();

    $replica = replicator()->replicate($tagged);

    expect($replica->getNote())->toBe($tagged->getNote())
        ->and($tagged->getNotes())->toHaveCount(1)
        ->and($replica->getNotes()->isEmpty())->toBeTrue()
        ->and($replica->getNotes())->not->toBe($tagged->getNotes());

    expect(replicator()->replicate($tagged, ['note'])->getNote())->toBeNull();
});

it('clones an embeddable instead of sharing it', function (): void {
    $tagged = seededTagged(street: 'Main St');

    $replica = replicator()->replicate($tagged);

    expect($replica->getAddress()->getStreet())->toBe('Main St')
        ->and(spl_object_id($replica->getAddress()))->not->toBe(spl_object_id($tagged->getAddress()));

    $replica->getAddress()->setStreet('Side St');

    expect($tagged->getAddress()->getStreet())->toBe('Main St');
});

it('clones a nested embeddable rather than sharing it with the original', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tagged = seededTagged();
    $tagged->getAddress()->getGeo()->setCity('Prague');
    $entityManager->flush();

    $replica = replicator()->replicate($tagged);

    expect(spl_object_id($replica->getAddress()))->not->toBe(spl_object_id($tagged->getAddress()))
        ->and(spl_object_id($replica->getAddress()->getGeo()))->not->toBe(spl_object_id($tagged->getAddress()->getGeo()))
        ->and($replica->getAddress()->getGeo()->getCity())->toBe('Prague');

    $replica->getAddress()->getGeo()->setCity('Vienna');
    $entityManager->persist($replica);
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->getRepository(Tagged::class)->find($tagged->getId())?->getAddress()->getGeo()->getCity())->toBe('Prague');
});

it('leaves an identifier association off the replica', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $note = new Note();
    $note->setTitle('linked');

    $membership = new Membership();
    $membership->setNote($note);
    $membership->setRole('editor');
    $membership->setLabel('Editors');

    $entityManager->persist($note);
    $entityManager->persist($membership);
    $entityManager->flush();

    $replica = replicator()->replicate($membership);

    expect($replica->getLabel())->toBe('Editors')
        ->and($replica->getRole())->toBe('')
        ->and($replica->getNote())->toBeNull();
});

it('reads and writes the copied field bag', function (): void {
    $tagged = seededTagged('alpha');
    $replicator = replicator();

    expect($replicator->getFieldValues($tagged))->toBe(['slug' => 'alpha']);

    $replicator->setFieldValues($tagged, ['slug' => 'beta']);

    expect($tagged->getSlug())->toBe('beta')
        ->and(fn (): mixed => $replicator->setFieldValues($tagged, ['version' => 9]))
        ->toThrow(LogicException::class, 'has no copyable mapped field [version]');
});

it('persists the replica as a new row', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tagged = seededTagged('alpha');

    $replica = replicator()->replicate($tagged);
    $entityManager->persist($replica);
    $entityManager->flush();

    expect($replica->getId())->not->toBeNull()
        ->and($replica->getId())->not->toBe($tagged->getId())
        ->and($entityManager->getRepository(Tagged::class)->findBy(['slug' => 'alpha']))->toHaveCount(2);
});

it('fails loudly for an assigned identifier', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $assigned = new AssignedNote();
    $assigned->setId(1);
    $assigned->setTitle('Alpha');

    $entityManager->persist($assigned);
    $entityManager->flush();

    $replica = replicator()->replicate($assigned);

    expect($replica->getTitle())->toBe('Alpha')
        ->and(function () use ($entityManager, $replica): void {
            $entityManager->persist($replica);
            $entityManager->flush();
        })->toThrow(EntityMissingAssignedId::class);
});
