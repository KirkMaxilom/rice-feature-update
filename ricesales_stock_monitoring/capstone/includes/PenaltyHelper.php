<?php
/**
 * PenaltyHelper Class
 * Manages daily penalties for overdue accounts (payable & receivable)
 * 
 * Features:
 * - Automatic penalty calculation for overdue accounts
 * - Prevents duplicate penalty records
 * - Tracks penalty waivers
 * - Easy integration with existing queries
 */

class PenaltyHelper {
    private $conn;
    private $penalty_rate = 10.00; // ₱10/day (configurable)
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Set custom penalty rate
     * @param float $rate Daily penalty rate
     */
    public function setPenaltyRate($rate) {
        $this->penalty_rate = (float)$rate;
    }
    
    /**
     * Get current penalty rate
     * @return float
     */
    public function getPenaltyRate() {
        return $this->penalty_rate;
    }
    
    /**
     * Calculate and update penalties for ALL overdue accounts
     * Call this on every page load or via cron job
     * 
     * @return bool Success status
     */
    public function updateAllPenalties() {
        $this->updatePenaltyByType('payable');
        $this->updatePenaltyByType('receivable');
        return true;
    }
    
    /**
     * Update penalties for CUSTOMERS ONLY (Account Receivable)
     * Simplified method for customer-focused penalty system
     * 
     * @return bool Success status
     */
    public function updateCustomerPenalties() {
        return $this->updatePenaltyByType('receivable');
    }
    
    /**
     * Update penalties for specific type (payable or receivable)
     * 
     * @param string $type 'payable' or 'receivable'
     * @return bool Success status
     */
    private function updatePenaltyByType($type) {
        $table = ($type === 'payable') ? 'account_payable' : 'account_receivable';
        $id_field = ($type === 'payable') ? 'ap_id' : 'ar_id';
        
        // Find all overdue accounts with remaining balance
        $query = "
            SELECT 
                {$id_field} as id,
                due_date,
                balance,
                status
            FROM {$table}
            WHERE due_date < CURDATE() 
            AND balance > 0
            AND status NOT IN ('paid', 'cancelled')
        ";
        
        $result = $this->conn->query($query);
        
        if (!$result) {
            error_log("PenaltyHelper Query Error ({$type}): " . $this->conn->error);
            return false;
        }
        
        while ($row = $result->fetch_assoc()) {
            $this->processAccountPenalty(
                $type,
                $row['id'],
                $row['due_date'],
                $row['balance']
            );
        }
        
        return true;
    }
    
