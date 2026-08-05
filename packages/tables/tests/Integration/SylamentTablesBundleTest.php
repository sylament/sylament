<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

it('boots the kernel with twig available', function (): void {
    expect($this::getContainer()->has('twig'))->toBeTrue();
});
