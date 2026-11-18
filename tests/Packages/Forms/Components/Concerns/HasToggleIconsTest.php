<?php

declare(strict_types=1);

use Sylament\Forms\Components\Toggle;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('renders each icon in a span named for its state, and only the ones given', function (Closure $toggle, array $icons): void {
    $render = $this->renderSchemaComponent($toggle());

    expect($render)->toContainElement('.sy-fo-toggle-thumb')
        ->and($render->filter(Selectors::TOGGLE_ICON_OFF . ', ' . Selectors::TOGGLE_ICON_ON)->each(static fn (Crawler $span): string => ($span->matches(Selectors::TOGGLE_ICON_OFF) ? 'off' : 'on') . ': ' . $span->filter('svg')->text()))->toBe($icons);
})->with([
    'both' => [fn (): Toggle => Toggle::make('notify')->offIcon('support:heart')->onIcon('support:star'), ['off: Heart', 'on: Star']],
    'only the off icon' => [fn (): Toggle => Toggle::make('notify')->offIcon('support:heart'), ['off: Heart']],
    'only the on icon' => [fn (): Toggle => Toggle::make('notify')->onIcon('support:star'), ['on: Star']],
    'neither' => [fn (): Toggle => Toggle::make('notify'), []],
]);
