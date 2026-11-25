<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A larder table a user searches, ordered by name with an `archive` bulk action: `name` is
 * globally searchable, and with `nameOwnInput` on an input of its own besides, and
 * `supplier.name` searchable on its own input alone. Each variant configures one search
 * setting: `nameUnsearchable`, `globalSupplier`, `supplierHidden`, `supplierToggleable`,
 * `searchable` (`searchable()`), `extras` (the supplier's name, a closure matching the term as
 * a larder's id and one keeping out the `Pantry`, beside two blank members), `notSearchable`
 * (`searchable(false)`, after the extras), `searchUsing` (a closure matching the term as the
 * supplier's whole name), `wholeTerm` (`splitSearchTerms(false)`), `onBlur`, `paged` (two rows
 * a page), `keepsSelection` and `markupSupplierLabel` (a markup label on `supplier.name`);
 * `placeholder` and `debounce` are handed to their setters as given.
 */
#[AsLiveComponent(name: 'Tables:SearchableLarderTable', template: '@Fixtures/tables/table.html.twig')]
class SearchableLarderTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    /**
     * Writable, so a column can become searchable on its own input between two requests.
     */
    #[LiveProp(writable: true)]
    public bool $nameOwnInput = false;

    #[LiveProp]
    public ?string $placeholder = null;

    #[LiveProp]
    public ?string $debounce = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $table
            ->query($this->entityManager->createQueryBuilder()->select('larder')->from(Larder::class, 'larder'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(!$this->has('nameUnsearchable'), isIndividual: $this->nameOwnInput),
                TextColumn::make('supplier.name')
                    ->searchable(isIndividual: true, isGlobal: $this->has('globalSupplier'))
                    ->hidden($this->has('supplierHidden'))
                    ->toggleable($this->has('supplierToggleable'))
                    ->label($this->has('markupSupplierLabel') ? new Markup('<b>Supplier</b> &amp; maker', 'UTF-8') : null),
            ])
            ->defaultSort('name')
            ->toolbarActions([
                BulkAction::make('archive')->action(static function (): void {
                }),
            ])
            ->searchPlaceholder($this->placeholder)
            ->searchDebounce($this->debounce)
            ->searchOnBlur($this->has('onBlur'))
            ->splitSearchTerms(!$this->has('wholeTerm'))
            ->deselectAllRecordsWhenFiltered(!$this->has('keepsSelection'));

        if ($this->has('paged')) {
            $table->paginated([2]);
        }

        if ($this->has('searchable')) {
            $table->searchable();
        }

        if ($this->has('extras')) {
            $table->searchable([
                'supplier.name',
                static fn (QueryBuilder $query, string $searchQuery): Comparison => $query->expr()->eq('larder.id', (int) $searchQuery),
                static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("larder.name <> 'Pantry'"),
                '',
                ' ',
            ]);
        }

        if ($this->has('notSearchable')) {
            $table->searchable(false);
        }

        if ($this->has('searchUsing')) {
            $table->searchUsing(static fn (QueryBuilder $query, string $search): QueryBuilder => $query
                ->leftJoin('larder.supplier', 'searchedSupplier')
                ->andWhere('searchedSupplier.name = :supplierName')
                ->setParameter('supplierName', $search));
        }

        return $table;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
