<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AttachAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The attach host, picking several at once, for a many-to-many onto a target
 * whose key has a decimal half.
 */
#[AsLiveComponent(name: 'ArtistFaresAttachTable', template: '@TablesFixtures/components/ArtistFaresAttachTable.html.twig')]
class ArtistFaresAttachTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
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
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, 'fares') : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->headerActions([AttachAction::make()->multiple()])
            ->paginated([10]);
    }
}
