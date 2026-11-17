<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose actions open modal forms, recording in the request's {@see HookLog} what
 * ran: `edit` validates a required title, shows one field per operation, holds two actions
 * of its own (one asking for `$set`, one for its arguments and data) and opens `attachFile`
 * above it; `quickRename` runs its required form without a modal; `swap` and `bare` replace
 * each other, one with a form and one without. Its own
 * form holds a required live `outside` field, an `annotate` action with a form of its own
 * and a modal-less `probe` action; `sidebar()` builds an undeclared schema holding `peek`,
 * `save()` is a host method no context may reach, and `broken()` a schema builder that
 * throws.
 */
#[AsLiveComponent(name: 'Actions:SchemaModalHost', template: '@Fixtures/actions/SchemaModalHost.html.twig')]
class SchemaModalHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('outside')->required()->live(),
                Action::make('annotate')
                    ->schema([
                        TextInput::make('remark')->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->hookLog->record('annotate: ' . json_encode($data, JSON_THROW_ON_ERROR));
                    }),
                Action::make('probe')->action(function (?Schema $schema): void {
                    $this->hookLog->record($schema instanceof Schema ? "probe: {$schema->getKey()}" : 'probe: no schema');
                }),
            ]);
    }

    public function sidebar(Schema $schema): Schema
    {
        return $schema->components([
            Action::make('peek')->action(function (): void {
                $this->hookLog->record('peek');
            }),
        ]);
    }

    public function broken(Schema $schema): Schema
    {
        throw new RuntimeException('The broken schema failed to build.');
    }

    public function save(): void
    {
        $this->hookLog->record('save()');
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('shownOnEdit')->visibleOn('edit'),
                TextInput::make('shownOnAttach')->visibleOn('edit.attachFile'),
                Action::make('fill')->action(function (Set $set): void {
                    $set('title', 'Filled');
                }),
                Action::make('count')->action(function (array $arguments, array $data): void {
                    $this->hookLog->record('count: ' . json_encode([$arguments, $data], JSON_THROW_ON_ERROR));
                }),
            ])
            ->beforeFormValidated(function (): void {
                $this->hookLog->record('beforeFormValidated');
            })
            ->afterFormValidated(function (): void {
                $this->hookLog->record('afterFormValidated');
            })
            ->before(function (): void {
                $this->hookLog->record('before');
            })
            ->action(function (Schema $schema, Schema $form, array $data): void {
                $this->hookLog->record(sprintf('action: %s%s %s', $schema->getKey(), ($form === $schema) ? ' as form' : '', json_encode($data, JSON_THROW_ON_ERROR)));
            })
            ->after(function (): void {
                $this->hookLog->record('after');
            })
            ->registerModalActions([
                Action::make('attachFile')
                    ->schema([
                        TextInput::make('file')->required(),
                        TextInput::make('shownOnEdit')->visibleOn('edit'),
                        TextInput::make('shownOnAttach')->visibleOn('edit.attachFile'),
                    ])
                    ->action(function (): void {
                        $this->hookLog->record('attachFile');
                    }),
            ]);
    }

    public function quickRenameAction(): Action
    {
        return Action::make('quickRename')
            ->modal(false)
            ->schema([
                TextInput::make('name')->required(),
            ])
            ->action(function (): void {
                $this->hookLog->record('quickRename');
            });
    }

    public function swapAction(): Action
    {
        return Action::make('swap')
            ->schema([
                TextInput::make('code'),
            ])
            ->action(function (): void {
                $this->replaceMountedAction('bare');
            });
    }

    public function bareAction(): Action
    {
        return Action::make('bare')
            ->modalHeading('Bare')
            ->action(function (): void {
                $this->replaceMountedAction('swap');
            });
    }
}
