<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders the Group as a child of a real Schema, the way it is used: the parent
 * Schema assigns itself as the Group's container, which supplies the live component
 * the Group's own child schema needs.
 */
function renderGroup(Group $group): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$group]));
}

it('renders a host div carrying the schema-component classes', function (): void {
    $html = renderGroup(Group::make());

    expect($html)->toContain('class="sy sy-sc-group"');
});

it('renders its children', function (): void {
    $html = renderGroup(Group::make([Text::make('Title')]));

    expect($html)->toContain('sy-sc-text')
        ->toContain('Title');
});

it('stacks its children in a single column', function (): void {
    $html = renderGroup(Group::make([Text::make('Title')]));

    expect($html)->toContain('--cols-default: repeat(1, minmax(0, 1fr))')
        ->not->toContain('--cols-lg');
});

it('renders a custom id and extra attributes on the host div', function (): void {
    $html = renderGroup(Group::make()->id('stack')->extraAttributes(['data-role' => 'group']));

    expect($html)->toContain('id="stack"')
        ->toContain('data-role="group"');
});

it('prefers the component id over an extra attributes id', function (): void {
    $html = renderGroup(Group::make()->id('real')->extraAttributes(['id' => 'fake']));

    expect($html)->toContain('id="real"')
        ->not->toContain('fake');
});
