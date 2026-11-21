<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

use function Symfony\Component\Translation\t;

/**
 * The {@see HeadedLarderJarTable} with every text it takes a message from the `fixtures-tables` catalogue:
 * its heading, description, search placeholder and record labels, an empty state worded by messages unless
 * `plainEmptyState` leaves the default arm to the translated plural label, a column labelled, titled, described
 * and affixed by messages, a filter labelled and chipping by messages, and a ternary filter worded by them.
 */
#[AsLiveComponent(name: 'Tables:TranslatedLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class TranslatedLarderJarTable extends HeadedLarderJarTable implements InteractsWithSchemas
{
    use HasSchemas;

    #[LiveProp]
    public bool $plainEmptyState = false;

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table)
            ->heading(t('table.heading', [], 'fixtures-tables'))
            ->description(t('table.description', [], 'fixtures-tables'))
            ->searchPlaceholder(t('table.search', [], 'fixtures-tables'))
            ->recordLabel(t('table.record', [], 'fixtures-tables'))
            ->pluralRecordLabel(t('table.records', [], 'fixtures-tables'))
            ->columns([
                TextColumn::make('label')
                    ->label(t('column.label', [], 'fixtures-tables'))
                    ->searchable()
                    ->tooltip(t('column.tooltip', [], 'fixtures-tables'))
                    ->headerTooltip(t('column.header_tooltip', [], 'fixtures-tables'))
                    ->description(t('column.description', [], 'fixtures-tables'))
                    ->prefix(t('column.prefix', [], 'fixtures-tables'))
                    ->suffix(t('column.suffix', [], 'fixtures-tables')),
            ])
            ->filters([
                Filter::make('sealed')
                    ->label(t('filter.label', [], 'fixtures-tables'))
                    ->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.seal IS NOT NULL'))
                    ->indicateUsing(static fn (): Indicator => Indicator::make(t('filter.chip', [], 'fixtures-tables'))),
                TernaryFilter::make('wrapped')
                    ->attribute('seal')
                    ->trueLabel(t('filter.true', [], 'fixtures-tables'))
                    ->falseLabel(t('filter.false', [], 'fixtures-tables'))
                    ->placeholder(t('filter.placeholder', [], 'fixtures-tables'))
                    ->nullable(),
            ]);

        return $this->plainEmptyState ? $table : $table
            ->emptyStateHeading(t('table.empty_heading', [], 'fixtures-tables'))
            ->emptyStateDescription(t('table.empty_description', [], 'fixtures-tables'));
    }
}
