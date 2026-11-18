<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\ColumnManagerProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Sylament\Tests\Fixtures\Tables\UnpersistedColumnManagerProductTable;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;

describe('mounting', function (): void {
    it('shows every column but the ones hidden by default on mount, the manager ticking each shown one and disabling the one nobody may toggle', function (): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class)->render()->crawler();

        expect($crawler->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name'])
            ->and($crawler->filter(Selectors::cell('1', 'code')))->toHaveCount(0)
            ->and($crawler)->toContainElement(Selectors::cell('1', 'name'), 'Lamp')
            ->and($crawler)->toContainElement(Selectors::columnManagerCheckbox('id') . '[checked][disabled]')
            ->and($crawler)->toContainElement(Selectors::columnManagerCheckbox('name') . '[checked]:not([disabled])')
            ->and($crawler)->toContainElement(Selectors::columnManagerCheckbox('code') . ':not([checked]):not([disabled])');
    });

    it('mounts from the columns the session holds, re-derived against the definition, and from the definition when the session holds no usable state, whether or not the table persists them', function (string $host, array $state, array $headers): void {
        $this->seed(new Product('Lamp'));
        $url = TablePageController::url('Tables:' . new ReflectionClass($host)->getShortName());
        $client = $this::getContainer()->get('test.client');

        $this->visit($url, client: $client);
        $session = $client->getRequest()->getSession();
        $session->set('sylament.tables.' . md5($host) . '_columns', $state);
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        $page = $this->visit($url, client: $client);

        expect($page->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe($headers);
    })->with([
        'a usable state' => [ColumnManagerProductTable::class, [
            ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
            ['type' => 'column', 'name' => 'name', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
        ], ['ID', 'Code']],
        'a state the definition has moved on from' => [ColumnManagerProductTable::class, [
            ['type' => 'group', 'name' => 'details', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
            ['type' => 'column', 'name' => 'id', 'isToggled' => false, 'isToggledHiddenByDefault' => null],
            ['type' => 'column', 'name' => 'gone', 'isToggled' => true, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'name', 'isToggled' => 'no', 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
        ], ['ID', 'Name', 'Code']],
        'an item without a name' => [ColumnManagerProductTable::class, [
            ['type' => 'column', 'isToggled' => true],
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
        ], ['ID', 'Name']],
        'a usable state the table does not persist' => [UnpersistedColumnManagerProductTable::class, [
            ['type' => 'column', 'name' => 'name', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
        ], ['ID']],
    ]);

    it('keeps both bags empty and lists no columns over a table with nothing to toggle or reorder, even with the manager turned on', function (): void {
        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['plain', 'managerOn']]);
        $crawler = $tester->render()->crawler();

        expect($crawler)->toContainElement(Selectors::COLUMN_MANAGER_TRIGGER)
            ->and($crawler)->toContainElement(Selectors::COLUMN_MANAGER)
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL))->toHaveCount(0)
            ->and($tester->component()->tableColumns)->toBe([])
            ->and($tester->component()->tableDeferredColumns)->toBe([]);
    });

    it('refuses to mount reorderable columns one of which has a blank label, naming it', function (): void {
        expect(fn () => $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['reorderable', 'blankLabel']])->render())
            ->toThrow(LogicException::class, 'The table column [name] has a blank label. All columns must have labels when they are reorderable.');
    });
});

