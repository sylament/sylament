<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Tables\ArrangedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\DefaultedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\PassingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\SchemalessJarFilterTable;
use Sylament\Tests\Fixtures\Tables\SealedJarFilter;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\DomCrawler\Crawler;

describe('the filter bags', function (): void {
    it('holds a field write in the draft while deferring, leaving the rows, the page and the chips to the applied filters until they are applied', function (): void {
        $this->seed($unlabelled = new LarderJar(), $apple = new LarderJar('Apple'), $bay = new LarderJar('Bay'), $cob = new LarderJar('Cob'), new LarderJar('Date'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['paged'], 'tablePage' => 2])
            ->set('tableDeferredFilters.labelled.isActive', true)
            ->assertCanSeeTableRecords([$bay, $cob])
            ->assertCanNotSeeTableRecords([$apple]);
        $drafted = $tester->render()->crawler();

        $tester->call('applyTableFilters')
            ->assertCanSeeTableRecords([$apple, $bay])
            ->assertCanNotSeeTableRecords([$unlabelled, $cob]);

        expect($drafted)->toContainElement(SchemasSelectors::boundTo('tableDeferredFilters.labelled.isActive') . '[checked]')
            ->and($drafted->filter(Selectors::FILTER_INDICATOR))->toHaveCount(0)
            ->and($tester->render()->crawler())->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Labelled');
    });

    it('filters on a field write at once while live, from the first page', function (): void {
        $this->seed($unlabelled = new LarderJar(), $apple = new LarderJar('Apple'), $bay = new LarderJar('Bay'), $cob = new LarderJar('Cob'), new LarderJar('Date'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['live', 'paged'], 'tablePage' => 2])
            ->set('tableFilters.labelled.isActive', true)
            ->assertCanSeeTableRecords([$apple, $bay])
            ->assertCanNotSeeTableRecords([$unlabelled, $cob]);

        expect($tester->render()->crawler())->toContainElement('[data-model="tableFilters.labelled.isActive"][checked]')
            ->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Labelled');
    });

    it('mirrors a whole applied bag into the draft while deferring and returns to the first page, leaving the page alone for a bag that changes nothing', function (): void {
        $this->seed($unlabelled = new LarderJar(), $apple = new LarderJar('Apple'), $bay = new LarderJar('Bay'), $cob = new LarderJar('Cob'), new LarderJar('Date'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['paged'], 'tablePage' => 2]);
        $applied = $tester->component()->tableFilters;

        $tester->set('tableFilters', $applied)
            ->assertCanSeeTableRecords([$bay, $cob])
            ->set('tableFilters', [...$applied, 'labelled' => ['isActive' => true]])
            ->assertCanSeeTableRecords([$apple, $bay])
            ->assertCanNotSeeTableRecords([$unlabelled, $cob]);

        expect($tester->render()->crawler())->toContainElement(SchemasSelectors::boundTo('tableDeferredFilters.labelled.isActive') . '[checked]');
    });

    it('lets a field write aimed at the applied bag of a deferring table through on the same page, missing the draft, so the next apply undoes it', function (): void {
        $this->seed($unlabelled = new LarderJar(), $apple = new LarderJar('Apple'), new LarderJar('Bay'), $cob = new LarderJar('Cob'), $date = new LarderJar('Date'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['paged'], 'tablePage' => 2])
            ->set('tableFilters.labelled.isActive', true)
            ->assertCanSeeTableRecords([$cob, $date])
            ->assertCanNotSeeTableRecords([$apple]);

        expect($tester->render()->crawler())->toContainElement(SchemasSelectors::boundTo('tableDeferredFilters.labelled.isActive') . ':not([checked])');

        $tester->call('applyTableFilters')->assertCanSeeTableRecords([$unlabelled, $apple]);
    });

    it('narrows the rows by a filter a whole bag leaves out, with no chip for it', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $unlabelled = new LarderJar(seal: JarSeal::Wax));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class)
            ->set('tableFilters', ['seal' => ['value' => null]])
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$unlabelled]);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR))->toHaveCount(0);
    });

    it('reads a filter slice that is not a map of its fields as no state', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(FilterableLarderJarTable::class);

        $tester->set('tableFilters', [...$tester->component()->tableFilters, 'seal' => 'wax'])
            ->assertCanSeeTableRecords([$plum, $fig]);
    });
});

describe('the filter name', function (): void {
    it('resolves a filter class to the name it gives itself, else to the class itself', function (): void {
        $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable(SealedJarFilter::class);

        expect($tester->render()->crawler())->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Sealed jars')
            ->and($tester->component()->parseTableFilterName(Filter::class))->toBe(Filter::class);
    });
});

