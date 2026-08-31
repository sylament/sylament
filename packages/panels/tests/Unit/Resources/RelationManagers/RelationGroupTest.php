<?php

declare(strict_types=1);

use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Support\Enums\IconPosition;

final class VisibleGroupedRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';
}

final class HiddenGroupedRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return false;
    }
}

it('holds the label and managers make() was given', function (): void {
    $group = RelationGroup::make('Content', [VisibleGroupedRelationManager::class]);

    expect($group->getLabel())->toBe('Content')
        ->and($group->getManagers())->toBe([VisibleGroupedRelationManager::class]);
});

it('evaluates a closure label and a closure manager list', function (): void {
    $group = RelationGroup::make(
        fn (): string => 'Deferred',
        fn (): array => [VisibleGroupedRelationManager::class],
    );

    expect($group->getLabel())->toBe('Deferred')
        ->and($group->getManagers())->toBe([VisibleGroupedRelationManager::class]);
});

it('leaves the list unfiltered until both the owner and the page are known', function (): void {
    $group = RelationGroup::make('Content', [HiddenGroupedRelationManager::class])
        ->ownerRecord(new Project());

    expect($group->getManagers())->toBe([HiddenGroupedRelationManager::class]);
});

it('drops a manager that declines the owner record', function (): void {
    $group = RelationGroup::make('Content', [
        VisibleGroupedRelationManager::class,
        HiddenGroupedRelationManager::class,
    ])
        ->ownerRecord(new Project())
        ->pageClass(EditProject::class);

    expect(array_values($group->getManagers()))->toBe([VisibleGroupedRelationManager::class]);
});

it('reads a configuration entry through the class it names', function (): void {
    $group = RelationGroup::make('Content', [HiddenGroupedRelationManager::make()])
        ->ownerRecord(new Project())
        ->pageClass(EditProject::class);

    expect($group->getManagers())->toBe([]);
});

it('injects the owner and the page class into an evaluated closure', function (): void {
    $project = new Project()->setName('Apollo');

    $group = RelationGroup::make('Content')
        ->ownerRecord($project)
        ->pageClass(EditProject::class)
        ->badge(fn (Project $ownerRecord, string $pageClass): string => $ownerRecord->getName() . '/' . $pageClass);

    expect($group->getBadge())->toBe('Apollo/' . EditProject::class);
});

it('hands the badge to the colour and tooltip closures that read it', function (): void {
    $group = RelationGroup::make('Content')
        ->badge('3')
        ->badgeColor(fn (?string $badge): string => $badge === '3' ? 'danger' : 'gray')
        ->badgeTooltip(fn (?string $badge): string => sprintf('%s outstanding', (string) $badge))
        ->icon('heroicons:check-circle')
        ->iconPosition(IconPosition::After);

    $tab = $group->getTabComponent();

    expect($tab->getLabel())->toBe('Content')
        ->and($tab->getBadge())->toBe('3')
        ->and($tab->getBadgeColor())->toBe('danger')
        ->and($tab->getBadgeTooltip())->toBe('3 outstanding')
        ->and($tab->getIcon())->toBe('heroicons:check-circle')
        ->and($tab->getIconPosition())->toBe(IconPosition::After);
});

it('lets the tab callback replace the component it built', function (): void {
    $group = RelationGroup::make('Content')
        ->tab(fn (Tab $tab): Tab => $tab->label('Replaced'));

    expect($group->getTabComponent()->getLabel())->toBe('Replaced');
});
