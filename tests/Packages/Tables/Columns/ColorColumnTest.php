<?php

declare(strict_types=1);

use Sylament\Tables\Columns\ColorColumn;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SwatchTone;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

describe('its swatches', function (): void {
    it('paints one swatch per value of a list or an iterable, each an image named by its colour', function (): void {
        $swatches = fn (mixed $state): Crawler => $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => $state]))->filter('.sy-ta-color-item');

        expect($swatches('#4f46e5')->each(static fn (Crawler $swatch): array => [$swatch->attr('role'), $swatch->attr('aria-label'), $swatch->attr('style')]))
            ->toBe([['img', '#4f46e5', 'background-color: #4f46e5']])
            ->and($swatches(['#fff', 'rgb(0, 0, 0)', 'teal'])->each(static fn (Crawler $swatch): ?string => $swatch->attr('aria-label')))->toBe(['#fff', 'rgb(0, 0, 0)', 'teal'])
            ->and($swatches(new ArrayIterator(['#fff', 'teal'])))->toHaveCount(2);
    });

    it('paints a value only as a CSS colour or a backed enum holding one, and keeps any other in its place as a swatch that paints and names nothing', function (): void {
        $swatches = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => ['#fff', 'red;position:fixed', SwatchTone::Indigo, SwatchTone::BrandBlue, 4, new Memo('#4f46e5')]]))
            ->filter('.sy-ta-color-item');

        expect($swatches->each(static fn (Crawler $swatch): array => [$swatch->attr('style'), $swatch->attr('role'), $swatch->attr('aria-label')]))->toBe([
            ['background-color: #fff', 'img', '#fff'],
            [null, null, null],
            ['background-color: #4f46e5', 'img', '#4f46e5'],
            [null, null, null],
            [null, null, null],
            [null, null, null],
        ]);
    });

    it('makes a copyable swatch a button named for its colour, Copy colour when it paints none, copying the value as the record holds it on a click, Enter or Space', function (): void {
        $swatches = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => ['#fff', 'red;position:fixed']])
            ->copyable()->copyMessage('Paint copied')->copyMessageDuration(500))->filter('.sy-ta-color-item');

        expect($swatches->each(static fn (Crawler $swatch): array => [
            $swatch->attr('role'),
            $swatch->attr('aria-label'),
            $swatch->attr('style'),
            $swatch->attr('data-sylament--support--clipboard-text-value'),
            $swatch->attr('data-sylament--support--clipboard-message-value'),
            $swatch->attr('data-sylament--support--clipboard-duration-value'),
        ]))->toBe([
            ['button', 'Copy #fff', 'background-color: #fff', '#fff', 'Paint copied', '500'],
            ['button', 'Copy colour', null, 'red;position:fixed', 'Paint copied', '500'],
        ])
            ->and($swatches->filter(SupportSelectors::COPYABLE . SupportSelectors::CLIPBOARD_CONTROLLER . SupportSelectors::CLIPBOARD_COPY . ':not([tabindex])'))->toHaveCount(2);
    });

    it('runs no copy closure for a swatch that is not copyable', function (): void {
        $refuse = static fn (): never => throw new LogicException('A swatch that is not copyable resolved its copy values.');
        $swatches = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => '#fff'])
            ->copyableState($refuse)->copyMessage($refuse)->copyMessageDuration($refuse))->filter('.sy-ta-color-item');

        expect($swatches->attr('role'))->toBe('img')
            ->and($swatches->attr('data-controller'))->toBeNull();
    });

    it('titles each swatch with its own tooltip and links each to its own url, in a new tab when asked, and leaves a url that ignores the state to the cell', function (): void {
        $linked = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => ['#fff', 'teal']])
            ->tooltip(fn (string $state): Markup => new Markup("<b>Paint</b> {$state}", 'UTF-8'))
            ->url(fn (string $state): string => '/paints/' . mb_ltrim($state, '#'), shouldOpenInNewTab: true));
        $sameTab = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => ['#fff', 'teal']])->url(fn (string $state): string => '/paints/' . mb_ltrim($state, '#')));
        $unlinked = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => ['#fff', 'teal']])->url('/paints'));

        expect($sameTab->filter('a:not([target])'))->toHaveCount(2)
            ->and($linked->filter('a[target="_blank"]')->each(static fn (Crawler $link): array => [$link->attr('href'), $link->filter('.sy-ta-color-item')->attr('title')]))
            ->toBe([['/paints/fff', 'Paint #fff'], ['/paints/teal', 'Paint teal']])
            ->and($unlinked->filter('.sy-ta-color-item'))->toHaveCount(2)
            ->and($unlinked->filter('a'))->toHaveCount(0);
    });
});

describe('its cell', function (): void {
    it('roots its cell with its alignment, its inline and wrapped looks and its extra attributes', function (): void {
        $root = fn (ColorColumn $column): Crawler => $this->renderViewComponent($column->record(['paint' => ['#fff', 'teal']]))->filter(Selectors::COLOR_COLUMN);

        expect($root(ColorColumn::make('paint')->alignEnd()->inline()->wrap()->extraAttributes(['data-shelf' => 'top']))
            ->matches(SupportSelectors::align('end') . SupportSelectors::INLINE . SupportSelectors::WRAPPED . '[data-shelf="top"]'))->toBeTrue()
            ->and($root(ColorColumn::make('paint'))->matches(':not(' . SupportSelectors::WRAPPED . ')'))->toBeTrue();
    });

    it('shows its placeholder for a blank state, titled by its empty tooltip as plain text, and no placeholder without one', function (): void {
        $blank = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => []])
            ->placeholder('No paint')->emptyTooltip(new Markup('<b>Nothing</b> mixed', 'UTF-8')));
        $bare = $this->renderViewComponent(ColorColumn::make('paint')->record(['paint' => null]));

        expect($blank)->toContainElement(Selectors::COLOR_COLUMN . '[title="Nothing mixed"] ' . Selectors::PLACEHOLDER, 'No paint')
            ->and($blank->filter('.sy-ta-color-item'))->toHaveCount(0)
            ->and($bare->filter(Selectors::COLOR_COLUMN . ':not([title])'))->toHaveCount(1)
            ->and($bare->filter(Selectors::PLACEHOLDER))->toHaveCount(0);
    });
});
