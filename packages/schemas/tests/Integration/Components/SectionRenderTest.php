<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enum\IconSize;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders a Section as a child of a real Schema, mirroring how it is used: the parent
 * Schema assigns itself as the Section's container, which supplies the live component
 * the Section's child schema needs. Asserting on the parent render exercises the whole
 * chain — Section + heading/description + nested child schema + the support Twig component.
 */
function renderSection(Section $section): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$section]));
}

it('renders the heading and description in the section header', function (): void {
    $html = renderSection(Section::make('Profile')->description('Your account details'));

    expect($html)->toContain('sy-section-header')
        ->toContain('Profile')
        ->toContain('Your account details');
});

it('resolves a heading closure through the real Invoker', function (): void {
    $html = renderSection(Section::make(fn (): string => 'Computed heading'));

    expect($html)->toContain('Computed heading');
});

it('omits the header when the section has no heading or description', function (): void {
    $html = renderSection(Section::make());

    expect($html)->not->toContain('sy-section-header');
});

it('derives the section ids from the heading key', function (): void {
    $html = renderSection(Section::make('Profile'));

    expect($html)->toContain('id="profile::section"')
        ->toContain('id="profile::section-heading"')
        ->toContain('aria-labelledby="profile::section-heading"');
});

it('renders no id attribute without a heading or custom id', function (): void {
    $html = renderSection(Section::make());

    expect($html)->not->toContain(' id=');
});

it('renders the section child components inside the content area', function (): void {
    $html = renderSection(Section::make('Details')->components([new Component()]));

    // The child Schema renders its own grid inside the section content container.
    expect($html)->toContain('sy-section-content')
        ->toContain('sy-grid');
});

it('renders an empty child schema for a section with no components', function (): void {
    // Regression: an empty default slot must still resolve to a schema (not null),
    // so the content container renders rather than collapsing.
    $html = renderSection(Section::make('Empty'));

    expect($html)->toContain('sy-section-content');
});

it('escapes a plain string heading and description', function (): void {
    $html = renderSection(Section::make('<b>Heading</b>')->description('<i>Description</i>'));

    expect($html)->toContain('&lt;b&gt;Heading&lt;/b&gt;')
        ->toContain('&lt;i&gt;Description&lt;/i&gt;')
        ->not->toContain('<b>Heading</b>')
        ->not->toContain('<i>Description</i>');
});

it('renders a Markup heading and description unescaped', function (): void {
    $html = renderSection(
        Section::make(new Markup('<em>Rich heading</em>', 'UTF-8'))
            ->description(new Markup('<em>Rich description</em>', 'UTF-8')),
    );

    expect($html)->toContain('<em>Rich heading</em>')
        ->toContain('<em>Rich description</em>');
});

it('renders the heading in an h2 by default', function (): void {
    $html = renderSection(Section::make('Profile'));

    expect($html)->toMatch('/<h2\s[^>]*class="sy-section-header-heading"\s*>/');
});

it('omits the header for a blank heading', function (string $heading): void {
    $html = renderSection(Section::make($heading));

    expect($html)->not->toContain('sy-section-header');
})->with([
    'empty string' => [''],
    'whitespace only' => ['   '],
]);

it('renders the label above the section', function (): void {
    $html = renderSection(Section::make('Heading')->label('My label'));

    expect($html)->toContain('sy-sc-section-label')
        ->toContain('My label')
        ->not->toContain('sy-sc-section-label-hidden');
});

it('keeps a hidden label in the markup as screen-reader only', function (): void {
    // Divergence from Filament (which ignores isLabelHidden() on sections): the label
    // must stay in the markup for assistive technology, hidden via CSS only.
    $html = renderSection(Section::make('Heading')->label('My label')->hiddenLabel());

    expect($html)->toContain('sy-sc-section-label-hidden')
        ->toContain('My label');
});

