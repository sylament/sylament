<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Schema;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The select's two option actions, end to end. They are affix actions, so the
 * whole chain is under test at once: the suffix cache splices them into the
 * component action map, a trigger mounts one by the field's dotted key, the modal
 * schema lands on `mountedActionsData.0`, and the Doctrine defaults
 * `relationship()` installs write the row and hand the new key back as the
 * select's own state. Kernel reboot is disabled throughout — the fixture database
 * is an in-memory SQLite built by a container service, so a rebooted kernel would
 * hand each request a different (empty) one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function optionActionClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

/**
 * The mount payload a select's suffix trigger emits: the action's name and the
 * field's absolute key as the schema-component context.
 *
 * @param array<string, mixed> $arguments
 *
 * @return array{name: string, arguments: array<string, mixed>, context: array<string, string>}
 */
function optionActionMount(string $name, string $field = 'author', array $arguments = []): array
{
    return [
        'name' => $name,
        'arguments' => $arguments,
        'context' => ['schemaComponent' => 'schema.' . $field],
    ];
}

/**
 * Wire a select under a `data`-bag host bound to the `Post` record class, for the
 * gates that are answered without a round-trip.
 *
 * @param array<string, mixed> $data
 */
function wireOptionActionSelect(Select $select, array $data = []): Select
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->record(Post::class)->components([$select])->getComponents();

    return $select;
}

function seedAuthor(EntityManagerInterface $entityManager, string $name): Author
{
    $author = new Author();
    $author->setName($name);

    $entityManager->persist($author);
    $entityManager->flush();

    return $author;
}

it("mounts the create-option action by the field's dotted key and renders its modal form", function (): void {
    [$client] = optionActionClient();

    $html = (string) $this->createLiveComponent('SelectOptionForm', client: $client)
        ->call('mountAction', optionActionMount('createOption'))
        ->render();

    expect($html)->toContain('<dialog')
        ->toContain('>Create</h2>')
        ->toContain('mountedActionsData.0.name');
});

it('persists the created row, selects it and re-enumerates the options', function (): void {
    [$client, $entityManager] = optionActionClient();

    seedAuthor($entityManager, 'Ada');

    $component = $this->createLiveComponent('SelectOptionForm', client: $client)
        ->call('mountAction', optionActionMount('createOption'))
        ->set('mountedActionsData.0.name', 'Carol')
        ->call('callMountedAction');

    $entityManager->clear();
    $carol = $entityManager->getRepository(Author::class)->findOneBy(['name' => 'Carol']);

    expect($carol)->toBeInstanceOf(Author::class);

    $host = $component->component();
    $key = (string) $carol?->getId();

    expect($host->data['author'])->toBe($key)
        ->and($host->mountedActions)->toBe([])
        // The outer schema's own read validates the select against its `Choice`,
        // so a stale option memo would fail the key the action just created.
        ->and($host->getSchema('schema')->getState())->toBe(['author' => $carol?->getId()])
        ->and((string) $component->render())->toContain('>Carol</option>');
});

it('appends the created key to a multiple select and offers the create-another footer action', function (): void {
    [$client, $entityManager] = optionActionClient();

    $tag = new Tag();
    $tag->setName('a');

    $entityManager->persist($tag);
    $entityManager->flush();

    $component = $this->createLiveComponent('SelectOptionForm', [
        'relationship' => 'tags',
        'isMultiple' => true,
    ], client: $client);

    $component->set('data', ['tags' => [(string) $tag->getId()]])
        ->call('mountAction', optionActionMount('createOption', 'tags'));

    expect((string) $component->render())->toContain('>Create &amp; create another</button>');

    $component->set('mountedActionsData.0.name', 'b')->call('callMountedAction');

    $entityManager->clear();
    $created = $entityManager->getRepository(Tag::class)->findOneBy(['name' => 'b']);

    expect($component->component()->data['tags'])->toBe([(string) $tag->getId(), (string) $created?->getId()]);
});

