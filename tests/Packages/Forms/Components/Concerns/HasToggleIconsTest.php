<?php

declare(strict_types=1);

use Sylament\Forms\Components\Toggle;
use Symfony\Component\DomCrawler\Crawler;

it('renders each icon in a span named for its state, and only the ones given', function (Closure $toggle, array $icons): void {
    $render = $this->renderSchemaComponent($toggle());

    expect($render)->toContainElement('.sy-fo-toggle-thumb')
        ->and($render->filter('.sy-fo-toggle-icon-off, .sy-fo-toggle-icon-on')->each(static fn (Crawler $span): string => $span->attr('class') . ': ' . $span->filter('svg')->text()))->toBe($icons);
})->with([
    'both' => [fn (): Toggle => Toggle::make('notify')->offIcon('support:heart')->onIcon('support:star'), ['sy-fo-toggle-icon-off: Heart', 'sy-fo-toggle-icon-on: Star']],
    'only the off icon' => [fn (): Toggle => Toggle::make('notify')->offIcon('support:heart'), ['sy-fo-toggle-icon-off: Heart']],
    'only the on icon' => [fn (): Toggle => Toggle::make('notify')->onIcon('support:star'), ['sy-fo-toggle-icon-on: Star']],
    'neither' => [fn (): Toggle => Toggle::make('notify'), []],
]);
