<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', function () {
    return redirect()->to('/home');
});
$routes->get('dashboard', function () {
    return redirect()->to('/home');
});
$routes->get('dashboard/(:any)', function () {
    return redirect()->to('/home');
});
$routes->get('dashboard-it', 'DashboardController::index', ['filter' => 'auth']);
$routes->get('it', 'ITController::index', ['filter' => 'auth']);
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
    $routes->post('activate/(:num)', 'EmployeeController::activate/$1');
    $routes->post('deactivate/(:num)', 'EmployeeController::deactivate/$1');
    $routes->post('delete/(:num)', 'EmployeeController::delete/$1');
});

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
    ['filter' => ['auth', 'write']]
);

//Audit Log

$routes->get('audit-logs', 'AuditLogController::index', ['filter' => 'admin']);

$routes->group('backups', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'BackupController::index');
    $routes->post('database', 'BackupController::createDatabase');
    $routes->post('files', 'BackupController::createFiles');
    $routes->post('full', 'BackupController::createFull');
    $routes->post('upload', 'BackupController::upload');
    $routes->post('auto-enable', 'BackupController::enableAutoBackup');
    $routes->post('auto-disable', 'BackupController::disableAutoBackup');
    $routes->get('download/(:segment)', 'BackupController::download/$1');
    $routes->post('restore-full/(:segment)', 'BackupController::restoreFull/$1');
    $routes->post('restore-database/(:segment)', 'BackupController::restoreDatabase/$1');
    $routes->post('restore-files/(:segment)', 'BackupController::restoreFiles/$1');
    $routes->post('delete/(:segment)', 'BackupController::delete/$1');
});

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
        $routes->post('delete/(:num)', 'ComplianceChecklistMasterController::delete/$1');
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

$routes->group('compliance/questionnaires', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ComplianceQuestionnaireController::index');
    $routes->get('analytics', 'ComplianceQuestionnaireController::analytics');
    $routes->get('create', 'ComplianceQuestionnaireController::create');
    $routes->post('store', 'ComplianceQuestionnaireController::store');
    $routes->get('edit/(:num)', 'ComplianceQuestionnaireController::edit/$1');
    $routes->post('update/(:num)', 'ComplianceQuestionnaireController::update/$1');
    $routes->post('delete/(:num)', 'ComplianceQuestionnaireController::delete/$1');
    $routes->get('fill/(:num)', 'ComplianceQuestionnaireController::fill/$1');
    $routes->post('submit/(:num)', 'ComplianceQuestionnaireController::submit/$1');
    $routes->get('response/(:num)', 'ComplianceQuestionnaireController::responseDetail/$1');
    $routes->get('response/(:num)/pdf', 'ComplianceQuestionnaireController::responsePdf/$1');
    $routes->post('response/delete/(:num)', 'ComplianceQuestionnaireController::deleteResponse/$1');
    $routes->get('(:num)/excel', 'ComplianceQuestionnaireController::exportExcel/$1');
    $routes->post('(:num)/respondent-settings', 'ComplianceQuestionnaireController::updateRespondentSettings/$1');
    $routes->post('(:num)/questions/store', 'ComplianceQuestionnaireController::storeQuestion/$1');
    $routes->post('(:num)/questions/reorder', 'ComplianceQuestionnaireController::reorderQuestions/$1');
    $routes->post('questions/update/(:num)', 'ComplianceQuestionnaireController::updateQuestion/$1');
    $routes->post('questions/delete/(:num)', 'ComplianceQuestionnaireController::deleteQuestion/$1');
    $routes->post('questions/move-up/(:num)', 'ComplianceQuestionnaireController::moveQuestionUp/$1');
    $routes->post('questions/move-down/(:num)', 'ComplianceQuestionnaireController::moveQuestionDown/$1');
    $routes->get('(:num)', 'ComplianceQuestionnaireController::detail/$1');
});

$routes->get('kuesioner/(:segment)', 'ComplianceQuestionnaireController::publicFill/$1');
$routes->get('kuesioner/(:segment)/selesai', 'ComplianceQuestionnaireController::publicThanks/$1');
$routes->post('kuesioner/(:segment)/kirim', 'ComplianceQuestionnaireController::publicSubmit/$1');

$routes->group('compliance/print', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'CompliancePrintController::index');
    $routes->get('item', 'CompliancePrintController::item');
    $routes->get('item/preview', 'CompliancePrintController::itemPreview');
    $routes->get('inventory/(:num)', 'CompliancePrintController::inventoryByType/$1');
    $routes->get('batch', 'CompliancePrintController::batch');
    $routes->get('batch/preview', 'CompliancePrintController::batchPreview');
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
$routes->get('compliance/progress/ajax', 'ProgressController::getProgressAjax', ['filter' => 'auth']);

$routes->get('compliance/progress/export', 'ProgressController::export', ['filter' => 'auth']);
$routes->get('compliance/progress/detail', 'ProgressController::getUserDetailAjax', ['filter' => 'auth']);

$routes->get('compliance/inventory/get/(:num)', 'ComplianceInventoryController::get/$1', ['filter' => 'auth']);

$routes->group('compliance/inventory', ['filter' => 'auth'], function ($routes) {

    $routes->get('qr-center', 'ComplianceInventoryController::qrCenter');

    $routes->get('qr-album/(:any)', 'ComplianceInventoryController::qrAlbumAjax/$1');

    $routes->get('qr-album-download/(:any)', 'ComplianceInventoryController::qrAlbumDownload/$1');

    $routes->get('qr-album-regen/(:any)', 'ComplianceInventoryController::qrAlbumRegen/$1');

    $routes->get('qr-album-print/(:any)', 'ComplianceInventoryController::qrAlbumPrint/$1');
});

$routes->get('/it/devices', 'ITDeviceController::index', ['filter' => 'auth']);
$routes->get('/it/devices/ajax', 'ITDeviceController::ajax', ['filter' => 'auth']);
$routes->post('/api/agent/heartbeat', 'Api\\AgentController::heartbeat');
$routes->get('/api/agent/heartbeat', 'Api\\AgentController::heartbeat');
$routes->post('/api/agent/command', 'Api\\AgentController::command');
$routes->get('/api/agent/command', 'Api\\AgentController::command');
$routes->post('/api/agent/update', 'Api\\AgentController::agentUpdate');
$routes->get('/api/agent/update', 'Api\\AgentController::agentUpdate');
$routes->get('/it/devices/(:num)', 'ITDeviceController::detail/$1', ['filter' => 'auth']);
$routes->post('/it/device/push-update', 'Api\\AgentController::pushUpdate', ['filter' => 'auth']);
$routes->post('/it/device/command', 'ITDeviceController::sendCommand', ['filter' => 'auth']);
$routes->post('/it/device/remote', 'ITDeviceController::remoteAction', ['filter' => 'auth']);

$routes->group('boiler', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'BoilerFuelController::index');
});

$routes->group('boiler', ['filter' => 'auth'], function ($routes) {
    $routes->get('detail/(:segment)', 'BoilerFuelController::detail/$1');
    $routes->post('save', 'BoilerFuelController::save');
    $routes->post('delete', 'BoilerFuelController::delete');
});
$routes->get('boiler/export', 'BoilerFuelController::export', ['filter' => 'auth']);

$routes->group('ipal', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'IpalController::index');
    $routes->post('save', 'IpalController::save');
});

$routes->get('ipal/export', 'IpalController::export', ['filter' => 'auth']);

$routes->add('logstores/(:any)', function () {
    return service('response')->setStatusCode(404);
});
