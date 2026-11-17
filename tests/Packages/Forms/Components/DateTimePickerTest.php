<?php

declare(strict_types=1);

use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\DateComparison;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\DatedManuscriptForm;
use Sylament\Tests\Fixtures\Forms\Record\StampedNote;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptObjectKinds;
use Twig\Error\RuntimeError;

describe('rendering', function (): void {
    it('renders the placeholder, the id, the name and the data-model on the input, with autofocus, disabled and required when set', function (): void {
        expect($this->renderSchemaComponent(DateTimePicker::make('startsAt')->placeholder('Pick a moment')->autofocus()->required()))
            ->toContainElement('input#schema\.startsAt[name="startsAt"][data-model$="data.startsAt"][placeholder="Pick a moment"][autofocus][required]:not([disabled])')
            ->and($this->renderSchemaComponent(DateTimePicker::make('startsAt')->disabled()))
            ->toContainElement('input[name="startsAt"][disabled]:not([placeholder]):not([autofocus]):not([required])');
    });

    it('renders readonly, a datalist the input points at and the extra input attributes when set, and none of them otherwise', function (): void {
        $render = $this->renderSchemaComponent(DateTimePicker::make('startsAt')->readOnly()->datalist(['2026-03-01 09:00:00', '2026-03-02 09:00:00'])->extraInputAttributes(['data-shift' => 'early']));

        expect($render)->toContainElement('input[name="startsAt"][readonly][list="schema.startsAt-list"][data-shift="early"]')
            ->and($render->filter('datalist[id="schema.startsAt-list"] option')->each(static fn ($option): ?string => $option->attr('value')))->toBe(['2026-03-01 09:00:00', '2026-03-02 09:00:00'])
            ->and($this->renderSchemaComponent(DateTimePicker::make('startsAt')))
            ->toContainElement('input[name="startsAt"]:not([readonly]):not([list]):not([data-shift])')
            ->not->toContainElement('datalist');
    });

    it('omits required while a container may hide the field, and keeps it otherwise', function (): void {
        expect($this->renderSchemaComponent(Section::make('When')->collapsible()->components([DateTimePicker::make('startsAt')->required()])))
            ->toContainElement('input[name="startsAt"]:not([required])')
            ->and($this->renderSchemaComponent(Section::make('When')->components([DateTimePicker::make('startsAt')->required()])))
            ->toContainElement('input[name="startsAt"][required]');
    });

    it('renders the raw wire string as the value and an empty value when unset', function (): void {
        expect($this->createLiveComponent(DatedManuscriptForm::class, ['data' => ['immutableAt' => '2026-08-18 14:30:00']])->render()->crawler())
            ->toContainElement('input[name="immutableAt"][value="2026-08-18 14:30:00"]')
            ->toContainElement('input[name="mutableAt"][value=""]');
    });

    it('marks the input invalid once the form refuses its value', function (): void {
        $tester = $this->createLiveComponent(DatedManuscriptForm::class)->fillForm(['immutableAt' => 'tampered'])->call('save');

        expect($tester->render()->crawler())
            ->toContainElement('input[name="immutableAt"][aria-invalid="true"]')
            ->toContainElement('input[name="mutableAt"][aria-invalid="false"]');
    });
});

describe('variants', function (): void {
    it('renders the control type each date and time combination asks for', function (Closure $field, string $type): void {
        expect($this->renderSchemaComponent($field()))->toContainElement("input[name=\"at\"][type=\"{$type}\"]");
    })->with([
        'a date and a time' => [fn (): DateTimePicker => DateTimePicker::make('at'), 'datetime-local'],
        'no date' => [fn (): DateTimePicker => DateTimePicker::make('at')->date(false), 'time'],
        'no time' => [fn (): DateTimePicker => DateTimePicker::make('at')->time(false), 'date'],
    ]);

    it('refuses to render a picker declared native(false), naming the field', function (): void {
        expect(fn () => $this->renderSchemaComponent(DateTimePicker::make('startsAt')->native(false)))->toThrow(function (RuntimeError $error): void {
            expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                ->and($error->getPrevious()?->getMessage())->toContain('The [startsAt] picker is declared native(false)');
        });
    });
});

