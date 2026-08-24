<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use DateTimeImmutable;
use LogicException;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Forms\Tests\Fixtures\Components\FixtureLengthField;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\ValidationStatus;
use Sylament\Forms\Validation\DateComparison;
use Sylament\Forms\Validation\MatchesField;
use Sylament\Forms\Validation\Prohibited;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use Twig\Markup;

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

/**
 * Wire a field beside a sibling under a `data`-bag host holding the sibling's
 * raw state, which is the frame a date comparison against a field name reads.
 *
 * @param array<string, mixed> $data
 */
function wireFieldToSibling(Field $field, array $data): Field
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)
        ->statePath('data')
        ->components([FixtureField::make('startsAt'), $field])
        ->getComponents();

    return $field;
}

/**
 * @return list<DateComparison>
 */
function dateComparisons(Field $field): array
{
    return array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (object $constraint): bool => $constraint instanceof DateComparison,
    ));
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

it('does not derive from a field-tier record() binding alone', function (): void {
    expect(wireField(FixtureField::make('title')->record(Article::class))->isRequired())->toBeFalse();
});

it('is not required when no record is bound', function (): void {
    expect(FixtureField::make('title')->isRequired())->toBeFalse()
        ->and(wireField(FixtureField::make('title'))->isRequired())->toBeFalse();
});

it('derives nothing for a field no schema contains', function (): void {
    expect(FixtureField::make('title')->record(Article::class)->isRequired())->toBeFalse();
});

it('builds a date comparison from a literal boundary', function (): void {
    $constraints = dateComparisons(FixtureField::make('endsAt')->after('2026-01-01'));

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0]->operator)->toBe(DateComparison::AFTER)
        ->and($constraints[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-01-01 00:00:00');
});

it('evaluates a closure boundary', function (): void {
    $constraints = dateComparisons(FixtureField::make('endsAt')->after(fn (): string => '2026-01-01'));

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-01-01 00:00:00');
});

it('builds each operator of the date comparison family', function (): void {
    expect(dateComparisons(FixtureField::make('endsAt')->afterOrEqual('2026-01-01'))[0]->operator)
        ->toBe(DateComparison::AFTER_OR_EQUAL)
        ->and(dateComparisons(FixtureField::make('endsAt')->before('2026-01-01'))[0]->operator)
        ->toBe(DateComparison::BEFORE)
        ->and(dateComparisons(FixtureField::make('endsAt')->beforeOrEqual('2026-01-01'))[0]->operator)
        ->toBe(DateComparison::BEFORE_OR_EQUAL);
});

it('takes a relative literal boundary', function (): void {
    $constraints = dateComparisons(FixtureField::make('endsAt')->before('tomorrow'));

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0]->boundary)->toBeGreaterThan(new DateTimeImmutable('today'));
});

it('skips a blank boundary', function (): void {
    expect(dateComparisons(FixtureField::make('endsAt')->after('')))->toBe([])
        ->and(dateComparisons(FixtureField::make('endsAt')->after(fn (): ?string => null)))->toBe([]);
});

it('skips a field-name boundary with no live component to read it from', function (): void {
    expect(dateComparisons(FixtureField::make('endsAt')->after('startsAt')))->toBe([]);
});

it('reads a field-name boundary out of the sibling live state', function (): void {
    $field = wireFieldToSibling(
        FixtureField::make('endsAt')->after('startsAt'),
        ['startsAt' => '2026-08-18T10:00'],
    );

    $constraints = dateComparisons($field);

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-08-18 10:00:00');
});

it('reads an absolute state path verbatim', function (): void {
    $field = wireFieldToSibling(
        FixtureField::make('endsAt')->after('data.startsAt', isStatePathAbsolute: true),
        ['startsAt' => '2026-08-18T10:00'],
    );

    expect(dateComparisons($field)[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-08-18 10:00:00');
});

it('skips a field-name boundary whose sibling is blank', function (): void {
    $field = wireFieldToSibling(
        FixtureField::make('endsAt')->after('startsAt'),
        ['startsAt' => ''],
    );

    expect(dateComparisons($field))->toBe([]);
});

it('skips a field-name boundary the sibling state cannot parse', function (): void {
    $field = wireFieldToSibling(
        FixtureField::make('endsAt')->after('startsAt'),
        ['startsAt' => 'banana'],
    );

    expect(dateComparisons($field))->toBe([]);
});

it('rebuilds the boundary on every run, so the sibling can change', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['startsAt' => '2026-08-18T10:00'];

    $field = FixtureField::make('endsAt')->after('startsAt');

    Schema::make($host)
        ->statePath('data')
        ->components([FixtureField::make('startsAt'), $field])
        ->getComponents();

    $host->data = ['startsAt' => '2026-09-01T08:00'];

    expect(dateComparisons($field)[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-09-01 08:00:00');
});

/**
 * Wire a field beside one sibling per key of `$data`, under a `data`-bag host
 * holding that state — the frame every cross-field arm reads.
 *
 * @param array<string, mixed> $data
 */
function wireFieldToSiblings(Field $field, array $data, ?FieldRenderHost $host = null): Field
{
    $host ??= new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)
        ->statePath('data')
        ->components([...array_map(FixtureField::make(...), array_keys($data)), $field])
        ->getComponents();

    return $field;
}

/**
 * @template TConstraint of Constraint
 *
 * @param  class-string<TConstraint> $class
 * @return list<TConstraint>
 */
function constraintsOf(Field $field, string $class): array
{
    return array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof $class,
    ));
}

