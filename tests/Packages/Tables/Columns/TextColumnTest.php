<?php

declare(strict_types=1);

use Doctrine\Common\Collections\ArrayCollection;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\FontFamily;
use Sylament\Support\Enums\FontWeight;
use Sylament\Support\Enums\TextSize;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\NumberedProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

describe('its values', function (): void {
    it('lists a scalar as one item, and an array or a traversable as one item per value, joined into one unless badged or broken onto lines', function (): void {
        $items = fn (TextColumn $column): array => $this->renderViewComponent($column)->filter(Selectors::TEXT_ITEM)->each(static fn (Crawler $item): string => $item->text());

        expect($items(TextColumn::make('tags')->record(['tags' => 'oak'])))->toBe(['oak'])
            ->and($items(TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])))->toBe(['oak, ash'])
            ->and($items(TextColumn::make('tags')->record(['tags' => new ArrayCollection(['oak', 'ash'])])))->toBe(['oak, ash'])
            ->and($items(TextColumn::make('tags')->record(['tags' => new ArrayIterator(['oak', 'ash'])])->listWithLineBreaks()))->toBe(['oak', 'ash'])
            ->and($items(TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->badge()))->toBe(['oak', 'ash']);
    });

    it('hands each value its own state for the colour map while badged or left single by a limit, and the joined text once collapsed', function (): void {
        $column = static fn (): TextColumn => TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->colors(['success' => 'oak']);
        $collapsed = $this->renderViewComponent($column());
        $badged = $this->renderViewComponent($column()->badge());
        $limited = $this->renderViewComponent($column()->limitList(1));

        expect($collapsed)->toContainElement(Selectors::TEXT_ITEM . SupportSelectors::GRAY, 'oak, ash')
            ->and($badged)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('success'), 'oak')
            ->and($badged)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('primary'), 'ash')
            ->and($limited)->toContainElement(Selectors::TEXT_ITEM . SupportSelectors::color('success'), 'oak')
            ->and($limited->filter(Selectors::TEXT_ITEM))->toHaveCount(1);
    });

    it('formats each value before the join, so state() rather than formatStateUsing() sets a different join', function (): void {
        $formatted = TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->formatStateUsing(fn (string $state): string => ucfirst($state));
        $joined = TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->state(fn (array $record): string => implode(' · ', $record['tags']));

        expect($this->renderViewComponent($formatted)->filter(Selectors::TEXT_ITEM)->text())->toBe('Oak, Ash')
            ->and($this->renderViewComponent($joined)->filter(Selectors::TEXT_ITEM)->text())->toBe('oak · ash');
    });

    it('flattens a Markup value into plain text when it joins a collapsed cell, and keeps it raw on a badge', function (): void {
        $column = static fn (): TextColumn => TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])
            ->formatStateUsing(fn (string $state): Markup => new Markup("<b>{$state}</b>", 'UTF-8'));
        $collapsed = $this->renderViewComponent($column());
        $badged = $this->renderViewComponent($column()->badge());

        expect($collapsed->filter(Selectors::TEXT_ITEM)->text())->toBe('<b>oak</b>, <b>ash</b>')
            ->and($collapsed->filter(Selectors::TEXT_ITEM . ' b'))->toHaveCount(0)
            ->and($badged->filter(SupportSelectors::BADGE . ' b')->each(static fn (Crawler $bold): string => $bold->text()))->toBe(['oak', 'ash']);
    });
});

