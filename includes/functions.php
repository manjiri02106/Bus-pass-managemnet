<?php
/**
 * Shared Helper Functions
 * Bus Pass Management System
 */

require_once __DIR__ . '/db.php';

// ─────────────────────────────────────────────────────────────
// OUTPUT / SANITISATION
// ─────────────────────────────────────────────────────────────

/**
 * Safely escape a value for HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitise and trim a string input.
 */
function clean_input(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Return a JSON response and exit.
 */
function json_response(bool $success, string $message, array $data = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ─────────────────────────────────────────────────────────────
// CSRF
// ─────────────────────────────────────────────────────────────

/**
 * Generate (or return existing) CSRF token for the session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 */
function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// ─────────────────────────────────────────────────────────────
// APPLICATION FUNCTIONS
// ─────────────────────────────────────────────────────────────

/**
 * Fetch all applications with applicant and route info.
 */
function get_all_applications(array $filters = []): array
{
    $pdo   = get_db();
    $where = ['1=1'];
    $binds = [];

    if (!empty($filters['status'])) {
        $where[] = 'a.status = :status';
        $binds[':status'] = $filters['status'];
    }
    if (!empty($filters['pass_type'])) {
        $where[] = 'a.pass_type = :pass_type';
        $binds[':pass_type'] = $filters['pass_type'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(ap.name LIKE :search OR a.application_number LIKE :search OR ap.email LIKE :search)';
        $binds[':search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(a.created_at) >= :date_from';
        $binds[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(a.created_at) <= :date_to';
        $binds[':date_to'] = $filters['date_to'];
    }

    $sql = "SELECT
                a.*,
                ap.name         AS applicant_name,
                ap.email        AS applicant_email,
                ap.phone        AS applicant_phone,
                ap.category     AS applicant_category,
                r.route_number  AS route_number,
                r.route_name    AS route_name,
                r.source        AS route_source,
                r.destination   AS route_destination,
                au.name         AS reviewed_by_name
            FROM applications a
            JOIN applicants   ap ON a.applicant_id = ap.id
            JOIN bus_routes   r  ON a.route_id     = r.id
            LEFT JOIN admin_users au ON a.reviewed_by = au.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($binds);
    return $stmt->fetchAll();
}

/**
 * Fetch a single application by ID.
 */
function get_application(int $id): array|false
{
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        SELECT
            a.*,
            ap.name          AS applicant_name,
            ap.email         AS applicant_email,
            ap.phone         AS applicant_phone,
            ap.dob           AS applicant_dob,
            ap.gender        AS applicant_gender,
            ap.address       AS applicant_address,
            ap.city          AS applicant_city,
            ap.pincode       AS applicant_pincode,
            ap.category      AS applicant_category,
            ap.id_proof_type AS applicant_id_proof_type,
            ap.id_proof_number AS applicant_id_proof_number,
            r.route_number   AS route_number,
            r.route_name     AS route_name,
            r.source         AS route_source,
            r.destination    AS route_destination,
            r.stops          AS route_stops,
            r.is_active      AS route_is_active,
            r.fare_monthly   AS fare_monthly,
            r.fare_quarterly AS fare_quarterly,
            r.fare_annual    AS fare_annual,
            au.name          AS reviewed_by_name
        FROM applications a
        JOIN applicants   ap ON a.applicant_id = ap.id
        JOIN bus_routes   r  ON a.route_id     = r.id
        LEFT JOIN admin_users au ON a.reviewed_by = au.id
        WHERE a.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

/**
 * Update application status and log the action.
 */
function update_application_status(
    int    $app_id,
    string $new_status,
    int    $admin_id,
    string $notes      = '',
    array  $extra_fields = []
): bool {
    $pdo = get_db();

    // Fetch current status for the log
    $stmt = $pdo->prepare("SELECT status FROM applications WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $app_id]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $old_status = $row['status'];

    // Build SET clause
    $set   = ['status = :new_status', 'reviewed_by = :reviewed_by', 'reviewed_at = NOW()'];
    $binds = [
        ':new_status'  => $new_status,
        ':reviewed_by' => $admin_id,
        ':id'          => $app_id,
    ];

    foreach ($extra_fields as $col => $val) {
        $set[]           = "{$col} = :{$col}";
        $binds[":{$col}"] = $val;
    }

    $sql = "UPDATE applications SET " . implode(', ', $set) . " WHERE id = :id";
    $pdo->prepare($sql)->execute($binds);

    // Audit log
    log_application_action($app_id, 'Status Updated', $old_status, $new_status, $admin_id, $notes);

    return true;
}

/**
 * Insert an audit log entry.
 */
function log_application_action(
    int     $app_id,
    string  $action,
    ?string $old_status,
    ?string $new_status,
    ?int    $performed_by,
    string  $notes = ''
): void {
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO application_logs
            (application_id, action, old_status, new_status, performed_by, notes, ip_address)
        VALUES
            (:app_id, :action, :old_status, :new_status, :performed_by, :notes, :ip)
    ");
    $stmt->execute([
        ':app_id'       => $app_id,
        ':action'       => $action,
        ':old_status'   => $old_status,
        ':new_status'   => $new_status,
        ':performed_by' => $performed_by,
        ':notes'        => $notes,
        ':ip'           => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    ]);
}

/**
 * Fetch all documents for an application.
 */
function get_application_documents(int $app_id): array
{
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        SELECT d.*, au.name AS verified_by_name
        FROM documents d
        LEFT JOIN admin_users au ON d.verified_by = au.id
        WHERE d.application_id = :app_id
        ORDER BY d.doc_type
    ");
    $stmt->execute([':app_id' => $app_id]);
    return $stmt->fetchAll();
}

/**
 * Get counts for dashboard KPI cards.
 */
function get_dashboard_stats(): array
{
    $pdo = get_db();

    $stats = [
        'total'                => 0,
        'pending'              => 0,
        'under_review'         => 0,
        'docs_verified'        => 0,
        'route_validated'      => 0,
        'approved'             => 0,
        'rejected'             => 0,
        'correction_requested' => 0,
        'today_total'          => 0,
        'this_month_approved'  => 0,
    ];

    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM applications
        GROUP BY status
    ");
    foreach ($stmt->fetchAll() as $row) {
        $stats[$row['status']] = (int)$row['cnt'];
        $stats['total']       += (int)$row['cnt'];
    }

    $row = $pdo->query("SELECT COUNT(*) AS cnt FROM applications WHERE DATE(created_at) = CURDATE()")->fetch();
    $stats['today_total'] = (int)$row['cnt'];

    $row = $pdo->query("SELECT COUNT(*) AS cnt FROM applications WHERE status = 'approved' AND MONTH(approved_at) = MONTH(CURDATE()) AND YEAR(approved_at) = YEAR(CURDATE())")->fetch();
    $stats['this_month_approved'] = (int)$row['cnt'];

    return $stats;
}

/**
 * Fetch audit log for an application.
 */
function get_application_logs(int $app_id): array
{
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        SELECT l.*, au.name AS performed_by_name
        FROM application_logs l
        LEFT JOIN admin_users au ON l.performed_by = au.id
        WHERE l.application_id = :app_id
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([':app_id' => $app_id]);
    return $stmt->fetchAll();
}

/**
 * Fetch correction requests for an application.
 */
function get_correction_requests(int $app_id): array
{
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        SELECT cr.*, au.name AS requested_by_name
        FROM correction_requests cr
        LEFT JOIN admin_users au ON cr.requested_by = au.id
        WHERE cr.application_id = :app_id
        ORDER BY cr.created_at DESC
    ");
    $stmt->execute([':app_id' => $app_id]);
    return $stmt->fetchAll();
}

/**
 * Fetch all active bus routes.
 */
function get_all_routes(bool $active_only = true): array
{
    $pdo = get_db();
    $sql = "SELECT * FROM bus_routes" . ($active_only ? " WHERE is_active = 1" : "") . " ORDER BY route_number";
    return $pdo->query($sql)->fetchAll();
}

// ─────────────────────────────────────────────────────────────
// STATUS BADGE + LABEL HELPERS
// ─────────────────────────────────────────────────────────────

function status_badge(string $status): string
{
    $map = [
        'pending'              => ['bg-secondary',  'Pending'],
        'under_review'         => ['bg-info text-dark', 'Under Review'],
        'docs_verified'        => ['bg-primary',    'Docs Verified'],
        'route_validated'      => ['bg-warning text-dark', 'Route Validated'],
        'approved'             => ['bg-success',    'Approved'],
        'rejected'             => ['bg-danger',     'Rejected'],
        'correction_requested' => ['bg-orange text-white', 'Correction Requested'],
    ];
    [$cls, $label] = $map[$status] ?? ['bg-secondary', ucwords(str_replace('_', ' ', $status))];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

function doc_status_badge(string $status): string
{
    return match($status) {
        'verified' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>',
        'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
        default    => '<span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Pending</span>',
    };
}

function pass_type_badge(string $type): string
{
    return match($type) {
        'monthly'   => '<span class="badge bg-info text-dark">Monthly</span>',
        'quarterly' => '<span class="badge bg-primary">Quarterly</span>',
        'annual'    => '<span class="badge bg-success">Annual</span>',
        default     => '<span class="badge bg-secondary">' . e(ucfirst($type)) . '</span>',
    };
}

function category_badge(string $cat): string
{
    $map = [
        'general'            => 'bg-secondary',
        'student'            => 'bg-info text-dark',
        'senior_citizen'     => 'bg-warning text-dark',
        'differently_abled'  => 'bg-purple text-white',
        'employee'           => 'bg-primary',
    ];
    $cls = $map[$cat] ?? 'bg-secondary';
    return '<span class="badge ' . $cls . '">' . e(ucwords(str_replace('_', ' ', $cat))) . '</span>';
}

/**
 * Format bytes to human-readable.
 */
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
    return $bytes . ' B';
}

/**
 * Format a datetime string nicely.
 */
function format_dt(?string $dt, string $format = 'd M Y, h:i A'): string
{
    if (!$dt) return '—';
    return date($format, strtotime($dt));
}

/**
 * Send a stub email notification (replace with PHPMailer for production).
 */
function send_email(string $to, string $subject, string $body): bool
{
    $headers  = "From: no-reply@buspass.gov\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    return @mail($to, $subject, $body, $headers);
}

/**
 * Redirect with a flash message stored in session.
 */
function redirect_with_flash(string $url, string $type, string $message): never
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear the flash message from session.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if the currently logged-in admin has the given role or higher.
 */
function admin_has_role(string $required_role): bool
{
    $hierarchy = ['verifier' => 1, 'admin' => 2, 'super_admin' => 3];
    $current   = $_SESSION['admin_role'] ?? 'verifier';
    return ($hierarchy[$current] ?? 0) >= ($hierarchy[$required_role] ?? 99);
}
