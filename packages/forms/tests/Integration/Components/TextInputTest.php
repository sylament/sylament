<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\TextInput\Actions\CopyAction;
use Sylament\Forms\Components\TextInput\Actions\HidePasswordAction;
use Sylament\Forms\Components\TextInput\Actions\ShowPasswordAction;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Schema;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Error\RuntimeError;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

it('renders the field wrapper chrome around the input', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->label('Title')->maxLength(120)->markAsRequired(),
    ));

    $wrapper = $crawler->filter('div.sy.sy-fo-field');
    expect($wrapper->count())->toBe(1);

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('for'))->toBe('title')
        ->and($label->filter('.sy-fo-field-label-content')->text())->toContain('Title')
        ->and($label->filter('.sy-fo-field-label-required-mark')->text())->toBe('*');

    $input = $crawler->filter('input.sy-fo-input');
    expect($input->count())->toBe(1)
        ->and($input->attr('type'))->toBe('text')
        ->and($input->attr('name'))->toBe('title')
        ->and($input->attr('id'))->toBe('title')
        ->and($input->attr('maxlength'))->toBe('120')
        ->and($input->attr('required'))->not->toBeNull()
        ->and($input->attr('data-model'))->toBe('norender|data.title')
        // Unset field: getState reads back null from the empty $data bag -> value="".
        ->and($input->attr('value'))->toBe('');
});

it('binds the input to its absolute state path via data-model', function (): void {
    // Standalone default: state syncs but suppresses the re-render.
    $default = new Crawler(renderField(TextInput::make('title')));
    expect($default->filter('input.sy-fo-input')->attr('data-model'))->toBe('norender|data.title');

    // live() drops the modifier to a bare path (re-render on input).
    $live = new Crawler(renderField(TextInput::make('title')->live()));
    expect($live->filter('input.sy-fo-input')->attr('data-model'))->toBe('data.title');
});

it('renders helper text in the below-content slot', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->helperText('Some help'),
    ));

    // Helper text is a Text component seeded into the below_content slot.
    $helper = $crawler->filter('.sy-fo-field-content-col .sy-sc-text');
    expect($helper->count())->toBe(1)
        ->and($helper->text())->toBe('Some help');
});

it('renders the hint text in the after-label slot', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand'),
    ));

    // Hint text is a Text component seeded into the after_label slot.
    $hint = $crawler->filter('.sy-fo-field-label-ctn .sy-sc-text');
    expect($hint->count())->toBe(1)
        ->and($hint->text())->toContain('Need a hand');

    // The label and hint share one row (the label container), not stacked rows.
    expect($crawler->filter('.sy-fo-field-label-ctn > label.sy-fo-field-label')->count())->toBe(1)
        ->and($crawler->filter('.sy-fo-field-label-ctn .sy-sc-text')->count())->toBe(1);
});

it('renders the hint icon through the support icon mechanism', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand')->hintIcon('heroicons:star'),
    ));

    $icon = $crawler->filter('.sy-fo-field-label-ctn svg.sy-sc-icon');
    expect($icon->count())->toBe(1);
});

it('renders the hint icon tooltip as the svg title attribute', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand')->hintIcon('heroicons:star', 'More info'),
    ));

    $icon = $crawler->filter('.sy-fo-field-label-ctn svg.sy-sc-icon');
    expect($icon->count())->toBe(1)
        ->and($icon->attr('title'))->toBe('More info');
});

it('renders the hint independent of a hidden label', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand')->hiddenLabel(),
    ));

    $hint = $crawler->filter('.sy-fo-field-label-ctn .sy-sc-text');
    expect($hint->count())->toBe(1)
        ->and($hint->text())->toContain('Need a hand');
});

it('renders a hint action as a small link in the after-label slot', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hintAction(Action::make('edit')->label('Edit')),
    ));

    // Action::LINK_TEMPLATE composes support's Link, whose root carries
    // `sy sy-link` plus the action's own `sy-ac`, and is a <button> with no URL.
    $action = $crawler->filter('.sy-fo-field-label-col button.sy-link.sy-ac');

    expect($action->count())->toBe(1)
        ->and($action->text())->toContain('Edit')
        ->and($action->attr('class'))->toContain('sy-size-sm');
});

