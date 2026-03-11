<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/*
|--------------------------------------------------------------------------
| CORE ROUTES
|--------------------------------------------------------------------------
*/

$routes->get('/', function () {
    return redirect()->to('/home');
});

$routes->get('/home', 'HomeController::index', ['filter' => 'auth']);

$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::doLogin');
$routes->get('/logout', 'AuthController::logout');


/*
|--------------------------------------------------------------------------
| IT ASSET MODULE
|--------------------------------------------------------------------------
*/

$routes->group('it-assets', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'ITAssetController::index');

    $routes->get('create', 'ITAssetController::create');
    $routes->post('store', 'ITAssetController::store');

    $routes->get('detail/(:num)', 'ITAssetController::detail/$1');

    $routes->get('edit/(:num)', 'ITAssetController::edit/$1');
    $routes->post('update/(:num)', 'ITAssetController::update/$1');

    $routes->get('assign/(:num)', 'ITAssetController::assignForm/$1');
    $routes->post('assign/(:num)', 'ITAssetController::assignSave/$1');
});


/*
|--------------------------------------------------------------------------
| EMPLOYEE MODULE
|--------------------------------------------------------------------------
*/

$routes->group('employees', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'EmployeeController::index');

    $routes->get('create', 'EmployeeController::create');
    $routes->post('store', 'EmployeeController::store');

    $routes->get('detail/(:num)', 'EmployeeController::detail/$1');

    $routes->get('edit/(:num)', 'EmployeeController::edit/$1');
    $routes->post('update/(:num)', 'EmployeeController::update/$1');

    $routes->get('deactivate/(:num)', 'EmployeeController::deactivate/$1');

    $routes->post('unassign/(:num)/(:num)', 'EmployeeController::unassign/$1/$2');
});


/*
|--------------------------------------------------------------------------
| USER MANAGEMENT (ADMIN)
|--------------------------------------------------------------------------
*/

$routes->group('users', ['filter' => 'admin'], function ($routes) {

    $routes->get('/', 'UserController::index');

    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');

    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('update/(:num)', 'UserController::update/$1');
});


/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

$routes->group('settings', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'SettingsController::index');
    $routes->post('change-password', 'SettingsController::changePassword');
});


/*
|--------------------------------------------------------------------------
| AUDIT LOG
|--------------------------------------------------------------------------
*/

$routes->get('audit-logs', 'AuditLogController::index', ['filter' => 'admin']);


/*
|--------------------------------------------------------------------------
| COMPLIANCE MODULE
|--------------------------------------------------------------------------
*/

$routes->group('compliance', ['filter' => 'auth'], function ($routes) {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    $routes->get('dashboard', 'ComplianceDashboardController::index');

    $routes->get('dashboard/trend', 'ComplianceDashboardController::getTrendAjax');
    $routes->get('dashboard/progress-trend', 'ComplianceDashboardController::getProgressTrendAjax');

    $routes->get('dashboard/status-pie', 'ComplianceDashboardController::getStatusPieAjax');
    $routes->get('dashboard/total-inventory', 'ComplianceDashboardController::getTotalInventoryByType');

    $routes->get('dashboard/risk-insight', 'ComplianceDashboardController::getRiskInsightAjax');
    $routes->get('dashboard/risk-trend', 'ComplianceDashboardController::getRiskTrendAjax');

    $routes->get('dashboard/pending-checklist', 'ComplianceDashboardController::getPendingChecklistAjax');


    /*
    |--------------------------------------------------------------------------
    | INVENTORY
    |--------------------------------------------------------------------------
    */

    $routes->group('inventory', function ($routes) {

        $routes->get('/', 'ComplianceInventoryController::index');

        $routes->get('create', 'ComplianceInventoryController::create');
        $routes->post('store', 'ComplianceInventoryController::store');

        $routes->get('detail/(:num)', 'ComplianceInventoryController::detail/$1');

        $routes->get('edit/(:num)', 'ComplianceInventoryController::edit/$1');
        $routes->post('update/(:num)', 'ComplianceInventoryController::update/$1');

        $routes->post('delete/(:num)', 'ComplianceInventoryController::delete/$1');

        $routes->post('update-photo/(:num)', 'ComplianceInventoryController::updatePhoto/$1');

        $routes->post('regenerate-qr/(:num)', 'ComplianceInventoryController::regenerateQr/$1');

        $routes->get('item-types/(:num)', 'ComplianceInventoryController::getItemTypesByCategory/$1');
    });


    /*
    |--------------------------------------------------------------------------
    | CHECKLIST
    |--------------------------------------------------------------------------
    */

    $routes->group('checklist', function ($routes) {

        $routes->get('(:num)', 'ComplianceInventoryController::checklist/$1');

        $routes->post('submit', 'ComplianceInventoryController::submitChecklist');

        $routes->get('(:num)/calendar', 'ComplianceInventoryController::calendar/$1');
    });
});


/*
|--------------------------------------------------------------------------
| REPORT MODULE
|--------------------------------------------------------------------------
*/

$routes->group('compliance/report', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'ComplianceReportController::index');

    $routes->get('load', 'ComplianceReportController::loadAjax');

    $routes->get('item-by-category', 'ComplianceReportController::getItemTypeByCategory');

    $routes->get('inventory-by-type', 'ComplianceReportController::getInventoryByType');
});


/*
|--------------------------------------------------------------------------
| EXPORT PDF
|--------------------------------------------------------------------------
*/

$routes->get(
    'export/pdf/single/(:num)/(:any)',
    'ExportPdfController::single/$1/$2',
    ['filter' => 'auth']
);

$routes->get(
    'export/pdf/recap/(:num)/(:num)/(:num)',
    'ExportPdfController::recap/$1/$2/$3',
    ['filter' => 'auth']
);


/*
|--------------------------------------------------------------------------
| IT DEVICE MONITORING
|--------------------------------------------------------------------------
*/

$routes->get('/it/devices', 'ITDeviceController::index', ['filter' => 'auth']);
$routes->get('/it/devices/ajax', 'ITDeviceController::ajax', ['filter' => 'auth']);

$routes->get('/it/devices/(:num)', 'ITDeviceController::detail/$1', ['filter' => 'auth']);

$routes->post('/it/device/push-update', 'ITDeviceController::pushUpdate', ['filter' => 'auth']);
$routes->post('/it/device/command', 'ITDeviceController::sendCommand', ['filter' => 'auth']);
$routes->post('/it/device/remote', 'ITDeviceController::remoteAction', ['filter' => 'auth']);


/*
|--------------------------------------------------------------------------
| AGENT API
|--------------------------------------------------------------------------
*/

$routes->post('/api/agent/heartbeat', 'Api\\AgentController::heartbeat');
$routes->get('/api/agent/heartbeat', 'Api\\AgentController::heartbeat');


/*
|--------------------------------------------------------------------------
| BOILER MODULE
|--------------------------------------------------------------------------
*/

$routes->group('boiler', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'BoilerFuelController::index');

    $routes->get('detail/(:segment)', 'BoilerFuelController::detail/$1');

    $routes->post('save', 'BoilerFuelController::save');

    $routes->post('delete', 'BoilerFuelController::delete');
});

$routes->get('boiler/export', 'BoilerFuelController::export', ['filter' => 'auth']);
