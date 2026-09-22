<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\RedirectHop;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The create action's auto-attach with an owner keyed by two fields: the record
 * it writes is an ordinary single-`id` child, so the only thing wider than
 * usual is the foreign key the attach fills in.
 */
#[AsLiveComponent(name: 'CompositeOwnerCreateHost', template: '@ActionsFixtures/components/CompositeOwnerCreateHost.html.twig')]
class CompositeOwnerCreateHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public string $redirectHost = 'example.com';

    #[LiveProp]
    public string $redirectPath = '/old';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return RedirectHop::class;
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        $redirect = $this->entityManager->find(Redirect::class, ['host' => $this->redirectHost, 'path' => $this->redirectPath]);

        return $redirect instanceof Redirect ? new Relationship($redirect, 'hops') : null;
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createHopAction(): Action
    {
        return CreateAction::make('createHop')
            ->schema([
                ActionSchemaField::make('id'),
                ActionSchemaField::make('label'),
            ]);
    }
}
