<?php
const GOOGLE_MAPS_API_KEY = 'YOUR_KEY_HERE';

const APP_BASE   = '';            // '' for document root, '/lvms' for XAMPP
const DEBUG      = false;         // true locally, false on Hostinger — shows full error detail vs. log-only
const DB_HOST    = 'localhost';
const DB_NAME    = 'your_db_name';
const DB_USER    = 'your_db_user';
const DB_PASS    = 'your_db_password';
const DB_CHARSET = 'utf8mb4';

// Guards browser access to database/seed_demo.php (no .htaccess on this repo,
// so that file is reachable by URL on Hostinger without this). CLI/cron
// execution never needs it. Generate with: php -r "echo bin2hex(random_bytes(24));"
// Leave undefined (delete this line) to block ALL browser access outright.
const SEED_DEMO_TOKEN = 'your_random_token_here';