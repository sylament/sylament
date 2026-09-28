<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Kettle;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Pantry;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seat;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Spice;
use Sylament\Actions\Tests\Fixtures\Twig\Components\PantryShelfHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RelationshipCreateHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * A relation-scoped create, where the relationship is resolved and attached
 * before the form is mapped and again after it. Real live requests with the
 * reboot disabled, for the reasons `CreateActionTest` states; the one
 * container-wide manager both requests share is also what lets a case read the
 * owner's in-memory collection after the request that refused the create. The
 * one in-process case reads what the schema was bound to, which only the host
 * object that ran the create still holds.
 */

function createRelationshipKeyPantry(string $title): Pantry
{
    $pantry = new Pantry();
    $pantry->setTitle($title);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($pantry);
    $entityManager->flush();

    return $pantry;
}

function createRelationshipKeyKettle(Pantry $pantry, string $code): Kettle
{
    $kettle = new Kettle();
    $kettle->setPantry($pantry);
    $kettle->setCode($code);
    $kettle->setLabel('Stored');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($kettle);
    $entityManager->flush();

    return $kettle;
}

function createRelationshipKeyRoster(): Roster
{
    $roster = new Roster();
    $roster->setTitle('Spring');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($roster);
    $entityManager->flush();

    return $roster;
}

function createRelationshipKeySeat(Roster $roster, string $code): Seat
{
    $seat = new Seat();
    $seat->setRoster($roster);
    $seat->setCode($code);
    $seat->setLabel('Stored');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($seat);
    $entityManager->flush();

    return $seat;
}

function createRelationshipKeyClient(): KernelBrowser
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return $client;
}

function createRelationshipKeyMounted(Pantry $pantry, string $action, ?KernelBrowser $client = null): TestLiveComponent
{
    return test()->createLiveComponent(PantryShelfHost::class, ['pantryId' => $pantry->getId()], client: $client ?? createRelationshipKeyClient())
        ->call('mountAction', ['name' => $action, 'arguments' => [], 'context' => []]);
}

function createRelationshipKeyMountedSeat(Roster $roster): TestLiveComponent
{
    return test()->createLiveComponent(RelationshipCreateHost::class, ['rosterId' => $roster->getId()], client: createRelationshipKeyClient())
        ->call('mountAction', ['name' => 'createSeat', 'arguments' => [], 'context' => []]);
}

function createRelationshipKeyRecorder(): RecordingMiddleware
{
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    $recorder->reset();

    return $recorder;
}

/**
 * @return array<string, list<string>>
 */
