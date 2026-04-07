<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group("api", ["namespace" => "App\Controllers"], function ($routes) {
    $routes->resource('stations', [
        'controller' => 'StationsController',
    ]);

    $routes->resource('observations', [
        'controller' => 'ObservationsController',
    ]);

    // $routes->resource('forecast', [
    //     'controller' => 'ForecastController',
    // ]);

    $routes->get('forecast', 'ForecastController::getForecast');
    
    $routes->resource('notifications', [
        'controller' => 'NotificationsController',
    ]);

});

$routes->get("cron/manage-log", "BackgroundController::logMyCustomMessage");
// CLI-only route to run migrations
$routes->cli('migrate/run', 'MigrateController::run');

