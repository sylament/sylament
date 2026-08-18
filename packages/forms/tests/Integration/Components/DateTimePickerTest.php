<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use ReflectionProperty;
use Sylament\Forms\Components\DatePicker;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\TimePicker;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\DateArticle;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Error\RuntimeError;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Render a picker inside a `data`-bag-bound schema seeded with `$data`, so the
 * `value` attribute reads the string the wire actually carries. `renderField()`
 * renders against an empty bag, which only covers the unset case.
 *
 * @param array<string, mixed> $data
 */
function renderPickerWithState(DateTimePicker $field, array $data): Crawler
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return new Crawler(renderToHtml(
        Schema::make($host)->statePath('data')->components([$field]),
    ));
}

/**
 * Wire pickers into a keyed, validator-backed schema over `$data` and run the
 * host's real validation, returning the host so the caller reads the errors per
 * state path. The constraints are built during that run, which is what lets a
 * boundary naming a sibling resolve against the state seeded here.
 *
 * @param array<DateTimePicker> $fields
 * @param array<string, mixed>  $data
 */
function validatePickers(array $fields, array $data): FieldRenderHost
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));
    $host->data = $data;

    $schema = Schema::make($host)->statePath('data')->components($fields);
    $host->cacheSchema('schema', $schema);

    $host->validateSchema($schema, shouldThrow: false);

    return $host;
}

/**
 * Wire a picker onto a bound record and fill it, so the raw state is what the
 * record's own value became on the wire. The host carries both the validator and
 * the schema key because `getData()` validates before it maps.
 */
function recordPickerSchema(DateTimePicker $field, object $record): Schema
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->getComponents();
    $schema->fill($record);

    return $schema;
}

/**
 * The date cast the picker appends. The write-side class it carries is resolved
 * from the bound record behind a protected method, so the cast is where a test
 * reads that resolution back.
 */
function pickerDateCast(DateTimePicker $field): DateTimeStateCast
{
    $cast = array_last($field->getDefaultStateCasts());

    if (! $cast instanceof DateTimeStateCast) {
        throw new LogicException('The picker appended no date cast to read a write-side class from.');
    }

    return $cast;
}

it('renders the native control of each variant', function (): void {
    $dateTime = new Crawler(renderField(DateTimePicker::make('publishedAt')))->filter('input.sy-fo-input');
    $date = new Crawler(renderField(DatePicker::make('day')))->filter('input.sy-fo-input');
    $time = new Crawler(renderField(TimePicker::make('alarm')))->filter('input.sy-fo-input');

    expect($dateTime->count())->toBe(1)
        ->and($dateTime->attr('type'))->toBe('datetime-local')
        ->and($dateTime->attr('id'))->toBe('publishedAt')
        ->and($dateTime->attr('name'))->toBe('publishedAt')
        ->and($dateTime->attr('data-model'))->toBe('norender|data.publishedAt')
        ->and($dateTime->attr('value'))->toBe('')
        ->and($dateTime->attr('step'))->toBe('1')
        ->and($dateTime->attr('min'))->toBeNull()
        ->and($dateTime->attr('max'))->toBeNull()
        ->and($date->attr('type'))->toBe('date')
        ->and($date->attr('step'))->toBeNull()
        ->and($time->attr('type'))->toBe('time')
        ->and($time->attr('step'))->toBe('1');
});

it('renders no step for a control without seconds', function (): void {
    $input = new Crawler(renderField(
        DateTimePicker::make('publishedAt')->seconds(false),
    ))->filter('input.sy-fo-input');

    expect($input->attr('step'))->toBeNull();
});

it('renders the composed step of a time step in seconds', function (): void {
    $input = new Crawler(renderField(
        DateTimePicker::make('publishedAt')->minutesStep(15),
    ))->filter('input.sy-fo-input');

    expect($input->attr('step'))->toBe('900');
});

it('renders the bounds of a control carrying time whole', function (): void {
    $input = new Crawler(renderField(
        DateTimePicker::make('publishedAt')
            ->minDate('2026-01-01')
            ->maxDate(new DateTimeImmutable('2026-12-31 20:00:00')),
    ))->filter('input.sy-fo-input');

    expect($input->attr('min'))->toBe('2026-01-01')
        ->and($input->attr('max'))->toBe('2026-12-31 20:00:00');
});

it('truncates the bounds of a date-only control', function (): void {
    $input = new Crawler(renderField(
        DatePicker::make('day')
            ->minDate(new DateTimeImmutable('2026-01-01 08:00:00'))
            ->maxDate('2026-12-31 20:00:00'),
    ))->filter('input.sy-fo-input');

    expect($input->attr('min'))->toBe('2026-01-01')
        ->and($input->attr('max'))->toBe('2026-12-31');
});

