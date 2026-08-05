<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

it('renders a support component tag against the fixture icon set', function (): void {
    $html = $this::getContainer()->get('twig')
        ->createTemplate('<twig:SylamentSupport:Icon icon="heroicons:x-mark" />')
        ->render();

    expect($html)
        ->toContain('<svg')
        ->toContain('M6 18 18 6M6 6l12 12');
});
