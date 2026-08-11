-- ============================================================
-- LOGISTICS VEHICLE MANAGEMENT SYSTEM
-- Remix Construction and Trading Corporation
-- Database Schema (MySQL)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- DESIGN NOTES
-- - Vehicles and drivers are shared across all companies
-- - users.company_id on a driver is for HR records only
-- - All trips are round-trip; origin is always the single
--   shared warehouse stored in system_settings
-- - destination on reservations = work site / delivery point
-- - Maintenance interval = every 5,000 km
--   (next_service_km = odometer_at_service + 5000)
-- - gross_weight_kg used for Philippine LTO weight coding
-- - Architecture: monolithic n-tier (PHP/MySQL)
-- - vehicles.preferred_purpose_ids: CSV of trip_purpose_ids
--   this vehicle is suited for. Used by VehicleRecommendationService
--   for purpose_fit scoring (vehicle-level, not category-level).
--   NULL = no preference (neutral 0.5 score).
-- ============================================================


-- ============================================================
-- 1. SYSTEM SETTINGS
-- ============================================================
CREATE TABLE system_settings (
    setting_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL UNIQUE,
    setting_value   TEXT         NOT NULL,
    description     VARCHAR(255),
    updated_by      INT UNSIGNED,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
    ('warehouse_address',    'Insert Warehouse Address Here',        'Fixed dispatch/return point for all trips'),
    ('warehouse_lat',        '0.00000000',                           'Warehouse GPS latitude'),
    ('warehouse_lng',        '0.00000000',                           'Warehouse GPS longitude'),
    ('system_name',          'MoveOps',                              'Application display name'),
    ('company_name',         'Remix Construction and Trading Corporation', 'Primary company name'),
    ('reservation_prefix',   'RES',                                  'Prefix used for reservation codes'),
    ('maintenance_interval_km', '5000',                              'Standard vehicle maintenance interval in km');


-- ============================================================
-- 2. COMPANIES
-- ============================================================
CREATE TABLE companies (
    company_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name    VARCHAR(100) NOT NULL,
    company_code    VARCHAR(20)  NOT NULL UNIQUE,
    address         VARCHAR(255),
    contact_number  VARCHAR(20),
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO companies (company_name, company_code) VALUES
    ('Remix Construction and Trading Corporation', 'REMIX'),
    ('Ideal Home',  'IDEAL'),
    ('TenBuild',    'TENBUILD');


-- ============================================================
-- 3. DEPARTMENTS
-- ============================================================
CREATE TABLE departments (
    department_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    description     VARCHAR(255),
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dept_company FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


-- ============================================================
-- 4. USERS
-- Roles: super_admin | fleet_admin | admin | employee | driver
-- For drivers: company_id = hiring company (record only)
--              department_id = NULL
-- For super_admin: department_id = NULL
-- ============================================================
CREATE TABLE users (
    user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    department_id   INT UNSIGNED,
    role            ENUM('super_admin','fleet_admin','admin','employee','driver') NOT NULL,
    employee_id     VARCHAR(50)  UNIQUE,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone_number    VARCHAR(20),
    profile_photo   VARCHAR(255),
    api_token       VARCHAR(64)  DEFAULT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_company    FOREIGN KEY (company_id)    REFERENCES companies(company_id),
    CONSTRAINT fk_user_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

ALTER TABLE system_settings
    ADD CONSTRAINT fk_settings_updater FOREIGN KEY (updated_by) REFERENCES users(user_id);


-- ============================================================
-- 5. DRIVER PROFILES
-- ============================================================
CREATE TABLE driver_profiles (
    driver_profile_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    license_number      VARCHAR(50)  NOT NULL,
    license_type        VARCHAR(20)  NOT NULL,
    license_expiry      DATE         NOT NULL,
    license_photo       VARCHAR(255),
    restriction_codes   VARCHAR(50),
    status              ENUM('available','on_trip','off_duty','on_leave') NOT NULL DEFAULT 'available',
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dp_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 6. VEHICLE CATEGORIES
-- ============================================================
CREATE TABLE vehicle_categories (
    category_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(50)  NOT NULL UNIQUE,
    description     VARCHAR(255),
    max_passengers  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    max_cargo_kg    DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- 7. VEHICLES
-- Shared fleet — no company_id.
-- gross_weight_kg = GVWR for LTO weight coding compliance.
-- preferred_purpose_ids = CSV of trip_purpose_ids this vehicle
--   is suited for. Read by VehicleRecommendationService for
--   purpose_fit scoring. NULL = neutral 0.5 score.
-- ============================================================
CREATE TABLE vehicles (
    vehicle_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id           INT UNSIGNED NOT NULL,
    plate_number          VARCHAR(20)  NOT NULL UNIQUE,
    brand                 VARCHAR(50)  NOT NULL,
    model                 VARCHAR(50)  NOT NULL,
    year_model            YEAR         NOT NULL,
    color                 VARCHAR(30),
    fuel_type             ENUM('gasoline','diesel','electric','hybrid') NOT NULL DEFAULT 'diesel',
    passenger_capacity    TINYINT UNSIGNED NOT NULL,
    cargo_capacity_kg     DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    current_odometer_km   DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    gross_weight_kg       DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    status                ENUM('available','reserved','on_trip','under_maintenance','retired') NOT NULL DEFAULT 'available',
    remarks               TEXT,
    preferred_purpose_ids VARCHAR(255) DEFAULT NULL
        COMMENT 'CSV of trip_purpose_ids this vehicle is suited for. NULL = no preference (neutral 0.5 score).',
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicle_category FOREIGN KEY (category_id) REFERENCES vehicle_categories(category_id)
) ENGINE=InnoDB;


-- ============================================================
-- 8. VEHICLE MAINTENANCE
-- Standard interval: every 5,000 km
-- next_service_km = odometer_at_service + 5000
-- ============================================================
CREATE TABLE vehicle_maintenance (
    maintenance_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id          INT UNSIGNED NOT NULL,
    recorded_by         INT UNSIGNED NOT NULL,
    maintenance_type    VARCHAR(100) NOT NULL,
    description         TEXT,
    odometer_at_service DECIMAL(10,2),
    service_date        DATE         NOT NULL,
    next_service_date   DATE,
    next_service_km     DECIMAL(10,2),
    cost                DECIMAL(10,2),
    performed_by        VARCHAR(100),
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maint_vehicle  FOREIGN KEY (vehicle_id)  REFERENCES vehicles(vehicle_id),
    CONSTRAINT fk_maint_recorder FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 9. PROJECTS
-- ============================================================
CREATE TABLE projects (
    project_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    created_by      INT UNSIGNED NOT NULL,
    project_name    VARCHAR(150) NOT NULL,
    project_code    VARCHAR(50),
    location        VARCHAR(255),
    location_lat    DECIMAL(10,8),
    location_lng    DECIMAL(11,8),
    start_date      DATE,
    end_date        DATE,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_company FOREIGN KEY (company_id) REFERENCES companies(company_id),
    CONSTRAINT fk_project_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 10. TRIP PURPOSES
-- preferred_category_ids removed — purpose-fit scoring moved
-- to vehicles.preferred_purpose_ids (vehicle-level granularity).
-- ============================================================
CREATE TABLE trip_purposes (
    purpose_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purpose_name     VARCHAR(100) NOT NULL UNIQUE,
    description      VARCHAR(255),
    requires_project TINYINT(1)   NOT NULL DEFAULT 0,
    max_per_project  TINYINT UNSIGNED,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- 11. RESERVATIONS
-- ============================================================
CREATE TABLE reservations (
    reservation_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_code          VARCHAR(30)  NOT NULL UNIQUE,
    requested_by              INT UNSIGNED NOT NULL,
    department_id             INT UNSIGNED NOT NULL,
    purpose_id                INT UNSIGNED NOT NULL,
    project_id                INT UNSIGNED,
    assigned_vehicle_id       INT UNSIGNED,
    assigned_driver_id        INT UNSIGNED,
    reviewed_by               INT UNSIGNED,
    cancelled_by              INT UNSIGNED,
    ai_recommended_vehicle_id INT UNSIGNED,
    destination               VARCHAR(255) NOT NULL,
    destination_lat           DECIMAL(10,8),
    destination_lng           DECIMAL(11,8),
    trip_details              TEXT,
    passenger_count           TINYINT UNSIGNED NOT NULL,
    cargo_weight_kg           DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    cargo_description         VARCHAR(255),
    departure_datetime        DATETIME     NOT NULL,
    return_datetime           DATETIME     NOT NULL,
    status                    ENUM(
                                  'pending',
                                  'approved',
                                  'gatepass_pending',
                                  'rejected',
                                  'cancelled',
                                  'in_progress',
                                  'completed'
                              ) NOT NULL DEFAULT 'pending',
    reviewed_at               TIMESTAMP    NULL,
    rejection_reason          TEXT,
    cancellation_reason       TEXT,
    ai_recommendation_score   DECIMAL(5,2),
    ai_recommendation_notes   TEXT,
    created_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_requester  FOREIGN KEY (requested_by)        REFERENCES users(user_id),
    CONSTRAINT fk_res_department FOREIGN KEY (department_id)       REFERENCES departments(department_id),
    CONSTRAINT fk_res_purpose    FOREIGN KEY (purpose_id)          REFERENCES trip_purposes(purpose_id),
    CONSTRAINT fk_res_project    FOREIGN KEY (project_id)          REFERENCES projects(project_id),
    CONSTRAINT fk_res_vehicle    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(vehicle_id),
    CONSTRAINT fk_res_driver     FOREIGN KEY (assigned_driver_id)  REFERENCES users(user_id),
    CONSTRAINT fk_res_reviewer   FOREIGN KEY (reviewed_by)         REFERENCES users(user_id),
    CONSTRAINT fk_res_canceller  FOREIGN KEY (cancelled_by)        REFERENCES users(user_id),
    CONSTRAINT fk_res_ai_vehicle FOREIGN KEY (ai_recommended_vehicle_id) REFERENCES vehicles(vehicle_id),
    CONSTRAINT chk_res_dates     CHECK (return_datetime > departure_datetime)
) ENGINE=InnoDB;


-- ============================================================
-- 12. GATEPASSES
-- ============================================================
CREATE TABLE gatepasses (
    gatepass_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id   INT UNSIGNED NOT NULL UNIQUE,
    gatepass_code    VARCHAR(30)  NOT NULL UNIQUE COMMENT 'e.g. GP-2026-00001',
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by      INT UNSIGNED NULL,
    reviewed_at      TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gatepass_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    CONSTRAINT fk_gatepass_reviewer    FOREIGN KEY (reviewed_by)    REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 13. AI RECOMMENDATION LOGS
-- Rule-based weighted scoring — not machine learning.
-- Scores per vehicle per reservation run stored for audit.
-- ============================================================
CREATE TABLE ai_recommendation_logs (
    log_id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id      INT UNSIGNED NOT NULL,
    vehicle_id          INT UNSIGNED NOT NULL,
    score               DECIMAL(5,2) NOT NULL,
    capacity_score      DECIMAL(5,2),
    cargo_score         DECIMAL(5,2),
    availability_score  DECIMAL(5,2),
    purpose_fit_score   DECIMAL(5,2),
    maintenance_score   DECIMAL(5,2),
    disqualified        TINYINT(1)   NOT NULL DEFAULT 0,
    disqualify_reason   VARCHAR(255),
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    CONSTRAINT fk_ai_vehicle     FOREIGN KEY (vehicle_id)     REFERENCES vehicles(vehicle_id)
) ENGINE=InnoDB;


-- ============================================================
-- 14. TRIPS
-- ============================================================
CREATE TABLE trips (
    trip_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id      INT UNSIGNED NOT NULL UNIQUE,
    vehicle_id          INT UNSIGNED NOT NULL,
    driver_id           INT UNSIGNED NOT NULL,
    cancelled_by        INT UNSIGNED,
    odometer_start_km   DECIMAL(10,2),
    odometer_end_km     DECIMAL(10,2),
    actual_departure    DATETIME,
    actual_return       DATETIME,
    trip_status         ENUM('pending_start','in_progress','completed','incident','cancelled') NOT NULL DEFAULT 'pending_start',
    employee_notes      TEXT,
    admin_notes         TEXT,
    cancellation_reason TEXT,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trip_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    CONSTRAINT fk_trip_vehicle     FOREIGN KEY (vehicle_id)     REFERENCES vehicles(vehicle_id),
    CONSTRAINT fk_trip_driver      FOREIGN KEY (driver_id)      REFERENCES users(user_id),
    CONSTRAINT fk_trip_canceller   FOREIGN KEY (cancelled_by)   REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 15. GPS TRACKING LOGS
-- BIGINT PK due to high insert volume.
-- Driver uses Android Foreground Service for background GPS.
-- ============================================================
CREATE TABLE gps_tracking_logs (
    log_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id         INT UNSIGNED    NOT NULL,
    latitude        DECIMAL(10,8)   NOT NULL,
    longitude       DECIMAL(11,8)   NOT NULL,
    speed_kph       DECIMAL(6,2),
    heading_degrees SMALLINT UNSIGNED,
    accuracy_meters DECIMAL(8,2),
    logged_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gps_trip_time (trip_id, logged_at),
    CONSTRAINT fk_gps_trip FOREIGN KEY (trip_id) REFERENCES trips(trip_id)
) ENGINE=InnoDB;


-- ============================================================
-- 16. TRIP INCIDENTS
-- ============================================================
CREATE TABLE trip_incidents (
    incident_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id             INT UNSIGNED NOT NULL,
    reported_by         INT UNSIGNED NOT NULL,
    incident_type       ENUM('accident','breakdown','traffic_delay','other') NOT NULL,
    description         TEXT         NOT NULL,
    latitude            DECIMAL(10,8),
    longitude           DECIMAL(11,8),
    occurred_at         DATETIME     NOT NULL,
    resolved            TINYINT(1)   NOT NULL DEFAULT 0,
    resolution_notes    TEXT,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_incident_trip     FOREIGN KEY (trip_id)     REFERENCES trips(trip_id),
    CONSTRAINT fk_incident_reporter FOREIGN KEY (reported_by) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 17. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         TEXT         NOT NULL,
    type            ENUM('reservation','trip','maintenance','system','incident') NOT NULL DEFAULT 'system',
    reference_id    INT UNSIGNED,
    reference_type  VARCHAR(50),
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user_read (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 18. AUDIT LOGS
-- BIGINT PK due to high insert volume.
-- ============================================================
CREATE TABLE audit_logs (
    audit_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED,
    action          VARCHAR(100) NOT NULL,
    table_name      VARCHAR(100),
    record_id       INT UNSIGNED,
    old_values      JSON,
    new_values      JSON,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(255),
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user  (user_id),
    INDEX idx_audit_table (table_name, record_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_res_status        ON reservations (status);
CREATE INDEX idx_res_departure     ON reservations (departure_datetime);
CREATE INDEX idx_res_requested_by  ON reservations (requested_by);
CREATE INDEX idx_res_department    ON reservations (department_id);
CREATE INDEX idx_vehicle_status    ON vehicles (status);
CREATE INDEX idx_trip_status       ON trips (trip_status);
CREATE INDEX idx_maint_next_svc    ON vehicle_maintenance (vehicle_id, next_service_date);
CREATE INDEX idx_driver_status     ON driver_profiles (status);

SET FOREIGN_KEY_CHECKS = 1;
