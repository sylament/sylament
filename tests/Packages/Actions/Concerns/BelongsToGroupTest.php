<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Tests\Fixtures\Entity\Product;

it('reaches the outermost of the groups it is nested in, and none outside a group', function (): void {
    $outer = ActionGroup::make([$inner = ActionGroup::make([$edit = Action::make('edit')])]);

    expect($edit->getGroup())->toBe($inner)
        ->and($inner->getGroup())->toBe($outer)
        ->and($edit->getRootGroup())->toBe($outer)
        ->and($inner->getRootGroup())->toBe($outer)
        ->and(Action::make('edit')->getRootGroup())->toBeNull();
});

it("leaves its group when told with no argument, and no longer takes the group's record", function (): void {
    ActionGroup::make([$edit = Action::make('edit')])->record($lamp = new Product('Lamp'));
    $record = static fn (mixed $record): mixed => $record;

    expect($edit->evaluate($record))->toBe($lamp)
        ->and($edit->group()->evaluate($record))->toBeNull();
});
