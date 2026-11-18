<?php

declare(strict_types=1);

use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGarden;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\HiddenRowsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TaggedPlantsRelationManager;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('is built through the container, so a closure it evaluates takes a service, and holds no managers unless given some', function (): void {
    $group = RelationGroup::make(static fn (StaticInstanceRegistry $registry): string => $registry::class);

    expect($group->getLabel())->toBe(StaticInstanceRegistry::class)
        ->and($group->getManagers())->toBe([]);
});

it('evaluates a closure list of managers, and keeps those the record may see, under their keys, once it knows the owner and the page', function (): void {
    $configured = TaggedPlantsRelationManager::make(['tag' => 'B']);
    $group = RelationGroup::make('Beds', static fn (): array => ['seedlings' => SeedlingsRelationManager::class, 'rows' => HiddenRowsRelationManager::class, 'plants' => $configured]);

    expect($group->getManagers())->toBe(['seedlings' => SeedlingsRelationManager::class, 'rows' => HiddenRowsRelationManager::class, 'plants' => $configured])
        ->and($group->ownerRecord(new Garden('Kitchen'))->pageClass(EditGarden::class)->getManagers())->toBe(['seedlings' => SeedlingsRelationManager::class, 'plants' => $configured]);
});

it('hands its closures the owner, by name and by its class, and the page class', function (): void {
    $garden = new Garden('Kitchen');

    $label = RelationGroup::make(static fn (object $ownerRecord, Garden $garden, string $pageClass): string => sprintf('%s, %s, %s', $ownerRecord::class, $garden->name, $pageClass))
        ->ownerRecord($garden)
        ->pageClass(EditGarden::class)
        ->getLabel();

    expect($label)->toBe(Garden::class . ', Kitchen, ' . EditGarden::class);
});

it('takes the tab its tab() callback returns, and keeps its own when the callback returns nothing', function (): void {
    $raised = Tab::make('Raised beds');

    $replaced = RelationGroup::make('Beds')->tab(static fn (Tab $tab): Tab => $raised)->getTabComponent();
    $kept = RelationGroup::make('Beds')->tab(static function (Tab $tab): void {
        $tab->badge('2');
    })->getTabComponent();

    expect($replaced)->toBe($raised)
        ->and($kept->getLabel())->toBe('Beds')
        ->and($kept->getBadge())->toBe('2');
});

it('shows its icon, label and badge on its tab, coloured and titled from the badge it resolved', function (): void {
    $garden = new Garden('Kitchen');
    $kale = new Seedling('Kale');
    $kale->garden = $garden;
    $this->seed($garden, $kale);

    $page = $this->visit("/relations/gardens/{$garden->id}/edit");

    expect($page)->toContainElement(Selectors::relationManagerTab('beds'), 'rectangle-stackBeds 1')
        ->toContainElement(Selectors::relationManagerTab('beds') . ' ' . SupportSelectors::BADGE . SupportSelectors::color('warning') . '[title="1 sown"]', '1');
});
