<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\GardenRow;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LineManifest;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AttachableRelationTable;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function (): void {
    $this->kew = new Garden('Kew');
    $this->kew->addPlant($this->rock = new Plant('Rock'));
    $this->kew->addPlant($this->moss = new Plant('Moss'));

    $this->wisley = new Garden('Wisley');
    $this->wisley->addPlant($this->rock);
    $this->seed($this->kew, $this->rock, $this->moss, $this->wisley);

    $this->plantsOf = fn (Garden $garden): array => array_map(static fn (Plant $plant): string => $plant->name, $this->reload($garden)->plants->toArray());
});

it('puts a danger "Detach" with the x-mark on each row, confirmed in a modal headed by the record title, with the x-mark and a "Detach" submit', function (?string $titleAttribute, string $heading): void {
    $tester = $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->kew, 'plants', $titleAttribute));
    $trigger = $tester->render()->crawler()->filter(Selectors::trigger('detach') . SupportSelectors::color('danger') . ':not(' . Selectors::BULK_ONLY . ')');
    $modal = $tester->mountAction(TestAction::make('detach')->table($this->rock))->render()->crawler();

    expect($trigger->each(static fn (Crawler $button): string => $button->filter(SupportSelectors::LINK_LABEL)->text()))->toBe(['Detach', 'Detach'])
        ->and($trigger->first()->filter(SupportSelectors::ICON)->text())->toBe('x-mark-20-solid')
        ->and($modal->filter(SupportSelectors::MODAL_HEADING)->text())->toBe($heading)
        ->and($modal->filter(SupportSelectors::MODAL_ICON)->text())->toBe('x-mark')
        ->and($modal->filter(SupportSelectors::MODAL_SUBMIT . SupportSelectors::color('danger'))->text())->toBe('Detach');
})->with([
    "titled by the table's title attribute" => ['name', 'Detach Rock'],
    'titled by nothing but the record label' => [null, 'Detach plant'],
]);

it("detaches the row's plant from the garden in one flush, keeping the plant, and toasts \"Detached\"", function (): void {
    $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->kew, 'plants'))
        ->callAction(TestAction::make('detach')->table($this->rock))
        ->assertNotified('Detached');

    expect(array_count_values($this->statementsRun())['"COMMIT"'] ?? 0)->toBe(1)
        ->and(($this->plantsOf)($this->kew))->toBe(['Moss'])
        ->and(($this->plantsOf)($this->wisley))->toBe(['Rock'])
        ->and($this->reload($this->rock)->name)->toBe('Rock');
});

it('detaches a plant from the side Doctrine persists when the table lists the inverse side', function (): void {
    $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->rock, 'gardens'))
        ->callAction(TestAction::make('detach')->table($this->kew))
        ->assertNotified('Detached');

    expect(($this->plantsOf)($this->kew))->toBe(['Moss'])
        ->and(($this->plantsOf)($this->wisley))->toBe(['Rock']);
});

it('refuses a table that declares no relationship', function (): void {
    expect(fn (): Testable => $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->kew, 'plants', variants: ['queryOnly']))
        ->callAction(TestAction::make('detach')->table($this->rock)))
        ->toThrow(LogicException::class, 'The action [detach] detaches a record from a relationship, which only a table declaring relationship() can supply.')
        ->and(($this->plantsOf)($this->kew))->toEqualCanonicalizing(['Moss', 'Rock']);
});

it('detaches a composite-keyed row, and only that row', function (Closure $scene, string $relation, string $titleAttribute): void {
    [$owner, $gone, $kept] = $scene();
    $this->seed($owner, $gone, ...$kept);
    $key = fn (object $record): string => RecordKey::of($this->entityManager(), $record);

    $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $owner, $relation, $titleAttribute))
        ->callAction(TestAction::make('detach')->table($gone))
        ->assertNotified('Detached');

    expect(array_map($key, $this->reload($owner)->{$relation}->toArray()))->toEqualCanonicalizing(array_map($key, $kept));
})->with([
    'a target keyed by scalar fields' => [static function (): array {
        $manifest = new LineManifest();
        $manifest->addOrderLine($gone = new OrderLine(order: 7, line: 2));
        $manifest->addOrderLine($sameOrder = new OrderLine(order: 7, line: 3));
        $manifest->addOrderLine($sameLine = new OrderLine(order: 8, line: 2));

        return [$manifest, $gone, [$sameOrder, $sameLine]];
    }, 'orderLines', 'note'],
    'a target keyed by an association plus a scalar' => [static function (): array {
        $manifest = new LineManifest();
        $manifest->addInvoiceLine($gone = new InvoiceLine($seven = new Invoice(7), line: 2));
        $manifest->addInvoiceLine($sameInvoice = new InvoiceLine($seven, line: 3));
        $manifest->addInvoiceLine($sameLine = new InvoiceLine(new Invoice(8), line: 2));

        return [$manifest, $gone, [$sameInvoice, $sameLine]];
    }, 'invoiceLines', 'note'],
    'an owner keyed by scalar fields' => [static function (): array {
        $terrace = new Terrace(estate: 4, number: 1);
        $terrace->addPlant($gone = new Plant('Gone'));
        $terrace->addPlant($kept = new Plant('Kept'));

        return [$terrace, $gone, [$kept]];
    }, 'plants', 'name'],
    'an owner keyed by an association plus a scalar' => [static function (): array {
        $allotment = new Allotment(new Garden('Kew'), number: 1);
        $allotment->addPlant($gone = new Plant('Gone'));
        $allotment->addPlant($kept = new Plant('Kept'));

        return [$allotment, $gone, [$kept]];
    }, 'plants', 'name'],
]);

it("refuses a one-to-many the mapping forbids dissociating with the association reader's message", function (): void {
    $row = new GardenRow();
    $row->garden = $this->reload($this->kew);
    $this->seed($row);

    expect(fn (): Testable => $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $this->kew, 'rows', null))
        ->callAction(TestAction::make('detach')->table($row)))
        ->toThrow(LogicException::class, 'is mapped with a `nullable: false` join column, so a [' . GardenRow::class . '] cannot be dissociated from its [' . Garden::class . ']; make the column nullable or use DeleteAction.')
        ->and($this->reload($row)->garden?->id)->toBe($this->kew->id);
});
