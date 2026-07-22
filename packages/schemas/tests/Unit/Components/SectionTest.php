<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enum\Alignment;

/**
 * Wire the section into a real parent Schema so its child slot schemas can reach
 * the live component through the container chain, as happens in a real render.
 */
function wireSection(Section $section): Section
{
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    return $section;
}

it('applies configureUsing() closures on make()', function (): void {
    Section::configureUsing(function (Section $section): void {
        $section->description('configured globally');
    });

    expect(Section::make('Settings')->getDescription())->toBe('configured globally');
});

it('passes the section to configuration closures as $component', function (): void {
    $section = Section::make('Settings');

    $received = null;
    $section->description(function (Section $component) use (&$received): string {
        $received = $component;

        return 'description';
    });

    expect($section->getDescription())->toBe('description')
        ->and($received)->toBe($section);
});

it('passes the section to closures type-hinting the base component class', function (): void {
    $section = Section::make('Settings');

    $received = null;
    $section->description(function (Component $schemaComponent) use (&$received): string {
        $received = $schemaComponent;

        return 'description';
    });
    $section->getDescription();

    expect($received)->toBe($section);
});

it('derives its key from the heading slug', function (): void {
    expect(Section::make('Personal Details')->getKey(isAbsolute: false))->toBe('personal-details::section');
});

it('transliterates the heading when deriving the key', function (): void {
    expect(Section::make('Účet používateľa')->getKey(isAbsolute: false))->toBe('ucet-pouzivatela::section');
});

it('has no key without a heading', function (): void {
    expect(Section::make()->getKey())->toBeNull();
});

it('lets an explicit key override the derived one', function (): void {
    expect(Section::make('Profile')->key('custom')->getKey(isAbsolute: false))->toBe('custom');
});

it('does not prefix child component keys with the section key', function (): void {
    $child = new Component()->key('name');
    $section = Section::make('Profile')->components([$child]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();
    $section->getChildSchema()?->getComponents();

    expect($child->getKey())->toBe('name')
        ->and($section->getKey())->toBe('profile::section');
});

it('exposes the Filament slot key constants', function (): void {
    expect(Section::AFTER_HEADER_SCHEMA_KEY)->toBe('after_header')
        ->and(Section::FOOTER_SCHEMA_KEY)->toBe('footer')
        ->and(Section::BEFORE_LABEL_SCHEMA_KEY)->toBe('before_label')
        ->and(Section::AFTER_LABEL_SCHEMA_KEY)->toBe('after_label')
        ->and(Section::ABOVE_CONTENT_SCHEMA_KEY)->toBe('above_content')
        ->and(Section::BELOW_CONTENT_SCHEMA_KEY)->toBe('below_content');
});

it('configures the after-header slot schema as inline, embedded, and end-aligned', function (): void {
    $component = new Component();
    $section = wireSection(Section::make('Profile')->afterHeader([$component]));

    $slot = $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);

    expect($slot?->isInline())->toBeTrue()
        ->and($slot?->isEmbeddedInParentComponent())->toBeTrue()
        ->and($slot?->getAlignment())->toBe(Alignment::End)
        ->and($slot?->getParentComponent())->toBe($section)
        ->and($slot?->getComponents())->toBe([$component]);
});

it('end-aligns the after-label slot schema too', function (): void {
    $section = wireSection(Section::make('Profile')->afterLabel([new Component()]));

    expect($section->getChildSchema(Section::AFTER_LABEL_SCHEMA_KEY)?->getAlignment())->toBe(Alignment::End);
});

