<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enums\Alignment;

it('appends footer actions across successive calls', function (): void {
    $one = Action::make('one');
    $two = Action::make('two');

    $section = Section::make('Profile')
        ->footerActions([$one])
        ->footerActions([$two]);

    expect($section->getFooterActions())->toBe([$one, $two]);
});

it('stamps the section onto prepared footer actions and action groups', function (): void {
    $action = Action::make('save');
    $group = ActionGroup::make([Action::make('inner')]);
    $section = Section::make('Profile')->footerActions([$action, $group]);

    expect($section->getFooterActions())->toBe([$action, $group])
        ->and($action->getSchemaComponent())->toBe($section)
        ->and($group->getSchemaComponent())->toBe($section);
});

it('defaults the footer actions alignment to start', function (): void {
    expect(Section::make('Profile')->getFooterActionsAlignment())->toBe(Alignment::Start);
});

it('evaluates a footer actions alignment closure', function (): void {
    $section = Section::make('Profile')->footerActionsAlignment(fn (): Alignment => Alignment::Center);

    expect($section->getFooterActionsAlignment())->toBe(Alignment::Center);
});

it('start-aligns the seeded footer slot schema by default', function (): void {
    $section = Section::make('Profile')->footerActions([Action::make('save')]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    $slot = $section->getChildSchema(Section::FOOTER_SCHEMA_KEY);

    expect($slot?->getAlignment())->toBe(Alignment::Start)
        ->and($slot?->isInline())->toBeTrue()
        ->and($slot?->isEmbeddedInParentComponent())->toBeTrue();
});

it('maps each footer alignment onto the matching schema factory', function (?Alignment $alignment, Alignment $expected): void {
    $section = Section::make('Profile')
        ->footerActions([Action::make('save')])
        ->footerActionsAlignment($alignment);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    expect($section->getChildSchema(Section::FOOTER_SCHEMA_KEY)?->getAlignment())->toBe($expected);
})->with([
    'start' => [Alignment::Start, Alignment::Start],
    'left' => [Alignment::Left, Alignment::Start],
    'center' => [Alignment::Center, Alignment::Center],
    'end' => [Alignment::End, Alignment::End],
    'right' => [Alignment::Right, Alignment::End],
    'between' => [Alignment::Between, Alignment::Between],
    'justify' => [Alignment::Justify, Alignment::Between],
    'unset' => [null, Alignment::Start],
]);

it('seeds the footer slot schema with the footer actions', function (): void {
    $action = Action::make('save');
    $section = Section::make('Profile')->footerActions([$action]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    $slot = $section->getChildSchema(Section::FOOTER_SCHEMA_KEY);

    expect($slot?->getComponents())->toBe([$action])
        ->and($action->getSchemaContainer())->toBe($slot)
        ->and($action->getSchemaComponent())->toBe($section);
});

it('lets a user footer() call replace the seeded footer actions', function (): void {
    $replacement = new Component();
    $section = Section::make('Profile')
        ->footerActions([Action::make('save')])
        ->footer([$replacement]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();

    $slot = $section->getChildSchema(Section::FOOTER_SCHEMA_KEY);

    expect($slot?->getComponents())->toBe([$replacement])
        ->and($slot?->getAlignment())->toBeNull();
});
