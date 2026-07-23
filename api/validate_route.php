<?php
/**
 * API: Validate Route / Pending Counts / Notifications
 * Bus Pass Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Auth Guard
if (empty($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized access.');
}

// Handle GET Actions (Counts and Notifications)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = clean_input($_GET['action'] ?? '');
    $pdo = get_db();

    if ($action === 'pending_count') {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status IN ('pending', 'under_review')");
            $row = $stmt->fetch();
            json_response(true, 'Count retrieved.', ['count' => (int)($row['count'] ?? 0)]);
        } catch (Exception $e) {
            json_response(false, 'Failed to fetch pending counts.');
        }
    }

    if ($action === 'notifications') {
        try {
            // Fetch 5 most recent pending applications or unresolved correction requests
            $stmt = $pdo->query("
                SELECT 'app' as type, id, application_number as ref, created_at, 'New application submitted' as title 
                FROM applications 
                WHERE status = 'pending'
                UNION ALL
                SELECT 'correction' as type, application_id as id, 'Unresolved' as ref, created_at, 'Correction request sent' as title
                FROM correction_requests
                WHERE status = 'open'
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $rows = $stmt->fetchAll();
            
            $notifications = [];
            foreach ($rows as $row) {
                $notifications[] = [
                    'title' => $row['title'],
                    'body' => $row['type'] === 'app' ? "Application {$row['ref']} requires review." : "Correction request is still pending response.",
                    'url' => $row['type'] === 'app' ? "application_detail.php?id={$row['id']}" : "request_corrections.php?id={$row['id']}",
                    'time' => format_dt($row['created_at'])
                ];
            }
            json_response(true, 'Notifications loaded.', ['notifications' => $notifications]);
        } catch (Exception $e) {
            json_response(false, 'Failed to fetch notifications.');
        }
    }

    json_response(false, 'Invalid action parameter.');
}

// Handle POST: Route Validation for specific application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Verification
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        json_response(false, 'Security token validation failed (CSRF).');
    }

    $app_id = (int)($_POST['application_id'] ?? 0);

    if (!$app_id) {
        json_response(false, 'Missing application ID.');
    }

    try {
        $pdo = get_db();
        $pdo->beginTransaction();

        $app = get_application($app_id);
        if (!$app) {
            $pdo->rollBack();
            json_response(false, 'Application not found.');
        }

        // Verify route stops validity
        $stops = json_decode($app['route_stops'] ?? '[]', true) ?: [];
        $boarding_valid  = in_array($app['boarding_stop'],  $stops);
        $alighting_valid = in_array($app['alighting_stop'], $stops);
        $route_active    = (bool)$app['route_is_active'];

        if (!$boarding_valid || !$alighting_valid || !$route_active) {
            $pdo->rollBack();
            json_response(false, 'Cannot validate route. One or more routing check criteria failed.');
        }

        // Check if boarding is different from alighting
        if ($app['boarding_stop'] === $app['alighting_stop']) {
            $pdo->rollBack();
            json_response(false, 'Boarding and alighting stops cannot be the same.');
        }

        // Check if documents are verified
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified FROM documents WHERE application_id = :app_id");
        $stmt->execute([':app_id' => $app_id]);
        $counts = $stmt->fetch();
        $all_docs_ok = $counts && (int)$counts['total'] > 0 && (int)$counts['total'] === (int)$counts['verified'];

        $new_status = 'route_validated';

        // Update application status to route_validated
        $updated = update_application_status(
            $app_id, 
            $new_status, 
            $_SESSION['admin_id'], 
            "Route validation completed. Boarding stop: '{$app['boarding_stop']}', Alighting stop: '{$app['alighting_stop']}' checked on operational route '{$app['route_number']}'."
        );

        if (!$updated) {
            $pdo->rollBack();
            json_response(false, 'Failed to update application status.');
        }

        $pdo->commit();
        json_response(true, 'Route successfully validated for this application.');

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Route Validation API Error: ' . $e->getMessage());
        json_response(false, 'An error occurred during route validation.');
    }
}
