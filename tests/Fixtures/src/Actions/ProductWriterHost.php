<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose actions write a `Product` named after the action, with or without a
 * transaction around their call: `write` writes, `writeAndFail` writes then throws,
 * `transact` opens a transaction and writes nothing, `transactAndFail` writes in one then
 * throws, and `idle` runs nothing.
 */
#[AsLiveComponent(name: 'Actions:ProductWriterHost', template: '@Fixtures/actions/ProductWriterHost.html.twig')]
class ProductWriterHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function writeAction(): Action
    {
        return Action::make('write')->action(function (Action $action): void {
            $this->writeProduct($action);
        });
    }

    public function writeAndFailAction(): Action
    {
        return Action::make('writeAndFail')->action(function (Action $action): never {
            $this->writeProduct($action);

            throw new RuntimeException('The write failed.');
        });
    }

    public function transactAction(): Action
    {
        return Action::make('transact')->databaseTransaction();
    }

    public function transactAndFailAction(): Action
    {
        return Action::make('transactAndFail')
            ->databaseTransaction()
            ->action(function (Action $action): never {
                $this->writeProduct($action);

                throw new RuntimeException('The write failed.');
            });
    }

    public function idleAction(): Action
    {
        return Action::make('idle');
    }

    private function writeProduct(Action $action): void
    {
        $this->entityManager->persist(new Product($action->getName()));
        $this->entityManager->flush();
    }
}
