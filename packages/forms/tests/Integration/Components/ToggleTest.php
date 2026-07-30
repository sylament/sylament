<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a toggle into a `data`-bag-bound schema and hydrate it, so the default
 * state reaches the host bag exactly as a live form host's `mount()` does.
 * `renderField()` deliberately skips hydration, so a `default()` assertion needs
 * this instead.
 */
function hydratedToggleSchema(Toggle $field): Schema
{
    $schema = Schema::make(new FieldRenderHost())->statePath('data')->components([$field]);
    $schema->hydrateState();

    return $schema;
}

/**
 * Wire a toggle into a keyed, validator-backed schema and run the host's real
 * validation over it, so the field reports the messages a failed submit leaves
 * in the host error bag. `renderField()` builds no host validator, so an
 * `aria-invalid="true"` assertion needs this instead.
 */
function validatedToggleSchema(Toggle $field): Schema
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->hydrateState();

    $host->validateSchema($schema, shouldThrow: false);

    return $schema;
}

it('renders a switch-role checkbox inside the toggle track', function (): void {
    $crawler = new Crawler(renderField(
        Toggle::make('isFeatured')->label('Featured'),
    ));

    $toggle = $crawler->filter('label.sy-fo-field-label span.sy-fo-toggle');
    expect($toggle->count())->toBe(1);

    $input = $toggle->filter('input.sy-fo-toggle-input');
    expect($input->count())->toBe(1)
        ->and($input->attr('type'))->toBe('checkbox')
        ->and($input->attr('role'))->toBe('switch')
        ->and($input->attr('id'))->toBe('isFeatured')
        ->and($input->attr('name'))->toBe('isFeatured')
        ->and($input->attr('data-model'))->toBe('norender|data.isFeatured')
        ->and($input->attr('value'))->toBeNull();

    expect($toggle->filter('span.sy-fo-toggle-track span.sy-fo-toggle-thumb')->count())->toBe(1);
});

