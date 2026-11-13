<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Infrastructure\TallyController;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('sylament_tests_tally', '/_tests/tally/{id}')
        ->controller(TallyController::class)
        ->methods(['POST'])
        ->requirements(['id' => '\d+']);

    $routes->add('sylament_tests_redirect', '/_tests/redirect')
        ->controller(RedirectController::class)
        ->defaults(['path' => '/_tests/landing', 'permanent' => false]);
};
