<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A plain host that resolves a frame's record key to an {@see Atelier} through its one
 * `resolveRecordUsing()` callback: `inspect` builds its heading from the record, fills a
 * form from it and records the key it ran on in the request's {@see HookLog}, `stamp`
 * above it does the same with the record it inherits, and `copy` beside it is a
 * create-shaped child; `draft`, create-shaped too, replaces itself with `inspect`.
 */
#[AsLiveComponent(name: 'Actions:AtelierResolvingHost', template: '@Fixtures/actions/AtelierResolvingHost.html.twig')]
class AtelierResolvingHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(
        private readonly HookLog $hookLog,
        EntityManagerInterface $entityManager,
    ) {
        $this->resolveRecordUsing(static fn (mixed $key): ?Atelier => is_scalar($key) ? $entityManager->find(Atelier::class, $key) : null);
    }

    public function schemas(): array
    {
        return [];
    }

    public function inspectAction(): Action
    {
        return Action::make('inspect')
            ->modalHeading(static fn (Atelier $record): string => "Inspect atelier {$record->id}")
            ->schema([
                TextInput::make('name'),
            ])
            ->fillForm(static fn (Atelier $record): array => ['name' => $record->name])
            ->action(function (Atelier $record): void {
                $this->hookLog->record("inspect: {$record->id}");
            })
            ->registerModalActions([
                Action::make('stamp')
                    ->modalHeading(static fn (?Atelier $record): string => 'Stamp atelier ' . ($record instanceof Atelier ? $record->id : 'none'))
                    ->action(function (?Atelier $record): void {
                        $this->hookLog->record('stamp: ' . ($record instanceof Atelier ? $record->id : 'none'));
                    }),
                CreateAction::make('copy')->modalHeading('Copy'),
            ]);
    }

    public function draftAction(): CreateAction
    {
        return CreateAction::make('draft')
            ->modalHeading('Draft')
            ->successNotificationTitle(null)
            ->action(function (Atelier $record): void {
                $this->replaceMountedAction('inspect', context: ['recordKey' => (string) $record->id]);
            });
    }
}
