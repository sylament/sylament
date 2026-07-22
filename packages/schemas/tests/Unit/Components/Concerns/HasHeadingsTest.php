<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

/**
 * Wire the component into a real parent Schema so the heading level can cascade
 * down from the container chain, as happens in a real render.
 */
function makeContained(Component $component, int $rootHeadingLevel = 2): Component
{
    Schema::make(new HasSchemasFixture())
        ->rootHeadingLevel($rootHeadingLevel)
        ->components([$component])
        ->getComponents();

    return $component;
}

it('derives the heading level from the container root level', function (): void {
    $component = makeContained(new Component());

    expect($component->getHeadingLevel())->toBe(2)
        ->and($component->getHeadingLevel(1))->toBe(3);
});

it('maps the heading level to a heading tag', function (): void {
    $component = makeContained(new Component(), rootHeadingLevel: 3);

    expect($component->getHeadingTag())->toBe('h3')
        ->and($component->getHeadingTag(2))->toBe('h5');
});

it('caps the heading tag at p beyond h6', function (): void {
    $component = makeContained(new Component(), rootHeadingLevel: 6);

    expect($component->getHeadingTag())->toBe('h6')
        ->and($component->getHeadingTag(1))->toBe('p');
});

it('has no headings by default, keeping the child schema at the same level', function (): void {
    $component = makeContained(new Component());

    expect($component->getHeadingsCount())->toBe(0)
        ->and($component->getChildSchemaRootHeadingLevel())->toBe(2);
});

it('bumps the child schema root level by the headings count', function (): void {
    $section = makeContained(Section::make('Details'));

    expect($section->getChildSchemaRootHeadingLevel())->toBe(3);
});

it('cascades down nested sections', function (): void {
    $inner = Section::make('Inner');
    $outer = makeContained(Section::make('Outer')->components([$inner]));

    $outer->getChildSchema()->getComponents();

    expect($outer->getHeadingTag())->toBe('h2')
        ->and($inner->getHeadingTag())->toBe('h3');
});
