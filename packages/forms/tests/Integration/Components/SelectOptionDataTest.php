<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Emblem;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Greenhouse;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Planter;
use Sylament\Forms\Tests\Fixtures\Twig\Components\SelectOptionDataForm;
use Sylament\Schemas\Schema;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * What a relationship select's two option defaults write: the option action's
 * data, so its mutateDataUsing() reaches the record, and never a path that
 * mutation added and no component of the option form maps, which is refused by
 * name. A key the client sent is neither refused nor written, and a
 * createOptionUsing() or updateOptionUsing() of one's own is never guarded.
 * Kernel reboot is disabled throughout — the fixture database is held by a
 * container service, so a rebooted kernel would hand each request a different
 * one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function selectOptionDataClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

/**
 * @return array{name: string, arguments: array<string, mixed>, context: array<string, string>}
 */
function selectOptionDataMount(string $name): array
{
    return ['name' => $name, 'arguments' => [], 'context' => ['schemaComponent' => 'schema.emblem']];
}

/**
 * Every emblem row as stored: the code in lower case, as `EmblemCodeType` writes it.
 *
 * @return list<array<string, mixed>>
 */
function selectOptionDataEmblemRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT code, label FROM Emblem ORDER BY code');
}

function selectOptionDataSeedEmblem(EntityManagerInterface $entityManager): void
{
    $emblem = new Emblem();
    $emblem->setCode('ACE');
    $emblem->setLabel('Ace');

    $entityManager->persist($emblem);
    $entityManager->flush();
}

function selectOptionDataRefusal(string $action, string $using): string
{
    return sprintf("The [data.emblem] select's [%s] action added [motto] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with %s().", $action, $using);
}

/**
 * Bind a schema to a record through a `data`-bag host, validator-fed so the
 * record check behind `getData()` runs its key passes.
 *
 * @param list<object> $components
 *
 * @return array{Schema, FieldRenderHost}
 */
function selectOptionDataWire(object $record, array $components): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A stored greenhouse with the planters select wired onto it, and the
 * create-option default that select installs.
 */
function selectOptionDataPlanterDefault(EntityManagerInterface $entityManager): Closure
{
    $greenhouse = new Greenhouse();
    $greenhouse->setName('North');

    $entityManager->persist($greenhouse);
    $entityManager->flush();

    $select = Select::make('planters')->relationship(titleAttribute: 'crop')->multiple();
    selectOptionDataWire($greenhouse, [$select]);

    $createOptionUsing = $select->getCreateOptionUsing();
    assert($createOptionUsing instanceof Closure);

    return static fn (Schema $optionSchema): mixed => $createOptionUsing($select, $optionSchema, Action::make('createOption')->data($optionSchema->getState()));
}

it("writes the create-option action's mutated data onto the new record", function (): void {
    [$client, $entityManager] = selectOptionDataClient();

    $component = $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::UPPERCASES], client: $client)
        ->call('mountAction', selectOptionDataMount('createOption'))
        ->set('mountedActionsData.0.code', 'ACE')
        ->set('mountedActionsData.0.label', 'Ace')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'ACE']])
        ->and($component->component()->data['emblem'])->toBe('ACE');
});

it("writes the edit-option action's mutated data onto the selected record", function (): void {
    [$client, $entityManager] = selectOptionDataClient();
    selectOptionDataSeedEmblem($entityManager);

    $component = $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::UPPERCASES], client: $client);
    $component->set('data', ['emblem' => 'ACE'])
        ->call('mountAction', selectOptionDataMount('editOption'))
        ->set('mountedActionsData.0.label', 'Renamed')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'RENAMED']]);
});

it("refuses a path the create-option action's mutation adds that no component maps, and writes nothing", function (): void {
    [$client, $entityManager] = selectOptionDataClient();

    $component = $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::ADDS_UNMAPPED], client: $client)
        ->call('mountAction', selectOptionDataMount('createOption'))
        ->set('mountedActionsData.0.code', 'ACE')
        ->set('mountedActionsData.0.label', 'Ace');

    expect(fn (): mixed => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, selectOptionDataRefusal('createOption', 'createOptionUsing'));

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([]);
});

it("refuses a path the edit-option action's mutation adds that no component maps, and leaves the record", function (): void {
    [$client, $entityManager] = selectOptionDataClient();
    selectOptionDataSeedEmblem($entityManager);

    $component = $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::ADDS_UNMAPPED], client: $client);
    $component->set('data', ['emblem' => 'ACE'])
        ->call('mountAction', selectOptionDataMount('editOption'))
        ->set('mountedActionsData.0.label', 'Renamed');

    expect(fn (): mixed => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, selectOptionDataRefusal('editOption', 'updateOptionUsing'));

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'Ace']]);
});

