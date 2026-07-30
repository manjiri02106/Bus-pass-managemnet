<?php
require_once dirname(__DIR__) . '/config.php';

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            $this->initializeTables();
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    private function initializeTables() {
        $tables = array(
            "users" => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    first_name VARCHAR(50) NOT NULL,
                    last_name VARCHAR(50) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    phone VARCHAR(15) UNIQUE NOT NULL,
                    address TEXT NOT NULL,
                    city VARCHAR(50) NOT NULL,
                    state VARCHAR(50) NOT NULL,
                    postal_code VARCHAR(10) NOT NULL,
                    id_proof VARCHAR(255),
                    profile_photo VARCHAR(255),
                    user_type ENUM('student', 'employee', 'senior_citizen', 'regular') DEFAULT 'regular',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_phone (phone)
                )
            ",
            "bus_passes" => "
                CREATE TABLE IF NOT EXISTS bus_passes (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    pass_number VARCHAR(20) UNIQUE NOT NULL,
                    qr_code VARCHAR(255),
                    pass_type ENUM('monthly', 'quarterly', 'annual', 'special') DEFAULT 'monthly',
                    issue_date DATE NOT NULL,
                    expiry_date DATE NOT NULL,
                    status ENUM('active', 'expired', 'cancelled', 'suspended') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_pass_number (pass_number),
                    INDEX idx_user_id (user_id),
                    INDEX idx_expiry_date (expiry_date)
                )
            ",
            "renewal_requests" => "
                CREATE TABLE IF NOT EXISTS renewal_requests (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    bus_pass_id INT NOT NULL,
                    user_id INT NOT NULL,
                    request_date DATETIME NOT NULL,
                    current_expiry_date DATE NOT NULL,
                    requested_expiry_date DATE NOT NULL,
                    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
                    rejection_reason TEXT,
                    approved_by INT,
                    approved_at DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_status (status),
                    INDEX idx_user_id (user_id)
                )
            ",
            "pass_transactions" => "
                CREATE TABLE IF NOT EXISTS pass_transactions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    bus_pass_id INT NOT NULL,
                    user_id INT NOT NULL,
                    transaction_type ENUM('issue', 'renewal', 'reprint', 'cancellation') DEFAULT 'issue',
                    amount DECIMAL(10, 2) NOT NULL,
                    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                    transaction_id VARCHAR(100),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_payment_status (payment_status)
                )
            ",
            "pass_downloads" => "
                CREATE TABLE IF NOT EXISTS pass_downloads (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    bus_pass_id INT NOT NULL,
                    download_date DATETIME NOT NULL,
                    download_format ENUM('pdf', 'png', 'jpg') DEFAULT 'pdf',
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id) ON DELETE CASCADE,
                    INDEX idx_bus_pass_id (bus_pass_id)
                )
            ",
            "admin_users" => "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    role ENUM('admin', 'approver', 'operator') DEFAULT 'admin',
                    permissions JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE INDEX idx_user_id (user_id)
                )
            "
        );
        
        foreach ($tables as $table) {
            $this->connection->query($table);
        }
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function close() {
        return $this->connection->close();
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Create global database instance
$db = new Database();
?>
