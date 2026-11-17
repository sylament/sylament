<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders at the size it is given over its default size, and medium with neither', function (Closure $action, string $size): void {
    expect($this->renderViewComponent($action()))->toContainElement(SupportSelectors::BUTTON . SupportSelectors::size($size), 'Save');
})->with([
    'a size over a default' => [fn (): Action => Action::make('save')->defaultSize('lg')->size('sm'), 'sm'],
    'a default size' => [fn (): Action => Action::make('save')->defaultSize('lg'), 'lg'],
    'neither' => [fn (): Action => Action::make('save'), 'md'],
]);