describe('steps', function (): void {
    it('renders step 1 only while seconds are on and no step was named', function (): void {
        expect($this->renderSchemaComponent(DateTimePicker::make('at')))->toContainElement('input[name="at"][step="1"]')
            ->and($this->renderSchemaComponent(DateTimePicker::make('at')->seconds(false)))->toContainElement('input[name="at"][type="datetime-local"]:not([step])');
    });

    it('composes the time steps into one native step in seconds, seconds first, then minutes, then hours', function (Closure $field, string $step): void {
        expect($this->renderSchemaComponent($field()))->toContainElement("input[name=\"at\"][step=\"{$step}\"]");
    })->with([
        'seconds' => [fn (): DateTimePicker => DateTimePicker::make('at')->secondsStep(30), '30'],
        'minutes' => [fn (): DateTimePicker => DateTimePicker::make('at')->minutesStep(5), '300'],
        'hours' => [fn (): DateTimePicker => DateTimePicker::make('at')->hoursStep(2), '7200'],
        'seconds over minutes and hours' => [fn (): DateTimePicker => DateTimePicker::make('at')->hoursStep(2)->minutesStep(5)->secondsStep(30), '30'],
        'minutes over hours' => [fn (): DateTimePicker => DateTimePicker::make('at')->hoursStep(2)->minutesStep(5), '300'],
        'a step left at 1, which counts as unset' => [fn (): DateTimePicker => DateTimePicker::make('at')->secondsStep(1)->hoursStep(2), '7200'],
    ]);

    it('lets an explicit step beat the time steps, and renders none on a control with no time part', function (): void {
        expect($this->renderSchemaComponent(DateTimePicker::make('at')->step(15)->minutesStep(5)))->toContainElement('input[name="at"][step="15"]')
            ->and($this->renderSchemaComponent(DateTimePicker::make('at')->time(false)->minutesStep(5)))->toContainElement('input[name="at"][type="date"]:not([step])');
    });
});

describe('format', function (): void {
    it("writes the wire string in the variant's own shape and hands a string property that same shape", function (Closure $field, string $value): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($field())->record(StampedNote::class)->fill(new StampedNote($value));

        expect($host->data['publishedAt'])->toBe($value)
            ->and($schema->getData()->publishedAt)->toBe($value);
    })->with([
        'a date and a time' => [fn (): DateTimePicker => DateTimePicker::make('publishedAt'), '2026-08-18 14:30:15'],
        'a date and a time without seconds' => [fn (): DateTimePicker => DateTimePicker::make('publishedAt')->seconds(false), '2026-08-18 14:30'],
        'a date' => [fn (): DateTimePicker => DateTimePicker::make('publishedAt')->time(false), '2026-08-18'],
        'a time' => [fn (): DateTimePicker => DateTimePicker::make('publishedAt')->date(false), '14:30:15'],
        'a time without seconds' => [fn (): DateTimePicker => DateTimePicker::make('publishedAt')->date(false)->seconds(false), '14:30'],
    ]);

    it('writes a string record property in the declared format while the wire keeps its own', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('publishedAt')->format('d.m.Y'))->record(StampedNote::class);
        $host->data = ['publishedAt' => '2026-08-18 14:30:00'];

        expect($schema->getData()->publishedAt)->toBe('18.08.2026');

        $schema->fill(new StampedNote('19.08.2026'));

        expect($host->data['publishedAt'])->toBe('2026-08-19 00:00:00');
    });

    it("reads the browser's T spelling and the server's space spelling as one value", function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('immutableAt'))->record(ManuscriptObjectKinds::class);

        $host->data = ['immutableAt' => '2026-08-18T14:30'];
        $browser = $schema->getData()->immutableAt;
        $host->data = ['immutableAt' => '2026-08-18 14:30:00'];

        expect($browser)->toBeInstanceOf(DateTimeImmutable::class)
            ->toEqual($schema->getData()->immutableAt);
    });
});

describe('timezone', function (): void {
    it("reads the wire string in the field's timezone into the default one, and writes a record value back into it", function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('immutableAt')->timezone('Europe/Bratislava'))->record(ManuscriptObjectKinds::class);
        $host->data = ['immutableAt' => '2026-07-04 14:30:00'];
        $read = $schema->getData()->immutableAt;

        expect($read?->getTimestamp())->toBe(new DateTimeImmutable('2026-07-04 14:30:00', new DateTimeZone('Europe/Bratislava'))->getTimestamp())
            ->and($read?->getTimezone()->getName())->toBe(date_default_timezone_get());

        $record = new ManuscriptObjectKinds();
        $record->immutableAt = new DateTimeImmutable('2026-07-04 12:30:00', new DateTimeZone('UTC'));

        $schema->fill($record);

        expect($host->data['immutableAt'])->toBe('2026-07-04 14:30:00');
    });
});

describe('record frame', function (): void {
    it('writes the object the record property declares, and a string to a string property', function (string $recordClass, string $property, string $written): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make($property))->record($recordClass);
        $host->data = [$property => '2026-08-18 14:30:00'];
        $value = $schema->getData()->{$property};

        expect(is_object($value) ? $value::class : $value)->toBe($written);
    })->with([
        'an immutable property' => [ManuscriptObjectKinds::class, 'immutableAt', DateTimeImmutable::class],
        'a mutable property' => [ManuscriptObjectKinds::class, 'mutableAt', DateTime::class],
        'a date interface property' => [ManuscriptObjectKinds::class, 'happenedAt', DateTimeImmutable::class],
        'a string property' => [StampedNote::class, 'publishedAt', '2026-08-18 14:30:00'],
    ]);

    it('reads a raw wire string as the formatted string with no record behind it, and as the object of the record a subtree binds', function (): void {
        $host = $this->validatingRecordMappingHost();
        $frameless = $host->mapping(DateTimePicker::make('publishedAt'));
        $host->data = ['publishedAt' => '2026-08-18 14:30:00'];

        expect($frameless->getState())->toBe(['publishedAt' => '2026-08-18 14:30:00']);

        $host = $this->validatingRecordMappingHost();
        $nested = $host->mapping(
            Section::make('Manuscript')->statePath('manuscript')->record(ManuscriptObjectKinds::class)->components([DateTimePicker::make('immutableAt')]),
            DateTimePicker::make('publishedAt'),
        )->record(StampedNote::class);
        $host->data = ['manuscript' => ['immutableAt' => '2026-08-18 14:30:00'], 'publishedAt' => '2026-08-18 14:30:00'];
        $state = $nested->getState();

        expect($state['manuscript']['immutableAt'])->toBeInstanceOf(DateTimeImmutable::class)
            ->and($state['publishedAt'])->toBe('2026-08-18 14:30:00');
    });
});

