<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Contracts\HasNestedRecursiveValidationConstraints;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Handle;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\Author;
use Sylament\Forms\Validation\Distinct;
use Sylament\Forms\Validation\ExistingValue;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Derived requirement as a whole schema resolves it: real components built by the
 * container, a record bound once on the root schema and inherited down the
 * container chain, and a `nullWhenBlank()` section whose current state decides
 * whether the non-nullable leaves beneath it are required.
 *
 * The constraint-map assertions are the payoff: getData() validates before it
 * maps, so a leaf still carrying NotBlank here could never reach the branch
 * nulling pass.
 */

beforeEach(function (): void {
    // Boot before the first make(): the resolver's static instance is what makes a
    // component container-built, and with it its reader the injected service rather
    // than the container-less fallback.
    $this::getContainer();
});

it('injects the container reader into a field built by make()', function (): void {
    expect(TextInput::make('title')->getRecordTypeReader())
        ->toBe($this::getContainer()->get(RecordTypeReader::class));
});

it('derives required from the record bound on the root schema', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([TextInput::make('title'), TextInput::make('subtitle')]);

    $schema->rawState(['title' => null, 'subtitle' => null]);

    expect($schema->getComponentByStatePath('data.title')->isRequired())->toBeTrue()
        ->and($schema->getComponentByStatePath('data.subtitle')->isRequired())->toBeFalse();
});

it('does not require a non-nullable leaf under a blank nullable branch', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), TextInput::make('handle')]),
        ]);

    $schema->rawState(['title' => 'Hello', 'author' => ['name' => null, 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeFalse()
        ->and($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeFalse()
        ->and(array_keys($schema->getValidationConstraints()))->toBe(['data.title']);
});

it('requires a non-nullable leaf once the branch is filled', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), TextInput::make('handle')]),
        ]);

    $schema->rawState(['title' => 'Hello', 'author' => ['name' => 'Ada', 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeTrue()
        ->and(array_keys($schema->getValidationConstraints()))
        ->toBe(['data.title', 'data.author.name', 'data.author.handle']);
});

it('requires a blank leaf when the branch is not declared nullable', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('ignores a path-less nullWhenBlank() container when suppressing requirement', function (): void {
    // The mapper refuses this declaration outright (it names no property to null),
    // so the only question here is that requirement stops reading the whole bag
    // through it: `title` sits outside the container and must not move `name`.
    $schema = fn (?string $title): Schema => Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->nullWhenBlank()
                ->components([TextInput::make('author.name')]),
        ])
        ->rawState(['title' => $title, 'author' => ['name' => null]]);

    expect($schema(null)->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue()
        ->and($schema('Hello')->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('keeps an explicit required() strict inside a blank nullable branch', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name')->required(), TextInput::make('handle')]),
        ]);

    $schema->rawState(['author' => ['name' => null, 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue()
        ->and($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeFalse();
});

it('suppresses derived requirement when the only non-blank branch value is an untouched checkbox', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), TextInput::make('handle'), Checkbox::make('verified')]),
        ]);

    $schema->rawState(['author' => ['name' => null, 'handle' => null, 'verified' => false]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeFalse();
});

it('resumes derived requirement once the checkbox is ticked', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), Checkbox::make('verified')]),
        ]);

    $schema->rawState(['author' => ['name' => null, 'verified' => true]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('nulls the branch on getData() when only the checkbox holds its resting value', function (): void {
    $article = new Article();

    // getData() validates before it maps, and the checkbox's Type('bool') keeps the
    // constraint map non-empty, so this host needs the validator and the key
    // `validateSchema()` demands — the other tests here stop before that point.
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)
        ->statePath('data')
        ->record($article)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), Checkbox::make('verified')]),
        ]);

    $host->cacheSchema('schema', $schema);

    $schema->rawState(['author' => ['name' => null, 'verified' => false]]);

    expect($schema->getData())->toBe($article)
        ->and($article->getAuthor())->toBeNull();
});

