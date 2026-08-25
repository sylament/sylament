<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;

it('names itself replicate', function (): void {
    expect(ReplicateAction::make()->getName())->toBe('replicate')
        ->and(ReplicateAction::getDefaultName())->toBe('replicate');
});

it('keeps an explicit name over the default', function (): void {
    expect(ReplicateAction::make('replicateArticle')->getName())->toBe('replicateArticle');
});

it('carries the replicate chrome', function (): void {
    $action = ReplicateAction::make();

    expect($action->getLabel())->toBe('Replicate')
        ->and($action->getIcon())->toBe('heroicons:square-2-stack-20-solid')
        ->and($action->getGroupedIcon())->toBe('heroicons:square-2-stack-20-solid')
        ->and($action->getModalSubmitActionLabel())->toBe('Replicate')
        ->and($action->getSuccessNotificationTitle())->toBe('Replicated');
});

it('interpolates the record title into the modal heading', function (): void {
    $record = new class {
        public string $name = 'Ada';
    };

    expect(ReplicateAction::make()->record($record)->recordTitleAttribute('name')->getModalHeading())->toBe('Replicate Ada')
        ->and(ReplicateAction::make()->getModalHeading())->toBe('Replicate');
});

it('evaluates the excluded attributes, literal or closure', function (): void {
    expect(ReplicateAction::make()->getExcludedAttributes())->toBeNull()
        ->and(ReplicateAction::make()->excludeAttributes(['slug'])->getExcludedAttributes())->toBe(['slug'])
        ->and(ReplicateAction::make()->excludeAttributes(fn (): array => ['note'])->getExcludedAttributes())->toBe(['note']);
});

it('re-points the modal schema at the record class, so it fills as a create', function (): void {
    $note = new Note();

    $action = ReplicateAction::make()
        ->record($note)
        ->schema([Text::make('hi')]);

    $container = Schema::make()->record($note);

    expect($container->getRecord())->toBe($note)
        ->and($action->getSchema($container)?->getRecord())->toBeNull()
        ->and($container->getRecordClass())->toBe(Note::class);
});

it('leaves a schemaless action without a container to re-point', function (): void {
    expect(ReplicateAction::make()->getSchema(Schema::make()))->toBeNull();
});

it('hands the replica to a closure that asks for it by name', function (): void {
    $replica = new Note();

    $action = new class extends ReplicateAction {
        public function setReplica(?object $replica): void
        {
            $this->replica = $replica;
        }
    };

    expect($action->getReplica())->toBeNull()
        ->and($action->evaluate(fn (?object $replica): ?object => $replica))->toBeNull();

    $action->setReplica($replica);

    expect($action->getReplica())->toBe($replica)
        ->and($action->evaluate(fn (object $replica): object => $replica))->toBe($replica);
});

it('runs the beforeReplicaSaved hook on demand', function (): void {
    $calls = [];

    $action = ReplicateAction::make()->beforeReplicaSaved(function () use (&$calls): void {
        $calls[] = 'called';
    });

    $action->callBeforeReplicaSaved();
    $action->callBeforeReplicaSaved();

    expect($calls)->toBe(['called', 'called']);

    $action->beforeReplicaSaved(null);
    $action->callBeforeReplicaSaved();

    expect($calls)->toHaveCount(2);
});
