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
 * own — the three owner flavours the guard sorts, in one database.
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
 * The container-built manager with its wire props in place — the shape a mount
 * leaves behind, reached without a render, since a manager whose owner the
 * guard refuses cannot be rendered at all.
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

function compositeGuardOwnerRootMessage(Throwable $throwable): string
{
    while (($previous = $throwable->getPrevious()) instanceof Throwable) {
        $throwable = $previous;
    }

    return $throwable->getMessage();
}

it('refuses to list an association of a derived-identity owner record', function (): void {
    compositeGuardOwnerClient();

    $manager = compositeGuardManager(StampMarksRelationManager::class, '1~A=2FB=20C', EditStamp::class);

    expect(fn (): mixed => $manager->getRelationship())
        ->toThrow(
            LogicException::class,
            'The relationship table [Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampMarksRelationManager] lists an association of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp], whose records are not keyed by a single scalar identifier; scoping a relationship table to such an owner record is not built yet.',
        );
});

it('refuses to list an association of a single-derived-identity owner record', function (): void {
    // `Colophon` is keyed by one association and nothing else, which Doctrine
    // reports as *not* composite — the arm a guard written on
    // `isIdentifierComposite` alone lets through to the relation layer.
    [, $entityManager] = compositeGuardOwnerClient();

    $manager = compositeGuardManager(ColophonLinesRelationManager::class, '1', EditColophon::class);

    expect($entityManager->getClassMetadata(Colophon::class)->isIdentifierComposite)->toBeFalse();

    expect(fn (): mixed => $manager->getRelationship())
        ->toThrow(
            LogicException::class,
            'The relationship table [Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ColophonLinesRelationManager] lists an association of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon], whose records are not keyed by a single scalar identifier; scoping a relationship table to such an owner record is not built yet.',
        );
});

it('carries the refusal out of a mounted manager rather than past it', function (): void {
    [$client] = compositeGuardOwnerClient();

    try {
        $this->createLiveComponent(
            StampMarksRelationManager::class,
            ['ownerRecord' => '1~A=2FB=20C', 'pageClass' => EditStamp::class],
            client: $client,
        )->render();

        $message = null;
    } catch (Throwable $throwable) {
        $message = compositeGuardOwnerRootMessage($throwable);
    }

    expect($message)->toBe('The relationship table [Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampMarksRelationManager] lists an association of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp], whose records are not keyed by a single scalar identifier; scoping a relationship table to such an owner record is not built yet.');
});

it('carries the refusal out of the routed host of the tier as well', function (): void {
    [$client] = compositeGuardOwnerClient();

    $client->catchExceptions(true);
    $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/marks');

    expect($client->getResponse()->getStatusCode())->toBe(500);

    $client->catchExceptions(false);

    try {
        $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/marks');

        $message = null;
    } catch (Throwable $throwable) {
        $message = compositeGuardOwnerRootMessage($throwable);
    }

    expect($message)->toBe('The relationship table [Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageStampMarks] lists an association of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp], whose records are not keyed by a single scalar identifier; scoping a relationship table to such an owner record is not built yet.');
});

it('leaves an owner Doctrine describes nothing for to the relation layer', function (object $owner): void {
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
    // `ManageRelatedRecords` is the trait's second host, so the one guard has to
    // leave it alone too — and this is the page that reads a composite-target
    // association through it.
    [$client] = compositeGuardOwnerClient();

    $crawler = $client->request('GET', '/key/catalogues/1/stamps');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(1);
});
