<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AttachAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DetachAction;
use Sylament\Actions\DetachBulkAction;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Circuit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The many-to-many half of the relationship-action family, rooted at a record
 * keyed by several fields: attach, detach and the bulk detach over an owner
 * that is a scalar pair, a derived identity or a scalar pair whose target is
 * composite too. The owner is a prop because the three flavours are three
 * classes, and the association is one because each flavour carries its own
 * owning and inverse arms.
 */
#[AsLiveComponent(name: 'CompositeOwnerRelationshipActionsManyToManyTable', template: '@TablesFixtures/components/CompositeOwnerRelationshipActionsManyToManyTable.html.twig')]
class CompositeOwnerRelationshipActionsManyToManyTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    /**
     * `'shortcut'` is the scalar pair, `'residency'` the derived identity and
     * `'circuit'` the scalar pair whose many-to-many target is composite.
     */
    #[LiveProp]
    public string $owner = 'shortcut';

    #[LiveProp]
    public string $association = 'troupes';

    #[LiveProp]
    public bool $fetchesRecords = true;

    #[LiveProp]
    public bool $multiple = false;

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
        $titleAttribute = $this->association === 'shortcuts' ? 'label' : 'name';

        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->findOwner();

                return $owner === null ? null : new Relationship($owner, $this->association);
            })
            ->recordTitleAttribute($titleAttribute)
            ->columns([TextColumn::make($titleAttribute)])
            ->headerActions([AttachAction::make()->multiple($this->multiple)])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([DetachBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->paginated([10]);
    }

    private function findOwner(): ?object
    {
        return match ($this->owner) {
            'residency' => $this->entityManager->getRepository(Residency::class)->findOneBy(['season' => 'summer']),
            'circuit' => $this->entityManager->getRepository(Circuit::class)->findOneBy(['region' => 'north', 'slot' => 'one']),
            default => $this->entityManager->getRepository(Shortcut::class)->findOneBy(['host' => 'plain', 'path' => 'about']),
        };
    }
}
