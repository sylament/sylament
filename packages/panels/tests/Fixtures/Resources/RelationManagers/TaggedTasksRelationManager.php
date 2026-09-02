<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The configured manager: it declares a default property, so a registration
 * that names the same key proves the configuration's properties are spread
 * last.
 */
#[AsLiveComponent]
final class TaggedTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    #[LiveProp]
    public ?string $tag = null;

    /**
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        return ['tag' => 'default'];
    }

    /**
     * Tabs on a manager render and switch, but bind to nothing: the manager
     * shares its page's URL and maps no state onto it.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'first' => Tab::make()
                ->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('record.position = 1')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
