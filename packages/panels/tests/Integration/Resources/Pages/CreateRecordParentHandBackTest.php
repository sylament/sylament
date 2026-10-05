<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Relic;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Reliquary;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateNichelessRelic;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateRelic;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateVaultlessRelic;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A nested create under a many-to-many parent joins the parent's collection
 * before the mapping and again before persist(). A create refused at either
 * step hands the record back, so the parent's collection holds no unsaved
 * record for the rest of the request and a later flush in it succeeds.
 *
 * Reliquary `1` holds the relic `v1`/`n1`. Kernel reboot is disabled because the
 * fixture database lives in memory on a container service, which is also what
 * keeps the parent the request loaded in the identity map afterwards.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function relicHandBackClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $relic = new Relic()->setVault('v1')->setNiche('n1')->setLabel('Stored');

    EntityManagerFactory::seed($entityManager, $relic, new Reliquary()->setName('First')->addRelic($relic));

    $request = new Request();
    $request->attributes->set('_sylament_panel', 'reliquary');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    return [$client, $entityManager];
}

/**
 * The labels the parent the request loaded holds in memory.
 *
 * @return list<string>
 */
function relicHandBackLabels(EntityManagerInterface $entityManager): array
{
    $reliquary = $entityManager->find(Reliquary::class, 1) ?? throw new LogicException('The reliquary vanished.');

    return array_values(array_map(static fn (Relic $relic): string => $relic->getLabel(), $reliquary->getRelics()->toArray()));
}

/**
 * @return list<array<string, mixed>>
 */
function relicHandBackJoinRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT reliquary_id, relic_vault, relic_niche FROM reliquary_relic ORDER BY relic_vault');
}

it('hands the record back when the mapping refuses a taken key', function (): void {
    [$client, $entityManager] = relicHandBackClient();

    $component = $this->createLiveComponent(CreateRelic::class, ['parentRecords' => ['reliquary' => '1'], 'panelId' => 'reliquary'], client: $client);
    $component->set('data', ['vault' => 'v1', 'niche' => 'n1', 'label' => 'Duplicate']);

    $client->catchExceptions(true);
    $component->call('create');

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(relicHandBackLabels($entityManager))->toBe(['Stored']);

    $entityManager->flush();

    expect(relicHandBackJoinRows($entityManager))->toBe([['reliquary_id' => 1, 'relic_vault' => 'v1', 'relic_niche' => 'n1']]);
});

it('hands the record back when the blank-key read refuses it after persist()', function (): void {
    [$client, $entityManager] = relicHandBackClient();

    $component = $this->createLiveComponent(CreateNichelessRelic::class, ['parentRecords' => ['reliquary' => '1'], 'panelId' => 'reliquary'], client: $client);
    $component->set('data', ['vault' => 'v2', 'label' => 'Nicheless']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.',
        CreateNichelessRelic::class,
        Relic::class,
    )));

    expect(relicHandBackLabels($entityManager))->toBe(['Stored']);

    $entityManager->flush();

    expect(relicHandBackJoinRows($entityManager))->toBe([['reliquary_id' => 1, 'relic_vault' => 'v1', 'relic_niche' => 'n1']])
        ->and($entityManager->getRepository(Relic::class)->count([]))->toBe(1);
});

it("hands the record back when persist() refuses a null key half, rethrowing Doctrine's refusal unchanged", function (): void {
    [$client, $entityManager] = relicHandBackClient();

    $component = $this->createLiveComponent(CreateVaultlessRelic::class, ['parentRecords' => ['reliquary' => '1'], 'panelId' => 'reliquary'], client: $client);
    $component->set('data', ['niche' => 'n2', 'label' => 'Vaultless']);

    expect(fn (): mixed => $component->call('create'))->toThrow(EntityMissingAssignedId::forField(new Relic(), 'vault'));

    expect(relicHandBackLabels($entityManager))->toBe(['Stored']);

    $entityManager->flush();

    expect(relicHandBackJoinRows($entityManager))->toBe([['reliquary_id' => 1, 'relic_vault' => 'v1', 'relic_niche' => 'n1']])
        ->and($entityManager->getRepository(Relic::class)->count([]))->toBe(1);
});

it('keeps a saved record in the parent it was created under', function (): void {
    [$client, $entityManager] = relicHandBackClient();

    $component = $this->createLiveComponent(CreateRelic::class, ['parentRecords' => ['reliquary' => '1'], 'panelId' => 'reliquary'], client: $client);
    $component->set('data', ['vault' => 'v2', 'niche' => 'n2', 'label' => 'Created']);
    $component->call('create');

    expect($component->response()->isRedirection())->toBeTrue()
        ->and(relicHandBackLabels($entityManager))->toBe(['Stored', 'Created'])
        ->and(relicHandBackJoinRows($entityManager))->toBe([
            ['reliquary_id' => 1, 'relic_vault' => 'v1', 'relic_niche' => 'n1'],
            ['reliquary_id' => 1, 'relic_vault' => 'v2', 'relic_niche' => 'n2'],
        ]);
});
