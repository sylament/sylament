<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\GardenRow;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LineManifest;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AttachableRelationTable;
use Sylament\Tests\Infrastructure\Doctrine\FailingWrite;

beforeEach(function (): void {
    $this->kew = new Garden('Kew');
    $this->kew->addPlant($this->rock = new Plant('Rock'));
    $this->kew->addPlant($this->moss = new Plant('Moss'));
    $this->kew->addPlant($this->fern = new Plant('Fern'));

    $this->wisley = new Garden('Wisley');
    $this->wisley->addPlant($this->rock);
    $this->seed($this->kew, $this->rock, $this->moss, $this->fern, $this->wisley);

    $this->table = fn (array $variants = []): Testable => $this->createLiveComponent(
        AttachableRelationTable::class,
        AttachableRelationTable::props($this->entityManager(), $this->kew, 'plants', variants: $variants),
    );
    $this->plantsOf = function (Garden $garden): array {
        $names = array_map(static fn (Plant $plant): string => $plant->name, $this->reload($garden)->plants->toArray());
        sort($names);

        return $names;
    };
});

it('puts a danger "Detach selected" with the x-mark in the toolbar, confirmed in a modal headed by the plural label, with the x-mark and a "Detach" submit', function (): void {
    $tester = ($this->table)();
    $trigger = $tester->render()->crawler()->filter(Selectors::trigger('detach') . SupportSelectors::color('danger') . Selectors::BULK_ONLY);
    $modal = $tester->selectTableRecords([$this->rock])->mountAction(TestAction::make('detach')->table()->bulk())->render()->crawler();

    expect($trigger->filter(SupportSelectors::ICON)->text())->toBe('x-mark-20-solid')
        ->and(mb_trim(str_replace('x-mark-20-solid', '', $trigger->text())))->toBe('Detach selected')
        ->and($modal->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Detach selected Plants')
        ->and($modal->filter(SupportSelectors::MODAL_DESCRIPTION)->text())->toBe('Are you sure you would like to do this?')
        ->and($modal->filter(SupportSelectors::MODAL_ICON)->text())->toBe('x-mark')
        ->and($modal->filter(SupportSelectors::MODAL_SUBMIT . SupportSelectors::color('danger'))->text())->toBe('Detach');
});

it('detaches every selected plant from the garden in one flush, keeping the plants, toasts "Detached" and clears the selection', function (array $variants): void {
    $tester = ($this->table)($variants)->selectTableRecords([$this->rock, $this->moss]);
    $selected = $tester->component()->selectedTableRecordKeys;

    $tester->callAction(TestAction::make('detach')->table()->bulk())->assertNotified('Detached');
    $commits = array_count_values($this->statementsRun())['"COMMIT"'] ?? 0;

    expect($selected)->toHaveCount(2)
        ->and($commits)->toBe(1)
        ->and($tester->component()->selectedTableRecordKeys)->toBe([])
        ->and(($this->plantsOf)($this->kew))->toBe(['Fern'])
        ->and(($this->plantsOf)($this->wisley))->toBe(['Rock'])
        ->and($this->reload($this->moss)->name)->toBe('Moss');
})->with([
    'reading the selected records' => [[]],
    'by their keys' => [['noFetch']],
]);

it('detaches composite-keyed rows by their keys, and only those rows', function (Closure $scene, string $relation): void {
    [$manifest, $gone, $kept] = $scene();
    $this->seed($manifest, $kept, ...$gone);

    $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $manifest, $relation, 'note', ['noFetch']))
        ->selectTableRecords($gone)
        ->callAction(TestAction::make('detach')->table()->bulk())
        ->assertNotified('Detached');

    expect(array_map(fn (object $record): string => RecordKey::of($this->entityManager(), $record), $this->reload($manifest)->{$relation}->toArray()))
        ->toBe([RecordKey::of($this->entityManager(), $kept)]);
})->with([
    'scalar fields' => [static function (): array {
        $manifest = new LineManifest();
        $manifest->addOrderLine($first = new OrderLine(order: 7, line: 2));
        $manifest->addOrderLine($second = new OrderLine(order: 8, line: 3));
        $manifest->addOrderLine($kept = new OrderLine(order: 7, line: 3));

        return [$manifest, [$first, $second], $kept];
    }, 'orderLines'],
    'an association plus a scalar' => [static function (): array {
        $manifest = new LineManifest();
        $manifest->addInvoiceLine($first = new InvoiceLine($seven = new Invoice(7), line: 2));
        $manifest->addInvoiceLine($second = new InvoiceLine($eight = new Invoice(8), line: 3));
        $manifest->addInvoiceLine($kept = new InvoiceLine($seven, line: 3));

        return [$manifest, [$first, $second], $kept];
    }, 'invoiceLines'],
]);