it('renders arbitrary content in the label and content wrapper slots', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->aboveLabel('Above label')
            ->belowContent('Below content'),
    ));

    // A string slot is coerced to a Text component in the matching wrapper column.
    expect($crawler->filter('.sy-fo-field-label-col')->text())->toContain('Above label')
        ->and($crawler->filter('.sy-fo-field-content-col')->text())->toContain('Below content')
        // Above-label sits before the label row within the label column.
        ->and($crawler->filter('.sy-fo-field-label-col > .sy-sc .sy-sc-text')->text())->toContain('Above label');
});

it('wraps the input in a content container when before/after content is present', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->beforeContent('Before')->afterContent('After'),
    ));

    $ctn = $crawler->filter('.sy-fo-field-content-ctn');
    expect($ctn->count())->toBe(1)
        ->and($ctn->filter('.sy-fo-field-content > .sy-input-wrp > .sy-input-wrp-content-ctn > input.sy-fo-input')->count())->toBe(1)
        ->and($ctn->text())->toContain('Before')
        ->and($ctn->text())->toContain('After');
});

it('makes the label screen-reader-only with hiddenLabel', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hiddenLabel(),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('class'))->toContain('sy-sr-only')
        ->and($label->attr('for'))->toBe('title')
        ->and($label->text())->toBe('Title');

    // The sr-only branch is a bare label with no visible content span.
    expect($crawler->filter('.sy-fo-field-label-content')->count())->toBe(0);
});

it('derives the input type from the type sugar', function (string $method, string $type): void {
    $input = new Crawler(renderField(
        TextInput::make('title')->{$method}(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe($type);
})->with([
    'email' => ['email', 'email'],
    'numeric' => ['numeric', 'number'],
    'password' => ['password', 'password'],
    'tel' => ['tel', 'tel'],
    'url' => ['url', 'url'],
]);

it('emits inputmode=decimal for numeric inputs only', function (): void {
    $numeric = new Crawler(renderField(TextInput::make('amount')->numeric()));
    expect($numeric->filter('input.sy-fo-input')->attr('inputmode'))->toBe('decimal');

    $text = new Crawler(renderField(TextInput::make('title')));
    expect($text->filter('input.sy-fo-input')->attr('inputmode'))->toBeNull();
});

it('renders no step attribute by default', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')));

    expect($crawler->filter('input.sy-fo-input')->attr('step'))->toBeNull();
});

it('renders an explicit step', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('amount')->step(0.5)));

    expect($crawler->filter('input.sy-fo-input')->attr('step'))->toBe('0.5');
});

it('seeds step any from numeric so the browser accepts decimals', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('amount')->numeric()));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('number')
        ->and($input->attr('step'))->toBe('any');
});

it('lets an explicit step override the one numeric seeds', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('amount')->numeric()->step(5)));

    expect($crawler->filter('input.sy-fo-input')->attr('step'))->toBe('5');
});

it('seeds no step when a closure numeric condition resolves false', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('amount')->numeric(fn (): bool => false)));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('text')
        ->and($input->attr('step'))->toBeNull()
        ->and($input->attr('inputmode'))->toBeNull();
});

it('makes an integer input numeric with a step of one', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('count')->integer()));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('number')
        ->and($input->attr('step'))->toBe('1')
        ->and($input->attr('inputmode'))->toBe('numeric');
});

it('drops every integer consequence when a closure condition resolves false', function (): void {
    $field = TextInput::make('count')->integer(fn (): bool => false);

    $input = new Crawler(renderField($field))->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('text')
        ->and($input->attr('step'))->toBeNull()
        ->and($input->attr('inputmode'))->toBeNull()
        ->and($field->getValidationConstraints())->toBe([]);
});

