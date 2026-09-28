<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seat;
use Sylament\Actions\Tests\Fixtures\Twig\Components\LarderCreateHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RelationshipCreateHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * A create whose modal leaves a key field out, so nothing the user can fix
 * carries the blank: the mapping lets it through, and the create refuses it by
 * name after persist() — once every prePersist listener has had its turn — and
 * before anything is flushed. Real live requests with the reboot disabled, for
 * the reasons `CreateActionTest` states; the container-wide manager both
 * requests share is also the one a case adds its listener to, and the one a
 * refused case flushes afterwards, which writes nothing only because the
 * refused record was taken back out of it.
 */

/**
 * @param class-string         $host
 * @param array<string, mixed> $props
 */
function createBlankKeyMounted(string $host, array $props, string $action): TestLiveComponent
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return test()->createLiveComponent($host, $props, client: $client)
        ->call('mountAction', ['name' => $action, 'arguments' => [], 'context' => []]);
}

function createBlankKeyRoster(): Roster
{
    $roster = new Roster();
    $roster->setTitle('Spring');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($roster);
    $entityManager->flush();

    return $roster;
}

/**
 * @return list<array<string, mixed>>
 */
function createBlankKeyRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

function createBlankKeyRefusal(string $action, string $class): LogicException
{
    return new LogicException(sprintf(
        "The [%s] action built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a field in the action's schema() — a Hidden one for a value the user should not type — or fill it in a prePersist listener.",
        $action,
        $class,
    ));
}

it('refuses by name a seat whose code the modal never maps, writing nothing', function (): void {
    $roster = createBlankKeyRoster();

    $component = createBlankKeyMounted(RelationshipCreateHost::class, ['rosterId' => $roster->getId()], 'createLabelledSeat')
        ->set('mountedActionsData.0.label', 'Nameless');

    expect(fn (): mixed => $component->call('callMountedAction'))->toThrow(createBlankKeyRefusal('createLabelledSeat', Seat::class));

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->flush();

    expect(createBlankKeyRows('SELECT roster_id, code, label FROM Seat'))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue();
});

it('refuses by name a plain create whose modal maps neither key half, writing nothing', function (): void {
    $component = createBlankKeyMounted(LarderCreateHost::class, [], 'createRedirect')
        ->set('mountedActionsData.0.target', '/new');

    expect(fn (): mixed => $component->call('callMountedAction'))->toThrow(createBlankKeyRefusal('createRedirect', Redirect::class));

    $this::getContainer()->get(EntityManagerInterface::class)->flush();

    expect(createBlankKeyRows('SELECT host, path, target FROM Redirect'))->toBe([]);
});

it('saves a seat whose code a global prePersist listener fills, since the create reads the key after persist()', function (): void {
    $roster = createBlankKeyRoster();

    $listener = new class {
        public function prePersist(PrePersistEventArgs $arguments): void
        {
            $seat = $arguments->getObject();

            if ($seat instanceof Seat && $seat->getCode() === '') {
                $seat->setCode('L1');
            }
        }
    };

    $eventManager = $this::getContainer()->get(EntityManagerInterface::class)->getEventManager();
    $eventManager->addEventListener([Events::prePersist], $listener);

    try {
        createBlankKeyMounted(RelationshipCreateHost::class, ['rosterId' => $roster->getId()], 'createLabelledSeat')
            ->set('mountedActionsData.0.label', 'Listened')
            ->call('callMountedAction');
    } finally {
        $eventManager->removeEventListener([Events::prePersist], $listener);
    }

    expect(createBlankKeyRows('SELECT roster_id, code, label FROM Seat'))->toBe([['roster_id' => 1, 'code' => 'L1', 'label' => 'Listened']]);
});

it('refuses a blank key field the modal maps as a field error on a class carrying a prePersist callback, writing nothing', function (): void {
    $component = createBlankKeyMounted(LarderCreateHost::class, [], 'createPlanter')
        ->set('mountedActionsData.0.bed', 'north')
        ->set('mountedActionsData.0.slot', '   ')
        ->set('mountedActionsData.0.crop', 'basil');

    try {
        $component->call('callMountedAction');
        test()->fail('Expected the create to be refused with a ValidationException.');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['mountedActionsData.0.slot' => ['This value should not be blank.']]);
    }

    $this::getContainer()->get(EntityManagerInterface::class)->flush();

    expect(createBlankKeyRows('SELECT bed, slot, crop, season FROM Planter'))->toBe([]);
});
