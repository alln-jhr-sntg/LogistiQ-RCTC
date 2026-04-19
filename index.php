<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Helpers.php';

// No url param = root visit
if (empty($_GET['url'])) {
    if (Auth::check()) {
        Helpers::redirect(Auth::dashboardUrl());
    } else {
        Helpers::redirect('/login');
    }
}

$uri    = '/' . trim($_GET['url'], '/');
$method = $_SERVER['REQUEST_METHOD'];

// ── Route table ──────────────────────────────────────────────
$routes = [

    // Auth
    ['GET',  '/login',  'AuthController', 'showLogin'],
    ['POST', '/login',  'AuthController', 'handleLogin'],
    ['POST', '/logout', 'AuthController', 'handleLogout'],

    // Dashboard
    ['GET', '/dashboard/super_admin', 'DashboardController', 'superAdmin'],
    ['GET', '/dashboard/admin',       'DashboardController', 'admin'],
    ['GET', '/dashboard/employee',    'DashboardController', 'employee'],
    ['GET', '/dashboard/driver',      'DashboardController', 'driver'],

    // Profile
    ['GET',  '/profile', 'ProfileController', 'index'],
    ['POST', '/profile', 'ProfileController', 'update'],

    // Reservations
    ['GET',  '/reservations',          'ReservationController', 'index'],
    ['GET',  '/reservations/create',   'ReservationController', 'create'],
    ['POST', '/reservations/create',   'ReservationController', 'store'],
    ['GET',  '/reservations/purposes', 'ReservationController', 'purposes'],
    ['POST', '/reservations/purposes', 'ReservationController', 'storePurpose'],
    ['GET',  '/reservations/1',        'ReservationController', 'detail'],
    ['GET',  '/reservations/2',        'ReservationController', 'detail'],
    ['GET',  '/reservations/3',        'ReservationController', 'detail'],
    ['GET',  '/reservations/4',        'ReservationController', 'detail'],
    ['GET',  '/reservations/1/review', 'ReservationController', 'review'],
    ['POST', '/reservations/1/approve','ReservationController', 'approve'],
    ['POST', '/reservations/1/reject', 'ReservationController', 'reject'],
    ['POST', '/reservations/1/cancel', 'ReservationController', 'cancel'],
    ['POST', '/reservations/2/cancel', 'ReservationController', 'cancel'],
    ['POST', '/reservations/3/cancel', 'ReservationController', 'cancel'],
    ['POST', '/reservations/4/cancel', 'ReservationController', 'cancel'],

    // Trips — admin/super admin
    ['GET',  '/trips',           'TripController', 'index'],
    ['GET',  '/trips/1',         'TripController', 'detail'],
    ['GET',  '/trips/2',         'TripController', 'detail'],
    ['GET',  '/trips/3',         'TripController', 'detail'],
    ['GET',  '/trips/1/map',     'TripController', 'liveMap'],
    ['POST', '/trips/1/start',   'TripController', 'start'],
    ['POST', '/trips/1/complete','TripController', 'complete'],
    ['POST', '/trips/1/notes',   'TripController', 'notes'],

    // Trips — driver
    ['GET',  '/trips/1/active',   'TripController', 'active'],
    ['POST', '/trips/1/incident', 'TripController', 'reportIncident'],

    // Vehicles
    ['GET',  '/vehicles',               'VehicleController', 'index'],
    ['GET',  '/vehicles/create',        'VehicleController', 'create'],
    ['POST', '/vehicles/create',        'VehicleController', 'store'],
    ['GET',  '/vehicles/categories',      'VehicleController', 'categories'],
    ['POST', '/vehicles/categories',      'VehicleController', 'storeCategory'],
    ['POST', '/vehicles/categories/1/edit', 'VehicleController', 'updateCategory'],
    ['POST', '/vehicles/categories/2/edit', 'VehicleController', 'updateCategory'],
    ['POST', '/vehicles/categories/3/edit', 'VehicleController', 'updateCategory'],
    ['GET',  '/vehicles/1/edit',        'VehicleController', 'edit'],
    ['GET',  '/vehicles/2/edit',        'VehicleController', 'edit'],
    ['GET',  '/vehicles/3/edit',        'VehicleController', 'edit'],
    ['GET',  '/vehicles/4/edit',        'VehicleController', 'edit'],
    ['POST', '/vehicles/1/edit',        'VehicleController', 'update'],
    ['GET',  '/vehicles/1/maintenance', 'VehicleController', 'maintenance'],
    ['GET',  '/vehicles/2/maintenance', 'VehicleController', 'maintenance'],
    ['GET',  '/vehicles/3/maintenance', 'VehicleController', 'maintenance'],
    ['GET',  '/vehicles/4/maintenance', 'VehicleController', 'maintenance'],
    ['POST', '/vehicles/1/maintenance', 'VehicleController', 'storeMaintenance'],

    // Users
    ['GET',  '/users',                  'UserController', 'index'],
    ['GET',  '/users/create',           'UserController', 'create'],
    ['POST', '/users/create',           'UserController', 'store'],
    ['GET',  '/users/1/edit',           'UserController', 'edit'],
    ['GET',  '/users/2/edit',           'UserController', 'edit'],
    ['GET',  '/users/3/edit',           'UserController', 'edit'],
    ['GET',  '/users/4/edit',           'UserController', 'edit'],
    ['POST', '/users/2/edit',           'UserController', 'update'],
    ['GET',  '/users/3/driver-profile', 'UserController', 'driverProfile'],
    ['POST', '/users/3/driver-profile', 'UserController', 'updateDriverProfile'],

    // Companies
    ['GET',  '/companies',              'CompanyController', 'index'],
    ['GET',  '/companies/access',       'CompanyController', 'access'],
    ['POST', '/companies/access',       'CompanyController', 'grantAccess'],
    ['POST', '/companies/access/1/revoke', 'CompanyController', 'revokeAccess'],
    ['POST', '/companies/access/2/revoke', 'CompanyController', 'revokeAccess'],
    ['GET',  '/companies/1/departments','CompanyController', 'departments'],
    ['GET',  '/companies/2/departments','CompanyController', 'departments'],
    ['GET',  '/companies/3/departments','CompanyController', 'departments'],
    ['POST', '/companies/1/departments','CompanyController', 'storeDepartment'],

    // Projects
    ['GET',  '/projects',        'ProjectController', 'index'],
    ['GET',  '/projects/create', 'ProjectController', 'create'],
    ['POST', '/projects/create', 'ProjectController', 'store'],
    ['GET',  '/projects/1/edit', 'ProjectController', 'edit'],
    ['GET',  '/projects/2/edit', 'ProjectController', 'edit'],
    ['GET',  '/projects/3/edit', 'ProjectController', 'edit'],
    ['POST', '/projects/1/edit', 'ProjectController', 'update'],

    // Reports
    ['GET', '/reports',                          'ReportController', 'index'],
    ['GET', '/reports/trip-history',             'ReportController', 'tripHistory'],
    ['GET', '/reports/maintenance-due',          'ReportController', 'maintenanceDue'],
    ['GET', '/reports/vehicle-utilization',      'ReportController', 'vehicleUtilization'],
    ['POST', '/reports/trip-history/export',     'ReportController', 'export'],
    ['POST', '/reports/maintenance-due/export',  'ReportController', 'export'],
    ['POST', '/reports/vehicle-utilization/export', 'ReportController', 'export'],

    // Notifications
    ['GET',  '/notifications',          'NotificationController', 'index'],
    ['POST', '/notifications/read-all', 'NotificationController', 'markAllRead'],

    // Settings
    ['GET',  '/settings', 'SettingsController', 'index'],
    ['POST', '/settings', 'SettingsController', 'update'],
];

// ── Dispatch ─────────────────────────────────────────────────
$matched = false;

foreach ($routes as [$routeMethod, $routeUri, $controller, $action]) {
    if ($method === $routeMethod && $uri === $routeUri) {
        $matched = true;

        $controllerFile = __DIR__ . '/controllers/' . $controller . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(501);
            echo '501 Not Implemented';
            exit;
        }

        require_once $controllerFile;
        $instance = new $controller();
        $instance->$action();
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    echo '404 Not Found — ' . htmlspecialchars($uri);
}
