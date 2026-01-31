<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'DashboardController::index', ['filter' => 'auth']);
$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::doLogin');
$routes->get('/logout', 'AuthController::logout');

/** asset IT */
$routes->group('it-assets', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ITAssetController::index');
    $routes->get('detail/(:num)', 'ITAssetController::detail/$1');
    $routes->get('assign/(:num)', 'ITAssetController::assignForm/$1');
    $routes->post('assign/(:num)', 'ITAssetController::assignSave/$1');
    $routes->get('create', 'ITAssetController::create');
    $routes->post('store', 'ITAssetController::store');

    $routes->get('edit/(:num)', 'ITAssetController::edit/$1');
    $routes->post('update/(:num)', 'ITAssetController::update/$1');
});

// employee
$routes->group('employees', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'EmployeeController::index');
    $routes->get('create', 'EmployeeController::create');
    $routes->post('store', 'EmployeeController::store');
    $routes->get('detail/(:num)', 'EmployeeController::detail/$1');
    $routes->get('edit/(:num)', 'EmployeeController::edit/$1');
    $routes->post('update/(:num)', 'EmployeeController::update/$1');
    $routes->get('deactivate/(:num)', 'EmployeeController::deactivate/$1');
});

$routes->post('employees/unassign/(:num)/(:num)', 'EmployeeController::unassign/$1/$2');

// routes khusus admin
$routes->group('users', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');
    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('update/(:num)', 'UserController::update/$1');
});

//filter

$routes->group('it-assets', ['filter' => 'write'], function ($routes) {
    $routes->get('create', 'ITAssetController::create');
    $routes->post('store', 'ITAssetController::store');
    $routes->get('assign/(:num)', 'ITAssetController::assign/$1');
    $routes->post('assign-save/(:num)', 'ITAssetController::assignSave/$1');
});

$routes->post(
    'employees/unassign/(:num)/(:num)',
    'EmployeeController::unassign/$1/$2',
    ['filter' => 'write']
);

//Audit Log

$routes->get('audit-logs', 'AuditLogController::index', ['filter' => 'admin']);

//setting
$routes->group('settings', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'SettingsController::index');
    $routes->post('change-password', 'SettingsController::changePassword');
});
// Compliance Inventory
$routes->group('compliance', function ($routes) {
    $routes->get('inventory/(:num)', 'ComplianceInventoryController::detail/$1');
});
$routes->group('compliance', function ($routes) {
    $routes->get('inventory/(:num)', 'ComplianceInventoryController::detail/$1');

    // checklist
    $routes->get(
        'inventory/(:num)/checklist/(:num)',
        'ComplianceChecklistController::create/$1/$2'
    );
    $routes->post(
        'inventory/(:num)/checklist/(:num)',
        'ComplianceChecklistController::store/$1/$2'
    );
});

// IMPORT EXCEL

$routes->group('compliance', function ($routes) {
    $routes->get('checklist/import', 'ComplianceChecklistImportController::index');
    $routes->post('checklist/import', 'ComplianceChecklistImportController::import');
});
#compliance dashboard
$routes->group('compliance', function ($routes) {
    $routes->get('dashboard', 'ComplianceDashboardController::index');
});

$routes->get(
    'compliance/inventory',
    'ComplianceInventoryController::index'
);

$routes->get(
    'compliance/overdue',
    'ComplianceDashboardController::overdue'
);

$routes->group('compliance', function ($routes) {
    $routes->get('inventory/create', 'ComplianceInventoryController::create');
    $routes->post('inventory/store', 'ComplianceInventoryController::store');
});

$routes->get(
    'compliance/inventory/(:num)/regenerate-qr',
    'ComplianceInventoryController::regenerateQr/$1'
);

$routes->post('compliance/inventory/store', 'ComplianceInventoryController::store');
$routes->group('compliance/inventory', function ($routes) {
    $routes->get('/', 'ComplianceInventoryController::index');
    $routes->post('store', 'ComplianceInventoryController::store');

    $routes->get('edit/(:num)', 'ComplianceInventoryController::edit/$1');
    $routes->post('update/(:num)', 'ComplianceInventoryController::update/$1');

    $routes->post('delete/(:num)', 'ComplianceInventoryController::delete/$1');
});

$routes->get('compliance/inventory/detail/(:num)', 'ComplianceInventoryController::detail/$1');
$routes->post('compliance/inventory/update-photo/(:num)', 'ComplianceInventoryController::updatePhoto/$1');

$routes->get(
    'compliance/inventory/item-types/(:num)',
    'ComplianceInventoryController::getItemTypesByCategory/$1'
);
$routes->post('compliance/checklist/store', 'ComplianceChecklistController::store');

$routes->get(
    'compliance/checklist/(:num)',
    'ComplianceInventoryController::checklist/$1'
);

$routes->post('compliance/checklist/submit', 'ComplianceInventoryController::submitChecklist');

$routes->get(
    'compliance/checklist/(:num)/calendar',
    'ComplianceInventoryController::calendar/$1'
);

$routes->group('compliance/checklist', function ($routes) {

    // MASTER (MANAGEMENT)
    $routes->get('master', 'ComplianceChecklistMasterController::masterIndex');
    $routes->get('master/category/(:num)', 'ComplianceChecklistMasterController::masterByCategory/$1');
    $routes->get('master/item/(:num)', 'ComplianceChecklistMasterController::masterItem/$1');

    $routes->post('master/store', 'ComplianceChecklistMasterController::store');
    $routes->post('master/update/(:num)', 'ComplianceChecklistMasterController::update/$1');
});

$routes->post(
    'compliance/checklist/master/item-frequency/(:num)',
    'ComplianceChecklistMasterController::updateItemFrequency/$1'
);

$routes->group('compliance/item', function ($routes) {
    $routes->get('create', 'ComplianceItemTypeController::create');
    $routes->post('store', 'ComplianceItemTypeController::store');
});