it('derives from the record a mid-tree record() binding puts on the subtree', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->record(Author::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('derives from a mid-tree binding the root record does not stand behind', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->components([
            Section::make('author')
                ->statePath('author')
                ->record(Author::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

/**
 * Wire a repeater whose items hold one field, over positional item keys so a test
 * can name them: `Repeater::fake()` turns uuid generation off for every repeater
 * built while its removal closure is alive.
 *
 * @param array<int, array<string, mixed>> $items
 */
function wireRepeaterItems(Field $field, array $items): Repeater
{
    $host = new FieldRenderHost();
    $host->data = ['tags' => $items];

    $repeater = Repeater::make('tags')->components([$field]);

    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();
    $repeater->hydrateItems();

    return $repeater;
}

/**
 * @return list<Distinct>
 */
function distinctConstraintsAt(Repeater $repeater, string $statePath): array
{
    $field = $repeater->getChildSchema()?->getComponentByStatePath($statePath)
        ?? $repeater->getRootContainer()->getComponentByStatePath($statePath);

    return array_values(array_filter(
        $field?->getValidationConstraints() ?? [],
        static fn (object $constraint): bool => $constraint instanceof Distinct,
    ));
}

it('builds a scalar distinct constraint from the sibling items', function (): void {
    $forget = Repeater::fake();

    $repeater = wireRepeaterItems(TextInput::make('name')->distinct(), [['name' => 'a'], ['name' => 'a'], ['name' => 'b']]);

    $first = distinctConstraintsAt($repeater, 'data.tags.0.name');
    $third = distinctConstraintsAt($repeater, 'data.tags.2.name');

    expect($first)->toHaveCount(1)
        ->and($first[0]->mode)->toBe(Distinct::SCALAR)
        ->and($first[0]->siblingValues)->toBe(['a', 'b'])
        ->and($third[0]->siblingValues)->toBe(['a', 'a']);

    $forget();
});

it('builds a boolean distinct constraint, carrying whether a sibling is ticked', function (): void {
    $forget = Repeater::fake();

    $repeater = wireRepeaterItems(Checkbox::make('primary')->distinct(), [['primary' => true], ['primary' => true], ['primary' => false]]);
    $constraints = distinctConstraintsAt($repeater, 'data.tags.0.primary');

    expect($constraints[0]->mode)->toBe(Distinct::BOOLEAN)
        ->and($constraints[0]->isSiblingSelected)->toBeTrue()
        ->and($constraints[0]->mustBeSelected)->toBeFalse();

    $forget();
});

it('demands a selection when the distinct checkbox is required and nothing is ticked', function (): void {
    $forget = Repeater::fake();

    $repeater = wireRepeaterItems(Checkbox::make('primary')->distinct()->required(), [['primary' => false], ['primary' => false]]);
    $constraints = distinctConstraintsAt($repeater, 'data.tags.0.primary');

    expect($constraints[0]->isSiblingSelected)->toBeFalse()
        ->and($constraints[0]->mustBeSelected)->toBeTrue();

    $forget();
});

it('unions the sibling arrays for a multi-value field', function (): void {
    $forget = Repeater::fake();

    $repeater = wireRepeaterItems(
        CheckboxList::make('roles')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])->distinct(),
        [['roles' => ['a']], ['roles' => ['b']], ['roles' => ['c']]],
    );
    $constraints = distinctConstraintsAt($repeater, 'data.tags.0.roles');

    expect($constraints[0]->mode)->toBe(Distinct::ARRAY)
        ->and($constraints[0]->siblingValues)->toBe(['b', 'c']);

    $forget();
});

it('resolves through a layout component between the item and the field', function (): void {
    $forget = Repeater::fake();

    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a'], ['name' => 'a']]];

    $repeater = Repeater::make('tags')->components([Section::make('Wrap')->components([TextInput::make('name')->distinct()])]);
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();
    $repeater->hydrateItems();

    expect(distinctConstraintsAt($repeater, 'data.tags.0.name')[0]->siblingValues)->toBe(['a']);

    $forget();
});

it('emits nothing for a lone item or a blank own value', function (): void {
    $forget = Repeater::fake();

    expect(distinctConstraintsAt(wireRepeaterItems(TextInput::make('name')->distinct(), [['name' => 'a']]), 'data.tags.0.name'))->toBe([])
        ->and(distinctConstraintsAt(wireRepeaterItems(TextInput::make('name')->distinct(), [['name' => ''], ['name' => 'b']]), 'data.tags.0.name'))->toBe([]);

    $forget();
});

it('emits nothing for a field outside any repeater', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['name' => 'a'];

    $field = TextInput::make('name')->distinct();

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    expect(array_filter($field->getValidationConstraints(), static fn (object $c): bool => $c instanceof Distinct))->toBe([]);
});

/**
 * @return list<Choice>
 */
function choiceConstraints(Field $field): array
{
    return array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof Choice,
    ));
}

it("lets a user in() list replace an option field's derived membership check", function (): void {
    $host = new FieldRenderHost();
    $host->data = ['role' => 'ghost'];

    $select = Select::make('role')->options(['a' => 'A'])->in(['ghost']);
    Schema::make($host)->statePath('data')->components([$select])->getComponents();

    $choices = choiceConstraints($select);

    expect($choices)->toHaveCount(1)
        ->and($choices[0]->choices)->toBe(['ghost'])
        ->and($choices[0]->multiple)->toBeFalse();
});

it('accepts in() on an option-less field, where the derived check would throw', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['role' => 'ghost'];

    $radio = Radio::make('role')->in(['ghost']);
    Schema::make($host)->statePath('data')->components([$radio])->getComponents();

    expect(choiceConstraints($radio))->toHaveCount(1);
});

