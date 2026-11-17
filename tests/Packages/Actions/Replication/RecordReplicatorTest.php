<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use Sylament\Actions\Replication\RecordReplicator;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\StowedCrate;
use Symfony\Component\Uid\Uuid;

describe('replicate()', function (): void {
    it('copies the plain fields, and leaves the identifier, the version, a generated column, a column no insert writes and an excluded name off the copy', function (): void {
        $crate = new StowedCrate('Tea', port: 'Leith', deck: 'Upper');
        $crate->id = 4;
        $crate->revision = 5;
        $crate->sequence = 9;
        $crate->status = 'shipped';

        $replicator = new RecordReplicator($this->entityManager());
        $replica = $replicator->replicate($crate);
        $excluded = $replicator->replicate($crate, ['contents']);

        expect($replica)->toBeInstanceOf(StowedCrate::class)
            ->toBeRecord(contents: 'Tea', revision: 1, sequence: 0, status: 'pending')
            ->and(isset($replica->id))->toBeFalse()
            ->and($excluded)->toBeRecord(revision: 1)
            ->and(isset($excluded->contents))->toBeFalse();
    });

    it("clones each embeddable, a nested one included, so a change to the copy leaves the original's row alone", function (): void {
        $this->seed(new StowedCrate('Tea', port: 'Leith', deck: 'Upper'));

        $entityManager = $this->entityManager();
        $original = $entityManager->getRepository(StowedCrate::class)->findOneBy([]) ?? throw new LogicException('No crate was seeded.');
        $replica = new RecordReplicator($entityManager)->replicate($original);

        $replica->label->place->deck = 'Lower';

        $entityManager->persist($replica);
        $entityManager->flush();

        expect($this->reload($original)->label->place->deck)->toBe('Upper')
            ->and($this->reload($replica))->toBeRecord(contents: 'Tea', revision: 1, sequence: 7, status: 'pending')
            ->and($replica->label->port)->toBe('Leith')
            ->and($replica->label->place->deck)->toBe('Lower')
            ->and($replica->label)->not->toBe($original->label);
    });

    it('copies a to-one reference as the same object, leaving a proxy uninitialised, and starts every collection empty and unshared', function (): void {
        $larder = new Larder('Cellar');
        $larder->supplier = new Product('Jam Co');
        $larder->addJar(new LarderJar('Plum'));
        $this->seed($larder->supplier, $larder);

        $entityManager = $this->entityManager();
        $original = $entityManager->find(Larder::class, $larder->id) ?? throw new LogicException('No larder was seeded.');
        $replica = new RecordReplicator($entityManager)->replicate($original);

        expect($replica->supplier)->toBe($original->supplier)
            ->and($entityManager->getUnitOfWork()->isUninitializedObject($replica->supplier))->toBeTrue()
            ->and($replica->jars)->toBeInstanceOf(ArrayCollection::class)->toHaveCount(0)
            ->and($original->jars)->toHaveCount(1)
            ->and($replica->crocks)->toHaveCount(0)->not->toBe($original->crocks);
    });

    it('keeps an identifier association, so a replica keeps its parent, unless that one association is the whole identifier', function (): void {
        $replicator = new RecordReplicator($this->entityManager());
        $line = $replicator->replicate(new InvoiceLine($invoice = new Invoice(7), line: 2, note: 'Bolts'));
        $vent = $replicator->replicate(new GreenhouseVent(new Greenhouse(Uuid::v7())));

        expect($line)->toBeRecord(invoice: $invoice, note: 'Bolts')
            ->and(isset($line->line))->toBeFalse()
            ->and($vent)->toBeInstanceOf(GreenhouseVent::class)
            ->and(isset($vent->greenhouse))->toBeFalse();
    });
});

describe('the field bag', function (): void {
    it('reads only the copyable fields, and writes them back, refusing by name a field the copy rules decline', function (): void {
        $crate = new StowedCrate('Tea', port: 'Leith', deck: 'Upper');
        $crate->id = 4;

        $replicator = new RecordReplicator($this->entityManager());
        $replica = $replicator->replicate($crate);
        $replicator->setFieldValues($replica, ['contents' => 'Rum']);

        expect($replicator->getFieldValues($crate))->toBe(['contents' => 'Tea'])
            ->and($replica)->toBeRecord(contents: 'Rum')
            ->and(fn () => $replicator->setFieldValues($replica, ['revision' => 2]))
            ->toThrow(LogicException::class, StowedCrate::class . ' has no copyable mapped field [revision].');
    });
});
