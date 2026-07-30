<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

/**
 * Wire the group into a real parent Schema so its child schema can reach the live
 * component through the container chain, as happens in a real render.
 */
function wireGroup(Group $group): Group
{
    Schema::make(new HasSchemasFixture())->components([$group])->getComponents();

    return $group;
}

it('takes its children as an array', function (): void {
    $group = wireGroup(Group::make([Text::make('one'), Text::make('two')]));

    expect($group->getChildSchema()?->getComponents())->toHaveCount(2);
});

it('takes a single component without an array', function (): void {
    $group = wireGroup(Group::make(Text::make('one')));

    expect($group->getChildSchema()?->getComponents())->toHaveCount(1);
});

it('resolves a schema closure through the real Invoker', function (): void {
    $group = wireGroup(Group::make(fn (): array => [Text::make('one')]));

    expect($group->getChildSchema()?->getComponents())->toHaveCount(1);
});

it('makes an empty group with no arguments', function (): void {
    $group = wireGroup(Group::make());

    expect($group->getChildSchema()?->getComponents())->toBe([]);
});

it('claims a single column so its children stack', function (): void {
    expect(Group::make()->getAllColumns())->toBe([
        'default' => 1,
        'sm' => null,
        'md' => null,
        'lg' => null,
        'xl' => null,
        '2xl' => null,
    ]);
});

it('applies configureUsing() closures on make()', function (): void {
    Group::configureUsing(function (Group $group): void {
        $group->id('configured');
    });

    expect(Group::make()->getId())->toBe('configured');
});
