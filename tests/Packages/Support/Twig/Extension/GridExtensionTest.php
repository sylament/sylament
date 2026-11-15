<?php

declare(strict_types=1);

use Sylament\Support\Enums\GridDirection;
use Symfony\Component\DomCrawler\Crawler;

it('gives grid() the sy-grid-cols marker and a column count per breakpoint, an int counting from lg', function (): void {
    $twig = $this::getContainer()->get('twig');
    $map = new Crawler($twig->createTemplate('<div {{ attrs(grid({default: 1, md: 3})) }}></div>')->render())->filter('div');
    $int = new Crawler($twig->createTemplate('<div {{ attrs(grid(2)) }}></div>')->render())->filter('div');

    expect(explode(' ', (string) $map->attr('class')))->toEqualCanonicalizing(['sy-grid', 'sy-grid-cols', 'md:sy-grid-cols'])
        ->and(explode('; ', (string) $map->attr('style')))->toEqualCanonicalizing(['--cols-default: repeat(1, minmax(0, 1fr))', '--cols-md: repeat(3, minmax(0, 1fr))'])
        ->and(explode(' ', (string) $int->attr('class')))->toEqualCanonicalizing(['sy-grid', 'sy-grid-cols', 'lg:sy-grid-cols'])
        ->and(explode('; ', (string) $int->attr('style')))->toEqualCanonicalizing(['--cols-default: repeat(1, minmax(0, 1fr))', '--cols-lg: repeat(2, minmax(0, 1fr))']);
});

it('gives a column-direction grid() its direction class and plain column counts', function (): void {
    $div = new Crawler($this::getContainer()->get('twig')->createTemplate('<div {{ attrs(grid({md: 2}, direction)) }}></div>')->render(['direction' => GridDirection::Column]))->filter('div');

    expect(explode(' ', (string) $div->attr('class')))->toEqualCanonicalizing(['sy-grid', 'sy-grid-direction-col', 'sy-grid-cols', 'md:sy-grid-cols'])
        ->and(explode('; ', (string) $div->attr('style')))->toEqualCanonicalizing(['--cols-default: 1', '--cols-md: 2']);
});

it('gives gridColumn() span, start and order classes and custom properties, a ! or @ in a breakpoint key sanitized', function (): void {
    $twig = $this::getContainer()->get('twig');
    $column = new Crawler($twig->createTemplate("<div {{ attrs(gridColumn({default: 2, '@md': 'full', '!lg': 3}, {default: 1, md: 2}, 3)) }}></div>")->render())->filter('div');
    $hidden = new Crawler($twig->createTemplate('<div {{ attrs(gridColumn(isHidden: true)) }}></div>')->render())->filter('div');

    expect(explode(' ', (string) $column->attr('class')))->toEqualCanonicalizing([
        'sy-grid-col',
        '@md:sy-grid-col-span',
        '!lg:sy-grid-col-span',
        'sy-grid-col-start',
        'md:sy-grid-col-start',
        'lg:sy-grid-col-order',
    ])
        ->and(explode('; ', (string) $column->attr('style')))->toEqualCanonicalizing([
            '--col-span-default: span 2 / span 2',
            '--col-span-cmd: 1 / -1',
            '--col-span-nlg: span 3 / span 3',
            '--col-start-default: 1',
            '--col-start-md: 2',
            '--col-order-lg: 3',
        ])
        ->and(explode(' ', (string) $hidden->attr('class')))->toEqualCanonicalizing(['sy-grid-col', 'sy-hidden']);
});

it('drops a blank breakpoint key from grid() and gridColumn()', function (): void {
    $twig = $this::getContainer()->get('twig');
    $grid = new Crawler($twig->createTemplate("<div {{ attrs(grid({' ': 2, md: 2})) }}></div>")->render())->filter('div');
    $column = new Crawler($twig->createTemplate("<div {{ attrs(gridColumn({' ': 2, md: 2})) }}></div>")->render())->filter('div');

    expect(explode(' ', (string) $grid->attr('class')))->toEqualCanonicalizing(['sy-grid', 'sy-grid-cols', 'md:sy-grid-cols'])
        ->and(explode(' ', (string) $column->attr('class')))->toEqualCanonicalizing(['sy-grid-col', 'md:sy-grid-col-span']);
});
