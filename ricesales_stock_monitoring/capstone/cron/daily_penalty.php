<?php
/**
 * Daily Penalty Processing Cron Job
 * 
 * Purpose: Run daily to automatically calculate and update penalties
 * for overdue accounts (both payable and receivable)
 * 
 * Setup (Windows Task Scheduler):
 * schtasks /create /tn "RicePenaltyUpdate" /tr "php C:\xampp\htdocs\capstone_final\ricesales_stock_monitoring\capstone\cron\daily_penalty.php" /sc daily /st 00:01:00
 * 
 * Setup (Linux Cron):
 * 0 1 * * * php /path/to/daily_penalty.php > /path/to/penalty_cron.log 2>&1
 */

// Prevent direct web access
$allowed_hosts = ['127.0.0.1', 'localhost'];
$is_cli = (php_sapi_name() === 'cli');
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_hosts);

if (!$is_cli && !$is_local) {
    http_response_code(403);
    die('Access Denied: This script can only be run via command line or from localhost.');
}

// Load configuration and PenaltyHelper
require_once __DIR__ . '/../config/db.php';

// Initialize logging
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/penalty_cron.log';

/**
 * Log message with timestamp
 */
function log_msg($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_line, FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo $log_line;
    }
}

try {
    log_msg("========== PENALTY PROCESSING STARTED ==========");
    
    // Verify database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }
    
    log_msg("Database connected successfully.");
    
    // Verify PenaltyHelper is loaded
    if (!class_exists('PenaltyHelper')) {
        throw new Exception("PenaltyHelper class not found.");
    }
    
    // Initialize and run penalty updates (CUSTOMERS ONLY)
    $penaltyHelper = new PenaltyHelper($conn);
    
    log_msg("Updating Customer Account Receivable penalties...");
    $penaltyHelper->updateCustomerPenalties();
    log_msg("✓ Customer penalties updated.");
    
    // Get statistics (CUSTOMERS ONLY)
    $stats_ar = $penaltyHelper->getPenaltyStatistics('receivable');
    
    if ($stats_ar) {
        log_msg(sprintf(
            "CUSTOMER PENALTY STATS: %d penalized | %d active | Total: ₱%.2f",
            $stats_ar['total_penalized'],
            $stats_ar['active_penalties'],
            $stats_ar['total_penalties'] ?? 0
        ));
    }
    
    log_msg("========== PENALTY PROCESSING COMPLETED SUCCESSFULLY ==========\n");
    
} catch (Exception $e) {
    log_msg("❌ ERROR: " . $e->getMessage());
    log_msg("========== PENALTY PROCESSING FAILED ==========\n");
    exit(1);
}

// Close connection
if (isset($conn) && $conn) {
    $conn->close();
}

exit(0);
?>
