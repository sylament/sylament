<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Charm;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Satchel;
use Sylament\Actions\Tests\Fixtures\Twig\Components\SatchelHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A relation-scoped create whose record Doctrine refuses inside persist(),
 * after the attach wrote it into the owner's many-to-many. Real live requests
 * with the reboot disabled, for the reasons `CreateActionTest` states; the one
 * container-wide manager both requests share is what lets a case read the
 * owner's collection and flush after the refused request.
 */

it("gives a charm Doctrine refuses inside persist() back to the satchel's collection, rethrowing Doctrine's refusal", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $satchel = new Satchel();
    $entityManager->persist($satchel);
    $entityManager->flush();

    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent(SatchelHost::class, ['satchelId' => $satchel->getId()], client: $client)
        ->call('mountAction', ['name' => 'stowCharm', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.label', 'Keyless');

    expect(fn (): mixed => $component->call('callMountedAction'))->toThrow(EntityMissingAssignedId::forField(new Charm(), 'name'));

    $charms = $entityManager->find(Satchel::class, $satchel->getId())?->getCharms()->map(static fn (Charm $charm): string => $charm->getLabel())->toArray();

    $entityManager->flush();

    $connection = $entityManager->getConnection();

    expect($charms)->toBe([])
        ->and($connection->fetchAllAssociative('SELECT satchel_id, charm_name FROM satchel_charm'))->toBe([])
        ->and($connection->fetchAllAssociative('SELECT name, label FROM Charm'))->toBe([]);
});
