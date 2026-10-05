<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AssociateAction;
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
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\HexOwner;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One owner's relationship onto a record keyed through a scalar type whose
 * reader is not idempotent, with the attach or the associate action over it:
 * `$relationship` names the association, `$action` which of the two the header
 * carries.
 */
#[AsLiveComponent(name: 'HexOwnerRelationTable', template: '@TablesFixtures/components/HexOwnerRelationTable.html.twig')]
class HexOwnerRelationTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $ownerId = 1;

    #[LiveProp]
    public string $relationship = 'swatches';

    #[LiveProp]
    public string $action = 'attach';

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
                $owner = $this->entityManager->find(HexOwner::class, $this->ownerId);

                return $owner instanceof HexOwner ? new Relationship($owner, $this->relationship) : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->headerActions([$this->action === 'associate' ? AssociateAction::make() : AttachAction::make()])
            ->paginated([10]);
    }
}
