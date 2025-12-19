<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home page - redirect to login
$routes->get('/', 'AuthController::login');

// Auth Routes - grouped for cleaner organization
$routes->group('auth', ['filter' => 'csrf'], function ($routes) {
    // Login page (GET)
    $routes->get('login', 'AuthController::login');

    // Login process (POST) - with rate limiting (CSRF + RateLimit)
    $routes->post('login', 'AuthController::doLogin');

    // Set password page (GET)
    $routes->get('set-password', 'AuthController::showSetPassword');

    // Set password process (POST) - with rate limiting
    $routes->post('set-password', 'AuthController::setPassword');

    // Logout
    $routes->get('logout', 'AuthController::logout');
});

$routes->group('dashboard', ['filter' => 'auth:admin,director,manager,executant,auditor'], function ($routes) {
    $routes->get('/', 'DashboardController::index');

    // Drill-down routes
    $routes->get('region/(:num)', 'DashboardController::regionView/$1');
    $routes->get('department/(:num)/region/(:num)', 'DashboardController::departmentView/$1/$2');
    $routes->get('contract/(:num)', 'DashboardController::contractView/$1');
    $routes->get('subdivision/(:num)', 'DashboardController::subdivisionView/$1');

    // Chart data API (for period filtering)
    $routes->get('chart/tasks-region', 'DashboardController::getChartData');
});

// Notification API Routes - require authentication
$routes->group('api', ['filter' => 'auth:admin,director,manager,executant,auditor'], function ($routes) {
    // Notifications
    $routes->get('notifications', 'NotificationController::index');
    $routes->get('notifications/unread', 'NotificationController::unread');
    $routes->get('notifications/unread-count', 'NotificationController::unreadCount');
    $routes->post('notifications/(:num)/read', 'NotificationController::markAsRead/$1');
    $routes->post('notifications/read-all', 'NotificationController::markAllAsRead');

    // Pusher
    $routes->get('pusher/config', 'NotificationController::pusherConfig');
    $routes->post('pusher/auth', 'NotificationController::pusherAuth');

    // Global Search
    $routes->get('search', 'SearchController::search');
});

// User Management Routes - access based on role (admin, director, manager)
$routes->group('users', ['filter' => 'auth:admin,director,manager'], function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');
    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('update/(:num)', 'UserController::update/$1');
    $routes->post('delete/(:num)', 'UserController::delete/$1');
});

// Region Management Routes - only admin
$routes->group('regions', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('/', 'RegionController::index');
    $routes->get('create', 'RegionController::create');
    $routes->post('store', 'RegionController::store');
    $routes->get('edit/(:num)', 'RegionController::edit/$1');
    $routes->post('update/(:num)', 'RegionController::update/$1');
    $routes->post('delete/(:num)', 'RegionController::delete/$1');
});

// Department Management Routes - only admin
$routes->group('departments', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('/', 'DepartmentController::index');
    $routes->get('create', 'DepartmentController::create');
    $routes->post('store', 'DepartmentController::store');
    $routes->get('edit/(:num)', 'DepartmentController::edit/$1');
    $routes->post('update/(:num)', 'DepartmentController::update/$1');
    $routes->post('delete/(:num)', 'DepartmentController::delete/$1');
});

// Contract Management Routes - admin, director, manager (view only for manager)
$routes->group('contracts', ['filter' => 'auth:admin,director,manager'], function ($routes) {
    $routes->get('/', 'ContractController::index');
    $routes->get('create', 'ContractController::create');
    $routes->post('store', 'ContractController::store');
    $routes->get('edit/(:num)', 'ContractController::edit/$1');
    $routes->post('update/(:num)', 'ContractController::update/$1');
    $routes->post('delete/(:num)', 'ContractController::delete/$1');
});

// Subdivision Management Routes - admin, director, manager
$routes->group('subdivisions', ['filter' => 'auth:admin,director,manager'], function ($routes) {
    $routes->get('/', 'SubdivisionController::index');
    $routes->get('create', 'SubdivisionController::create');
    $routes->post('store', 'SubdivisionController::store');
    $routes->get('edit/(:num)', 'SubdivisionController::edit/$1');
    $routes->post('update/(:num)', 'SubdivisionController::update/$1');
    $routes->post('delete/(:num)', 'SubdivisionController::delete/$1');
});

// Task Management Routes - all authenticated users (with role-based permissions in controller)
$routes->group('tasks', ['filter' => 'auth:admin,director,manager,executant,auditor'], function ($routes) {
    // List tasks (table view) - all roles
    $routes->get('/', 'TaskController::index');

    // My tasks (cards view) - all roles
    $routes->get('my-tasks', 'TaskController::myTasks');

    // Create task - manager and above
    $routes->get('create', 'TaskController::create');
    $routes->post('store', 'TaskController::store');

    // View task details - all roles (with permission check)
    $routes->get('view/(:num)', 'TaskController::view/$1');

    // Edit task - manager and above
    $routes->get('edit/(:num)', 'TaskController::edit/$1');
    $routes->post('update/(:num)', 'TaskController::update/$1');

    // Delete task - manager and above
    $routes->post('delete/(:num)', 'TaskController::delete/$1');

    // Task actions - AJAX endpoints
    $routes->post('(:num)/comment', 'TaskController::addComment/$1');
    $routes->post('(:num)/upload-file', 'TaskController::uploadFile/$1');
    $routes->post('(:num)/update-status', 'TaskController::updateStatus/$1');
});

// File download route - requires authentication
$routes->group('tasks/files', ['filter' => 'auth:admin,director,manager,executant,auditor'], function ($routes) {
    $routes->get('download/(:num)', 'TaskController::downloadFile/$1');
});

// Reports Routes - only for Director and Admin (level 80+)
$routes->group('reports', ['filter' => 'auth:admin,director'], function ($routes) {
    $routes->get('/', 'ReportsController::index');
    $routes->get('preview/(:segment)', 'ReportsController::preview/$1');
    $routes->get('export/(:segment)/excel', 'ReportsController::exportExcel/$1');
    $routes->get('export/(:segment)/pdf', 'ReportsController::exportPdf/$1');
});