it('renders no datalist by default', function (): void {
    $crawler = new Crawler(renderField(DatePicker::make('meetup')));

    expect($crawler->filter('datalist')->count())->toBe(0)
        ->and($crawler->filter('input.sy-fo-input')->attr('list'))->toBeNull();
});

it('renders a datalist of dates and points the input at it', function (): void {
    $crawler = new Crawler(renderField(
        DatePicker::make('meetup')->datalist(['2026-09-01', '2026-09-15']),
    ));

    expect($crawler->filter('input.sy-fo-input')->attr('list'))->toBe('meetup-list');

    $datalist = $crawler->filter('datalist#meetup-list');

    expect($datalist->count())->toBe(1)
        ->and($datalist->filter('option')->count())->toBe(2)
        ->and($datalist->filter('option')->eq(0)->attr('value'))->toBe('2026-09-01')
        ->and($datalist->filter('option')->eq(1)->attr('value'))->toBe('2026-09-15');
});

it('resolves closure datalist options through evaluate', function (): void {
    $crawler = new Crawler(renderField(
        DatePicker::make('meetup')->datalist(fn (): array => ['2026-10-01']),
    ));

    expect($crawler->filter('datalist#meetup-list option')->attr('value'))->toBe('2026-10-01');
});

it('renders the read-only, disabled and required attributes', function (): void {
    $readOnly = new Crawler(renderField(DatePicker::make('day')->readOnly()))->filter('input.sy-fo-input');
    $disabled = new Crawler(renderField(DatePicker::make('day')->disabled()))->filter('input.sy-fo-input');
    $required = new Crawler(renderField(DatePicker::make('day')->required()));

    expect($readOnly->attr('readonly'))->not->toBeNull()
        ->and($disabled->attr('disabled'))->not->toBeNull()
        ->and($required->filter('input.sy-fo-input')->attr('required'))->not->toBeNull()
        ->and($required->filter('.sy-fo-field-label-required-mark')->count())->toBe(1);
});

it('renders the raw state verbatim as the value attribute', function (): void {
    $browserForm = renderPickerWithState(
        DateTimePicker::make('publishedAt')->seconds(false),
        ['publishedAt' => '2026-08-18T14:30'],
    );
    $serverForm = renderPickerWithState(
        DateTimePicker::make('publishedAt')->seconds(false),
        ['publishedAt' => '2026-08-18 14:30'],
    );

    expect($browserForm->filter('input.sy-fo-input')->attr('value'))->toBe('2026-08-18T14:30')
        ->and($serverForm->filter('input.sy-fo-input')->attr('value'))->toBe('2026-08-18 14:30');
});

it('refuses to render a non-native picker', function (): void {
    expect(fn (): string => renderField(DateTimePicker::make('publishedAt')->native(false)))
        ->toThrow(RuntimeError::class, 'date-time-picker.md');
});

it('reports a value no date parse accepts, on the time-only variant too', function (): void {
    $host = validatePickers(
        [DateTimePicker::make('publishedAt'), TimePicker::make('alarm')],
        ['publishedAt' => 'banana', 'alarm' => 'banana'],
    );

    expect($host->getErrorsForSchemaStatePath('data.publishedAt'))->toBe(['This value is not a valid date.'])
        ->and($host->getErrorsForSchemaStatePath('data.alarm'))->toBe(['This value is not a valid date.']);
});

it('reports nothing for the strings the controls speak', function (): void {
    $host = validatePickers(
        [DateTimePicker::make('publishedAt'), DatePicker::make('day'), TimePicker::make('alarm')],
        ['publishedAt' => '2026-08-18T14:30', 'day' => '2026-08-18', 'alarm' => '14:30'],
    );

    expect($host->getErrorsForSchemaStatePath('data.publishedAt'))->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.day'))->toBe([])
        ->and($host->getErrorsForSchemaStatePath('data.alarm'))->toBe([]);
});

it('compares a field against the sibling that after() names', function (): void {
    $fields = static fn (): array => [
        DateTimePicker::make('startsAt')->seconds(false),
        DateTimePicker::make('endsAt')->seconds(false)->after('startsAt'),
    ];

    $violating = validatePickers($fields(), ['startsAt' => '2026-08-18T10:00', 'endsAt' => '2026-08-18T09:00']);
    $clean = validatePickers($fields(), ['startsAt' => '2026-08-18T10:00', 'endsAt' => '2026-08-18T11:00']);

    expect($violating->getErrorsForSchemaStatePath('data.endsAt'))
        ->toBe(['This value must be after 2026-08-18 10:00:00.'])
        ->and($clean->getErrorsForSchemaStatePath('data.endsAt'))->toBe([]);
});

