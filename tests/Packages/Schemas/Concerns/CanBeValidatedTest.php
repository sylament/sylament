<?php

declare(strict_types=1);

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Symfony\Component\Validator\Validator\ValidatorInterface;

beforeEach(function (): void {
    $this->host = new RecordMappingHost();
    $this->host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));
});

describe('getValidationConstraints()', function (): void {
    it('collects the constraints of every component it validates by absolute state path, through child schemas, skipping one neither dehydrated nor validated', function (): void {
        $schema = Schema::make()->statePath('data')->components([
            TextInput::make('code')->required(),
            TextInput::make('secret')->required()->hidden()->dehydratedWhenHidden(),
            TextInput::make('draft')->required()->dehydrated(false),
            TextInput::make('gone')->required()->hidden(),
            TextInput::make('quiet')->required()->dehydrated(false)->validatedWhenNotDehydrated(false),
            Section::make()->components([TextInput::make('city')->required()]),
        ]);

        expect(array_map(static fn (array $constraints): array => array_map(get_class(...), $constraints), $schema->getValidationConstraints()))->toBe([
            'data.code' => [RequiredValue::class],
            'data.secret' => [RequiredValue::class],
            'data.draft' => [RequiredValue::class],
            'data.city' => [RequiredValue::class],
        ]);
    });
});

describe('validate()', function (): void {
    it('passes a schema with no constraints without asking for a host', function (): void {
        $schema = Schema::make()->statePath('data')->components([TextInput::make('note')]);

        expect($schema->validate())->toBeNull();
    });

    it("has the host validate a schema with constraints, raising the host's ValidationException", function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->required(), TextInput::make('note'));

        expect(fn () => $schema->validate())->toThrow(function (ValidationException $exception): void {
            expect($exception->getStatusCode())->toBe(422)
                ->and($exception->getErrors())->toBe(['data.code' => ['This value should not be blank.']]);
        });
    });
});

describe('validateRecordKey()', function (): void {
    it('hands a taken key to the flush when switched off, asking nothing first', function (): void {
        $this->seed(new OrderLine(order: 1, line: 1, note: 'Stored'));

        $schema = $this->host->mapping(TextInput::make('order'), TextInput::make('line'), TextInput::make('note'))
            ->record($line = new OrderLine(order: 0, line: 0))
            ->validateRecordKey(false);
        $this->host->data = ['order' => '1', 'line' => '1', 'note' => 'Copy'];

        expect($schema->getData())->toBe($line)
            ->and($this->statementsRun())->toBe([]);

        $entityManager = $this->entityManager();
        $entityManager->persist($line);

        expect(fn () => $entityManager->flush())->toThrow(UniqueConstraintViolationException::class)
            ->and($entityManager->isOpen())->toBeFalse();
    });

    it('lets a blank key field through to the create when switched off', function (): void {
        $schema = $this->host->mapping(TextInput::make('pier'), TextInput::make('slot'))
            ->record($mooring = new BerthMooring())
            ->validateRecordKey(false);
        $this->host->data = ['pier' => '   ', 'slot' => 'S1'];

        expect($schema->getData())->toBe($mooring)
            ->and($mooring->slot)->toBe('S1');
    });

    it("still refuses a changed key and answers the record's own constraints when switched off", function (): void {
        $this->seed(new OrderLine(order: 1, line: 1, note: 'Stored'));
        $line = $this->reload(new OrderLine(order: 1, line: 1));

        $moved = $this->host->mapping(TextInput::make('order'), TextInput::make('line'))->record($line)->validateRecordKey(false);
        $moved->fill($line);
        $this->host->data['line'] = '2';

        expect(fn () => $moved->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.line' => ['This value cannot be changed.']]);
        });

        $capped = $this->host->mapping(TextInput::make('code'), TextInput::make('title'))->record(new CappedHeadline())->validateRecordKey(false);
        $this->host->data = ['code' => 'H1', 'title' => 'Far too long'];

        expect(fn () => $capped->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['This value is too long. It should have 5 characters or less.']]);
        });
    });

    it('hands its closure the record the schema is bound to', function (): void {
        $this->seed(new OrderLine(order: 1, line: 1, note: 'Stored'));
        $received = null;

        $schema = $this->host->mapping(TextInput::make('order'), TextInput::make('line'))
            ->record($line = new OrderLine(order: 0, line: 0))
            ->validateRecordKey(function (?object $record) use (&$received): bool {
                $received = $record;

                return false;
            });
        $this->host->data = ['order' => '1', 'line' => '1'];

        expect($schema->getData())->toBe($line)
            ->and($received)->toBe($line);
    });

    it('takes the switch a Schema::configureUsing() registration sets as the default of every schema made', function (): void {
        $this->seed(new OrderLine(order: 1, line: 1, note: 'Stored'));
        Schema::configureUsing(fn (Schema $schema): Schema => $schema->validateRecordKey(false));

        $schema = $this->host->mapping(TextInput::make('order'), TextInput::make('line'))->record($line = new OrderLine(order: 0, line: 0));
        $this->host->data = ['order' => '1', 'line' => '1'];

        expect($schema->getData())->toBe($line)
            ->and($this->statementsRun())->toBe([]);
    });
});