describe('the filters form', function (): void {
    it('refuses to build on a host that holds no schemas, naming what it must implement', function (): void {
        expect(fn () => $this->createLiveComponent(SchemalessJarFilterTable::class)->render())
            ->toThrow(LogicException::class, 'Table filters need a schema host: ' . SchemalessJarFilterTable::class . ' must implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
    });
});

describe('mounting', function (): void {
    it("seeds the filters from the page's query string, one named filter dropping the others' defaults, and keeps the defaults for an absent or a malformed one", function (string $query, array $labels): void {
        $this->seed(
            new LarderJar('Plum', seal: JarSeal::Wax),
            new LarderJar('Fig', seal: JarSeal::Screw),
            new LarderJar('Pear', sealedOn: new DateTimeImmutable('2026-01-01'), seal: JarSeal::Wax),
            new LarderJar('Quince', sealedOn: new DateTimeImmutable('2026-01-01'), seal: JarSeal::Screw),
            new LarderJar('Open'),
        );

        $page = $this->visit(TablePageController::url('Tables:QueryStringJarFilterTable') . $query);

        expect($page->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($labels);
    })->with([
        'a filter named' => ['?filters[seal][value]=screw', ['Fig', 'Quince']],
        'a false named as text' => ['?filters[sealedOn][value]=false', ['Plum', 'Fig', 'Open']],
        'a null named as text' => ['?filters[sealedOn][value]=null', ['Plum', 'Fig', 'Pear', 'Quince', 'Open']],
        'no filters' => ['', ['Plum']],
        'a list in place of the filters' => ['?filters[]=abc', ['Plum']],
    ]);
});

describe('removing and resetting', function (): void {
    it('applies what else the panel held when one chip is removed from a deferring table', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw), $unlabelled = new LarderJar(seal: JarSeal::Wax));

        $this->createLiveComponent(FilterableLarderJarTable::class)
            ->filterTable('seal', JarSeal::Wax)
            ->set('tableDeferredFilters.labelled.isActive', true)
            ->assertCanSeeTableRecords([$plum, $unlabelled])
            ->removeTableFilter('seal')
            ->assertCanSeeTableRecords([$plum, $fig])
            ->assertCanNotSeeTableRecords([$unlabelled]);
    });

    it('returns a live table to its first page when a chip is removed or the filters are reset', function (Closure $clear): void {
        $this->seed(new LarderJar(), $apple = new LarderJar('Apple'), $bay = new LarderJar('Bay'), $cob = new LarderJar('Cob'), $date = new LarderJar('Date'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['live', 'paged']])
            ->filterTable('labelled')
            ->call('gotoTablePage', ['page' => 2])
            ->assertCanSeeTableRecords([$cob, $date])
            ->assertCanNotSeeTableRecords([$bay]);

        $clear($tester)
            ->assertCanSeeTableRecords([$apple])
            ->assertCanNotSeeTableRecords([$bay, $cob, $date]);
    })->with([
        'a chip removed' => [static fn (Testable $tester): Testable => $tester->removeTableFilter('labelled')],
        'the filters reset' => [static fn (Testable $tester): Testable => $tester->resetTableFilters()],
    ]);

    it('resets the whole filter when its chip names a field the filter does not have', function (): void {
        $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
            ->filterTable('stocked', ['state' => 'shut', 'note' => 'Top shelf'])
            ->removeTableFilter('stocked', 'shelf');

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['State: open']);
    });

    it('removes every filter whose chips can all be removed, and clears the search terms', function (): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw), $unsealed = new LarderJar('Lid'));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class)
            ->filterTable('labelled')
            ->filterTable('pinned')
            ->searchTable('Plum')
            ->searchTableColumns(['label' => 'Plum'])
            ->assertCanNotSeeTableRecords([$fig])
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$plum, $fig])
            ->assertCanNotSeeTableRecords([$unsealed]);

        expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Pinned']);
    });

    it('refills the declared defaults on a reset, where removing a chip clears its filter', function (): void {
        $tester = $this->createLiveComponent(DefaultedJarFilterTable::class)->removeTableFilter('sealed');
        $removed = $tester->render()->crawler();
        $reset = $tester->resetTableFilters()->render()->crawler();

        expect($removed)->toContainElement(Selectors::filterField('sealed', 'isActive') . ':not([checked])')
            ->and($removed)->toContainElement(Selectors::filterField('seal', 'value') . ' option[value="wax"][selected]')
            ->and($reset)->toContainElement(Selectors::filterField('sealed', 'isActive') . '[checked]');
    });

    it('clears a nullable boolean field to no answer and a checkbox or a toggle to off when its filter is removed, and a field its reset state names to what it names', function (): void {
        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class)
            ->filterTable('flags', ['chosen' => true, 'picked' => true, 'toggled' => true, 'ticked' => true, 'switched' => true, 'kept' => true])
            ->removeTableFilter('flags');

        expect($tester->component()->tableFilters['flags'])->toBe(['chosen' => null, 'picked' => null, 'toggled' => null, 'ticked' => false, 'switched' => false, 'kept' => null]);
    });
});

describe('the query', function (): void {
    it("runs every filter's base query before any filter's own, over one query", function (): void {
        $this->seed(
            $plum = new LarderJar('Plum', seal: JarSeal::Wax),
            $pear = new LarderJar('Pear', sealedOn: new DateTimeImmutable('2026-01-01'), seal: JarSeal::Wax),
            $quince = new LarderJar('Quince', sealedOn: new DateTimeImmutable('2026-01-01'), seal: JarSeal::Screw),
        );

        $this->createLiveComponent(PassingJarFilterTable::class)
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$pear])
            ->filterTable('seal', JarSeal::Wax)
            ->filterTable('withDated')
            ->assertCanSeeTableRecords([$plum, $pear])
            ->assertCanNotSeeTableRecords([$quince]);
    });

    it('resolves the record of a row action inside the filtered rows, save for a filter that opts out of record resolution', function (): void {
        $this->seed(
            $plum = new LarderJar('Plum', seal: JarSeal::Wax, position: 1),
            $fig = new LarderJar('Fig', seal: JarSeal::Screw, position: 1),
            $loose = new LarderJar('Loose', seal: JarSeal::Wax),
        );

        $this->createLiveComponent(PassingJarFilterTable::class)
            ->filterTable('seal', JarSeal::Wax)
            ->filterTable('shelved')
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$fig, $loose])
            ->mountAction(TestAction::make('open')->table($fig))
            ->assertActionNotMounted()
            ->callAction(TestAction::make('open')->table($loose));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['opened Loose']);
    });
});
