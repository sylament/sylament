<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;

it('shows the actions of every call in its footer, a closure giving one or several, groups included', function (): void {
    $render = $this->renderSchemaComponent(Section::make('Shipping')
        ->footerActions([Action::make('save')])
        ->footerActions([
            static fn (): Action => Action::make('cancel'),
            static fn (): array => [Action::make('print'), ActionGroup::make([Action::make('export')])->label('More')],
        ])
        ->components([Text::make('Body')]));

    expect($render)
        ->toContainButtons('section footer button', ['Save', 'Cancel', 'Print', 'More', 'Export'])
        ->and($render->filter('section header'))->toHaveCount(1)
        ->and($render->filter('section header button'))->toHaveCount(0);
});
