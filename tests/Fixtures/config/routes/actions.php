<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('sylament_tests_actions_localised_live', '/{_locale}/_components/{_live_component}/{_live_action}')
        ->defaults(['_locale' => 'en', '_live_action' => 'get'])
        ->requirements(['_locale' => '[a-z]{2}', '_live_component' => 'Actions:[^/]+']);
};
