<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Tables\Columns\IconColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Twig\TablesIconAlias;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Forms\ColoredLevel;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

describe('boolean mode', function (): void {
    it('draws a success check for a true item, a danger cross for a false one and nothing for a null one, each named Yes or No', function (): void {
        $cell = $this->renderViewComponent(IconColumn::make('stocked')->record(['stocked' => [true, false, null]])->boolean());
        $unwired = $this->renderViewComponent(new IconColumn()->name('stocked')->record(['stocked' => true])->boolean());

        expect($cell->filter(Selectors::ICON_COLUMN . ' svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['check-circle', 'x-circle'])
            ->and($cell->filter('svg')->eq(0)->matches(SupportSelectors::color('success')))->toBeTrue()
            ->and($cell->filter('svg')->eq(1)->matches(SupportSelectors::color('danger')))->toBeTrue()
            ->and($cell->filter('.sy-ta-sr-only')->each(static fn (Crawler $name): string => $name->text()))->toBe(['Yes', 'No'])
            ->and($unwired->filter('svg')->text())->toBe('check-circle');
    });

    it('turns boolean over a Doctrine boolean field on its own, for every row, unless told not to, and never over another field, on the records lane or outside a table', function (): void {
        $host = new class implements InteractsWithTable {
            use HasTable;
        };
        $query = Table::make($host)->query(fn (): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('sample')->from(ColumnKindSample::class, 'sample'));
        $records = Table::make($host)->records(fn (): array => []);
        $icons = fn (IconColumn $column, object|array $record): array => $this->renderViewComponent($column->record($record))
            ->filter('svg')->each(static fn (Crawler $icon): string => $icon->text());
        $flagged = IconColumn::make('flagged')->table($query);

        expect($icons($flagged, new ColumnKindSample(flagged: true)))->toBe(['check-circle'])
            ->and($icons($flagged, new ColumnKindSample(flagged: false)))->toBe(['x-circle'])
            ->and($icons(IconColumn::make('flagged')->table($query)->boolean(false), new ColumnKindSample(flagged: true)))->toBe([])
            ->and($icons(IconColumn::make('body')->table($query), new ColumnKindSample(body: '1')))->toBe([])
            ->and($icons(IconColumn::make('flagged')->table($records), ['flagged' => true]))->toBe([])
            ->and($icons(IconColumn::make('flagged'), ['flagged' => true]))->toBe([]);
    });

    it('swaps the icon and colour of each arm, each setter alone turning the column boolean, and draws nothing for an arm given false', function (): void {
        $icons = fn (IconColumn $column): Crawler => $this->renderViewComponent($column->record(['stocked' => [true, false]]))->filter('svg');

        expect($icons(IconColumn::make('stocked')->trueIcon('support:star'))->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['Star', 'x-circle'])
            ->and($icons(IconColumn::make('stocked')->falseColor('warning'))->eq(1)->matches(SupportSelectors::color('warning')))->toBeTrue()
            ->and($icons(IconColumn::make('stocked')->true())->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['check-circle', 'x-circle'])
            ->and($icons(IconColumn::make('stocked')->true('support:heart', 'info')->false('support:star', 'gray'))->each(static fn (Crawler $icon): array => [$icon->text(), $icon->matches(SupportSelectors::color('info')), $icon->matches(SupportSelectors::GRAY)]))
            ->toBe([['Heart', true, false], ['Star', false, true]])
            ->and($this->renderViewComponent(IconColumn::make('stocked')->record(['stocked' => [true, false]])->falseIcon(false))->filter('.sy-ta-sr-only')->each(static fn (Crawler $name): string => $name->text()))
            ->toBe(['Yes']);
    });

    it('draws the icon an application registers for the true arm, and an icon given to the column over it', function (): void {
        $this::getContainer()->get(IconManager::class)->register([TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE => 'support:heart']);
        $icons = fn (IconColumn $column): array => $this->renderViewComponent($column->record(['stocked' => [true, false]]))->filter('svg')->each(static fn (Crawler $icon): string => $icon->text());

        expect($icons(IconColumn::make('stocked')->boolean()))->toBe(['Heart', 'x-circle'])
            ->and($icons(IconColumn::make('stocked')->trueIcon('support:star')))->toBe(['Star', 'x-circle']);
    });

    it('lets its own icon and colour win over the boolean arms, falling through to them where they answer nothing', function (): void {
        $icons = fn (IconColumn $column): array => $this->renderViewComponent($column->record(['stocked' => [true, false]])->boolean())
            ->filter('svg')->each(static fn (Crawler $icon): array => [$icon->text(), $icon->matches(SupportSelectors::color('info'))]);

        expect($icons(IconColumn::make('stocked')->icon('support:star')->color('info')))->toBe([['Star', true], ['Star', true]])
            ->and($icons(IconColumn::make('stocked')->icon(fn (bool $state): ?string => $state ? 'support:heart' : null)->color(fn (bool $state): ?string => $state ? null : 'info')))
            ->toBe([['Heart', false], ['x-circle', true]]);
    });
});

describe('its icons', function (): void {
    it("draws one icon per value of a list or an iterable, or the state's own icon, nothing for a value no icon answers, and names only what it draws", function (): void {
        $cell = fn (mixed $state, IconColumn $column): Crawler => $this->renderViewComponent($column->record(['trees' => $state]));
        $mapped = $cell(['oak', 'ash', 'elm', 'fir', 'yew'], IconColumn::make('trees')->icons(['support:star' => 'oak', 'support:heart' => 'ash', 'heroicons:check-circle' => 'elm']));
        $levels = $cell([ColoredLevel::Low, ColoredLevel::Unrated], IconColumn::make('trees'));

        expect($cell('oak', IconColumn::make('trees')->icon('support:star'))->filter('svg'))->toHaveCount(1)
            ->and($cell(new ArrayIterator(['oak', 'ash']), IconColumn::make('trees')->icon('support:star'))->filter('svg'))->toHaveCount(2)
            ->and($mapped->filter('svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['Star', 'Heart', 'check-circle'])
            ->and($mapped->filter('.sy-ta-sr-only')->each(static fn (Crawler $name): string => $name->text()))->toBe(['oak', 'ash', 'elm'])
            ->and($mapped->filter(Selectors::PLACEHOLDER))->toHaveCount(0)
            ->and($levels->filter('svg')->text())->toBe('Heart')
            ->and($levels->filter('svg')->matches(SupportSelectors::color('success')))->toBeTrue()
            ->and($levels->filter('svg'))->toHaveCount(1);
    });

    it('draws its icons large unless given a size, base or a blank size meaning large too', function (IconSize|string|null $size, string $drawn): void {
        $column = IconColumn::make('tree')->record(['tree' => 'oak'])->icon('support:star');

        expect($this->renderViewComponent($size === null ? $column : $column->size($size))->filter('svg')->matches(SupportSelectors::size($drawn)))->toBeTrue();
    })->with([
        'no size' => [null, 'lg'],
        'a size case' => [IconSize::Small, 'sm'],
        'a size name' => ['xs', 'xs'],
        'base' => ['base', 'lg'],
        'a blank size' => ['', 'lg'],
    ]);

    it('hides each icon from assistive technology and names it by its tooltip, flattened and trimmed, else by what its state is', function (Closure $column, mixed $state, string $name): void {
        $cell = $this->renderViewComponent($column()->record(['item' => $state]));

        expect($cell->filter('svg[aria-hidden="true"]'))->toHaveCount(1)
            ->and($cell->filter('.sy-ta-sr-only')->text())->toBe($name);
    })->with([
        'a tooltip' => [fn (): IconColumn => IconColumn::make('item')->icon('support:star')->tooltip(new Markup(' <b>Top</b> shelf ', 'UTF-8')), 'oak', 'Top shelf'],
        'a tooltip on a boolean column' => [fn (): IconColumn => IconColumn::make('item')->boolean()->tooltip('Stocked'), true, 'Stocked'],
        'a state that labels itself' => [fn (): IconColumn => IconColumn::make('item')->icon('support:star'), DeliveryMode::Standard, 'Standard delivery'],
        'a backed enum' => [fn (): IconColumn => IconColumn::make('item')->icon('support:star'), StringBackedLetter::A, 'a'],
        'a markup state' => [fn (): IconColumn => IconColumn::make('item')->icon('support:star'), new Markup(' <i>oak</i> ', 'UTF-8'), 'oak'],
        'a scalar' => [fn (): IconColumn => IconColumn::make('item')->icon('support:star'), ' <b>ash</b> ', 'ash'],
    ]);

    it('leaves an icon unnamed when its state is an object with no name to give', function (): void {
        $cell = $this->renderViewComponent(IconColumn::make('suit')->record(['suit' => PlainSuit::Hearts])->icon('support:star'));

        expect($cell->filter('svg')->text())->toBe('Star')
            ->and($cell->filter('.sy-ta-sr-only'))->toHaveCount(0);
    });

    it('titles each icon with its own tooltip and links each to its own url, named by its text, in a new tab when asked, and leaves a url that ignores the state to the cell', function (): void {
        $linked = $this->renderViewComponent(IconColumn::make('trees')->record(['trees' => ['oak', 'ash']])->icon('support:star')
            ->tooltip(fn (string $state): Markup => new Markup("<b>About</b> {$state}", 'UTF-8'))
            ->url(fn (string $state): string => "/trees/{$state}", shouldOpenInNewTab: true));
        $unlinked = $this->renderViewComponent(IconColumn::make('trees')->record(['trees' => ['oak', 'ash']])->icon('support:star')->url('/trees'));

        expect($linked->filter('a[target="_blank"]')->each(static fn (Crawler $link): array => [$link->attr('href'), $link->filter('svg')->attr('title'), $link->filter('.sy-ta-sr-only')->text()]))
            ->toBe([['/trees/oak', 'About oak', 'About oak'], ['/trees/ash', 'About ash', 'About ash']])
            ->and($unlinked->filter('svg'))->toHaveCount(2)
            ->and($unlinked->filter('a'))->toHaveCount(0);
    });
});

describe('its cell', function (): void {
    it('roots its cell with its alignment, its inline, line-break and wrapped looks and its extra attributes', function (): void {
        $root = fn (IconColumn $column): Crawler => $this->renderViewComponent($column->record(['trees' => ['oak', 'ash']])->icon('support:star'))->filter(Selectors::ICON_COLUMN);

        expect($root(IconColumn::make('trees')->alignEnd()->inline()->listWithLineBreaks()->wrap()->extraAttributes(['data-shelf' => 'top']))
            ->matches(SupportSelectors::align('end') . SupportSelectors::INLINE . '.sy-ta-icon-has-line-breaks' . SupportSelectors::WRAPPED . '[data-shelf="top"]'))->toBeTrue()
            ->and($root(IconColumn::make('trees'))->matches(':not(.sy-ta-icon-has-line-breaks):not(' . SupportSelectors::WRAPPED . ')'))->toBeTrue();
    });

    it('shows its placeholder for a blank state, titled by its empty tooltip as plain text, and no placeholder without one', function (): void {
        $blank = $this->renderViewComponent(IconColumn::make('trees')->record(['trees' => []])->icon('support:star')
            ->placeholder('No trees')->emptyTooltip(new Markup('<b>Nothing</b> planted', 'UTF-8')));
        $bare = $this->renderViewComponent(IconColumn::make('trees')->record(['trees' => null])->icon('support:star'));

        expect($blank)->toContainElement(Selectors::ICON_COLUMN . '[title="Nothing planted"] ' . Selectors::PLACEHOLDER, 'No trees')
            ->and($blank->filter('svg'))->toHaveCount(0)
            ->and($bare->filter(Selectors::ICON_COLUMN . ':not([title])'))->toHaveCount(1)
            ->and($bare->filter(Selectors::PLACEHOLDER))->toHaveCount(0);
    });
});
