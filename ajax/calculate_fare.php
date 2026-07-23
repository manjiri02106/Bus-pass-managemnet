<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$source_name = $_POST['source_name'] ?? '';
$source_lat = floatval($_POST['source_lat'] ?? 0);
$source_lon = floatval($_POST['source_lon'] ?? 0);

$dest_name = $_POST['dest_name'] ?? '';
$dest_lat = floatval($_POST['dest_lat'] ?? 0);
$dest_lon = floatval($_POST['dest_lon'] ?? 0);

if (!$source_name || !$dest_name) {
    echo json_encode(['success' => false, 'error' => 'Source or Destination missing']);
    exit;
}

// 1. Calculate Distance using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

$distance = calculateDistance($source_lat, $source_lon, $dest_lat, $dest_lon);
// Add 30% to straight-line distance to estimate road distance
$road_distance = round($distance * 1.3, 2);
if ($road_distance < 1) $road_distance = 1; // Minimum 1km

// 2. Calculate Fare from PMPML_Fare_Chart.csv (daily/single-trip fare)
function calculateBaseFare($distance_km) {
    $csv_path = __DIR__ . '/../data/PMPML_Fare_Chart.csv';
    if (!file_exists($csv_path)) {
        // Fallback: basic tier in case file is missing
        $tiers = [
            5 => 10, 10 => 20, 15 => 30, 20 => 40, 25 => 50,
            30 => 60, 40 => 70, 50 => 80, 60 => 90, 70 => 100, 80 => 120
        ];
        foreach ($tiers as $max_km => $fare) {
            if ($distance_km <= $max_km) return $fare;
        }
        return 120;
    }

    $handle = fopen($csv_path, 'r');
    $first = true;
    $daily_fare = 120; // default: highest stage

    while (($row = fgetcsv($handle)) !== false) {
        if ($first) { $first = false; continue; } // skip header
        if (count($row) < 3) continue;

        // Column 1: "0 to 5" or "5.1 to 10" etc.
        $range = trim($row[1]);
        $fare  = (int) trim($row[2]);
        if (empty($range) || $fare === 0) continue;

        // Parse upper bound from range string (e.g. "5.1 to 10" -> 10)
        if (preg_match('/to\s*([\d.]+)/i', $range, $m)) {
            $upper = (float) $m[1];
        } elseif (preg_match('/^0\s*to\s*([\d.]+)/i', $range, $m)) {
            $upper = (float) $m[1];
        } else {
            continue;
        }

        if ($distance_km <= $upper) {
            $daily_fare = $fare;
            break;
        }
        // Keep updating so the last row covers distances beyond the last stage
        $daily_fare = $fare;
    }
    fclose($handle);
    return $daily_fare;
}

/**
 * Calculate bus pass fee from the daily (single-trip) fare using simple day multipliers.
 *   Daily       = daily × 1
 *   Monthly     = daily × 30 × 0.95 (5% discount)
 *   Quarterly   = daily × 90  (3 months)
 *   Half Yearly = daily × 180 (6 months)
 *   Yearly      = daily × 365
 */
function calculatePassFeeFromDaily($daily_fare, $pass_type) {
    $multipliers = [
        'Daily'       => 1,
        'Monthly'     => 30 * 0.95,
        'Quarterly'   => 90,
        'Half Yearly' => 180,
        'Yearly'      => 365,
    ];
    return round($daily_fare * ($multipliers[$pass_type] ?? 30), 2);
}

// Daily single-trip fare from CSV
$base_fare = calculateBaseFare($road_distance);

// Pre-calculate pass fees with discounts (mirrors JS PASS_TYPES discount schedule)
$pass_fees = [
    'Daily'       => $base_fare,                                       // no discount
    'Monthly'     => round($base_fare * 30  * (1 - 5  / 100)),        //  5% off
    'Quarterly'   => round($base_fare * 90  * (1 - 10 / 100)),        // 10% off
    'Half Yearly' => round($base_fare * 180 * (1 - 15 / 100)),        // 15% off
    'Yearly'      => round($base_fare * 365 * (1 - 20 / 100)),        // 20% off
];

// 3. Find matching routes — search the real PMPML CSV first, then fall back to DB
$routes = [];
$csv_routes_file = __DIR__ . '/../data/pmpml-routes-list.csv';

