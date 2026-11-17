<?php

declare(strict_types=1);

use Sylament\Forms\Components\CheckboxList;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Uuid;

beforeEach(function (): void {
    $this->host = $this->validatingRecordMappingHost();
});

it("checks every element of the field's list against its nested constraints, a list a closure returns included, reporting on the field's own path", function (): void {
    $schema = $this->host->mapping(
        CheckboxList::make('codes')
            ->options(['ab' => 'AB', 'abc' => 'ABC', 'x1' => 'X1'])
            ->nestedRecursiveConstraint(new Length(max: 2))
            ->nestedRecursiveConstraints(static fn (): array => [new Regex('/^[a-z]+$/')])
            ->nestedRecursiveConstraints([new Uuid()], false),
    );
    $this->host->data = ['codes' => ['abc', 'x1']];

    expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
        expect($exception->getErrors())->toBe(['data.codes' => [
            'This value is too long. It should have 2 characters or less.',
            'This value is not valid.',
        ]]);
    });

    $this->host->data = ['codes' => ['ab']];

    expect(fn () => $this->host->validateSchema($schema))->not->toThrow(ValidationException::class);
});

it('checks nothing nested over a value that is no list, leaving the type refusal alone', function (): void {
    $schema = $this->host->mapping(CheckboxList::make('codes')->options(['ab' => 'AB'])->nestedRecursiveConstraint(new Length(max: 2)));
    $this->host->data = ['codes' => 'abc'];

    expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
        expect($exception->getErrors())->toBe(['data.codes' => ['This value should be of type array.']]);
    });
});
