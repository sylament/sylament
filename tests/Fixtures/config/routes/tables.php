<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('sylament_tests_tables_page', '/tables/page/{component}')
        ->controller(TablePageController::class)
        ->methods(['GET'])
        ->requirements(['component' => 'Tables:\w+']);
};
