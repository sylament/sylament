<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\ShelvedPreserve;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\LockableReorderLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SpotReorderablePreserveTable;

describe('the reorder mode', function (): void {
    it('lists every row on one page while reordering, and pages again once done', function (): void {
        $this->seed(new LarderJar('Apple', position: 1), new LarderJar('Banana', position: 2), new LarderJar('Cherry', position: 3));

        $tester = $this->createLiveComponent(LockableReorderLarderJarTable::class)->call('toggleTableReordering');

        expect($tester->render()->crawler()->filter(Selectors::ROW))->toHaveCount(3)
            ->and($tester->call('toggleTableReordering')->render()->crawler()->filter(Selectors::ROW))->toHaveCount(2);
    });

    it('keeps its pages while reordering when the table pages while reordering', function (): void {
        $this->seed(new LarderJar('Apple', position: 1), new LarderJar('Banana', position: 2), new LarderJar('Cherry', position: 3));

        $reordering = $this->createLiveComponent(LockableReorderLarderJarTable::class, ['variants' => ['paginatedWhileReordering']])
            ->call('toggleTableReordering')
            ->render()
            ->crawler();

        expect($reordering->filter(Selectors::ROW))->toHaveCount(2)
            ->and($reordering)->toContainElement('tbody' . SupportSelectors::SORTABLE_CONTROLLER);
    });

    it('ignores an order and drops a standing mode once the table stops being reorderable', function (): void {
        $this->seed($first = new LarderJar('Apple', position: 1), $second = new LarderJar('Banana', position: 2));

        $page = $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->call('toggleTableReordering')
            ->set('locked', true)
            ->call('reorderTable', ['order' => [(string) $second->id, (string) $first->id]])
            ->render()
            ->crawler();

        expect($page)->toContainElement(Selectors::sortTrigger('label'))
            ->and($page->filter('tbody' . SupportSelectors::SORTABLE_CONTROLLER))->toHaveCount(0)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($this->reload($first)->position)->toBe(1)
            ->and($this->reload($second)->position)->toBe(2);
    });
});

describe('reorderTable()', function (): void {
    it('numbers the records by their place in the order between its two hooks, the reorder mode off, and lists them in it', function (): void {
        $this->seed($apple = new LarderJar('Apple', position: 1), $banana = new LarderJar('Banana', position: 2), $cherry = new LarderJar('Cherry', position: 3));
        $order = [(string) $cherry->id, (string) $apple->id, (string) $banana->id];

        $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->call('reorderTable', ['order' => $order])
            ->assertCanSeeTableRecords([$cherry, $apple], inOrder: true);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['before ' . implode(',', $order), 'after ' . implode(',', $order)])
            ->and($this->reload($cherry)->position)->toBe(1)
            ->and($this->reload($apple)->position)->toBe(2)
            ->and($this->reload($banana)->position)->toBe(3);
    });

    it('numbers a descending table from the end of the order', function (): void {
        $this->seed($apple = new LarderJar('Apple', position: 1), $banana = new LarderJar('Banana', position: 2), $cherry = new LarderJar('Cherry', position: 3));

        $this->createLiveComponent(LockableReorderLarderJarTable::class, ['variants' => ['desc']])
            ->call('reorderTable', ['order' => [(string) $cherry->id, (string) $apple->id, (string) $banana->id]]);

        expect($this->reload($banana)->position)->toBe(1)
            ->and($this->reload($apple)->position)->toBe(2)
            ->and($this->reload($cherry)->position)->toBe(3);
    });

    it('writes the order as one transaction of an update per record', function (): void {
        $this->seed($apple = new LarderJar('Apple', position: 1), $banana = new LarderJar('Banana', position: 2), $cherry = new LarderJar('Cherry', position: 3));

        $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->call('reorderTable', ['order' => [(string) $cherry->id, (string) $apple->id, (string) $banana->id]]);

        $statements = $this->statementsRun();
        $start = array_search('"START TRANSACTION"', $statements, true);
        $commit = array_search('"COMMIT"', $statements, true);

        expect($start)->toBeInt()
            ->and($commit)->toBeInt()
            ->and(array_map(static fn (string $sql): string => mb_substr($sql, 0, 17), array_slice($statements, (int) $start + 1, (int) $commit - (int) $start - 1)))
            ->toBe(array_fill(0, 3, 'UPDATE LarderJar '));
    });

    it('leaves a gap for a key the query does not answer for, and none for a value that is no key', function (): void {
        $this->seed($apple = new LarderJar('Apple'), $banana = new LarderJar('Banana'), $spare = new LarderJar('Spare', position: 9));

        $this->createLiveComponent(LockableReorderLarderJarTable::class, ['variants' => ['scoped']])
            ->call('reorderTable', ['order' => [(string) $apple->id, '99999', (string) $spare->id, (string) $banana->id]]);

        expect($this->reload($apple)->position)->toBe(1)
            ->and($this->reload($spare)->position)->toBe(9)
            ->and($this->reload($banana)->position)->toBe(4);

        $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->call('reorderTable', ['order' => [(string) $banana->id, ['smuggled'], (string) $apple->id]]);

        expect($this->reload($banana)->position)->toBe(1)
            ->and($this->reload($apple)->position)->toBe(2);
    });

    it('resolves an order too wide for one statement one chunk at a time', function (): void {
        $this->seed($apple = new LarderJar('Apple'), $banana = new LarderJar('Banana'));

        $this->createLiveComponent(LockableReorderLarderJarTable::class)
            ->call('reorderTable', ['order' => [(string) $apple->id, ...array_map(strval(...), range(100_001, 101_999)), (string) $banana->id]]);

        expect(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, ' IN (')))->toHaveCount(2)
            ->and($this->reload($apple)->position)->toBe(1)
            ->and($this->reload($banana)->position)->toBe(2001);
    });

    it('writes a position its embeddable holds', function (): void {
        $this->seed($apricot = new ShelvedPreserve('Apricot', position: 1), $blackcurrant = new ShelvedPreserve('Blackcurrant', position: 2));

        $this->createLiveComponent(SpotReorderablePreserveTable::class)
            ->call('reorderTable', ['order' => [(string) $blackcurrant->id, (string) $apricot->id]]);

        expect($this->reload($blackcurrant)->spot->position)->toBe(1)
            ->and($this->reload($apricot)->spot->position)->toBe(2);
    });

    it('refuses to reorder records the table does not query', function (): void {
        expect(fn () => $this->createLiveComponent(LockableReorderLarderJarTable::class, ['variants' => ['arrayRecords']])->call('reorderTable', ['order' => ['plum']]))
            ->toThrow(LogicException::class, 'Table [' . LockableReorderLarderJarTable::class . '] reorders records it does not query; a position column is written through the [query()] and [relationship()] lanes only.');
    });
});