it('escapes a plain string label', function (): void {
    $html = renderSection(Section::make('Heading')->label('<b>Label</b>'));

    expect($html)->toContain('&lt;b&gt;Label&lt;/b&gt;')
        ->not->toContain('<b>Label</b>');
});

it('omits the label container for an absent or blank label', function (string|null $label): void {
    $html = renderSection(Section::make('Heading')->label($label));

    expect($html)->not->toContain('sy-sc-section-label-ctn');
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ['   '],
]);

it('marks the section when content is placed before the header', function (): void {
    expect(renderSection(Section::make('Heading')))
        ->not->toContain('sy-section-has-content-before');

    // The closure pins the closure-evaluated path shared by all boolean concerns.
    $html = renderSection(Section::make('Heading')->contentBefore(fn (): bool => true));

    expect($html)->toContain('sy-section-has-content-before');
});

it('renders no variant classes by default', function (): void {
    $html = renderSection(Section::make('Heading'));

    expect($html)->not->toContain('sy-compact')
        ->not->toContain('sy-divided')
        ->not->toContain('sy-secondary')
        ->not->toContain('sy-aside')
        ->not->toContain('sy-section-not-contained');
});

it('marks a compact section', function (): void {
    expect(renderSection(Section::make('Heading')->compact()))->toContain('sy-compact');
});

it('marks a divided section', function (): void {
    // With sy-section-content on the schema root, each schema child is a direct
    // child of the content element — the shape the divider CSS targets.
    $html = renderSection(Section::make('Heading')->divided());

    expect($html)->toContain('sy-divided')
        ->toMatch('/class="sy sy-sc[^"]*sy-section-content"/');
});

it('marks a secondary section', function (): void {
    expect(renderSection(Section::make('Heading')->secondary()))->toContain('sy-secondary');
});

it('marks an aside section', function (): void {
    expect(renderSection(Section::make('Heading')->aside()))->toContain('sy-aside');
});

it('resets aside() with a null condition', function (): void {
    // Tri-state per Filament: null returns the flag to unset, which renders as
    // not-aside until something (e.g. future global configuration) decides otherwise.
    $html = renderSection(Section::make('Heading')->aside()->aside(null));

    expect($html)->not->toContain('sy-aside');
});

it('marks a not-contained section', function (): void {
    expect(renderSection(Section::make('Heading')->contained(false)))
        ->toContain('sy-section-not-contained');
});

it('renders the component id on the section wrapper', function (): void {
    expect(renderSection(Section::make('Heading')->id('profile')))->toContain('id="profile"');
});

it('sanitizes the wrapper id', function (): void {
    expect(renderSection(Section::make('Heading')->id('pro"file')))->toContain('id="profile"');
});

it('renders extra attributes on the section wrapper', function (): void {
    $html = renderSection(
        Section::make('Heading')->extraAttributes(['class' => 'custom', 'data-owner' => 'billing']),
    );

    expect($html)->toContain('data-owner="billing"')
        ->toMatch('/class="sy sy-sc-section[^"]*custom"/');
});

it('prefers the component id over an extra attributes id', function (): void {
    $html = renderSection(
        Section::make('Heading')->id('real')->extraAttributes(['id' => 'fake']),
    );

    expect($html)->toContain('id="real"')
        ->not->toContain('fake');
});

it('keeps an extra attributes id when no component id is set', function (): void {
    // Divergence from Filament, which lets the unset component id clobber the
    // extra attributes id to null (docs/parity/schemas/section.md). Headingless,
    // because a heading derives a key and the key-derived id wins like a custom one.
    $html = renderSection(Section::make()->extraAttributes(['id' => 'user-id']));

    expect($html)->toContain('id="user-id"');
});

it('escapes extra attribute values on the wrapper', function (): void {
    $html = renderSection(
        Section::make('Heading')->extraAttributes(['data-x' => '"><script>alert(1)</script>']),
    );

    expect($html)->not->toContain('<script>')
        ->toContain('&quot;&gt;&lt;script&gt;');
});