function createRelationshipKeyRefusal(TestLiveComponent $component): array
{
    try {
        $component->call('callMountedAction');
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the create to be refused with a ValidationException.');
}

/**
 * @return list<array<string, mixed>>
 */
function createRelationshipKeyRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

it('joins the kettle to its pantry before the record check, so the application constraint over that half passes', function (): void {
    $pantry = createRelationshipKeyPantry('Main');

    $component = createRelationshipKeyMounted($pantry, 'shelveKettle')
        ->set('mountedActionsData.0.code', 'K1')
        ->set('mountedActionsData.0.label', 'Copper');

    $recorder = createRelationshipKeyRecorder();

    $component->call('callMountedAction');

    expect($recorder->statements())->toBe([
        'SELECT t0.code AS code_1, t0.label AS label_2, t0.pantry_id AS pantry_id_3 FROM Kettle t0 WHERE t0.pantry_id = ? AND t0.code = ?',
        'BEGIN',
        'INSERT INTO Kettle (code, label, pantry_id) VALUES (?, ?, ?)',
        'COMMIT',
    ])
        ->and(createRelationshipKeyRows('SELECT pantry_id, code, label FROM Kettle'))->toBe([['pantry_id' => 1, 'code' => 'K1', 'label' => 'Copper']]);
});

it('refuses a key a stored seat holds under the code field after one lookup', function (): void {
    $roster = createRelationshipKeyRoster();
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->detach(createRelationshipKeySeat($roster, 'A1'));

    $component = createRelationshipKeyMountedSeat($roster)
        ->set('mountedActionsData.0.code', 'A1')
        ->set('mountedActionsData.0.label', 'Duplicate');

    $recorder = createRelationshipKeyRecorder();

    expect(createRelationshipKeyRefusal($component))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.label AS label_2, t0.roster_id AS roster_id_3 FROM Seat t0 WHERE t0.roster_id = ? AND t0.code = ?'])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(createRelationshipKeyRows('SELECT roster_id, code, label FROM Seat'))->toBe([['roster_id' => 1, 'code' => 'A1', 'label' => 'Stored']]);
});

it('refuses a key a managed seat holds without running a statement', function (): void {
    $roster = createRelationshipKeyRoster();
    createRelationshipKeySeat($roster, 'A1');

    $component = createRelationshipKeyMountedSeat($roster)
        ->set('mountedActionsData.0.code', 'A1')
        ->set('mountedActionsData.0.label', 'Duplicate');

    $recorder = createRelationshipKeyRecorder();

    expect(createRelationshipKeyRefusal($component))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([])
        ->and($this::getContainer()->get(EntityManagerInterface::class)->isOpen())->toBeTrue()
        ->and(createRelationshipKeyRows('SELECT roster_id, code, label FROM Seat'))->toBe([['roster_id' => 1, 'code' => 'A1', 'label' => 'Stored']]);
});

it('binds the create schema to the new record while it maps, and back to its class once the create is refused', function (): void {
    $pantry = createRelationshipKeyPantry('Main');
    createRelationshipKeyKettle($pantry, 'K1');

    $host = $this->createLiveComponent(PantryShelfHost::class, ['pantryId' => $pantry->getId()])->component();
    assert($host instanceof PantryShelfHost);

    $host->mountAction('shelveKettle');
    $host->mountedActionsData[0] = ['code' => 'K1', 'label' => 'Duplicate'];

    expect(fn (): mixed => $host->callMountedAction())->toThrow(ValidationException::class)
        ->and($host->bindingsWhileMapping)->toBe([Kettle::class])
        ->and($host->getMountedActionSchema()?->getRecord())->toBeNull()
        ->and($host->getMountedActionSchema()?->getRecordClass())->toBe(Kettle::class);
});

it('re-renders a refused create with the fields a create shows', function (): void {
    $pantry = createRelationshipKeyPantry('Main');
    createRelationshipKeyKettle($pantry, 'K1');

    $client = createRelationshipKeyClient();

    $component = createRelationshipKeyMounted($pantry, 'shelveKettle', $client)
        ->set('mountedActionsData.0.code', 'K1')
        ->set('mountedActionsData.0.label', 'Duplicate');

    expect(new Crawler($component->render()->toString())->filter('.sy-modal input[name="hint"]')->count())->toBe(1);

    $client->catchExceptions(true);
    $component->call('callMountedAction');

    $crawler = new Crawler((string) $component->response()->getContent());

    expect($component->response()->getStatusCode())->toBe(422)
        ->and($crawler->filter('.sy-modal input[name="hint"]')->count())->toBe(1);
});

it('takes a refused spice back out of the collection the pantry owns', function (): void {
    $pantry = createRelationshipKeyPantry('Main');

    $salt = new Spice();
    $salt->setName('salt');
    $salt->setLabel('Flaked');

    $pantry->addSpice($salt);

    $cumin = new Spice();
    $cumin->setName('cumin');
    $cumin->setLabel('Whole');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($salt);
    $entityManager->persist($cumin);
    $entityManager->flush();
    $entityManager->clear();

    $component = createRelationshipKeyMounted($pantry, 'stockSpice')
        ->set('mountedActionsData.0.name', 'cumin')
        ->set('mountedActionsData.0.label', 'Ground');

    expect(createRelationshipKeyRefusal($component))->toBe(['mountedActionsData.0.name' => ['This value is already used.']]);

    $spices = $entityManager->find(Pantry::class, 1)?->getSpices()->map(static fn (Spice $spice): string => $spice->getName() . ':' . $spice->getLabel())->toArray();

    $entityManager->flush();

    expect($spices)->toBe(['salt:Flaked'])
        ->and(createRelationshipKeyRows('SELECT pantry_id, spice_name FROM pantry_spice'))->toBe([['pantry_id' => 1, 'spice_name' => 'salt']])
        ->and(createRelationshipKeyRows('SELECT name, label FROM Spice ORDER BY name'))->toBe([
            ['name' => 'cumin', 'label' => 'Whole'],
            ['name' => 'salt', 'label' => 'Flaked'],
        ]);
});

it('joins a created spice once, though the relationship attaches it twice', function (): void {
    $pantry = createRelationshipKeyPantry('Main');

    createRelationshipKeyMounted($pantry, 'stockSpice')
        ->set('mountedActionsData.0.name', 'pepper')
        ->set('mountedActionsData.0.label', 'Black')
        ->call('callMountedAction');

    expect(createRelationshipKeyRows('SELECT pantry_id, spice_name FROM pantry_spice'))->toBe([['pantry_id' => 1, 'spice_name' => 'pepper']]);
});

it('keeps the relationship winning over a form that maps the same association', function (): void {
    $main = createRelationshipKeyPantry('Main');
    createRelationshipKeyPantry('Spare');

    createRelationshipKeyMounted($main, 'shelveKettleOnPick')
        ->set('mountedActionsData.0.pantry', '2')
        ->set('mountedActionsData.0.code', 'K2')
        ->set('mountedActionsData.0.label', 'Picked')
        ->call('callMountedAction');

    expect(createRelationshipKeyRows('SELECT pantry_id, code, label FROM Kettle'))->toBe([['pantry_id' => 1, 'code' => 'K2', 'label' => 'Picked']]);
});

it('checks the key against the pantry the form picked, not the one the relationship attaches', function (): void {
    $main = createRelationshipKeyPantry('Main');
    createRelationshipKeyKettle(createRelationshipKeyPantry('Spare'), 'K3');

    $component = createRelationshipKeyMounted($main, 'shelveKettleOnPick')
        ->set('mountedActionsData.0.pantry', '2')
        ->set('mountedActionsData.0.code', 'K3')
        ->set('mountedActionsData.0.label', 'Picked');

    expect(createRelationshipKeyRefusal($component))->toBe(['mountedActionsData.0.code' => ['This value is already used.']])
        ->and(createRelationshipKeyRows('SELECT pantry_id, code, label FROM Kettle'))->toBe([['pantry_id' => 2, 'code' => 'K3', 'label' => 'Stored']]);
});

it('refuses a seat whose code the create left blank under the code field, before any lookup or write', function (): void {
    $roster = createRelationshipKeyRoster();

    $component = createRelationshipKeyMountedSeat($roster)
        ->set('mountedActionsData.0.code', '   ')
        ->set('mountedActionsData.0.label', 'Nameless');

    $recorder = createRelationshipKeyRecorder();

    expect(createRelationshipKeyRefusal($component))->toBe(['mountedActionsData.0.code' => ['This value should not be blank.']])
        ->and($recorder->statements())->toBe([])
        ->and(createRelationshipKeyRows('SELECT roster_id, code, label FROM Seat'))->toBe([]);
});