if (file_exists($csv_routes_file)) {
    // Normalize a stop name for loose matching
    $normalize = function($s) {
        return strtolower(preg_replace('/[^a-zA-Z0-9 ]/', ' ', $s));
    };

    $src_words  = array_filter(explode(' ', $normalize($source_name)));
    $dst_words  = array_filter(explode(' ', $normalize($dest_name)));

    // Score a route description against a list of keywords
    $scoreMatch = function($desc, $words) {
        $desc_norm = strtolower(preg_replace('/[^a-zA-Z0-9 ]/', ' ', $desc));
        $score = 0;
        foreach ($words as $w) {
            if (strlen($w) >= 3 && strpos($desc_norm, $w) !== false) $score++;
        }
        return $score;
    };

    $handle = fopen($csv_routes_file, 'r');
    $first   = true;
    $candidates = [];

    while (($row = fgetcsv($handle)) !== false) {
        if ($first) { $first = false; continue; } // skip header
        if (count($row) < 4) continue;

        $route_id   = trim($row[0]);           // e.g. "101-D"
        $route_desc = trim($row[1]);           // e.g. "Kondhwa Bk To Kothrud Depot"
        $distance_km = (float) trim($row[3]);  // route total km

        // The description is "Source To Destination" — split on " To "
        $parts = preg_split('/ To /i', $route_desc, 2);
        $csv_src  = isset($parts[0]) ? trim($parts[0]) : $route_desc;
        $csv_dst  = isset($parts[1]) ? trim($parts[1]) : '';

        // Score how well each half matches the user's chosen stop
        $src_score = $scoreMatch($csv_src, $src_words);
        $dst_score = $scoreMatch($csv_dst, $dst_words);
        $rev_src   = $scoreMatch($csv_src, $dst_words);
        $rev_dst   = $scoreMatch($csv_dst, $src_words);

        $total = $src_score + $dst_score;
        $rev_total = $rev_src + $rev_dst;
        $best = max($total, $rev_total);

        if ($best >= 1) {
            $candidates[] = [
                'score'       => $best,
                'route_id'    => $route_id,
                'route_desc'  => $route_desc,
                'source'      => $csv_src,
                'destination' => $csv_dst,
                'distance_km' => $distance_km,
            ];
        }
    }
    fclose($handle);

    // Deduplicate candidates by route_id and route_desc
    $seen = [];
    $unique_candidates = [];
    foreach ($candidates as $c) {
        $dedup_key = strtolower(trim($c['route_id'])) . '|' . strtolower(trim($c['route_desc']));
        if (!isset($seen[$dedup_key])) {
            $seen[$dedup_key] = true;
            $unique_candidates[] = $c;
        }
    }

    // Sort by score descending, take top 10
    usort($unique_candidates, function($a, $b) { return $b['score'] - $a['score']; });
    $candidates = array_slice($unique_candidates, 0, 10);

    foreach ($candidates as $c) {
        $routes[] = [
            'id'          => 0,                // no DB id for CSV routes
            'route_number'=> $c['route_id'],
            'route_desc'  => $c['route_desc'],
            'source'      => $c['source'],
            'destination' => $c['destination'],
            'distance_km' => $c['distance_km'],
        ];
    }
}

// If CSV returned nothing, fall back to the DB (catches custom/imported routes)
if (empty($routes)) {
    $s_like = "%$source_name%";
    $d_like  = "%$dest_name%";
    $q = "SELECT id, route_name, source, destination FROM routes WHERE status = 'Active'
          AND ((source LIKE ? AND destination LIKE ?) OR (source LIKE ? AND destination LIKE ?)) 
          GROUP BY route_name LIMIT 10";
    $stmt = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($stmt, 'ssss', $s_like, $d_like, $d_like, $s_like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $parts  = explode(' : ', $row['route_name']);
        $routes[] = [
            'id'           => $row['id'],
            'route_number' => $parts[0],
            'route_desc'   => isset($parts[1]) ? $parts[1] : $row['route_name'],
            'source'       => $row['source'],
            'destination'  => $row['destination'],
            'distance_km'  => null,
        ];
    }
}

$formatted_routes = $routes; // already formatted above

// Calculate estimated timing (assuming average speed of 15 km/h in city traffic)
// 15 km/h means 4 minutes per kilometer
$estimated_time_mins = ceil($road_distance * 4);
if ($estimated_time_mins < 5) $estimated_time_mins = 5;

// Estimated next bus arrival (randomized slightly between 5-15 mins for realism, or just fixed)
$next_bus_in = rand(5, 15);
$departure_time = date('h:i A', strtotime("+$next_bus_in minutes"));
$arrival_time = date('h:i A', strtotime("+" . ($next_bus_in + $estimated_time_mins) . " minutes"));

echo json_encode([
    'success'    => true,
    'distance'   => $road_distance,
    'fare'       => $base_fare,         // single-trip (daily) fare from CSV
    'pass_fees'  => $pass_fees,         // pre-calculated pass fees per type
    'routes'     => $formatted_routes,
    'timing'     => [
        'travel_time_mins' => $estimated_time_mins,
        'next_bus_in'      => $next_bus_in,
        'departure'        => $departure_time,
        'arrival'          => $arrival_time
    ]
]);
?>
