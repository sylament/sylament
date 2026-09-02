<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Panels\Tests\Fixtures\Resources\MrrProjectResource;
use Sylament\Panels\Tests\Fixtures\Resources\MrrTaskResource;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The manage page, registered under the `tasks` key its relationship name
 * kebabs to. Its own `table()` adds the header action on top of everything the
 * related resource stamped.
 */
#[AsLiveComponent]
final class ManageMrrProjectTasks extends ManageRelatedRecords
{
    protected static string $resource = MrrProjectResource::class;

    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = MrrTaskResource::class;

    /**
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
        return $table->headerActions([CreateAction::make()]);
    }
}
