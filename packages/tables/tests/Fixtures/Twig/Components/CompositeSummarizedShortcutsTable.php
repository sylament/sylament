<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The summary half of the same depth, on the scalar-pair flavour: a summarizer
 * batch and a relation search on one keyed query. It is a second host because
 * `SummaryQuery::aggregable()` refuses a to-many join, so a summary band and a
 * fetch join cannot share a table.
 */
#[AsLiveComponent(name: 'CompositeSummarizedShortcutsTable', template: '@TablesFixtures/components/CompositeSummarizedShortcutsTable.html.twig')]
class CompositeSummarizedShortcutsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut')->orderBy('shortcut.position', 'ASC'))
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('hits.agent')->searchable(),
                TextColumn::make('position')->summarize(Sum::make()),
            ])
            ->paginated([2]);
    }
}