it('names the section landmark from its heading', function (): void {
    $html = renderSection(Section::make('Heading')->id('profile'));

    expect($html)->toContain('aria-labelledby="profile-heading"')
        ->toMatch('/<h2\s+id="profile-heading" class="sy-section-header-heading"\s*>/');
});

it('falls back to the label for the landmark name', function (): void {
    $html = renderSection(Section::make()->label('My label')->id('profile'));

    expect($html)->toContain('aria-labelledby="profile-label"')
        ->toContain('id="profile-label"');
});

it('gives the label no id when a heading is present', function (): void {
    $html = renderSection(Section::make('Heading')->label('My label')->id('profile'));

    expect($html)->toContain('aria-labelledby="profile-heading"')
        ->not->toContain('profile-label');
});

it('omits the landmark attributes without a component id', function (): void {
    // Description-only: the header renders, but with no heading there is no
    // derived key, so the section has no id for the landmark attributes to target.
    $html = renderSection(Section::make()->description('Details'));

    expect($html)->not->toContain('aria-labelledby')
        ->not->toContain('aria-describedby');
});

it('associates the description with the section', function (): void {
    $html = renderSection(Section::make('Heading')->description('Details')->id('profile'));

    expect($html)->toContain('aria-describedby="profile-description"')
        ->toMatch('/<p\s+id="profile-description" class="sy-section-header-description"\s*>/');
});

it('identifies the content container', function (): void {
    expect(renderSection(Section::make('Heading')->id('profile')))
        ->toContain('id="profile-content"');
});

it('places the content class on the child schema root', function (): void {
    $html = renderSection(Section::make('Heading'));

    expect($html)->not->toContain('<div class="sy-section-content">')
        ->toMatch('/class="sy sy-sc[^"]*sy-section-content"/');
});

it('renders the after-header slot as an inline end-aligned schema in the header', function (): void {
    $html = renderSection(Section::make('Heading')->afterHeader([new Component()]));

    expect($html)->toMatch('/sy-section-header-after-ctn">\s*<div\s+class="sy sy-sc sy-inline[^"]*sy-align-end/');
});

it('renders the header for an after-header slot alone', function (): void {
    $html = renderSection(Section::make()->afterHeader([new Component()]));

    expect($html)->toContain('sy-section-header');
});

it('renders no slot containers or inline schemas without slots', function (): void {
    $html = renderSection(Section::make('Heading')->components([new Component()]));

    expect($html)->not->toContain('sy-section-header-after-ctn')
        ->not->toContain('sy-section-footer')
        ->not->toContain('sy-inline');
});

it('renders the footer slot as an unaligned inline schema in the content container', function (): void {
    $html = renderSection(Section::make('Heading')->footer([new Component()]));

    expect($html)->toMatch('/<footer class="sy-section-footer">\s*<div\s+class="sy sy-sc sy-inline/')
        ->not->toContain('sy-align-');
});

it('renders the label slots around the label', function (): void {
    $html = renderSection(
        Section::make('Heading')
            ->label('My label')
            ->beforeLabel(Schema::make()->extraAttributes(['data-slot' => 'before-label']))
            ->afterLabel(Schema::make()->extraAttributes(['data-slot' => 'after-label'])),
    );

    expect($html)->toMatch(
        '/sy-sc-section-label-ctn.*?data-slot="before-label".*?class="sy-sc-section-label".*?data-slot="after-label"/s',
    );
});

it('does not render label slots without a label', function (): void {
    $html = renderSection(
        Section::make('Heading')->beforeLabel(Schema::make()->extraAttributes(['data-slot' => 'before-label'])),
    );

    expect($html)->not->toContain('sy-sc-section-label-ctn')
        ->not->toContain('data-slot="before-label"');
});

