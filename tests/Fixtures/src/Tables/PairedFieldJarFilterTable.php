<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function Sylament\Support\filled;

/**
 * A larder's jars, filtered by a shelf filter whose two fields each narrow the rows, one to a
 * label and one to a lowest position, and by a filter class that names itself.
 */
#[AsLiveComponent(name: 'Tables:PairedFieldJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class PairedFieldJarFilterTable implements InteractsWithSchemas, InteractsWithTable
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
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                Filter::make('shelf')
                    ->schema([
                        TextInput::make('label'),
                        TextInput::make('lowestPosition'),
                    ])
                    ->query(static function (QueryBuilder $query, array $data): QueryBuilder {
                        if (filled($data['label'] ?? null)) {
                            $query->andWhere('jar.label = :label')->setParameter('label', $data['label']);
                        }

                        $lowestPosition = $data['lowestPosition'] ?? null;

                        if (is_numeric($lowestPosition)) {
                            $query->andWhere('jar.position >= :lowestPosition')->setParameter('lowestPosition', (int) $lowestPosition);
                        }

                        return $query;
                    }),
                ShelvedJarFilter::make(),
            ]);
    }
}
