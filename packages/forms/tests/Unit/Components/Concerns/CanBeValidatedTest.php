<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use LogicException;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Forms\Tests\Fixtures\Components\FixtureLengthField;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

use function Sylament\Forms\Tests\wireField;

/**
 * Wire a field under a schema that carries the record, which is the frame
 * derivation reads: the class comes from the root container, so `wireField()`'s
 * record-less schema plus a field-tier `record()` derives nothing.
 */
function wireFieldToRecord(Field $field): Field
{
    Schema::make(new FieldRenderHost())->record(Article::class)->components([$field])->getComponents();

    return $field;
}

it('is not required by default and builds no constraints', function (): void {
    $field = FixtureField::make('title');

    expect($field->isRequired())->toBeFalse()
        ->and($field->getValidationConstraints())->toBe([]);
});

it('builds NotBlank when required', function (): void {
    $constraints = FixtureField::make('title')->required()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
        ->and($constraints[0]->message)->toBe('This value should not be blank.');
});

it('inverts required through nullable()', function (): void {
    expect(FixtureField::make('title')->required()->nullable()->isRequired())->toBeFalse()
        ->and(FixtureField::make('title')->nullable(false)->isRequired())->toBeTrue();
});

it('evaluates a closure required condition', function (): void {
    expect(FixtureField::make('title')->required(fn (): bool => true)->isRequired())->toBeTrue()
        ->and(FixtureField::make('title')->required(fn (): bool => false)->isRequired())->toBeFalse();
});

it('appends escape-hatch constraints behind their condition', function (): void {
    $field = FixtureField::make('slug')
        ->constraint(new Regex('/^[a-z-]+$/'))
        ->constraint(new NotBlank(), condition: false);

    $constraints = $field->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Regex::class);
});

it('evaluates closure constraints and flattens arrays', function (): void {
    $field = FixtureField::make('slug')->constraints(fn (): array => [new NotBlank(), new Regex('/^[a-z-]+$/')]);

    expect($field->getValidationConstraints())->toHaveCount(2);
});

it('skips a closure constraint that returns null', function (): void {
    $field = FixtureField::make('slug')
        ->constraint(fn (): ?Regex => null)
        ->constraint(new NotBlank());

    $constraints = $field->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class);
});

it('evaluates a closure constraint returning a single constraint', function (): void {
    $field = FixtureField::make('slug')->constraint(fn (): NotBlank => new NotBlank());

    expect($field->getValidationConstraints())->toHaveCount(1)
        ->and($field->getValidationConstraints()[0])->toBeInstanceOf(NotBlank::class);
});

it('accepts a constraint list in constraints()', function (): void {
    $field = FixtureField::make('slug')->constraints([new NotBlank(), new Regex('/^[a-z-]+$/')], condition: false);

    expect($field->getValidationConstraints())->toBe([]);
});

it('applies a string message to the constraint message option', function (): void {
    $constraints = FixtureField::make('title')
        ->required()
        ->validationMessages([NotBlank::class => 'Give it a title.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('Give it a title.');
});

it('applies each named option of a multi-message constraint', function (): void {
    $constraints = FixtureLengthField::make('title')
        ->length(10)
        ->validationMessages([Length::class => ['exactMessage' => 'Exactly ten.']])
        ->getValidationConstraints();

    expect($constraints[0]->exactMessage)->toBe('Exactly ten.');
});

it('applies one option map to every instance of that constraint class', function (): void {
    $constraints = FixtureLengthField::make('title')
        ->maxLength(255)
        ->minLength(3)
        ->validationMessages([Length::class => ['maxMessage' => 'Too long.', 'minMessage' => 'Too short.']])
        ->getValidationConstraints();

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0]->maxMessage)->toBe('Too long.')
        ->and($constraints[0]->minMessage)->toBe('Too short.')
        ->and($constraints[1]->maxMessage)->toBe('Too long.')
        ->and($constraints[1]->minMessage)->toBe('Too short.');
});

it('evaluates a closure message and a closure leaf of an option map', function (): void {
    $constraints = FixtureLengthField::make('title')
        ->required()
        ->maxLength(255)
        ->validationMessages([
            NotBlank::class => fn (): string => 'Give it a title.',
            Length::class => ['maxMessage' => fn (): string => 'Too long.'],
        ])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('Give it a title.')
        ->and($constraints[1]->maxMessage)->toBe('Too long.');
});

it('leaves the constraint default in place for a blank message', function (): void {
    $constraints = FixtureField::make('title')
        ->required()
        ->validationMessages([NotBlank::class => ''])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('This value should not be blank.');
});

it('drops only the blank leaf of an option map', function (): void {
    $constraints = FixtureLengthField::make('title')
        ->maxLength(255)
        ->minLength(3)
        ->validationMessages([Length::class => ['maxMessage' => 'Too long.', 'minMessage' => '']])
        ->getValidationConstraints();

    expect($constraints[0]->maxMessage)->toBe('Too long.')
        ->and($constraints[1]->minMessage)->toBe(new Length(min: 3)->minMessage);
});

