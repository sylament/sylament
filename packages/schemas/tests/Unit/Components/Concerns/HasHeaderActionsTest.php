<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('appends header actions across successive calls', function (): void {
    $one = Action::make('one');
    $two = Action::make('two');

    $section = Section::make('Profile')
        ->headerActions([$one])
        ->headerActions([$two]);

    expect($section->getHeaderActions())->toBe([$one, $two]);
});

it('evaluates a closure entry and wraps its single-action result', function (): void {
    $action = Action::make('lazy');
    $received = null;

    $section = Section::make('Profile');
    $section->headerActions([
        function (Section $component) use (&$received, $action): Action {
            $received = $component;

            return $action;
        },
    ]);

    expect($section->getHeaderActions())->toBe([$action])
        ->and($received)->toBe($section);
});

it('stamps the section as the schema component on every prepared header action', function (): void {
    $action = Action::make('save');
    $section = Section::make('Profile')->headerActions([$action]);

    expect($section->getHeaderActions())->toBe([$action])
        ->and($action->getSchemaComponent())->toBe($section);
});

it('prepares an action group through prepareActionGroup', function (): void {
    $member = Action::make('inner');
    $group = ActionGroup::make([$member]);
    $section = Section::make('Profile')->headerActions([$group]);

    expect($section->getHeaderActions())->toBe([$group])
        ->and($group->getSchemaComponent())->toBe($section)
        ->and($member->getSchemaComponent())->toBe($section);
});

it('re-evaluates header actions on every call without caching', function (): void {
    $calls = 0;

    $section = Section::make('Profile');
    $section->headerActions([
        function () use (&$calls): Action {
            $calls++;

            return Action::make('fresh');
        },
    ]);

    $section->getHeaderActions();
    $section->getHeaderActions();

    expect($calls)->toBe(2);
});

it('yields no after-header slot schema without header actions', function (): void {
    expect(Section::make('Profile')->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY))->toBeNull();
});

it('seeds the after-header slot schema with the header actions', function (): void {
    $action = Action::make('save');
    $section = Section::make('Profile')->headerActions([$action]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    $slot = $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);

    expect($slot?->getComponents())->toBe([$action])
        ->and($action->getSchemaContainer())->toBe($slot);
});
