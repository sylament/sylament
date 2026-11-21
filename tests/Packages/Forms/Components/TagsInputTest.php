<?php

declare(strict_types=1);

use Sylament\Forms\Components\TagsInput;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\TagFieldsForm;
use Sylament\Tests\Fixtures\Schemas\Record\Manuscript;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Length;

use function Symfony\Component\Translation\t;

describe('rendering', function (): void {
    it('renders the unbound text box, a chip per tag inside the ignored container and the blueprint outside it', function (): void {
        $crawler = $this->renderSchemaComponent(TagsInput::make('tags'), ['tags' => ['php', 'twig']]);
        $input = $crawler->filter('input[type="text"]');
        $chips = $crawler->filter('#schema\.tags-tags');

        expect($input)->toHaveCount(1)
            ->and([$input->attr('id'), $input->attr('list'), $input->attr('autocomplete'), $input->attr('placeholder')])->toBe(['schema.tags', 'schema.tags-suggestions', 'off', 'New tag'])
            ->and($crawler->filter('label[for="schema.tags"]'))->toHaveCount(1)
            ->and($crawler->filter('input[type="text"]:not([data-model]):not([name]):not([required]):not([aria-invalid])'))->toHaveCount(1)
            ->and($chips->attr('data-live-ignore'))->toBe('true')
            ->and($chips->filter('[aria-live="polite"]'))->toHaveCount(1)
            ->and($chips->filter('datalist#schema\.tags-suggestions'))->toHaveCount(1)
            ->and($chips->filter('datalist#schema\.tags-suggestions option'))->toHaveCount(0)
            ->and($chips->filter(SupportSelectors::BADGE . ' .sy-fo-tags-input-tag-label')->each(static fn (Crawler $label): string => $label->text()))->toBe(['php', 'twig'])
            ->and($chips)->toContainButtons('button', ['Delete: php', 'Delete: twig'])
            ->and($chips->filter('template'))->toHaveCount(0)
            ->and($crawler->filter('template ' . SupportSelectors::BADGE . ' .sy-fo-tags-input-tag-label')->text())->toBe('')
            ->and($crawler->filter('template'))->toContainButtons('button', ['Delete: __TAG__']);
    });

    it("wires its controller's values, targets and actions", function (): void {
        $crawler = $this->renderSchemaComponent(TagsInput::make('tags')->suggestions(['php', 'css']), ['tags' => ['php']]);
        $wrapper = $crawler->filter('[data-controller~="sylament--forms--tags-input"]');
        $value = static fn (string $name): ?string => $wrapper->attr("data-sylament--forms--tags-input-{$name}-value");
        $target = static fn (string $name): Crawler => $wrapper->filter("[data-sylament--forms--tags-input-target=\"{$name}\"]");

        expect([$value('state-path'), $value('model-binding'), $value('split-keys'), $value('suggestions'), $value('tag-added-message'), $value('tag-removed-message')])
            ->toBe(['data.tags', 'norender|data.tags', '[]', '["php","css"]', 'Added: __TAG__', 'Removed: __TAG__'])
            ->and($target('input')->attr('id'))->toBe('schema.tags')
            ->and($target('input')->attr('data-action'))->toBe('keydown->sylament--forms--tags-input#keydown blur->sylament--forms--tags-input#createTag paste->sylament--forms--tags-input#paste')
            ->and($target('chips')->attr('id'))->toBe('schema.tags-tags')
            ->and($target('liveRegion')->attr('aria-live'))->toBe('polite')
            ->and($target('datalist')->nodeName())->toBe('datalist')
            ->and($target('chipTemplate')->nodeName())->toBe('template')
            ->and($target('chips')->filter(SupportSelectors::BADGE . ' button')->attr('data-action'))->toBe('click->sylament--forms--tags-input#deleteTag');
    });

    it('hands the controller its split keys', function (): void {
        $wrapper = $this->renderSchemaComponent(TagsInput::make('tags')->splitKeys(['Enter', ',']))->filter('[data-controller~="sylament--forms--tags-input"]');

        expect($wrapper->attr('data-sylament--forms--tags-input-split-keys-value'))->toBe('["Enter",","]');
    });

    it("hands the controller the field's binding modifiers verbatim", function (Closure $field, string $binding): void {
        $wrapper = $this->renderSchemaComponent($field())->filter('[data-controller~="sylament--forms--tags-input"]');

        expect($wrapper->attr('data-sylament--forms--tags-input-model-binding-value'))->toBe($binding);
    })->with([
        'debounced' => [fn (): TagsInput => TagsInput::make('tags')->live(debounce: 500), 'debounce(500)|data.tags'],
        'on blur' => [fn (): TagsInput => TagsInput::make('tags')->live(onBlur: true), 'on(change)|data.tags'],
    ]);

    it('lists the suggestions that are not already tags', function (): void {
        $crawler = $this->renderSchemaComponent(TagsInput::make('tags')->suggestions(['php', 'twig', 'css']), ['tags' => ['php']]);

        expect($crawler->filter('#schema\.tags-tags datalist option')->each(static fn (Crawler $option): string => $option->attr('value') ?? ''))->toBe(['twig', 'css'])
            ->and($crawler->filter('datalist')->attr('id'))->toBe($crawler->filter('input[type="text"]')->attr('list'));
    });

    it('wraps each chip label in the prefix and suffix', function (): void {
        $crawler = $this->renderSchemaComponent(TagsInput::make('tags')->tagPrefix('#')->tagSuffix('!'), ['tags' => ['php']]);

        expect($crawler->filter('#schema\.tags-tags ' . SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_LABEL)->text())->toBe('#php!')
            ->and($crawler->filter('template ' . SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_LABEL)->text())->toBe('#!');
    });

    it('makes the chips sortable only when reorderable and enabled', function (Closure $field, bool $sortable): void {
        $chips = $this->renderSchemaComponent($field(), ['tags' => ['php', 'twig']])->filter('#schema\.tags-tags');

        expect($chips->filter(SupportSelectors::BADGE))->toHaveCount(2);

        if (! $sortable) {
            expect($chips->attr('data-controller'))->toBeNull()
                ->and($chips->filter(SupportSelectors::SORTABLE_ITEM))->toHaveCount(0);

            return;
        }

        expect($chips->matches(SupportSelectors::SORTABLE_CONTROLLER . SupportSelectors::sortableAnimationDuration('100')))->toBeTrue()
            ->and($chips->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--forms--tags-input#reorder')
            ->and($chips->filter(SupportSelectors::BADGE . SupportSelectors::SORTABLE_HANDLE)->each(static fn (Crawler $chip): string => $chip->attr('data-sortable-item') ?? ''))->toBe(['php', 'twig']);
    })->with([
        'reorderable()' => [fn (): TagsInput => TagsInput::make('tags')->reorderable(), true],
        'reorderable() and disabled()' => [fn (): TagsInput => TagsInput::make('tags')->reorderable()->disabled(), false],
        'by default' => [fn (): TagsInput => TagsInput::make('tags'), false],
    ]);

    it('renders a seeded duplicate as two chips under one sortable key', function (): void {
        $chips = $this->renderSchemaComponent(TagsInput::make('tags')->reorderable(), ['tags' => ['php', 'php']])->filter('#schema\.tags-tags ' . SupportSelectors::BADGE);

        expect($chips->each(static fn (Crawler $chip): string => $chip->attr('data-sortable-item') ?? ''))->toBe(['php', 'php']);
    });

    it('colours its chips through the badge policy', function (Closure $field, string $color): void {
        $chips = $this->renderSchemaComponent($field(), ['tags' => ['php']]);

        expect($chips->filter('#schema\.tags-tags ' . SupportSelectors::BADGE . SupportSelectors::color($color)))->toHaveCount(1);
    })->with([
        'by default' => [fn (): TagsInput => TagsInput::make('tags'), 'primary'],
        'color()' => [fn (): TagsInput => TagsInput::make('tags')->color('danger'), 'danger'],
    ]);

    it("keeps the chip container, its id and its ignore marker whatever the state", function (): void {
        $tagged = $this->renderSchemaComponent(TagsInput::make('tags'), ['tags' => ['php']])->filter('[data-live-ignore]');
        $empty = $this->renderSchemaComponent(TagsInput::make('tags'), ['tags' => []])->filter('[data-live-ignore]');

        expect($tagged->attr('id'))->toBe('schema.tags-tags')
            ->and($empty->attr('id'))->toBe('schema.tags-tags')
            ->and($tagged->filter(SupportSelectors::BADGE))->toHaveCount(1)
            ->and($empty->filter(SupportSelectors::BADGE))->toHaveCount(0)
            ->and($empty->filter('[aria-live="polite"]'))->toHaveCount(1)
            ->and($empty->filter('datalist'))->toHaveCount(1);
    });

    it('renders a numeric tag as a chip carrying its string value', function (): void {
        $chips = $this->renderSchemaComponent(TagsInput::make('tags'), ['tags' => [1, 2]])->filter('#schema\.tags-tags ' . SupportSelectors::BADGE);

        expect($chips->each(static fn (Crawler $chip): array => [$chip->attr('data-value'), $chip->filter('.sy-fo-tags-input-tag-label')->text()]))->toBe([['1', '1'], ['2', '2']]);
    });

    it('disables the text box and marks the wrapper when disabled', function (): void {
        $crawler = $this->renderSchemaComponent(TagsInput::make('tags')->disabled(), ['tags' => ['php']]);

        expect($crawler->filter('input[type="text"]')->attr('disabled'))->not->toBeNull()
            ->and($crawler->filter('.sy-fo-tags-input' . SupportSelectors::DISABLED))->toHaveCount(1);
    });

    it('puts a failed validation on the wrapper and under it, never on the text box', function (): void {
        $tester = $this->createLiveComponent(TagFieldsForm::class)->call('save')->assertHasFormErrors(['tags' => 'This value should not be blank.']);
        $field = $tester->render()->crawler()->filter(Selectors::FIELD_WRAPPER)->reduce(static fn (Crawler $wrapper): bool => $wrapper->filter('[data-sylament--forms--tags-input-state-path-value="data.tags"]')->count() === 1);

        expect($field->filter('.sy-fo-tags-input' . SupportSelectors::INVALID))->toHaveCount(1)
            ->and($field->text())->toContain('This value should not be blank.')
            ->and($field->filter('input[type="text"]'))->toHaveCount(1)
            ->and($field->filter('input[aria-invalid]'))->toHaveCount(0)
            ->and($field->filter('input[required]'))->toHaveCount(0);

        $tester->set('data.tags', ['php'])->call('save')->assertHasNoFormErrors();
    });
});

describe('state', function (): void {
    it('rests on an empty list', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(TagsInput::make('tags'));
        $schema->fill();

        expect($host->data)->toBe(['tags' => []])
            ->and($schema->getState())->toBe(['tags' => []]);
    });

    it('hydrates a fill into a list, exploding a separated string and collapsing a blank one', function (Closure $field, mixed $tags, array $hydrated): void {
        $host = new RecordMappingHost();
        $host->mapping($field())->fill(['tags' => $tags]);

        expect($host->data['tags'])->toBe($hydrated);
    })->with([
        'a list' => [fn (): TagsInput => TagsInput::make('tags'), ['php', 'twig'], ['php', 'twig']],
        'a string, with no separator' => [fn (): TagsInput => TagsInput::make('tags'), 'php,twig', []],
        'a separated string' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), 'php,twig,css', ['php', 'twig', 'css']],
        'a single tag' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), 'php', ['php']],
        'a blank string' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), '', []],
        'null' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), null, []],
        'an integer' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), 5, []],
    ]);

    it('explodes a separated string on a fill but not on a live write', function (): void {
        $tester = $this->createLiveComponent(TagFieldsForm::class, ['record' => ['joined' => 'php,twig']]);
        $chips = static fn (): Crawler => $tester->render()->crawler()->filter('[data-sylament--forms--tags-input-state-path-value="data.joined"] [data-live-ignore] ' . SupportSelectors::BADGE);

        expect($tester->component()->data['joined'])->toBe(['php', 'twig'])
            ->and($chips())->toHaveCount(2);

        $tester->set('data.joined', 'php,css');

        expect($tester->component()->data['joined'])->toBe('php,css')
            ->and($chips())->toHaveCount(0);
    });

    it('saves a separator field as a joined string and a plain one as its list', function (Closure $field, mixed $tags, mixed $saved): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping($field());
        $host->data = ['tags' => $tags];

        expect($schema->getState())->toBe(['tags' => $saved]);
    })->with([
        'a separator' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), ['php', 'twig'], 'php,twig'],
        'no separator' => [fn (): TagsInput => TagsInput::make('tags'), ['php', 'twig'], ['php', 'twig']],
        'an emptied separator field' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), [], ''],
        'a tag that is no string' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), ['php', ['twig']], 'php,'],
        'a null bag' => [fn (): TagsInput => TagsInput::make('tags')->separator(','), null, null],
    ]);

    it('writes an emptied list as an empty array even under nullWhenBlank()', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(TagsInput::make('meta')->nullWhenBlank())->record($manuscript = new Manuscript());
        $schema->fill($manuscript);

        $schema->getData();

        expect($manuscript->getMeta())->toBe([]);
    });
});

