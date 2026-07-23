<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Html;
use Twig\Markup;

it('returns the content', function (): void {
    expect(Html::make('<b>raw</b>')->getContent())->toBe('<b>raw</b>');
});

it('evaluates a content closure', function (): void {
    expect(Html::make(fn (): string => '<i>x</i>')->getContent())->toBe('<i>x</i>');
});

it('returns a Markup content unchanged', function (): void {
    $markup = new Markup('<b>x</b>', 'UTF-8');

    expect(Html::make($markup)->getContent())->toBe($markup);
});

it('returns null content', function (): void {
    expect(Html::make(null)->getContent())->toBeNull();
});
