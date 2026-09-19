<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkAction;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableActionsHost;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('binds a table and falls back through the group', function (): void {
    $table = Table::make(new TableHost());
    $action = Action::make('edit');

    expect($action->hasTable())->toBeFalse();

    $group = ActionGroup::make([$action])->table($table);

    expect($action->getTable())->toBe($table)
        ->and($action->hasTable())->toBeTrue()
        ->and($group->getTable())->toBe($table);
});

it('lets an action table binding win over its group', function (): void {
    $own = Table::make(new TableHost());
    $action = Action::make('edit')->table($own);

    ActionGroup::make([$action])->table(Table::make(new TableHost()));

    expect($action->getTable())->toBe($own);
});

it('emits the table flag beside the record key for a table-bound action', function (): void {
    $action = Action::make('edit')
        ->table(Table::make(new TableHost()))
        ->record(['__key' => '7']);

    expect($action->getContext())->toBe(['recordKey' => '7', 'table' => true]);
});

/**
 * A query-lane table over the composite fixture, built outside the container
 * through the HOST, the way `CanReorderRecordsTest::reorderRig()` builds one:
 * `Table::make($host)` runs only `newInstance()`, `liveComponent()` and
 * `configure()` and never calls `$host->table()`, so a table built that way
 * carries no query and answers `getRecordClass() === null`.
 */
function shortcutKeyedTable(): Table
{
    $entityManager = EntityManagerFactory::create();

    return new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut')))
        ->getTable();
}

it('carries the table-minted composite key for a record of its record class', function (): void {
    $table = shortcutKeyedTable();

    $record = new Shortcut();
    $record->setHost('sylament.test');
    $record->setPath('/docs/a b');
    $record->setLabel('Docs');

    $action = Action::make('edit')->table($table)->record($record);

    expect($table->getRecordClass())->toBe(Shortcut::class)
        ->and($action->getContext())->toBe([
            'recordKey' => 'sylament=2Etest~=2Fdocs=2Fa=20b',
            'table' => true,
        ]);
});

it('falls back to the id reading for a record the table does not key', function (): void {
    $table = shortcutKeyedTable();

    $action = Action::make('edit')->table($table)->record(new Song(7, 'Airbag', 100));

    expect($table->getRecordClass())->toBe(Shortcut::class)
        ->and($action->getContext())->toBe(['recordKey' => '7', 'table' => true]);
});

it('leaves a record the table cannot key and that has no id without a recordKey', function (): void {
    $action = Action::make('edit')->table(shortcutKeyedTable())->record(new class {});

    expect($action->getContext())->toBe(['table' => true]);
});

it('refuses a record of the table class whose identifier is incomplete', function (): void {
    $record = new Shortcut();
    $record->setHost('sylament.test');

    $action = Action::make('edit')->table(shortcutKeyedTable())->record($record);

    expect(fn (): array => $action->getContext())->toThrow(LogicException::class, 'is not a printable value');
});

it('drops the table flag on a nested frame', function (): void {
    $action = Action::make('child')
        ->table(Table::make(new TableHost()))
        ->record(['__key' => '7'])
        ->parentAction(Action::make('parent'));

    expect($action->getContext())->toBe(['recordKey' => '7']);
});

it('resolves the live component through the bound table', function (): void {
    $host = new TableActionsHost();

    $action = Action::make('edit')->table(Table::make($host));

    expect($action->getLiveComponent())->toBe($host);
});

it('emits the bulk flag beside the table flag for a bulk table action', function (): void {
    $action = BulkAction::make('delete')->table(Table::make(new TableHost()));

    expect($action->getContext())->toBe(['table' => true, 'bulk' => true]);
});

it('emits no bulk flag for a plain table action', function (): void {
    $action = Action::make('edit')->table(Table::make(new TableHost()));

    expect($action->getContext())->toBe(['table' => true]);
});

it('drops the bulk flag with the table flag on a nested frame', function (): void {
    $action = BulkAction::make('delete')
        ->table(Table::make(new TableHost()))
        ->record(['__key' => '7'])
        ->parentAction(Action::make('parent'));

    expect($action->getContext())->toBe(['recordKey' => '7']);
});
