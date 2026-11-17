<?php

declare(strict_types=1);

use Sylament\Forms\Components\Concerns\HasDatalistOptions;
use Sylament\Tests\Infrastructure\ComponentRegistry;
use Symfony\Component\DomCrawler\Crawler;

it('offers its options in a datalist the control of every field that offers one points at, and none when it has none', function (string $field): void {
    $render = $this->renderSchemaComponent($field::make('browser')->datalist(['Firefox', 'Safari']));

    expect($render)->toContainElement('[name="browser"][list="schema.browser-list"]')
        ->and($render->filter('datalist[id="schema.browser-list"] option')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['Firefox', 'Safari'])
        ->and($this->renderSchemaComponent($field::make('browser')))
        ->toContainElement('[name="browser"]:not([list])')
        ->not->toContainElement('datalist');
})->with(fn (): array => ComponentRegistry::componentsUsing(HasDatalistOptions::class));