it('renders the above and below content slots around the section element', function (): void {
    $html = renderSection(
        Section::make('Heading')
            ->aboveContent(Schema::make()->extraAttributes(['data-slot' => 'above-content']))
            ->belowContent(Schema::make()->extraAttributes(['data-slot' => 'below-content'])),
    );

    expect($html)->toMatch('/data-slot="above-content".*?<section.*?<\/section>.*?data-slot="below-content"/s');
});

it('cascades heading tags down nested sections', function (): void {
    $html = renderSection(
        Section::make('Outer')->components([
            Section::make('Inner'),
        ]),
    );

    expect($html)->toMatch('~<h2\s[^>]*id="outer::section-heading"~')
        ->toMatch('~<h3\s[^>]*id="inner::section-heading"~');
});

it('does not bump the heading level under a heading-less section', function (): void {
    $html = renderSection(
        Section::make()->components([
            Section::make('Inner'),
        ]),
    );

    expect($html)->toMatch('~<h2\s[^>]*id="inner::section-heading"~');
});

it('renders headings beyond h6 as paragraphs, honoring the schema root heading level', function (): void {
    $html = renderToHtml(
        Schema::make(new HasSchemasFixture())
            ->rootHeadingLevel(6)
            ->components([
                Section::make('Outer')->components([
                    Section::make('Inner'),
                ]),
            ]),
    );

    expect($html)->toMatch('~<h6\s[^>]*id="outer::section-heading"~')
        ->toMatch('~<p\s[^>]*id="inner::section-heading"~');
});

it('renders the header icon before the heading at the large default size', function (): void {
    $html = renderSection(Section::make('Profile')->icon('heroicons:x-mark'));

    expect($html)->toMatch('/sy-section-header">\s*<svg[^>]*class="sy-icon sy-size-lg"/')
        ->toContain('aria-hidden="true"');
});

it('renders the header for an icon alone', function (): void {
    $html = renderSection(Section::make()->icon('heroicons:x-mark'));

    expect($html)->toContain('sy-section-header')
        ->toContain('<svg');
});

it('renders no icon markup without an icon', function (): void {
    $html = renderSection(Section::make('Heading'));

    expect($html)->not->toContain('sy-icon')
        ->not->toContain('<svg');
});

it('marks the icon with the dynamic color classes for a palette', function (): void {
    $html = renderSection(Section::make('Heading')->icon('heroicons:x-mark')->iconColor('danger'));

    expect($html)->toContain('class="sy-icon sy-size-lg sy-color sy-color-danger"');
});

it('applies no color classes for the default or explicit gray icon color', function (): void {
    expect(renderSection(Section::make('Heading')->icon('heroicons:x-mark')))
        ->not->toContain('sy-color');

    expect(renderSection(Section::make('Heading')->icon('heroicons:x-mark')->iconColor('gray')))
        ->not->toContain('sy-color');
});

it('applies an explicit icon size from an enum case or its string value', function (): void {
    expect(renderSection(Section::make('Heading')->icon('heroicons:x-mark')->iconSize(IconSize::Small)))
        ->toContain('sy-size-sm');

    expect(renderSection(Section::make('Heading')->icon('heroicons:x-mark')->iconSize('sm')))
        ->toContain('sy-size-sm');
});

it('resolves an icon closure through the real Invoker', function (): void {
    $html = renderSection(Section::make('Heading')->icon(fn (): string => 'heroicons:x-mark'));

    expect($html)->toContain('<svg');
});

it('renders the standalone support component from raw icon props', function (): void {
    // Filament-parity for direct `<twig:SylamentSupport:Section>` use: the support
    // component itself turns icon/iconColor/iconSize values into the icon HTML.
    $html = $this::getContainer()->get('twig')->createTemplate(
        "{{ component('SylamentSupport:Section', { heading: 'Standalone', icon: 'heroicons:x-mark', iconColor: 'danger', iconSize: 'sm' }) }}",
    )->render();

    expect($html)->toContain('Standalone')
        ->toContain('class="sy-icon sy-size-sm sy-color sy-color-danger"')
        ->toContain('aria-hidden="true"');
});
