<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');

// Authenticated (any role)
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('logout', 'Auth::logout');
    $routes->get('/', 'Dashboard::index');

    $routes->get('items', 'Items::index');
    $routes->get('customers', 'Customers::index');
    $routes->get('ledger', 'Ledger::index');
    $routes->get('ledger/export', 'Ledger::exportCsv');
    $routes->get('reports', 'Reports::index');
    $routes->get('reports/print', 'Reports::printView');
    $routes->get('reports/export', 'Reports::exportCsv');
    $routes->get('batches', 'Batches::index');
    $routes->get('batches/(:segment)/export', 'Batches::exportCsv/$1');
});

// Editors: ADMIN, MANAGER, STOREKEEPER
$routes->group('', ['filter' => 'role:editor'], static function ($routes) {
    $routes->get('items/new', 'Items::create');
    $routes->post('items/new', 'Items::store');
    $routes->get('items/(:segment)/edit', 'Items::edit/$1');
    $routes->post('items/(:segment)/edit', 'Items::update/$1');
    $routes->post('items/(:segment)/delete', 'Items::deactivate/$1');

    $routes->get('customers/new', 'Customers::create');
    $routes->post('customers/new', 'Customers::store');
    $routes->get('customers/(:segment)/edit', 'Customers::edit/$1');
    $routes->post('customers/(:segment)/edit', 'Customers::update/$1');
    $routes->post('customers/(:segment)/delete', 'Customers::deactivate/$1');

    $routes->get('inward', 'Scan::inward');
    $routes->get('outward', 'Scan::outward');
    $routes->get('movements/new', 'Movements::create');
    $routes->post('movements/new', 'Movements::store');

    $routes->get('labels', 'Labels::index');
    $routes->get('labels/print', 'Labels::printView');
    $routes->get('batches/(:segment)/print-labels', 'Batches::printLabels/$1');
});

// Admin only
$routes->group('', ['filter' => 'role:admin'], static function ($routes) {
    $routes->get('settings', 'Settings::index');
    $routes->post('settings', 'Settings::save');
    $routes->post('settings/users', 'Settings::createUser');
    $routes->post('settings/users/(:segment)', 'Settings::updateUser/$1');
    $routes->post('settings/users/(:segment)/delete', 'Settings::deactivateUser/$1');
});

// JSON API (AJAX; CSRF via X-CSRF-TOKEN header)
$routes->group('api', ['filter' => 'role:editor'], static function ($routes) {
    $routes->post('scan/validate', 'Api\Scan::check');
    $routes->post('scan/commit', 'Api\Scan::commit');
    $routes->get('items/next-barcode', 'Api\Items::nextBarcode');
    $routes->post('batches', 'Api\Batches::generate');
    $routes->post('batches/(:segment)/printed', 'Api\Batches::recordPrint/$1');
});
$routes->group('api', ['filter' => 'auth'], static function ($routes) {
    $routes->get('batches/(:segment)', 'Api\Batches::details/$1');
});
