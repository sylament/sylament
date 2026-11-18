<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\BaseFilter;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars under filters of every shape a filters form lays out, none filtering until
 * set: checkbox and toggle filters, one reading its own state and draft, a bare one, a
 * two-field one that resets to an open state, select filters with grouped, placeholder-less,
 * static and blank options, and four hidden filters that would keep no jar, in a form three
 * columns wide whose filters span, start and lay out columns of their own.
 */
#[AsLiveComponent(name: 'Tables:ShapedJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class ShapedJarFilterTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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
        $seals = ['wax' => 'Wax', 'screw' => 'Screw'];
        $keepsNoJar = static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.id < 0');

        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filtersFormColumns(3)
            ->filters([
                SealedJarFilter::make()->columnSpan(2),
                Filter::make('waxed')->toggle()->columnStart(2)->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.seal = 'wax'")),
                Filter::make('screwed')->toggle()->checkbox()->columnSpan(static fn (Group $component): int => 3),
                Filter::make('kept')->resetState(['isActive' => true])->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.label <> ''")),
                Filter::make('owned')->modifyFormFieldUsing(static fn (Checkbox $field): Checkbox => $field->label('Mine only')),
                BaseFilter::make('bare'),
                Filter::make('stocked')
                    ->schema([
                        Select::make('state')->options(['open' => 'Open', 'shut' => 'Shut']),
                        TextInput::make('note'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->resetState(['state' => 'open'])
                    ->modifyFormFieldUsing(static fn (Checkbox $field): Checkbox => $field->label('Never shown'))
                    ->indicateUsing(static fn (array $state): array => array_filter([
                        'state' => is_string($state['state'] ?? null) ? "State: {$state['state']}" : null,
                        'note' => is_string($state['note'] ?? null) && $state['note'] !== '' ? "Note: {$state['note']}" : null,
                    ])),
                Filter::make('drafted')->indicateUsing(static fn (Filter $filter): array => ($filter->getFormState()['isActive'] ?? false)
                    ? [sprintf('Applied %s, drafted on', ($filter->getState()['isActive'] ?? false) ? 'on' : 'off')]
                    : []),
                SelectFilter::make('kind')->attribute('seal')->placeholder('Any seal')->indicator('Seal kind')->options(['Sealed' => $seals]),
                SelectFilter::make('strict')->attribute('seal')->selectablePlaceholder(false)->options($seals),
                SelectFilter::make('pinnedSeal')->attribute('seal')->static()->options($seals),
                SelectFilter::make('marked')->attribute('seal')->indicator(Indicator::make('Marked chip'))->options($seals),
                SelectFilter::make('blankable')->attribute('seal')->options(['' => 'None', 'wax' => 'Wax']),
                Filter::make('hiddenOutright')->hidden()->query($keepsNoJar),
                Filter::make('invisible')->visible(false)->query($keepsNoJar),
                Filter::make('hiddenHere')->hiddenOn(self::class)->query($keepsNoJar),
                Filter::make('shownElsewhere')->visibleOn(FilterableLarderJarTable::class)->query($keepsNoJar),
            ]);
    }
}
