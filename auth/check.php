<?php
require_once __DIR__ . '/config/db.php';
try {
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>