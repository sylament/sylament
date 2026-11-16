<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\ReactiveSchemaForm;
use Sylament\Tests\Fixtures\Schemas\SchemaResolutionHost;
use Sylament\Tests\Fixtures\Schemas\UnnamedSchemaHost;

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
