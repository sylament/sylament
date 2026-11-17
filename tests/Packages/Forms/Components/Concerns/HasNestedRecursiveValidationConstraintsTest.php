<?php

declare(strict_types=1);

use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Concerns\HasNestedRecursiveValidationConstraints;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Infrastructure\ComponentRegistry;
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

    expect($this->host->validateSchema($schema))->toBeNull();
});

it('checks the nested constraints on every component that offers them, each holding a list as its user configures it', function (string $field): void {
    $component = $field::make('codes')->nestedRecursiveConstraint(new Length(max: 2));

    if (method_exists($component, 'options')) {
        $component->options(['ab' => 'AB', 'abc' => 'ABC']);
    }

    if (method_exists($component, 'multiple')) {
        $component->multiple();
    }

    $schema = $this->host->mapping($component);
    $this->host->data = ['codes' => ['abc']];

    expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
        expect($exception->getErrors())->toBe(['data.codes' => ['This value is too long. It should have 2 characters or less.']]);
    });

    $this->host->data = ['codes' => ['ab']];

    expect($this->host->validateSchema($schema))->toBeNull();
})->with(fn (): array => ComponentRegistry::componentsUsing(HasNestedRecursiveValidationConstraints::class));

it('checks nothing nested over a value that is no list, leaving the type refusal alone', function (): void {
    $schema = $this->host->mapping(CheckboxList::make('codes')->options(['ab' => 'AB'])->nestedRecursiveConstraint(new Length(max: 2)));
    $this->host->data = ['codes' => 'abc'];

    expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
        expect($exception->getErrors())->toBe(['data.codes' => ['This value should be of type array.']]);
    });
});