it('renders the remaining html attributes', function (): void {
    $input = new Crawler(renderField(
        TextInput::make('title')
            ->placeholder('Type here')
            ->minLength(3)
            ->autofocus(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('placeholder'))->toBe('Type here')
        ->and($input->attr('minlength'))->toBe('3')
        ->and($input->attr('autofocus'))->not->toBeNull();
});

it('honors an explicit type over the sugar', function (): void {
    $input = new Crawler(renderField(
        TextInput::make('title')->type('search')->numeric(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('search');
});

it('renders the disabled attribute when disabled', function (): void {
    $enabled = new Crawler(renderField(TextInput::make('title')));
    expect($enabled->filter('input.sy-fo-input')->attr('disabled'))->toBeNull();

    $disabled = new Crawler(renderField(TextInput::make('title')->disabled()));
    expect($disabled->filter('input.sy-fo-input')->attr('disabled'))->not->toBeNull();
});

it('renders the readonly attribute only when read-only', function (): void {
    $writable = new Crawler(renderField(TextInput::make('title')));
    expect($writable->filter('input.sy-fo-input')->attr('readonly'))->toBeNull();

    $readOnly = new Crawler(renderField(TextInput::make('title')->readOnly()));
    expect($readOnly->filter('input.sy-fo-input')->attr('readonly'))->not->toBeNull();
});

it('adds an Email constraint when email()', function (): void {
    $constraints = TextInput::make('mail')->email()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Email::class);
});

it('adds a numeric Type constraint when numeric()', function (): void {
    $constraints = TextInput::make('price')->numeric()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Type::class)
        ->and($constraints[0]->type)->toBe('numeric');
});

it('validates an integer with the numeric arm plus a regex that accepts a numeric string', function (): void {
    $constraints = TextInput::make('count')->integer()->getValidationConstraints();

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0])->toBeInstanceOf(Type::class)
        ->and($constraints[0]->type)->toBe('numeric')
        ->and($constraints[1])->toBeInstanceOf(Regex::class);

    $pattern = $constraints[1]->pattern;

    expect(preg_match($pattern, '5'))->toBe(1)
        ->and(preg_match($pattern, '-5'))->toBe(1)
        ->and(preg_match($pattern, '+5'))->toBe(1)
        ->and(preg_match($pattern, '5.5'))->toBe(0)
        ->and(preg_match($pattern, 'abc'))->toBe(0)
        ->and(preg_match($pattern, ''))->toBe(0);
});

it('adds a Regex constraint whose default pattern accepts phone numbers and rejects prose', function (): void {
    $constraints = TextInput::make('phone')->tel()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Regex::class);

    $pattern = $constraints[0]->pattern;

    expect(preg_match($pattern, '+420 123 456 789'))->toBe(1)
        ->and(preg_match($pattern, '(123)456-7890'))->toBe(1)
        ->and(preg_match($pattern, '1234567890'))->toBe(1)
        ->and(preg_match($pattern, 'abc'))->toBe(0)
        ->and(preg_match($pattern, ''))->toBe(0);
});

it('honours a custom tel regex', function (): void {
    $constraints = TextInput::make('phone')->tel()->telRegex('/^\d{9}$/')->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Regex::class)
        ->and($constraints[0]->pattern)->toBe('/^\d{9}$/');
});

it('resolves a closure tel regex through evaluate', function (): void {
    $constraints = TextInput::make('phone')->tel()->telRegex(fn (): string => '/^\d{9}$/')->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0]->pattern)->toBe('/^\d{9}$/');
});

it('adds a Url constraint when url()', function (): void {
    $constraints = TextInput::make('site')->url()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Url::class);
});

it('drops the tel and url arms when a closure condition resolves false', function (): void {
    $tel = TextInput::make('phone')->tel(fn (): bool => false);
    $url = TextInput::make('site')->url(fn (): bool => false);

    expect($tel->getValidationConstraints())->toBe([])
        ->and($tel->getType())->toBe('text')
        ->and($url->getValidationConstraints())->toBe([])
        ->and($url->getType())->toBe('text');
});

it('applies email and numeric messages to the type arms', function (): void {
    $email = TextInput::make('mail')
        ->email()
        ->validationMessages([Email::class => 'Not an address.'])
        ->getValidationConstraints();

    $numeric = TextInput::make('price')
        ->numeric()
        ->validationMessages([Type::class => 'Not a number.'])
        ->getValidationConstraints();

    expect($email[0]->message)->toBe('Not an address.')
        ->and($numeric[0]->message)->toBe('Not a number.');
});

