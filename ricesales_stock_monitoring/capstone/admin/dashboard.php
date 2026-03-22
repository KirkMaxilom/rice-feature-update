<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? '';
include '../config/db.php';

$totalProductsRow = $conn->query("
    SELECT COUNT(*) AS total_products
    FROM products
    WHERE archived=0
")->fetch_assoc();

$totalStockRow = $conn->query("
    SELECT IFNULL(SUM(stock_kg),0) AS total_stock
    FROM products
    WHERE archived=0
")->fetch_assoc();

$flowRow = $conn->query("
    SELECT
        IFNULL(SUM(CASE WHEN type='in' THEN qty_kg ELSE 0 END),0) AS total_in,
        IFNULL(SUM(CASE WHEN type='out' THEN qty_kg ELSE 0 END),0) AS total_out,
        IFNULL(SUM(CASE WHEN type='adjust' THEN qty_kg ELSE 0 END),0) AS total_adjust
    FROM inventory_transactions
")->fetch_assoc();

$inventory = [
    'total_products' => (int)($totalProductsRow['total_products'] ?? 0),
    'total_stock'    => (float)($totalStockRow['total_stock'] ?? 0),
    'total_in'       => (float)($flowRow['total_in'] ?? 0),
    'total_out'      => (float)($flowRow['total_out'] ?? 0),
    'total_adjust'   => (float)($flowRow['total_adjust'] ?? 0),
];

$ar_total = $conn->query("
SELECT 
    IFNULL(SUM(total_amount),0) AS total_ar
FROM account_receivable
")->fetch_assoc();

//ar outstanding
$ar_outstanding = $conn->query("
SELECT 
    IFNULL(SUM(balance),0) AS balance_ar
FROM account_receivable
WHERE LOWER(status) IN ('unpaid','partial','overdue','pending','approved')
")->fetch_assoc();

//ap all records
$ap_total = $conn->query("
SELECT 
    IFNULL(SUM(total_amount),0) AS total_ap
FROM account_payable
")->fetch_assoc();

//ap outstanding
$ap_outstanding = $conn->query("
SELECT 
    IFNULL(SUM(balance),0) AS balance_ap
FROM account_payable
WHERE LOWER(status) IN ('unpaid','partial','overdue','pending','approved')
")->fetch_assoc();

$notif_summary = $conn->query("
SELECT COUNT(*) AS total_sent,
       SUM(CASE WHEN UPPER(status)='SENT' THEN 1 ELSE 0 END) AS successful,
       SUM(CASE WHEN UPPER(status)='FAILED' THEN 1 ELSE 0 END) AS failed
FROM push_notif_logs
")->fetch_assoc();

$recent_logins = $conn->query("
SELECT l.*, u.username
FROM login_logs l
JOIN users u ON l.user_id = u.user_id
ORDER BY l.login_time DESC
LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard | DO HIYS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<link href="../css/layout.css" rel="stylesheet">
</head>

<body>

<?php include '../includes/topnav.php'; ?>

<div class="container-fluid">
<div class="row">


<?php include '../includes/admin_sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="col-lg-10 ms-sm-auto px-4 main-content">

<h3 class="fw-bold mb-4">Dashboard Overview</h3>

<!-- Row 1: Inventory Cards -->
<div class="row g-4">
  <div class="col-md-4">
    <div class="card modern-card bg-gradient-primary text-white p-3">
      <h6>Total Products</h6>
      <h2><?= (int)$inventory['total_products'] ?></h2>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card modern-card bg-gradient-success text-white p-3">
      <h6>Total Stock (kg)</h6>
      <h2><?= number_format((float)$inventory['total_stock'],2) ?></h2>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card modern-card bg-gradient-info text-white p-3">
      <h6>Stock Movement</h6>
      <p class="mb-0">
        In: <?= number_format((float)$inventory['total_in'],2) ?> |
        Out: <?= number_format((float)$inventory['total_out'],2) ?>
      </p>
      <small class="opacity-75">Adjust: <?= number_format((float)$inventory['total_adjust'],2) ?></small>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-md-4">
    <div class="card modern-card bg-gradient-warning text-dark p-3">
      <h6>Accounts Receivable (AR)</h6>
      <p class="mb-0">Total: ₱<?= number_format((float)($ar_total['total_ar'] ?? 0),2) ?></p>
      <p class="mb-0">Outstanding: ₱<?= number_format((float)($ar_outstanding['balance_ar'] ?? 0),2) ?></p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card modern-card bg-gradient-danger text-white p-3">
      <h6>Accounts Payable (AP)</h6>
      <p class="mb-0">Total: ₱<?= number_format((float)($ap_total['total_ap'] ?? 0),2) ?></p>
      <p class="mb-0">Outstanding: ₱<?= number_format((float)($ap_outstanding['balance_ap'] ?? 0),2) ?></p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card modern-card bg-gradient-info text-white p-3">
      <h6>Notifications</h6>
      <p class="mb-0">Total: <?= (int)($notif_summary['total_sent'] ?? 0) ?></p>
      <p class="mb-0">
        Success: <?= (int)($notif_summary['successful'] ?? 0) ?> |
        Failed: <?= (int)($notif_summary['failed'] ?? 0) ?>
      </p>
    </div>
  </div>
</div>

<div class="card shadow-sm mt-5 modern-card">
  <div class="card-body">
    <h5 class="fw-bold mb-3">Recent Logins</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>User</th>
            <th>Login Time</th>
            <th>Device</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if($recent_logins && $recent_logins->num_rows > 0): ?>
            <?php while($log = $recent_logins->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($log['username']) ?></td>
                <td><?= date("M d, Y h:i A", strtotime($log['login_time'])) ?></td>
                <td><?= htmlspecialchars($log['device_info']) ?></td>
                <td><?= htmlspecialchars($log['ip_address']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-center text-muted">No recent logins found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
