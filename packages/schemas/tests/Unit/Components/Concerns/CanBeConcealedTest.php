<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

/**
 * Bind every component in the tree to its container, as a real render does.
 */
function wireConcealmentTree(Component ...$components): void
{
    Schema::make(new HasSchemasFixture())->components($components)->getFlatComponents();
}

it('is not concealed at the root or inside a plain group', function (): void {
    $field = Text::make('x');
    wireConcealmentTree(Group::make([$field]));

    expect($field->isConcealed())->toBeFalse()
        ->and($field->getConcealingComponent())->toBeNull();
});

it('is concealed by a collapsible section and names it', function (): void {
    $field = Text::make('x');
    $section = Section::make('S')->collapsible()->components([$field]);
    wireConcealmentTree($section);

    expect($field->getConcealingComponent())->toBe($section)
        ->and($field->isConcealed())->toBeTrue();
});

it('is not concealed by a non-collapsible section', function (): void {
    $field = Text::make('x');
    wireConcealmentTree(Section::make('S')->components([$field]));

    expect($field->getConcealingComponent())->toBeNull()
        ->and($field->isConcealed())->toBeFalse();
});

it('walks up through a group to a concealing ancestor', function (): void {
    $field = Text::make('x');
    $section = Section::make('S')->collapsible()->components([Group::make([$field])]);
    wireConcealmentTree($section);

    expect($field->getConcealingComponent())->toBe($section);
});

it('memoises the answer', function (): void {
    $field = Text::make('x');
    $section = Section::make('S')->collapsible()->components([$field]);
    wireConcealmentTree($section);

    expect($field->getConcealingComponent())->toBe($section);

    $section->collapsible(false);

    expect($section->canConcealComponents())->toBeFalse()
        ->and($field->getConcealingComponent())->toBe($section)
        ->and($field->isConcealed())->toBeTrue();
});

it('memoises the absence of a concealer', function (): void {
    $field = Text::make('x');
    $section = Section::make('S')->components([$field]);
    wireConcealmentTree($section);

    expect($field->getConcealingComponent())->toBeNull();

    $section->collapsible();

    expect($field->getConcealingComponent())->toBeNull();
});
