<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\BerthCleat;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\BoundVolume;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\IndexedBinder;
use Sylament\Tests\Fixtures\Entity\IndexedBinderSheet;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\VettedBinder;
use Sylament\Tests\Fixtures\Entity\VettedBinderSheet;
use Sylament\Tests\Fixtures\Schemas\CappedHeadlineForm;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\ComponentSeamForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\ReactiveSchemaForm;
use Sylament\Tests\Fixtures\Schemas\Record\VettedCard;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\SchemaResolutionHost;
use Sylament\Tests\Fixtures\Schemas\ScopedValidationForm;
use Sylament\Tests\Fixtures\Schemas\StickyValidationForm;
use Sylament\Tests\Fixtures\Schemas\UnnamedSchemaHost;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Type;

it('keeps the constraint each error failed beside its message, which the re-rendered host still holds', function (): void {
    $host = $this->createLiveComponent(CodeForm::class)->call('save')->component();

    expect($host->getErrorConstraintsForSchemaStatePath('data.code'))->toBe([RequiredValue::class])
        ->and($host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value should not be blank.']);
});

describe('schemas()', function (): void {
    it('gives a host that declares no schemas one, named schema', function (): void {
        $host = new UnnamedSchemaHost();

        expect(array_keys($host->getCachedSchemas()))->toBe(['schema'])
            ->and($host->getSchema()->getKey())->toBe('schema');
    });
});

describe('getSchema()', function (): void {
    it('builds a schema once from its same-named method, bound to the host and keyed by its name', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());
        $schema = $host->getSchema('form');

        expect($host->getSchema('form'))->toBe($schema)
            ->and($schema->getKey())->toBe('form')
            ->and($schema->getLiveComponent())->toBe($host)
            ->and($log->hooks)->toBe(['form()']);
    });

    it('builds a schema from its <name>Schema method when no bare one exists, and from the bare one when both do', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());

        expect($host->getSchema('settings')->getKey())->toBe('settings')
            ->and($host->getSchema('notes')->getKey())->toBe('notes')
            ->and($log->hooks)->toBe(['settingsSchema()', 'notes()']);
    });

    it('throws naming the host when no method builds the schema', function (): void {
        expect(fn (): Schema => new SchemaResolutionHost(new HookLog())->getSchema('missing'))
            ->toThrow(RuntimeException::class, 'Schema missing does not exist on component ' . SchemaResolutionHost::class . ' — expected a missing() or missingSchema() method.');
    });

    it('throws when the method returns no schema', function (): void {
        expect(fn (): Schema => new SchemaResolutionHost(new HookLog())->getSchema('title'))
            ->toThrow(RuntimeException::class, 'Schema must be an instance of ' . Schema::class);
    });
});

describe('getCachedSchemas()', function (): void {
    it('builds each declared schema the cache lacks, once, beside the ones it holds, and no undeclared one', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());
        $host->getSchema('form');
        $host->cacheSchema('extra', $host->makeSchema());

        expect(array_keys($host->getCachedSchemas()))->toBe(['form', 'extra', 'settings'])
            ->and(array_keys($host->getCachedSchemas()))->toBe(['form', 'extra', 'settings'])
            ->and($log->hooks)->toBe(['form()', 'settingsSchema()'])
            ->and($host->getSchema('draft')->getKey())->toBe('draft');
    });
});

describe('cacheSchema()', function (): void {
    it('registers a schema under a name, keyed by it, and unsets it for null', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());
        $schema = $host->makeSchema();

        expect($host->cacheSchema('form', $schema))->toBe($schema)
            ->and($host->getSchema('form'))->toBe($schema)
            ->and($schema->getKey())->toBe('form')
            ->and($log->hooks)->toBe([])
            ->and($host->cacheSchema('form', null))->toBeNull()
            ->and($host->getSchema('form'))->not->toBe($schema)
            ->and($log->hooks)->toBe(['form()']);
    });
});

describe('hasCachedSchema()', function (): void {
    it('answers from the cache alone, building no schema to answer', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());

        expect($host->hasCachedSchema('form'))->toBeFalse()
            ->and($log->hooks)->toBe([]);

        $host->getSchema('settings');

        expect($host->hasCachedSchema('settings'))->toBeTrue()
            ->and($host->hasCachedSchema('form'))->toBeFalse()
            ->and($log->hooks)->toBe(['settingsSchema()']);

        $host->cacheSchema('settings', null);

        expect($host->hasCachedSchema('settings'))->toBeFalse();
    });
});

