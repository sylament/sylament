<?php

declare(strict_types=1);

use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

it('renders markup() content raw, and gives nothing for blank content', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('<div>{{ markup(content) ?? "nothing" }}</div>');

    expect(new Crawler($template->render(['content' => '<b>Bold</b>'])))->toContainElement('div b', 'Bold')
        ->and(new Crawler($template->render(['content' => " \u{00A0}"])))->toContainElement('div', 'nothing');
});

it('answers the filled test as filled() does', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('{{ value is filled ? "filled" : "blank" }}');

    expect($template->render(['value' => 'Text']))->toBe('filled')
        ->and($template->render(['value' => '  ']))->toBe('blank')
        ->and($template->render(['value' => []]))->toBe('blank');
});

it('passes the markup test only for a Twig Markup', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('{{ value is markup ? "markup" : "plain" }}');

    expect($template->render(['value' => new Markup('<b>Bold</b>', 'UTF-8')]))->toBe('markup')
        ->and($template->render(['value' => '<b>Bold</b>']))->toBe('plain');
});
