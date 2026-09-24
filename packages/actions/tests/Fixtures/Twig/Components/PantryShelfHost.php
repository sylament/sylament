<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Kettle;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Pantry;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Spice;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The relation-scoped create into one pantry: a kettle joins its one-to-many,
 * where the relationship fills half the kettle's key, and a spice joins the
 * many-to-many the pantry owns, where the attach writes the owner's own
 * collection.
 */
#[AsLiveComponent(name: 'PantryShelfHost', template: '@ActionsFixtures/components/PantryShelfHost.html.twig')]
class PantryShelfHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public int $pantryId = 0;

    /**
     * What the create schema was bound to while each mapping ran, as the label
     * field's save hook saw it.
     *
     * @var list<string>
     */
    public array $bindingsWhileMapping = [];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        $pantry = $this->entityManager->find(Pantry::class, $this->pantryId);

        if (! $pantry instanceof Pantry) {
            return null;
        }

        return new Relationship($pantry, $action->getName() === 'stockSpice' ? 'spices' : 'kettles');
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    /**
     * A hint only a create shows: its visibility reads the record the schema is
     * bound to, which a create leaves unbound.
     */
    public function shelveKettleAction(): Action
    {
        return CreateAction::make('shelveKettle')
            ->recordClass(Kettle::class)
            ->schema([
                ActionSchemaField::make('code'),
                ActionSchemaField::make('label')
                    ->saveRelationshipsUsing(function (ActionSchemaField $component, Kettle $record, ?string $state): void {
                        $this->bindingsWhileMapping[] = get_debug_type($component->getRecord());
                        $record->setLabel((string) $state);
                    }),
                ActionSchemaField::make('hint')
                    ->visible(static fn (?Kettle $record): bool => ! $record instanceof Kettle)
                    ->dehydrated(false),
            ]);
    }

    /**
     * A form that also maps the relationship's own association, picking a pantry
     * by id — the misconfiguration where the relationship and the form disagree.
     */
    public function shelveKettleOnPickAction(): Action
    {
        return CreateAction::make('shelveKettleOnPick')
            ->recordClass(Kettle::class)
            ->schema([
                ActionSchemaField::make('pantry'),
                ActionSchemaField::make('code'),
                ActionSchemaField::make('label'),
            ])
            ->mutateDataUsing(fn (array $data): array => [...$data, 'pantry' => $this->entityManager->find(Pantry::class, (int) $data['pantry'])]);
    }

    public function stockSpiceAction(): Action
    {
        return CreateAction::make('stockSpice')
            ->recordClass(Spice::class)
            ->schema([
                ActionSchemaField::make('name'),
                ActionSchemaField::make('label'),
            ]);
    }
}