it('marks the in() Choice multiple on the multi-value fields', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['roles' => ['ghost'], 'tags' => ['ghost'], 'flags' => ['ghost']];

    $list = CheckboxList::make('roles')->options(['a' => 'A'])->in(['ghost']);
    $select = Select::make('tags')->multiple()->options(['a' => 'A'])->in(['ghost']);
    $buttons = ToggleButtons::make('flags')->multiple()->options(['a' => 'A'])->in(['ghost']);

    Schema::make($host)->statePath('data')->components([$list, $select, $buttons])->getComponents();

    expect($list->hasInValidationOnMultipleValues())->toBeTrue()
        ->and(choiceConstraints($list)[0]->multiple)->toBeTrue()
        ->and($select->hasInValidationOnMultipleValues())->toBeTrue()
        ->and(choiceConstraints($select)[0]->multiple)->toBeTrue()
        ->and($buttons->hasInValidationOnMultipleValues())->toBeTrue()
        ->and(choiceConstraints($buttons)[0]->multiple)->toBeTrue();
});

it('implements the nested-constraint contract on the four multi-value consumers', function (): void {
    expect(Select::make('a'))->toBeInstanceOf(HasNestedRecursiveValidationConstraints::class)
        ->and(CheckboxList::make('a'))->toBeInstanceOf(HasNestedRecursiveValidationConstraints::class)
        ->and(ToggleButtons::make('a'))->toBeInstanceOf(HasNestedRecursiveValidationConstraints::class)
        ->and(FileUpload::make('a'))->toBeInstanceOf(HasNestedRecursiveValidationConstraints::class);
});

it("emits a unique constraint against the schema's record class, ignoring the bound record", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $handle = new Handle('ada');
    EntityManagerFactory::seed($entityManager, $handle);
    $persisted = $entityManager->find(Handle::class, $handle->getId());

    $host = new FieldRenderHost();
    $host->data = ['name' => 'ada'];

    $field = TextInput::make('name')->unique();

    Schema::make($host)->statePath('data')->record($persisted)->components([$field])->getComponents();

    $unique = constraintsOfClass($field, UniqueValue::class);

    expect($unique)->toHaveCount(1)
        ->and($unique[0]->entityClass)->toBe(Handle::class)
        ->and($unique[0]->property)->toBe('name')
        ->and($unique[0]->ignoredIdentifier)->toBe((string) $handle->getId());
});

