<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Field;
use Sylament\Forms\Tests\Fixtures\Components\FixtureNestedField;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @param array<string, mixed> $data
 */
function wireNestedField(Field $field, array $data = []): Field
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    return $field;
}

/**
 * @return list<All>
 */
function allConstraints(Field $field): array
{
    return array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof All,
    ));
}

it('emits one All holding the nested constraints when the raw state is an array', function (): void {
    $field = wireNestedField(FixtureNestedField::make('tags')->nestedRecursiveConstraint(new Length(min: 3)), ['tags' => ['ab', 'abc']]);
    $all = allConstraints($field);

    expect($all)->toHaveCount(1)
        ->and($all[0]->constraints)->toHaveCount(1)
        ->and($all[0]->constraints[0])->toBeInstanceOf(Length::class);
});

it('flattens a closure returning a list', function (): void {
    $field = wireNestedField(
        FixtureNestedField::make('tags')->nestedRecursiveConstraints(fn (): array => [new Length(min: 3), new Length(max: 5)]),
        ['tags' => ['abc']],
    );

    expect(allConstraints($field)[0]->constraints)->toHaveCount(2);
});

it('drops a nested constraint under a false condition', function (): void {
    $field = wireNestedField(FixtureNestedField::make('tags')->nestedRecursiveConstraint(new Length(min: 3), condition: false), ['tags' => ['abc']]);

    expect(allConstraints($field))->toBe([]);
});

it('emits no All when the raw state is not an array', function (): void {
    expect(allConstraints(wireNestedField(FixtureNestedField::make('tags')->nestedRecursiveConstraint(new Length(min: 3)), ['tags' => 'x'])))->toBe([])
        ->and(allConstraints(wireNestedField(FixtureNestedField::make('tags')->nestedRecursiveConstraint(new Length(min: 3)), ['tags' => null])))->toBe([]);
});

it('reaches a nested constraint with a class-keyed validation message', function (): void {
    $field = wireNestedField(
        FixtureNestedField::make('tags')
            ->nestedRecursiveConstraint(new Length(min: 3))
            ->validationMessages([Length::class => ['minMessage' => 'Short.']]),
        ['tags' => ['ab']],
    );

    expect(allConstraints($field)[0]->constraints[0]->minMessage)->toBe('Short.');
});

it("leaves the field's own constraint list unmutated by the message pass", function (): void {
    $length = new Length(min: 3);
    $field = wireNestedField(
        FixtureNestedField::make('tags')
            ->nestedRecursiveConstraint($length)
            ->validationMessages([Length::class => ['minMessage' => 'Short.']]),
        ['tags' => ['ab']],
    );

    allConstraints($field);

    expect($length->minMessage)->not->toBe('Short.');
});