it('keeps the modal mounted and resets its form when the create-another footer action submits', function (): void {
    [$client, $entityManager] = optionActionClient();

    $component = $this->createLiveComponent('SelectOptionForm', [
        'relationship' => 'tags',
        'isMultiple' => true,
    ], client: $client);

    $component->call('mountAction', optionActionMount('createOption', 'tags'))
        ->set('mountedActionsData.0.name', 'b')
        ->call('callMountedAction', ['arguments' => ['another' => true]]);

    $entityManager->clear();
    $created = $entityManager->getRepository(Tag::class)->findOneBy(['name' => 'b']);

    $host = $component->component();

    expect($created)->toBeInstanceOf(Tag::class)
        ->and($host->data['tags'])->toBe([(string) $created?->getId()])
        ->and($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActionsData)->toBe([0 => ['name' => null]]);
});

it('offers no create-another footer action on a single select', function (): void {
    [$client] = optionActionClient();

    $html = (string) $this->createLiveComponent('SelectOptionForm', client: $client)
        ->call('mountAction', optionActionMount('createOption'))
        ->render();

    expect($html)->not->toContain('create another');
});

it('fills the edit modal from the selected record and flushes the update', function (): void {
    [$client, $entityManager] = optionActionClient();

    $ada = seedAuthor($entityManager, 'Ada');
    $key = (string) $ada->getId();

    $component = $this->createLiveComponent('SelectOptionForm', client: $client);
    $component->set('data', ['author' => $key])
        ->call('mountAction', optionActionMount('editOption'));

    expect((string) $component->render())->toContain('value="Ada"');

    $component->set('mountedActionsData.0.name', 'Adele')->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->find(Author::class, $ada->getId())?->getName())->toBe('Adele')
        ->and($component->component()->data['author'])->toBe($key);
});

it('throws when a non-relationship select creates an option without a createOptionUsing closure', function (): void {
    [$client] = optionActionClient();

    $component = $this->createLiveComponent('SelectOptionForm', ['hasRelationship' => false], client: $client);
    $component->call('mountAction', optionActionMount('createOption'));

    expect(fn (): mixed => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'createOptionUsing');
});

it('forgets the memoized option map on demand, so a row the create arm wrote is enumerable', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ada = seedAuthor($entityManager, 'Ada');

    $select = wireOptionActionSelect(Select::make('author')->relationship(titleAttribute: 'name'));

    expect($select->getOptions())->toBe([$ada->getId() => 'Ada']);

    $carol = seedAuthor($entityManager, 'Carol');

    expect($select->getOptions())->toBe([$ada->getId() => 'Ada']);

    $select->forgetCachedRelationshipOptions();

    expect($select->getOptions())->toBe([$ada->getId() => 'Ada', $carol->getId() => 'Carol']);
});

it('caches no option action until a form is declared for it', function (): void {
    $this::getContainer();

    $select = wireOptionActionSelect(Select::make('author')->relationship(titleAttribute: 'name'));

    expect($select->getSuffixActions())->toBe([])
        ->and($select->getCreateOptionAction())->toBeNull()
        ->and($select->getEditOptionAction())->toBeNull()
        ->and($select->hasCreateOptionActionFormSchema())->toBeFalse()
        ->and($select->hasEditOptionActionFormSchema())->toBeFalse();
});

it('closes both option actions on a disabled select', function (): void {
    $this::getContainer();

    $select = wireOptionActionSelect(
        Select::make('author')
            ->relationship(titleAttribute: 'name')
            ->manageOptionForm([TextInput::make('name')])
            ->disabled(),
        ['author' => '1'],
    );

    expect($select->getCreateOptionAction())->toBeNull()
        ->and($select->getEditOptionAction())->toBeNull()
        ->and($select->getSuffixActions())->toBe([]);
});

