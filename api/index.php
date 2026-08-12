<?php

/**
 * LVMS REST API Entry Point
 *
 * Handles all /api/* requests from the Android driver app.
 * Authentication: Bearer token in Authorization header.
 * All responses are JSON.
 *
 * Routes:
 *   POST  /api/auth/login
 *   GET   /api/trips
 *   PATCH /api/trips/{id}/start
 *   PATCH /api/trips/{id}/complete
 *   POST  /api/gps
 *   GET   /api/notifications
 *   PATCH /api/notifications/{id}/read
 *   POST  /api/incidents
 */

// ── Headers ──────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Bootstrap ─────────────────────────────────────────────────
// api/ is one level below the project root.
require_once __DIR__ . '/../config/app.php';

// ── Route table ───────────────────────────────────────────────
$routes = [
    ['POST',  '/api/auth/login',          'AuthApiController',     'login'],
    ['GET',   '/api/trips',               'TripApiController',     'index'],
    ['PATCH', '/api/trips/{id}/start',    'TripApiController',     'start'],
    ['PATCH', '/api/trips/{id}/complete', 'TripApiController',     'complete'],
    ['POST',  '/api/gps',                 'GpsApiController',      'store'],
    ['GET',   '/api/notifications',           'NotificationApiController', 'index'],
    ['PATCH', '/api/notifications/{id}/read', 'NotificationApiController', 'markRead'],
    // Driver incident report. Future feature
    // ['POST',  '/api/incidents',           'IncidentApiController', 'store'],
];

// ── Request info ──────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

// Mirror the web router's ?url= query string approach.
// Android app calls: POST api/index.php?url=auth/login
//                    GET  api/index.php?url=trips
//                    PATCH api/index.php?url=trips/5/start
// Prepend /api/ so routes match the table above.
$urlParam = trim($_GET['url'] ?? '', '/');
$uri      = '/api/' . $urlParam;

// ── Auth middleware ───────────────────────────────────────────
// POST /api/auth/login is the only unauthenticated endpoint.
$isLoginRoute = ($method === 'POST' && $uri === '/api/auth/login');

$apiCtx = []; // Shared request context — populated with authed user below

if (!$isLoginRoute) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? apache_request_headers()['Authorization']
        ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $tokenMatch)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid Authorization header']);
        exit;
    }

    $userModel = new UserModel();
    $authUser  = $userModel->findByApiToken(trim($tokenMatch[1]));

    if (!$authUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $apiCtx['user_id'] = (int) $authUser['user_id'];
    $apiCtx['role']    = $authUser['role'];
}

// ── Route matching & dispatch ─────────────────────────────────
foreach ($routes as [$routeMethod, $routePattern, $controllerClass, $action]) {
    if ($method !== $routeMethod) {
        continue;
    }

    // Replace {id} tokens with numeric capture groups
    $regex = '#^' . preg_replace('/\{id\}/', '(\d+)', $routePattern) . '$#';

    if (!preg_match($regex, $uri, $matches)) {
        continue;
    }

    // Collect captured IDs in order
    $params = array_map('intval', array_slice($matches, 1));

    require_once __DIR__ . '/' . $controllerClass . '.php';
    $controller = new $controllerClass($apiCtx);
    $controller->$action(...$params);
    exit;
}

// ── No route matched ──────────────────────────────────────────
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
exit;
