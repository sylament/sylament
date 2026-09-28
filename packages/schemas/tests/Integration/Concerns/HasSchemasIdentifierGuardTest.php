<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Binder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Callsign;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Headband;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Spine;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Stamped;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/*
 * The record check a schema's getData() ends in, in the order it runs: the
 * changed-key pass puts a moved key back before anything else reads the
 * record, the record's own constraints speak next, the blank-key pass then
 * refuses a blank key field the schema maps, and the taken-key probe runs only
 * when none of them raised anything — so it binds no value the record's own
 * constraints refuse. Each pass's refusals are violations of the record's own
 * validation, translated and filed like the rest.
 */

/**
 * A `data`-bag host validating through `$validator` — the container's unless
 * one is given, none at all when `false` — with the schema's association
 * reader pointed at `$entityManager`, the manager the record belongs to.
 *
 * @return array{Schema, StateHostFixture}
 */
function identifierGuardSchema(object $record, EntityManager $entityManager, ValidatorInterface|false|null $validator, Component ...$components): array
{
    $host = new StateHostFixture();

    if ($validator !== false) {
        $host->setSchemaStateValidator($validator ?? test()->getContainer()->get(ValidatorInterface::class));
    }

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);
    $schema->setAssociationReader(new AssociationReader($entityManager));

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>
 */
function identifierGuardErrors(Schema $schema): array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return [];
}

it('refuses a moved key at its field, puts the stored key back and leaves the flush nothing to write', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = IdentifierGuardRig::callsign($entityManager);

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK2';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.code' => ['This value cannot be changed.']])
        ->and($host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value cannot be changed.'])
        ->and($callsign->getCode())->toBe(IdentifierGuardRig::CALLSIGN)
        ->and($recorder->statements())->toBe([]);

    $entityManager->flush();

    expect($recorder->statements())->toBe([]);
});

it('puts the stored key back before the record constraints read it, so one refusing the moved value stays silent', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $callsign = IdentifierGuardRig::callsign($entityManager);

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = Callsign::RESERVED;

    expect(identifierGuardErrors($schema))->toBe(['data.code' => ['This value cannot be changed.']]);
});

it('still reports the record constraints beside a refused key, and probes nothing', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = IdentifierGuardRig::callsign($entityManager);

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK2';
    $host->data['label'] = 'x';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe([
        'data.code' => ['This value cannot be changed.'],
        'data.label' => ['Name the station.'],
    ])->and($recorder->statements())->toBe([]);
});

it('answers a record failing its own constraint with that constraint alone and no SELECT, and its taken key on the next submit', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = new Callsign();

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = IdentifierGuardRig::CALLSIGN;
    $host->data['label'] = 'x';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.label' => ['Name the station.']])
        ->and($recorder->statements())->toBe([]);

    $host->data['label'] = 'Station two';

    expect(identifierGuardErrors($schema))->toBe(['data.code' => ['This value is already used.']])
        ->and($host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value is already used.'])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.label AS label_2 FROM Callsign t0 WHERE t0.code = ?']);
});

it('maps a new record on a free key after one primary-key SELECT', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = new Callsign();

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK9';
    $host->data['label'] = 'Station nine';
    $recorder->reset();

    expect($schema->getData())->toBe($callsign)
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.label AS label_2 FROM Callsign t0 WHERE t0.code = ?']);
});

it('lists a message once per path however many constraints raise it there', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $callsign = new Callsign();

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, null, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK9';
    $host->data['label'] = 'x';

    expect(identifierGuardErrors($schema))->toBe(['data.label' => ['Name the station.']])
        ->and($host->getErrorsForSchemaStatePath('data.label'))->toBe(['Name the station.']);
});

