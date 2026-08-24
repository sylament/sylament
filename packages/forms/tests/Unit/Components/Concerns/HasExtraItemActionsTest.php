<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Forms\Components\Repeater;
use Sylament\Support\Enums\Size;

it('appends extra item actions across calls, unwraps closures and stamps the icon-button defaults', function (): void {
    $repeater = Repeater::make('rows')
        ->extraItemActions([Action::make('first')])
        ->extraItemActions([static fn (): array => [Action::make('second')->color('primary')]]);

    $actions = $repeater->getExtraItemActions();

    expect(array_keys($actions))->toBe(['first', 'second'])
        ->and($actions['first']->getColor())->toBe('gray')
        ->and($actions['first']->getSize())->toBe(Size::Small)
        ->and($actions['first']->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($actions['second']->getColor())->toBe('primary')
        ->and($actions['first']->getSchemaComponent())->toBe($repeater);
});
