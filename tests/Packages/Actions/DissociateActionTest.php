<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\GardenPlot;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AssociableRelationTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function (): void {
    $this->kew = new Garden('Kew');
    $this->bramble = new Seedling('Bramble');
    $this->bramble->garden = $this->kew;

    $this->thistle = new Seedling('Thistle');
    $this->thistle->garden = $this->kew;
    $this->seed($this->kew, $this->bramble, $this->thistle);

    $this->table = fn (?string $titleAttribute = 'name', array $variants = []): Testable => $this->createLiveComponent(
        AssociableRelationTable::class,
        AssociableRelationTable::props($this->entityManager(), $this->kew, 'seedlings', $titleAttribute, $variants),
    );
});

it('puts a danger "Dissociate" with the x-mark on each row, confirmed in a modal headed by the record title, with the x-mark and a "Dissociate" submit', function (?string $titleAttribute, string $heading): void {
    $tester = ($this->table)($titleAttribute);
    $trigger = $tester->render()->crawler()->filter(Selectors::trigger('dissociate') . SupportSelectors::color('danger') . ':not(' . TablesSelectors::BULK_ONLY . ')');
    $modal = $tester->mountAction(TestAction::make('dissociate')->table($this->bramble))->render()->crawler();

    expect($trigger->each(static fn (Crawler $button): string => $button->filter('.sy-link-label')->text()))->toBe(['Dissociate', 'Dissociate'])
        ->and($trigger->first()->filter(SupportSelectors::ICON)->text())->toBe('x-mark-20-solid')
        ->and($modal->filter(Selectors::MODAL_HEADING)->text())->toBe($heading)
        ->and($modal->filter(Selectors::MODAL_DESCRIPTION)->text())->toBe('Are you sure you would like to do this?')
        ->and($modal->filter(Selectors::MODAL_ICON)->text())->toBe('x-mark')
        ->and($modal->filter(Selectors::MODAL_SUBMIT . SupportSelectors::color('danger'))->text())->toBe('Dissociate');
})->with([
    "titled by the table's title attribute" => ['name', 'Dissociate Bramble'],
    'titled by nothing but the record label' => [null, 'Dissociate seedling'],
]);

it("nulls the row's seedling's garden in one flush, keeping the seedling, toasts \"Dissociated\" and drops the row", function (): void {
    $tester = ($this->table)()
        ->callAction(TestAction::make('dissociate')->table($this->bramble))
        ->assertNotified('Dissociated')
        ->assertActionNotMounted();
    $commits = array_count_values($this->statementsRun())['"COMMIT"'] ?? 0;

    expect($commits)->toBe(1)
        ->and($tester->render()->crawler()->filter(TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Thistle'])
        ->and($this->reload($this->bramble)->garden)->toBeNull()
        ->and($this->reload($this->bramble)->name)->toBe('Bramble')
        ->and($this->reload($this->thistle)->garden?->id)->toBe($this->kew->id);
});

it('nulls a foreign key onto a composite-keyed owner whole, and only on the row', function (Closure $scene, string $property): void {
    [$owner, $gone, $kept] = $scene();
    $this->seed($owner, $gone, $kept);

    $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $owner, 'seedlings'))
        ->callAction(TestAction::make('dissociate')->table($gone))
        ->assertNotified('Dissociated');

    expect($this->reload($gone)->{$property})->toBeNull()
        ->and(array_map(static fn (Seedling $seedling): string => $seedling->name, $this->reload($owner)->seedlings->toArray()))->toBe(['Kept']);
})->with([
    'an owner keyed by scalar fields' => [static function (): array {
        $terrace = new Terrace(estate: 4, number: 1);
        [$gone, $kept] = [new Seedling('Gone'), new Seedling('Kept')];
        $gone->terrace = $terrace;
        $kept->terrace = $terrace;

        return [$terrace, $gone, $kept];
    }, 'terrace'],
    'an owner keyed by an association plus a scalar' => [static function (): array {
        $allotment = new Allotment(new Garden('Wisley'), number: 1);
        [$gone, $kept] = [new Seedling('Gone'), new Seedling('Kept')];
        $gone->allotment = $allotment;
        $kept->allotment = $allotment;

        return [$allotment, $gone, $kept];
    }, 'allotment'],
]);

it("refuses a one-to-many the mapping forbids dissociating with the association reader's message", function (): void {
    $plot = new GardenPlot();
    $plot->garden = $this->reload($this->kew);
    $this->seed($plot);

    expect(fn (): Testable => $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $this->kew, 'plots', null))
        ->callAction(TestAction::make('dissociate')->table($plot)))
        ->toThrow(LogicException::class, sprintf('[%s::$plots] declares orphanRemoval, so a record removed from it is deleted rather than dissociated', Garden::class))
        ->and($this->reload($plot)->garden?->id)->toBe($this->kew->id);
});

it('refuses a table that declares no relationship', function (): void {
    expect(fn (): Testable => ($this->table)(variants: ['queryOnly'])->callAction(TestAction::make('dissociate')->table($this->bramble)))
        ->toThrow(LogicException::class, 'The action [dissociate] dissociates a record through a relationship, which only a table declaring relationship() can supply.')
        ->and($this->reload($this->bramble)->garden?->id)->toBe($this->kew->id);
});
