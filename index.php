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
// Dynamic segments use {id} — matched as one-or-more digits and
// passed to the controller method in the order they appear in
// the URI. Static segments must come before {id} patterns for
// the same prefix to prevent 'create', 'categories', etc. from
// being captured as an {id} segment.
$routes = [

    // Auth
    ['GET',  '/login',  'AuthController', 'showLogin'],
    ['POST', '/login',  'AuthController', 'handleLogin'],
    ['POST', '/logout', 'AuthController', 'handleLogout'],

    // Dashboard
    ['GET', '/dashboard/super_admin', 'DashboardController', 'superAdmin'],
    ['GET', '/dashboard/fleet_admin', 'DashboardController', 'fleetAdmin'],
    ['GET', '/dashboard/admin',       'DashboardController', 'admin'],
    ['GET', '/dashboard/employee',    'DashboardController', 'employee'],
    ['GET', '/dashboard/driver',      'DashboardController', 'driver'],

    // Profile
    ['GET',  '/profile', 'ProfileController', 'index'],
    ['POST', '/profile', 'ProfileController', 'update'],

    // Reservations — static before {id}
    ['GET',  '/reservations',                    'ReservationController', 'index'],
    ['GET',  '/reservations/create',             'ReservationController', 'create'],
    ['POST', '/reservations/create',             'ReservationController', 'store'],
    ['GET',  '/reservations/purposes',           'ReservationController', 'purposes'],
    ['POST', '/reservations/purposes',           'ReservationController', 'storePurpose'],
    ['POST', '/reservations/purposes/{id}/edit', 'ReservationController', 'updatePurpose'],
    ['GET',  '/reservations/{id}',               'ReservationController', 'detail'],
    ['GET',  '/reservations/{id}/edit',          'ReservationController', 'editReservation'],
    ['POST', '/reservations/{id}/edit',          'ReservationController', 'updateReservation'],
    ['GET',  '/reservations/{id}/review',        'ReservationController', 'review'],
    ['POST', '/reservations/{id}/approve',       'ReservationController', 'approve'],
    ['POST', '/reservations/{id}/reject',        'ReservationController', 'reject'],
    ['POST', '/reservations/{id}/cancel',        'ReservationController', 'cancel'],

    // Gatepasses — static before {id}
    ['GET',  '/gatepasses',              'GatepassController', 'index'],
    ['GET',  '/gatepasses/{id}/review',  'GatepassController', 'review'],
    ['POST', '/gatepasses/{id}/approve', 'GatepassController', 'approve'],
    ['POST', '/gatepasses/{id}/reject',  'GatepassController', 'reject'],
    ['GET',  '/gatepasses/{id}/print',   'GatepassController', 'printView'],

    // Trips
    ['GET',  '/trips',                  'TripController', 'index'],
    ['GET',  '/trips/{id}',             'TripController', 'detail'],
    ['GET',  '/trips/{id}/map',         'TripController', 'liveMap'],
    ['POST', '/trips/{id}/start',       'TripController', 'start'],
    ['POST', '/trips/{id}/complete',    'TripController', 'complete'],
    ['POST', '/trips/{id}/cancel',      'TripController', 'cancelTrip'],
    ['POST', '/trips/{id}/notes',       'TripController', 'notes'],
    ['GET',  '/trips/{id}/active',      'TripController', 'active'],
    ['POST', '/trips/{id}/incident',              'TripController', 'reportIncident'],
    ['POST', '/trips/{id}/incident/{id}/resolve', 'TripController', 'resolveIncident'],

    // Vehicles — static before {id}
    ['GET',  '/vehicles',                           'VehicleController', 'index'],
    ['GET',  '/vehicles/create',                    'VehicleController', 'create'],
    ['POST', '/vehicles/create',                    'VehicleController', 'store'],
    ['GET',  '/vehicles/categories',                'VehicleController', 'categories'],
    ['POST', '/vehicles/categories',                'VehicleController', 'storeCategory'],
    ['POST', '/vehicles/categories/{id}/edit',      'VehicleController', 'updateCategory'],
    ['GET',  '/vehicles/{id}/edit',                 'VehicleController', 'edit'],
    ['POST', '/vehicles/{id}/edit',                 'VehicleController', 'update'],
    ['GET',  '/vehicles/{id}/maintenance',          'VehicleController', 'maintenance'],
    ['POST', '/vehicles/{id}/maintenance',          'VehicleController', 'storeMaintenance'],
    ['POST', '/vehicles/{id}/maintenance/check',    'VehicleController', 'checkMaintenance'],

    // Users — static before {id}
    ['GET',  '/users',                      'UserController', 'index'],
    ['GET',  '/users/create',               'UserController', 'create'],
    ['POST', '/users/create',               'UserController', 'store'],
    ['GET',  '/users/{id}/edit',            'UserController', 'edit'],
    ['POST', '/users/{id}/edit',            'UserController', 'update'],
    ['GET',  '/users/{id}/driver-profile',  'UserController', 'driverProfile'],
    ['POST', '/users/{id}/driver-profile',  'UserController', 'updateDriverProfile'],

    // Companies — static before {id}
    ['GET',  '/companies',                      'CompanyController', 'index'],
    ['GET',  '/companies/{id}/departments',     'CompanyController', 'departments'],
    ['POST', '/companies/{id}/departments',     'CompanyController', 'storeDepartment'],

    // Projects — static before {id}
    ['GET',  '/projects',              'ProjectController', 'index'],
    ['GET',  '/projects/create',       'ProjectController', 'create'],
    ['POST', '/projects/create',       'ProjectController', 'store'],
    ['GET',  '/projects/request',      'ProjectController', 'requestForm'],
    ['POST', '/projects/request',      'ProjectController', 'submitRequest'],
    ['GET',  '/projects/{id}/edit',    'ProjectController', 'edit'],
    ['POST', '/projects/{id}/edit',    'ProjectController', 'update'],
    ['GET',  '/projects/{id}/review',  'ProjectController', 'review'],
    ['POST', '/projects/{id}/approve', 'ProjectController', 'approve'],
    ['POST', '/projects/{id}/reject',  'ProjectController', 'reject'],

    // Reports
    ['GET',  '/reports',                            'ReportController', 'index'],
    ['GET',  '/reports/trip-history',               'ReportController', 'tripHistory'],
    ['GET',  '/reports/maintenance-due',            'ReportController', 'maintenanceDue'],
    ['GET',  '/reports/vehicle-utilization',        'ReportController', 'vehicleUtilization'],
    ['POST', '/reports/trip-history/export',        'ReportController', 'export'],
    ['POST', '/reports/maintenance-due/export',     'ReportController', 'export'],
    ['POST', '/reports/vehicle-utilization/export', 'ReportController', 'export'],

    // GPS feed — JSON endpoint for the admin live map
    ['GET',  '/gps/{id}/feed', 'GpsController', 'feed'],

    // Notifications
    ['GET',  '/notifications',           'NotificationController', 'index'],
    ['GET',  '/notifications/count',     'NotificationController', 'count'],
    ['GET',  '/notifications/{id}/read', 'NotificationController', 'markOneRead'],
    ['POST', '/notifications/read-all',  'NotificationController', 'markAllRead'],

    // Settings
    ['GET',  '/settings', 'SettingsController', 'index'],
    ['POST', '/settings', 'SettingsController', 'update'],
];