it('renders the toggle in the content column as its own label when not inline', function (): void {
    $crawler = new Crawler(renderField(
        Toggle::make('isFeatured')->label('Featured')->inline(false),
    ));

    $control = $crawler->filter('.sy-fo-field-content-col label.sy-fo-toggle');

    expect($crawler->filter('label.sy-fo-field-label .sy-fo-toggle')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-toggle')->count())->toBe(1)
        ->and($control->count())->toBe(1)
        ->and($control->attr('for'))->toBe('isFeatured')
        ->and($control->filter('input.sy-fo-toggle-input')->attr('id'))->toBe('isFeatured');
});

it('keeps the control a plain span inside the field label when inline', function (): void {
    $crawler = new Crawler(renderField(
        Toggle::make('isFeatured')->label('Featured'),
    ));

    $control = $crawler->filter('span.sy-fo-toggle');

    expect($crawler->filter('.sy-fo-toggle')->count())->toBe(1)
        ->and($control->count())->toBe(1)
        ->and($control->attr('for'))->toBeNull()
        ->and($crawler->filter('.sy-fo-field-content-col .sy-fo-toggle')->count())->toBe(0);
});

it('defaults the state to false and casts to a non-nullable boolean', function (): void {
    $field = Toggle::make('isFeatured');
    hydratedToggleSchema($field);

    expect($field->getState())->toBeFalse();

    $field->state('1');

    expect($field->getState())->toBeTrue();
});

it('renders the checked attribute from state', function (): void {
    $checked = new Crawler(renderToHtml(hydratedToggleSchema(Toggle::make('isFeatured')->default(true))));
    $unchecked = new Crawler(renderToHtml(hydratedToggleSchema(Toggle::make('isFeatured'))));

    expect($checked->filter('input.sy-fo-toggle-input')->attr('checked'))->not->toBeNull()
        ->and($unchecked->filter('input.sy-fo-toggle-input')->attr('checked'))->toBeNull();
});

it('renders no icon spans by default', function (): void {
    $crawler = new Crawler(renderField(Toggle::make('isFeatured')));

    expect($crawler->filter('.sy-fo-toggle-icon-on')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-toggle-icon-off')->count())->toBe(0);
});

it('renders the on and off icons in the thumb', function (): void {
    $crawler = new Crawler(renderField(
        Toggle::make('isFeatured')->onIcon('heroicons:star')->offIcon('heroicons:star'),
    ));

    $thumb = $crawler->filter('.sy-fo-toggle-thumb');
    expect($thumb->filter('.sy-fo-toggle-icon-on')->count())->toBe(1)
        ->and($thumb->filter('.sy-fo-toggle-icon-on svg')->count())->toBe(1)
        ->and($thumb->filter('.sy-fo-toggle-icon-off')->count())->toBe(1)
        ->and($thumb->filter('.sy-fo-toggle-icon-off svg')->count())->toBe(1);
});

it('renders each icon span only for the setter that filled it', function (): void {
    $onOnly = new Crawler(renderField(Toggle::make('isFeatured')->onIcon('heroicons:star')));

    expect($onOnly->filter('.sy-fo-toggle-icon-on')->count())->toBe(1)
        ->and($onOnly->filter('.sy-fo-toggle-icon-on svg')->count())->toBe(1)
        ->and($onOnly->filter('.sy-fo-toggle-icon-off')->count())->toBe(0);

    $offOnly = new Crawler(renderField(Toggle::make('isFeatured')->offIcon('heroicons:star')));

    expect($offOnly->filter('.sy-fo-toggle-icon-off')->count())->toBe(1)
        ->and($offOnly->filter('.sy-fo-toggle-icon-off svg')->count())->toBe(1)
        ->and($offOnly->filter('.sy-fo-toggle-icon-on')->count())->toBe(0);
});

it('builds a boolean Type constraint', function (): void {
    $types = array_values(array_filter(
        Toggle::make('isFeatured')->getValidationConstraints(),
        fn (object $constraint): bool => $constraint instanceof Type,
    ));

    expect($types)->toHaveCount(1)
        ->and($types[0]->type)->toBe('bool');
});

it('reads the boolean validation message set after construction', function (): void {
    $types = array_values(array_filter(
        Toggle::make('isFeatured')->validationMessages([Type::class => 'Must be a boolean.'])->getValidationConstraints(),
        fn (object $constraint): bool => $constraint instanceof Type,
    ));

    expect($types)->toHaveCount(1)
        ->and($types[0]->message)->toBe('Must be a boolean.');
});

it('requires presence rather than truth', function (): void {
    $constraints = Toggle::make('isFeatured')->required()->getValidationConstraints();

    $notNulls = array_values(array_filter(
        $constraints,
        fn (object $constraint): bool => $constraint instanceof NotNull,
    ));

    expect($notNulls)->toHaveCount(1)
        ->and(array_filter($constraints, fn (object $constraint): bool => $constraint instanceof NotBlank))->toBe([]);

    $field = Toggle::make('isFeatured')->required();
    hydratedToggleSchema($field);

    $violations = $this::getContainer()->get(ValidatorInterface::class)
        ->validate($field->getState(), $field->getValidationConstraints());

    expect($field->getState())->toBeFalse()
        ->and($violations)->toHaveCount(0);
});

it('renders disabled, autofocus and aria-invalid', function (): void {
    $input = new Crawler(renderField(
        Toggle::make('isFeatured')->disabled()->autofocus(),
    ))->filter('input.sy-fo-toggle-input');

    expect($input->attr('disabled'))->not->toBeNull()
        ->and($input->attr('autofocus'))->not->toBeNull()
        ->and($input->attr('aria-invalid'))->toBe('false');
});

it('renders aria-invalid true and the message once the field is invalid', function (): void {
    $schema = validatedToggleSchema(Toggle::make('isFeatured')->accepted());

    $crawler = new Crawler(renderToHtml($schema));

    expect($crawler->filter('input.sy-fo-toggle-input')->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('.sy-fo-field-error-message')->count())->toBe(1);
});

it('emits no required attribute even when marked as required', function (): void {
    $input = new Crawler(renderField(
        Toggle::make('isFeatured')->required(),
    ))->filter('input.sy-fo-toggle-input');

    expect($input->count())->toBe(1)
        ->and($input->attr('required'))->toBeNull();
});
