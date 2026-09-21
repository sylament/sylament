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
 * The attach host for a many-to-many onto a **scalar-pair** target, carried from
 * both ends: `$inverse` swings the table from `Artist::$shortcuts`, which owns
 * the join table, onto `Artist::$billedShortcuts`, which names a `mappedBy`.
 */
#[AsLiveComponent(name: 'ArtistShortcutsAttachTable', template: '@TablesFixtures/components/ArtistShortcutsAttachTable.html.twig')]
class ArtistShortcutsAttachTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    #[LiveProp]
    public bool $inverse = false;

    #[LiveProp]
    public bool $multiple = false;

    /**
     * Swings the option read onto the hydrating arm, whose keys the projection
     * arm's have to equal.
     */
    #[LiveProp]
    public bool $titlesByClosure = false;

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
        $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, $this->inverse ? 'billedShortcuts' : 'shortcuts') : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->headerActions([AttachAction::make()->multiple($this->multiple)])
            ->paginated([10]);

        if ($this->titlesByClosure) {
            $table->recordTitle(static fn (): string => 'hydrated');
        }

        return $table;
    }
}
