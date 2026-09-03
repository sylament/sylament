<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\TagsInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Render a tags input against a host whose `data` bag is pre-filled, so `getState()`
 * resolves to a real list and the server-rendered chips have something to answer —
 * the same rig `renderCheckboxListWithState()` builds one file over.
 *
 * @param array<string, mixed> $data
 */
function renderTagsInputWithState(TagsInput $field, array $data): Crawler
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return new Crawler(renderToHtml(Schema::make($host)->statePath('data')->components([$field])));
}

/**
 * The controller's own element — the input wrapper, which is where the values, the
 * targets and the chip container all hang off.
 */
function tagsInputRoot(Crawler $crawler): Crawler
{
    return $crawler->filter('div.sy-fo-tags-input');
}

it('renders the scratch text input, unbound and pointed at its datalist', function (): void {
    $input = new Crawler(renderField(TagsInput::make('tags')))->filter('input.sy-fo-input');

    expect($input->count())->toBe(1)
        ->and($input->attr('type'))->toBe('text')
        ->and($input->attr('id'))->toBe('tags')
        ->and($input->attr('list'))->toBe('tags-suggestions')
        ->and($input->attr('autocomplete'))->toBe('off')
        ->and($input->attr('placeholder'))->toBe('New tag')
        // The write is `component.set()`, so the box holding the half-typed tag is scratch
        // state: a `data-model` here would bind the field's array to a scalar read.
        ->and($input->attr('data-model'))->toBeNull()
        ->and($input->attr('name'))->toBeNull()
        ->and($input->attr('disabled'))->toBeNull();
});

it('binds the three input actions of the controller', function (): void {
    $input = new Crawler(renderField(TagsInput::make('tags')))->filter('input.sy-fo-input');

    expect($input->attr('data-sylament--forms--tags-input-target'))->toBe('input')
        ->and($input->attr('data-action'))->toBe(
            'keydown->sylament--forms--tags-input#keydown '
            . 'blur->sylament--forms--tags-input#createTag '
            . 'paste->sylament--forms--tags-input#paste',
        );
});

it('disables the text input and marks the wrapper when the field is disabled', function (): void {
    $crawler = new Crawler(renderField(TagsInput::make('tags')->disabled()));

    expect($crawler->filter('input.sy-fo-input')->attr('disabled'))->not->toBeNull()
        ->and(tagsInputRoot($crawler)->attr('class'))->toContain('sy-disabled');
});

it('carries the state path and the binding directive as controller values', function (): void {
    $root = tagsInputRoot(new Crawler(renderField(TagsInput::make('tags'))));

    expect($root->attr('data-controller'))->toBe('sylament--forms--tags-input')
        ->and($root->attr('data-sylament--forms--tags-input-state-path-value'))->toBe('data.tags')
        ->and($root->attr('data-sylament--forms--tags-input-model-binding-value'))->toBe('norender|data.tags');
});

it('hands the binding modifiers through unchanged for a live and a debounced field', function (): void {
    $live = tagsInputRoot(new Crawler(renderField(TagsInput::make('tags')->live())));
    $debounced = tagsInputRoot(new Crawler(renderField(TagsInput::make('tags')->live(debounce: 500))));

    expect($live->attr('data-sylament--forms--tags-input-model-binding-value'))->toBe('data.tags')
        ->and($debounced->attr('data-sylament--forms--tags-input-model-binding-value'))->toBe('debounce(500)|data.tags');
});

it('carries the split keys, the suggestions and the two announcements as controller values', function (): void {
    $root = tagsInputRoot(new Crawler(renderField(
        TagsInput::make('tags')->splitKeys([',', ' '])->suggestions(['php', 'js']),
    )));

    expect($root->attr('data-sylament--forms--tags-input-split-keys-value'))->toBe('[","," "]')
        ->and($root->attr('data-sylament--forms--tags-input-suggestions-value'))->toBe('["php","js"]')
        ->and($root->attr('data-sylament--forms--tags-input-tag-added-message-value'))->toBe('Added: :tag')
        ->and($root->attr('data-sylament--forms--tags-input-tag-removed-message-value'))->toBe('Removed: :tag');
});

it('ignores the chip container on morph and gives it an id that cannot change', function (): void {
    $container = new Crawler(renderField(TagsInput::make('tags')))->filter('div.sy-fo-tags-input-tags-ctn');

    expect($container->count())->toBe(1)
        ->and($container->attr('data-live-ignore'))->not->toBeNull()
        // idiomorph runs its id-mismatch branch before the ignore branch, so an id that
        // moved with the state would blow the client-owned subtree away on every render.
        ->and($container->attr('id'))->toBe('tags-tags')
        ->and($container->attr('data-sylament--forms--tags-input-target'))->toBe('chips');
});

