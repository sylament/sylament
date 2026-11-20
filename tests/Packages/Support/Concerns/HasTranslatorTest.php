<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Markup;

it('translates a message through the container translator', function (): void {
    expect(Text::make('x')->translateText(new TranslatableMessage('greeting', ['name' => 'Ada'], 'fixtures-support')))->toBe('Hello Ada');
});

it('passes a string and a Markup through untouched', function (): void {
    $text = Text::make('x');
    $markup = new Markup('<b>b</b>', 'UTF-8');

    expect($text->translateText('plain'))->toBe('plain')
        ->and($text->translateText($markup))->toBe($markup)
        ->and($text->translateText(null))->toBeNull();
});

it('refuses a message on a component built without the container, naming its class', function (): void {
    expect(fn (): mixed => new Text()->translateText(new TranslatableMessage('plain', [], 'fixtures-support')))
        ->toThrow(LogicException::class, 'The [' . Text::class . '] was built without the container');
});

it('needs no translator for a string on a component built without the container', function (): void {
    expect(new Text()->translateText('plain'))->toBe('plain');
});
