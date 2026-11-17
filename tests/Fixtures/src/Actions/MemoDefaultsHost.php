<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host answering the `getDefaultAction*()` hooks for the one action each names, as a
 * resource page does: a URL for its `EditAction` (or for every action, when
 * `answersEveryAction` is on), a success redirect for `archive` and a failure redirect for
 * `restore`, and a {@see Memo} record, its title and a form for its `ViewAction`, and the
 * record class and label its `CreateAction` names. `review` opens a modal and `visit` has
 * a URL of its own.
 */
#[AsLiveComponent(name: 'Actions:MemoDefaultsHost', template: '@Fixtures/actions/MemoDefaultsHost.html.twig')]
class MemoDefaultsHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public const string EDIT_URL = '/memos/edit';

    public const string EVERY_ACTION_URL = '/memos/every';

    #[LiveProp]
    public bool $answersEveryAction = false;

    public function schemas(): array
    {
        return [];
    }

    public function editAction(): EditAction
    {
        return EditAction::make();
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make();
    }

    public function reviewAction(): Action
    {
        return Action::make('review')->modal()->modalHeading('Review');
    }

    public function visitAction(): Action
    {
        return Action::make('visit')->url('/visit');
    }

    public function viewAction(): ViewAction
    {
        return ViewAction::make();
    }

    public function createAction(): CreateAction
    {
        return CreateAction::make();
    }

    public function archiveAction(): Action
    {
        return Action::make('archive');
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')->action(static function (Action $action): void {
            $action->failure();
        });
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        if ($this->answersEveryAction) {
            return self::EVERY_ACTION_URL;
        }

        return ($action instanceof EditAction) ? self::EDIT_URL : null;
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        return ($action->getName() === 'archive') ? '/memos/archived' : null;
    }

    public function getDefaultActionFailureRedirectUrl(Action $action): ?string
    {
        return ($action->getName() === 'restore') ? '/memos/not-restored' : null;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        if (!$action instanceof ViewAction) {
            return null;
        }

        return static fn (Schema $schema): Schema => $schema->components([
            TextInput::make('subject'),
        ]);
    }

    public function getDefaultActionRecord(Action $action): ?Memo
    {
        return ($action instanceof ViewAction) ? new Memo('Ledger') : null;
    }

    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        return ($action instanceof ViewAction) ? 'the ledger' : null;
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return ($action instanceof CreateAction) ? Memo::class : null;
    }

    public function getDefaultActionRecordLabel(Action $action): ?string
    {
        return ($action instanceof CreateAction) ? 'memo entry' : null;
    }
}
