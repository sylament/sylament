<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Notifications\NotificationsPageController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('sylament_tests_notifications_page', NotificationsPageController::URL)
        ->controller(NotificationsPageController::class)
        ->methods(['GET']);
};
