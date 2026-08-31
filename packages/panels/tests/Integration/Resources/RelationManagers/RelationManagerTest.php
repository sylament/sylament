<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PaginatedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled throughout: the fixture database is an in-memory
 * SQLite built by a container service, so a reboot between two requests would
 * hand the second one a freshly-migrated, empty database.
 */

/**
 * Two projects, three tasks on the first and one on the second — so a scoping
 * miss shows up as a fourth row, and three rows over two per page paginate.
 *
 * @return array{KernelBrowser, EntityManagerInterface, string}
 */
function seededProjectClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $project = new Project()->setName('Apollo');
    $other = new Project()->setName('Gemini');

    EntityManagerFactory::seed(
        $entityManager,
        $project,
        $other,
        new Task()->setTitle('Alpha')->setPosition(1)->setProject($project),
        new Task()->setTitle('Beta')->setPosition(2)->setProject($project),
        new Task()->setTitle('Gamma')->setPosition(3)->setProject($project),
        new Task()->setTitle('Delta')->setPosition(1)->setProject($other),
    );

    $key = $project->getId() ?? throw new LogicException('The seeded project carries no identifier.');

    return [$client, $entityManager, (string) $key];
}

function taskKey(EntityManagerInterface $entityManager, string $title): string
{
    $task = $entityManager->getRepository(Task::class)->findOneBy(['title' => $title]);

    expect($task)->toBeInstanceOf(Task::class);

    return (string) $task?->getId();
}

it('renders its heading and the owner records the association holds', function (): void {
    [$client, , $key] = seededProjectClient();

    $crawler = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->render()
        ->crawler();

    $rows = $crawler->filter('tbody .sy-ta-row');
    $rowText = implode(' ', $rows->each(fn (Crawler $row): string => $row->text()));

    // The heading is the relationship-name fallback, seeded onto the table by
    // makeTable() and rendered inside the table's own header block — Filament's
    // shape, where the manager owns no chrome of its own. The rows are the
    // owner's tasks alone — the second project's `Delta` is the scoping miss.
    expect($crawler->filter('section.sy-pa-relation-manager .sy-ta-header > div > h2.sy-ta-header-heading')->text())->toBe('Tasks')
        ->and($crawler->filter('section.sy-pa-relation-manager > .sy-ta')->count())->toBe(1)
        ->and($rows->count())->toBe(3)
        ->and($rowText)->toContain('Alpha')
        ->and($rowText)->not->toContain('Delta');
});

it('renders as a live root of its own', function (): void {
    [$client, , $key] = seededProjectClient();

    $crawler = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->render()
        ->crawler();

    expect($crawler->filter('section.sy-pa-relation-manager[data-controller~="live"]')->count())->toBe(1)
        ->and($crawler->filter('section.sy-pa-relation-manager[data-live-name-value]')->count())->toBe(1);
});

it('opens a mounted action inside its own modals outlet', function (): void {
    [$client, , $key] = seededProjectClient();

    $component = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]]);

    // Without the partial's own `Modals`, a mounted action renders nothing at
    // all and the failure is silent — so the outlet is pinned inside the root.
    expect($component->render()->crawler()->filter('section.sy-pa-relation-manager .sy-modal')->count())->toBe(1);
});

it('paginates the association and renders the extreme page links', function (): void {
    [$client, , $key] = seededProjectClient();

    $component = $this->createLiveComponent(PaginatedTasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client);

    $first = $component->render()->crawler();
    $second = $component->call('gotoTablePage', ['page' => 2])->render()->crawler();

    expect($first->filter('tbody .sy-ta-row')->count())->toBe(2)
        // Page one has somewhere to go last and nowhere to go first.
        ->and($first->filter('.sy-pagination-item[rel="last"] svg')->count())->toBe(1)
        ->and($first->filter('.sy-pagination-item[rel="first"]')->count())->toBe(0)
        ->and($second->filter('tbody .sy-ta-row')->count())->toBe(1)
        ->and($second->filter('tbody .sy-ta-row')->text())->toContain('Gamma')
        ->and($second->filter('.sy-pagination-item[rel="first"] svg')->count())->toBe(1);
});

it('creates a record attached to the owner and announces it', function (): void {
    [$client, $entityManager, $key] = seededProjectClient();

    $component = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.title', 'Epsilon')
        ->call('callMountedAction');

    $project = $entityManager->find(Project::class, (int) $key) ?? throw new LogicException('The seeded project went missing.');
    $entityManager->refresh($project);

    $created = $entityManager->getRepository(Task::class)->findOneBy(['title' => 'Epsilon']);

    expect($created)->toBeInstanceOf(Task::class)
        ->and($created?->getProject()?->getId())->toBe((int) $key)
        ->and($project->getTasks())->toHaveCount(4)
        ->and($component->component()->mountedActions)->toBe([])
        ->and((string) $component->render())->toContain('sylament:notification');
});

it('sends a delete on a relation manager nowhere', function (): void {
    [$client, $entityManager, $key] = seededProjectClient();

    $taskKey = taskKey($entityManager, 'Beta');

    $response = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $taskKey]])
        ->call('callMountedAction')
        ->response();

    $entityManager->clear();

    // The manager does not extend the resource-page base, so there is no
    // delete-to-index redirect for it to inherit.
    expect($response->isRedirection())->toBeFalse()
        ->and($entityManager->find(Task::class, (int) $taskKey))->toBeNull();
});

it('refuses an owner key the resource does not answer for', function (): void {
    [$client] = seededProjectClient();

    // The owner resolves during dehydration, so an unanswerable key never even
    // reaches a render.
    expect(fn (): object => $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => '99999', 'pageClass' => EditProject::class], client: $client)->component())
        ->toThrow(NotFoundHttpException::class);
});
