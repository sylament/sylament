<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Actions;
use Sylament\Tests\Fixtures\Actions\Selectors;

it('leaves out of the render an action that is hidden or not visible, a hidden one even when told it is visible', function (): void {
    expect($this->renderSchemaComponent(Actions::make([
        Action::make('keep'),
        Action::make('purge')->hidden(static fn (): bool => true),
        Action::make('archive')->visible(false),
        Action::make('stash')->hidden()->visible(),
        Action::make('reveal')->hidden(static fn (): bool => false),
    ])))->toContainButtons(Selectors::TRIGGER, ['Keep', 'Reveal']);
});