it('builds no type arms without the sugar', function (): void {
    expect(TextInput::make('title')->getValidationConstraints())->toBe([]);
});

it('keeps required first and appends type arms after lengths', function (): void {
    $constraints = TextInput::make('mail')->required()->maxLength(100)->email()->getValidationConstraints();

    expect($constraints)->toHaveCount(3)
        ->and($constraints[0])->toBeInstanceOf(RequiredValue::class)
        ->and($constraints[1])->toBeInstanceOf(Length::class)
        ->and($constraints[2])->toBeInstanceOf(Email::class);
});

it('renders the autocapitalize and autocomplete attributes', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->autocapitalize('words')->autocomplete('given-name'),
    ));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->attr('autocapitalize'))->toBe('words')
        ->and($input->attr('autocomplete'))->toBe('given-name');
});

it('omits the autocapitalize and autocomplete attributes when unset', function (): void {
    $input = new Crawler(renderField(TextInput::make('title')))->filter('input.sy-fo-input');

    expect($input->attr('autocapitalize'))->toBeNull()
        ->and($input->attr('autocomplete'))->toBeNull();
});

it('renders no datalist by default', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')));

    expect($crawler->filter('datalist')->count())->toBe(0)
        ->and($crawler->filter('input.sy-fo-input')->attr('list'))->toBeNull();
});

it('renders a datalist and points the input at it', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->datalist(['Alpha', 'Beta']),
    ));

    expect($crawler->filter('input.sy-fo-input')->attr('list'))->toBe('title-list');

    $datalist = $crawler->filter('datalist#title-list');

    expect($datalist->count())->toBe(1)
        ->and($datalist->filter('option')->count())->toBe(2)
        ->and($datalist->filter('option')->eq(0)->attr('value'))->toBe('Alpha')
        ->and($datalist->filter('option')->eq(1)->attr('value'))->toBe('Beta');
});

it('resolves closure datalist options through evaluate', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->datalist(fn (): array => ['Lazy']),
    ));

    expect($crawler->filter('datalist#title-list option')->attr('value'))->toBe('Lazy');
});

it('merges extra input attributes onto the control and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->numeric()->extraInputAttributes([
            'data-extra' => 'yes',
            'inputmode' => 'email',
        ]),
    ));

    $input = $crawler->filter('input.sy-fo-input');

    expect($input->attr('data-extra'))->toBe('yes')
        ->and($input->attr('inputmode'))->toBe('email');
});

it('renders no min or max attribute by default', function (): void {
    $input = new Crawler(renderField(TextInput::make('count')))->filter('input.sy-fo-input');

    expect($input->attr('min'))->toBeNull()
        ->and($input->attr('max'))->toBeNull();
});

it('renders the min and max attributes and resolves closures', function (): void {
    $input = new Crawler(renderField(
        TextInput::make('count')->numeric()->minValue(1)->maxValue(fn (): int => 10),
    ))->filter('input.sy-fo-input');

    expect($input->attr('min'))->toBe('1')
        ->and($input->attr('max'))->toBe('10');
});

it('adds a comparison constraint for each bound that is set', function (): void {
    $min = TextInput::make('count')->minValue(1)->getValidationConstraints();
    $max = TextInput::make('count')->maxValue(10)->getValidationConstraints();
    $both = TextInput::make('count')->minValue(1)->maxValue(10)->getValidationConstraints();

    expect($min)->toHaveCount(1)
        ->and($min[0])->toBeInstanceOf(GreaterThanOrEqual::class)
        ->and($max)->toHaveCount(1)
        ->and($max[0])->toBeInstanceOf(LessThanOrEqual::class)
        ->and($both)->toHaveCount(2)
        ->and($both[0])->toBeInstanceOf(GreaterThanOrEqual::class)
        ->and($both[0]->value)->toBe(1)
        ->and($both[1])->toBeInstanceOf(LessThanOrEqual::class)
        ->and($both[1]->value)->toBe(10);
});

