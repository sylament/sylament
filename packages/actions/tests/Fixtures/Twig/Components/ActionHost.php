<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'ActionHost', template: '@ActionsFixtures/components/ActionHost.html.twig')]
class ActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    /**
     * Exposed as a LiveProp so the closure's side effect survives the round-trip
     * and can be read back in the harness (the `LiveStateHost::$savedState` idiom).
     */
    #[LiveProp]
    public bool $called = false;

    /**
     * Set by the two non-confirming actions below; `blocked` shares it so the
     * disabled test asserts the closure never ran.
     */
    #[LiveProp]
    public bool $notified = false;

    /**
     * Plain public property, not a LiveProp: the arguments test calls mountAction()
     * and callMountedAction() directly on one instance, so there is no round-trip
     * for it to survive.
     *
     * @var array<string, mixed>|null
     */
    public ?array $argumentsSeen = null;

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->action(fn (): true => $this->called = true);
    }

    public function notifyAction(): Action
    {
        return Action::make('notify')
            ->action(fn (): true => $this->notified = true);
    }

    /**
     * Carries only a modal heading — no confirmation. Pins the general
     * shouldOpenModal() gate: this must mount and open a modal, not auto-run.
     */
    public function headedAction(): Action
    {
        return Action::make('headed')
            ->modalHeading('Headed?')
            ->action(fn (): true => $this->notified = true);
    }

    public function blockedAction(): Action
    {
        return Action::make('blocked')
            ->disabled()
            ->action(fn (): true => $this->notified = true);
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->modalHeading('Edit thing')
            ->action(function (array $arguments): void {
                $this->argumentsSeen = $arguments;
            })
            ->extraModalFooterActions([
                Action::make('confirmDelete')->modalHeading('Really delete?'),
                Action::make('deeper')->modalHeading('Deeper')
                    ->cancelParentActionsOnClose('edit')
                    ->extraModalFooterActions([
                        Action::make('deepest')->modalHeading('Deepest'),
                    ]),
                Action::make('overlayChild')->modalHeading('Over the top')->overlayParentActions(),
                Action::make('haltingChild')->action(fn (Action $action) => $action->halt()),
            ]);
    }

    public function replacerAction(): Action
    {
        return Action::make('replacer')
            ->action(function (): void {
                $this->replaceMountedAction('headed');
            });
    }

    /**
     * Bare method (no `Action` suffix) with a declared Action return type —
     * resolvable through getAction()'s reflection gate.
     */
    public function publish(): Action
    {
        return Action::make('publish');
    }

    /**
     * Bare sibling of deleteAction(): getAction('delete') must prefer the
     * suffixed method, so the resolved action is named `delete`, never `delete-bare`.
     */
    public function delete(): Action
    {
        return Action::make('delete-bare');
    }

    /**
     * Deliberately missing a native return type: the reflection gate must
     * refuse to invoke it. The dynamic call keeps Rector's type-declaration
     * set from inferring (and re-adding) a return type.
     */
    public function untyped()
    {
        return $this->{'publish'}();
    }

    public function notAnAction(): string
    {
        return 'not-an-action';
    }

    /**
     * Lying factory: the declared union passes the reflection gate, but the
     * runtime value is not an Action — getAction() must throw.
     */
    public function liar(): Action|string
    {
        return 'x';
    }
}
