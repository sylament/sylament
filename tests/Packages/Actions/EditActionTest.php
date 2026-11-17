<?php

declare(strict_types=1);

use Sylament\Actions\ActionGroup;
use Sylament\Actions\EditAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\EditableHeadlineTable;
use Sylament\Tests\Fixtures\Tables\EditableInvoiceLineTable;
use Sylament\Tests\Fixtures\Tables\EditableOrderLineTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

describe('the trigger', function (): void {
    it('reads "Edit" in the primary colour, with a pencil on its own and inside a dropdown', function (): void {
        $bare = $this->renderViewComponent(EditAction::make());
        $grouped = $this->renderViewComponent(ActionGroup::make([EditAction::make()]));

        expect($bare)->toContainElement('button' . SupportSelectors::color('primary'), 'pencil-square-20-solidEdit')
            ->and($bare)->toContainElement(SupportSelectors::ICON, 'pencil-square-20-solid')
            ->and($grouped)->toContainElement(SupportSelectors::DROPDOWN_ITEM . SupportSelectors::color('primary') . ' ' . SupportSelectors::ICON, 'pencil-square-20-solid');
    });

    it('links to the page its host routes it to', function (): void {
        expect($this->createLiveComponent(RecordActionsHost::class)->render()->crawler())->toContainElement('a[href="/headlines/linkedEdit"]', 'pencil-square-20-solidEdit');
    });
});

describe('the modal', function (): void {
    it('is filled from the row\'s record, headed "Edit" and its title, and offers Save changes and Cancel', function (): void {
        $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

        $tester = $this->createLiveComponent(EditableHeadlineTable::class)
            ->mountAction(TestAction::make(EditAction::class)->table($sun))
            ->assertActionDataSet(['title' => 'Sun']);

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Edit Sun')
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Save changes', 'Cancel']);
    });

    it('is headed by the bare verb when nothing names its record', function (): void {
        expect($this->createLiveComponent(RecordActionsHost::class)->mountAction('editUntitled')->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, 'Edit');
    });

    it("saves the row's record in one transaction, toasts \"Saved\" and shows the new value in the row", function (): void {
        $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

        $tester = $this->createLiveComponent(EditableHeadlineTable::class)
            ->callAction(TestAction::make(EditAction::class)->table($sun), data: ['title' => 'Moon'])
            ->assertActionNotMounted()
            ->assertNotified('Saved');

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))
            ->toBe(['"START TRANSACTION"', 'UPDATE CappedHeadline SET', '"COMMIT"'])
            ->and($this->reload($sun))->toBeRecord(title: 'Moon')
            ->and($tester->render()->crawler())->toContainElement(TablesSelectors::TEXT_COLUMN, 'Moon');
    });

    it('answers a constraint as a field error, writes nothing, and shows the refused value in the row until the next request', function (): void {
        $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

        $tester = $this->createLiveComponent(EditableHeadlineTable::class)
            ->callAction(TestAction::make(EditAction::class)->table($sun), data: ['title' => 'Far too long'])
            ->assertHasActionErrors(['title']);

        expect($tester->render()->crawler())->toContainElement(TablesSelectors::TEXT_COLUMN, 'Far too long')
            ->and($this->reload($sun))->toBeRecord(title: 'Sun');

        expect($tester->refresh()->render()->crawler())->toContainElement(TablesSelectors::TEXT_COLUMN, 'Sun');
    });

    it('fills from and saves a record bound by its key, handing the record to using()', function (): void {
        $this->seed($lamp = new Product('Lamp'));

        $this->createLiveComponent(RecordActionsHost::class)
            ->call('mountAction', ['name' => 'editProduct', 'context' => ['recordKey' => (string) $lamp->id]])
            ->assertActionDataSet(['name' => 'Lamp'])
            ->setActionData(['name' => 'Vase'])
            ->callMountedAction()
            ->assertNotified('Saved');

        expect($this->reload($lamp))->toBeRecord(name: 'Vase');

        $this->createLiveComponent(RecordActionsHost::class)
            ->call('mountAction', ['name' => 'editUsing', 'context' => ['recordKey' => (string) $lamp->id]])
            ->setActionData(['name' => 'Bowl'])
            ->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['using() was handed a ' . Product::class . ', renamed Bowl'])
            ->and($this->reload($lamp))->toBeRecord(name: 'Vase');
    });

    it('fills from an array record and refuses to write one, advising using()', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class)
            ->mountAction('editArray')
            ->assertActionDataSet(['name' => 'Lamp']);

        expect(fn () => $tester->callMountedAction())
            ->toThrow(LogicException::class, 'The action [editArray] writes a record but no entity is bound — mount it from a query() table row or a record page, call record(), or take the write over with using().');
    });

    it('refuses by name to write through a form that never resolved', function (): void {
        $this->seed(new Product('Lamp'));

        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('editBare'))
            ->toThrow(LogicException::class, 'The action [editBare] edits a record through a form, but none resolved');
    });

    it('asks the record check of the reader its schema holds, rebound to the manager it writes through', function (): void {
        $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

        $this->createLiveComponent(EditableHeadlineTable::class)
            ->callAction(TestAction::make('editWatched')->table($sun), data: ['title' => 'Moon'])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['key check on a ' . CappedHeadline::class . ' by the rebound reader']);
    });
});