it('messages an escape-hatch constraint', function (): void {
    $constraints = FixtureField::make('slug')
        ->constraint(new Regex('/^[a-z-]+$/'))
        ->validationMessages([Regex::class => 'Lowercase and dashes only.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('Lowercase and dashes only.');
});

it('does not mutate a user-supplied constraint instance', function (): void {
    $regex = new Regex('/^[a-z-]+$/');

    $constraints = FixtureField::make('slug')
        ->constraint($regex)
        ->validationMessages([Regex::class => 'Lowercase and dashes only.'])
        ->getValidationConstraints();

    expect($constraints[0])->not->toBe($regex)
        ->and($regex->message)->toBe('This value is not valid.');
});

it('returns the same constraint instance when no message applies to it', function (): void {
    $regex = new Regex('/^[a-z-]+$/');

    $constraints = FixtureField::make('slug')
        ->constraint($regex)
        ->validationMessages([NotBlank::class => 'Give it a title.'])
        ->getValidationConstraints();

    expect($constraints[0])->toBe($regex);
});

it('does not apply a parent-class key to a subclass constraint', function (): void {
    $constraints = FixtureField::make('quantity')
        ->constraint(new Positive())
        ->validationMessages([GreaterThan::class => 'Must be positive.'])
        ->getValidationConstraints();

    expect($constraints[0])->toBeInstanceOf(Positive::class)
        ->and($constraints[0]->message)->not->toBe('Must be positive.');
});

it('ignores a message keyed to a constraint the field does not build', function (): void {
    $constraints = FixtureField::make('title')
        ->required()
        ->validationMessages([Email::class => 'Not an address.'])
        ->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
        ->and($constraints[0]->message)->toBe('This value should not be blank.');
});

it('ignores a message keyed to a constraint excluded by a false condition', function (): void {
    $constraints = FixtureField::make('slug')
        ->constraint(new Regex('/^[a-z-]+$/'), condition: false)
        ->validationMessages([Regex::class => 'Lowercase and dashes only.'])
        ->getValidationConstraints();

    expect($constraints)->toBe([]);
});

it('throws for a string message on a constraint with no message option', function (): void {
    expect(fn (): array => wireField(FixtureLengthField::make('title')
        ->maxLength(255)
        ->validationMessages([Length::class => 'Too long.']))
        ->getValidationConstraints())
        ->toThrow(LogicException::class, 'has no [message] message option');
});

it('throws for an unknown option name and lists the real ones', function (): void {
    expect(fn (): array => wireField(FixtureLengthField::make('title')
        ->maxLength(255)
        ->validationMessages([Length::class => ['maxMesage' => 'Too long.']]))
        ->getValidationConstraints())
        ->toThrow(LogicException::class, '[maxMessage]');
});

it('throws rather than writing a real but non-message property', function (): void {
    expect(fn (): array => wireField(FixtureLengthField::make('title')
        ->maxLength(255)
        ->validationMessages([Length::class => ['max' => '5']]))
        ->getValidationConstraints())
        ->toThrow(LogicException::class, 'has no [max] message option');
});

it('appends the length constraints of a length-constrained field', function (): void {
    $constraints = FixtureLengthField::make('title')->required()->maxLength(255)->getValidationConstraints();

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
        ->and($constraints[1])->toBeInstanceOf(Length::class)
        ->and($constraints[1]->max)->toBe(255);
});

it('keys dehydrated constraints by state path', function (): void {
    $field = wireField(FixtureField::make('title')->required());

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect(array_keys($constraints))->toBe([$field->getStatePath()])
        ->and($constraints['title'])->toHaveCount(1)
        ->and($constraints['title'][0])->toBeInstanceOf(NotBlank::class);
});

it('dehydrates nothing without constraints', function (): void {
    $field = wireField(FixtureField::make('title'));

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect($constraints)->toBe([]);
});

it('dehydrates nothing without a state path', function (): void {
    $field = wireField(FixtureField::make()->required());

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect($constraints)->toBe([]);
});

it('derives required from a non-nullable record property', function (): void {
    expect(wireFieldToRecord(FixtureField::make('title'))->isRequired())->toBeTrue();
});

it('derives required through a nested record path', function (): void {
    expect(wireFieldToRecord(FixtureField::make('author.name'))->isRequired())->toBeTrue();
});

it('builds NotBlank from a derived requirement', function (): void {
    $constraints = wireFieldToRecord(FixtureField::make('title'))->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class);
});

it('derives not-required from a nullable record property', function (): void {
    expect(wireFieldToRecord(FixtureField::make('subtitle'))->isRequired())->toBeFalse();
});

it('derives not-required from a non-nullable collection property', function (): void {
    expect(wireFieldToRecord(FixtureField::make('tags'))->isRequired())->toBeFalse();
});

it('derives not-required from a path the record does not declare', function (): void {
    expect(wireFieldToRecord(FixtureField::make('unknown'))->isRequired())->toBeFalse();
});

it('lets an explicit required(false) beat the derived value', function (): void {
    expect(wireFieldToRecord(FixtureField::make('title')->required(false))->isRequired())->toBeFalse();
});

it('lets an explicit nullable() beat the derived value', function (): void {
    expect(wireFieldToRecord(FixtureField::make('title')->nullable())->isRequired())->toBeFalse();
});

it('is not required when no record is bound', function (): void {
    expect(FixtureField::make('title')->isRequired())->toBeFalse()
        ->and(wireField(FixtureField::make('title'))->isRequired())->toBeFalse();
});

it('derives nothing for a field no schema contains', function (): void {
    expect(FixtureField::make('title')->record(Article::class)->isRequired())->toBeFalse();
});