it('drops the comparison when the sibling it names is blank', function (): void {
    $host = validatePickers([
        DateTimePicker::make('startsAt')->seconds(false),
        DateTimePicker::make('endsAt')->seconds(false)->after('startsAt'),
    ], ['startsAt' => '', 'endsAt' => '2026-08-18T09:00']);

    expect($host->getErrorsForSchemaStatePath('data.endsAt'))->toBe([]);
});

it('reports a value outside the declared bounds', function (): void {
    $host = validatePickers(
        [DatePicker::make('day')->minDate('2026-01-01')->maxDate('2026-12-31')],
        ['day' => '2025-12-31'],
    );

    expect($host->getErrorsForSchemaStatePath('data.day'))
        ->toBe(['This value must be on or after 2026-01-01 00:00:00.']);
});

it('round-trips a date through an immutable record property', function (): void {
    $record = new DateArticle();
    $record->publishedAt = new DateTimeImmutable('2026-08-18 14:30:00');

    $picker = DateTimePicker::make('publishedAt')->seconds(false);
    $schema = recordPickerSchema($picker, $record);
    $cast = pickerDateCast($picker);

    expect(new ReflectionProperty($cast, 'objectClass')->getValue($cast))->toBe(DateTimeImmutable::class)
        ->and($picker->getRawState())->toBe('2026-08-18 14:30');

    $schema->rawState(['publishedAt' => '2026-08-19T09:15']);

    expect($schema->getData())->toBe($record)
        ->and($record->publishedAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($record->publishedAt->format('Y-m-d H:i:s'))->toBe('2026-08-19 09:15:00');
});

it('round-trips a date through a mutable record property', function (): void {
    $record = new DateArticle();
    $record->reviewedAt = new DateTime('2026-08-18 14:30:00');

    $picker = DateTimePicker::make('reviewedAt')->seconds(false);
    $schema = recordPickerSchema($picker, $record);
    $cast = pickerDateCast($picker);

    expect(new ReflectionProperty($cast, 'objectClass')->getValue($cast))->toBe(DateTime::class)
        ->and($picker->getRawState())->toBe('2026-08-18 14:30');

    $schema->rawState(['reviewedAt' => '2026-08-19T09:15']);
    $schema->getData();

    expect($record->reviewedAt)->toBeInstanceOf(DateTime::class)
        ->and($record->reviewedAt->format('Y-m-d H:i:s'))->toBe('2026-08-19 09:15:00');
});

it('round-trips a date through a string record property', function (): void {
    $record = new DateArticle();
    $record->archivedAt = '2026-08-18 14:30:00';

    $picker = DateTimePicker::make('archivedAt');
    $schema = recordPickerSchema($picker, $record);
    $cast = pickerDateCast($picker);

    expect(new ReflectionProperty($cast, 'objectClass')->getValue($cast))->toBeNull()
        ->and($picker->getRawState())->toBe('2026-08-18 14:30:00');

    $schema->rawState(['archivedAt' => '2026-08-19T09:15']);
    $schema->getData();

    expect($record->archivedAt)->toBe('2026-08-19 09:15:00');
});

it('writes a string property in the record-side format rather than the wire one', function (): void {
    $record = new DateArticle();

    $schema = recordPickerSchema(
        DateTimePicker::make('archivedAt')->seconds(false)->format('d.m.Y H:i'),
        $record,
    );

    $schema->rawState(['archivedAt' => '2026-08-19T09:15']);
    $schema->getData();

    expect($record->archivedAt)->toBe('19.08.2026 09:15');
});

it('reads back the record-side string when no record is bound', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['publishedAt' => '2026-08-19T09:15'];

    $picker = DateTimePicker::make('publishedAt');
    Schema::make($host)->statePath('data')->components([$picker])->getComponents();

    $cast = pickerDateCast($picker);

    expect(new ReflectionProperty($cast, 'objectClass')->getValue($cast))->toBeNull()
        ->and($picker->getState())->toBe('2026-08-19 09:15:00');
});

it('converts between the field timezone and the record instant', function (): void {
    $record = new DateArticle();
    $record->publishedAt = new DateTimeImmutable('2026-08-18 12:00:00', new DateTimeZone('UTC'));

    $picker = DateTimePicker::make('publishedAt')->seconds(false)->timezone('Europe/Bratislava');
    $schema = recordPickerSchema($picker, $record);

    expect($picker->getRawState())->toBe('2026-08-18 14:00');

    $schema->rawState(['publishedAt' => '2026-08-18 16:00']);
    $schema->getData();

    expect($record->publishedAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i'))
        ->toBe('2026-08-18 14:00');
});

it('seeds a default date as the string the control speaks', function (): void {
    $picker = DateTimePicker::make('publishedAt')
        ->seconds(false)
        ->default(new DateTimeImmutable('2026-08-18 09:00:00'));

    Schema::make(new FieldRenderHost())->statePath('data')->components([$picker])->fill();

    expect($picker->getRawState())->toBe('2026-08-18 09:00');
});