// ── Dispatch ─────────────────────────────────────────────────
/**
 * Compile a route pattern containing {id} placeholders into a
 * regex. Static text is escaped; {id} becomes a capturing group
 * matching one or more digits.
 */
function compileRoutePattern(string $routeUri): string
{
    $escaped = preg_quote($routeUri, '#');
    $pattern = str_replace('\{id\}', '(\d+)', $escaped);
    return '#^' . $pattern . '$#';
}

$matched = false;

foreach ($routes as [$routeMethod, $routeUri, $controller, $action]) {
    if ($method !== $routeMethod) {
        continue;
    }

    if (!str_contains($routeUri, '{id}')) {
        if ($uri !== $routeUri) {
            continue;
        }
        $params = [];
    } else {
        $pattern = compileRoutePattern($routeUri);
        if (!preg_match($pattern, $uri, $matches)) {
            continue;
        }
        $params = array_map('intval', array_slice($matches, 1));
    }

    $matched = true;

    $controllerFile = __DIR__ . '/controllers/' . $controller . '.php';
    if (!file_exists($controllerFile)) {
        render_error_page(501, 'Not Implemented', 'This page is not available yet.', $controller . '::' . $action);
        exit;
    }

    require_once $controllerFile;
    $instance = new $controller();

    if (!method_exists($instance, $action)) {
        render_error_page(501, 'Not Implemented', 'This page is not available yet.', $controller . '::' . $action);
        exit;
    }

    $instance->$action(...$params);
    break;
}

if (!$matched) {
    render_error_page(404, 'Not Found', 'The page you were looking for doesn\'t exist.', $uri, showLink: true);
}
