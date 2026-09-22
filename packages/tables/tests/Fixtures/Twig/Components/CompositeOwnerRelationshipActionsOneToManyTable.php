<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AssociateAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateAction;
use Sylament\Actions\DissociateBulkAction;
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
 * The one-to-many half of the same family: associate, dissociate and both arms
 * of the bulk dissociate over a collection whose mapped-by to-one spans the
 * owner's whole identifier — which is every one-to-many a record keyed by
 * several fields owns, and is why the by-key arm is refused there.
 */
#[AsLiveComponent(name: 'CompositeOwnerRelationshipActionsOneToManyTable', template: '@TablesFixtures/components/CompositeOwnerRelationshipActionsOneToManyTable.html.twig')]
class CompositeOwnerRelationshipActionsOneToManyTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    /**
     * `'shortcut'` is the scalar pair, `'residency'` the derived identity and
     * `'circuit'` the self-referencing pair, whose `stops` arm is the NOT NULL
     * join the detach assert refuses before the by-key refusal is reached.
     */
    #[LiveProp]
    public string $owner = 'shortcut';

    #[LiveProp]
    public string $association = 'notes';

    #[LiveProp]
    public bool $fetchesRecords = true;

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
        $titleAttribute = match ($this->association) {
            'notes' => 'heading',
            'stops' => 'label',
            default => 'name',
        };

        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->findOwner();

                return $owner === null ? null : new Relationship($owner, $this->association);
            })
            ->recordTitleAttribute($titleAttribute)
            ->columns([TextColumn::make($titleAttribute)])
            ->headerActions([AssociateAction::make()])
            ->recordActions([DissociateAction::make()])
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
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
