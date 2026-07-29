<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\IconSize;
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

it('collapses the content container for a section with no components', function (): void {
    // Filament parity: an empty child schema renders nothing, so a section without
    // components or footer emits no content container at all.
    $html = renderSection(Section::make('Empty'));

    expect($html)->not->toContain('sy-section-content');
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
    $html = renderSection(Section::make('Heading')->divided()->components([new Component()]));

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
    // extra attributes id to null. Headingless,
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
    expect(renderSection(Section::make('Heading')->id('profile')->components([new Component()])))
        ->toContain('id="profile-content"');
});

it('places the content class on the child schema root', function (): void {
    $html = renderSection(Section::make('Heading')->components([new Component()]));

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
            ->beforeLabel(Schema::make()->components([new Component()])->extraAttributes(['data-slot' => 'before-label']))
            ->afterLabel(Schema::make()->components([new Component()])->extraAttributes(['data-slot' => 'after-label'])),
    );

    expect($html)->toMatch(
        '/sy-sc-section-label-ctn.*?data-slot="before-label".*?class="sy-sc-section-label".*?data-slot="after-label"/s',
    );
});

it('does not render label slots without a label', function (): void {
    $html = renderSection(
        Section::make('Heading')->beforeLabel(Schema::make()->components([new Component()])->extraAttributes(['data-slot' => 'before-label'])),
    );

    expect($html)->not->toContain('sy-sc-section-label-ctn')
        ->not->toContain('data-slot="before-label"');
});

it('renders the above and below content slots around the section element', function (): void {
    $html = renderSection(
        Section::make('Heading')
            ->aboveContent(Schema::make()->components([new Component()])->extraAttributes(['data-slot' => 'above-content']))
            ->belowContent(Schema::make()->components([new Component()])->extraAttributes(['data-slot' => 'below-content'])),
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

    expect($html)->toMatch('/sy-section-header"\s*>\s*<svg[^>]*class="sy sy-icon sy-size-lg"/')
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

    expect($html)->toContain('class="sy sy-icon sy-size-lg sy-color sy-color-danger"');
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
        ->toContain('class="sy sy-icon sy-size-sm sy-color sy-color-danger"')
        ->toContain('aria-hidden="true"');
});

it('renders no collapse markup by default', function (): void {
    $html = renderSection(Section::make('Profile'));

    expect($html)->not->toContain('data-controller')
        ->not->toContain('sy-collapsible')
        ->not->toContain('sy-collapsed')
        ->not->toContain('sy-section-collapse-btn')
        ->not->toContain('sy-cloak');
});

it('wires the Stimulus controller on a collapsible section', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible());

    expect($html)->toContain('sy-collapsible')
        ->not->toContain('sy-collapsed')
        ->toContain('data-controller="sylament--support--section"')
        ->toContain('collapse-section@window-&gt;sylament--support--section#collapse')
        ->toContain('expand-section@window-&gt;sylament--support--section#expand')
        ->toContain('open-section@window-&gt;sylament--support--section#expand')
        ->toContain('toggle-section@window-&gt;sylament--support--section#toggle')
        ->toContain('expand-&gt;sylament--support--section#reveal')
        ->not->toContain('reset-schema-component-state')
        ->toContain('data-sylament--support--section-id-value="profile::section"')
        ->toContain('data-sylament--support--section-collapsed-value="false"')
        ->toContain('data-sylament--support--section-expand-label-value="Expand section"')
        ->toContain('data-sylament--support--section-collapse-label-value="Collapse section"')
        ->not->toContain('persist-value');
});

it('makes the header toggle the collapsible section', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible());

    expect($html)->toMatch(
        '/<header\s+class="sy-section-header" data-action="click-&gt;sylament--support--section#toggleFromHeader"/',
    );
});

it('renders the disclosure chevron button', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible()->components([new Component()]));

    expect($html)->toMatch('/<button[^>]*class="sy sy-icon-btn sy-size-md sy-section-collapse-btn"/')
        ->toContain('data-action="click-&gt;sylament--support--section#toggleFromButton"')
        ->toContain('data-sylament--support--section-target="button"')
        ->toContain('aria-expanded="true"')
        ->toContain('aria-label="Collapse section"')
        ->toContain('aria-controls="profile::section-content"')
        ->toContain('<svg')
        ->not->toContain('title=');
});

it('marks the content container as the controller content target', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible()->components([new Component()]));

    expect($html)->toContain('data-sylament--support--section-target="content"')
        ->not->toContain('sy-cloak');
});

it('renders an initially collapsed section with matching ARIA', function (): void {
    $html = renderSection(Section::make('Profile')->collapsed());

    expect($html)->toContain('sy-collapsed')
        ->toContain('data-sylament--support--section-collapsed-value="true"')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-label="Expand section"')
        ->toContain('data-controller="sylament--support--section"');
});

it('renders no collapse markup when collapsed() opts out of collapsibility', function (): void {
    $html = renderSection(Section::make('Profile')->collapsed(shouldMakeComponentCollapsible: false));

    expect($html)->not->toContain('data-controller')
        ->not->toContain('sy-collapsed')
        ->not->toContain('sy-section-collapse-btn');
});

