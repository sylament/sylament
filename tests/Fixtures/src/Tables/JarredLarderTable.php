<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Larders by name with the count of their jars, the larder's query fetch-joining the jars
 * when `fetchesJars` is set; with `groupsOnly` they are groupable by name, and only the
 * groups show once a user groups them.
 */
#[AsLiveComponent(name: 'Tables:JarredLarderTable', template: '@Fixtures/tables/table.html.twig')]
class JarredLarderTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public bool $fetchesJars = false;

    #[LiveProp]
    public bool $groupsOnly = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $query = $this->entityManager->createQueryBuilder()->select('larder')->from(Larder::class, 'larder');

        if ($this->fetchesJars) {
            $query->addSelect('jar')->leftJoin('larder.jars', 'jar');
        }

        $table
            ->query($query)
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('jars_count')->counts('jars'),
            ])
            ->defaultSort('name');

        if ($this->groupsOnly) {
            $table->groups(['name'])->groupsOnly();
        }

        return $table;
    }
}
