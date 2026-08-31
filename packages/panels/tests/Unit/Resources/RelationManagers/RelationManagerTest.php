<?php

declare(strict_types=1);

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Resources\RelationManagers\RelationManagerConfiguration;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditProject;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tables\Table;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Markup;

/*
 * Every fixture here is declared in the test file rather than under
 * `tests/Fixtures/`: none carries `#[AsLiveComponent]`, and the Kernel's PSR-4
 * glob would autoconfigure them onto the relation-manager tag, where the
 * pass' throw would kill every Integration boot.
 */

final class HumanizedRelationManager extends RelationManager
{
    protected static string $relationship = 'blogPosts';
}

final class TitledRelationManager extends RelationManager
{
    protected static string $relationship = 'blogPosts';

    protected static ?string $title = 'Checklist';

    protected static string|BackedEnum|null $icon = 'heroicons:check-circle';

    protected static IconPosition $iconPosition = IconPosition::After;

    protected static ?string $badge = '4';

    protected static ?string $badgeColor = 'warning';

    protected static string|Markup|null $badgeTooltip = 'Open items';
}

final class RelationlessRelationManager extends RelationManager
{
}

final class CountingRelationManager extends RelationManager
{
    public static int $tableCalls = 0;

    protected static string $relationship = 'tasks';

    public function table(Table $table): Table
    {
        self::$tableCalls++;

        return $table;
    }
}

it('answers the relationship the static names', function (): void {
    expect(HumanizedRelationManager::getRelationshipName())->toBe('blogPosts');
});

it('names the manager when no relationship is declared', function (): void {
    expect(RelationlessRelationManager::getRelationshipName(...))
        ->toThrow(LogicException::class, RelationlessRelationManager::class);
});

it('humanizes the relationship name without pluralizing it', function (): void {
    expect(HumanizedRelationManager::getTitle(new Project(), EditProject::class))->toBe('Blog Posts');
});

it('prefers the declared title over the relationship name', function (): void {
    expect(TitledRelationManager::getTitle(new Project(), EditProject::class))->toBe('Checklist');
});

it('carries the label, badge and icon statics onto the tab component', function (): void {
    $tab = TitledRelationManager::getTabComponent(new Project(), EditProject::class);

    expect($tab->getLabel())->toBe('Checklist')
        ->and($tab->getBadge())->toBe('4')
        ->and($tab->getBadgeColor())->toBe('warning')
        ->and($tab->getBadgeTooltip())->toBe('Open items')
        ->and($tab->getIcon())->toBe('heroicons:check-circle')
        ->and($tab->getIconPosition())->toBe(IconPosition::After);
});

it('leaves an undeclared badge and icon off the tab', function (): void {
    $tab = HumanizedRelationManager::getTabComponent(new Project(), EditProject::class);

    expect($tab->getLabel())->toBe('Blog Posts')
        ->and($tab->getBadge())->toBeNull()
        ->and($tab->getIcon())->toBeNull()
        ->and($tab->getIconPosition())->toBe(IconPosition::Before);
});

it('answers a configuration holding the class and its properties', function (): void {
    $configuration = HumanizedRelationManager::make(['tablePage' => 2]);

    expect($configuration)->toBeInstanceOf(RelationManagerConfiguration::class)
        ->and($configuration->relationManager)->toBe(HumanizedRelationManager::class)
        ->and($configuration->getProperties())->toBe(['tablePage' => 2]);
});

it('carries no default properties and views for every record', function (): void {
    expect(HumanizedRelationManager::getDefaultProperties())->toBe([])
        ->and(HumanizedRelationManager::canViewForRecord(new Project(), EditProject::class))->toBeTrue();
});

it('derives the live component name from the class-string', function (): void {
    expect(HumanizedRelationManager::getComponentName())->toBe('HumanizedRelationManager');
});

it('applies the table hook exactly once and memoises the result', function (): void {
    CountingRelationManager::$tableCalls = 0;

    $relationManager = new CountingRelationManager();
    $table = $relationManager->getTable();

    expect($relationManager->getTable())->toBe($table)
        ->and(CountingRelationManager::$tableCalls)->toBe(1);
});

it('refuses to resolve an owner it carries no key for', function (): void {
    $relationManager = new CountingRelationManager();
    $relationManager->pageClass = EditProject::class;

    expect($relationManager->getOwnerRecord(...))->toThrow(NotFoundHttpException::class);
});

it('refuses to answer a page class it was never mounted with', function (): void {
    expect(new CountingRelationManager()->getPageClass(...))->toThrow(LogicException::class);
});

it('renders through the relation-manager partial', function (): void {
    expect(new CountingRelationManager()->getTemplate())->toBe('@SylamentPanels/partials/RelationManager/index.html.twig');
});
