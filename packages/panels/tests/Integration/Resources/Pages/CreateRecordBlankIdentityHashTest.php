<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Locket;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateLocket;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The `locket` panel's create of a single derived identity whose key flattens
 * to '': persist() files it under an identity hash of '', which Doctrine can
 * neither detach nor remove. Kernel reboot is disabled because the fixture
 * database lives in memory on a container service.
 */

it('refuses by name a locket whose clasp key the form never maps, though Doctrine cannot detach it', function (): void {
    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $request = new Request();
    $request->attributes->set('_sylament_panel', 'locket');
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);

    $component = $this->createLiveComponent(CreateLocket::class, ['panelId' => 'locket'], client: $client);
    $component->set('data', ['engraving' => 'For ever']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.',
        CreateLocket::class,
        Locket::class,
    )));

    $connection = $this::getContainer()->get(EntityManagerInterface::class)->getConnection();

    expect($connection->fetchAllAssociative('SELECT clasp_code FROM Locket'))->toBe([])
        ->and($connection->fetchAllAssociative('SELECT code FROM Clasp'))->toBe([]);
});
