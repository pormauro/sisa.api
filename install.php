    <?php
/**
 * install.php
 *
 * Script to install or update the database.
 *
 * - If the "users" table does not exist, it creates it with the complete structure.
 *   It is assumed that if it exists, it already has the required structure.
 *
 * - Creates/verifies the following tables:
 *      users, files, activity_log,
 *      user_profile, user_profile_history, user_configurations, user_configurations_history,
 *      clients, clients_history, products_services, folders, jobs, sales, expenses,
 *      appointments, notifications, cash_boxes, accounting_closings.
 *
 * For historical data (clients_history, user_profile_history, etc.) the foreign key constraints
 * referencing users (or clients, etc.) have been removed (or replaced by indexes)
 * so that, upon deletion of the user, the historical data is preserved.
 *
 * Only in user_profile and user_configurations we keep the FK to users with ON DELETE CASCADE.
 *
 * Images and documents are referenced via the "files" table.
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

    // Helper function to execute SQL in a standardized way
    function executeSQL(PDO $pdo, string $sql, string $successMessage): void {
        try {
            $pdo->exec($sql);
            echo $successMessage . "<br/>";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage() . "<br/>";
        }
    }

    // -------------------------------
    // 1. Table: users
    // -------------------------------
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();
    if (!$tableExists) {
        $createSql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            api_token VARCHAR(255) DEFAULT NULL,
            failed_attempts INT NOT NULL DEFAULT 0,
            locked_until DATETIME DEFAULT NULL,
            password_reset_token VARCHAR(255) DEFAULT NULL,
            password_reset_expires DATETIME DEFAULT NULL,
            activation_token VARCHAR(255) DEFAULT NULL,
            activated TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        executeSQL($pdo, $createSql, "Table 'users' created successfully.");
    } else {
        echo "Table 'users' already exists. Verification completed.<br/>";
    }

    // -------------------------------
    // 20. Table: permissions
    // -------------------------------
    $createPermissionsSql = "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        sector VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (sector)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createPermissionsSql, "Table 'permissions' created/verified.");
    
    // -------------------------------
    // 21. Table: permissions_history
    // -------------------------------
    $createPermissionsHistorySql = "CREATE TABLE IF NOT EXISTS permissions_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        permission_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        sector VARCHAR(100) NOT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (permission_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createPermissionsHistorySql, "Table 'permissions_history' created/verified.");
    
    
    // -------------------------------
    // 2. Table: files
    // -------------------------------
    // En vez de FK a users, solo índice para conservar la info histórica.
    $createFilesSql = "CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        file_data LONGBLOB NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createFilesSql, "Table 'files' created/verified.");

    // -------------------------------
    // 3. Table: activity_log
    // -------------------------------
    // Ya se usa INDEX en lugar de FK para user_id.
    $createActivityLogSql = "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        route VARCHAR(255) NOT NULL,
        method VARCHAR(10) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        action_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status_code INT NOT NULL,
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (route),
        INDEX (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createActivityLogSql, "Table 'activity_log' created/verified.");

    // ===================================================
    // Complementary tables (without is_deleted and edit_id)
    // ===================================================

    // 4. Table: user_profile
    // Se mantiene FK a users para que al eliminar el usuario se elimine el perfil.
    $sql = "CREATE TABLE IF NOT EXISTS user_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        cuit VARCHAR(20) DEFAULT NULL,
        profile_file_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (profile_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'user_profile' created/verified.");

    // 5. Table: user_profile_history
    // Se eliminan las FK y se dejan índices para conservar la información histórica.
    $sql = "CREATE TABLE IF NOT EXISTS user_profile_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        user_id INT NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        cuit VARCHAR(20) DEFAULT NULL,
        profile_file_id INT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (profile_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'user_profile_history' created/verified.");

    // 6. Table: user_configurations
    // Se mantiene FK a users para que al eliminar el usuario se elimine la configuración.
    $sql = "CREATE TABLE IF NOT EXISTS user_configurations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL,
        view_type VARCHAR(50) NOT NULL,
        theme VARCHAR(50) NOT NULL,
        font_size VARCHAR(10) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'user_configurations' created/verified.");

    // 7. Table: user_configurations_history
    // Se eliminan las FK y se dejan índices para conservar el historial.
    $sql = "CREATE TABLE IF NOT EXISTS user_configurations_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        config_id INT NOT NULL,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL,
        view_type VARCHAR(50) NOT NULL,
        theme VARCHAR(50) NOT NULL,
        font_size VARCHAR(10) NOT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (config_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'user_configurations_history' created/verified.");

    // 8. Table: clients
    // Se elimina FK a users; se deja FK a files (ON DELETE SET NULL) y se agrega índice para user_id.
    $sql = "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        business_name VARCHAR(255) NOT NULL,
        tax_id VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        brand_file_id INT DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (brand_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'clients' created/verified.");

    // 9. Table: clients_history
    // Se eliminan las FK para conservar la información histórica; se reemplazan por índices.
    $sql = "CREATE TABLE IF NOT EXISTS clients_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        user_id INT NOT NULL,
        business_name VARCHAR(255) NOT NULL,
        tax_id VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        brand_file_id INT DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (client_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'clients_history' created/verified.");

    /*
     * Para las tablas restantes, se eliminaron las FK que referencian a otras tablas (o se mantienen aquellas con ON DELETE SET NULL)
     * y se utilizan índices para preservar la información histórica sin que la restricción impida la eliminación.
     */

    // 10. Table: products_services
    // Se mantiene FK para product_image_file_id (ON DELETE SET NULL) y se reemplaza FK de user_id por índice.
    // -------------------------------
    // Table: products_services
    // -------------------------------
    $createProductsServicesSql = "CREATE TABLE IF NOT EXISTS products_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        category VARCHAR(100) DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        difficulty VARCHAR(50) DEFAULT NULL,
        item_type ENUM('product','service') NOT NULL,
        product_image_file_id INT DEFAULT NULL,
        stock INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (product_image_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createProductsServicesSql, "Table 'products_services' created/verified.");
    
    // -------------------------------
    // Table: products_services_history
    // -------------------------------
    $createProductsServicesHistorySql = "CREATE TABLE IF NOT EXISTS products_services_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        ps_id INT NOT NULL,
        user_id INT NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        category VARCHAR(100) DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        difficulty VARCHAR(50) DEFAULT NULL,
        item_type ENUM('product','service') DEFAULT NULL,
        product_image_file_id INT DEFAULT NULL,
        stock INT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (ps_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createProductsServicesHistorySql, "Table 'products_services_history' created/verified.");


    // 11. Table: folders
    // Se reemplazan todas las FK por índices, salvo las que sean ON DELETE SET NULL.
    $sql = "CREATE TABLE IF NOT EXISTS folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        parent_id INT DEFAULT NULL,
        folder_image_file_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (client_id),
        INDEX (parent_id),
        FOREIGN KEY (folder_image_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'folders' created/verified.");
    
    // -------------------------------
    // 19. Table: folders_history
    // -------------------------------
    $createFoldersHistorySql = "CREATE TABLE IF NOT EXISTS folders_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        folder_id INT NOT NULL,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        parent_id INT DEFAULT NULL,
        folder_image_file_id INT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (folder_id),
        INDEX (user_id),
        INDEX (client_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createFoldersHistorySql, "Table 'folders_history' created/verified.");


    // -------------------------------
    // Table: jobs
    // -------------------------------
    $createJobsSql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        client_id DEFAULT NULL,
        product_service_id INT DEFAULT NULL,
        folder_id INT DEFAULT NULL,
        type_of_work VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(50) DEFAULT NULL,
        start_datetime DATETIME DEFAULT NULL,
        end_datetime DATETIME DEFAULT NULL,
        multiplicative_value DECIMAL(10,2) DEFAULT 1.00,
        attached_files TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (client_id),
        FOREIGN KEY (product_service_id) REFERENCES products_services(id) ON DELETE SET NULL,
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createJobsSql, "Table 'jobs' created/verified.");
    
    // -------------------------------
    // Table: jobs_history
    // -------------------------------
    $createJobsHistorySql = "CREATE TABLE IF NOT EXISTS jobs_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        user_id INT NOT NULL,
        client_id INT DEFAULT NULL,
        product_service_id INT DEFAULT NULL,
        folder_id INT DEFAULT NULL,
        type_of_work VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(50) DEFAULT NULL,
        start_datetime DATETIME DEFAULT NULL,
        end_datetime DATETIME DEFAULT NULL,
        multiplicative_value DECIMAL(10,2) DEFAULT 1.00,
        attached_files TEXT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (job_id),
        INDEX (user_id),
        INDEX (client_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createJobsHistorySql, "Table 'jobs_history' created/verified.");


    // -------------------------------
    // Table: sales
    // -------------------------------
    $createSalesSql = "CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        product_service_id INT NOT NULL,
        folder_id INT DEFAULT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        attached_files TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (client_id),
        INDEX (product_service_id),
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createSalesSql, "Table 'sales' created/verified.");
    
    // -------------------------------
    // Table: sales_history
    // -------------------------------
    $createSalesHistorySql = "CREATE TABLE IF NOT EXISTS sales_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        product_service_id INT NOT NULL,
        folder_id INT DEFAULT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        attached_files TEXT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (sale_id),
        INDEX (user_id),
        INDEX (client_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createSalesHistorySql, "Table 'sales_history' created/verified.");


    // -------------------------------
    // Table: expenses
    // -------------------------------
    $createExpensesSql = "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        folder_id INT DEFAULT NULL,
        attached_files TEXT DEFAULT NULL,
        expense_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createExpensesSql, "Table 'expenses' created/verified.");
    
    // -------------------------------
    // Table: expenses_history
    // -------------------------------
    $createExpensesHistorySql = "CREATE TABLE IF NOT EXISTS expenses_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        expense_id INT NOT NULL,
        user_id INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        folder_id INT DEFAULT NULL,
        attached_files TEXT DEFAULT NULL,
        expense_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (expense_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createExpensesHistorySql, "Table 'expenses_history' created/verified.");

    
    // -------------------------------
    // Table: appointments
    // -------------------------------
    $createAppointmentsSql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        location VARCHAR(255) DEFAULT NULL,
        site_image_file_id INT DEFAULT NULL,
        attached_files TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (client_id),
        INDEX (job_id),
        FOREIGN KEY (site_image_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createAppointmentsSql, "Table 'appointments' created/verified.");
    
    // -------------------------------
    // Table: appointments_history
    // -------------------------------
    $createAppointmentsHistorySql = "CREATE TABLE IF NOT EXISTS appointments_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        location VARCHAR(255) DEFAULT NULL,
        site_image_file_id INT DEFAULT NULL,
        attached_files TEXT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (appointment_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createAppointmentsHistorySql, "Table 'appointments_history' created/verified.");


    // 16. Table: notifications
    // Se reemplaza la FK de user_id por un índice.
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        action_reference VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        read_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $sql, "Table 'notifications' created/verified.");

    // -------------------------------
    // Table: cash_boxes
    // -------------------------------
    $createCashBoxesSql = "CREATE TABLE IF NOT EXISTS cash_boxes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        image_file_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (image_file_id) REFERENCES files(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createCashBoxesSql, "Table 'cash_boxes' created/verified.");
    
    // -------------------------------
    // Table: cash_boxes_history
    // -------------------------------
    $createCashBoxesHistorySql = "CREATE TABLE IF NOT EXISTS cash_boxes_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        cash_box_id INT NOT NULL,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        image_file_id INT DEFAULT NULL,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        changed_by INT DEFAULT NULL,
        operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
        INDEX (cash_box_id),
        INDEX (user_id),
        INDEX (changed_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createCashBoxesHistorySql, "Table 'cash_boxes_history' created/verified.");