describe('its markup', function (): void {
    it('roots every cell in one element holding a span for a single value, described or not, and a list item per value of a list', function (): void {
        $single = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => 'oak']));
        $described = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => 'oak'])->description('Timber'));
        $listed = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->listWithLineBreaks());
        $bulleted = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => 'oak'])->bulleted());

        expect($single)->toContainElement(Selectors::TEXT_COLUMN . ' span' . Selectors::TEXT_ITEM, 'oak')
            ->and($single->filter(Selectors::TEXT_COLUMN . ' ul'))->toHaveCount(0)
            ->and($described)->toContainElement(Selectors::TEXT_COLUMN . ' span' . Selectors::TEXT_ITEM, 'oak')
            ->and($listed->filter(Selectors::TEXT_COLUMN . ' ul li' . Selectors::TEXT_ITEM)->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak', 'ash'])
            ->and($bulleted)->toContainElement(Selectors::TEXT_COLUMN . ' ul li' . Selectors::TEXT_ITEM, 'oak');
    });

    it('marks its root with the list shape it renders, and spreads its extra attributes there', function (): void {
        $root = fn (TextColumn $column): Crawler => $this->renderViewComponent($column->record(['tags' => ['oak', 'ash', 'elm']]))->filter(Selectors::TEXT_COLUMN);

        expect($root(TextColumn::make('tags')->badge())->matches('.sy-ta-text-has-badges'))->toBeTrue()
            ->and($root(TextColumn::make('tags')->bulleted())->matches(Selectors::BULLETED . '.sy-ta-text-has-line-breaks'))->toBeTrue()
            ->and($root(TextColumn::make('tags')->listWithLineBreaks())->matches('.sy-ta-text-has-line-breaks:not(' . Selectors::BULLETED . ')'))->toBeTrue()
            ->and($root(TextColumn::make('tags')->limitList(2))->matches('.sy-ta-text-list-limited'))->toBeTrue()
            ->and($root(TextColumn::make('tags')->extraAttributes(['data-shelf' => 'top']))->attr('data-shelf'))->toBe('top')
            ->and($root(TextColumn::make('tags'))->matches(Selectors::TEXT_COLUMN . ':not(.sy-ta-text-has-badges):not(' . Selectors::BULLETED . '):not(.sy-ta-text-has-line-breaks):not(.sy-ta-text-list-limited)'))->toBeTrue();
    });

    it('renders each value as a badge, coloured and sized as the column says, with its icon inside it on the side it is given', function (): void {
        $styled = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->badge()->color('success')->size(TextSize::Large)->icon('support:star')->iconPosition('after'));
        $plain = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak']])->badge()->icon('support:star'));

        expect($styled->filter(SupportSelectors::BADGE . SupportSelectors::color('success') . SupportSelectors::size('lg') . ' ' . SupportSelectors::BADGE_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['oak', 'ash'])
            ->and($styled->filter(SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_LABEL_CONTAINER . ' + svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['Star', 'Star'])
            ->and($styled->filter(Selectors::TEXT_ITEM . ' > svg'))->toHaveCount(0)
            ->and($plain)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('primary') . SupportSelectors::size('sm') . ' ' . SupportSelectors::BADGE_LABEL, 'oak')
            ->and($plain->filter(SupportSelectors::BADGE . ' svg + ' . SupportSelectors::BADGE_LABEL_CONTAINER))->toHaveCount(1);
    });

    it('sizes each item small by default, base as medium, and a custom size as a class of its own', function (): void {
        $item = fn (TextColumn $column): Crawler => $this->renderViewComponent($column->record(['name' => 'Lamp']))->filter(Selectors::TEXT_ITEM);

        expect($item(TextColumn::make('name'))->matches(SupportSelectors::size('sm')))->toBeTrue()
            ->and($item(TextColumn::make('name')->size('lg'))->matches(SupportSelectors::size('lg')))->toBeTrue()
            ->and($item(TextColumn::make('name')->size('base'))->matches(SupportSelectors::MEDIUM))->toBeTrue()
            ->and($item(TextColumn::make('name')->size('my-size'))->matches('.my-size' . SupportSelectors::UNSIZED))->toBeTrue();
    });

    it('gives a weight or font family closure the whole column state, once, rather than each value', function (): void {
        $seen = [];
        $weighted = TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->listWithLineBreaks()->weight(function (mixed $state) use (&$seen): FontWeight {
            $seen[] = $state;

            return FontWeight::Bold;
        });
        $families = [];
        $typed = TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->badge()->fontFamily(function (mixed $state) use (&$families): FontFamily {
            $families[] = $state;

            return FontFamily::Mono;
        });

        expect($this->renderViewComponent($weighted)->filter(Selectors::TEXT_ITEM . SupportSelectors::font('bold'))->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak', 'ash'])
            ->and($seen)->toBe([['oak', 'ash']])
            ->and($this->renderViewComponent($typed)->filter(Selectors::TEXT_ITEM . SupportSelectors::font('mono'))->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak', 'ash'])
            ->and($families)->toBe([['oak', 'ash']]);
    });

    it('paints a gray item as a colour of its own, unlike an uncoloured one, and leaves a gray icon uncoloured', function (): void {
        $gray = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])->color('gray')->icon('support:star')->iconColor('gray'));
        $uncoloured = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp']));

        expect($gray->filter(Selectors::TEXT_ITEM . SupportSelectors::COLORED . SupportSelectors::color('gray')))->toHaveCount(1)
            ->and($gray)->toContainElement(Selectors::TEXT_ITEM . ' svg' . SupportSelectors::GRAY, 'Star')
            ->and($uncoloured)->toContainElement(Selectors::TEXT_ITEM . SupportSelectors::GRAY, 'Lamp');
    });

    it('stamps its alignment on its root', function (): void {
        $single = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => 'oak'])->alignment(Alignment::End));

        expect($single)->toContainElement(Selectors::TEXT_COLUMN . SupportSelectors::align('end'), 'oak');
    });

    it('makes the content the copy control on a plain item and the badge itself on a badged one, titled by its tooltip and copying the text the cell shows', function (): void {
        $plain = $this->renderViewComponent(TextColumn::make('price')->record(['price' => 1234.5])->money('USD', locale: 'en_US')->copyable()->tooltip('Price')->icon('support:star'));
        $badged = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak', 'ash']])->badge()->copyable()->tooltip('Tag'));

        expect($plain)->toContainElement(Selectors::TEXT_ITEM . ' span' . SupportSelectors::COPYABLE . '[role="button"][title="Price"]' . SupportSelectors::clipboardValue('text', '$1,234.50'), '$1,234.50')
            ->and($plain->filter(Selectors::TEXT_ITEM . ' > svg')->text())->toBe('Star')
            ->and($plain->filter(SupportSelectors::COPYABLE . ' svg, button'))->toHaveCount(0)
            ->and($badged->filter(SupportSelectors::BADGE . SupportSelectors::COPYABLE . '[role="button"][title="Tag"]')->each(static fn (Crawler $badge): ?string => $badge->attr('data-sylament--support--clipboard-text-value')))->toBe(['oak', 'ash']);
    });
});