describe('the draft', function (): void {
    it("lists the draft's columns in its order, each ticked as it says, while the table keeps the applied columns until Apply", function (): void {
        $this->seed(new Product('Lamp'));

        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['reorderable']])->set('tableDeferredColumns', [
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
            ['type' => 'column', 'name' => 'name', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
        ]);
        $drafted = $tester->render()->crawler();
        $applied = $tester->call('applyTableColumnManager')->render()->crawler();

        expect($drafted->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['Code', 'Name', 'ID'])
            ->and($drafted)->toContainElement(Selectors::columnManagerCheckbox('code') . '[checked]')
            ->and($drafted)->toContainElement(Selectors::columnManagerCheckbox('name') . ':not([checked])')
            ->and($drafted->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name'])
            ->and($applied->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Code', 'ID'])
            ->and($applied->filter(Selectors::cell('1', 'name')))->toHaveCount(0)
            ->and($applied)->toContainElement(Selectors::cell('1', 'code'), 'LAMP');
    });

    it('keeps a column nobody may toggle shown, with its own label and its checkbox disabled, whatever a forged draft claims for it', function (): void {
        $this->seed(new Product('Lamp'));

        $tester = $this->createLiveComponent(ColumnManagerProductTable::class)->set('tableDeferredColumns', [
            ['type' => 'column', 'name' => 'id', 'label' => 'Forged', 'isHidden' => false, 'isToggleable' => true, 'isToggled' => false, 'isToggledHiddenByDefault' => false],
        ]);
        $drafted = $tester->render()->crawler();
        $applied = $tester->call('applyTableColumnManager')->render()->crawler();

        expect($drafted->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['ID', 'Name', 'Code'])
            ->and($drafted)->toContainElement(Selectors::columnManagerCheckbox('id') . '[checked][disabled]')
            ->and($applied->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name'])
            ->and($tester->component()->tableColumns[0])->toBe(['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null]);
    });

    it('re-derives the columns against the definition on the next request: a column it dropped leaves, one it added arrives at its declared place, and one that became toggleable starts as it declares', function (): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class)
            ->call('applyTableColumnManager', ['state' => [
                ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
                ['type' => 'column', 'name' => 'name', 'isToggled' => true, 'isToggledHiddenByDefault' => false],
                ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
            ]])
            ->set('variants', ['noteAdded', 'codeDropped', 'idToggleable'])
            ->render()
            ->crawler();

        expect($crawler->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Name', 'Note'])
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['ID', 'Name', 'Note'])
            ->and($crawler)->toContainElement(Selectors::columnManagerCheckbox('id') . ':not([checked]):not([disabled])');
    });

    it('keeps a draft the user has not applied through a request that does not touch it, until a reset replaces it', function (): void {
        $tester = $this->createLiveComponent(ColumnManagerProductTable::class)
            ->set('tableDeferredColumns', [
                ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
                ['type' => 'column', 'name' => 'name', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
                ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
            ])
            ->refresh();
        $kept = $tester->render()->crawler();
        $replaced = $tester->call('resetTableColumnManager')->render()->crawler();

        expect($kept)->toContainElement(Selectors::columnManagerCheckbox('name') . ':not([checked])')
            ->and($kept)->toContainElement(Selectors::columnManagerCheckbox('code') . '[checked]')
            ->and($replaced)->toContainElement(Selectors::columnManagerCheckbox('name') . '[checked]')
            ->and($replaced)->toContainElement(Selectors::columnManagerCheckbox('code') . ':not([checked])');
    });
});

describe('applying and resetting', function (): void {
    it('applies a state it is handed, the default state for a malformed one, and resets to the declared toggles and order', function (): void {
        $this->seed(new Product('Lamp'));
        $headers = static fn (Crawler $crawler): array => $crawler->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text());
        $reordered = [
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
            ['type' => 'column', 'name' => 'name', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
        ];

        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['reorderable']]);
        $applied = $headers($tester->call('applyTableColumnManager', ['state' => $reordered])->render()->crawler());
        $malformed = $headers($tester->call('applyTableColumnManager', ['state' => ['code' => ['type' => 'column', 'name' => 'code', 'isToggled' => true]]])->render()->crawler());
        $tester->call('applyTableColumnManager', ['state' => $reordered]);
        $reset = $tester->call('resetTableColumnManager')->render()->crawler();

        expect($applied)->toBe(['Code', 'ID'])
            ->and($malformed)->toBe(['ID', 'Name'])
            ->and($headers($reset))->toBe(['ID', 'Name'])
            ->and($reset->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['ID', 'Name', 'Code']);
    });

    it('reads a second apply within one request afresh, the visible columns following the state it applied', function (): void {
        $host = new ColumnManagerProductTable($this->entityManager());
        $state = static fn (bool $name): array => [
            ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
            ['type' => 'column', 'name' => 'name', 'isToggled' => $name, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
        ];

        $host->applyTableColumnManager($state(false));
        $hidden = array_keys($host->getTable()->getVisibleColumns());
        $host->applyTableColumnManager($state(true));

        expect($hidden)->toBe(['id', 'code'])
            ->and(array_keys($host->getTable()->getVisibleColumns()))->toBe(['id', 'name', 'code'])
            ->and($host->isTableColumnToggledHidden('name'))->toBeFalse();
    });

    it('toggles every column it can on or off at once, leaving the one nobody may toggle shown', function (): void {
        $this->seed(new Product('Lamp'));

        $tester = $this->createLiveComponent(ColumnManagerProductTable::class);
        $shown = $tester->toggleAllTableColumns()->render()->crawler();
        $hidden = $tester->toggleAllTableColumns(false)->render()->crawler();

        expect($shown->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name', 'Code'])
            ->and($hidden->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID']);
    });
});

describe('the order', function (): void {
    it('renders the applied order only while the columns are reorderable, keeping it in the session while they are not', function (): void {
        $this->seed(new Product('Lamp'));
        $client = $this::getContainer()->get('test.client');

        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['reorderable']], client: $client)->call('applyTableColumnManager', ['state' => [
            ['type' => 'column', 'name' => 'code', 'isToggled' => true, 'isToggledHiddenByDefault' => true],
            ['type' => 'column', 'name' => 'name', 'isToggled' => true, 'isToggledHiddenByDefault' => false],
            ['type' => 'column', 'name' => 'id', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
        ]]);
        $reordered = $tester->render()->crawler();
        $declared = $tester->set('variants', [])->render()->crawler();

        expect($reordered->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Code', 'Name', 'ID'])
            ->and($declared->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name', 'Code'])
            ->and(array_column($client->getRequest()->getSession()->get('sylament.tables.' . md5(ColumnManagerProductTable::class) . '_columns'), 'name'))->toBe(['code', 'name', 'id']);
    });

    it('reads an order dragged back to the declared one as no reorder at all, so a column declared later arrives at its declared place', function (): void {
        $this->seed(new Product('Lamp'));
        $state = static fn (string ...$names): array => array_map(static fn (string $name): array => ['type' => 'column', 'name' => $name, 'isToggled' => true, 'isToggledHiddenByDefault' => $name === 'id' ? null : $name === 'code'], $names);

        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['reorderable']])
            ->call('applyTableColumnManager', ['state' => $state('code', 'id', 'name')])
            ->call('applyTableColumnManager', ['state' => $state('id', 'name', 'code')])
            ->set('variants', ['reorderable', 'noteAdded'])
            ->render()
            ->crawler();

        expect($crawler->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['ID', 'Name', 'Note', 'Code']);
    });
});

describe('the session', function (): void {
    it('remembers the applied columns on the next page load only when the table persists them', function (string $host, array $headers): void {
        $this->seed(new Product('Lamp'));
        $client = $this::getContainer()->get('test.client');

        $this->createLiveComponent($host, client: $client)->toggleAllTableColumns(false);

        $page = $this->visit(TablePageController::url('Tables:' . new ReflectionClass($host)->getShortName()), client: $client);

        expect($page->filter('thead th')->each(static fn (Crawler $cell): string => $cell->text()))->toBe($headers);
    })->with([
        'persisted' => [ColumnManagerProductTable::class, ['ID']],
        'not persisted' => [UnpersistedColumnManagerProductTable::class, ['ID', 'Name']],
    ]);

    it('writes the columns to the session on the mount that seeds them and on each apply or reset, never on a request that changes nothing', function (string $action): void {
        $key = 'sylament.tables.' . md5(ColumnManagerProductTable::class) . '_columns';
        $visitor = $this::getContainer()->get('test.client');
        $user = $this::getContainer()->get('test.client');

        $this->visit(TablePageController::url('Tables:ColumnManagerProductTable'), client: $visitor);
        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, client: $user)->refresh();
        $refreshed = $user->getRequest()->getSession()->has($key);
        $tester->call($action);

        expect($visitor->getRequest()->getSession()->has($key))->toBeTrue()
            ->and($refreshed)->toBeFalse()
            ->and($user->getRequest()->getSession()->has($key))->toBeTrue();
    })->with([
        'apply' => ['applyTableColumnManager'],
        'reset' => ['resetTableColumnManager'],
    ]);
});
