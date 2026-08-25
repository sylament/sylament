<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\ViewAction;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The write-path host: a schema host with no schemas of its own, whose header
 * actions are the prebuilt record-writing ones over the `Note` fixture entity.
 * The record class reaches them through the host hook alone — no table, no
 * page — which is the narrowest shape that lets one persist.
 */
#[AsLiveComponent(name: 'RecordWriteHost', template: '@ActionsFixtures/components/RecordWriteHost.html.twig')]
class RecordWriteHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * The class every action here writes, settable per case so the
     * nothing-names-a-class arm is reachable.
     *
     * @var class-string|null
     */
    #[LiveProp]
    public ?string $recordClass = Note::class;

    /**
     * Where the bare replicate arm's `after()` hook records the row it landed,
     * so a case can prove the hook saw the saved replica rather than the
     * original.
     */
    public ?int $replicatedId = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->resolveRecordUsing(static fn (string $key): ?Note => $entityManager->find(Note::class, (int) $key));
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return $this->recordClass;
    }

    /**
     * The record-title hook the edit and view headings interpolate.
     */
    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        $record = $action->getRecord();

        return $record instanceof Note ? $record->getTitle() : null;
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createAction(): Action
    {
        return CreateAction::make()
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ]);
    }

    /**
     * The mutate-then-redirect arm: `mutateDataUsing()` rewrites the validated
     * state before the record mapping sees it, and the run ends in a redirect
     * rather than a re-render.
     */
    public function createWithHookAction(): Action
    {
        return CreateAction::make('createWithHook')
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ])
            ->mutateDataUsing(fn (array $data): array => [...$data, 'title' => mb_strtoupper((string) $data['title'])])
            ->successRedirectUrl('/done');
    }

    /**
     * Create-another with one field carried across the reset.
     */
    public function createPreservingAction(): Action
    {
        return CreateAction::make('createPreserving')
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ])
            ->preserveFormDataWhenCreatingAnother(['body']);
    }

    public function editAction(): Action
    {
        return EditAction::make()
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ]);
    }

    /**
     * The fill-side rewrite: `mutateRecordDataUsing()` sits between the record
     * and the form, so the modal opens on something the record does not say.
     */
    public function editMutatedAction(): Action
    {
        return EditAction::make('editMutated')
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ])
            ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'title' => 'draft: ' . $data['title']]);
    }

    /**
     * The asymmetry `CanMutateRecordData` draws: an array record is state the
     * fill accepts and the default write body refuses.
     */
    public function editArrayAction(): Action
    {
        return EditAction::make('editArray')
            ->record(['__key' => '1', 'title' => 'Loose', 'body' => null])
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ]);
    }

    /**
     * The modal arm: a one-field schema, so the fill and the write both go
     * through the mounted container while the untouched columns ride the
     * metadata copy, and `mutateRecordDataUsing()` marks the copy in the form.
     */
    public function replicateAction(): Action
    {
        return ReplicateAction::make()
            ->schema([
                ActionSchemaField::make('title'),
            ])
            ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'title' => $data['title'] . ' (copy)']);
    }

    /**
     * The no-schema arm: nothing opens a form, so the same mutator runs over
     * the replicator's own field bag instead of over form state.
     */
    public function replicateBareAction(): Action
    {
        return ReplicateAction::make('replicateBare')
            ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'title' => $data['title'] . ' (copy)'])
            ->after(function (object $replica): void {
                $this->replicatedId = $replica instanceof Note ? $replica->getId() : null;
            });
    }

    public function viewAction(): Action
    {
        return ViewAction::make()
            ->schema([
                ActionSchemaField::make('title'),
                ActionSchemaField::make('body'),
            ]);
    }
}