describe('its lists', function (): void {
    it('lists its values as bullets or on their own lines, and cuts a limited list to its first values with a count of the rest', function (): void {
        $render = fn (TextColumn $column): Crawler => $this->renderViewComponent($column->record(['tags' => ['oak', 'ash', 'elm']]));
        $items = static fn (Crawler $cell): array => $cell->filter(Selectors::TEXT_ITEM)->each(static fn (Crawler $item): string => $item->text());
        $collapsed = $render(TextColumn::make('tags')->limitList(2));
        $lines = $render(TextColumn::make('tags')->limitList(2)->listWithLineBreaks());

        expect($items($render(TextColumn::make('tags')->bulleted())))->toBe(['oak', 'ash', 'elm'])
            ->and($items($render(TextColumn::make('tags')->listWithLineBreaks())))->toBe(['oak', 'ash', 'elm'])
            ->and($items($collapsed))->toBe(['oak, ash'])
            ->and($collapsed->filter('.sy-ta-text-list-limited-message')->text())->toBe('and 1 more')
            ->and($items($lines))->toBe(['oak', 'ash'])
            ->and($lines->filter('.sy-ta-text-list-limited-message')->text())->toBe('and 1 more');
    });

    it('keeps every value of an expandable line-broken list in the markup, the rest hidden behind toggles a consumer controller cannot unbind', function (): void {
        $cell = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak', 'ash', 'elm']])
            ->listWithLineBreaks()
            ->limitList(1)
            ->expandableLimitedList()
            ->extraAttributes(['data-controller' => 'shelf']));
        $controller = 'sylament--tables--limited-list';

        expect($cell->filter(Selectors::TEXT_ITEM . ':not([hidden])')->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak'])
            ->and($cell->filter(Selectors::TEXT_ITEM . '[hidden][data-over-limit]')->each(static fn (Crawler $item): string => $item->text()))->toBe(['ash', 'elm'])
            ->and($cell->filter(Selectors::TEXT_COLUMN)->attr('data-controller'))->toBe($controller)
            ->and($cell->filter(Selectors::TEXT_COLUMN)->attr("data-{$controller}-expanded-value"))->toBe('false')
            ->and($cell)->toContainElement("span[role=\"button\"]:not([tabindex]):not([hidden])[data-{$controller}-target=\"expandToggle\"][data-action~=\"click->{$controller}#expand\"][data-action~=\"keydown.enter->{$controller}#expand\"][data-action~=\"keydown.space->{$controller}#expand\"]", 'Show 2 more')
            ->and($cell)->toContainElement("span[role=\"button\"]:not([tabindex])[hidden][data-{$controller}-target=\"collapseToggle\"][data-action~=\"click->{$controller}#collapse\"][data-action~=\"keydown.enter->{$controller}#collapse\"][data-action~=\"keydown.space->{$controller}#collapse\"]", 'Show 2 less');
    });

    it('counts the rest without toggles wherever they were cut, an expandable list that keeps its values on one line included', function (): void {
        $cell = $this->renderViewComponent(TextColumn::make('tags')->record(['tags' => ['oak', 'ash', 'elm']])->limitList(1)->expandableLimitedList());

        expect($cell->filter(Selectors::TEXT_ITEM)->each(static fn (Crawler $item): string => $item->text()))->toBe(['oak'])
            ->and($cell->filter('.sy-ta-text-list-limited-message')->text())->toBe('and 2 more')
            ->and($cell->filter('[role="button"], [data-controller]'))->toHaveCount(0);
    });
});

