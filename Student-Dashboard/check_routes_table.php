<?php
require_once 'config/database.php';
echo "<h1>Checking Routes Table</h1>";
$result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM routes");
$row = mysqli_fetch_assoc($result);
echo "<p>Total routes in table: " . $row['count'] . "</p>";

if ($row['count'] > 0) {
    echo "<h2>First 10 Routes:</h2>";
    $result2 = mysqli_query($conn, "SELECT * FROM routes LIMIT 10");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Route Name</th><th>Source</th><th>Destination</th><th>Distance</th><th>Fare</th><th>Status</th></tr>";
    while ($r = mysqli_fetch_assoc($result2)) {
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>" . htmlspecialchars($r['route_name']) . "</td>";
        echo "<td>" . htmlspecialchars($r['source']) . "</td>";
        echo "<td>" . htmlspecialchars($r['destination']) . "</td>";
        echo "<td>{$r['distance_km']}</td>";
        echo "<td>{$r['fare']}</td>";
        echo "<td>{$r['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No routes found! You need to run process_pmpml_data.php!</p>";
}
?>