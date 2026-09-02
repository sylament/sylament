<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Task;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditConfiguredRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditFilteredRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditGroupedRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditSingleRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditTwoRelationProject;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PaginatedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TaggedTasksRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled throughout for the reason `RelationManagerTest`
 * disables it: the fixture database is an in-memory SQLite on a container
 * service, so a reboot between two requests hands the second an empty one.
 */

/**
 * One project with three tasks — enough rows for the paginating manager to need
 * a second page, so the two managers behind the tab bar are told apart by what
 * they render.
 *
 * @return array{KernelBrowser, string}
 */
function relationManagersPageClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $project = new Project()->setName('Apollo');

    EntityManagerFactory::seed(
        $entityManager,
        $project,
        new Task()->setTitle('Alpha')->setPosition(1)->setProject($project),
        new Task()->setTitle('Beta')->setPosition(2)->setProject($project),
        new Task()->setTitle('Gamma')->setPosition(3)->setProject($project),
    );

    $key = $project->getId() ?? throw new LogicException('The seeded project carries no identifier.');

    return [$client, (string) $key];
}

/**
 * The components the page's content schema holds, in registration order.
 *
 * @return list<object>
 */
function relationManagersContent(EditRecord $page): array
{
    return array_values($page->getSchema('content')->getComponents());
}

/**
 * The embeds one tab or group holds, in registration order.
 *
 * @return list<object>
 */
function relationManagersEmbeds(Component $component): array
{
    return array_values($component->getChildSchema()?->getComponents() ?? []);
}

it('names the content schema beside the form and holds no managers before a record is bound', function (): void {
    $page = $this::getContainer()->get(EditTwoRelationProject::class);

    // The page controller reads an un-mounted page, so every record probe the
    // trait makes has to answer there.
    expect($page->schemas())->toBe(['form', 'content'])
        ->and($page->hasRecord())->toBeFalse()
        ->and($page->getRecordKey())->toBeNull()
        ->and($page->getCachedRelationManagers())->toBe([])
        ->and($page->activeRelationManager)->toBeNull();
});

it('answers the record probes for a bound page', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditSingleRelationProject::class, ['record' => $key], client: $client)->component();

    expect($page->hasRecord())->toBeTrue()
        ->and($page->getRecordKey())->toBe($key);
});

it('hides the content of a resource that registers no managers', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditProject::class, ['record' => $key], client: $client)->component();

    $components = relationManagersContent($page);

    // A hidden group rather than nothing at all: the content schema always holds
    // exactly one component, so the page template needs no emptiness branch.
    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Group::class)
        ->and($components[0]->isHidden())->toBeTrue()
        ->and($page->activeRelationManager)->toBeNull();
});

it('mounts a single manager as a bare keyed embed with no tab bar', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditSingleRelationProject::class, ['record' => $key], client: $client)->component();

    $components = relationManagersContent($page);
    $embed = $components[0];

    expect($components)->toHaveCount(1)
        ->and($embed)->toBeInstanceOf(LiveComponent::class)
        ->and($embed->getComponent())->toBe(TasksRelationManager::class)
        ->and($embed->getKey(isAbsolute: false))->toBe(TasksRelationManager::class)
        ->and($embed->getData())->toBe([
            'ownerRecord' => $key,
            'pageClass' => EditSingleRelationProject::class,
        ]);
});

it('mounts two managers behind a live-driven tab bar keyed by the registration keys', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditTwoRelationProject::class, ['record' => $key], client: $client)->component();

    $tabs = relationManagersContent($page)[0];

    expect($tabs)->toBeInstanceOf(Tabs::class)
        ->and($tabs->getLiveComponentProperty())->toBe('activeRelationManager')
        ->and($tabs->isContained())->toBeFalse()
        ->and($tabs->getKey(isAbsolute: false))->toBe('relationManagerTabs');

    $tabComponents = $tabs->getTabs();

    // The registration keys reach the tab bar unchanged: `Tabs` re-keys its tabs
    // from them and the active manager is compared against them, so re-indexing
    // the array would break switching with no error anywhere.
    expect(array_map(static fn (Tab $tab): ?string => $tab->getKey(isAbsolute: false), $tabComponents))->toBe(['tasks', 'paged'])
        ->and($tabComponents[0]->getLabel())->toBe('Tasks')
        ->and($tabComponents[0]->getBadge())->toBeNull()
        ->and($tabComponents[1]->getBadge())->toBe('3')
        ->and($tabComponents[1]->getBadgeColor('3'))->toBe('danger')
        ->and($page->activeRelationManager)->toBe('tasks');

    $embeds = relationManagersEmbeds($tabComponents[1]);

    expect($embeds)->toHaveCount(1)
        ->and($embeds[0])->toBeInstanceOf(LiveComponent::class)
        ->and($embeds[0]->getComponent())->toBe(PaginatedTasksRelationManager::class)
        ->and($embeds[0]->getKey(isAbsolute: false))->toBe(PaginatedTasksRelationManager::class)
        ->and($embeds[0]->getData())->toBe([
            'ownerRecord' => $key,
            'pageClass' => EditTwoRelationProject::class,
        ]);
});

it('heals an active manager the registration does not name', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditTwoRelationProject::class, ['record' => $key], client: $client)->component();

    $page->activeRelationManager = 'retired';

    // The defaulting sits in the builder rather than a render hook, so it runs
    // again on every request that rebuilds the content schema.
    $page->getRelationManagersContentComponent();

    expect($page->activeRelationManager)->toBe('tasks');
});