describe('resolveSchemaByName()', function (): void {
    it('resolves a cached schema, and a method taking or returning a schema', function (): void {
        $host = new SchemaResolutionHost($log = new HookLog());
        $host->cacheSchema('extra', $extra = $host->makeSchema());

        expect($host->resolveSchemaByName('extra'))->toBe($extra)
            ->and($host->resolveSchemaByName('form')?->getKey())->toBe('form')
            ->and($host->resolveSchemaByName('settings')?->getKey())->toBe('settings')
            ->and($host->resolveSchemaByName('preview')?->getKey())->toBe('preview')
            ->and($log->hooks)->toBe(['form()', 'settingsSchema()', 'preview()']);
    });

    it('answers null without calling a method that is not a schema of the host', function (string $name): void {
        $host = new SchemaResolutionHost($log = new HookLog());

        expect($host->resolveSchemaByName($name))->toBeNull()
            ->and($log->hooks)->toBe([]);
    })->with([
        'no method' => ['missing'],
        'a parameterless method returning no schema' => ['heading'],
        'a first parameter that is no schema' => ['caption'],
        'a first parameter typed as a union, even one naming a schema' => ['badge'],
        'a second required parameter' => ['pair'],
        'a member of the host contract' => ['validateSchema'],
        'a member of the host contract by its Schema suffix' => ['make'],
    ]);
});

describe('getSchemaComponent()', function (): void {
    it('finds a component by its absolute key, and a hidden one only when asked', function (): void {
        $host = $this->createLiveComponent(ReactiveSchemaForm::class)->component();

        expect($host->getSchemaComponent('form.title')?->getName())->toBe('title')
            ->and($host->getSchemaComponent('form.secret'))->toBeNull()
            ->and($host->getSchemaComponent('form.secret', withHidden: true)?->getName())->toBe('secret');
    });

    it('answers null for a key with no schema name, an unknown schema or a name the gate refuses', function (string $key): void {
        $host = $this->createLiveComponent(ReactiveSchemaForm::class)->component();

        expect($host->getSchemaComponent($key))->toBeNull();
    })->with([
        'no dot' => ['title'],
        'an unknown schema' => ['missing.title'],
        'a member of the host contract' => ['validateSchema.title'],
    ]);
});

describe('getSchemasHostId()', function (): void {
    it("keeps the host's id, and with it every component's live key, across requests", function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class);
        $hostId = $tester->component()->getSchemasHostId();

        expect($hostId)->toMatch('/^[0-9a-f]{8}$/')
            ->and($tester->component()->getSchemaComponent('form.title')?->getLiveComponentKey())->toBe("{$hostId}.form.title");

        $host = $tester->fillForm(['title' => 'First'])->component();

        expect($host->getSchemasHostId())->toBe($hostId)
            ->and($host->getSchemaComponent('form.title')?->getLiveComponentKey())->toBe("{$hostId}.form.title");
    });
});

describe('fill()', function (): void {
    it('fills the default schema from the state given, or from its defaults, and returns the host', function (): void {
        $host = new UnnamedSchemaHost();

        expect($host->fill(['title' => 'Draft']))->toBe($host)
            ->and($host->data)->toBe(['title' => 'Draft']);

        $host->fill();

        expect($host->data)->toBe(['title' => 'Untitled']);
    });
});

describe('afterStateUpdated hooks', function (): void {
    it('runs the hooks of the field a request changed, with the value it held before, and none for a request that changed nothing', function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class)
            ->fillForm(['title' => 'First'])
            ->fillForm(['title' => 'Second']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['title: First -> Second']);

        $tester->refresh();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it("runs the hooks of a field inside a mounted action's modal schema", function (): void {
        $this->createLiveComponent(ReactiveSchemaForm::class)
            ->mountAction('rename')
            ->fillForm(['name' => 'Renamed']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['name: Renamed']);
    });
});