it('drops a bound whose closure resolves null', function (): void {
    $field = TextInput::make('count')
        ->minValue(fn (): ?int => null)
        ->maxValue(fn (): int => 10);

    expect($field->getValidationConstraints())->toHaveCount(1)
        ->and($field->getValidationConstraints()[0])->toBeInstanceOf(LessThanOrEqual::class);
});

it('drops a bound that resolves blank rather than building an empty comparison', function (): void {
    $empty = TextInput::make('count')->minValue('')->maxValue('');
    $whitespace = TextInput::make('count')->maxValue(fn (): string => '   ');

    expect($empty->getValidationConstraints())->toBe([])
        ->and($whitespace->getValidationConstraints())->toBe([]);
});

it('keeps a zero bound, which is a value rather than a blank', function (): void {
    $int = TextInput::make('count')->maxValue(0)->getValidationConstraints();
    $string = TextInput::make('count')->minValue('0')->getValidationConstraints();

    expect($int)->toHaveCount(1)
        ->and($int[0])->toBeInstanceOf(LessThanOrEqual::class)
        ->and($int[0]->value)->toBe(0)
        ->and($string)->toHaveCount(1)
        ->and($string[0])->toBeInstanceOf(GreaterThanOrEqual::class)
        ->and($string[0]->value)->toBe('0');
});

it('applies a validation message to a comparison arm', function (): void {
    $constraints = TextInput::make('count')
        ->maxValue(10)
        ->validationMessages([LessThanOrEqual::class => 'Too big.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('Too big.');
});

it('compares a submitted numeric string by magnitude', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([
        TextInput::make('count')->numeric()->minValue(1)->maxValue(10),
    ]);
    $host->cacheSchema('schema', $schema);

    $host->data['count'] = '7';
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.count'))->toBe([]);

    $host->data['count'] = '70';
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.count'))
        ->toBe(['This value should be less than or equal to 10.']);
});

it('reports neither bound against the empty string a cleared input submits', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([
        TextInput::make('count')->minValue(1)->maxValue(10),
    ]);
    $host->cacheSchema('schema', $schema);

    $host->data['count'] = '';
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.count'))->toBe([]);

    $host->data['count'] = null;
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.count'))->toBe([]);
});

it('still reports both bounds on a required field, whose RequiredValue survives the gate', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([
        TextInput::make('count')->required()->minValue(1)->maxValue(10),
    ]);
    $host->cacheSchema('schema', $schema);

    $host->data['count'] = '';
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.count'))->toBe(['This value should not be blank.']);
});

it('strips characters for validation, for the saved value, and never for the raw bag', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['n' => '1,234'];

    $field = TextInput::make('n')->stripCharacters(',')->numeric();
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.n' => '1,234']))->toBe(['data.n' => '1234'])
        ->and($schema->getStateSnapshot())->toBe(['n' => '1234'])
        ->and($host->data['n'])->toBe('1,234');
});

it('trims for validation and for the saved value, and never for the raw bag', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['n' => '  ada  '];

    $field = TextInput::make('n')->trim();
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.n' => '  ada  ']))->toBe(['data.n' => 'ada'])
        ->and($schema->getStateSnapshot())->toBe(['n' => 'ada'])
        ->and($host->data['n'])->toBe('  ada  ');
});

it('mutates state for validation only once trim, strip or a closure is set', function (): void {
    expect(TextInput::make('n')->mutatesStateForValidation())->toBeFalse()
        ->and(TextInput::make('n')->trim()->mutatesStateForValidation())->toBeTrue()
        ->and(TextInput::make('n')->stripCharacters(',')->mutatesStateForValidation())->toBeTrue()
        ->and(TextInput::make('n')->mutateStateForValidationUsing(fn (): string => 'x')->mutatesStateForValidation())->toBeTrue();
});

