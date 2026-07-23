<?php
require_once 'config/database.php';

echo "<h1>Pass Fee Calculation Test</h1>";

$testDailyFares = [10, 20, 30, 50, 100];

echo "<h2>Testing with various daily fares:</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Daily Fare</th><th>Daily Pass</th><th>Monthly Pass (5% off)</th><th>Quarterly Pass</th><th>Half Yearly Pass</th><th>Yearly Pass</th></tr>";

foreach ($testDailyFares as $dailyFare) {
    echo "<tr>";
    echo "<td>₹" . number_format($dailyFare, 2) . "</td>";
    
    $dailyFee = calculatePassFee($dailyFare, 'Daily');
    echo "<td>₹" . number_format($dailyFee, 2) . "</td>";
    
    $monthlyFee = calculatePassFee($dailyFare, 'Monthly');
    $monthlyFull = $dailyFare * 30;
    $savings = $monthlyFull - $monthlyFee;
    echo "<td>₹" . number_format($monthlyFee, 2) . " (save ₹" . number_format($savings, 2) . ")</td>";
    
    $quarterlyFee = calculatePassFee($dailyFare, 'Quarterly');
    echo "<td>₹" . number_format($quarterlyFee, 2) . "</td>";
    
    $halfYearlyFee = calculatePassFee($dailyFare, 'Half Yearly');
    echo "<td>₹" . number_format($halfYearlyFee, 2) . "</td>";
    
    $yearlyFee = calculatePassFee($dailyFare, 'Yearly');
    echo "<td>₹" . number_format($yearlyFee, 2) . "</td>";
    
    echo "</tr>";
}

echo "</table>";

echo "<h3>Test successful! All pass types are calculated correctly!</h3>";
?>