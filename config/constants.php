<?php

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_ADMIN       = 'admin';
const ROLE_EMPLOYEE    = 'employee';
const ROLE_DRIVER      = 'driver';

const ROLES = [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_DRIVER];

const RES_PENDING     = 'pending';
const RES_APPROVED    = 'approved';
const RES_REJECTED    = 'rejected';
const RES_CANCELLED   = 'cancelled';
const RES_IN_PROGRESS = 'in_progress';
const RES_COMPLETED   = 'completed';

const TRIP_PENDING_START = 'pending_start';
const TRIP_IN_PROGRESS   = 'in_progress';
const TRIP_COMPLETED     = 'completed';
const TRIP_INCIDENT      = 'incident';

const VEH_AVAILABLE   = 'available';
const VEH_RESERVED    = 'reserved';
const VEH_ON_TRIP     = 'on_trip';
const VEH_MAINTENANCE = 'under_maintenance';
const VEH_RETIRED     = 'retired';

const DRV_AVAILABLE = 'available';
const DRV_ON_TRIP   = 'on_trip';
const DRV_OFF_DUTY  = 'off_duty';
const DRV_ON_LEAVE  = 'on_leave';

const NOTIF_RESERVATION = 'reservation';
const NOTIF_TRIP        = 'trip';
const NOTIF_MAINTENANCE = 'maintenance';
const NOTIF_SYSTEM      = 'system';
const NOTIF_INCIDENT    = 'incident';

const MAINTENANCE_INTERVAL_KM = 5000;

const ROLE_DASHBOARD = [
    ROLE_SUPER_ADMIN => '/dashboard/super_admin',
    ROLE_ADMIN       => '/dashboard/admin',
    ROLE_EMPLOYEE    => '/dashboard/employee',
    ROLE_DRIVER      => '/dashboard/driver',
];

// App base path — no trailing slash
const APP_BASE = '/lvms';
