<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditFilteredRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\OwnerNamedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PaginatedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TaggedTasksRelationManager;
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

it('renders a tab strip that switches the manager but binds nothing', function (): void {
    [$client, , $key] = seededProjectClient();

    $component = $this->createLiveComponent(
        TaggedTasksRelationManager::class,
        ['ownerRecord' => $key, 'pageClass' => EditFilteredRelationProject::class],
        client: $client,
    );

    $crawler = $component->render()->crawler();
    $strip = $crawler->filter('section.sy-pa-relation-manager .sy-sc-tabs');

    // The strip is the schemas `Tabs` in server-driven mode, so switching is a
    // write to the manager's own property — and the manager maps no state onto
    // the URL, which is the page's.
    expect($strip->count())->toBe(1)
        ->and($strip->filter('[data-model="activeTab"]')->count())->toBe(2)
        ->and($crawler->filter('section.sy-pa-relation-manager[data-live-query-mapping-value]')->count())->toBe(0)
        ->and($component->component()->activeTab)->toBe('all')
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(3);

    $narrowed = $component->set('activeTab', 'first')->render()->crawler();

    expect($narrowed->filter('tbody .sy-ta-row')->count())->toBe(1);
});

it('builds its tabs against the owner it is mounted with', function (): void {
    [$client, , $key] = seededProjectClient();

    $crawler = $this->createLiveComponent(
        OwnerNamedTasksRelationManager::class,
        ['ownerRecord' => $key, 'pageClass' => EditProject::class],
        client: $client,
    )->render()->crawler();

    $labels = $crawler->filter('section.sy-pa-relation-manager .sy-tabs-item')
        ->each(fn (Crawler $item): string => mb_trim($item->text()));

    // The owner is read while the tabs are built, and the tab set is memoised,
    // so the build has to come after the mount data lands.
    expect($labels)->toBe(['Apollo', 'First']);
});

it('builds its tabs against a registration property assigned after the mount', function (): void {
    [$client, , $key] = seededProjectClient();

    // The page embeds the manager, so this is the manager's own mount render —
    // the one every page render produces.
    $crawler = $this->createLiveComponent(EditFilteredRelationProject::class, ['record' => $key], client: $client)
        ->render()
        ->crawler();

    $labels = $crawler->filter('section.sy-pa-relation-manager .sy-tabs-item')
        ->each(fn (Crawler $item): string => mb_trim($item->text()));

    // The registration property is assigned by the loop that follows the mount,
    // so tabs memoised inside it would carry a null one's generated label.
    expect($labels)->toBe(['default', 'First']);
});

it('seeds a row action on a manager whose actions carry no url', function (): void {
    [$client, , $key] = seededProjectClient();

    $crawler = $this->createLiveComponent(TasksRelationManager::class, ['ownerRecord' => $key, 'pageClass' => EditProject::class], client: $client)
        ->render()
        ->crawler();

    $cell = $crawler->filter('tbody .sy-ta-row')->first()->filter('button.sy-ta-col')->first();

    // The manager speaks for no resource, so its `edit` action has no url and
    // becomes the row's click target rather than its link.
    expect($crawler->filter('tbody .sy-ta-row a.sy-ta-col')->count())->toBe(0)
        ->and($cell->attr('data-live-action-param'))->toBe('mountAction')
        ->and($cell->attr('data-live-name-param'))->toBe('edit');
});
