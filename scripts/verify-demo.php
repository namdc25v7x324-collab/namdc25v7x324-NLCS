<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(getenv('VALI_DB_HOST') ?: '127.0.0.1', getenv('VALI_DB_USER') ?: 'root', getenv('VALI_DB_PASSWORD') ?: '', getenv('VALI_DB_NAME') ?: 'vali_store', (int)(getenv('VALI_DB_PORT') ?: 3306));
    foreach (['users','products','orders','order_details','settings','banners','content_pages','faqs','contact_messages','subscribers'] as $table) {
        $conn->query("SELECT 1 FROM `$table` LIMIT 1");
    }
    $conn->query("SELECT payment_status,note FROM orders LIMIT 1");
    if (!$conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->num_rows) throw new RuntimeException('No admin account in database.');
    echo "Database ready: 10 tables, admin present.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
