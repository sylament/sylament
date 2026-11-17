<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\MountedActionsHost;
use Sylament\Tests\Fixtures\Schemas\NoteForm;
use Sylament\Tests\Fixtures\Tables\ProductTable;

it('reaches the host it was bound to, or the one its schema, component, table or group was bound to', function (Action $action, object $host): void {
    expect($action->evaluate(static fn (object $liveComponent): object => $liveComponent))->toBe($host);
})->with([
    'its own' => fn (): array => [Action::make('save')->liveComponent($host = new MountedActionsHost()), $host],
    "its schema's" => fn (): array => [Action::make('save')->schemaContainer(($host = new MountedActionsHost())->makeSchema()), $host],
    "its component's" => function (): array {
        [$component] = ($host = new MountedActionsHost())->makeSchema()->components([TextInput::make('name')])->getComponents();

        return [Action::make('save')->schemaComponent($component), $host];
    },
    "its table's" => fn (): array => [Action::make('save')->table(Table::make($host = new ProductTable($this->entityManager()))), $host],
    "its group's, though it joined the group after the group was bound" => function (): array {
        $group = ActionGroup::make([])->liveComponent($host = new MountedActionsHost());
        $group->actions([$action = Action::make('save')]);

        return [$action, $host];
    },
]);

it('reaches no host through a schema whose host hosts no actions, and refuses to answer one it cannot reach', function (): void {
    $action = Action::make('save')->schemaContainer(new NoteForm()->makeSchema());

    expect($action->getLiveComponentOrNull())->toBeNull()
        ->and(fn (): mixed => $action->evaluate(static fn (object $liveComponent): object => $liveComponent))
        ->toThrow(RuntimeException::class, 'Action is not part of LiveComponent');
});
