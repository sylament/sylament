<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\Tests\Fixtures\Components\RequiredField;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
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
     * The validating twin of `edit`: its single field is unconditionally
     * constrained, so a submit against an untouched frame fails.
     */
    public function publishAction(): Action
    {
        return Action::make('publish')
            ->schema([
                RequiredField::make('title'),
            ])
            ->beforeFormValidated(function (): void {
                $this->hookLog[] = 'beforeFormValidated';
            })
            ->afterFormValidated(function (): void {
                $this->hookLog[] = 'afterFormValidated';
            })
            ->action(function (array $data): void {
                $this->savedData = $data;
            });
    }

    /**
     * The same constrained schema with no modal to keep open, so a failing
     * submit takes the unmount-and-rethrow arm instead.
     */
    public function publishInlineAction(): Action
    {
        return Action::make('publishInline')
            ->schema([
                RequiredField::make('title'),
            ])
            ->modalHidden()
            ->action(function (array $data): void {
                $this->savedData = $data;
            });
    }

    /**
     * Validation raised out of a mount-time hook rather than out of a submit.
     */
    public function guardedAction(): Action
    {
        return Action::make('guarded')
            ->schema([
                RequiredField::make('title'),
            ])
            ->beforeFormFilled(function (): never {
                throw new ValidationException(['guard' => ['Blocked at mount.']]);
            });
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
