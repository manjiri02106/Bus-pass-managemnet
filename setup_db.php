<?php
/**
 * Unified Database Configurator for Bus Pass Management System Modules
 * This script runs MySQL initialization and seeding for all 13 workspace folders.
 */

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $host = isset($_POST['host']) ? $_POST['host'] : 'localhost';
    $user = isset($_POST['user']) ? $_POST['user'] : 'root';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';
    
    $response = ['success' => true, 'log' => []];
    
    function log_message(&$response, $msg, $type = 'info') {
        $response['log'][] = ['message' => $msg, 'type' => $type];
    }
    
    try {
        // Test connection
        $setup_mysqli = @new mysqli($host, $user, $pass);
        if ($setup_mysqli->connect_error) {
            throw new Exception("MySQL Connection failed: " . $setup_mysqli->connect_error);
        }
        log_message($response, "Connected to MySQL successfully.", "success");
        
        // SQL script executor with DROP and DELIMITER support
        function run_sql_file($mysqli_conn, $filepath, $db_name) {
            if (!file_exists($filepath)) {
                throw new Exception("SQL file not found: " . basename($filepath));
            }
            
            // Recreate database fresh to ensure no stale table conflicts
            $mysqli_conn->query("DROP DATABASE IF EXISTS `$db_name`");
            $createDbSql = "CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$mysqli_conn->query($createDbSql)) {
                throw new Exception("Failed to create database $db_name: " . $mysqli_conn->error);
            }
            $mysqli_conn->select_db($db_name);
            
            $sql = file_get_contents($filepath);
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql); // Strip UTF-8 BOM
            
            // Dynamically override any hardcoded database selection/creation statements in the SQL file
            $sql = preg_replace('/CREATE DATABASE (IF NOT EXISTS\s+)?(`[a-zA-Z0-9_-]+`|[a-zA-Z0-9_-]+)/i', "CREATE DATABASE IF NOT EXISTS `$db_name`", $sql);
            $sql = preg_replace('/USE\s+(`[a-zA-Z0-9_-]+`|[a-zA-Z0-9_-]+)/i', "USE `$db_name`", $sql);
            
            $queries = [];
            $current_query = '';
            $delimiter = ';';
            
            $lines = explode("\n", $sql);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                
                // Skip comments
                if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                    continue;
                }
                if ($trimmed === '') {
                    continue;
                }
                
                // DELIMITER handling
                if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
                    $delimiter = trim($matches[1]);
                    continue;
                }
                
                $current_query .= $line . "\n";
                
                if (str_ends_with(trim($current_query), $delimiter)) {
                    $query_to_run = substr(trim($current_query), 0, -strlen($delimiter));
                    if (trim($query_to_run) !== '') {
                        $queries[] = trim($query_to_run);
                    }
                    $current_query = '';
                }
            }
            
            $count = 0;
            foreach ($queries as $q) {
                if (!$mysqli_conn->query($q)) {
                    throw new Exception("DB $db_name Error: " . $mysqli_conn->error . " in query:\n" . substr($q, 0, 150) . "...");
                }
                $count++;
            }
            return $count;
        }
        
        // 1. Setup bus_pass_db
        log_message($response, "Initializing fresh 'bus_pass_db' database...", "info");
        $schema_path = __DIR__ . '/database-design/database/schema.sql';
        $queries_run = run_sql_file($setup_mysqli, $schema_path, 'bus_pass_db');
        log_message($response, "'bus_pass_db' created & seeded successfully. (Executed $queries_run queries)", "success");
        
        // 2. Setup buspass_db (auth module)
        log_message($response, "Initializing fresh 'buspass_db' (Authentication) database...", "info");
        $auth_schema_path = __DIR__ . '/auth/database/buspass_auth.sql';
        $queries_run = run_sql_file($setup_mysqli, $auth_schema_path, 'buspass_db');
        log_message($response, "'buspass_db' created & seeded successfully. (Executed $queries_run queries)", "success");
        
        // 3. Setup bus_pass_system (transport officer module)
        log_message($response, "Initializing fresh 'bus_pass_system' (Transport Officer Module) database...", "info");
        $officer_schema_path = __DIR__ . '/Transport-Officer-Module/database/schema.sql';
        $queries_run = run_sql_file($setup_mysqli, $officer_schema_path, 'bus_pass_system');
        log_message($response, "'bus_pass_system' created & seeded successfully. (Executed $queries_run queries, compiled stored procedure)", "success");
        
        // 4. Setup bus_pass_officer_db (converted SQLite database for Transport Officer Module root)
        log_message($response, "Initializing fresh 'bus_pass_officer_db' (Converted SQLite database for Transport Officer Module) database...", "info");
        $converted_schema_path = __DIR__ . '/Transport-Officer-Module/database_mysql.sql';
        $queries_run = run_sql_file($setup_mysqli, $converted_schema_path, 'bus_pass_officer_db');
        log_message($response, "'bus_pass_officer_db' created successfully. (Executed $queries_run queries)", "success");
        
        // 5. Seed Transport Officer Module's MySQL tables
        log_message($response, "Seeding 'bus_pass_officer_db' with default users and test applications...", "info");
        ob_start();
        $pdo = new PDO("mysql:host=$host;dbname=bus_pass_officer_db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        include __DIR__ . '/Transport-Officer-Module/seed.php';
        $seed_output = ob_get_clean();
        log_message($response, "Seed completed: " . trim(strip_tags($seed_output)), "success");
        
        // 6. Setup bus_route_management_db (converted SQLite database for Bus Route and Bus management)
        log_message($response, "Initializing fresh 'bus_route_management_db' (Converted SQLite database for Route Management)...", "info");
        $setup_mysqli->query("DROP DATABASE IF EXISTS `bus_route_management_db`");
        include __DIR__ . '/Bus-Route-and-Bus-management/db.php';
        log_message($response, "'bus_route_management_db' created & initialized tables successfully.", "success");
        
        // 7. Setup bus_pass_management (for Bus-Pass-Generation-&-Renewal)
        log_message($response, "Initializing fresh 'bus_pass_management' database...", "info");
        $setup_mysqli->query("DROP DATABASE IF EXISTS `bus_pass_management`");
        ob_start();
        include __DIR__ . '/Bus-Pass-Generation-&-Renewal/create_database.php';
        $create_db_out = ob_get_clean();
        log_message($response, "Generation Module DB initialization: " . trim(strip_tags($create_db_out)), "success");
        
        // 8. Setup bus_pass_analytics_db (for Dasboard-and-analytics)
        log_message($response, "Initializing fresh 'bus_pass_analytics_db' database...", "info");
        $setup_mysqli->query("DROP DATABASE IF EXISTS `bus_pass_analytics_db`");
        ob_start();
        include __DIR__ . '/Dasboard-and-analytics/db.php';
        $analytics_db_out = ob_get_clean();
        log_message($response, "Dashboard Module DB (Analytics) initialization complete.", "success");
        
        // 9. Setup student_dashboard_db (for Student-Dashboard)
        log_message($response, "Initializing fresh 'student_dashboard_db' database...", "info");
        $student_schema_path = __DIR__ . '/Student-Dashboard/database/bus_pass_db.sql';
        $queries_run = run_sql_file($setup_mysqli, $student_schema_path, 'student_dashboard_db');
        log_message($response, "'student_dashboard_db' created & routes initialized. (Executed $queries_run queries)", "success");
        
        // Execute add_original_pass_id.sql
        $alter_path = __DIR__ . '/Student-Dashboard/database/add_original_pass_id.sql';
        if (file_exists($alter_path)) {
            log_message($response, "Applying table alterations to 'student_dashboard_db'...", "info");
            $setup_mysqli->select_db('student_dashboard_db');
            $alter_queries = explode(';', file_get_contents($alter_path));
            $alt_count = 0;
            foreach ($alter_queries as $aq) {
                if (trim($aq) !== '') {
                    if (!$setup_mysqli->query($aq)) {
                        throw new Exception("Alteration Error: " . $setup_mysqli->error);
                    }
                    $alt_count++;
                }
            }
            log_message($response, "Applied $alt_count database alterations successfully.", "success");
        }
        
        // Seed default student user
        log_message($response, "Seeding default student user into 'student_dashboard_db'...", "info");
        $setup_mysqli->select_db('student_dashboard_db');
        $student_email = 'student@gmail.com';
        $student_pass = password_hash('student123', PASSWORD_DEFAULT);
        
        $chk_student = $setup_mysqli->query("SELECT id FROM students WHERE email = '$student_email'");
        if ($chk_student->num_rows === 0) {
            $insert_student = "INSERT INTO students (full_name, email, password, phone, college_name, college_id_number, course, year_of_study) 
                               VALUES ('Demo Student', '$student_email', '$student_pass', '1234567890', 'DY Patil College', 'CS-2026-001', 'Computer Science', 3)";
            if ($setup_mysqli->query($insert_student)) {
                log_message($response, "Seeded student login: student@gmail.com / student123", "success");
            } else {
                throw new Exception("Failed to seed student user: " . $setup_mysqli->error);
            }
        }
        
        log_message($response, "All databases successfully configured and populated!", "success");
        
        $setup_mysqli->close();
    } catch (Exception $e) {
        $response['success'] = false;
        log_message($response, "Error during setup: " . $e->getMessage(), "danger");
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup — Bus Pass Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #06b6d4;
            --primary-hover: #0891b2;
            --accent: #10b981;
            --accent-bg: rgba(16, 185, 129, 0.1);
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(6, 182, 212, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.05) 0%, transparent 40%);
        }
        
        .container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow);
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #fff 0%, #a5f3fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        p.subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .alert-box {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fde047;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media(max-width: 580px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
        }
        
        button {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #0891b2 100%);
            border: none;
            border-radius: 14px;
            padding: 1rem;
            color: white;
            font-family: inherit;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(6, 182, 212, 0.4);
        }
        
        button:active {
            transform: translateY(1px);
        }
        
        button:disabled {
            background: rgba(148, 163, 184, 0.2);
            color: rgba(255, 255, 255, 0.3);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }
        
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .terminal-panel {
            background: #090d16;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 1.25rem;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.875rem;
            max-height: 250px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);
        }
        
        .terminal-panel::-webkit-scrollbar {
            width: 8px;
        }
        
        .terminal-panel::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .terminal-panel::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        
        .log-entry {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.4;
            animation: fadeIn 0.15s ease-out;
        }
        
        .log-entry.info { color: #38bdf8; }
        .log-entry.success { color: #34d399; }
        .log-entry.danger { color: #f87171; }
        
        .log-entry::before {
            content: "➔";
            flex-shrink: 0;
        }
        
        .db-status-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        @media(max-width: 480px) {
            .db-status-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .db-badge {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .db-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        
        .db-status {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            background: rgba(148, 163, 184, 0.1);
            color: var(--text-muted);
        }
        
        .db-badge.configured {
            border-color: rgba(16, 185, 129, 0.2);
            background: rgba(16, 185, 129, 0.03);
        }
        
        .db-badge.configured .db-name {
            color: var(--text-main);
        }
        
        .db-badge.configured .db-status {
            background: var(--accent-bg);
            color: var(--accent);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Database Configurator</h1>
        <p class="subtitle">Unified Setup for all Bus Pass Management System modules</p>
    </header>
    
    <div class="alert-box">
        <span>⚠</span>
        <span><strong>Notice:</strong> This setup will drop and recreate the databases listed below to ensure a clean, conflict-free import.</span>
    </div>

    <form id="setup-form">
        <div class="form-grid">
            <div class="form-group">
                <label for="host">MySQL Host</label>
                <input type="text" id="host" name="host" value="localhost" placeholder="e.g. localhost">
            </div>
            <div class="form-group">
                <label for="user">Username</label>
                <input type="text" id="user" name="user" value="root" placeholder="e.g. root">
            </div>
            <div class="form-group">
                <label for="pass">Password</label>
                <input type="password" id="pass" name="pass" value="" placeholder="empty for XAMPP">
            </div>
        </div>
        
        <button type="submit" id="setup-btn">
            <span class="spinner" id="spinner"></span>
            <span id="btn-text">Run Setup</span>
        </button>
    </form>
    
    <div class="db-status-grid">
        <div class="db-badge" id="badge-bus_pass_db">
            <span class="db-name">bus_pass_db</span>
            <span class="db-status" id="status-bus_pass_db">Pending</span>
        </div>
        <div class="db-badge" id="badge-buspass_db">
            <span class="db-name">buspass_db</span>
            <span class="db-status" id="status-buspass_db">Pending</span>
        </div>
        <div class="db-badge" id="badge-bus_pass_system">
            <span class="db-name">bus_pass_system</span>
            <span class="db-status" id="status-bus_pass_system">Pending</span>
        </div>
        <div class="db-badge" id="badge-bus_pass_officer_db">
            <span class="db-name">bus_pass_officer_db</span>
            <span class="db-status" id="status-bus_pass_officer_db">Pending</span>
        </div>
        <div class="db-badge" id="badge-bus_route_management_db">
            <span class="db-name">route_management_db</span>
            <span class="db-status" id="status-bus_route_management_db">Pending</span>
        </div>
        <div class="db-badge" id="badge-bus_pass_management">
            <span class="db-name">bus_pass_management</span>
            <span class="db-status" id="status-bus_pass_management">Pending</span>
        </div>
        <div class="db-badge" id="badge-bus_pass_analytics_db">
            <span class="db-name">bus_pass_analytics_db</span>
            <span class="db-status" id="status-bus_pass_analytics_db">Pending</span>
        </div>
        <div class="db-badge" id="badge-student_dashboard_db">
            <span class="db-name">student_dashboard_db</span>
            <span class="db-status" id="status-student_dashboard_db">Pending</span>
        </div>
    </div>
    
    <div class="terminal-panel" id="terminal">
        <div class="log-entry info">Ready to configure local databases. Click 'Run Setup' above.</div>
    </div>
</div>

<script>
    const form = document.getElementById('setup-form');
    const button = document.getElementById('setup-btn');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btn-text');
    const terminal = document.getElementById('terminal');
    
    // Auto-run if ajax query parameter is present (to facilitate automated script calling)
    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autostart') === '1') {
            form.dispatchEvent(new Event('submit'));
        }
    });

    function logToTerminal(msg, type = 'info') {
        const entry = document.createElement('div');
        entry.className = `log-entry ${type}`;
        entry.textContent = msg;
        terminal.appendChild(entry);
        terminal.scrollTop = terminal.scrollHeight;
    }
    
    function updateBadge(id, success) {
        const badge = document.getElementById(`badge-${id}`);
        const status = document.getElementById(`status-${id}`);
        if (badge && status) {
            if (success) {
                badge.classList.add('configured');
                status.textContent = 'Configured';
            } else {
                badge.classList.remove('configured');
                status.textContent = 'Failed';
            }
        }
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset state
        terminal.innerHTML = '';
        logToTerminal('Starting database setup...', 'info');
        
        button.disabled = true;
        spinner.style.display = 'block';
        btnText.textContent = 'Setting up...';
        
        const formData = new FormData(form);
        
        fetch('setup_db.php?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.log) {
                data.log.forEach(log => {
                    logToTerminal(log.message, log.type);
                    
                    // Update badges dynamically based on messages
                    if (log.message.includes("'bus_pass_db' created & seeded successfully")) {
                        updateBadge('bus_pass_db', true);
                    }
                    if (log.message.includes("'buspass_db' created & seeded successfully")) {
                        updateBadge('buspass_db', true);
                    }
                    if (log.message.includes("'bus_pass_system' created & seeded successfully")) {
                        updateBadge('bus_pass_system', true);
                    }
                    if (log.message.includes("'bus_pass_officer_db' created successfully")) {
                        updateBadge('bus_pass_officer_db', true);
                    }
                    if (log.message.includes("'bus_route_management_db' created & initialized")) {
                        updateBadge('bus_route_management_db', true);
                    }
                    if (log.message.includes("Generation Module DB initialization")) {
                        updateBadge('bus_pass_management', true);
                    }
                    if (log.message.includes("Dashboard Module DB (Analytics) initialization complete")) {
                        updateBadge('bus_pass_analytics_db', true);
                    }
                    if (log.message.includes("'student_dashboard_db' created & routes initialized")) {
                        updateBadge('student_dashboard_db', true);
                    }
                });
            }
            
            if (data.success) {
                logToTerminal('All database migrations and configuration checks completed successfully!', 'success');
                btnText.textContent = 'Success!';
            } else {
                logToTerminal('Database setup failed. Check the error log above.', 'danger');
                btnText.textContent = 'Failed';
                button.disabled = false;
            }
            spinner.style.display = 'none';
        })
        .catch(err => {
            logToTerminal('Connection error: ' + err.message, 'danger');
            btnText.textContent = 'Error';
            spinner.style.display = 'none';
            button.disabled = false;
        });
    });
</script>

</body>
</html>
