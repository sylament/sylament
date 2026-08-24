<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\Author;
use Sylament\Forms\Validation\Distinct;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\RecordTypeReader;
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

it('derives from the root record class when a mid-tree record() binding intervenes', function (): void {
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

it('does not derive from a mid-tree binding alone', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->components([
            Section::make('author')
                ->statePath('author')
                ->record(Author::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeFalse();
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
