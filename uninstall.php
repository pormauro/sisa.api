<?php
/**
 * uninstall.php
 *
 * Script to uninstall (drop) the database tables.
 * It disables foreign key checks, drops all tables,
 * and then re-enables the foreign key checks.
 */

require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database credentials
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'my_db';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // List of tables to drop in order that minimizes FK issues.
    // Se incluye todas las tablas definidas en el instalador:
    $tables = [
        'accounting_closings_history',
        'accounting_closings',
        'cash_boxes_history',
        'cash_boxes',
        'appointments_history',
        'appointments',
        'expenses_history',
        'expenses',
        'sales_history',
        'sales',
        'jobs_history',
        'jobs',
        'folders_history',
        'folders',
        'products_services_history',
        'products_services',
        'clients_history',
        'clients',
        'user_configurations_history',
        'user_configurations',
        'user_profile_history',
        'user_profile',
        'notifications',
        'activity_log',
        'files',
        'permissions',
        'users'
    ];

    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS `$table`;";
        $pdo->exec($sql);
        echo "Table '$table' dropped.<br/>";
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<br/>Uninstallation completed successfully.";
} catch (PDOException $e) {
    echo "Error during uninstallation: " . $e->getMessage();
}
?>