/*
 * The message's remedy: the label is mapped by a Hidden field the user never
 * types, so the value the mutation sets is written. The Hidden takes
 * required(false), as the message says, because `Emblem::$label` is a
 * non-nullable string the record would otherwise require. It dehydrates its
 * null into the state the action is handed, so this pins the remedy rather than
 * an addition.
 */
it('writes a value the mutation sets on a path a Hidden field maps', function (): void {
    [$client, $entityManager] = selectOptionDataClient();

    $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::FILLS_HIDDEN], client: $client)
        ->call('mountAction', selectOptionDataMount('createOption'))
        ->set('mountedActionsData.0.code', 'ACE')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'Stamped']]);
});

/*
 * A key the client sent is in the state the action was handed, so it is no
 * addition and is never refused. It is not written either: `Emblem` has no
 * `motto`, so a write would have been Symfony's NoSuchPropertyException, and
 * the save succeeding is the evidence.
 */
it('neither refuses nor writes a key the client sent that no component maps', function (): void {
    [$client, $entityManager] = selectOptionDataClient();

    $this->createLiveComponent('SelectOptionDataForm', client: $client)
        ->call('mountAction', selectOptionDataMount('createOption'))
        ->set('mountedActionsData.0.code', 'ACE')
        ->set('mountedActionsData.0.label', 'Ace')
        ->set('mountedActionsData.0.motto', 'Brave')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'Ace']]);
});

it('never guards a createOptionUsing() of its own, which reads the added path', function (): void {
    [$client, $entityManager] = selectOptionDataClient();

    $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::ADDS_UNMAPPED, 'takesCreateOver' => true], client: $client)
        ->call('mountAction', selectOptionDataMount('createOption'))
        ->set('mountedActionsData.0.code', 'ACE')
        ->set('mountedActionsData.0.label', 'Ace')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'Ace / Brave']]);
});

it('never guards an updateOptionUsing() of its own, which reads the added path', function (): void {
    [$client, $entityManager] = selectOptionDataClient();
    selectOptionDataSeedEmblem($entityManager);

    $component = $this->createLiveComponent('SelectOptionDataForm', ['mutation' => SelectOptionDataForm::ADDS_UNMAPPED, 'takesUpdateOver' => true], client: $client);
    $component->set('data', ['emblem' => 'ACE'])
        ->call('mountAction', selectOptionDataMount('editOption'))
        ->set('mountedActionsData.0.label', 'Renamed')
        ->call('callMountedAction');

    $entityManager->clear();

    expect(selectOptionDataEmblemRows($entityManager))->toBe([['code' => 'ace', 'label' => 'Renamed / Brave']]);
});

/*
 * D22: with the option form's key rules switched off, a blank key field the
 * form maps is no longer refused under that field by the key pass and reaches
 * the default's own refusal, whose advice — put the field in the form — would
 * then be false on its own. `Planter::$slot` is a non-nullable string, so the
 * field takes required(false), which the record would otherwise impose, and a
 * whitespace-only value, since a TextInput dehydrates an empty one to a null
 * the property cannot take. `Planter`'s callback fills neither key half, so
 * the check after persist() is what refuses.
 */
it('names validateRecordKey() in the incomplete-key refusal when the option form turns the key rules off', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $create = selectOptionDataPlanterDefault($entityManager);

    [$optionSchema] = selectOptionDataWire(new Planter(), [TextInput::make('bed'), TextInput::make('slot')->required(false), TextInput::make('crop')]);
    $optionSchema->validateRecordKey(false);
    $optionSchema->fill(['bed' => 'north', 'slot' => '   ', 'crop' => 'basil']);

    expect(static fn (): mixed => $create($optionSchema))
        ->toThrow(LogicException::class, sprintf('The [planters] select created a [%s] whose identifier is incomplete, so no option could name it. Put every identifier field in createOptionForm(). The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.', Planter::class));

    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT bed, slot, crop, season FROM Planter'))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue();
});

/*
 * The sibling with the rules on: the blank half is one the form leaves out, so
 * the record check refuses nothing and the default's refusal keeps its form
 * advice alone. Pest's toThrow() matches a substring, so the whole message is
 * compared here to pin the clause's absence.
 */
it('keeps the incomplete-key refusal to its form advice while the key rules are on', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $create = selectOptionDataPlanterDefault($entityManager);

    [$optionSchema] = selectOptionDataWire(new Planter(), [TextInput::make('bed'), TextInput::make('crop')]);
    $optionSchema->fill(['bed' => 'north', 'crop' => 'basil']);

    $message = null;

    try {
        $create($optionSchema);
    } catch (LogicException $logicException) {
        $message = $logicException->getMessage();
    }

    expect($message)->toBe(sprintf('The [planters] select created a [%s] whose identifier is incomplete, so no option could name it. Put every identifier field in createOptionForm().', Planter::class));
});
