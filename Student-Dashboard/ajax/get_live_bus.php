<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$route_number = trim($_POST['route_number'] ?? '');
$distance_km  = (float)($_POST['distance_km'] ?? 10);

if (!$route_number) {
    echo json_encode(['success' => false, 'error' => 'Route number missing']);
    exit;
}

// Convert route_number (e.g. "100-D" or "100-U") to API route_long_name (e.g. "100DOWN" or "100UP")
$route_api_name = strtoupper($route_number);
if (strpos($route_api_name, '-D') !== false) {
    $route_api_name = str_replace('-D', 'DOWN', $route_api_name);
} elseif (strpos($route_api_name, '-U') !== false) {
    $route_api_name = str_replace('-U', 'UP', $route_api_name);
} elseif (!preg_match('/(DOWN|UP)$/', $route_api_name)) {
    $route_api_name .= 'DOWN';
}

// Call PMPML Schedule API
$url = "https://schedule-api.chartr.in/schedules/pune/" . urlencode($route_api_name);
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: test']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
curl_close($ch);

$schedule_data = json_decode($response, true);

$schedules = [];
if (!empty($schedule_data['data']['schedule'])) {
    $schedules = $schedule_data['data']['schedule'];
}

$now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$current_hi = $now->format('H:i');

$next_bus_time_str = null;
$is_tomorrow = false;

// Search for next departure today
foreach ($schedules as $time_str) {
    if ($time_str >= $current_hi) {
        $next_bus_time_str = $time_str;
        break;
    }
}

// If no more departures today, take first departure tomorrow
if (!$next_bus_time_str && !empty($schedules)) {
    $next_bus_time_str = $schedules[0];
    $is_tomorrow = true;
}

// Fallback if no schedule data available from API
if (!$next_bus_time_str) {
    $next_bus_in = rand(8, 20);
    $dep_dt = (clone $now)->modify("+$next_bus_in minutes");
} else {
    $dep_dt = new DateTime($next_bus_time_str, new DateTimeZone('Asia/Kolkata'));
    if ($is_tomorrow || $dep_dt < $now) {
        $dep_dt->modify('+1 day');
    }
    $diff_seconds = $dep_dt->getTimestamp() - $now->getTimestamp();
    $next_bus_in  = max(1, ceil($diff_seconds / 60));
}

// Calculate travel time and arrival time
$travel_time_mins = max(5, ceil($distance_km * 4)); // 15 km/h avg speed = 4 min/km
$arr_dt = (clone $dep_dt)->modify("+$travel_time_mins minutes");

$departure_formatted = $dep_dt->format('h:i A');
$arrival_formatted   = $arr_dt->format('h:i A');

echo json_encode([
    'success'          => true,
    'route_number'     => $route_number,
    'route_api_name'   => $route_api_name,
    'next_bus_in'      => $next_bus_in,
    'departure'        => $departure_formatted,
    'arrival'          => $arrival_formatted,
    'travel_time_mins' => $travel_time_mins,
    'is_tomorrow'      => $is_tomorrow
]);
?>
