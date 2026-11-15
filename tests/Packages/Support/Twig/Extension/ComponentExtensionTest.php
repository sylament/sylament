<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Symfony\Component\DomCrawler\Crawler;

it('renders a component unescaped, and nothing for no component or one without a template', function (): void {
    $template = $this::getContainer()->get('twig')->createTemplate('<div data-slot="rendered">{{ renderComponent(rendered) }}</div><div data-slot="none">{{ renderComponent(none) }}</div><div data-slot="untemplated">{{ renderComponent(untemplated) }}</div>');

    $page = new Crawler($template->render([
        'rendered' => Html::make('<p>Greeting</p>'),
        'none' => null,
        'untemplated' => Html::make('<p>Greeting</p>')->template(null),
    ]));

    expect($page)->toContainElement('[data-slot="rendered"] p', 'Greeting')
        ->and($page->filter('[data-slot="none"]')->html())->toBe('')
        ->and($page->filter('[data-slot="untemplated"]')->html())->toBe('');
});
