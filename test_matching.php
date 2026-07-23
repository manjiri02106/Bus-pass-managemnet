<?php
/**
 * Test script to debug route matching
 */

// Test data - let's simulate a source and destination
$test_source = "Kothrud Depot";
$test_destination = "Kondhwa Bk";

echo "<h1>Testing Route Matching</h1>";
echo "<p>Source: " . htmlspecialchars($test_source) . "</p>";
echo "<p>Destination: " . htmlspecialchars($test_destination) . "</p>";

// Normalize function
$normalize = function($s) {
    return strtolower(preg_replace('/[^a-zA-Z0-9 ]/', ' ', $s));
};

// Score match function
$scoreMatch = function($desc, $words) {
    $desc_norm = strtolower(preg_replace('/[^a-zA-Z0-9 ]/', ' ', $desc));
    $score = 0;
    foreach ($words as $w) {
        if (strlen($w) >= 3 && strpos($desc_norm, $w) !== false) $score++;
    }
    return $score;
};

$src_words = array_filter(explode(' ', $normalize($test_source)));
$dst_words = array_filter(explode(' ', $normalize($test_destination)));

echo "<p>Source words: " . implode(', ', $src_words) . "</p>";
echo "<p>Destination words: " . implode(', ', $dst_words) . "</p>";
echo "<hr>";

// Now read the pmpml-routes-list.csv and test
$csv_file = __DIR__ . '/data/pmpml-routes-list.csv';
if (!file_exists($csv_file)) {
    die("File not found: $csv_file");
}

$handle = fopen($csv_file, 'r');
$first = true;
$candidates = [];

while (($row = fgetcsv($handle)) !== false) {
    if ($first) { $first = false; continue; }
    if (count($row) < 4) continue;

    $route_id = trim($row[0]);
    $route_desc = trim($row[1]);
    $distance_km = floatval($row[3]);

    // Split into source and destination
    $parts = preg_split('/ To /i', $route_desc, 2);
    $csv_src = isset($parts[0]) ? trim($parts[0]) : $route_desc;
    $csv_dst = isset($parts[1]) ? trim($parts[1]) : '';

    // Remove via from destination
    if (strpos($csv_dst, ' (Vai ') !== false) {
        $csv_dst = substr($csv_dst, 0, strpos($csv_dst, ' (Vai '));
    } elseif (strpos($csv_dst, ' (via ') !== false) {
        $csv_dst = substr($csv_dst, 0, strpos($csv_dst, ' (via '));
    }

    // Calculate scores
    $src_score = $scoreMatch($csv_src, $src_words);
    $dst_score = $scoreMatch($csv_dst, $dst_words);
    $rev_src = $scoreMatch($csv_src, $dst_words);
    $rev_dst = $scoreMatch($csv_dst, $src_words);

    $total = $src_score + $dst_score;
    $rev_total = $rev_src + $rev_dst;
    $best = max($total, $rev_total);

    if ($best >= 1) {
        $candidates[] = [
            'score' => $best,
            'route_id' => $route_id,
            'route_desc' => $route_desc,
            'csv_src' => $csv_src,
            'csv_dst' => $csv_dst
        ];
    }
}
fclose($handle);

// Sort candidates
usort($candidates, function($a, $b) { return $b['score'] - $a['score']; });

echo "<h2>Top 10 Candidates:</h2>";
echo "<ul>";
$top10 = array_slice($candidates, 0, 10);
foreach ($top10 as $c) {
    echo "<li>";
    echo "<strong>Route:</strong> {$c['route_id']} | <strong>Desc:</strong> {$c['route_desc']} | <strong>Score:</strong> {$c['score']}";
    echo "</li>";
}
echo "</ul>";
?>