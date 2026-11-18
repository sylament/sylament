<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The lines of a composite-keyed class, an {@see OrderLine} unless named, sortable by note.
 */
#[AsLiveComponent(name: 'Tables:NoteSortedLineTable', template: '@Fixtures/tables/table.html.twig')]
class NoteSortedLineTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $lineClass = OrderLine::class;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('line')->from($this->lineClass, 'line'))
            ->columns([
                TextColumn::make('note')->sortable(),
            ]);
    }
}
