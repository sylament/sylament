<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars, sealed ones only by a default filter, searched and sorted by label,
 * groupable by position, with their supplier's name shown, hidden by the developer or
 * toggled off (`maker`); with `filtersByMaker` it filters by an `Acme` supplier through the
 * join a later, default filter's base query adds. Its `inspect` row action records the jar it runs on.
 */
#[AsLiveComponent(name: 'Tables:FilteredLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class FilteredLarderJarTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public string $maker = 'shown';

    #[LiveProp]
    public bool $filtersByMaker = false;

    #[LiveProp]
    public bool $groupsOnly = false;

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
        $filters = [
            Filter::make('sealed')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.seal IS NOT NULL'))->default(),
        ];

        if ($this->filtersByMaker) {
            $filters[] = Filter::make('fromAcme')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('maker.name = :maker')->setParameter('maker', 'Acme'));
            $filters[] = Filter::make('joinsMaker')->baseQuery(static fn (QueryBuilder $query): QueryBuilder => $query->leftJoin('jar.supplier', 'maker'))->default();
        }

        $maker = TextColumn::make('supplier.name');

        $maker = match ($this->maker) {
            'shown' => $maker,
            'hidden' => $maker->hidden(),
            'toggledOff' => $maker->toggleable(isToggledHiddenByDefault: true),
            default => throw new LogicException("No supplier column state is named [{$this->maker}]."),
        };

        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('position')->sortable(),
                $maker,
            ])
            ->filters($filters)
            ->groups(['position'])
            ->groupsOnly($this->groupsOnly)
            ->defaultSort('label')
            ->recordActions([
                Action::make('inspect')->action(function (LarderJar $record): void {
                    $this->hookLog->record("inspect {$record->label}");
                }),
            ]);
    }
}
