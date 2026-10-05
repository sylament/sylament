<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Locket;
use Sylament\Actions\Tests\Fixtures\Twig\Components\LocketCreateHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A single derived identity whose key flattens to '': persist() files it under
 * an identity hash of '', which Doctrine can neither detach nor remove. A real
 * live request with the reboot disabled, for the reasons `CreateActionTest`
 * states.
 */

it('refuses by name a locket whose clasp key the modal never maps, though Doctrine cannot detach it', function (): void {
    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent(LocketCreateHost::class, client: $client)
        ->call('mountAction', ['name' => 'createLocket', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.engraving', 'For ever');

    expect(fn (): mixed => $component->call('callMountedAction'))->toThrow(new LogicException(sprintf(
        "The [createLocket] action built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a field in the action's schema() — a Hidden one for a value the user should not type — or fill it in a prePersist listener.",
        Locket::class,
    )));

    $connection = $this::getContainer()->get(EntityManagerInterface::class)->getConnection();

    expect($connection->fetchAllAssociative('SELECT clasp_code FROM Locket'))->toBe([])
        ->and($connection->fetchAllAssociative('SELECT code FROM Clasp'))->toBe([]);
});