// -------------------------------
// Table: accounting_closings
// -------------------------------
$createAccountingClosingsSql = "CREATE TABLE IF NOT EXISTS accounting_closings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cash_box_id INT NOT NULL,
    closing_date DATETIME NOT NULL,
    final_balance DECIMAL(10,2) NOT NULL,
    total_income DECIMAL(10,2) NOT NULL,
    total_expenses DECIMAL(10,2) NOT NULL,
    comments TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (cash_box_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
executeSQL($pdo, $createAccountingClosingsSql, "Table 'accounting_closings' created/verified.");

// -------------------------------
// Table: accounting_closings_history
// -------------------------------
$createAccountingClosingsHistorySql = "CREATE TABLE IF NOT EXISTS accounting_closings_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    closing_id INT NOT NULL,
    user_id INT NOT NULL,
    cash_box_id INT NOT NULL,
    closing_date DATETIME NOT NULL,
    final_balance DECIMAL(10,2) NOT NULL,
    total_income DECIMAL(10,2) NOT NULL,
    total_expenses DECIMAL(10,2) NOT NULL,
    comments TEXT DEFAULT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    changed_by INT DEFAULT NULL,
    operation_type ENUM('CREATION', 'UPDATE', 'DELETION') NOT NULL,
    INDEX (closing_id),
    INDEX (user_id),
    INDEX (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
executeSQL($pdo, $createAccountingClosingsHistorySql, "Table 'accounting_closings_history' created/verified.");

    // -------------------------------
    // Table: statuses
    // -------------------------------
    $createStatusesSql = "CREATE TABLE IF NOT EXISTS statuses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(100) NOT NULL,
        value VARCHAR(100) NOT NULL,
        background_color VARCHAR(7) NOT NULL,
        order_index INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    executeSQL($pdo, $createStatusesSql, "Table 'statuses' created/verified.");

    // Insertar valores iniciales en 'statuses' solo si la tabla está vacía
    $stmt = $pdo->query("SELECT COUNT(*) FROM statuses");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $statuses = [
            ["label" => "Pendiente",    "value" => "Pendiente",    "background_color" => "#9C27B0", "order_index" => 0],
            ["label" => "En progreso",  "value" => "En progreso",  "background_color" => "#66BB6A", "order_index" => 1],
            ["label" => "En espera",    "value" => "En espera",    "background_color" => "#FBC02D", "order_index" => 2],
            ["label" => "En revisión",  "value" => "En revisión",  "background_color" => "#FFB74D", "order_index" => 3],
            ["label" => "Aprobado",     "value" => "Aprobado",     "background_color" => "#26A69A", "order_index" => 4],
            ["label" => "Completado",   "value" => "Completado",   "background_color" => "#42A5F5", "order_index" => 5],
            ["label" => "Cancelado",    "value" => "Cancelado",    "background_color" => "#EF5350", "order_index" => 6],
            ["label" => "Rechazado",    "value" => "Rechazado",    "background_color" => "#EC407A", "order_index" => 7]
        ];
        $insertSql = "INSERT INTO statuses (label, value, background_color, order_index) 
                      VALUES (:label, :value, :background_color, :order_index)";
        $stmtInsert = $pdo->prepare($insertSql);
        foreach ($statuses as $status) {
            $stmtInsert->execute([
                ':label' => $status['label'],
                ':value' => $status['value'],
                ':background_color' => $status['background_color'],
                ':order_index' => $status['order_index']
            ]);
        }
        echo "Initial statuses inserted successfully.<br/>";
    } else {
        echo "Statuses already seeded.<br/>";
    }

    echo "<br/>Installation/Update completed.";
} catch (PDOException $e) {
    echo "Connection or execution error: " . $e->getMessage();
}
?>