describe('a composite-keyed row', function (): void {
    it("saves the row's note, and only that row", function (string $table, object $line, object $sameHead): void {
        $this->seed($line, $sameHead);

        $this->createLiveComponent($table)
            ->callAction(TestAction::make(EditAction::class)->table($line), data: ['note' => 'Edited'])
            ->assertActionNotMounted()
            ->assertNotified('Saved');

        expect($this->reload($line))->toBeRecord(note: 'Edited')
            ->and($this->reload($sameHead))->toBeRecord(note: 'Kept');
    })->with([
        'scalar fields' => fn (): array => [EditableOrderLineTable::class, new OrderLine(order: 7, line: 2, note: 'Old'), new OrderLine(order: 7, line: 3, note: 'Kept')],
        'an association plus a scalar' => fn (): array => [EditableInvoiceLineTable::class, new InvoiceLine($seven = new Invoice(7), line: 2, note: 'Old'), new InvoiceLine($seven, line: 3, note: 'Kept')],
    ]);

    it('locks its key field under the edit operation, and refuses a changed key under an action of another name, putting it back', function (string $table, object $line): void {
        $this->seed($line);

        $locked = $this->createLiveComponent($table)->mountAction(TestAction::make(EditAction::class)->table($line));

        expect($locked->render()->crawler())->toContainElement('dialog[open] input[name="line"][disabled]');

        $this->createLiveComponent($table)
            ->mountAction(TestAction::make('editRenamed')->table($line))
            ->assertActionDataSet(['line' => 2])
            ->setActionData(['line' => 9, 'note' => 'Edited'])
            ->callMountedAction()
            ->assertHasActionErrors(['line'])
            ->assertActionMounted(TestAction::make('editRenamed')->table($line));

        expect($this->reload($line))->toBeRecord(line: 2, note: 'Old');
    })->with([
        'scalar fields' => fn (): array => [EditableOrderLineTable::class, new OrderLine(order: 7, line: 2, note: 'Old')],
        'an association plus a scalar' => fn (): array => [EditableInvoiceLineTable::class, new InvoiceLine(new Invoice(7), line: 2, note: 'Old')],
    ]);

    it("refuses a key half its before() hook moved, which the modal does not declare, in the modal's record errors", function (): void {
        $this->seed($line = new OrderLine(order: 7, line: 2, note: 'Old'));

        $tester = $this->createLiveComponent(EditableOrderLineTable::class)
            ->callAction(TestAction::make('editShifted')->table($line), data: ['note' => 'Edited'])
            ->assertActionMounted(TestAction::make('editShifted')->table($line));

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . Selectors::RECORD_ERROR, 'This value cannot be changed.')
            ->and($this->reload($line))->toBeRecord(line: 2, note: 'Old');
    });
});
