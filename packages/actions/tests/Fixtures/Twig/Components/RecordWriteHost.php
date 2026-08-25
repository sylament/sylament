<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
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
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return $this->recordClass;
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
}
