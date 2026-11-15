<?php

declare(strict_types=1);

use Symfony\Component\DomCrawler\Crawler;

it('merges attrs() sources left to right, appending class and style and the later scalar winning, a falsy source skipped', function (): void {
    $div = new Crawler($this::getContainer()->get('twig')->createTemplate(
        "<div {{ attrs({class: 'a', style: 'color: red', id: 'first'}, null, false, '', {class: 'b', style: 'margin: 0', id: 'second'}) }}></div>",
    )->render())->filter('div');

    expect($div->attr('class'))->toBe('a b')
        ->and($div->attr('style'))->toBe('color: red; margin: 0')
        ->and($div->attr('id'))->toBe('second');
});

it('refuses a string source other than the empty string', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate("<div {{ attrs('class=\"a\"') }}></div>");

    expect(fn (): string => $template->render())->toThrow('Only empty strings may be passed as string sources to attrs(), to support the implicit else of a ternary.');
});

it('joins classes() into a string attrs() splits again, and styles() into declarations in their order', function (): void {
    $div = new Crawler($this::getContainer()->get('twig')->createTemplate(
        "<div {{ attrs({class: classes('a', {b: true, c: false})}, {class: 'd'}) }} style=\"{{ styles('color: red', {color: 'blue', margin: 0}) }}\"></div>",
    )->render())->filter('div');

    expect($div->attr('class'))->toBe('a b d')
        ->and($div->attr('style'))->toBe('color: red; color: blue; margin: 0');
});
