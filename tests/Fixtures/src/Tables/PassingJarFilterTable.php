<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars with no date sealed on, filtered by their seal, by a filter whose base query
 * drops that condition to list the dated jars too, and by whether they are shelved (a position
 * above 0), a filter record actions resolve their record without; its `open` row action
 * records the label of the jar it opens in the {@see HookLog}.
 */
#[AsLiveComponent(name: 'Tables:PassingJarFilterTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class PassingJarFilterTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->where('jar.sealedOn IS NULL'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                SelectFilter::make('seal')->options(['wax' => 'Wax', 'screw' => 'Screw']),
                Filter::make('withDated')->baseQuery(static fn (QueryBuilder $query): QueryBuilder => $query->resetDQLPart('where')),
                Filter::make('shelved')
                    ->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 0'))
                    ->excludeWhenResolvingRecord(),
            ])
            ->recordActions([
                Action::make('open')->action(function (LarderJar $record): void {
                    $this->hookLog->record("opened {$record->label}");
                }),
            ]);
    }
}
