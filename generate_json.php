<?php
$stops_file = __DIR__ . '/data/pmpml-stops-map.kml';
$depots_file = __DIR__ . '/data/pmpml-depots-map.kml';
$stops_json_file = __DIR__ . '/data/stops.json';
$all_stops = [];

function processKML($filename, &$all_stops) {
    if (!file_exists($filename)) return;
    $kml = file_get_contents($filename);
    preg_match_all('/<Placemark>.*?<\/Placemark>/s', $kml, $placemarks);
    
    foreach ($placemarks[0] as $placemark) {
        $name = ''; $lat = ''; $lon = '';
        if (preg_match('/<SimpleData name="(?:stop_name|name)">(.*?)<\/SimpleData>/s', $placemark, $m)) $name = trim($m[1]);
        elseif (preg_match('/<name>(.*?)<\/name>/s', $placemark, $m)) $name = trim($m[1]);
        
        if (preg_match('/<SimpleData name="(?:stop_lat|lat)">(.*?)<\/SimpleData>/s', $placemark, $m)) $lat = floatval($m[1]);
        if (preg_match('/<SimpleData name="(?:stop_lon|lon)">(.*?)<\/SimpleData>/s', $placemark, $m)) $lon = floatval($m[1]);
        
        if ((!$lat || !$lon) && preg_match('/<coordinates>\s*([\d\.]+),([\d\.]+).*?<\/coordinates>/s', $placemark, $m)) {
            $lon = floatval($m[1]); $lat = floatval($m[2]);
        }
        
        if ($name && $lat && $lon) {
            // Deduplicate by normalized stop name
            $key = strtolower(trim($name));
            if (!isset($all_stops[$key])) {
                $all_stops[$key] = ['name' => trim($name), 'lat' => $lat, 'lon' => $lon];
            }
        }
    }
}
processKML($stops_file, $all_stops);
processKML($depots_file, $all_stops);
$stops_array = array_values($all_stops);
usort($stops_array, function($a, $b) { return strcmp($a['name'], $b['name']); });
file_put_contents($stops_json_file, json_encode($stops_array));
echo "Saved " . count($stops_array) . " unique stops to data/stops.json\n";
?>