it('raises both refusals through the validator translator, in the validators domain', function (): void {
    $translator = new class implements TranslatorInterface {
        /** @var list<string> */
        public array $domains = [];

        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            $this->domains[] = $domain ?? '';

            return match ($id) {
                'This value cannot be changed.' => 'Táto hodnota sa nedá zmeniť.',
                'This value is already used.' => 'Táto hodnota sa už používa.',
                default => strtr($id, $parameters),
            };
        }

        public function getLocale(): string
        {
            return 'sk';
        }
    };

    $validator = Validation::createValidatorBuilder()
        ->enableAttributeMapping()
        ->setTranslator($translator)
        ->setTranslationDomain('validators')
        ->getValidator();

    $entityManager = IdentifierGuardRig::create();

    [$moved, $movedHost] = identifierGuardSchema(IdentifierGuardRig::callsign($entityManager), $entityManager, $validator, StateField::make('code'), StateField::make('label'));
    [$taken, $takenHost] = identifierGuardSchema(new Callsign(), $entityManager, $validator, StateField::make('code'), StateField::make('label'));

    $movedHost->data['code'] = 'OK2';
    $takenHost->data['code'] = IdentifierGuardRig::CALLSIGN;
    $takenHost->data['label'] = 'Station two';

    expect(identifierGuardErrors($moved))->toBe(['data.code' => ['Táto hodnota sa nedá zmeniť.']])
        ->and(identifierGuardErrors($taken))->toBe(['data.code' => ['Táto hodnota sa už používa.']])
        ->and(array_unique($translator->domains))->toBe(['validators']);
});

it('refuses and puts back nothing on a host with no validator', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $callsign = IdentifierGuardRig::callsign($entityManager);

    [$schema, $host] = identifierGuardSchema($callsign, $entityManager, false, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK2';

    expect($schema->getData())->toBe($callsign)
        ->and($callsign->getCode())->toBe('OK2');
});

it('reaches the container reader through the schema the container builds', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $callsign = new Callsign();

    $callsign->setCode(IdentifierGuardRig::CALLSIGN);
    $callsign->setLabel('Station one');

    $entityManager->persist($callsign);
    $entityManager->flush();

    $host = new StateHostFixture();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($callsign)->components([StateField::make('code'), StateField::make('label')]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($callsign);
    $host->data['code'] = 'OK2';

    expect($schema->getAssociationReader())->toBe($this::getContainer()->get(AssociationReader::class))
        ->and(identifierGuardErrors($schema))->toBe(['data.code' => ['This value cannot be changed.']])
        ->and($callsign->getCode())->toBe(IdentifierGuardRig::CALLSIGN);
});

it('guards nothing on a schema built outside the container', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $callsign = new Callsign();

    $callsign->setCode(IdentifierGuardRig::CALLSIGN);
    $callsign->setLabel('Station one');

    $entityManager->persist($callsign);
    $entityManager->flush();

    $host = new StateHostFixture();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = new Schema()->liveComponent($host)->statePath('data')->record($callsign)->components([StateField::make('code'), StateField::make('label')]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($callsign);
    $host->data['code'] = 'OK2';

    expect($schema->getAssociationReader()->hasEntityManager())->toBeFalse()
        ->and($schema->getData())->toBe($callsign)
        ->and($callsign->getCode())->toBe('OK2');
});

it('refuses a dotted field moving the key of a loaded related record at that field', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = IdentifierGuardRig::binder($entityManager, 1);

    [$schema, $host] = identifierGuardSchema($binder, $entityManager, null, StateField::make('title'), StateField::make('spine.label'), StateField::make('spine.code'));

    $host->data['spine']['code'] = 'SP-9';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.spine.code' => ['This value cannot be changed.']])
        ->and($binder->getSpine()?->getCode())->toBe(IdentifierGuardRig::SPINE);

    $entityManager->flush();

    expect($recorder->statements())->toBe([]);
});

it('leaves a key written into a related record nobody loaded to Doctrine, which drops it', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = IdentifierGuardRig::binder($entityManager, 1);

    [$schema, $host] = identifierGuardSchema($binder, $entityManager, null, StateField::make('title'), StateField::make('spine.code'));

    $spine = $binder->getSpine() ?? throw new LogicException('The rig seeds a spine on the first binder.');

    expect($entityManager->isUninitializedObject($spine))->toBeTrue();

    $host->data['spine']['code'] = 'SP-9';
    $recorder->reset();

    expect($schema->getData())->toBe($binder)
        ->and($entityManager->isUninitializedObject($spine))->toBeTrue();

    $entityManager->flush();

    expect($recorder->statements())->toBe([]);
});

it('refuses at the schema path a key a hook moves on a related record the form never maps', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $binder = IdentifierGuardRig::binder($entityManager, 1);
    $spine = $binder->getSpine() ?? throw new LogicException('The rig seeds a spine on the first binder.');

    expect($spine->getLabel())->toBe('stored spine');

    [$schema, $host] = identifierGuardSchema(
        $binder,
        $entityManager,
        null,
        StateField::make('title')->saveRelationshipsUsing(static function (Binder $record, string $state): void {
            $record->setTitle($state);
            $record->getSpine()?->setCode('SP-9');
        }),
    );

    $host->data['title'] = 'Retitled';

    expect(identifierGuardErrors($schema))->toBe(['data' => ['This value cannot be changed.']])
        ->and($spine->getCode())->toBe(IdentifierGuardRig::SPINE)
        ->and($binder->getTitle())->toBe('Retitled');
});

