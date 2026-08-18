<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\FusedGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

function renderFusedGroup(FusedGroup $group): Crawler
{
    return new Crawler(renderToHtml(
        Schema::make(new FieldRenderHost())->statePath('data')->components([$group]),
    ));
}

function priceRange(): FusedGroup
{
    return FusedGroup::make([TextInput::make('min'), TextInput::make('max')])->label('Price range');
}

it('renders the group root as the field wrapper content, under one group label', function (): void {
    $crawler = renderFusedGroup(priceRange());

    $label = $crawler->filter('.sy-fo-field > .sy-fo-field-label-col .sy-fo-field-label-content');

    expect($crawler->filter('.sy-fo-field > .sy-fo-field-content-col > .sy-fo-fused-group')->count())->toBe(1)
        ->and($label->count())->toBe(1)
        ->and($label->text())->toBe('Price range');
});

it('makes the child schema the group root immediate child', function (): void {
    $children = renderFusedGroup(priceRange())->filter('.sy-fo-fused-group')->children();

    expect($children->count())->toBe(1)
        ->and($children->first()->attr('class'))->toContain('sy-sc');
});

it('marks the group root as a labelled group', function (): void {
    $crawler = renderFusedGroup(priceRange()->key('range'));
    $root = $crawler->filter('.sy-fo-fused-group');

    expect($root->attr('role'))->toBe('group')
        ->and($root->attr('id'))->toBe('range')
        ->and($root->attr('aria-labelledby'))->toBe('range-label')
        ->and($crawler->filter('#range-label')->text())->toBe('Price range');
});

it('renders the child fields through the plain wrapper branch', function (): void {
    $crawler = renderFusedGroup(priceRange());

    expect($crawler->filter('.sy-fo-fused-group .sy-fo-field')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-fused-group label.sy-fo-field-label.sy-fo-sr-only')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-fused-group .sy-fo-field-label-col')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-fused-group .sy-fo-field-content-col')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-fused-group .sy-input-wrp')->count())->toBe(2);
});

it('leaves a field outside a fused group on the full wrapper', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')->label('Title')));

    expect($crawler->filter('.sy-fo-field-label-col')->count())->toBe(1)
        ->and($crawler->filter('label.sy-fo-field-label:not(.sy-fo-sr-only)')->count())->toBe(1)
        ->and($crawler->filter('.sy-fo-field-content-col')->count())->toBe(1);
});

it('drops the gap between the fused children', function (): void {
    $schema = renderFusedGroup(priceRange())->filter('.sy-fo-fused-group > .sy-sc');

    expect($schema->count())->toBe(1)
        ->and($schema->attr('class'))->not->toContain('sy-sc-has-gap');
});

it('marks the group required when a child is', function (): void {
    $required = FusedGroup::make([TextInput::make('min')->required(), TextInput::make('max')])
        ->label('Price range');

    expect($required->isMarkedAsRequired())->toBeTrue()
        ->and(renderFusedGroup($required)->filter('.sy-fo-field-label-required-mark')->count())->toBe(1)
        ->and(renderFusedGroup(priceRange())->filter('.sy-fo-field-label-required-mark')->count())->toBe(0);
});

it('surfaces a child validation error once, at group level', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)
        ->key('schema')
        ->statePath('data')
        ->components([
            FusedGroup::make([TextInput::make('min')->required(), TextInput::make('max')])
                ->label('Price range'),
        ]);

    try {
        $schema->validate();
    } catch (ValidationException) {
    }

    $crawler = new Crawler(renderToHtml($schema));

    expect($crawler->filter('.sy-fo-field-error-message')->count())->toBe(1)
        ->and($crawler->filter('.sy-fo-field-error-message')->text())->toBe('This value should not be blank.')
        ->and($crawler->filter('.sy-fo-fused-group')->attr('aria-invalid'))->toBe('true');
});

it('marks every child schema with the plain field wrapper view', function (): void {
    $group = priceRange()->belowContent(Text::make('Both ends are inclusive.'));

    expect($group->getChildSchema()?->getFieldWrapperView())->toBe(FusedGroup::PLAIN_FIELD_WRAPPER_VIEW)
        ->and($group->getChildSchema(FusedGroup::BELOW_CONTENT_SCHEMA_KEY)?->getFieldWrapperView())
        ->toBe(FusedGroup::PLAIN_FIELD_WRAPPER_VIEW);
});

it('aligns the after-label slot to the end', function (): void {
    $group = priceRange()->afterLabel(Text::make('optional'));

    expect($group->getChildSchema(FusedGroup::AFTER_LABEL_SCHEMA_KEY)?->getAlignment())->toBe(Alignment::End)
        ->and($group->getChildSchema()?->getAlignment())->toBeNull();
});

it('inlines and embeds the six inline slots only', function (): void {
    $group = priceRange()
        ->beforeLabel(Text::make('before label'))
        ->afterLabel(Text::make('after label'))
        ->aboveContent(Text::make('above content'))
        ->belowContent(Text::make('below content'))
        ->aboveErrorMessage(Text::make('above error'))
        ->belowErrorMessage(Text::make('below error'))
        ->aboveLabel(Text::make('above label'))
        ->belowLabel(Text::make('below label'))
        ->beforeContent(Text::make('before content'))
        ->afterContent(Text::make('after content'));

    $inline = [
        FusedGroup::BEFORE_LABEL_SCHEMA_KEY,
        FusedGroup::AFTER_LABEL_SCHEMA_KEY,
        FusedGroup::ABOVE_CONTENT_SCHEMA_KEY,
        FusedGroup::BELOW_CONTENT_SCHEMA_KEY,
        FusedGroup::ABOVE_ERROR_MESSAGE_SCHEMA_KEY,
        FusedGroup::BELOW_ERROR_MESSAGE_SCHEMA_KEY,
    ];

    foreach ($inline as $key) {
        expect($group->getChildSchema($key)?->isInline())->toBeTrue()
            ->and($group->getChildSchema($key)?->isEmbeddedInParentComponent())->toBeTrue();
    }

    $notInline = [
        FusedGroup::ABOVE_LABEL_SCHEMA_KEY,
        FusedGroup::BELOW_LABEL_SCHEMA_KEY,
        FusedGroup::BEFORE_CONTENT_SCHEMA_KEY,
        FusedGroup::AFTER_CONTENT_SCHEMA_KEY,
    ];

    foreach ($notInline as $key) {
        expect($group->getChildSchema($key)?->isInline())->toBeFalse();
    }
});

it('renders a component handed to a slot', function (): void {
    $crawler = renderFusedGroup(priceRange()->belowContent(Text::make('Both ends are inclusive.')));

    expect($crawler->filter('.sy-fo-field-content-col .sy-sc-text')->text())
        ->toBe('Both ends are inclusive.');
});

it('renders the helper text below the group', function (): void {
    $crawler = renderFusedGroup(priceRange()->helperText('In euros.'));

    expect($crawler->filter('.sy-fo-field-content-col .sy-sc-text')->text())->toBe('In euros.');
});