it('bakes the sibling value into an ordering constraint', function (): void {
    $field = wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => '9']);

    expect(constraintsOf($field, GreaterThan::class))->toHaveCount(1)
        ->and(constraintsOf($field, GreaterThan::class)[0]->value)->toBe('9');
});

it('drops an ordering constraint whose sibling is blank, null or unreadable', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => '']), GreaterThan::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => null]), GreaterThan::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gt('nope'), ['other' => '9']), GreaterThan::class))->toBe([]);
});

it('emits no cross-field constraint for a field with no live host', function (): void {
    expect(FixtureField::make('a')->gt('other')->getValidationConstraints())->toBe([])
        ->and(FixtureField::make('a')->same('other')->getValidationConstraints())->toBe([])
        ->and(FixtureField::make('a')->requiredIf('other', 'x')->getValidationConstraints())->toBe([])
        ->and(FixtureField::make('a')->distinct()->getValidationConstraints())->toBe([]);
});

it('maps each ordering arm onto its stock constraint', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gte('other'), ['other' => '9']), GreaterThanOrEqual::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->lt('other'), ['other' => '9']), LessThan::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->lte('other'), ['other' => '9']), LessThanOrEqual::class))->toHaveCount(1);
});

it('compares two numeric strings numerically through the live validator', function (): void {
    $constraints = wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => '9'])->getValidationConstraints();
    $tooSmall = wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => '11'])->getValidationConstraints();

    expect(Validation::createValidator()->validate('10', $constraints))->toHaveCount(0)
        ->and(Validation::createValidator()->validate('10', $tooSmall))->toHaveCount(1);
});

it('resolves an absolute sibling path in both spellings', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gt('/data.other'), ['other' => '9']), GreaterThan::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->gt('data.other', isStatePathAbsolute: true), ['other' => '9']), GreaterThan::class))->toHaveCount(1);
});

it('re-reads the sibling on every build', function (): void {
    $host = new FieldRenderHost();
    $field = wireFieldToSiblings(FixtureField::make('a')->gt('other'), ['other' => '9'], $host);

    $host->data = ['other' => '99'];

    expect(constraintsOf($field, GreaterThan::class)[0]->value)->toBe('99');
});

it('compares a sibling by identity, naming it rather than printing it', function (): void {
    $matches = constraintsOf(wireFieldToSiblings(FixtureField::make('a')->same('other'), ['other' => 'x']), MatchesField::class);

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->value)->toBe('x')
        ->and($matches[0]->other)->toBe('Other')
        ->and($matches[0]->match)->toBeTrue();
});

it('compares a blank or null sibling rather than skipping it', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->same('other'), ['other' => '']), MatchesField::class)[0]->value)->toBe('')
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->same('other'), ['other' => null]), MatchesField::class)[0]->value)->toBeNull();
});

it('strips tags from a Markup sibling label', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['other' => 'x'];

    $field = FixtureField::make('a')->same('other');

    Schema::make($host)
        ->statePath('data')
        ->components([FixtureField::make('other')->label(new Markup('<em>Other</em>', 'UTF-8')), $field])
        ->getComponents();

    expect(constraintsOf($field, MatchesField::class)[0]->other)->toBe('Other');
});

it('inverts under different()', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->different('other'), ['other' => 'x']), MatchesField::class)[0]->match)->toBeFalse();
});

it('reads the confirmation partner at the snake-cased suffix', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['password_confirmation' => 'pw'];

    $field = FixtureField::make('password')->confirmed();

    Schema::make($host)
        ->statePath('data')
        ->components([FixtureField::make('password_confirmation'), $field])
        ->getComponents();

    expect(constraintsOf($field, MatchesField::class)[0]->value)->toBe('pw')
        ->and(constraintsOf($field, MatchesField::class)[0]->other)->toBe('Password confirmation');
});

it('falls back to the humanised path segment when no component answers to the partner path', function (): void {
    $field = wireFieldToSiblings(FixtureField::make('password')->confirmed(), ['other' => 'x']);
    $matches = constraintsOf($field, MatchesField::class);

    expect($matches[0]->value)->toBeNull()
        ->and($matches[0]->other)->toBe('Password confirmation')
        ->and(Validation::createValidator()->validate('pw', $matches)->get(0)->getMessage())
        ->toBe('This value must match Password confirmation.');
});

it('requires the field when the sibling matches, loosely for scalars', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('status', 'published'), ['status' => 'published']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('status', 'published'), ['status' => 'draft']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('status', ['a', 'b']), ['status' => 'b']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('count', 1), ['count' => '1']), NotBlank::class))->toHaveCount(1);
});

