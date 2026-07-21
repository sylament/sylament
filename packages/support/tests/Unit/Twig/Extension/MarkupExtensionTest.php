<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Twig\Extension\MarkupExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

it('registers the markup function', function (): void {
    $functions = new MarkupExtension()->getFunctions();

    expect($functions)->toHaveCount(1)
        ->and($functions[0]->getName())->toBe('markup');
});

it('returns null for null and blank content', function (string|null $content): void {
    $twig = new Environment(new ArrayLoader());

    expect(new MarkupExtension()->markup($twig, $content))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ["  \n\t  "],
]);

it('wraps content in a Markup instance', function (): void {
    $twig = new Environment(new ArrayLoader());

    $markup = new MarkupExtension()->markup($twig, '<b>bold</b>');

    expect($markup)->toBeInstanceOf(Markup::class)
        ->and((string) $markup)->toBe('<b>bold</b>');
});

it('uses the environment charset', function (): void {
    $twig = new Environment(new ArrayLoader());
    $twig->setCharset('ISO-8859-1');

    $markup = new MarkupExtension()->markup($twig, 'content');

    expect($markup?->getCharset())->toBe($twig->getCharset());
});