describe('getOldSchemaState()', function (): void {
    it('answers the state the last request left at a path, and null for a path it held nothing at or for no path', function (): void {
        $host = $this->createLiveComponent(ReactiveSchemaForm::class)->fillForm(['title' => 'First'])->component();

        expect($host->getOldSchemaState('data.title'))->toBe('First')
            ->and($host->getOldSchemaState('data.missing'))->toBeNull()
            ->and($host->getOldSchemaState(null))->toBeNull();
    });
});

describe('callSchemaComponentMethod()', function (): void {
    it('calls an exposed public method with the arguments named, and answers its result in a browser event', function (): void {
        $tester = $this->createLiveComponent(ComponentSeamForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.greeter', 'method' => 'greet', 'arguments' => ['name' => 'Ada']]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['greet: Ada'])
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
                ['componentKey' => 'form.greeter', 'method' => 'greet', 'fileKey' => null, 'result' => 'Hello, Ada'],
            ]);
    });

    it('calls nothing and answers a null result for a call the gate refuses', function (string $componentKey, string $method, array $arguments): void {
        $tester = $this->createLiveComponent(ComponentSeamForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => $componentKey, 'method' => $method, 'arguments' => $arguments]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
                ['componentKey' => $componentKey, 'method' => $method, 'fileKey' => null, 'result' => null],
            ]);
    })->with([
        'a method not exposed' => ['form.greeter', 'unexposed', []],
        'an exposed method that is not public' => ['form.greeter', 'guarded', []],
        'a method the component lacks' => ['form.greeter', 'missing', []],
        'an unknown component' => ['form.nobody', 'greet', ['name' => 'Ada']],
        'a key naming a member of the host contract' => ['validateSchemaRecord.greeter', 'greet', ['name' => 'Ada']],
        'an argument the method does not take' => ['form.greeter', 'greet', ['name' => 'Ada', 'nickname' => 'A']],
        'a required argument missing' => ['form.greeter', 'greet', []],
        'an argument of the wrong type' => ['form.greeter', 'greet', ['name' => ['Ada']]],
    ]);

    it('throws naming the method and the component for a result JSON cannot carry', function (): void {
        expect(fn () => $this->createLiveComponent(ComponentSeamForm::class)->call('callSchemaComponentMethod', ['componentKey' => 'form.greeter', 'method' => 'rawBytes']))
            ->toThrow(LogicException::class, 'The method "rawBytes" on schema component "form.greeter" returned a value that is not JSON-serialisable; a browser-callable method must.');
    });
});

describe('uploadSchemaComponentFile()', function (): void {
    it('hands the part named after the file key to the component, and answers its result with that key', function (): void {
        $tester = $this->createLiveComponent(ComponentSeamForm::class)->call(
            'uploadSchemaComponentFile',
            ['componentKey' => 'form.attachment', 'fileKey' => 'second'],
            [
                'sylament_upload_first' => new UploadedFile(__FILE__, 'first.txt', 'text/plain', test: true),
                'sylament_upload_second' => new UploadedFile(__FILE__, 'second.txt', 'text/plain', test: true),
            ],
        );

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['upload: second second.txt'])
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
                ['componentKey' => 'form.attachment', 'method' => 'uploadSchemaComponentFile', 'fileKey' => 'second', 'result' => ['ok' => true, 'fileKey' => 'second', 'name' => 'second.txt']],
            ]);
    });

    it('hands no file over and answers a null result for an upload the gate refuses', function (string $componentKey, string $fileKey, string $part): void {
        $tester = $this->createLiveComponent(ComponentSeamForm::class)->call(
            'uploadSchemaComponentFile',
            ['componentKey' => $componentKey, 'fileKey' => $fileKey],
            [$part => new UploadedFile(__FILE__, 'note.txt', 'text/plain', test: true)],
        );

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
                ['componentKey' => $componentKey, 'method' => 'uploadSchemaComponentFile', 'fileKey' => $fileKey, 'result' => null],
            ]);
    })->with([
        'a component accepting no uploads' => ['form.plain', 'k1', 'sylament_upload_k1'],
        'a hidden component' => ['form.hiddenAttachment', 'k1', 'sylament_upload_k1'],
        'a file key with a dot' => ['form.attachment', 'k.1', 'sylament_upload_k.1'],
        'a file key ending in a newline' => ['form.attachment', "k1\n", "sylament_upload_k1\n"],
        'a file key over 64 characters' => ['form.attachment', str_repeat('k', 65), 'sylament_upload_' . str_repeat('k', 65)],
        'no part under the file key' => ['form.attachment', 'k1', 'sylament_upload_k2'],
    ]);
});

