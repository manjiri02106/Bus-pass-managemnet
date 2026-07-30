<?php
require_once 'config/database.php';
$r = mysqli_query($conn, "SELECT route_name, source, destination FROM routes LIMIT 10");
while($row = mysqli_fetch_assoc($r)) {
    echo $row['route_name'] . ' | SRC: ' . $row['source'] . ' | DST: ' . $row['destination'] . "\n";
}
