<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\ColophonLine;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditColophon;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStamp;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ColophonLinesRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampMarksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampsRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\UnmappedOwnerRelationManager;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for the same reason `RelationManagerTest` disables
 * it: the fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 *
 * Every owner key below is a literal — catalogue "First" is `1`, the colophon
 * of that catalogue is `1`, and the stamp whose code reads `A/B C` under it is
 * `1~A=2FB=20C`. Asking the codec for an expected key would make a case pass
 * against any format at all.
 */

/**
 * One catalogue owning one stamp and one colophon, each owning one row of its
 * own — the three owner flavours the relationship table serves, in one
 * database.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeGuardOwnerClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha');
    $colophon = new Colophon()->setCatalogue($catalogue)->setNote('Note');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Mark'),
        $colophon,
        new ColophonLine()->setColophon($colophon)->setText('Line'),
    );

    return [$client, $entityManager];
}

/**
 * A second catalogue with a stamp of the very same code and a colophon of its
 * own, each owning one row — the rows a scope reading half an owner's
 * identifier, or none of it, would list beside the first owner's.
 */
function compositeGuardOwnerRivals(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('Second');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Beta');
    $colophon = new Colophon()->setCatalogue($catalogue)->setNote('Other note');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Rival mark'),
        $colophon,
        new ColophonLine()->setColophon($colophon)->setText('Rival line'),
    );
}

/**
 * The container-built manager with its wire props in place — the shape a mount
 * leaves behind, reached without a render.
 *
 * @param class-string<RelationManager> $class
 * @param class-string<Page>            $pageClass
 */
function compositeGuardManager(string $class, string $ownerKey, string $pageClass): RelationManager
{
    $manager = test()->getContainer()->get($class);

    expect($manager)->toBeInstanceOf($class);

    $manager->ownerRecord = $ownerKey;
    $manager->pageClass = $pageClass;

    return $manager;
}

/**
 * The records one relationship lists, read back as the one field each carries.
 *
 * @return list<string>
 */
function compositeGuardOwnedTexts(Relationship $relationship, string $getter): array
{
    $reader = test()->getContainer()->get(AssociationReader::class);

    expect($reader)->toBeInstanceOf(AssociationReader::class);

    $records = $reader->createRelationshipQuery($relationship)->getQuery()->getResult();

    expect($records)->toBeArray();

    return array_map(static fn (object $record): string => (string) $record->{$getter}(), $records);
}

function compositeGuardOwnerDql(Relationship $relationship): string
{
    $reader = test()->getContainer()->get(AssociationReader::class);

    expect($reader)->toBeInstanceOf(AssociationReader::class);

    return $reader->createRelationshipQuery($relationship)->getDQL();
}

it('lists an association of a derived-identity owner record', function (): void {
    [, $entityManager] = compositeGuardOwnerClient();
    compositeGuardOwnerRivals($entityManager);

    $relationship = compositeGuardManager(StampMarksRelationManager::class, '1~A=2FB=20C', EditStamp::class)->getRelationship();
    $rival = compositeGuardManager(StampMarksRelationManager::class, '2~A=2FB=20C', EditStamp::class)->getRelationship();

    // Both stamps carry the same code, so only the catalogue half tells the two
    // collections apart — and the owner is reached through its own collection
    // rather than through a join on the listed table.
    expect($relationship)->toBeInstanceOf(Relationship::class)
        ->and($relationship->owner)->toBeInstanceOf(Stamp::class)
        ->and(compositeGuardOwnerDql($relationship))->toBe('SELECT record FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark record WHERE EXISTS (SELECT 1 FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp sy_owner JOIN sy_owner.marks sy_related WHERE sy_owner.catalogue = :sylament_relationship_owner_0 AND sy_owner.code = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and(compositeGuardOwnedTexts($relationship, 'getLabel'))->toBe(['Mark'])
        ->and(compositeGuardOwnedTexts($rival, 'getLabel'))->toBe(['Rival mark']);
});

it('lists an association of a single-derived-identity owner record', function (): void {
    // `Colophon` is keyed by one association and nothing else, which Doctrine
    // reports as *not* composite — the arm that keeps the plain foreign-key
    // comparison and the unsuffixed parameter name.
    [, $entityManager] = compositeGuardOwnerClient();
    compositeGuardOwnerRivals($entityManager);

    $relationship = compositeGuardManager(ColophonLinesRelationManager::class, '1', EditColophon::class)->getRelationship();
    $rival = compositeGuardManager(ColophonLinesRelationManager::class, '2', EditColophon::class)->getRelationship();

    expect($entityManager->getClassMetadata(Colophon::class)->isIdentifierComposite)->toBeFalse()
        ->and($relationship->owner)->toBeInstanceOf(Colophon::class)
        ->and(compositeGuardOwnerDql($relationship))->toBe('SELECT record FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\ColophonLine record WHERE record.colophon = :sylament_relationship_owner')
        ->and(compositeGuardOwnedTexts($relationship, 'getText'))->toBe(['Line'])
        ->and(compositeGuardOwnedTexts($rival, 'getText'))->toBe(['Rival line']);
});

it('renders a mounted manager over a derived-identity owner', function (): void {
    [$client, $entityManager] = compositeGuardOwnerClient();
    compositeGuardOwnerRivals($entityManager);

    $html = (string) $this->createLiveComponent(
        StampMarksRelationManager::class,
        ['ownerRecord' => '1~A=2FB=20C', 'pageClass' => EditStamp::class],
        client: $client,
    )->render();

    expect($html)->toContain('Mark')
        ->and($html)->not->toContain('Rival mark');
});

it('lists a derived-identity owner association through the routed host of the tier', function (): void {
    [$client, $entityManager] = compositeGuardOwnerClient();
    compositeGuardOwnerRivals($entityManager);

    $crawler = $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/marks');
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(1)
        ->and($rows->text())->toContain('Mark');
});

it('characterizes: an owner Doctrine describes nothing for reaches the relation layer unchanged', function (object $owner): void {
    // Written as the escape hatch a guard needed and kept as a characterization
    // of `getRelationship()` being owner-agnostic: it passed while the guard
    // stood and passes without it, because the refusals for such an owner are
    // the relation layer's own.
    $manager = test()->getContainer()->get(UnmappedOwnerRelationManager::class);

    expect($manager)->toBeInstanceOf(UnmappedOwnerRelationManager::class);

    expect($manager->ownedBy($owner)->getRelationship()->owner)->toBe($owner);
})->with([
    'a class no driver maps' => [new stdClass()],
    'an anonymous class' => [new class {}],
]);

it('lists an association of a single-identifier owner record whose target is composite', function (): void {
    compositeGuardOwnerClient();

    $relationship = compositeGuardManager(StampsRelationManager::class, '1', EditCatalogue::class)->getRelationship();

    expect($relationship)->toBeInstanceOf(Relationship::class)
        ->and($relationship->name)->toBe('stamps')
        ->and($relationship->owner)->toBeInstanceOf(Catalogue::class);
});

it('renders the embedded manager of a single-identifier owner through its page', function (): void {
    [$client] = compositeGuardOwnerClient();

    $crawler = $client->request('GET', '/key/catalogues/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('section.sy-pa-relation-manager tbody .sy-ta-row')->count())->toBe(1);
});

it('lists the same association through the routed host of the tier', function (): void {
    // `ManageRelatedRecords` is the trait's second host, so the one seam has to
    // serve it too — and this is the page that reads a composite-target
    // association through it.
    [$client] = compositeGuardOwnerClient();

    $crawler = $client->request('GET', '/key/catalogues/1/stamps');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(1);
});
