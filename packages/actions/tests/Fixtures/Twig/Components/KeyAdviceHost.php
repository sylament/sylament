<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\ConstructorKeyedSeat;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The refusals' advice: a scalar pair whose modal maps both key fields, with
 * the key rules switched off or left on; a plain create of a class whose
 * constructor takes its key; and a modal replicate of that class, whose copy
 * Doctrine builds without the constructor.
 */
#[AsLiveComponent(name: 'KeyAdviceHost', template: '@ActionsFixtures/components/KeyAdviceHost.html.twig')]
class KeyAdviceHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->resolveRecordUsing(static fn (string $key): ?ConstructorKeyedSeat => $entityManager->find(ConstructorKeyedSeat::class, $key));
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createUncheckedRedirectAction(): Action
    {
        return CreateAction::make('createUncheckedRedirect')
            ->recordClass(Redirect::class)
            ->schema(static fn (Schema $schema): Schema => $schema
                ->components(self::getRedirectFields())
                ->validateRecordKey(false));
    }

    public function createCheckedRedirectAction(): Action
    {
        return CreateAction::make('createCheckedRedirect')
            ->recordClass(Redirect::class)
            ->schema(self::getRedirectFields());
    }

    public function createKeyedSeatAction(): Action
    {
        return CreateAction::make('createKeyedSeat')
            ->recordClass(ConstructorKeyedSeat::class)
            ->schema([ActionSchemaField::make('code')]);
    }

    public function replicateKeyedSeatAction(): Action
    {
        return ReplicateAction::make('replicateKeyedSeat')
            ->schema([ActionSchemaField::make('code')]);
    }

    /**
     * @return list<ActionSchemaField>
     */
    private static function getRedirectFields(): array
    {
        return [ActionSchemaField::make('host'), ActionSchemaField::make('path'), ActionSchemaField::make('target')];
    }
}
