<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration;

use Twig\Environment;

it('registers the renderSchema twig function', function (): void {
    $twig = $this::getContainer()->get('twig');

    expect($twig)->toBeInstanceOf(Environment::class)
        ->and($twig->getFunction('renderSchema'))->not->toBeFalse();
});
