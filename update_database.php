<?php
/**
 * Update Database to Add Daily Pass Type
 * Run this file once to update your existing bus_pass_db
 */
require_once 'config/database.php';

echo "<h1>Updating Database...</h1>";

// Alter bus_passes table to add 'Daily' to pass_type enum
$alter_query = "ALTER TABLE `bus_passes` 
                MODIFY COLUMN `pass_type` 
                ENUM('Daily','Monthly','Quarterly','Half Yearly','Yearly') 
                NOT NULL DEFAULT 'Monthly'";

if (mysqli_query($conn, $alter_query)) {
    echo "<p style='color:green;'>Successfully updated the bus_passes table! Added 'Daily' pass type.</p>";
} else {
    $error = mysqli_error($conn);
    // Check if the error is because the enum already has 'Daily'
    if (strpos($error, "Duplicate column name") === false && strpos($error, "Duplicate entry") === false) {
        echo "<p style='color:red;'>Error updating table: " . htmlspecialchars($error) . "</p>";
    } else {
        echo "<p style='color:blue;'>The 'Daily' pass type is already in the database!</p>";
    }
}

echo "<p>You can now delete this file if you want.</p>";
?>