it('puts the polite live region and the datalist inside the ignored container', function (): void {
    $container = new Crawler(renderField(TagsInput::make('tags')->suggestions(['php'])))
        ->filter('div.sy-fo-tags-input-tags-ctn');

    $liveRegion = $container->filter('div.sy-sr-only');
    $datalist = $container->filter('datalist');

    expect($liveRegion->attr('aria-live'))->toBe('polite')
        ->and($liveRegion->attr('data-sylament--forms--tags-input-target'))->toBe('liveRegion')
        ->and($datalist->attr('id'))->toBe('tags-suggestions')
        ->and($datalist->attr('data-sylament--forms--tags-input-target'))->toBe('datalist');
});

it('renders the initial suggestion options minus the tags already chosen', function (): void {
    $options = renderTagsInputWithState(
        TagsInput::make('tags')->suggestions(['php', 'js', 'go']),
        ['tags' => ['js']],
    )->filter('datalist option');

    expect($options->extract(['value']))->toBe(['php', 'go']);
});

it('renders one badge chip per seeded tag, each carrying its own value', function (): void {
    $chips = renderTagsInputWithState(TagsInput::make('tags'), ['tags' => ['php', 'js']])
        ->filter('div.sy-fo-tags-input-tags-ctn > span.sy-badge');

    expect($chips->count())->toBe(2)
        ->and($chips->eq(0)->attr('data-value'))->toBe('php')
        ->and($chips->eq(0)->filter('span.sy-fo-tags-input-tag-label')->text())->toBe('php')
        ->and($chips->eq(1)->attr('data-value'))->toBe('js')
        ->and($chips->eq(1)->filter('span.sy-fo-tags-input-tag-label')->text())->toBe('js');
});

it('gives every chip the badge delete button, its label and the shared delete icon', function (): void {
    // Resolve the container before the field is built: a component `make()`d outside it
    // gets a fallback IconManager, and the shared delete alias would then resolve for
    // the wrong reason.
    $this::getContainer();

    $button = renderTagsInputWithState(TagsInput::make('tags'), ['tags' => ['php']])
        ->filter('div.sy-fo-tags-input-tags-ctn span.sy-badge button.sy-badge-delete-btn');

    expect($button->count())->toBe(1)
        ->and($button->attr('type'))->toBe('button')
        ->and($button->attr('aria-label'))->toBe('Delete')
        ->and($button->attr('data-action'))->toBe('click->sylament--forms--tags-input#deleteTag')
        ->and($button->filter('svg.sy-icon')->count())->toBe(1);
});

it('wraps the tag label in the prefix and the suffix', function (): void {
    $label = renderTagsInputWithState(
        TagsInput::make('tags')->tagPrefix('#')->tagSuffix('!'),
        ['tags' => ['php']],
    )->filter('span.sy-badge span.sy-badge-label');

    expect($label->html())->toBe('#<span class="sy-fo-tags-input-tag-label">php</span>!');
});

it('paints the chips from the colour policy rather than a hardcoded shade', function (): void {
    $chip = renderTagsInputWithState(TagsInput::make('tags')->color('danger'), ['tags' => ['php']])
        ->filter('span.sy-badge');

    expect($chip->attr('class'))->toContain('sy-color-danger')
        ->and($chip->attr('class'))->not->toContain('sy-color-primary');

    $default = renderTagsInputWithState(TagsInput::make('tags'), ['tags' => ['php']])->filter('span.sy-badge');

    expect($default->attr('class'))->toContain('sy-color-primary');
});

it('renders one blueprint chip in a template the controller clones', function (): void {
    $crawler = renderTagsInputWithState(TagsInput::make('tags'), ['tags' => ['php']]);
    $template = $crawler->filter('template[data-sylament--forms--tags-input-target="chipTemplate"]');

    expect($template->count())->toBe(1)
        // Outside the ignored container: a clone appended beside a server-rendered chip
        // inside it would survive every morph and duplicate.
        ->and($crawler->filter('div.sy-fo-tags-input-tags-ctn template')->count())->toBe(0)
        ->and($template->filter('span.sy-badge')->count())->toBe(1)
        ->and($template->filter('span.sy-badge')->attr('data-value'))->toBe('')
        ->and($template->filter('span.sy-fo-tags-input-tag-label')->text())->toBe('');
});

