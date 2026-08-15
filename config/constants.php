<?php

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_FLEET_ADMIN = 'fleet_admin';
const ROLE_ADMIN       = 'admin';
const ROLE_EMPLOYEE    = 'employee';
const ROLE_DRIVER      = 'driver';

const ROLES = [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_DRIVER];

// Display labels — ucfirst('fleet_admin') renders "Fleet_admin", which is wrong
// everywhere it appears. Use this map in dropdowns, badges and the sidebar.
const ROLE_LABELS = [
    ROLE_SUPER_ADMIN => 'Super Admin',
    ROLE_FLEET_ADMIN => 'Fleet Admin',
    ROLE_ADMIN       => 'Admin',
    ROLE_EMPLOYEE    => 'Employee',
    ROLE_DRIVER      => 'Driver',
];

// Which roles each actor may assign when creating or editing a user.
// super_admin is deliberately absent even from its own row: nobody can
// promote another account to super_admin (or demote their own) through the
// app. A new super_admin is created directly in the database.
const ROLE_ASSIGNABLE = [
    ROLE_SUPER_ADMIN => [ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_DRIVER],
    ROLE_FLEET_ADMIN => [ROLE_EMPLOYEE, ROLE_DRIVER],
    ROLE_ADMIN       => [ROLE_EMPLOYEE],
];

const RES_PENDING          = 'pending';
const RES_APPROVED         = 'approved';
const RES_GATEPASS_PENDING = 'gatepass_pending';
const RES_REJECTED         = 'rejected';
const RES_CANCELLED        = 'cancelled';
const RES_IN_PROGRESS      = 'in_progress';
const RES_COMPLETED        = 'completed';

// Display labels for the Reservations list status filter dropdown.
const RES_STATUS_LABELS = [
    RES_PENDING          => 'Pending',
    RES_APPROVED         => 'Approved',
    RES_GATEPASS_PENDING => 'Gatepass',
    RES_IN_PROGRESS      => 'In Progress',
    RES_COMPLETED        => 'Completed',
    RES_REJECTED         => 'Rejected',
    RES_CANCELLED        => 'Cancelled',
];
// Gate pass exists in the schema only (gatepasses table + the reservation status
// above). No controller, model, route or view yet — built in a later step. When it
// lands, its review/approve/reject guards are super_admin ONLY.

const TRIP_PENDING_START = 'pending_start';
const TRIP_IN_PROGRESS   = 'in_progress';
const TRIP_COMPLETED     = 'completed';
const TRIP_INCIDENT      = 'incident';
const TRIP_CANCELLED     = 'cancelled';

// Display labels for the Trips list status filter dropdown.
const TRIP_STATUS_LABELS = [
    TRIP_PENDING_START => 'Pending Start',
    TRIP_IN_PROGRESS   => 'In Progress',
    TRIP_COMPLETED     => 'Completed',
    TRIP_INCIDENT      => 'Incident',
    TRIP_CANCELLED     => 'Cancelled',
];

// Statuses a trip cannot leave. Guards read this instead of comparing
// to 'completed' by hand, so adding a future terminal status is one edit.
const TRIP_TERMINAL_STATUSES = ['completed', 'cancelled'];

// Statuses during which GPS pings are accepted and the Live Map is shown.
// Tracking deliberately continues through 'incident' — an accident is
// exactly when the vehicle's location matters most — and only stops once
// the trip reaches a terminal status.
const TRIP_TRACKING_STATUSES = ['in_progress', 'incident'];

const PROJ_PENDING   = 'pending';
const PROJ_ACTIVE    = 'active';
const PROJ_COMPLETED = 'completed';
const PROJ_REJECTED  = 'rejected';

// Statuses an admin may set directly on the project edit form. A project
// finishes and a project reopens, so active <-> completed moves freely in
// both directions.
const PROJ_EDITABLE_STATUSES = [PROJ_ACTIVE, PROJ_COMPLETED];

// Rows in these statuses refuse ANY status change through the edit form,
// for every role including super_admin — approve() and reject() are the
// only exits from pending, and rejected is terminal (the requester files
// a new request rather than resubmitting in place).
const PROJ_LOCKED_STATUSES = [PROJ_PENDING, PROJ_REJECTED];

const VEH_AVAILABLE   = 'available';
const VEH_RESERVED    = 'reserved';
const VEH_ON_TRIP     = 'on_trip';
const VEH_MAINTENANCE = 'under_maintenance';
const VEH_RETIRED     = 'retired';

// Vehicles currently tied to a reservation or an active trip — i.e. not
// free to dispatch. Used by dashboard fleet-summary cards to report an
// "assigned" bucket distinct from available/maintenance/retired.
const VEH_ASSIGNED_STATUSES = [VEH_RESERVED, VEH_ON_TRIP];

const DRV_AVAILABLE = 'available';
const DRV_ON_TRIP   = 'on_trip';
const DRV_OFF_DUTY  = 'off_duty';
const DRV_ON_LEAVE  = 'on_leave';

const NOTIF_RESERVATION = 'reservation';
const NOTIF_TRIP        = 'trip';
const NOTIF_MAINTENANCE = 'maintenance';
const NOTIF_SYSTEM      = 'system';
const NOTIF_INCIDENT    = 'incident';

// Display labels for the Notifications list type filter dropdown.
const NOTIF_TYPE_LABELS = [
    NOTIF_RESERVATION => 'Reservation',
    NOTIF_TRIP        => 'Trip',
    NOTIF_MAINTENANCE => 'Maintenance',
    NOTIF_SYSTEM      => 'System',
    NOTIF_INCIDENT    => 'Incident',
];

const MAINTENANCE_INTERVAL_KM = 5000;

// Mirrors the vehicle_maintenance.maintenance_type ENUM (database/migrations/
// 2026_08_15_maintenance_type_enum.sql) — the single source of truth for the
// "Log Maintenance Record" type dropdown and the maintenance report/history
// type filters, so a filter option always matches a value the DB will accept.
const MAINTENANCE_TYPES = [
    'Oil Change',
    'Preventive Maintenance',
    'Brake Inspection',
    'Tire Inspection',
    'Engine Check',
    'Repair',
    'Other',
];

const ROLE_DASHBOARD = [
    ROLE_SUPER_ADMIN => '/dashboard/super_admin',
    ROLE_FLEET_ADMIN => '/dashboard/fleet_admin',
    ROLE_ADMIN       => '/dashboard/admin',
    ROLE_EMPLOYEE    => '/dashboard/employee',
    ROLE_DRIVER      => '/dashboard/driver',
];

// Google Maps JavaScript API key used by live map.
// Required APIs: Maps JavaScript API
require_once __DIR__ . '/secrets.php';
