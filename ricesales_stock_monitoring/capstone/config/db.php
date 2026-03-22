<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "rice_inventory";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed");
}

// Load PenaltyHelper for automatic penalty calculations
require_once __DIR__ . '/../includes/PenaltyHelper.php';
global $penaltyHelper;
$penaltyHelper = new PenaltyHelper($conn);

// Update CUSTOMER penalties on every page load (customers only)
// Comment this out if running via cron job only
$penaltyHelper->updateCustomerPenalties();
?>
