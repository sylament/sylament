<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('is not a nullable branch by default', function (): void {
    expect(Section::make('author')->isNullableBranch())->toBeFalse();
});

it('becomes a nullable branch once declared', function (): void {
    expect(Section::make('author')->nullWhenBlank()->isNullableBranch())->toBeTrue();
});

it('nulls the branch when every leaf is blank', function (): void {
    $section = Section::make('author')->nullWhenBlank();

    expect($section->shouldNullBranch(['name' => null, 'handle' => '']))->toBeTrue();
});

it('keeps the branch when any leaf is filled', function (): void {
    $section = Section::make('author')->nullWhenBlank();

    expect($section->shouldNullBranch(['name' => 'Ada', 'handle' => null]))->toBeFalse();
});

it('treats a nested blank sub-branch as blank', function (): void {
    $section = Section::make('author')->nullWhenBlank();

    expect($section->shouldNullBranch(['name' => null, 'address' => ['city' => null]]))->toBeTrue();
});

it('treats a filled nested sub-branch as filled', function (): void {
    $section = Section::make('author')->nullWhenBlank();

    expect($section->shouldNullBranch(['name' => null, 'address' => ['city' => 'Paris']]))->toBeFalse();
});

it('lets a closure decide with the branch state injected', function (): void {
    $section = Section::make('author')
        ->nullWhenBlank(fn (array $state): bool => ($state['name'] ?? null) === 'drop');

    expect($section->shouldNullBranch(['name' => 'drop', 'handle' => 'kept']))->toBeTrue()
        ->and($section->shouldNullBranch(['name' => 'Ada', 'handle' => null]))->toBeFalse();
});

it('never nulls when declared false', function (): void {
    $section = Section::make('author')->nullWhenBlank(false);

    expect($section->isNullableBranch())->toBeFalse()
        ->and($section->shouldNullBranch(['name' => null]))->toBeFalse();
});

it('judges a wired branch by its declared leaves only', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([StateField::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null, 'unmanaged' => 'kept elsewhere']]);

    $section = $schema->getComponentByStatePath('data.author.name')->getContainer()->getParentComponent();

    expect($section->shouldNullBranch(['name' => null, 'unmanaged' => 'kept elsewhere']))->toBeTrue()
        ->and($section->shouldNullBranch(['name' => 'Ada', 'unmanaged' => null]))->toBeFalse();
});

it('keeps a wired branch alive through a deep filled leaf', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([
                    Section::make('address')
                        ->statePath('address')
                        ->components([StateField::make('city')]),
                ]),
        ]);

    $section = $schema->getComponentByStatePath('data.author.address.city')
        ->getContainer()->getParentComponent()
        ->getContainer()->getParentComponent();

    expect($section->shouldNullBranch(['address' => ['city' => 'Paris']]))->toBeFalse()
        ->and($section->shouldNullBranch(['address' => ['city' => null]]))->toBeTrue();
});
