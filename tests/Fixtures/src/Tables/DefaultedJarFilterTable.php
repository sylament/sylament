<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars whose filters start set: sealed ticked, the wax seal chosen and the
 * sealed-on date answered "No"; `chosenSeal: false` leaves the seal unchosen, so the
 * sealed tick alone narrows the seals.
 */
#[AsLiveComponent(name: 'Tables:DefaultedJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class DefaultedJarFilterTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public bool $chosenSeal = true;

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
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                SealedJarFilter::make()->default(),
                SelectFilter::make('seal')->options(['wax' => 'Wax', 'screw' => 'Screw'])->default($this->chosenSeal ? 'wax' : null),
                TernaryFilter::make('sealedOn')->nullable()->default(false),
            ]);
    }
}