it('registers both password actions as suffix actions and reads the password back as revealable', function (): void {
    $field = TextInput::make('secret')->password()->revealable();
    $actions = $field->getSuffixActions();

    expect($field->isPasswordRevealable())->toBeTrue()
        ->and(array_keys($actions))->toBe(['showPassword', 'hidePassword'])
        ->and($actions['showPassword'])->toBeInstanceOf(ShowPasswordAction::class)
        ->and($actions['hidePassword'])->toBeInstanceOf(HidePasswordAction::class)
        ->and($actions['showPassword']->isVisible())->toBeTrue()
        ->and($actions['hidePassword']->isVisible())->toBeTrue();
});

it('reads a plain text input back as not revealable and registers no affix action for it', function (): void {
    $field = TextInput::make('title');

    expect($field->isPasswordRevealable())->toBeFalse()
        ->and($field->getSuffixActions())->toBe([]);
});

it('answers false for a revealable condition that resolves false, without reaching the password guard', function (): void {
    expect(TextInput::make('title')->revealable(false)->isPasswordRevealable())->toBeFalse()
        ->and(TextInput::make('title')->revealable(static fn (): bool => false)->isPasswordRevealable())->toBeFalse();
});

it('refuses to reveal an input that is not a password', function (): void {
    $field = TextInput::make('title')->revealable();
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    expect(static fn (): bool => $field->isPasswordRevealable())
        ->toThrow(LogicException::class, 'The text input [data.title] is not a [password()], so it cannot be [revealable()].');
});

it('hides both password actions behind a revealable condition that resolves false', function (): void {
    $actions = TextInput::make('secret')->password()->revealable(false)->getSuffixActions();

    expect($actions['showPassword']->isVisible())->toBeFalse()
        ->and($actions['hidePassword']->isVisible())->toBeFalse();
});

it('registers the copy action as a suffix action and reads the field back as copyable', function (): void {
    $field = TextInput::make('token')->copyable();
    $actions = $field->getSuffixActions();

    expect($field->isCopyable())->toBeTrue()
        ->and(array_keys($actions))->toBe(['copy'])
        ->and($actions['copy'])->toBeInstanceOf(CopyAction::class)
        ->and($actions['copy']->isVisible())->toBeTrue();
});

it('carries the copy message and duration onto the registered action', function (): void {
    $field = TextInput::make('token')->copyable(copyMessage: 'Token copied', copyMessageDuration: 5000);
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    $copy = $field->getSuffixActions()['copy'];

    expect($copy)->toBeInstanceOf(CopyAction::class)
        ->and($copy->getExtraAttributeBag()->toScalarAttributes())
        ->toMatchArray([
            'data-sylament--forms--text-input-message-param' => 'Token copied',
            'data-sylament--forms--text-input-duration-param' => '5000',
        ]);
});

it('reads a plain text input back as not copyable and hides a copy action behind a false condition', function (): void {
    expect(TextInput::make('title')->isCopyable())->toBeFalse()
        ->and(TextInput::make('token')->copyable(false)->isCopyable())->toBeFalse()
        ->and(TextInput::make('token')->copyable(false)->getSuffixActions()['copy']->isVisible())->toBeFalse();
});

it('sees the render-time state in a copy message closure', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['token' => 'abc'];

    $field = TextInput::make('token')->copyable(copyMessage: static fn (mixed $state): string => "Copied {$state}");
    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    expect($field->getSuffixActions()['copy']->getExtraAttributeBag()->toScalarAttributes())
        ->toMatchArray(['data-sylament--forms--text-input-message-param' => 'Copied abc']);
});

it('appends the current-password constraint only behind the flag', function (): void {
    $constraints = TextInput::make('password')->currentPassword()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(UserPassword::class)
        ->and(TextInput::make('password')->getValidationConstraints())->toBe([])
        ->and(TextInput::make('password')->currentPassword(false)->getValidationConstraints())->toBe([]);
});

it('builds the three affix actions through the container, so a registered alias replaces their icons', function (): void {
    $this::getContainer()->get(IconManager::class)->register([
        FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD => 'heroicons:star',
        FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD => 'heroicons:star',
        FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_COPY => 'heroicons:star',
    ]);

    expect(ShowPasswordAction::make()->getIcon())->toBe('heroicons:star')
        ->and(HidePasswordAction::make()->getIcon())->toBe('heroicons:star')
        ->and(CopyAction::make()->getIcon())->toBe('heroicons:star');
});

