<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;

it('has no schema association by default', function (): void {
    $action = Action::make('edit');

    expect($action->getSchemaComponent())->toBeNull()
        ->and($action->getSchemaContainer())->toBeNull();
});

it('holds schema component and container back-references', function (): void {
    $component = Section::make('Box');
    $schema = Schema::make();

    $action = Action::make('edit')
        ->schemaComponent($component)
        ->schemaContainer($schema);

    expect($action->getSchemaComponent())->toBe($component)
        ->and($action->getSchemaContainer())->toBe($schema);
});

it('round-trips the schema component and container directly on an ActionGroup', function (): void {
    $component = Section::make('Box');
    $schema = Schema::make();

    $group = ActionGroup::make([])
        ->schemaComponent($component)
        ->schemaContainer($schema);

    expect($group->getSchemaComponent())->toBe($component)
        ->and($group->getSchemaContainer())->toBe($schema);
});

it('falls back to the schema container parent component when no schema component is set', function (): void {
    $component = Section::make('Box');
    $schema = Schema::make()->parentComponent($component);

    $action = Action::make('edit')->schemaContainer($schema);

    expect($action->getSchemaComponent())->toBe($component);
});

it('falls back to the group schema component when neither is set directly', function (): void {
    $component = Section::make('Box');
    $group = ActionGroup::make([])->schemaComponent($component);

    $action = Action::make('edit')->group($group);

    expect($action->getSchemaComponent())->toBe($component);
});

it('falls back to the group schema container when neither is set directly', function (): void {
    $schema = Schema::make();
    $group = ActionGroup::make([])->schemaContainer($schema);

    $action = Action::make('edit')->group($group);

    expect($action->getSchemaContainer())->toBe($schema);
});
