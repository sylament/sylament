<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

describe('money and numbers', function (): void {
    it('prints money in the currency and locale it is given, divided and rounded as told', function (Closure $summarizer, string $printed): void {
        expect($this->renderSummarizer($summarizer()))->toContainElement(Selectors::summarizerFigure('text'), $printed);
    })->with([
        'dollars' => [fn (): Summarizer => Summarizer::make()->using(static fn (): float => 1234.5)->money('USD', locale: 'en_US'), '$1,234.50'],
        'cents divided into euros' => [fn (): Summarizer => Summarizer::make()->using(static fn (): int => 123450)->money('EUR', divideBy: 100, locale: 'de_DE'), "1.234,50\u{a0}€"],
        'whole dollars' => [fn (): Summarizer => Summarizer::make()->using(static fn (): float => 1234.56)->money('USD', locale: 'en_US', decimalPlaces: 0), '$1,235'],
    ]);

    it('prints a number to at most the places it is given, in the locale it is given', function (): void {
        expect($this->renderSummarizer(Summarizer::make()->using(static fn (): float => 1234.56)->numeric(maxDecimalPlaces: 1, locale: 'en_US')))
            ->toContainElement(Selectors::summarizerFigure('text'), '1,234.6');
    });

    it('treats an explicit null separator as passed and only the omitted one as the default', function (Closure $summarizer, string $printed): void {
        expect($this->renderSummarizer($summarizer()))->toContainElement(Selectors::summarizerFigure('text'), $printed);
    })->with([
        'omitted, so the locale separates' => [fn (): Summarizer => Summarizer::make()->using(static fn (): float => 1234.5)->numeric(2, locale: 'de_DE'), '1.234,50'],
        'null, so number_format() separates' => [fn (): Summarizer => Summarizer::make()->using(static fn (): float => 1234.5)->numeric(2, null, null, locale: 'de_DE'), '1,234.50'],
        'explicit' => [fn (): Summarizer => Summarizer::make()->using(static fn (): float => 1234.5)->numeric(2, ',', ' ', locale: 'en_US'), '1 234,50'],
    ]);
});

describe('the text around the figure', function (): void {
    it('escapes a text prefix and suffix, and the figure beside a markup one', function (): void {
        $plain = $this->renderSummarizer(Summarizer::make()->using(static fn (): string => '<3')->prefix('<b>')->suffix(' jars'))->filter(Selectors::summarizerFigure('text'));
        $marked = $this->renderSummarizer(Summarizer::make()->using(static fn (): string => '<3')->prefix(new Markup('<i>about</i> ', 'UTF-8')))->filter(Selectors::summarizerFigure('text'));

        expect($plain->text())->toBe('<b><3 jars')
            ->and($plain->filter('b'))->toHaveCount(0)
            ->and($marked->text())->toBe('about <3')
            ->and($marked->filter('i'))->toHaveCount(1);
    });

    it('cuts a long figure to its limit, ending it as told', function (): void {
        expect($this->renderSummarizer(Summarizer::make()->using(static fn (): string => 'Blackcurrant')->limit(5)))->toContainElement(Selectors::summarizerFigure('text'), 'Black...');
    });

    it('prints its placeholder for a figure that comes out blank', function (Closure $figure): void {
        expect($this->renderSummarizer(Summarizer::make()->using($figure)->placeholder('Nothing yet')))->toContainElement(Selectors::summarizerFigure('text'), 'Nothing yet');
    })->with([
        'no figure' => [static fn (): null => null],
        'a blank one' => [static fn (): string => '  '],
    ]);

    it('prints an enum by the label it gives itself, its markup kept, and an enum with none by its backing value', function (): void {
        $figure = fn (mixed $state): Crawler => $this->renderSummarizer(Summarizer::make()->using(static fn (): mixed => $state))->filter(Selectors::summarizerFigure('text'));

        expect($figure(DeliveryMode::Standard)->text())->toBe('Standard delivery')
            ->and($figure(DeliveryMode::Express)->text())->toBe('Express delivery')
            ->and($figure(DeliveryMode::Express)->filter('b')->text())->toBe('Express')
            ->and($figure(JarSeal::Wax)->text())->toBe('wax');
    });
});

describe('markup', function (): void {
    it('sanitizes an html figure, and converts a markdown one first', function (): void {
        $html = $this->renderSummarizer(Summarizer::make()->using(static fn (): string => '<b>Full</b><script>alert(1)</script>')->html())->filter(Selectors::summarizerFigure('text'));
        $markdown = $this->renderSummarizer(Summarizer::make()->using(static fn (): string => '**Full** shelf')->markdown())->filter(Selectors::summarizerFigure('text'));

        expect($html->filter('b')->text())->toBe('Full')
            ->and($html->filter('script'))->toHaveCount(0)
            ->and($markdown->filter('strong')->text())->toBe('Full')
            ->and($markdown->text())->toBe('Full shelf');
    });
});

it('hands a range its pair whole, through its closure and past its affixes', function (): void {
    $crawler = $this->renderSummarizer(Range::make()->using(static fn (): array => [1, 9])->prefix('#')->formatStateUsing(static fn (array $state): array => array_map(static fn (int $end): string => "{$end} jars", $state)));

    expect($crawler)->toContainElement(Selectors::summarizerFigure('range'), '1 jars - 9 jars');
});

it('refuses a figure it cannot print, naming its class', function (): void {
    expect(fn (): Crawler => $this->renderSummarizer(Summarizer::make()->using(static fn (): stdClass => new stdClass())))
        ->toThrow(LogicException::class, 'State of summarizer [' . Summarizer::class . '] must be stringable; stdClass given.');
});
