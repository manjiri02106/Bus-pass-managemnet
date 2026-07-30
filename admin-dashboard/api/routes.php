<?php
/**
 * REST API Endpoint - Fetch Routes
 * Returns JSON list of transit routes.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../database/db.php';

try {
    $db = Database::connect();
    $stmt = $db->query("SELECT id, route_code, source, destination, standard_price FROM routes ORDER BY route_code ASC");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($routes),
        'routes' => $routes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
