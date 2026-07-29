<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Set;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose only schema is the one its action carries: `schemas()` is empty,
 * so `mountedActionSchema0` is the single entry the schema cache can hold and
 * neither the reactive round-trip nor the nested resolution can be satisfied by
 * a method-discovered schema standing in for it.
 *
 * `title` mirrors its previous value into `slug` on `afterStateUpdated` (the
 * probe schemas' `LiveStateHost` uses), and `archive` is an action declared
 * *inside* the modal schema — the two gaps this fixture exists to close, in one
 * host. `archive` carries the same mirroring pair so the composed case has a
 * subject too: a reactive field inside a modal that is itself only reachable
 * through the frame-0 schema cache.
 */
#[AsLiveComponent(name: 'ModalSchemaHost', template: '@ActionsFixtures/components/ModalSchemaHost.html.twig')]
class ModalSchemaHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public ?string $archived = null;

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->schema([
                ActionSchemaField::make('title')
                    ->live()
                    ->afterStateUpdated(fn (mixed $old, Set $set): mixed => $set('slug', $old)),
                ActionSchemaField::make('slug'),
                Action::make('archive')
                    ->requiresConfirmation()
                    ->schema([
                        ActionSchemaField::make('reason')
                            ->live()
                            ->afterStateUpdated(fn (mixed $old, Set $set): mixed => $set('echo', $old)),
                        ActionSchemaField::make('echo'),
                    ])
                    ->action(function (ModalSchemaHost $liveComponent): void {
                        $liveComponent->archived = 'archived';
                    }),
            ])
            ->action(fn (): null => null);
    }
}
