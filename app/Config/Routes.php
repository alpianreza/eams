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

        $routes->get('regenerate-qr/(:num)', 'ComplianceInventoryController::regenerateQr/$1');

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
    });

    // =========================
    // ITEM TYPE (MASTER)
    // =========================
    $routes->group('item', function ($routes) {
        $routes->get('create', 'ComplianceItemTypeController::create');
        $routes->post('store', 'ComplianceItemTypeController::store');
    });
});

$routes->group('pdf/checklist', [
    'filter' => ['auth', 'pdfAccess']
], function ($routes) {

    // print satuan + lampiran
    $routes->get(
        'single/(:num)/(:segment)',
        'ChecklistPdfController::singleItemWithAttachment/$1/$2'
    );

    // rekap periode (harian/mingguan/bulanan)
    $routes->get(
        'recap/(:segment)/(:num)/(:num)',
        'ChecklistPdfController::recapMonthly/$1/$2/$3'
    );

    // rekap tahunan per item (APAR Jan–Des)
    $routes->get(
        'item-yearly/(:num)/(:num)',
        'ChecklistPdfController::recapItemYearly/$1/$2'
    );
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