it('ignores nothing for a class-string binding, an unflushed record or an opt-out', function (): void {
    $classString = wireUniqueField(TextInput::make('name')->unique(), Handle::class);
    $fresh = wireUniqueField(TextInput::make('name')->unique(), new Handle('fresh'));
    $optOut = wireUniqueField(TextInput::make('name')->unique(ignoreRecord: false), Handle::class);
    $defaultOff = wireUniqueField(TextInput::make('name')->uniqueValidationIgnoresRecordByDefault(false)->unique(), Handle::class);

    expect(constraintsOfClass($classString, UniqueValue::class)[0]->ignoredIdentifier)->toBeNull()
        ->and(constraintsOfClass($fresh, UniqueValue::class)[0]->ignoredIdentifier)->toBeNull()
        ->and(constraintsOfClass($optOut, UniqueValue::class)[0]->ignoredIdentifier)->toBeNull()
        ->and(constraintsOfClass($defaultOff, UniqueValue::class)[0]->ignoredIdentifier)->toBeNull();
});

it('lets an explicit ignorable win over the bound record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $ada = new Handle('ada');
    $bob = new Handle('bob');
    EntityManagerFactory::seed($entityManager, $ada, $bob);

    $field = wireUniqueField(TextInput::make('name')->unique(ignorable: $entityManager->find(Handle::class, $bob->getId())), $entityManager->find(Handle::class, $ada->getId()));

    expect(constraintsOfClass($field, UniqueValue::class)[0]->ignoredIdentifier)->toBe((string) $bob->getId());
});

it('emits no data-layer constraint when no entity class resolves', function (): void {
    $host = new FieldRenderHost();
    $field = TextInput::make('name')->unique();

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    expect(constraintsOfClass($field, UniqueValue::class))->toBe([]);
});

it('emits an exists constraint naming the entity and property', function (): void {
    $field = wireUniqueField(TextInput::make('name')->exists(Handle::class, 'owner'), null);
    $existing = constraintsOfClass($field, ExistingValue::class);

    expect($existing)->toHaveCount(1)
        ->and($existing[0]->entityClass)->toBe(Handle::class)
        ->and($existing[0]->property)->toBe('owner');
});

it('wraps modifyQueryUsing so the Invoker injects into it', function (): void {
    $received = null;
    $field = wireUniqueField(
        TextInput::make('name')->unique(modifyQueryUsing: function (QueryBuilder $query) use (&$received): void {
            $received = $query;
        }),
        Handle::class,
    );

    $constraint = constraintsOfClass($field, UniqueValue::class)[0];
    $builder = $this::getContainer()->get(EntityManagerInterface::class)->createQueryBuilder();
    ($constraint->modifyQueryUsing)($builder);

    expect($received)->toBe($builder);
});

/**
 * @template TConstraint of Constraint
 *
 * @param  class-string<TConstraint> $class
 * @return list<TConstraint>
 */
function constraintsOfClass(Field $field, string $class): array
{
    return array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof $class,
    ));
}

/**
 * Wire a field under a schema bound to `$record`, which is the frame `unique()`
 * and `exists()` resolve their entity class and their ignored identifier from.
 */
function wireUniqueField(Field $field, object|string|null $record): Field
{
    $host = new FieldRenderHost();
    $host->data = ['name' => 'ada'];

    $schema = Schema::make($host)->statePath('data');

    if ($record !== null) {
        $schema->record($record);
    } else {
        $schema->record(Handle::class);
    }

    $schema->components([$field])->getComponents();

    return $field;
}