describe('validateSchema()', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it("files each failing path's message, announces the failure and throws, and a passing run drops the errors and the stickiness", function (): void {
        $tester = $this->createLiveComponent(ScopedValidationForm::class)
            ->call('save')
            ->assertHasFormErrors(['code' => 'This value should not be blank.', 'city']);

        expect($this->dispatchedBrowserEvents($tester, 'form-validation-error'))->toBe([['schemaKey' => 'form']]);

        $tester->fillForm(['city' => 'Prague'])
            ->assertHasFormErrors(['code'])
            ->fillForm(['code' => 'X1'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['code' => ''])
            ->assertHasNoFormErrors();
    });

    it('throws without announcing the failure on a host built outside the container', function (): void {
        $schema = $this->host->mapping(TextInput::make('label')->required())->record($mooring = new BerthMooring())->fill($mooring);

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.label' => ['This value should not be blank.']]);
        });
    });

    it('validates the value each component rewrites for validation', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('code')->maxLength(3)->mutateStateForValidationUsing(fn (?string $state): string => str_replace('-', '', (string) $state)),
            TextInput::make('reference')->maxLength(3),
        );
        $this->host->data = ['code' => 'A-B-C', 'reference' => 'A-B-C'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.reference' => ['This value is too long. It should have 3 characters or less.']]);
        });
    });

    it("rewrites a schema's errors whole on every run, so a field whose constraint a sibling took away loses its error", function (): void {
        $schema = $this->host->mapping(
            TextInput::make('kind'),
            TextInput::make('reference')->requiredIf('kind', 'invoice'),
            TextInput::make('code')->required(),
        );
        $this->host->data = ['kind' => 'invoice', 'reference' => '', 'code' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.reference'))->toBe(['This value should not be blank.']);

        $this->host->data['kind'] = 'note';

        expect(fn () => $this->host->validateSchema($schema))->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.reference'))->toBe([])
            ->and($this->host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value should not be blank.']);
    });

    it('refuses to validate on a host holding no validator, naming its setter, while such a host still maps a record unchecked', function (): void {
        $host = new RecordMappingHost();

        expect(fn () => $host->validateSchema($host->mapping(TextInput::make('code')->required())))
            ->toThrow(LogicException::class, 'setSchemaStateValidator()');

        $card = new VettedCard();
        $schema = $host->mapping(TextInput::make('title'))->record($card);
        $host->data = ['title' => VettedCard::WITHDRAWN];

        expect($schema->getData())->toBe($card)
            ->and($card->title)->toBe(VettedCard::WITHDRAWN);
    });

    it('refuses to validate a schema registered under no key', function (): void {
        $schema = $this->host->makeSchema()->statePath('data')->components([TextInput::make('code')->required()]);

        expect(fn () => $this->host->validateSchema($schema))->toThrow(LogicException::class, 'Cannot validate a schema without a key');
    });

    it('brings an error back when a fixed field breaks again before any submit, announcing only the submit', function (): void {
        $tester = $this->createLiveComponent(CodeForm::class)->call('save');

        expect($this->dispatchedBrowserEvents($tester, 'form-validation-error'))->toBe([['schemaKey' => 'form']]);

        $tester->fillForm(['code' => 'X1'])
            ->assertHasNoFormErrors()
            ->fillForm(['code' => ''])
            ->assertHasFormErrors(['code' => RequiredValue::class]);

        expect($this->dispatchedBrowserEvents($tester, 'form-validation-error'))->toBe([]);
    });

    it('validates the value an afterStateUpdated hook wrote in the same request', function (): void {
        $this->createLiveComponent(StickyValidationForm::class)
            ->fillForm(['city' => 'Prague'])
            ->call('save')
            ->assertHasFormErrors(['slug'])
            ->fillForm(['title' => 'Harbour'])
            ->assertFormSet(['slug' => 'harbour'])
            ->assertHasNoFormErrors();
    });

    it('runs only the implicit constraints against a blank string, so a cleared optional bound passes while required() and accepted() still refuse it', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('quantity')->numeric()->minValue(1)->maxValue(10),
            TextInput::make('code')->required(),
            Checkbox::make('terms')->accepted(),
        );
        $this->host->data = ['quantity' => '', 'code' => '', 'terms' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.code' => ['This value should not be blank.'],
                'data.terms' => ['This value should be true.'],
            ]);
        });
    });
});

