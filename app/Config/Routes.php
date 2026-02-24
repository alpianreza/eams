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
$routes->group('compliance', ['filter' => 'auth'], function ($routes) {

    // =========================
    // DASHBOARD
    // =========================
    $routes->get('dashboard', 'ComplianceDashboardController::index');
    $routes->get('dashboard/trend', 'ComplianceDashboardController::getTrendAjax');
    $routes->get('dashboard/progress-trend', 'ComplianceDashboardController::getProgressTrendAjax');
    $routes->get('dashboard/status-pie', 'ComplianceDashboardController::getStatusPieAjax');
    $routes->get(
        'dashboard/total-inventory',
        'ComplianceDashboardController::getTotalInventoryByType'
    );

    $routes->get(
        'dashboard/risk-insight',
        'ComplianceDashboardController::getRiskInsightAjax'
    );
    $routes->get(
        'dashboard/risk-trend',
        'ComplianceDashboardController::getRiskTrendAjax'
    );
    $routes->get(
        'dashboard/pending-checklist',
        'ComplianceDashboardController::getPendingChecklistAjax'
    );



    // =========================
    // INVENTORY
    // =========================
    $routes->group('inventory', function ($routes) {

        $routes->get('/',            'ComplianceInventoryController::index');
        $routes->get('create',       'ComplianceInventoryController::create');
        $routes->post('store',       'ComplianceInventoryController::store');

        $routes->get('edit/(:num)',  'ComplianceInventoryController::edit/$1');
        $routes->post('update/(:num)', 'ComplianceInventoryController::update/$1');
        $routes->post('delete/(:num)', 'ComplianceInventoryController::delete/$1');

        $routes->get('detail/(:num)', 'ComplianceInventoryController::detail/$1');
        $routes->post('update-photo/(:num)', 'ComplianceInventoryController::updatePhoto/$1');

        $routes->post('regenerate-qr/(:num)', 'ComplianceInventoryController::regenerateQr/$1');

        $routes->get(
            'item-types/(:num)',
            'ComplianceInventoryController::getItemTypesByCategory/$1'
        );
    });

    // =========================
    // CHECKLIST (OPERASIONAL)
    // =========================
    $routes->group('checklist', function ($routes) {

        // halaman checklist utama
        $routes->get('(:num)', 'ComplianceInventoryController::checklist/$1');

        // submit checklist
        $routes->post('submit', 'ComplianceInventoryController::submitChecklist');

        // ajax calendar
        $routes->get('(:num)/calendar', 'ComplianceInventoryController::calendar/$1');
    });

    // =========================
    // CHECKLIST MASTER (SETTING)
    // =========================
    $routes->group('checklist/master', function ($routes) {

        $routes->get('/',                 'ComplianceChecklistMasterController::masterIndex');
        $routes->get('category/(:num)',   'ComplianceChecklistMasterController::masterByCategory/$1');
        $routes->get('item/(:num)',       'ComplianceChecklistMasterController::masterItem/$1');

        $routes->post('store',             'ComplianceChecklistMasterController::store');
        $routes->post('update/(:num)',     'ComplianceChecklistMasterController::update/$1');

        // update frekuensi item (CHECKPOINT)
        $routes->post(
            'item-frequency/(:num)',
            'ComplianceChecklistMasterController::updateItemFrequency/$1'
        );
        $routes->post('delete/(:num)', 'ChecklistMasterController::delete/$1');
    });

    // =========================
    // ITEM TYPE (MASTER)
    // =========================
    $routes->group('item', function ($routes) {
        $routes->get('create', 'ComplianceItemTypeController::create');
        $routes->post('store', 'ComplianceItemTypeController::store');
    });
});


$routes->group('holidays', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'HolidayController::index');
    $routes->post('store', 'HolidayController::store');
    $routes->post('update/(:num)', 'HolidayController::update/$1');
    $routes->post('delete/(:num)', 'HolidayController::delete/$1');
});

$routes->group('compliance/report', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ComplianceReportController::index');
    $routes->get('load', 'ComplianceReportController::loadAjax');
    $routes->get('item-by-category', 'ComplianceReportController::getItemTypeByCategory');
    $routes->get('inventory-by-type', 'ComplianceReportController::getInventoryByType');
});

$routes->group('compliance', ['filter' => 'auth'], function ($routes) {

    $routes->get('evidence', 'ComplianceEvidenceController::index');
    $routes->get('evidence/ajax', 'ComplianceEvidenceController::getEvidenceAjax');
    $routes->get('evidence/detail/(:num)', 'ComplianceEvidenceController::detail/$1');
    $routes->post('evidence/update-followup', 'ComplianceEvidenceController::updateFollowUp');
});

$routes->get('compliance/dashboard/data', 'ComplianceDashboardController::ajaxData', ['filter' => 'auth']);

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

$routes->get('/home', 'HomeController::index', ['filter' => 'auth']);

$routes->get('compliance/progress', 'ProgressController::index', ['filter' => 'auth']);
$routes->get('compliance/progress/ajax', 'ProgressController::getProgressAjax');

$routes->get('compliance/progress/export', 'ProgressController::export');
$routes->get('compliance/progress/detail', 'ProgressController::getUserDetailAjax');

$routes->get('compliance/inventory/get/(:num)', 'ComplianceInventoryController::get/$1');

$routes->get('compliance/inventory/qr-center', 'ComplianceInventoryController::qrCenter', ['filter' => 'auth']);
$routes->get('compliance/inventory/qr-batch', 'ComplianceInventoryController::qrBatch', ['filter' => 'auth']);
$routes->get('compliance/inventory/qr-album/(:any)', 'ComplianceInventoryController::qrAlbumAjax/$1', ['filter' => 'auth']);
$routes->get('compliance/inventory/qr-album-download/(:any)', 'ComplianceInventoryController::qrAlbumDownload/$1', ['filter' => 'auth']);
$routes->get('compliance/inventory/qr-album-regen/(:any)', 'ComplianceInventoryController::qrAlbumRegen/$1', ['filter' => 'auth']);
$routes->get(
    'compliance/inventory/qr-album-print/(:any)',
    'ComplianceInventoryController::qrAlbumPrint/$1',
    ['filter' => 'auth']
);

$routes->match(['get', 'post'], 'api/it/heartbeat', 'Api\ITController::heartbeat');