it('leaves a blank key no component maps to the create seam, running no lookup', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = identifierGuardSchema(new Redirect(), $entityManager, null, StateField::make('label'));
    $host->data['label'] = 'Orphan';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('files the refusal under an identifier association the schema maps and leaves unset', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = identifierGuardSchema(new Headband(), $entityManager, null, StateField::make('spine'));
    $host->data['spine'] = null;
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.spine' => ['This value should not be blank.']])
        ->and($recorder->statements())->toBe([]);
});

it('files the refusal under an identifier association the schema maps onto a new record whose own key is blank', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    $headband = new Headband();
    $headband->setSpine(new Spine());

    [$schema] = identifierGuardSchema($headband, $entityManager, null, StateField::make('spine'));
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.spine' => ['This value should not be blank.']])
        ->and($recorder->statements())->toBe([]);
});

it('files the blank-key refusal under the key field the schema maps', function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = identifierGuardSchema(new Redirect(), $entityManager, null, StateField::make('host'), StateField::make('path'), StateField::make('label'));
    $host->data['host'] = 'example.com';
    $host->data['path'] = '   ';
    $host->data['label'] = 'Spaced';

    expect(identifierGuardErrors($schema))->toBe(['data.path' => ['This value should not be blank.']]);
});

it('files the refusal under the first blank key field a component maps, past one it does not', function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = identifierGuardSchema(new Redirect(), $entityManager, null, StateField::make('path'), StateField::make('label'));
    $host->data['path'] = '   ';
    $host->data['label'] = 'Hostless';

    expect(identifierGuardErrors($schema))->toBe(['data.path' => ['This value should not be blank.']]);
});

it('files the refusal under the first of two blank key fields the schema maps', function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = identifierGuardSchema(new Redirect(), $entityManager, null, StateField::make('host'), StateField::make('path'), StateField::make('label'));
    $host->data['host'] = '   ';
    $host->data['path'] = '   ';
    $host->data['label'] = 'Keyless';

    expect(identifierGuardErrors($schema))->toBe(['data.host' => ['This value should not be blank.']]);
});

it('files nothing under a filled key field the schema maps when the blank one is unmapped', function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = identifierGuardSchema(new Redirect(), $entityManager, null, StateField::make('host'), StateField::make('label'));
    $host->data['host'] = 'example.com';
    $host->data['label'] = 'Pathless';

    expect(identifierGuardErrors($schema))->toBe([]);
});

it('refuses a blank key field the schema maps on a class whose prePersist callback fills the other half', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = identifierGuardSchema(new Stamped(), $entityManager, null, StateField::make('scope'), StateField::make('label'));
    $host->data['scope'] = '   ';
    $host->data['label'] = 'Spaced';
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.scope' => ['This value should not be blank.']])
        ->and($recorder->statements())->toBe([]);
});

it('leaves the half a prePersist callback fills to the create seam when the schema does not map it', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $stamped = new Stamped();

    [$schema, $host] = identifierGuardSchema($stamped, $entityManager, null, StateField::make('scope'), StateField::make('label'));
    $host->data['scope'] = 'news';
    $host->data['label'] = 'Stamped';
    $recorder->reset();

    expect($schema->getData())->toBe($stamped)
        ->and($stamped->getStamp())->toBe('')
        ->and($recorder->statements())->toBe([]);
});

it("files the refusal under the related record's own key field when the schema maps it through the identifier association", function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = identifierGuardSchema(new Headband(), $entityManager, null, StateField::make('spine.code'), StateField::make('spine.label'));
    $host->data['spine'] = ['code' => '   ', 'label' => 'Spaced'];
    $recorder->reset();

    expect(identifierGuardErrors($schema))->toBe(['data.spine.code' => ['This value should not be blank.']])
        ->and($recorder->statements())->toBe([]);
});

it('leaves a blank single scalar key to the application, as before', function (): void {
    $entityManager = IdentifierGuardRig::create();

    [$schema, $host] = identifierGuardSchema(new Shortlink(), $entityManager, null, StateField::make('target'));
    $host->data['target'] = 'https://example.com';

    expect(identifierGuardErrors($schema))->toBe([]);
});
