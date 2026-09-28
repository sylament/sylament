<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Spice;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The plain create over an assigned key: no relationship, no table, the record
 * class named on each action — so the key the modal types is the whole key, and
 * a modal typing none of it leaves the whole key to the class.
 */
#[AsLiveComponent(name: 'LarderCreateHost', template: '@ActionsFixtures/components/LarderCreateHost.html.twig')]
class LarderCreateHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * What the create schema was bound to while each mapping ran, as the title
     * field's save hook saw it.
     *
     * @var list<string>
     */
    public array $bindingsWhileMapping = [];

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createNoteAction(): Action
    {
        return CreateAction::make('createNote')
            ->recordClass(AssignedNote::class)
            ->schema([
                ActionSchemaField::make('id'),
                ActionSchemaField::make('title')
                    ->saveRelationshipsUsing(function (ActionSchemaField $component, AssignedNote $record, ?string $state): void {
                        $this->bindingsWhileMapping[] = get_debug_type($component->getRecord());
                        $record->setTitle((string) $state);
                    }),
            ]);
    }

    public function createSpiceAction(): Action
    {
        return CreateAction::make('createSpice')
            ->recordClass(Spice::class)
            ->schema([
                ActionSchemaField::make('name'),
                ActionSchemaField::make('label'),
            ]);
    }

    /**
     * A scalar pair whose modal maps neither key field.
     */
    public function createRedirectAction(): Action
    {
        return CreateAction::make('createRedirect')
            ->recordClass(Redirect::class)
            ->schema([
                ActionSchemaField::make('target'),
            ]);
    }
}
