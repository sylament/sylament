<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The three record actions over `Note`, each with a `mutateDataUsing()` hook,
 * in the arm the `arm` prop picks: the hook adds a key and a nested key no
 * component maps (`adding`); it overwrites the mapped title, the modal mapping
 * the body too (`overwriting`) or not, the case then typing a body no component
 * maps (`injected`); it adds the body a hidden component maps (`hidden`); or it
 * adds the body and `using()` takes the write over (`using`).
 */
#[AsLiveComponent(name: 'HookedNoteHost', template: '@ActionsFixtures/components/HookedNoteHost.html.twig')]
class HookedNoteHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public string $arm = 'adding';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->resolveRecordUsing(fn (string $key): ?Note => $this->entityManager->find(Note::class, (int) $key));
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
        $action = CreateAction::make()
            ->recordClass(Note::class)
            ->schema($this->getFields())
            ->mutateDataUsing($this->getMutation());

        return $this->arm === 'using'
            ? $action->using(fn (array $data): Note => $this->saveNote(new Note(), $data))
            : $action;
    }

    public function editAction(): Action
    {
        $action = EditAction::make()
            ->schema($this->getFields())
            ->mutateDataUsing($this->getMutation());

        return $this->arm === 'using'
            ? $action->using(fn (array $data, Note $record): Note => $this->saveNote($record, $data))
            : $action;
    }

    public function replicateAction(): Action
    {
        $action = ReplicateAction::make()
            ->schema($this->getFields())
            ->mutateDataUsing($this->getMutation());

        return $this->arm === 'using'
            ? $action->using(fn (array $data): Note => $this->saveNote(new Note(), $data))
            : $action;
    }

    /**
     * @return list<ActionSchemaField>
     */
    private function getFields(): array
    {
        return match ($this->arm) {
            'overwriting' => [ActionSchemaField::make('title'), ActionSchemaField::make('body')],
            'hidden' => [ActionSchemaField::make('title'), ActionSchemaField::make('body')->hidden()],
            default => [ActionSchemaField::make('title')],
        };
    }

    private function getMutation(): Closure
    {
        return match ($this->arm) {
            'adding' => static fn (array $data): array => [...$data, 'body' => 'from the hook', 'meta' => ['source' => 'hook']],
            'overwriting', 'injected' => static fn (array $data): array => [...$data, 'title' => mb_strtoupper((string) $data['title'])],
            default => static fn (array $data): array => [...$data, 'body' => 'from the hook'],
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveNote(Note $note, array $data): Note
    {
        $note->setTitle((string) $data['title']);
        $note->setBody(is_string($data['body'] ?? null) ? $data['body'] : null);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