it('keys the active manager as a string even where the registration keys are integers', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditConfiguredRelationProject::class, ['record' => $key], client: $client)->component();

    $tabs = relationManagersContent($page)[0];

    expect($tabs)->toBeInstanceOf(Tabs::class)
        ->and(array_map(static fn (Tab $tab): ?string => $tab->getKey(isAbsolute: false), $tabs->getTabs()))->toBe(['0', '1'])
        ->and($page->activeRelationManager)->toBe('0');
});

it('spreads a registration configuration over the manager defaults', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditConfiguredRelationProject::class, ['record' => $key], client: $client)->component();

    $tabs = relationManagersContent($page)[0];
    $embeds = relationManagersEmbeds($tabs->getTabs()[0]);

    expect($embeds[0]->getComponent())->toBe(TaggedTasksRelationManager::class)
        ->and($embeds[0]->getData())->toBe([
            'ownerRecord' => $key,
            'pageClass' => EditConfiguredRelationProject::class,
            'tag' => 'configured',
        ]);
});

it('drops a manager the record hides and a group that names none', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditFilteredRelationProject::class, ['record' => $key], client: $client)->component();

    // Three registrations, one survivor: without the filter the page would take
    // the tabs arm and mount the hidden manager beside an empty group's tab.
    expect(array_keys($page->getCachedRelationManagers()))->toBe(['tagged'])
        ->and(relationManagersContent($page)[0])->toBeInstanceOf(LiveComponent::class)
        ->and($page->activeRelationManager)->toBe('tagged');

    $crawler = $this->createLiveComponent(EditFilteredRelationProject::class, ['record' => $key], client: $client)
        ->render()
        ->crawler();

    // Scoped to the page's own bar: the surviving manager carries record-filter
    // tabs of its own, which are a different strip on the same page.
    expect($crawler->filter('[role="tab"][data-model="activeRelationManager"]')->count())->toBe(0)
        ->and($crawler->filter('section.sy-pa-relation-manager')->count())->toBe(1);
});

it('spreads the manager own default properties into a bare registration', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditFilteredRelationProject::class, ['record' => $key], client: $client)->component();

    $embed = relationManagersContent($page)[0];

    expect($embed->getComponent())->toBe(TaggedTasksRelationManager::class)
        ->and($embed->getData())->toBe([
            'ownerRecord' => $key,
            'pageClass' => EditFilteredRelationProject::class,
            'tag' => 'default',
        ]);
});

it('folds a group into one tab holding every manager it names', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditConfiguredRelationProject::class, ['record' => $key], client: $client)->component();

    $tab = relationManagersContent($page)[0]->getTabs()[1];
    $embeds = relationManagersEmbeds($tab);

    expect($tab->getLabel())->toBe('Work')
        ->and($embeds)->toHaveCount(1)
        // The group key joins the class, so the same manager can be registered
        // in two groups and still keep two identities.
        ->and($embeds[0]->getKey(isAbsolute: false))->toBe(TasksRelationManager::class . '-primary');
});

it('mounts a lone group as a plain container of its managers', function (): void {
    [$client, $key] = relationManagersPageClient();

    $page = $this->createLiveComponent(EditGroupedRelationProject::class, ['record' => $key], client: $client)->component();

    $components = relationManagersContent($page);
    $embeds = relationManagersEmbeds($components[0]);

    expect($components[0])->toBeInstanceOf(Group::class)
        ->and($components[0]->isHidden())->toBeFalse()
        ->and(array_map(static fn (LiveComponent $embed): ?string => $embed->getKey(isAbsolute: false), $embeds))->toBe([
            TasksRelationManager::class . '-primary',
            PaginatedTasksRelationManager::class . '-paged',
        ]);
});

it('renders the single manager under the page form with no tab bar', function (): void {
    [$client, $key] = relationManagersPageClient();

    $crawler = $this->createLiveComponent(EditSingleRelationProject::class, ['record' => $key], client: $client)
        ->render()
        ->crawler();

    expect($crawler->filter('.sy-pa-resource-edit-record-page section.sy-pa-relation-manager')->count())->toBe(1)
        ->and($crawler->filter('[role="tab"]')->count())->toBe(0)
        // The manager sits after the page form rather than inside it: its own
        // actions open forms, which cannot nest in the page's form element.
        ->and($crawler->filter('form section.sy-pa-relation-manager')->count())->toBe(0)
        ->and($crawler->filter('section.sy-pa-relation-manager tbody .sy-ta-row')->count())->toBe(3);
});

it('renders one manager at a time behind the tab bar and switches on the live property', function (): void {
    [$client, $key] = relationManagersPageClient();

    $component = $this->createLiveComponent(EditTwoRelationProject::class, ['record' => $key], client: $client);

    $first = $component->render()->crawler();

    expect($first->filter('[role="tab"]')->count())->toBe(2)
        ->and($first->filter('[role="tab"]')->eq(0)->attr('data-tab-key'))->toBe('tasks')
        ->and($first->filter('[role="tab"]')->eq(1)->attr('data-tab-key'))->toBe('paged')
        // Only the active tab's panel renders in the live-property mode, so
        // exactly one manager is mounted per request.
        ->and($first->filter('section.sy-pa-relation-manager')->count())->toBe(1)
        ->and($first->filter('section.sy-pa-relation-manager tbody .sy-ta-row')->count())->toBe(3)
        // The writing manager is the one mounted: its header action is what the
        // paginating manager beside it does not carry.
        ->and($first->filter('section.sy-pa-relation-manager [data-live-name-param="create"]')->count())->toBe(1);

    $second = $component->set('activeRelationManager', 'paged')->render()->crawler();

    expect($second->filter('section.sy-pa-relation-manager')->count())->toBe(1)
        ->and($second->filter('section.sy-pa-relation-manager tbody .sy-ta-row')->count())->toBe(2)
        ->and($second->filter('section.sy-pa-relation-manager [data-live-name-param="create"]')->count())->toBe(0);
});
