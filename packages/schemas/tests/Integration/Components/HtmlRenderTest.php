<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Html;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

it('renders string content raw without a wrapper element', function (): void {
    $html = renderToHtml(Html::make('<div class="custom">raw</div>'));

    expect(mb_trim($html))->toBe('<div class="custom">raw</div>');
});

it('renders Markup content raw', function (): void {
    $html = renderToHtml(Html::make(new Markup('<b>x</b>', 'UTF-8')));

    expect($html)->toContain('<b>x</b>');
});

it('renders nothing for null content', function (): void {
    expect(mb_trim(renderToHtml(Html::make(null))))->toBe('');
});