it('resolves a collapsible closure through the real Invoker', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible(fn (): bool => true));

    expect($html)->toContain('data-controller="sylament--support--section"');
});

it('cloaks the content container when persisting the collapse state', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible()->persistCollapsed()->components([new Component()]));

    expect($html)->toContain('data-sylament--support--section-persist-value="true"')
        ->toContain('sy-cloak');
});

it('never renders an aside section as collapsible', function (): void {
    $html = renderSection(Section::make('Profile')->aside()->collapsible());

    expect($html)->not->toContain('data-controller')
        ->not->toContain('sy-collapsible')
        ->not->toContain('sy-section-collapse-btn');
});

it('renders the header for a collapsible section alone', function (): void {
    // Filament parity: `hasHeader` gains the collapsible term, so a bare
    // collapsible section still gets a header to hold the chevron.
    $html = renderSection(Section::make()->collapsible());

    expect($html)->toContain('sy-section-header')
        ->toContain('sy-section-collapse-btn')
        ->not->toContain('id-value')
        ->not->toContain('aria-controls');
});

it('renders the standalone support component as collapsible from raw props', function (): void {
    $html = $this::getContainer()->get('twig')->createTemplate(
        "{{ component('SylamentSupport:Section', { heading: 'Standalone', collapsible: true, collapsed: true }) }}",
    )->render();

    expect($html)->toContain('data-controller="sylament--support--section"')
        ->toContain('sy-collapsed')
        ->toContain('sy-section-collapse-btn')
        ->toContain('aria-label="Expand section"');
});

it('leaves the aside decision to the caller in the standalone support component', function (): void {
    // Filament parity with the support Blade component: the support layer obeys
    // the caller's `collapsible` verbatim — only the schemas layer resolves aside.
    $html = $this::getContainer()->get('twig')->createTemplate(
        "{{ component('SylamentSupport:Section', { heading: 'Standalone', collapsible: true, aside: true }) }}",
    )->render();

    expect($html)->toContain('data-controller="sylament--support--section"')
        ->toContain('sy-aside');
});

it('renders a header action button inside the after-header container at its default size', function (): void {
    $html = renderSection(Section::make('Profile')->headerActions([Action::make('publish')]));

    expect($html)->toMatch('/sy-section-header-after-ctn">\s*<div\s+class="sy sy-sc sy-inline[^"]*sy-align-end[^"]*".*?<button[^>]*class="sy sy-btn sy-size-md sy-color sy-color-primary sy-ac"/s')
        ->toContain('Publish')
        ->not->toContain('sy-size-sm');
});

it('keeps header actions inside the container the collapse toggle ignores', function (): void {
    $html = renderSection(Section::make('Profile')->collapsible()->headerActions([Action::make('publish')]));

    expect($html)->toMatch('/sy-section-header-after-ctn">\s*<div\s+class="sy sy-sc sy-inline[^"]*sy-align-end[^"]*".*?Publish/s')
        ->toContain('toggleFromHeader');
});

it('renders a footer action button inside a start-aligned footer schema', function (): void {
    $html = renderSection(Section::make('Profile')->footerActions([Action::make('save')]));

    expect($html)->toMatch('/<footer class="sy-section-footer">\s*<div\s+class="sy sy-sc sy-inline sy-align-start.*?<button[^>]*class="sy sy-btn sy-size-md sy-color sy-color-primary sy-ac"/s')
        ->toContain('Save');
});

it('aligns the footer actions schema from footerActionsAlignment', function (): void {
    $html = renderSection(
        Section::make('Profile')
            ->footerActions([Action::make('save')])
            ->footerActionsAlignment(Alignment::End),
    );

    expect($html)->toMatch('/<footer class="sy-section-footer">\s*<div\s+class="sy sy-sc sy-inline sy-align-end/');
});

it('renders no footer element without footer actions', function (): void {
    $html = renderSection(Section::make('Profile')->components([new Component()]));

    expect($html)->not->toContain('sy-section-footer')
        ->not->toContain('<footer');
});

it('defaults label slot actions to the small link template', function (): void {
    $html = renderSection(
        Section::make('Profile')->label('My label')->afterLabel([Action::make('edit')]),
    );

    expect($html)->toMatch('/<button[^>]*class="sy sy-link sy-size-sm sy-color sy-color-primary sy-ac"/')
        ->toContain('Edit');
});

it('lets an explicit button() beat the label slot link default', function (): void {
    $html = renderSection(
        Section::make('Profile')->label('My label')->afterLabel([Action::make('edit')->button()]),
    );

    expect($html)->toMatch('/<button[^>]*class="sy sy-btn sy-size-sm sy-color sy-color-primary sy-ac"/')
        ->toContain('Edit');
});

it('coerces a string slot argument into a text component', function (): void {
    $html = renderSection(Section::make('Details')->footer('Footer note'));

    expect($html)->toContain('sy-section-footer')
        ->toContain('sy-sc-text')
        ->toContain('Footer note');
});

it('coerces a Markup slot argument into a raw html component', function (): void {
    $html = renderSection(
        Section::make('Details')->afterHeader(new Markup('<span class="sy-badge">New</span>', 'UTF-8')),
    );

    expect($html)->toContain('<span class="sy-badge">New</span>');
});