it('drags through the shared sortable control only once reorderable and enabled', function (): void {
    $plain = new Crawler(renderField(TagsInput::make('tags')))->filter('div.sy-fo-tags-input-tags-ctn');
    $reorderable = new Crawler(renderField(TagsInput::make('tags')->reorderable()))
        ->filter('div.sy-fo-tags-input-tags-ctn');
    $disabled = new Crawler(renderField(TagsInput::make('tags')->reorderable()->disabled()))
        ->filter('div.sy-fo-tags-input-tags-ctn');

    expect($plain->attr('data-controller'))->toBeNull()
        ->and($reorderable->attr('data-controller'))->toBe('sylament--support--sortable')
        ->and($reorderable->attr('data-action'))->toBe(
            'sylament--support--sortable:end->sylament--forms--tags-input#reorder',
        )
        ->and($reorderable->attr('data-sylament--support--sortable-animation-duration-value'))->toBe('100')
        ->and($disabled->attr('data-controller'))->toBeNull();
});

it('marks each chip as a sortable item and its own handle only when reorderable', function (): void {
    $reorderable = renderTagsInputWithState(TagsInput::make('tags')->reorderable(), ['tags' => ['php']])
        ->filter('span.sy-badge');
    $plain = renderTagsInputWithState(TagsInput::make('tags'), ['tags' => ['php']])->filter('span.sy-badge');

    expect($reorderable->attr('data-sortable-item'))->toBe('php')
        ->and($reorderable->attr('data-sortable-handle'))->not->toBeNull()
        ->and($reorderable->attr('class'))->toContain('sy-reorderable')
        ->and($plain->attr('data-sortable-item'))->toBeNull()
        ->and($plain->attr('data-sortable-handle'))->toBeNull()
        ->and($plain->attr('class'))->not->toContain('sy-reorderable');
});

it('renders its affixes through the shared input wrapper', function (): void {
    $this::getContainer();

    $crawler = new Crawler(renderField(
        TagsInput::make('tags')
            ->prefix('Tag')
            ->prefixIcon('heroicons:magnifying-glass-20-solid')
            ->suffix('go')
            ->inlineSuffix(),
    ));

    $labels = $crawler->filter('div.sy-fo-tags-input span.sy-input-wrp-label');

    expect($labels->extract(['_text']))->toBe(['Tag', 'go'])
        ->and($crawler->filter('div.sy-input-wrp-prefix svg.sy-icon')->count())->toBe(1)
        ->and($crawler->filter('div.sy-input-wrp-suffix')->attr('class'))->toContain('sy-inline')
        ->and($crawler->filter('div.sy-input-wrp-prefix')->attr('class'))->not->toContain('sy-inline');
});

it('reports an empty list as blank when the field is required', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));
    $host->data = ['tags' => []];

    $schema = Schema::make($host)->statePath('data')->components([TagsInput::make('tags')->required()]);
    $host->cacheSchema('schema', $schema);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.tags'))->toBe(['This value should not be blank.']);

    $host->data = ['tags' => ['php']];
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.tags'))->toBe([]);
});

it('emits a nested recursive constraint as one All over the list', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['tags' => ['ab', 'abc']];

    $field = TagsInput::make('tags')->nestedRecursiveConstraint(new Length(min: 3));
    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    $all = array_values(array_filter(
        $field->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof All,
    ));

    expect($all)->toHaveCount(1)
        ->and($all[0]->constraints)->toHaveCount(1)
        ->and($all[0]->constraints[0])->toBeInstanceOf(Length::class);
});

it('strips characters per element for validation, for the saved value, and never for the raw bag', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['tags' => ['a,1', 'b,2']];

    $field = TagsInput::make('tags')->stripCharacters(',');
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.tags' => ['a,1', 'b,2']]))->toBe(['data.tags' => ['a1', 'b2']])
        ->and($schema->getStateSnapshot())->toBe(['tags' => ['a1', 'b2']])
        ->and($host->data['tags'])->toBe(['a,1', 'b,2']);
});

it('trims per element for validation, for the saved value, and never for the raw bag', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['tags' => ['  php  ', '  js']];

    $field = TagsInput::make('tags')->trim();
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.tags' => ['  php  ', '  js']]))->toBe(['data.tags' => ['php', 'js']])
        ->and($schema->getStateSnapshot())->toBe(['tags' => ['php', 'js']])
        ->and($host->data['tags'])->toBe(['  php  ', '  js']);
});