describe('clearSchemaValidation()', function (): void {
    it("drops one schema's errors with those of the schemas nested in it, keeping another's, and every schema's with no key", function (): void {
        $tester = $this->createLiveComponent(ScopedValidationForm::class)
            ->call('saveDetails')
            ->call('saveSettings')
            ->assertHasFormErrors(['city'])
            ->assertHasFormErrors(['theme'], 'settings');

        $tester->call('clearErrors', ['schemaKey' => 'form'])
            ->assertHasNoFormErrors(['city'])
            ->assertHasFormErrors(['theme'], 'settings');

        $tester->call('clearErrors')
            ->assertHasNoFormErrors(form: 'settings');
    });
});

describe('validateSchemaRecord()', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('files a blank key refusal under the first blank key field a component maps, past one it does not', function (): void {
        $schema = $this->host->mapping(TextInput::make('slot'), TextInput::make('label'))->record($mooring = new BerthMooring())->fill($mooring);
        $this->host->data = ['slot' => '   ', 'label' => 'Pierless'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.slot' => ['This value should not be blank.']]);
        })
            ->and($this->host->getErrorsForSchemaStatePath('data.slot'))->toBe(['This value should not be blank.']);
    });

    it('leaves a blank key no component maps to the create that persists the record, running no statement', function (): void {
        $mooring = new BerthMooring();
        $schema = $this->host->mapping(TextInput::make('label'))->record($mooring)->fill($mooring);
        $this->host->data = ['label' => 'Orphan'];

        expect($schema->getData())->toBe($mooring)
            ->and($mooring->label)->toBe('Orphan')
            ->and($this->statementsRun())->toBe([]);
    });

    it('refuses a blank key field a component maps on a record whose prePersist callback fills the other half', function (): void {
        $schema = $this->host->mapping(TextInput::make('scope'), TextInput::make('label'))->record($bulletin = new StampedBulletin())->fill($bulletin);
        $this->host->data = ['scope' => '   ', 'label' => 'Spaced'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.scope' => ['This value should not be blank.']]);
        });
    });

    it("files a related record's blank key refusal at the schema's own path when only a layout component entangling it maps the key", function (): void {
        $schema = $this->host->mapping(Group::make([TextInput::make('label')])->relationship('spine'))->record($volume = new BoundVolume())->fill($volume);
        $this->host->data = ['spine' => ['label' => 'Unnumbered']];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data' => ['This value should not be blank.']]);
        })
            ->and($this->host->getErrorsForSchemaStatePath('data'))->toBe(['This value should not be blank.']);
    });

    it('files a taken key under the one half a relationship repeater item maps, and nothing at the item', function (): void {
        $this->seed(new BenchPot(bay: 'north', slot: '1', plant: 'basil'));

        $bench = new NurseryBench();
        $bench->addPot(new BenchPot(bay: 'south', slot: '1'));

        $schema = $this->host->mapping(TextInput::make('name'), Repeater::make('pots')->relationship()->components([TextInput::make('bay'), TextInput::make('plant')]))->record($bench)->fill($bench);
        $itemKey = array_key_first($this->host->data['pots']);
        $this->host->data['name'] = 'North';
        $this->host->data['pots'][$itemKey]['bay'] = 'north';
        $this->host->data['pots'][$itemKey]['plant'] = 'thyme';

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($itemKey): void {
            expect($exception->getErrors())->toBe(["data.pots.{$itemKey}.bay" => ['This value is already used.']]);
        })
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it("answers a record's own constraints first, and a taken key only once they pass", function (): void {
        $this->seed(new CappedHeadline(code: 'H1', title: 'Old', slug: 'old'));

        $schema = $this->host->mapping(TextInput::make('code'), TextInput::make('title'))->record(new CappedHeadline(slug: 'new'));
        $this->host->data = ['code' => 'H1', 'title' => 'Far too long'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['This value is too long. It should have 5 characters or less.']]);
        });

        $this->host->data['title'] = 'Short';

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value is already used.']]);
        });
    });

    it('refuses to map a record onto a schema registered under no key, even a clean one', function (): void {
        $schema = $this->host->makeSchema()->statePath('data')->components([TextInput::make('title')])->record(new VettedCard());
        $this->host->data = ['title' => 'Fine'];

        expect(fn () => $schema->getData())->toThrow(LogicException::class, 'Cannot validate a schema without a key');
    });

    it("files a violation no component maps at a pathless schema's empty path", function (): void {
        $this->seed(new CappedHeadline(code: 'H1', title: 'Old', slug: 'news'));

        $schema = $this->host->makeSchema()->record(new CappedHeadline(code: 'H2', title: 'New', slug: 'news'));
        $this->host->cacheSchema('schema', $schema);

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['' => ['This value is already used.']]);
        })
            ->and($this->host->getErrorsForSchemaStatePath(''))->toBe(['This value is already used.']);
    });

    it('shows a record constraint with its class on the refused submit, writing nothing, and drops it on the next request', function (): void {
        $this->seed(new CappedHeadline(code: 'H1', title: 'Old', slug: 'news'));

        $tester = $this->createLiveComponent(CappedHeadlineForm::class)
            ->fillForm(['code' => 'H2', 'title' => 'Fresh', 'slug' => 'news'])
            ->call('save')
            ->assertHasFormErrors(['slug' => UniqueEntity::class]);

        expect($this->entityManager()->getRepository(CappedHeadline::class)->count())->toBe(1);

        $tester->fillForm(['title' => 'Later'])
            ->assertHasNoFormErrors();
    });

    it('refuses a new record whose assigned key a stored row holds, under each key field the form maps but never the association', function (object $stored, Closure $candidate, array $mapped, array $state, array $expected): void {
        $this->seed($stored);

        $schema = $this->host->mapping(...array_map(TextInput::make(...), $mapped))->record($candidate($this->entityManager()));
        $this->host->data = $state;

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($expected): void {
            expect($exception->getErrors())->toBe($expected);
        })
            ->and($this->statementsRun())->toHaveCount(1)
            ->and($this->entityManager()->isOpen())->toBeTrue();
    })->with([
        'scalar fields' => fn (): array => [
            new OrderLine(order: 1, line: 1, note: 'Stored'),
            fn (): OrderLine => new OrderLine(order: 0, line: 0),
            ['order', 'line', 'note'],
            ['order' => '1', 'line' => '1', 'note' => 'Copy'],
            ['data.order' => ['This value is already used.'], 'data.line' => ['This value is already used.']],
        ],
        'an association plus a scalar' => fn (): array => [
            new InvoiceLine(new Invoice(7), line: 1, note: 'Stored'),
            fn (EntityManagerInterface $entityManager): InvoiceLine => new InvoiceLine($entityManager->getReference(Invoice::class, 7), line: 0),
            ['line', 'note'],
            ['line' => '1', 'note' => 'Copy'],
            ['data.line' => ['This value is already used.']],
        ],
    ]);

    it("files a taken key at the schema's own path when the form maps none of its fields", function (object $stored, Closure $candidate): void {
        $this->seed($stored);

        $schema = $this->host->mapping(TextInput::make('note'))->record($candidate($this->entityManager()));
        $this->host->data = ['note' => 'Copy'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data' => ['This value is already used.']]);
        })
            ->and($this->host->getErrorsForSchemaStatePath('data'))->toBe(['This value is already used.']);
    })->with([
        'scalar fields' => fn (): array => [
            new OrderLine(order: 1, line: 1, note: 'Stored'),
            fn (): OrderLine => new OrderLine(order: 1, line: 1),
        ],
        'an association plus a scalar' => fn (): array => [
            new InvoiceLine(new Invoice(7), line: 1, note: 'Stored'),
            fn (EntityManagerInterface $entityManager): InvoiceLine => new InvoiceLine($entityManager->getReference(Invoice::class, 7), line: 1),
        ],
    ]);

    it('asks nothing for a new record whose identifier is generated', function (): void {
        $article = new Article('Draft');
        $schema = $this->host->mapping(TextInput::make('title'))->record($article);
        $this->host->data = ['title' => 'Published'];

        expect($schema->getData())->toBe($article)
            ->and($article->title)->toBe('Published')
            ->and($this->statementsRun())->toBe([]);
    });

    it("refuses a new record's blank key under its first blank key field the form maps, a string before an association", function (Closure $record, array $state, array $expected): void {
        $schema = $this->host->mapping(...array_map(TextInput::make(...), array_keys($state)))->record($record());
        $this->host->data = $state;

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($expected): void {
            expect($exception->getErrors())->toBe($expected);
        })
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'scalar fields' => [fn (): BerthMooring => new BerthMooring(), ['pier' => '   ', 'slot' => '   ', 'label' => 'North'], ['data.pier' => ['This value should not be blank.']]],
        'an association plus a scalar' => [fn (): BerthCleat => new BerthCleat(), ['slot' => '   ', 'label' => 'North'], ['data.slot' => ['This value should not be blank.']]],
    ]);

    it('refuses a key field the form maps but does not dehydrate, whose typed value never reaches the record', function (): void {
        $schema = $this->host->mapping(TextInput::make('pier')->dehydrated(false), TextInput::make('slot'))->record(new BerthMooring());
        $this->host->data = ['pier' => 'P1', 'slot' => 'S1'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.pier' => ['This value should not be blank.']]);
        });
    });

    it('refuses a changed key under its field and puts the stored key back, leaving the rest of the mapping on the record', function (object $stored, string $keyField, string $otherField, string $movedKey): void {
        $this->seed($stored);
        $record = $this->reload($stored);
        $storedKey = $record->{$keyField};

        $schema = $this->host->mapping(TextInput::make($keyField), TextInput::make($otherField))->record($record);
        $schema->fill($record);
        $this->host->data[$keyField] = $movedKey;
        $this->host->data[$otherField] = 'Edited';

        $entityManager = $this->entityManager();

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($keyField): void {
            expect($exception->getErrors())->toBe(["data.{$keyField}" => ['This value cannot be changed.']]);
        })
            ->and($record->{$keyField})->toBe($storedKey)
            ->and($record->{$otherField})->toBe('Edited')
            ->and($entityManager->find($record::class, $entityManager->getClassMetadata($record::class)->getIdentifierValues($record)))->toBe($record)
            ->and($this->reload($record)->{$otherField})->toBe('Stored');
    })->with([
        'a generated identifier' => fn (): array => [new Article('Stored'), 'id', 'title', '99'],
        'scalar fields' => fn (): array => [new OrderLine(order: 1, line: 1, note: 'Stored'), 'line', 'note', '2'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 1, note: 'Stored'), 'line', 'note', '2'],
    ]);

    it('takes the stored key typed back as a string for no change', function (): void {
        $this->seed($stored = new Article('Stored'));
        $article = $this->reload($stored);

        $schema = $this->host->mapping(TextInput::make('id'), TextInput::make('title'))->record($article);
        $schema->fill($article);
        $this->host->data['id'] = (string) $article->id;
        $this->host->data['title'] = 'Edited';

        expect($schema->getData())->toBe($article)
            ->and($article->title)->toBe('Edited');
    });

    it("gates a blank string in front of the state run only, so a constraint a field adds passes it while the record's own still refuses it", function (): void {
        $card = new VettedCard();
        $schema = $this->host->mapping(Section::make()->statePath('author')->components([
            TextInput::make('name')->constraint(new Type('alpha'))->dehydrateStateUsing(fn (?string $state): string => $state ?? ''),
        ]))->record($card);
        $this->host->data = ['author' => ['name' => '']];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.author.name' => ['The author name is too short.']]);
        })
            ->and($card->author->name)->toBe('');
    });

    it('files a violation inside an item under the item the record collection holds it at, after a save that thins and grows it', function (): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One'));
        $binder->addSheet(new VettedBinderSheet('Two'));
        $this->seed($binder);
        $binder = $this->reload($binder);

        $schema = $this->host->mapping(Repeater::make('sheets')->relationship()->components([TextInput::make('heading')]))->record($binder);
        $schema->fill($binder);
        [$removed, $kept] = array_keys($this->host->data['sheets']);
        unset($this->host->data['sheets'][$removed]);
        $this->host->data['sheets'][$kept]['heading'] = 'Overlong';
        $this->host->data['sheets']['added'] = ['heading' => 'Overlong too'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($kept): void {
            expect($exception->getErrors())->toBe([
                "data.sheets.{$kept}.heading" => ['A sheet heading is too long.'],
                'data.sheets.added.heading' => ['A sheet heading is too long.'],
            ]);
        });
    });

    it('files a violation inside an item under the item a collection indexed by a string holds it at', function (): void {
        $binder = new IndexedBinder();
        $binder->addSheet(new IndexedBinderSheet(code: 'alpha', heading: 'One'));
        $binder->addSheet(new IndexedBinderSheet(code: 'beta', heading: 'Two'));
        $this->seed($binder);
        $binder = $this->reload($binder);

        $schema = $this->host->mapping(Repeater::make('sheets')->relationship()->components([TextInput::make('code'), TextInput::make('heading')]))->record($binder);
        $schema->fill($binder);

        $beta = array_find_key($this->host->data['sheets'], static fn (array $item): bool => $item['code'] === 'beta');
        $this->host->data['sheets'][$beta]['heading'] = 'Overlong';

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($beta): void {
            expect($exception->getErrors())->toBe(["data.sheets.{$beta}.heading" => ['A sheet heading is too long.']]);
        });
    });

    it("files a violation on the collection under the repeater, and one on an item property no item field maps at the schema's own path", function (): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One', status: VettedBinderSheet::DRAFT));
        $binder->addSheet(new VettedBinderSheet('Two'));
        $this->seed($binder);
        $binder = $this->reload($binder);

        $schema = $this->host->mapping(Repeater::make('sheets')->relationship()->components([TextInput::make('heading')]))->record($binder);
        $schema->fill($binder);
        $this->host->data['sheets']['added'] = ['heading' => 'Three'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.sheets' => ['A binder holds two sheets at most.'],
                'data' => ['A sheet cannot stay a draft.'],
            ]);
        })
            ->and($this->host->getErrorsForSchemaStatePath('data'))->toBe(['A sheet cannot stay a draft.']);
    });
});