it('reads the selection by key in as many statements as by record on the owning side, and in one more a key on the inverse side', function (Closure $scene, string $relation, int $extra): void {
    [$byRecord, $byKey] = [$scene(), $scene()];
    $this->seed(...$byRecord[2], ...$byKey[2]);
    $statements = function (array $arm, array $variants) use ($relation): int {
        [$owner, $selection] = $arm;

        $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $owner, $relation, variants: $variants))
            ->selectTableRecords($selection)
            ->callAction(TestAction::make('detach')->table()->bulk())
            ->assertNotified('Detached');

        return count($this->statementsRun());
    };

    expect($statements($byKey, ['noFetch']))->toBe($statements($byRecord, []) + $extra);
})->with([
    'the owning side, two keys' => [static function (): array {
        $garden = new Garden('Kew');
        $garden->addPlant($rock = new Plant('Rock'));
        $garden->addPlant($moss = new Plant('Moss'));

        return [$garden, [$rock, $moss], [$garden, $rock, $moss]];
    }, 'plants', 0],
    'the inverse side, two keys' => [static function (): array {
        $rock = new Plant('Rock');
        ($kew = new Garden('Kew'))->addPlant($rock);
        ($wisley = new Garden('Wisley'))->addPlant($rock);

        return [$rock, [$kew, $wisley], [$rock, $kew, $wisley]];
    }, 'gardens', 2],
]);

it('detaches nothing when one join row fails to go, toasting that the whole selection failed', function (): void {
    FailingWrite::afterJoinTableDeletes($this->entityManager(), Garden::class, 'plants', 1);

    ($this->table)()
        ->selectTableRecords([$this->rock, $this->moss])
        ->callAction(TestAction::make('detach')->table()->bulk())
        ->assertNotified('Failed to detach 2 records');

    expect($this->statementsRun())->toContain('"ROLLBACK"')
        ->and(($this->plantsOf)($this->kew))->toBe(['Fern', 'Moss', 'Rock']);
});

it('counts what a using() closure reports done in the failure title', function (): void {
    ($this->table)(['partialBulk'])
        ->selectTableRecords([$this->rock, $this->moss])
        ->callAction(TestAction::make('detach')->table()->bulk())
        ->assertNotified('Detached 1 of 2');
});

it('hands using() the selected records instead of detaching them', function (): void {
    ($this->table)(['recordingBulk'])
        ->selectTableRecords([$this->rock, $this->moss])
        ->callAction(TestAction::make('detach')->table()->bulk());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['detach 2 selected'])
        ->and(($this->plantsOf)($this->kew))->toBe(['Fern', 'Moss', 'Rock']);
});

it('toasts a failure for a one-to-many the mapping forbids dissociating, detaching nothing', function (): void {
    $row = new GardenRow();
    $row->garden = $this->reload($this->kew);
    $this->seed($row);

    $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->kew, 'rows', null))
        ->selectTableRecords([$row])
        ->callAction(TestAction::make('detach')->table()->bulk())
        ->assertNotified('Failed to detach 1 records');

    expect($this->reload($row)->garden?->id)->toBe($this->kew->id);
});

it('refuses a selection of neither entities nor keys as the developer error it is', function (): void {
    expect(fn (): Testable => ($this->table)(['arrayRecords'])
        ->selectTableRecords(['0'])
        ->callAction(TestAction::make('detach')->table()->bulk()))
        ->toThrow(LogicException::class, 'DetachBulkAction can only detach entities or their keys, got array.')
        ->and(($this->plantsOf)($this->kew))->toBe(['Fern', 'Moss', 'Rock']);
});

it('refuses a table that declares no relationship', function (): void {
    expect(fn (): Testable => ($this->table)(['queryOnly'])
        ->selectTableRecords([$this->rock])
        ->callAction(TestAction::make('detach')->table()->bulk()))
        ->toThrow(LogicException::class, 'The action [detach] detaches selected records from a relationship, which only a table declaring relationship() can supply.')
        ->and(($this->plantsOf)($this->kew))->toBe(['Fern', 'Moss', 'Rock']);
});