describe('its row index', function (): void {
    it('numbers each row from one or from zero, offset by the pages behind it, and from one on every page while it shows all', function (): void {
        $this->seed($alpha = new Product('Alpha'), $beta = new Product('Beta'), $gamma = new Product('Gamma'));
        $tester = $this->createLiveComponent(NumberedProductTable::class);
        $numbers = static fn (Crawler $table, Product ...$products): array => array_map(static fn (Product $product): array => [
            $table->filter(Selectors::cell((string) $product->id, 'number'))->text(),
            $table->filter(Selectors::cell((string) $product->id, 'index'))->text(),
        ], $products);
        $firstPage = $tester->render()->crawler();
        $secondPage = $tester->call('gotoTablePage', ['page' => 2])->render()->crawler();
        $all = $tester->set('tableRecordsPerPage', 'all')->call('gotoTablePage', ['page' => 2])->render()->crawler();

        expect($numbers($firstPage, $alpha, $beta))->toBe([['1', '0'], ['2', '1']])
            ->and($firstPage->filter(Selectors::ROW))->toHaveCount(2)
            ->and($numbers($secondPage, $gamma))->toBe([['3', '2']])
            ->and($secondPage->filter(Selectors::ROW))->toHaveCount(1)
            ->and($numbers($all, $alpha, $beta, $gamma))->toBe([['1', '0'], ['2', '1'], ['3', '2']]);
    });

    it('refuses to number a cell rendered without its row loop, naming the column', function (): void {
        $column = $this->createLiveComponent(NumberedProductTable::class)->component()->getTable()->getColumn('number');

        expect(fn (): mixed => $column?->record(new Product('Alpha'))->getState())
            ->toThrow(LogicException::class, 'rowIndex() on column [number] needs the row loop the table cell passes; the column was rendered without one.');
    });
});
