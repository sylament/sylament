<?php

declare(strict_types=1);

use Sylament\Schemas\State\RecordTarget;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Schemas\Record\Anthology;
use Sylament\Tests\Fixtures\Schemas\Record\Manuscript;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptCatalogEntry;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptObjectKinds;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptSignatures;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptWriter;

describe('describe()', function (): void {
    it("describes the leaf a dotted path reaches by hopping each segment's declared class", function (): void {
        $reader = $this::getContainer()->get(RecordTypeReader::class);

        expect($reader->describe(ManuscriptSignatures::class, 'writer'))->toEqual(new RecordTarget(objectClass: ManuscriptWriter::class, isNullable: true, isCollection: false, isObjectCollection: false))
            ->and($reader->describe(ManuscriptSignatures::class, 'writer.name'))->toEqual(new RecordTarget(objectClass: null, isNullable: true, isCollection: false, isObjectCollection: false, builtinType: 'string'))
            ->and($reader->describe(ManuscriptSignatures::class, 'invoice'))->toEqual(new RecordTarget(objectClass: Invoice::class, isNullable: true, isCollection: false, isObjectCollection: false))
            ->and($reader->describe(ManuscriptSignatures::class, 'invoice.id'))->toEqual(new RecordTarget(objectClass: null, isNullable: false, isCollection: false, isObjectCollection: false, builtinType: 'int'));
    });

    it('answers no type for a segment with no class to hop, a class that does not exist and a property nothing declares', function (string $class, string $path): void {
        expect($this::getContainer()->get(RecordTypeReader::class)->describe($class, $path))->toBeNull();
    })->with([
        'a segment with no class to hop' => [ManuscriptSignatures::class, 'name.first'],
        'a class that does not exist' => ['Sylament\Tests\Fixtures\Schemas\Record\NoSuchManuscript', 'name'],
        'a property nothing declares' => [ManuscriptSignatures::class, 'publisher'],
    ]);
});

describe('describeProperty()', function (): void {
    it('reads a property from its write side, the setter ahead of the getter', function (string $property, RecordTarget $expected): void {
        expect($this::getContainer()->get(RecordTypeReader::class)->describeProperty(ManuscriptSignatures::class, $property))->toEqual($expected);
    })->with([
        'a string on both sides' => ['name', new RecordTarget(objectClass: null, isNullable: false, isCollection: false, isObjectCollection: false, builtinType: 'string')],
        'a nullable string on both sides' => ['bio', new RecordTarget(objectClass: null, isNullable: true, isCollection: false, isObjectCollection: false, builtinType: 'string')],
        'a nullable setter behind a non-nullable getter' => ['writer', new RecordTarget(objectClass: ManuscriptWriter::class, isNullable: true, isCollection: false, isObjectCollection: false)],
        'a non-nullable setter behind a nullable getter' => ['reviewer', new RecordTarget(objectClass: ManuscriptWriter::class, isNullable: false, isCollection: false, isObjectCollection: false)],
        'a native int' => ['pages', new RecordTarget(objectClass: null, isNullable: false, isCollection: false, isObjectCollection: false, builtinType: 'int')],
        'a native bool' => ['published', new RecordTarget(objectClass: null, isNullable: false, isCollection: false, isObjectCollection: false, builtinType: 'bool')],
        'a union' => ['code', new RecordTarget(objectClass: null, isNullable: false, isCollection: false, isObjectCollection: false)],
    ]);
});

describe('objectClass', function (): void {
    it('reports the class to construct for an object segment, and none for one new cannot build', function (string $property, ?string $expected): void {
        expect($this::getContainer()->get(RecordTypeReader::class)->describeProperty(ManuscriptObjectKinds::class, $property)?->objectClass)->toBe($expected);
    })->with([
        'a constructible class' => ['writer', ManuscriptWriter::class],
        'a class whose constructor requires an argument' => ['invoice', Invoice::class],
        'the date interface' => ['happenedAt', DateTimeImmutable::class],
        'the immutable date' => ['immutableAt', DateTimeImmutable::class],
        'the mutable date' => ['mutableAt', DateTime::class],
        'a backed enum' => ['zone', null],
        'an abstract class' => ['revision', null],
        'a class with a private constructor' => ['seal', null],
        'another interface' => ['countable', null],
    ]);
});

describe('collection flags', function (): void {
    it('tells any collection from one whose items are objects', function (string $class, string $property, bool $isCollection, bool $isObjectCollection): void {
        $target = $this::getContainer()->get(RecordTypeReader::class)->describeProperty($class, $property);

        expect([$target?->isCollection, $target?->isObjectCollection])->toBe([$isCollection, $isObjectCollection]);
    })->with([
        'a collection behind an adder and a remover' => [Anthology::class, 'contributors', true, true],
        'a Traversable object' => [Anthology::class, 'index', true, true],
        'a plain array' => [ManuscriptSignatures::class, 'notes', true, false],
        'a nullable array' => [Manuscript::class, 'meta', true, false],
        'an array of objects in a docblock alone' => [Anthology::class, 'drafts', true, false],
        'a scalar' => [ManuscriptSignatures::class, 'pages', false, false],
    ]);
});

describe('service', function (): void {
    it("answers, as the container builds it, from the setter where the promoted constructor and its docblock disagree", function (): void {
        expect($this::getContainer()->get(RecordTypeReader::class)->describeProperty(ManuscriptCatalogEntry::class, 'code'))
            ->toEqual(new RecordTarget(objectClass: null, isNullable: true, isCollection: false, isObjectCollection: false, builtinType: 'int'));
    });
});
