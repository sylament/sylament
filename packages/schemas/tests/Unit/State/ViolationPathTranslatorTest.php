<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Sylament\Schemas\State\ViolationPathTranslator;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Schemas\Tests\Fixtures\Components\KeyedItemsOwner;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Components\SubtreeOwner;

it('translates a declared leaf path onto the schema state path', function (): void {
    $translator = new ViolationPathTranslator(['title' => StateField::make('title')], 'data');

    expect($translator->translate('title'))->toBe('data.title');
});

it('translates a declared nested leaf path whole', function (): void {
    $translator = new ViolationPathTranslator(['author.name' => StateField::make('name')], 'data');

    expect($translator->translate('author.name'))->toBe('data.author.name');
});

it('takes the deepest declared path when a shallower one also maps', function (): void {
    $translator = new ViolationPathTranslator([
        'author' => StateField::make('author'),
        'author.name' => StateField::make('name'),
    ], 'data');

    expect($translator->translate('author.name'))->toBe('data.author.name');
});

it('folds a violation beneath a declared leaf onto that leaf', function (): void {
    $translator = new ViolationPathTranslator(['author' => SubtreeOwner::make('author')], 'data');

    expect($translator->translate('author.name'))->toBe('data.author');
});

it('needs no prefix when the schema carries no state path', function (): void {
    $translator = new ViolationPathTranslator(['title' => StateField::make('title')], null);

    expect($translator->translate('title'))->toBe('title');
});

it('returns null for a path no declared path owns', function (): void {
    $translator = new ViolationPathTranslator(['title' => StateField::make('title')], 'data');

    expect($translator->translate('publishedAt'))->toBeNull();
});

it('returns null for the empty path a class-level constraint carries', function (): void {
    $translator = new ViolationPathTranslator(['title' => StateField::make('title')], 'data');

    expect($translator->translate(''))->toBeNull();
});

it('returns null for a path leading with an index', function (): void {
    $translator = new ViolationPathTranslator(['title' => StateField::make('title')], 'data');

    expect($translator->translate('[0].title'))->toBeNull();
});

it('resolves an index against the owning components item keys', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7', '01H-added', 'record-9'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translate('sections[2].heading'))->toBe('data.sections.record-9.heading');
});

it('resolves an index against the collection key rather than the position', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys([3 => 'record-7', 9 => 'record-9'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translate('sections[9].heading'))->toBe('data.sections.record-9.heading')
        ->and($translator->translate('sections[1].heading'))->toBe('data.sections');
});

it('resolves a string collection key', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['intro' => 'record-7'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translate('sections[intro].heading'))->toBe('data.sections.record-7.heading');
});

it('translates a violation on the collection itself onto the component path', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')->itemKeys(['record-7']),
    ], 'data');

    expect($translator->translate('sections'))->toBe('data.sections');
});

it('stops at the component when the index names no item', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')->itemKeys(['record-7']),
    ], 'data');

    expect($translator->translate('sections[4].heading'))->toBe('data.sections');
});

it('stops at the component when the index names a key the collection does not hold', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')->itemKeys(['record-7']),
    ], 'data');

    expect($translator->translate('sections[alpha].heading'))->toBe('data.sections');
});

it('stops at the item when the violation names a path the item declares nothing for', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translate('sections[0].internalNote'))->toBe('data.sections.record-7');
});

it('stops at the item for a class-level violation on the item record', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translate('sections[0]'))->toBe('data.sections.record-7');
});

it('folds a violation beneath a declared item path onto that path', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['meta.locale']),
    ], 'data');

    expect($translator->translate('sections[0].meta.locale.code'))->toBe('data.sections.record-7.meta.locale');
});

it('stops at the item when a second index nests inside it', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['tags']),
    ], 'data');

    expect($translator->translate('sections[0].tags[1].name'))->toBe('data.sections.record-7.tags');
});

it('ignores an index against a component that keys no items', function (): void {
    $translator = new ViolationPathTranslator(['meta' => SubtreeOwner::make('meta')], 'data');

    expect($translator->translate('meta[0].locale'))->toBe('data.meta');
});

it('answers the translated path as the error path when the owning component declares validation constraints', function (): void {
    $translator = new ViolationPathTranslator(['spine.code' => ConstrainedField::make('code'), 'spine' => StateGroup::make()->statePath('spine')], 'data');

    expect($translator->translateToErrorPath('spine.code'))->toBe('data.spine.code');
});

it('answers the item path as the error path when the owning component keys items, though it declares no constraints', function (): void {
    $sections = new class extends Component implements HasKeyedItems {
        public function getItemKeys(): array
        {
            return ['record-7'];
        }

        public function getItemStatePaths(): array
        {
            return ['heading'];
        }
    };

    $translator = new ViolationPathTranslator(['sections' => $sections->statePath('sections')], 'data');

    expect($translator->translateToErrorPath('sections[0].heading'))->toBe('data.sections.record-7.heading');
});

it('answers no error path for a violation that stops at an item, though translate() names the item', function (string $path): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['heading', 'tags']),
    ], 'data');

    expect($translator->translate($path))->toBe('data.sections.record-7')
        ->and($translator->translateToErrorPath($path))->toBeNull();
})->with([
    'a class-level violation on the item record' => 'sections[0]',
    'an item property no item field maps' => 'sections[0].internalNote',
    'an index nesting under an unmapped item property' => 'sections[0].notes[1].body',
]);

it('answers the item field as the error path when a declared item path takes the remainder', function (): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['heading', 'tags']),
    ], 'data');

    expect($translator->translateToErrorPath('sections[0].heading.length'))->toBe('data.sections.record-7.heading')
        ->and($translator->translateToErrorPath('sections[0].tags[1].name'))->toBe('data.sections.record-7.tags');
});

it('answers the keyed-items component itself as the error path for a violation on the collection or an index naming no item', function (string $path): void {
    $translator = new ViolationPathTranslator([
        'sections' => KeyedItemsOwner::make('sections')
            ->itemKeys(['record-7'])
            ->itemStatePaths(['heading']),
    ], 'data');

    expect($translator->translateToErrorPath($path))->toBe('data.sections');
})->with([
    'the collection itself' => 'sections',
    'an index naming no item' => 'sections[4].heading',
]);

it('answers no error path when the owning component shows no errors, though translate() names its path', function (): void {
    $translator = new ViolationPathTranslator(['spine' => StateGroup::make()->statePath('spine')], 'data');

    expect($translator->translate('spine.code'))->toBe('data.spine')
        ->and($translator->translateToErrorPath('spine.code'))->toBeNull()
        ->and($translator->translateToErrorPath('spine'))->toBeNull();
});

it('answers no error path for a path no declared path owns, the empty path or one Symfony cannot parse', function (string $path): void {
    $translator = new ViolationPathTranslator(['title' => ConstrainedField::make('title')], 'data');

    expect($translator->translateToErrorPath($path))->toBeNull();
})->with(['unmapped' => 'publishedAt', 'empty' => '', 'unparseable' => 'title[', 'leading index' => '[0].title']);
