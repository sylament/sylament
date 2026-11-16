<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;

it('shows the actions of every call after its heading, a closure giving one or several, groups included', function (): void {
    $render = $this->renderSchemaComponent(Section::make('Shipping')
        ->headerActions([Action::make('edit')])
        ->headerActions([
            static fn (): Action => Action::make('copy'),
            static fn (): array => [Action::make('print'), ActionGroup::make([Action::make('export')])->label('More')],
        ])
        ->components([Text::make('Body')]));

    expect($render)
        ->toContainButtons('section header button', ['Edit', 'Copy', 'Print', 'More', 'Export'])
        ->and($render->filter('section footer'))->toHaveCount(0);
});
