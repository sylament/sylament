<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\ConstructorKeyedSeat;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seat;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ConstructorKeyedCreateHost;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RelationshipCreateHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * Real live requests, and a reboot disabled, for the reasons `CreateActionTest`
 * states: the success toast is a session flash, and the fixture database lives
 * in memory on a container service.
 */

function seededRoster(string $title = 'Spring'): Roster
{
    $roster = new Roster();
    $roster->setTitle($title);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($roster);
    $entityManager->flush();

    return $roster;
}

function mountedSeatCreateAction(Roster $roster): TestLiveComponent
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return test()->createLiveComponent(RelationshipCreateHost::class, ['rosterId' => $roster->getId()], client: $client)
        ->call('mountAction', ['name' => 'createSeat', 'arguments' => [], 'context' => []]);
}

function createdSeat(string $code): ?Seat
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getRepository(Seat::class)->findOneBy(['code' => $code]);
}

it('joins a derived-identity record to its owner before persisting it', function (): void {
    $roster = seededRoster();

    mountedSeatCreateAction($roster)
        ->set('mountedActionsData.0.code', 'A1')
        ->set('mountedActionsData.0.label', 'Front row')
        ->call('callMountedAction');

    $seat = createdSeat('A1');

    expect($seat)->toBeInstanceOf(Seat::class)
        ->and($seat?->getLabel())->toBe('Front row')
        ->and($seat?->getRoster()?->getId())->toBe($roster->getId());
});

it('writes a row the composite identifier the relationship supplied reads back', function (): void {
    $roster = seededRoster('Autumn');

    mountedSeatCreateAction($roster)
        ->set('mountedActionsData.0.code', 'B2')
        ->set('mountedActionsData.0.label', 'Back row')
        ->call('callMountedAction');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->clear();

    expect($entityManager->find(Seat::class, ['roster' => $roster->getId(), 'code' => 'B2']))->toBeInstanceOf(Seat::class);
});

it('refuses by name a relationship record whose constructor requires arguments', function (): void {
    $roster = seededRoster('Winter');

    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $component = test()->createLiveComponent(ConstructorKeyedCreateHost::class, ['rosterId' => $roster->getId()], client: $client)
        ->call('mountAction', ['name' => 'createSeat', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.code', 'C3')
        ->set('mountedActionsData.0.label', 'Side row');

    expect(fn () => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'Cannot map a new record for the [createSeat] action: ' . ConstructorKeyedSeat::class . ' requires constructor arguments');
});
