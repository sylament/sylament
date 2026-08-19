<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;

/**
 * Wire the flex row into a real parent Schema so its child schema can reach the
 * live component through the container chain, as happens in a real render.
 */
function wireFlex(Flex $flex): Flex
{
    Schema::make(new HasSchemasFixture())->components([$flex])->getComponents();

    return $flex;
}

it('takes its children as an array', function (): void {
    $flex = wireFlex(Flex::make([Text::make('one'), Text::make('two')]));

    expect($flex->getChildSchema()?->getComponents())->toHaveCount(2);
});

it('resolves a schema closure through the real Invoker', function (): void {
    $flex = wireFlex(Flex::make(fn (): array => [Text::make('one')]));

    expect($flex->getChildSchema()?->getComponents())->toHaveCount(1);
});

it('has no from breakpoint by default and stores one', function (): void {
    expect(Flex::make([])->getFromBreakpoint())->toBeNull()
        ->and(Flex::make([])->from('md')->getFromBreakpoint())->toBe('md');
});

it('stores a vertical alignment', function (): void {
    expect(Flex::make([])->getVerticalAlignment())->toBeNull()
        ->and(Flex::make([])->verticallyAlignCenter()->getVerticalAlignment())->toBe(VerticalAlignment::Center);
});

it('stores an alignment and builds its class', function (): void {
    expect(Flex::make([])->getAlignmentClass())->toBeNull()
        ->and(Flex::make([])->alignEnd()->getAlignment())->toBe(Alignment::End)
        ->and(Flex::make([])->alignBetween()->getAlignmentClass())->toBe('sy-align-between');
});

it('takes the density flag from the container until set', function (): void {
    expect(wireFlex(Flex::make([]))->isDense())->toBeFalse()
        ->and(wireFlex(Flex::make([])->dense())->isDense())->toBeTrue();
});

it('entangles with a singular relationship', function (): void {
    $flex = Flex::make([])->relationship('author');

    expect($flex)->toBeInstanceOf(CanEntangleWithSingularRelationships::class)
        ->and($flex->getRelationshipName())->toBe('author')
        ->and($flex->hasRelationship())->toBeTrue();
});

it('applies configureUsing() closures on make()', function (): void {
    Flex::configureUsing(function (Flex $flex): void {
        $flex->id('configured');
    });

    expect(Flex::make([])->getId())->toBe('configured');
});