describe('bounds', function (): void {
    it('refuses a value before minDate or after maxDate and accepts the bound itself', function (Closure $field, string $outside, string $bound, string $message): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($field());
        $host->data = ['startsAt' => $outside];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($message): void {
            expect($exception->getErrors())->toBe(['data.startsAt' => [$message]]);
        });

        $host->data = ['startsAt' => $bound];

        expect($host->validateSchema($schema))->toBeNull();
    })->with([
        'minDate' => [fn (): DateTimePicker => DateTimePicker::make('startsAt')->minDate('2026-01-01 08:00:00'), '2026-01-01 07:59:59', '2026-01-01 08:00:00', 'This value must be on or after 2026-01-01 08:00:00.'],
        'maxDate, a date object' => [fn (): DateTimePicker => DateTimePicker::make('startsAt')->maxDate(new DateTimeImmutable('2026-12-31 18:00:00')), '2026-12-31 18:00:01', '2026-12-31 18:00:00', 'This value must be on or before 2026-12-31 18:00:00.'],
    ]);

    it('registers no bound for a blank one', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('startsAt')->minDate(null), DateTimePicker::make('endsAt')->minDate('2026-01-01'));
        $host->data = ['startsAt' => '1990-01-01 00:00:00', 'endsAt' => '1990-01-01 00:00:00'];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.endsAt' => ['This value must be on or after 2026-01-01 00:00:00.']]);
        });
    });

    it('renders the bounds as native min and max attributes, whole on a control with a time part, and none without a bound', function (): void {
        expect($this->renderSchemaComponent(DateTimePicker::make('at')->minDate('2026-01-01 08:00:00')->maxDate(new DateTimeImmutable('2026-12-31 18:00:00'))))
            ->toContainElement('input[name="at"][min="2026-01-01 08:00:00"][max="2026-12-31 18:00:00"]')
            ->and($this->renderSchemaComponent(DateTimePicker::make('at')->date(false)->minDate('08:00')))
            ->toContainElement('input[name="at"][type="time"][min="08:00"]:not([max])')
            ->and($this->renderSchemaComponent(DateTimePicker::make('at')))
            ->toContainElement('input[name="at"]:not([min]):not([max])');
    });

    it('refuses to render a date-only control whose bound no date parse accepts, while a control with a time part passes it through and validates clean', function (): void {
        expect(fn () => $this->renderSchemaComponent(DateTimePicker::make('startsAt')->time(false)->minDate('2026-13-45')))->toThrow(function (RuntimeError $error): void {
            expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
                ->and($error->getPrevious()?->getMessage())->toBe("The [startsAt] picker's bound [2026-13-45] is not a parseable date.");
        });

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('startsAt')->minDate('2026-13-45'));
        $host->data = ['startsAt' => '1990-01-01 00:00:00'];

        expect($this->renderSchemaComponent(DateTimePicker::make('startsAt')->minDate('2026-13-45')))->toContainElement('input[name="startsAt"][min="2026-13-45"]')
            ->and($host->validateSchema($schema))->toBeNull();
    });

    it('answers one class-keyed message for both bounds and after()', function (): void {
        $host = $this->validatingRecordMappingHost();
        $messages = [DateComparison::class => 'Out of range.'];
        $schema = $host->mapping(
            DateTimePicker::make('opensAt')->minDate('2026-01-01 00:00:00')->validationMessages($messages),
            DateTimePicker::make('closesAt')->maxDate('2026-01-01 00:00:00')->validationMessages($messages),
            TextInput::make('noted')->after('2026-01-01')->validationMessages($messages),
        );
        $host->data = ['opensAt' => '2025-01-01 00:00:00', 'closesAt' => '2027-01-01 00:00:00', 'noted' => '2025-01-01'];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.opensAt' => ['Out of range.'],
                'data.closesAt' => ['Out of range.'],
                'data.noted' => ['Out of range.'],
            ]);
        });
    });
});

describe('validation', function (): void {
    it('refuses a value no date parse accepts and passes a cleared or absent one', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(DateTimePicker::make('tampered'), DateTimePicker::make('cleared'), DateTimePicker::make('absent'));
        $host->data = ['tampered' => 'tampered', 'cleared' => ''];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.tampered' => ['This value is not a valid date.']]);
        });
    });
});