it("lets the sibling's own type decide the strictness, as Laravel does", function (): void {
    // `in_array($sibling, $values, is_bool($sibling) || is_null($sibling))` — the
    // *sibling* picks strict or loose, not the declared value. So a raw `true`
    // sibling is never matched by the string `'1'`, while a `'1'` sibling is
    // matched by `true` under PHP's loose comparison.
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('flag', true), ['flag' => true]), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('flag', '1'), ['flag' => true]), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('flag', true), ['flag' => '1']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('flag', null), ['flag' => null]), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('flag', ''), ['flag' => null]), NotBlank::class))->toBe([]);
});

it('unwraps a backed enum in the matched values', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIf('status', ValidationStatus::Published), ['status' => 'published']), NotBlank::class))->toHaveCount(1);
});

it('requires the field when the sibling is accepted or declined', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfAccepted('terms'), ['terms' => 'on']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfAccepted('terms'), ['terms' => true]), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfAccepted('terms'), ['terms' => '1']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfAccepted('terms'), ['terms' => 'off']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfDeclined('terms'), ['terms' => 'off']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredIfDeclined('terms'), ['terms' => 'on']), NotBlank::class))->toBe([]);
});

it('requires the field unless the sibling matches, an absent sibling included', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredUnless('status', 'draft'), ['status' => 'published']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredUnless('status', 'draft'), ['status' => 'draft']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredUnless('status', 'draft'), ['status' => null]), NotBlank::class))->toHaveCount(1);
});

it('requires the field with, with all, without and without all', function (): void {
    expect(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWith('x,y'), ['x' => 'set', 'y' => '']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWith('x,y'), ['x' => '', 'y' => '']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithAll(['x', 'y']), ['x' => 'set', 'y' => '']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithAll(['x', 'y']), ['x' => 'set', 'y' => 'set']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithout('x'), ['x' => '']), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithout('x'), ['x' => 'set']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithoutAll(['x', 'y']), ['x' => '', 'y' => 'set']), NotBlank::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->requiredWithoutAll(['x', 'y']), ['x' => '', 'y' => '']), NotBlank::class))->toHaveCount(1);
});

it("emits the field's own required constraint, so a boolean field keeps NotNull", function (): void {
    $field = wireFieldToSiblings(Toggle::make('a')->requiredIf('status', 'published'), ['status' => 'published']);

    expect(constraintsOf($field, NotNull::class))->toHaveCount(1)
        ->and(constraintsOf($field, NotBlank::class))->toBe([]);
});

it('emits no second required constraint for a field that is already required', function (): void {
    $field = wireFieldToSiblings(FixtureField::make('a')->required()->requiredIf('status', 'published'), ['status' => 'published']);

    expect(constraintsOf($field, NotBlank::class))->toHaveCount(1);
});

it('never marks the field required, so no asterisk appears', function (): void {
    $field = wireFieldToSiblings(FixtureField::make('a')->requiredIf('status', 'published'), ['status' => 'published']);

    expect($field->isRequired())->toBeFalse()
        ->and($field->isMarkedAsRequired())->toBeFalse();
});

it('prohibits a value unconditionally and conditionally', function (): void {
    expect(constraintsOf(wireField(FixtureField::make('a')->prohibited()), Prohibited::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->prohibitedIf('status', 'draft'), ['status' => 'draft']), Prohibited::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->prohibitedIf('status', 'draft'), ['status' => 'published']), Prohibited::class))->toBe([])
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->prohibitedUnless('status', 'draft'), ['status' => 'published']), Prohibited::class))->toHaveCount(1)
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->prohibitedUnless('status', 'draft'), ['status' => 'draft']), Prohibited::class))->toBe([]);
});

it('prohibits combining with a filled sibling, naming it in the message', function (): void {
    $field = wireFieldToSiblings(FixtureField::make('a')->prohibits('x, y'), ['x' => 'set', 'y' => '']);
    $prohibited = constraintsOf($field, Prohibited::class);

    expect($prohibited)->toHaveCount(1)
        ->and($prohibited[0]->message)->toBe('This value cannot be combined with X.')
        ->and(constraintsOf(wireFieldToSiblings(FixtureField::make('a')->prohibits('x'), ['x' => '']), Prohibited::class))->toBe([]);
});

it('emits the required constraint under filled()', function (): void {
    expect(constraintsOf(wireField(FixtureField::make('a')->filled()), NotBlank::class))->toHaveCount(1)
        ->and(constraintsOf(wireField(Toggle::make('a')->filled()), NotNull::class))->toHaveCount(1)
        ->and(wireField(FixtureField::make('a')->filled())->isRequired())->toBeFalse();
});

it('emits DivisibleBy under multipleOf, and nothing when the closure resolves to nothing', function (): void {
    expect(constraintsOf(wireField(FixtureField::make('a')->multipleOf(5)), DivisibleBy::class)[0]->value)->toBe(5)
        ->and(constraintsOf(wireField(FixtureField::make('a')->multipleOf(fn (): float => 0.5)), DivisibleBy::class)[0]->value)->toBe(0.5)
        ->and(constraintsOf(wireField(FixtureField::make('a')->multipleOf(fn (): null => null)), DivisibleBy::class))->toBe([]);
});
