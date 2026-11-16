<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\BoundVolume;
use Sylament\Tests\Fixtures\Entity\HostPathAlias;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\ComponentSeamForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\ReactiveSchemaForm;
use Sylament\Tests\Fixtures\Schemas\RecordCheckHost;
use Sylament\Tests\Fixtures\Schemas\SchemaResolutionHost;
use Sylament\Tests\Fixtures\Schemas\ScopedValidationForm;
use Sylament\Tests\Fixtures\Schemas\UnnamedSchemaHost;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        'a member of the host contract' => ['makeSchema.title'],
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
    it("files each failing path's message, announces the failure and throws, and a passing run drops the errors and the stickiness", function (): void {
        $tester = $this->createLiveComponent(ScopedValidationForm::class)
            ->call('save')
            ->assertHasFormErrors(['code' => 'This value should not be blank.', 'city']);

        expect($this->dispatchedBrowserEvents($tester, 'form-validation-error'))->toBe([['schemaKey' => 'form']]);

        $tester->fillForm(['code' => 'X1', 'city' => 'Prague'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['code' => ''])
            ->assertHasNoFormErrors();
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
        $this->host = new RecordCheckHost();
        $this->host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));
    });

    it('files a blank key refusal under the first blank key field a component maps, past one it does not', function (): void {
        $schema = $this->host->check(new HostPathAlias(), TextInput::make('path'), TextInput::make('label'));
        $this->host->data = ['path' => '   ', 'label' => 'Hostless'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.path' => ['This value should not be blank.']]);
        })
            ->and($this->host->getErrorsForSchemaStatePath('data.path'))->toBe(['This value should not be blank.']);
    });

    it('leaves a blank key no component maps to the create that persists the record, running no statement', function (): void {
        $alias = new HostPathAlias();
        $schema = $this->host->check($alias, TextInput::make('label'));
        $this->host->data = ['label' => 'Orphan'];

        expect($schema->getData())->toBe($alias)
            ->and($alias->label)->toBe('Orphan')
            ->and($this->statementsRun())->toBe([]);
    });

    it('refuses a blank key field a component maps on a record whose prePersist callback fills the other half', function (): void {
        $schema = $this->host->check(new StampedBulletin(), TextInput::make('scope'), TextInput::make('label'));
        $this->host->data = ['scope' => '   ', 'label' => 'Spaced'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.scope' => ['This value should not be blank.']]);
        });
    });

    it("files a related record's blank key refusal at the schema's own path when only a layout component entangling it maps the key", function (): void {
        $schema = $this->host->check(new BoundVolume(), Group::make([TextInput::make('label')])->relationship('spine'));
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

        $schema = $this->host->check($bench, TextInput::make('name'), Repeater::make('pots')->relationship()->components([TextInput::make('bay'), TextInput::make('plant')]));
        $itemKey = array_key_first($this->host->data['pots']);
        $this->host->data['name'] = 'North';
        $this->host->data['pots'][$itemKey]['bay'] = 'north';
        $this->host->data['pots'][$itemKey]['plant'] = 'thyme';

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception) use ($itemKey): void {
            expect($exception->getErrors())->toBe(["data.pots.{$itemKey}.bay" => ['This value is already used.']]);
        });
    });
});
