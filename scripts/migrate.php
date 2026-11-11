<?php
if (PHP_SAPI !== 'cli' && !defined('VALI_SETUP')) {
    http_response_code(403);
    exit('Migration chỉ chạy qua CLI.');
}

require_once __DIR__ . '/../app/database.php';

require __DIR__ . '/../database/migrations/001_cms.php';
require __DIR__ . '/../database/migrations/002_order_snapshots.php';
require __DIR__ . '/../database/migrations/003_payment_demo.php';