it('marks the wrapper as a text input, binds the controller and targets the control', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')));

    $wrapper = $crawler->filter('div.sy-input-wrp.sy-fo-text-input');
    expect($wrapper->count())->toBe(1)
        ->and($wrapper->attr('data-controller'))->toBe('sylament--forms--text-input');

    $input = $crawler->filter('input.sy-fo-input');
    expect($input->attr('data-sylament--forms--text-input-target'))->toBe('input')
        ->and($input->attr('class'))->not->toContain('sy-revealable')
        ->and($crawler->filter('.sy-fo-text-input-copy-message')->count())->toBe(0);
});

it('renders both reveal buttons with their glyphs and client bindings, and mounts no live action', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('secret')->password()->revealable()));

    $show = $crawler->filter('button.sy-fo-text-input-show-password');
    $hide = $crawler->filter('button.sy-fo-text-input-hide-password');

    expect($show->count())->toBe(1)
        ->and($show->attr('data-action'))->toBe('sylament--forms--text-input#reveal')
        ->and($show->attr('aria-label'))->toBe('Show password')
        ->and($show->attr('data-live-action-param'))->toBeNull()
        ->and($show->filter('svg')->count())->toBe(1)
        ->and($hide->count())->toBe(1)
        ->and($hide->attr('data-action'))->toBe('sylament--forms--text-input#conceal')
        ->and($hide->attr('aria-label'))->toBe('Hide password')
        ->and($hide->attr('data-live-action-param'))->toBeNull()
        // The eye-slash glyph: ux-icons renders nothing at all for a name it cannot resolve.
        ->and($hide->filter('svg')->count())->toBe(1);
});

it('keeps a revealable password concealed server-side, and marks it for the ms-reveal rule', function (): void {
    $input = new Crawler(renderField(TextInput::make('secret')->password()->revealable()))
        ->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('password')
        ->and($input->attr('class'))->toContain('sy-revealable');
});

it('refuses to render a revealable input that is not a password', function (): void {
    // Twig wraps whatever a template throws, so the guard reaches a renderer as a RuntimeError
    // carrying the message; the class itself is pinned on the getter above.
    expect(static fn (): string => renderField(TextInput::make('title')->revealable()))
        ->toThrow(RuntimeError::class, 'The text input [data.title] is not a [password()], so it cannot be [revealable()].');
});

it('renders the copy button with its message and duration params beside a live region', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('token')->copyable(copyMessage: 'Token copied', copyMessageDuration: 5000),
    ));

    $copy = $crawler->filter('button.sy-fo-text-input-copy');
    expect($copy->count())->toBe(1)
        ->and($copy->attr('data-action'))->toBe('sylament--forms--text-input#copy')
        ->and($copy->attr('data-sylament--forms--text-input-message-param'))->toBe('Token copied')
        ->and($copy->attr('data-sylament--forms--text-input-duration-param'))->toBe('5000')
        ->and($copy->attr('data-live-action-param'))->toBeNull()
        // The clipboard glyph, on the same terms as the eye-slash one above.
        ->and($copy->filter('svg')->count())->toBe(1);

    $message = $crawler->filter('span.sy-fo-text-input-copy-message');
    expect($message->count())->toBe(1)
        ->and($message->attr('role'))->toBe('status')
        ->and($message->attr('aria-live'))->toBe('polite')
        ->and($message->attr('hidden'))->not->toBeNull()
        ->and($message->attr('data-sylament--forms--text-input-target'))->toBe('copyMessage')
        ->and($message->text())->toBe('');
});

it('renders a copy message closure against the render-time state', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['token' => 'abc'];

    $html = renderToHtml(Schema::make($host)->statePath('data')->components([
        TextInput::make('token')->copyable(copyMessage: static fn (mixed $state): string => "Copied {$state}"),
    ]));

    expect(new Crawler($html)->filter('button.sy-fo-text-input-copy')->attr('data-sylament--forms--text-input-message-param'))
        ->toBe('Copied abc');
});
