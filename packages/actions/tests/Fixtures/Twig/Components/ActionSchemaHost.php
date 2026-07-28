<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A schema-hosting action host whose schemas are *only* the per-mount ones:
 * `schemas()` is empty, so `getCachedSchemas()` never discovers a host method
 * and the only entries in `$cachedSchemas` are the `mountedActionSchema{i}`
 * ones registered by `getMountedActionSchema()`.
 */
#[AsLiveComponent(name: 'ActionSchemaHost', template: '@ActionsFixtures/components/ActionSchemaHost.html.twig')]
class ActionSchemaHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * The data the called action saw, as a LiveProp so it survives the
     * round-trip and can be read back in the harness.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedData = [];

    /**
     * @var array<int, string>
     */
    #[LiveProp]
    public array $hookLog = [];

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    /**
     * No modal heading, description or content: the only thing that can make
     * this open a modal is its schema.
     */
    public function editAction(): Action
    {
        return Action::make('edit')
            ->schema([
                ActionSchemaField::make('title'),
            ])
            ->beforeFormFilled(function (): void {
                $this->hookLog[] = 'beforeFormFilled';
            })
            ->afterFormFilled(function (): void {
                $this->hookLog[] = 'afterFormFilled';
            })
            ->action(function (array $data): void {
                $this->savedData = $data;
            })
            ->extraModalFooterActions([
                Action::make('nested')->schema([
                    ActionSchemaField::make('reason'),
                ]),
            ]);
    }

    /**
     * Schema-less but modal-bearing: it stays mounted, so the seeded frame is
     * observable and the form-filled hooks must stay silent.
     */
    public function noteAction(): Action
    {
        return Action::make('note')
            ->modalHeading('Note')
            ->beforeFormFilled(function (): void {
                $this->hookLog[] = 'beforeFormFilled';
            })
            ->afterFormFilled(function (): void {
                $this->hookLog[] = 'afterFormFilled';
            })
            ->action(fn (): true => true);
    }
}
