<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\RedirectHop;
use Sylament\Actions\Tests\Fixtures\Twig\Components\CompositeOwnerCreateHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * `CreateAction`'s auto-attach when the relationship's **owner** is keyed by
 * several fields. This is the one relationship verb the actions package can
 * host on its own — the others need a table to supply the relationship and a
 * selection, and this kernel registers neither the tables nor the forms bundle,
 * so they are pinned from the tables suite instead.
 *
 * The write half of the relation layer never reads the owner's identity: it
 * points the child's mapped-by field at the owner object and lets the flush
 * work out the columns. What a wider owner changes is only what lands in the
 * row, which is why the assertion is the stored foreign-key pair rather than
 * the in-memory association.
 */

function seededCompositeOwnerRedirect(string $host = 'example.com', string $path = '/old'): Redirect
{
    $redirect = new Redirect();
    $redirect->setHost($host);
    $redirect->setPath($path);
    $redirect->setId(7);
    $redirect->setTarget('/new');

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($redirect);
    $entityManager->flush();

    return $redirect;
}

/**
 * @param array<string, mixed> $props
 */
function mountedCompositeOwnerHopCreateAction(array $props = []): TestLiveComponent
{
    /** @var KernelBrowser $client */
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return test()->createLiveComponent(CompositeOwnerCreateHost::class, $props, client: $client)
        ->call('mountAction', ['name' => 'createHop', 'arguments' => [], 'context' => []]);
}

it('attaches a created record to an owner keyed by several fields', function (): void {
    seededCompositeOwnerRedirect();

    mountedCompositeOwnerHopCreateAction()
        ->set('mountedActionsData.0.id', '1')
        ->set('mountedActionsData.0.label', 'First hop')
        ->call('callMountedAction');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->clear();

    $hop = $entityManager->find(RedirectHop::class, 1);

    expect($hop)->toBeInstanceOf(RedirectHop::class)
        ->and($hop?->getLabel())->toBe('First hop')
        ->and($hop?->getRedirect()?->getHost())->toBe('example.com')
        ->and($hop?->getRedirect()?->getPath())->toBe('/old');
});

it('writes both foreign-key columns of the owner, not just the first', function (): void {
    seededCompositeOwnerRedirect();

    mountedCompositeOwnerHopCreateAction()
        ->set('mountedActionsData.0.id', '2')
        ->set('mountedActionsData.0.label', 'Second hop')
        ->call('callMountedAction');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    expect($entityManager->getConnection()->fetchAssociative('SELECT redirect_host, redirect_path FROM RedirectHop WHERE id = ?', [2]))
        ->toBe(['redirect_host' => 'example.com', 'redirect_path' => '/old']);
});

it('leaves the foreign key null when the host names no owner', function (): void {
    seededCompositeOwnerRedirect();

    mountedCompositeOwnerHopCreateAction(['redirectPath' => '/missing'])
        ->set('mountedActionsData.0.id', '3')
        ->set('mountedActionsData.0.label', 'Orphan hop')
        ->call('callMountedAction');

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    expect($entityManager->getConnection()->fetchAssociative('SELECT redirect_host, redirect_path FROM RedirectHop WHERE id = ?', [3]))
        ->toBe(['redirect_host' => null, 'redirect_path' => null]);
});
