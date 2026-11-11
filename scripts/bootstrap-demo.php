<?php
/** CLI-only demo installation, with no reset of existing databases. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    foreach (['mysqli', 'mbstring', 'fileinfo'] as $extension) {
        if (!extension_loaded($extension)) throw new RuntimeException("Missing PHP extension: $extension");
    }
    if (in_array('--check-runtime', $argv, true)) { echo "PHP runtime ready.\n"; exit(0); }
    $name = getenv('VALI_DB_NAME') ?: 'vali_store';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new RuntimeException('Invalid database name.');
    $db = new mysqli(getenv('VALI_DB_HOST') ?: '127.0.0.1', getenv('VALI_DB_USER') ?: 'root', getenv('VALI_DB_PASSWORD') ?: '', null, (int)(getenv('VALI_DB_PORT') ?: 3306));
    $s = $db->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=?');
    $s->bind_param('s', $name); $s->execute();
    $tables = array_column($s->get_result()->fetch_all(MYSQLI_ASSOC), 'table_name');
    if (!$tables) {
        $db->close();
        require __DIR__ . '/../setup.php';
    } else {
        foreach (['users','products','orders','order_details'] as $table) {
            if (!in_array($table, $tables, true)) throw new RuntimeException('Database is not empty but lacks core tables. Select another VALI_DB_NAME; existing data was not reset.');
        }
        $db->close();
        require __DIR__ . '/migrate.php';
    }
    // setup.php can exit early (e.g. connection failure); the launcher also
    // performs a separate verification invocation before starting Apache.
} catch (Throwable $e) {
    fwrite(STDERR, "Demo setup failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
