<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\TextInput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

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
]);

it('emits inputmode=decimal for numeric inputs only', function (): void {
    $numeric = new Crawler(renderField(TextInput::make('amount')->numeric()));
    expect($numeric->filter('input.sy-fo-input')->attr('inputmode'))->toBe('decimal');

    $text = new Crawler(renderField(TextInput::make('title')));
    expect($text->filter('input.sy-fo-input')->attr('inputmode'))->toBeNull();
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
