<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;

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
        ->and($ctn->filter('.sy-fo-field-content > input.sy-fo-input')->count())->toBe(1)
        ->and($ctn->text())->toContain('Before')
        ->and($ctn->text())->toContain('After');
});

it('makes the label screen-reader-only with hiddenLabel', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hiddenLabel(),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('class'))->toContain('sy-fo-sr-only')
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
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
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

it('still reports both bounds on a required field, whose NotBlank survives the gate', function (): void {
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