describe('the validation identity', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();

        $this->schema = $this->host->mapping(
            TextInput::make('code')->required(),
            Section::make()->key('details', isInheritable: false)->components([TextInput::make('city')->required()]),
        );
        $details = $this->host->getSchemaComponent('schema.details');
        $this->details = $details instanceof Component ? $details->getChildSchema() : null;
    });

    it("files a component's child schema under that component, leaving the root schema's errors alone", function (): void {
        expect(fn () => $this->host->validateSchema($this->schema))->toThrow(ValidationException::class);

        $this->host->data['city'] = 'Prague';
        $this->host->validateSchema($this->details);

        expect($this->host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value should not be blank.']);
    });

    it("drops a component's child schema errors when its root schema validates", function (): void {
        expect(fn () => $this->host->validateSchema($this->details))->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.city'))->toBe(['This value should not be blank.']);

        $this->host->data = ['code' => 'X1', 'city' => 'Prague'];
        $this->host->validateSchema($this->schema);

        expect($this->host->getErrorsForSchemaStatePath('data.city'))->toBe([]);
    });

    it('reads a message two schemas hold for one path once, and nothing for no path or an unknown one', function (): void {
        expect(fn () => $this->host->validateSchema($this->schema))->toThrow(ValidationException::class)
            ->and(fn () => $this->host->validateSchema($this->details))->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.city'))->toBe(['This value should not be blank.'])
            ->and($this->host->getErrorConstraintsForSchemaStatePath('data.city'))->toBe([RequiredValue::class])
            ->and($this->host->getErrorsForSchemaStatePath(null))->toBe([])
            ->and($this->host->getErrorsForSchemaStatePath('data.street'))->toBe([])
            ->and($this->host->getErrorConstraintsForSchemaStatePath(null))->toBe([]);
    });

    it("keeps a component's child schema errors across requests until its field is fixed", function (): void {
        $this->createLiveComponent(StickyValidationForm::class)
            ->call('saveDetails')
            ->fillForm(['title' => 'Harbour'])
            ->assertHasFormErrors(['city'])
            ->fillForm(['city' => 'Prague'])
            ->assertHasNoFormErrors();
    });

    it("forgets a child schema's errors once its component leaves the form, so they stay gone when it returns", function (): void {
        $this->createLiveComponent(StickyValidationForm::class)
            ->call('saveDetails')
            ->assertHasFormErrors(['city'])
            ->set('showDetails', false)
            ->set('showDetails', true)
            ->assertHasNoFormErrors();
    });
});