it('configures the remaining slot schemas as inline and embedded without alignment', function (): void {
    $section = wireSection(
        Section::make('Profile')
            ->footer([new Component()])
            ->beforeLabel([new Component()])
            ->aboveContent([new Component()])
            ->belowContent([new Component()]),
    );

    foreach ([
        Section::FOOTER_SCHEMA_KEY,
        Section::BEFORE_LABEL_SCHEMA_KEY,
        Section::ABOVE_CONTENT_SCHEMA_KEY,
        Section::BELOW_CONTENT_SCHEMA_KEY,
    ] as $key) {
        $slot = $section->getChildSchema($key);

        expect($slot?->isInline())->toBeTrue()
            ->and($slot?->isEmbeddedInParentComponent())->toBeTrue()
            ->and($slot?->getAlignment())->toBeNull();
    }
});

it('keeps the default child schema out of the slot configuration', function (): void {
    $section = wireSection(Section::make('Profile')->components([new Component()]));

    $default = $section->getChildSchema();

    expect($default?->isInline())->toBeFalse()
        ->and($default?->isEmbeddedInParentComponent())->toBeFalse();
});

it('returns null for an unset slot', function (): void {
    expect(Section::make('Profile')->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->toBeNull();
});

it('clears a slot with null', function (): void {
    $section = wireSection(Section::make('Profile')->afterHeader([new Component()]));

    expect($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->not->toBeNull();

    $section->afterHeader(null);

    expect($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->toBeNull();
});

it('evaluates a slot closure with the section as $component', function (): void {
    $received = null;
    $section = wireSection(Section::make('Profile')->afterHeader(function (Section $component) use (&$received): array {
        $received = $component;

        return [new Component()];
    }));

    $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);

    expect($received)->toBe($section);
});

it('adopts a schema instance passed to a slot without the array-path alignment default', function (): void {
    $slotSchema = Schema::make()->components([new Component()]);
    $section = wireSection(Section::make('Profile')->afterHeader($slotSchema));

    $resolved = $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);

    // Filament parity: the End default lives in makeChildSchema(), which only runs for
    // array/closure slot values — a passed Schema instance keeps its own alignment.
    expect($resolved)->toBe($slotSchema)
        ->and($resolved?->isInline())->toBeTrue()
        ->and($resolved?->isEmbeddedInParentComponent())->toBeTrue()
        ->and($resolved?->getParentComponent())->toBe($section)
        ->and($resolved?->getAlignment())->toBeNull();
});

it('keeps a factory alignment on a schema instance passed to a slot', function (): void {
    $section = wireSection(Section::make('Profile')->afterHeader(Schema::end([new Component()])));

    expect($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY)?->getAlignment())->toBe(Alignment::End);
});

it('resolves slots identically across repeated reads', function (): void {
    $section = wireSection(Section::make('Profile')->afterHeader([new Component()]));

    $first = $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);
    $second = $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);

    expect($first)->not->toBe($second)
        ->and($second?->isInline())->toBeTrue()
        ->and($second?->getAlignment())->toBe(Alignment::End);

    $slotSchema = Schema::make()->components([new Component()]);
    $section->afterHeader($slotSchema);

    expect($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->toBe($slotSchema)
        ->and($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->toBe($slotSchema)
        ->and($slotSchema->isInline())->toBeTrue();
});

it('implements the CanConcealComponents contract', function (): void {
    expect(Section::make('Profile'))->toBeInstanceOf(CanConcealComponents::class)
        ->and(Section::make('Profile')->canConcealComponents())->toBeFalse()
        ->and(Section::make('Profile')->collapsible()->canConcealComponents())->toBeTrue();
});

it('conceals components even for an aside section', function (): void {
    // Filament parity: the contract tracks isCollapsible() alone — only the
    // rendering suppresses collapse for aside sections.
    expect(Section::make('Profile')->aside()->collapsible()->canConcealComponents())->toBeTrue();
});

it('counts one heading when a heading is present', function (): void {
    expect(Section::make('Details')->getHeadingsCount())->toBe(1);
});

it('counts no headings without a heading', function (): void {
    expect(Section::make()->getHeadingsCount())->toBe(0);
});

it('counts no headings for a whitespace-only heading', function (): void {
    expect(Section::make('   ')->getHeadingsCount())->toBe(0);
});