    /**
     * Process penalty for a single account
     * 
     * @param string $type 'payable' or 'receivable'
     * @param int $id Reference ID
     * @param string $due_date Due date (Y-m-d format)
     * @param float $balance Current balance
     */
    private function processAccountPenalty($type, $id, $due_date, $balance) {
        $today = date('Y-m-d');
        
        // Calculate days overdue
        $due = new DateTime($due_date);
        $current = new DateTime($today);
        $days_late = $current->diff($due)->days;
        
        // Don't calculate penalty if not yet overdue
        if ($days_late <= 0) {
            return;
        }
        
        // Check if penalty already exists
        $check_query = "
            SELECT * FROM penalties 
            WHERE reference_id = {$id} 
            AND reference_type = '{$type}'
        ";
        
        $check_result = $this->conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing penalty
            $penalty = $check_result->fetch_assoc();
            $this->updateExistingPenalty($penalty, $days_late);
        } else {
            // Create new penalty record
            $this->createNewPenalty($type, $id, $days_late);
        }
    }
    
    /**
     * Update existing penalty record (incremental)
     * 
     * @param array $penalty Current penalty record
     * @param int $days_late Total days overdue
     */
    private function updateExistingPenalty($penalty, $days_late) {
        $today = date('Y-m-d');
        $last_calculated = $penalty['last_calculated'];
        
        // Check if already calculated today
        if ($last_calculated === $today) {
            return; // Already processed today
        }
        
        // Calculate days since last calculation
        $last_date = new DateTime($last_calculated);
        $current = new DateTime($today);
        $days_since_last = $current->diff($last_date)->days;
        
        // Only update if at least 1 day has passed
        if ($days_since_last > 0) {
            $additional_penalty = $days_since_last * $this->penalty_rate;
            $new_total = $penalty['penalty_amount'] + $additional_penalty;
            
            $update_query = "
                UPDATE penalties SET
                    penalty_amount = {$new_total},
                    days_late = {$days_late},
                    last_calculated = '{$today}'
                WHERE penalty_id = {$penalty['penalty_id']}
            ";
            
            if (!$this->conn->query($update_query)) {
                error_log("Penalty Update Error: " . $this->conn->error);
            }
        }
    }
    
    /**
     * Create new penalty record for first-time overdue
     * 
     * @param string $type 'payable' or 'receivable'
     * @param int $id Reference ID
     * @param int $days_late Days overdue
     */
    private function createNewPenalty($type, $id, $days_late) {
        $today = date('Y-m-d');
        $initial_amount = $days_late * $this->penalty_rate;
        
        $insert_query = "
            INSERT INTO penalties 
            (reference_id, reference_type, days_late, penalty_amount, penalty_rate, last_calculated)
            VALUES 
            ({$id}, '{$type}', {$days_late}, {$initial_amount}, {$this->penalty_rate}, '{$today}')
            ON DUPLICATE KEY UPDATE
                days_late = {$days_late},
                penalty_amount = {$initial_amount},
                last_calculated = '{$today}'
        ";
        
        if (!$this->conn->query($insert_query)) {
            error_log("Penalty Insert Error: " . $this->conn->error);
        }
    }
    
    /**
     * Get penalty amount for display
     * 
     * @param string $type 'payable' or 'receivable'
     * @param int $id Reference ID
     * @return float Penalty amount or 0 if none
     */
    public function getPenalty($type, $id) {
        $query = "
            SELECT IFNULL(penalty_amount, 0) as penalty_amount FROM penalties 
            WHERE reference_id = {$id} 
            AND reference_type = '{$type}'
        ";
        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (float)$row['penalty_amount'];
        }
        
        return 0.00;
    }
    
    /**
     * Get complete penalty information
     * 
     * @param string $type 'payable' or 'receivable'
     * @param int $id Reference ID
     * @return array|null Penalty record or null if none
     */
    public function getPenaltyInfo($type, $id) {
        $query = "
            SELECT * FROM penalties 
            WHERE reference_id = {$id} 
            AND reference_type = '{$type}'
        ";
        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Waive penalty (admin only) - sets amount to 0
     * 
     * @param string $type 'payable' or 'receivable'
     * @param int $id Reference ID
     * @param int $waived_by User ID who waived it
     * @param string $reason Reason for waiver
     * @return bool Success status
     */
    public function waivePenalty($type, $id, $waived_by = null, $reason = '') {
        // Update penalty amount to 0
        $update_query = "
            UPDATE penalties SET 
            penalty_amount = 0, 
            days_late = 0
            WHERE reference_id = {$id} 
            AND reference_type = '{$type}'
        ";
        
        if (!$this->conn->query($update_query)) {
            error_log("Penalty Waive Error: " . $this->conn->error);
            return false;
        }
        
        // Log the waiver in penalty_waivers table
        if ($waived_by) {
            $reason_esc = $this->conn->real_escape_string($reason);
            $log_query = "
                INSERT INTO penalty_waivers 
                (reference_type, reference_id, waived_by, reason)
                VALUES ('{$type}', {$id}, {$waived_by}, '{$reason_esc}')
            ";
            
            if (!$this->conn->query($log_query)) {
                error_log("Penalty Waiver Log Error: " . $this->conn->error);
            }
        }
        
        return true;
    }
    
    /**
     * Get penalty statistics
     * 
     * @param string $type 'payable' or 'receivable'
     * @return array Statistics with counts and totals
     */
    public function getPenaltyStatistics($type) {
        $query = "
            SELECT 
                COUNT(*) as total_penalized,
                COUNT(CASE WHEN penalty_amount > 0 THEN 1 END) as active_penalties,
                SUM(CASE WHEN penalty_amount > 0 THEN penalty_amount ELSE 0 END) as total_penalties,
                AVG(CASE WHEN penalty_amount > 0 THEN penalty_amount ELSE NULL END) as avg_penalty
            FROM penalties
            WHERE reference_type = '{$type}'
        ";
        
        $result = $this->conn->query($query);
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Clear all penalties (use with caution - admin only)
     * 
     * @param string $type 'payable' or 'receivable'
     * @return bool Success status
     */
    public function clearPenalties($type) {
        $delete_query = "
            DELETE FROM penalties 
            WHERE reference_type = '{$type}' 
            AND penalty_amount = 0
        ";
        
        if (!$this->conn->query($delete_query)) {
            error_log("Penalty Clear Error: " . $this->conn->error);
            return false;
        }
        
        return true;
    }
}
?>