describe('trim', function (): void {
    it('trims each tag when saved, and a string a live write left whole, leaving the bag as typed', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(TagsInput::make('tags')->trim());
        $host->data = ['tags' => [' php ', 'twig ']];

        expect($schema->getState())->toBe(['tags' => ['php', 'twig']])
            ->and($host->data['tags'])->toBe([' php ', 'twig ']);

        $host->data = ['tags' => ' php,twig '];

        expect($schema->getState())->toBe(['tags' => 'php,twig']);
    });
});

describe('strip', function (): void {
    it('strips the characters from each tag the state reads, and from a string a live write left whole', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(TagsInput::make('tags')->stripCharacters(['#']));
        $host->data = ['tags' => ['#php', 'twig#']];

        expect($schema->getState())->toBe(['tags' => ['php', 'twig']]);

        $host->data = ['tags' => '#php,twig#'];

        expect($schema->getState())->toBe(['tags' => 'php,twig']);
    });
});

describe('validation', function (): void {
    it('validates each tag stripped, then trimmed', function (array $tags, bool $passes): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(TagsInput::make('tags')->trim()->stripCharacters(['#'])->nestedRecursiveConstraint(new Length(min: 2)));
        $host->data = ['tags' => $tags];

        if ($passes) {
            expect($host->validateSchema($schema))->toBeNull()
                ->and($host->data['tags'])->toBe($tags);

            return;
        }

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.tags' => ['This value is too short. It should have 2 characters or more.']]);
        });
    })->with([
        'a tag that is one letter once cleaned' => [[' p# ', 'twig'], false],
        'tags of two letters once cleaned' => [[' #ph ', 'tw'], true],
    ]);
});

it('translates a tag prefix given as a message', function (): void {
    expect($this->renderSchemaComponent(TagsInput::make('tags')->tagPrefix(t('tags.prefix', [], 'fixtures-forms')), ['tags' => ['php']])->filter('#schema\.tags-tags ' . SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_LABEL)->text())->toBe('# (translated)php');
});
