<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use stdClass;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\Tests\Fixtures\Components\RequiredField;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
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
     * The key of the schema the called action closure received, or null when
     * no closure asking for one has run.
     */
    #[LiveProp]
    public ?string $injectedSchemaKey = null;

    /**
     * A writable prop with nothing behind it, so a case can drive one unrelated
     * live update over a mounted frame.
     */
    #[LiveProp(writable: true)]
    public ?string $probe = null;

    /**
     * The last link of the mounted schema's record chain, settable per case.
     *
     * @var object|array<string, mixed>|string|null
     */
    public object|array|string|null $mountedSchemaRecord = null;

    /**
     * @return object|array<string, mixed>|string|null
     */
    protected function getMountedActionSchemaRecord(): object|array|string|null
    {
        return $this->mountedSchemaRecord;
    }

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
     * Its `action()` closure asks for the mounted modal schema by name, so it
     * only runs if `callMountedAction()` hands the schema to `call()`.
     */
    public function injectAction(): Action
    {
        return Action::make('inject')
            ->schema([
                ActionSchemaField::make('title'),
            ])
            ->action(function (Schema $schema, array $data): void {
                $this->injectedSchemaKey = $schema->getKey();
                $this->savedData = $data;
            });
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
     * The create-shaped arm: an action that clears its record once the stack
     * empties, which is the only thing `CreateAction` will change about the base
     * behaviour. It carries a modal so the record is observable while mounted.
     */
    public function clearingAction(): Action
    {
        $action = new class () extends Action {
            public function shouldClearRecordAfter(): bool
            {
                return true;
            }
        };

        return $action
            ->name('clearing')
            ->configure()
            ->modalHeading('Clearing')
            ->record(new stdClass())
            ->action(fn (): null => null);
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
