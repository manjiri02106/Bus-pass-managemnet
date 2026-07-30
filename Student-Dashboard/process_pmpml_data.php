<?php
require_once 'config/database.php';

echo "Starting data processing...\n";

// 1. Process KML Files for Stops and Depots
$stops_file = __DIR__ . '/data/pmpml-stops-map.kml';
$depots_file = __DIR__ . '/data/pmpml-depots-map.kml';
$stops_json_file = __DIR__ . '/data/stops.json';

$all_stops = [];

function processKML($filename, &$all_stops) {
    if (!file_exists($filename)) {
        echo "File not found: $filename\n";
        return;
    }
    $kml = file_get_contents($filename);
    
    // We use preg_match_all to find all Placemark blocks to be safer
    preg_match_all('/<Placemark>.*?<\/Placemark>/s', $kml, $placemarks);
    
    $count = 0;
    foreach ($placemarks[0] as $placemark) {
        $name = '';
        $lat = '';
        $lon = '';
        
        // Some KMLs have <name>, others have <SimpleData name="stop_name">
        if (preg_match('/<SimpleData name="(?:stop_name|name)">(.*?)<\/SimpleData>/s', $placemark, $m)) {
            $name = trim($m[1]);
        } elseif (preg_match('/<name>(.*?)<\/name>/s', $placemark, $m)) {
            $name = trim($m[1]);
        }
        
        if (preg_match('/<SimpleData name="(?:stop_lat|lat)">(.*?)<\/SimpleData>/s', $placemark, $m)) {
            $lat = floatval($m[1]);
        }
        if (preg_match('/<SimpleData name="(?:stop_lon|lon)">(.*?)<\/SimpleData>/s', $placemark, $m)) {
            $lon = floatval($m[1]);
        }
        
        // Try coordinates tag if SimpleData lat/lon missing
        if ((!$lat || !$lon) && preg_match('/<coordinates>\s*([\d\.]+),([\d\.]+).*?<\/coordinates>/s', $placemark, $m)) {
            $lon = floatval($m[1]);
            $lat = floatval($m[2]);
        }
        
        if ($name && $lat && $lon) {
            $key = md5($name . $lat . $lon);
            $all_stops[$key] = [
                'name' => $name,
                'lat' => $lat,
                'lon' => $lon
            ];
            $count++;
        }
    }
    echo "Extracted $count locations from " . basename($filename) . "\n";
}

processKML($stops_file, $all_stops);
processKML($depots_file, $all_stops);

// Sort by name
$stops_array = array_values($all_stops);
usort($stops_array, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

file_put_contents($stops_json_file, json_encode($stops_array));
echo "Saved " . count($stops_array) . " stops to data/stops.json\n";

// 2. Process CSV for Routes
$csv_file = __DIR__ . '/data/pmpml-routes-list.csv';
if (!file_exists($csv_file)) {
    die("CSV file not found: $csv_file\n");
}

function calculateBaseFare($distance_km) {
    if ($distance_km <= 5) return 10;
    if ($distance_km <= 10) return 20;
    if ($distance_km <= 15) return 30;
    if ($distance_km <= 20) return 40;
    if ($distance_km <= 25) return 50;
    if ($distance_km <= 30) return 60;
    if ($distance_km <= 40) return 70;
    if ($distance_km <= 50) return 80;
    if ($distance_km <= 60) return 90;
    if ($distance_km <= 70) return 100;
    return 120; // 70-80 km
}

// Clear old routes (this will cascade delete mock bus passes, but user approved replacing mock data)
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0;");
mysqli_query($conn, "TRUNCATE TABLE routes;");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1;");

$handle = fopen($csv_file, "r");
$header = fgetcsv($handle); // Skip header

$stmt = mysqli_prepare($conn, "INSERT INTO routes (route_name, source, destination, distance_km, fare, status) VALUES (?, ?, ?, ?, ?, 'Active')");

$routes_count = 0;
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    if (count($data) < 4) continue;
    
    $route_id = $data[0];
    $description = $data[1]; // e.g. "Hinjawadi Maan Phase 3 To Ma Na Pa"
    $distance = floatval($data[3]);
    
    // Extract source and destination from description
    $source = "";
    $destination = "";
    
    $parts = explode(" To ", $description);
    if (count($parts) >= 2) {
        $source = trim($parts[0]);
        // Handle via points if they exist in destination part
        $dest_parts = explode(" (Vai ", $parts[1]);
        if (count($dest_parts) == 1) {
             $dest_parts = explode(" (via ", $parts[1]);
        }
        $destination = trim($dest_parts[0]);
    } else {
        $source = $description;
        $destination = $description;
    }
    
    $fare = calculateBaseFare($distance);
    $route_name_full = $route_id . " : " . $description;
    
    mysqli_stmt_bind_param($stmt, "sssdd", $route_name_full, $source, $destination, $distance, $fare);
    if (mysqli_stmt_execute($stmt)) {
        $routes_count++;
    }
}
fclose($handle);

echo "Inserted $routes_count routes into database.\n";
echo "Data processing completed successfully.\n";
?>
