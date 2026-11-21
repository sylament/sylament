<?php

declare(strict_types=1);

use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\Record\IdlessTicket;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Forms\SelfTranslatedStatus;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Tables\GlazeFinish;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

use function Symfony\Component\Translation\t;

describe('the pipeline', function (): void {
    it('rewrites each cell through a formatStateUsing() closure, pins every cell to a literal, and shows the raw state without one', function (): void {
        $text = fn (TextColumn $column): string => $this->renderViewComponent($column)->filter(Selectors::TEXT_ITEM)->text();
        $shouted = TextColumn::make('name')->formatStateUsing(fn (string $state): string => mb_strtoupper($state));
        $fixed = TextColumn::make('name')->formatStateUsing('Fixed');

        expect($text($shouted->record(['name' => 'lamp'])))->toBe('LAMP')
            ->and($text($shouted->record(['name' => 'vase'])))->toBe('VASE')
            ->and($text($fixed->record(['name' => 'lamp'])))->toBe('Fixed')
            ->and($text($fixed->record(['name' => 'vase'])))->toBe('Fixed')
            ->and($text(TextColumn::make('name')->record(['name' => 'lamp'])))->toBe('lamp');
    });

    it('prints a HasLabel enum as its label, a backed enum without one as its value, a list item as its JSON and a scalar as its string', function (): void {
        $render = fn (mixed $state, ?int $limit = null): Crawler => $this->renderViewComponent(TextColumn::make('state')->record(['state' => $state])->limit($limit));

        expect($render(DeliveryMode::Standard)->filter(Selectors::TEXT_ITEM)->text())->toBe('Standard delivery')
            ->and($render(DeliveryMode::Express, limit: 3)->filter(Selectors::TEXT_ITEM)->text())->toBe('Express delivery')
            ->and($render(DeliveryMode::Express, limit: 3)->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('Express')
            ->and($render(StringBackedLetter::B)->filter(Selectors::TEXT_ITEM)->text())->toBe('b')
            ->and($render([['w' => 2, 'h' => 3]])->filter(Selectors::TEXT_ITEM)->text())->toBe('{"w":2,"h":3}')
            ->and($render(42)->filter(Selectors::TEXT_ITEM)->text())->toBe('42');
    });

    it('runs formatStateUsing() first, then the truncation, then the affixes', function (): void {
        $column = TextColumn::make('name')->record(['name' => 'lamp'])
            ->formatStateUsing(fn (string $state): string => "brass desk {$state}")
            ->limit(5)
            ->prefix('[')
            ->suffix(']');

        expect($this->renderViewComponent($column)->filter(Selectors::TEXT_ITEM)->text())->toBe('[brass...]');
    });

    it('answers its whole state run through the pipeline once from getFormattedState(), where the cell formats each value', function (): void {
        $column = TextColumn::make('tags')->record(['tags' => ['oak', 'ash']]);

        expect($column->getFormattedState())->toBe('["oak","ash"]')
            ->and($this->renderViewComponent($column)->filter(Selectors::TEXT_ITEM)->text())->toBe('oak, ash');
    });

    it('always answers a string from formatState(), and null for a null state', function (): void {
        $column = TextColumn::make('amount');

        expect($column->formatState(42))->toBe('42')
            ->and($column->formatState(1.5))->toBe('1.5')
            ->and($column->formatState(true))->toBe('1')
            ->and($column->formatState(null))->toBeNull();
    });

    it('refuses a state it cannot stringify, naming the column', function (): void {
        expect(fn (): mixed => TextColumn::make('ticket')->formatState(new IdlessTicket('T-1')))
            ->toThrow(LogicException::class, 'State of column [ticket] must be stringable; Sylament\Tests\Fixtures\Actions\Record\IdlessTicket given.');
    });
});

describe('truncation and affixes', function (): void {
    it('cuts a cell to limit() characters plus its end, trimmed before the end, and to words() words, leaving a text within the limit whole', function (): void {
        $text = fn (string $state, TextColumn $column): string => $this->renderViewComponent($column->record(['note' => $state]))->filter(Selectors::TEXT_ITEM)->text();

        expect($text('A brass desk lamp', TextColumn::make('note')->limit(8)))->toBe('A brass...')
            ->and($text('Lampshade', TextColumn::make('note')->limit(5, ' (more)')))->toBe('Lamps (more)')
            ->and($text('Lamp', TextColumn::make('note')->limit(4)))->toBe('Lamp')
            ->and($text('日本語のテキスト', TextColumn::make('note')->limit(3)))->toBe('日本語...')
            ->and($text('A brass desk lamp', TextColumn::make('note')->words(2)))->toBe('A brass...')
            ->and($text('A brass desk lamp', TextColumn::make('note')->words(3, ' …')))->toBe('A brass desk …')
            ->and($text('A brass desk lamp', TextColumn::make('note')->words(4)))->toBe('A brass desk lamp');
    });

    it('truncates nothing at a zero limit', function (): void {
        $text = fn (TextColumn $column): string => $this->renderViewComponent($column->record(['note' => 'A brass desk lamp']))->filter(Selectors::TEXT_ITEM)->text();

        expect($text(TextColumn::make('note')->limit(0)))->toBe('A brass desk lamp')
            ->and($text(TextColumn::make('note')->words(0)))->toBe('A brass desk lamp');
    });

    it('wraps the text in its prefix and suffix, trusting a Markup affix raw and escaping whichever side is not Markup', function (): void {
        $plain = $this->renderViewComponent(TextColumn::make('price')->record(['price' => '12'])->prefix('€ ')->suffix(' net'));
        $markupPrefix = $this->renderViewComponent(TextColumn::make('price')->record(['price' => '<i>12</i>'])->prefix(new Markup('<b>€</b>', 'UTF-8')));
        $markupState = $this->renderViewComponent(TextColumn::make('price')->record(['price' => '12'])
            ->formatStateUsing(fn (string $state): Markup => new Markup("<b>{$state}</b>", 'UTF-8'))
            ->suffix('<i>'));

        expect($plain->filter(Selectors::TEXT_ITEM)->text())->toBe('€ 12 net')
            ->and($markupPrefix->filter(Selectors::TEXT_ITEM)->text())->toBe('€<i>12</i>')
            ->and($markupPrefix->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('€')
            ->and($markupPrefix->filter(Selectors::TEXT_ITEM . ' i'))->toHaveCount(0)
            ->and($markupState->filter(Selectors::TEXT_ITEM)->text())->toBe('12<i>')
            ->and($markupState->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('12')
            ->and($markupState->filter(Selectors::TEXT_ITEM . ' i'))->toHaveCount(0);
    });

    it('leaves a Markup state whole under a limit, and cuts a plain state before a Markup affix joins it', function (): void {
        $markupState = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Brass desk lamp'])
            ->formatStateUsing(fn (string $state): Markup => new Markup("<b>{$state}</b>", 'UTF-8'))
            ->limit(5));
        $markupPrefix = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Brass desk lamp'])
            ->prefix(new Markup('<b>№</b> ', 'UTF-8'))
            ->limit(5));

        expect($markupState->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('Brass desk lamp')
            ->and($markupPrefix->filter(Selectors::TEXT_ITEM)->text())->toBe('№ Brass...')
            ->and($markupPrefix->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('№');
    });
});

describe('html and markdown', function (): void {
    it("renders an html() state, a state's label included, as sanitized markup that no limit cuts and whose plain affix is escaped, and a plain state as text", function (): void {
        $state = '<b>Bold</b> <a href="https://example.test" onclick="steal()">link</a>';
        $html = $this->renderViewComponent(TextColumn::make('note')->record(['note' => $state])->html()->limit(4)->suffix(' <i>'));
        $plain = $this->renderViewComponent(TextColumn::make('note')->record(['note' => '<b>Bold</b>']));
        $label = $this->renderViewComponent(TextColumn::make('glaze')->record(['glaze' => GlazeFinish::Matte])->html());
        $plainLabel = $this->renderViewComponent(TextColumn::make('glaze')->record(['glaze' => GlazeFinish::Matte]));

        expect($html->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('Bold')
            ->and($html->filter(Selectors::TEXT_ITEM . ' a[href="https://example.test"]')->text())->toBe('link')
            ->and($html->filter(Selectors::TEXT_ITEM . ' a[onclick]'))->toHaveCount(0)
            ->and($html->filter(Selectors::TEXT_ITEM)->text())->toBe('Bold link <i>')
            ->and($html->filter(Selectors::TEXT_ITEM . ' i'))->toHaveCount(0)
            ->and($plain->filter(Selectors::TEXT_ITEM)->text())->toBe('<b>Bold</b>')
            ->and($plain->filter(Selectors::TEXT_ITEM . ' b'))->toHaveCount(0)
            ->and($label->filter(Selectors::TEXT_ITEM . ' em')->text())->toBe('Matte')
            ->and($label->filter(Selectors::TEXT_ITEM)->text())->toBe('Matte glaze')
            ->and($label->filter(Selectors::TEXT_ITEM . ' script'))->toHaveCount(0)
            ->and($plainLabel->filter(Selectors::TEXT_ITEM)->text())->toBe('<em>Matte</em> glaze<script>alert(1)</script>')
            ->and($plainLabel->filter(Selectors::TEXT_ITEM . ' em'))->toHaveCount(0);
    });

    it('converts a markdown() state before sanitizing it, with the options and extensions it is given', function (): void {
        $state = '**Bold** <b>raw</b> <a href="https://example.test" onclick="steal()">link</a>';
        $markdown = $this->renderViewComponent(TextColumn::make('note')->record(['note' => $state])->markdown());
        $escaped = $this->renderViewComponent(TextColumn::make('note')->record(['note' => $state])->markdown()->commonMarkOptions(['html_input' => 'escape']));
        $smart = $this->renderViewComponent(TextColumn::make('note')->record(['note' => '"Quoted" -- here...'])->markdown()->commonMarkExtensions([new SmartPunctExtension()]));
        $plain = $this->renderViewComponent(TextColumn::make('note')->record(['note' => '"Quoted" -- here...'])->markdown());

        expect($markdown->filter(Selectors::TEXT_ITEM . ' strong')->text())->toBe('Bold')
            ->and($markdown->filter(Selectors::TEXT_ITEM . ' b')->text())->toBe('raw')
            ->and($markdown->filter(Selectors::TEXT_ITEM . ' a[href="https://example.test"]')->text())->toBe('link')
            ->and($markdown->filter(Selectors::TEXT_ITEM . ' a[onclick]'))->toHaveCount(0)
            ->and($escaped->filter(Selectors::TEXT_ITEM . ' strong')->text())->toBe('Bold')
            ->and($escaped->filter(Selectors::TEXT_ITEM . ' b'))->toHaveCount(0)
            ->and($escaped->filter(Selectors::TEXT_ITEM)->text())->toContain('<b>raw</b>')
            ->and($smart->filter(Selectors::TEXT_ITEM)->text())->toBe('“Quoted” – here…')
            ->and($plain->filter(Selectors::TEXT_ITEM)->text())->toBe('"Quoted" -- here...');
    });

    it('refuses html() on a column built with new, which has no sanitizer, naming the column', function (): void {
        expect(fn (): mixed => new TextColumn()->name('note')->html()->formatState('<b>Bold</b>'))
            ->toThrow(LogicException::class, 'Column [note] has no HTML sanitizer, which html() and markdown() need: build the column through the container rather than with new.');
    });
});

describe('dates', function (): void {
    it('prints a date, a timestamp or a parseable string in the format it is given, and nothing for a blank state or a blank member of a list', function (): void {
        $text = fn (mixed $state, TextColumn $column): string => $this->renderViewComponent($column->record(['shippedOn' => $state]))->filter(Selectors::TEXT_ITEM)->text();
        $blank = $this->renderViewComponent(TextColumn::make('shippedOn')->record(['shippedOn' => null])->date('Y'));
        $list = $this->renderViewComponent(TextColumn::make('shippedOn')->record(['shippedOn' => ['2024-03-05', '']])->date('Y')->listWithLineBreaks());

        expect($text(new DateTimeImmutable('2024-03-05 14:30:00', new DateTimeZone('UTC')), TextColumn::make('shippedOn')->date('d M Y', timezone: 'UTC')))->toBe('05 Mar 2024')
            ->and($text(1709649000, TextColumn::make('shippedOn')->dateTime('Y-m-d H:i', timezone: 'UTC')))->toBe('2024-03-05 14:30')
            ->and($text('2024-03-05', TextColumn::make('shippedOn')->date('j F Y')))->toBe('5 March 2024')
            ->and($blank)->toContainElement(Selectors::TEXT_COLUMN)
            ->and($blank->filter(Selectors::TEXT_ITEM))->toHaveCount(0)
            ->and($list->filter(Selectors::TEXT_ITEM)->each(static fn (Crawler $item): string => $item->text()))->toBe(['2024', '']);
    });

    it("prints since() as the time elapsed, and puts each date arm's text in the cell's tooltip, in the table's formats when given none, leaving a blank member of a list without either", function (): void {
        $since = $this->renderViewComponent(TextColumn::make('seenAt')->record(['seenAt' => new DateTimeImmutable('-3 days')])->since()->sinceTooltip());
        $blankMember = fn (TextColumn $column): array => $this->renderViewComponent($column->record(['seenAt' => ['2024-03-05', '']])->listWithLineBreaks())
            ->filter(Selectors::TEXT_ITEM)->each(static fn (Crawler $item): array => [$item->text(), $item->filter('[title]')->count()]);
        $table = Table::make(new class implements InteractsWithTable {
            use HasTable;
        })->defaultDateDisplayFormat('d/m/Y')->defaultDateTimeDisplayFormat('d/m/Y H:i')->defaultTimeDisplayFormat('H:i');
        $title = fn (TextColumn $column): ?string => $this->renderViewComponent($column->table($table)->record(['packedAt' => '2024-03-05 14:30:00']))
            ->filter(Selectors::TEXT_ITEM . ' [title]')->attr('title');

        expect($since->filter(Selectors::TEXT_ITEM)->text())->toBe('3 days ago')
            ->and($since->filter(Selectors::TEXT_ITEM . ' [title]')->attr('title'))->toBe('3 days ago')
            ->and($title(TextColumn::make('packedAt')->dateTimeTooltip()))->toBe('05/03/2024 14:30')
            ->and($title(TextColumn::make('packedAt')->timeTooltip()))->toBe('14:30')
            ->and($title(TextColumn::make('packedAt')->dateTooltip()))->toBe('05/03/2024')
            ->and($blankMember(TextColumn::make('seenAt')->since()->sinceTooltip())[1])->toBe(['', 0])
            ->and($blankMember(TextColumn::make('seenAt')->dateTooltip('Y')))->toBe([['2024-03-05', 1], ['', 0]]);
    });

    it("prints an instant in the timezone it is given, else the column's timezone(), else the process default", function (): void {
        $instant = new DateTimeImmutable('2024-03-05 14:30:00', new DateTimeZone('UTC'));
        $text = fn (mixed $state, TextColumn $column): string => $this->renderViewComponent($column->record(['openedAt' => $state]))->filter(Selectors::TEXT_ITEM)->text();

        expect($text($instant, TextColumn::make('openedAt')->time('H:i', timezone: 'Asia/Tokyo')))->toBe('23:30')
            ->and($text($instant, TextColumn::make('openedAt')->timezone('America/New_York')->time('H:i')))->toBe('09:30')
            ->and($text($instant, TextColumn::make('openedAt')->timezone('America/New_York')->time('H:i', timezone: 'Asia/Tokyo')))->toBe('23:30');

        $processZone = date_default_timezone_get();
        date_default_timezone_set('Asia/Tokyo');

        try {
            expect($text($instant, TextColumn::make('openedAt')->time('H:i')))->toBe('23:30');
        } finally {
            date_default_timezone_set($processZone);
        }
    });

    it('refuses a state that is no date, timestamp or string, naming the column', function (): void {
        expect(fn (): mixed => TextColumn::make('shippedOn')->date('Y')->formatState(1.5))
            ->toThrow(LogicException::class, 'State of column [shippedOn] must be a date, a timestamp or a parseable string; float given.');
    });
});

describe('money and numbers', function (): void {
    it('prints money in the currency and locale it is given, divided and rounded as told', function (): void {
        $text = fn (mixed $state, TextColumn $column): string => $this->renderViewComponent($column->record(['price' => $state]))->filter(Selectors::TEXT_ITEM)->text();
        $blank = $this->renderViewComponent(TextColumn::make('price')->record(['price' => null])->money('USD', locale: 'en_US'));

        expect($text(1234.5, TextColumn::make('price')->money('USD', locale: 'en_US')))->toBe('$1,234.50')
            ->and($text(123450, TextColumn::make('price')->money('USD', divideBy: 100, locale: 'en_US')))->toBe('$1,234.50')
            ->and($text(1234.56, TextColumn::make('price')->money('USD', locale: 'en_US', decimalPlaces: 0)))->toBe('$1,235')
            ->and($text(1234.5, TextColumn::make('price')->money('EUR', locale: 'de_DE')))->toBe("1.234,50\u{a0}€")
            ->and($text('n/a', TextColumn::make('price')->money('USD', locale: 'en_US')))->toBe('n/a')
            ->and($blank)->toContainElement(Selectors::TEXT_COLUMN)
            ->and($blank->filter(Selectors::TEXT_ITEM))->toHaveCount(0);
    });

    it('prints a number grouped by its locale with the decimal places it is given, the maximum winning, and through number_format() once a separator is passed', function (): void {
        $text = fn (mixed $state, TextColumn $column): string => $this->renderViewComponent($column->record(['figure' => $state]))->filter(Selectors::TEXT_ITEM)->text();
        $blank = $this->renderViewComponent(TextColumn::make('figure')->record(['figure' => null])->numeric(locale: 'de_DE'));

        expect($text(1234567.891, TextColumn::make('figure')->numeric(locale: 'de_DE')))->toBe('1.234.567,891')
            ->and($text(1234567.891, TextColumn::make('figure')->numeric(decimalPlaces: 1, locale: 'de_DE')))->toBe('1.234.567,9')
            ->and($text(1234567.891, TextColumn::make('figure')->numeric(decimalPlaces: 1, maxDecimalPlaces: 2, locale: 'de_DE')))->toBe('1.234.567,89')
            ->and($text(1234567.891, TextColumn::make('figure')->numeric(decimalPlaces: 2, thousandsSeparator: ' ')))->toBe('1 234 567.89')
            ->and($text(1234567.891, TextColumn::make('figure')->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')))->toBe('1.234.567,89')
            ->and($text('n/a', TextColumn::make('figure')->numeric(locale: 'de_DE')))->toBe('n/a')
            ->and($blank)->toContainElement(Selectors::TEXT_COLUMN)
            ->and($blank->filter(Selectors::TEXT_ITEM))->toHaveCount(0);
    });

    it('treats an explicit null separator as passed and only the omitted one as the default', function (): void {
        $text = fn (TextColumn $column): string => $this->renderViewComponent($column->record(['figure' => 1234567.891]))->filter(Selectors::TEXT_ITEM)->text();

        expect($text(TextColumn::make('figure')->numeric(decimalPlaces: 2, decimalSeparator: null, locale: 'de_DE')))->toBe('1,234,567.89')
            ->and($text(TextColumn::make('figure')->numeric(decimalPlaces: 2, thousandsSeparator: null, locale: 'de_DE')))->toBe('1,234,567.89')
            ->and($text(TextColumn::make('figure')->numeric(decimalPlaces: 2, locale: 'de_DE')))->toBe('1.234.567,89');
    });

    it('refuses a currency the formatter cannot resolve, naming the column', function (): void {
        expect(fn (): mixed => TextColumn::make('price')->money('X', locale: 'en_US')->formatState(12))
            ->toThrow(LogicException::class, 'Column [price] could not format its state as a number.');
    });
});

describe('messages', function (): void {
    it('prints a message formatStateUsing() answers, an enum that is a message itself and the affixes given as messages in their translation', function (): void {
        $render = fn (TextColumn $column): Crawler => $this->renderViewComponent($column)->filter(Selectors::TEXT_ITEM);

        expect($render(TextColumn::make('state')->record(['state' => 'x'])->formatStateUsing(static fn (): TranslatableInterface => t('column.state', [], 'fixtures-tables')))->text())->toBe('Pickled (translated)')
            ->and($render(TextColumn::make('state')->record(['state' => SelfTranslatedStatus::Archived]))->text())->toBe('Archived (translated)')
            ->and($render(TextColumn::make('state')->record(['state' => 'x'])->prefix(t('column.prefix', [], 'fixtures-tables'))->suffix(t('column.suffix', [], 'fixtures-tables')))->text())->toBe('Jar: (translated)x(translated)');
    });
});