it('closes the edit action on a multiple select and hides it while nothing is selected', function (): void {
    $this::getContainer();

    $single = wireOptionActionSelect(
        Select::make('author')->relationship(titleAttribute: 'name')->manageOptionForm([TextInput::make('name')]),
    );

    expect($single->getEditOptionAction()?->isVisible())->toBeFalse();

    $selected = wireOptionActionSelect(
        Select::make('author')->relationship(titleAttribute: 'name')->manageOptionForm([TextInput::make('name')]),
        ['author' => '1'],
    );

    expect($selected->getEditOptionAction()?->isVisible())->toBeTrue();

    $multiple = wireOptionActionSelect(
        Select::make('tags')->multiple()->relationship(titleAttribute: 'name')->manageOptionForm([TextInput::make('name')]),
        ['tags' => ['1']],
    );

    expect($multiple->getEditOptionAction())->toBeNull()
        ->and(array_keys($multiple->getSuffixActions()))->toBe(['createOption']);
});

it('makes the field live as soon as an edit form is declared', function (): void {
    $this::getContainer();

    $plain = wireOptionActionSelect(Select::make('author')->options(['a' => 'A']));
    $edited = wireOptionActionSelect(Select::make('author')->options(['a' => 'A'])->editOptionForm([TextInput::make('name')]));

    expect($plain->getDataModel())->toBe('norender|data.author')
        ->and($edited->getDataModel())->toBe('data.author');
});

it('applies the manage-level modifier before the per-action one', function (): void {
    $this::getContainer();

    $select = wireOptionActionSelect(
        Select::make('author')
            ->options(['a' => 'A'])
            ->manageOptionForm([TextInput::make('name')])
            ->manageOptionActions(static fn (Action $action): Action => $action->label('Managed'))
            ->createOptionAction(static fn (Action $action): Action => $action->label($action->getLabel() . ' create'))
            ->editOptionAction(static fn (Action $action): Action => $action->label($action->getLabel() . ' edit')),
        ['author' => 'a'],
    );

    expect($select->getCreateOptionAction()?->getLabel())->toBe('Managed create')
        ->and($select->getEditOptionAction()?->getLabel())->toBe('Managed edit');
});

it('carries the ported headings, submit labels, names and gray icon-button chrome', function (): void {
    $this::getContainer();

    $select = wireOptionActionSelect(
        Select::make('author')->options(['a' => 'A'])->manageOptionForm([TextInput::make('name')]),
        ['author' => 'a'],
    );

    $create = $select->getCreateOptionAction();
    $edit = $select->getEditOptionAction();

    expect($select->getCreateOptionActionName())->toBe('createOption')
        ->and($select->getEditOptionActionName())->toBe('editOption')
        ->and($create?->getLabel())->toBe('Create')
        ->and($create?->getModalHeading())->toBe('Create')
        ->and($create?->getModalSubmitActionLabel())->toBe('Create')
        ->and($create?->getColor())->toBe('gray')
        ->and($create?->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($create?->getIcon())->toBe('heroicons:plus-20-solid')
        ->and($edit?->getLabel())->toBe('Edit')
        ->and($edit?->getModalHeading())->toBe('Edit')
        ->and($edit?->getModalSubmitActionLabel())->toBe('Save')
        ->and($edit?->getColor())->toBe('gray')
        ->and($edit?->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($edit?->getIcon())->toBe('heroicons:pencil-square-20-solid')
        ->and($select->createOptionModalHeading('New author')->getCreateOptionModalHeading())->toBe('New author')
        ->and($select->editOptionModalHeading('Rename')->getEditOptionModalHeading())->toBe('Rename');
});

it("renders both option actions as icon buttons in the select's suffix slot", function (): void {
    [$client, $entityManager] = optionActionClient();

    $ada = seedAuthor($entityManager, 'Ada');

    $component = $this->createLiveComponent('SelectOptionForm', client: $client);
    $html = (string) $component->set('data', ['author' => (string) $ada->getId()])->render();

    expect($html)->toContain('sy-input-wrp-suffix')
        ->toContain('sy-icon-btn')
        ->toContain('data-live-name-param="createOption"')
        ->toContain('data-live-name-param="editOption"')
        ->toContain('data-live-context-param="{&quot;schemaComponent&quot;:&quot;schema.author&quot;}"');
});
