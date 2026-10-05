<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Charm;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Satchel;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The relation-scoped create into one satchel's many-to-many, whose modal maps
 * the charm's label alone and leaves its key `null`.
 */
#[AsLiveComponent(name: 'SatchelHost', template: '@ActionsFixtures/components/SatchelHost.html.twig')]
class SatchelHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public int $satchelId = 0;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        $satchel = $this->entityManager->find(Satchel::class, $this->satchelId);

        return $satchel instanceof Satchel ? new Relationship($satchel, 'charms') : null;
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function stowCharmAction(): Action
    {
        return CreateAction::make('stowCharm')
            ->recordClass(Charm::class)
            ->schema([
                ActionSchemaField::make('label'),
            ]);
    }
}
