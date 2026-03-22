<?php

session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'admin'){ header("Location: ../login.php"); exit; }

include "../config/db.php";

$username = $_SESSION['username'] ?? 'Admin';
$admin_id = (int)$_SESSION['user_id'];

$success = "";
$error   = "";

// Handle waiver requests
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['waive_penalty'])) {
        $type = strtolower(trim($_POST['type'] ?? ''));
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Admin waiver');
        
        if(!in_array($type, ['payable', 'receivable']) || $id <= 0) {
            $error = "Invalid request parameters.";
        } else {
            global $penaltyHelper;
            if($penaltyHelper->waivePenalty($type, $id, $admin_id, $reason)) {
                $success = "Penalty waived successfully.";
            } else {
                $error = "Failed to waive penalty.";
            }
        }
    }
}

// Filter & Search
$filter = strtolower(trim($_GET['filter'] ?? 'all')); // all, active, waived
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query for penalties (CUSTOMER/AR ONLY)
$where = "1=1";

if($filter === 'active') {
    $where .= " AND p.penalty_amount > 0";
} elseif($filter === 'waived') {
    $where .= " AND p.penalty_amount = 0";
}

// Search in customer names
if($search !== '') {
    $searchLike = $conn->real_escape_string("%$search%");
    $where .= " AND (c.name LIKE '$searchLike' OR c.first_name LIKE '$searchLike' OR c.last_name LIKE '$searchLike')";
}

// Count total (CUSTOMER/AR ONLY)
$countSql = "
    SELECT COUNT(*) as cnt
    FROM penalties p
    INNER JOIN account_receivable ar ON p.reference_id = ar.ar_id AND p.reference_type = 'receivable'
    LEFT JOIN customers c ON ar.customer_id = c.customer_id
    WHERE $where
";

$countRes = $conn->query($countSql);
$totalRows = $countRes ? (int)($countRes->fetch_assoc()['cnt'] ?? 0) : 0;
$totalPages = (int)ceil($totalRows / $perPage);

// Get penalties list (CUSTOMER/AR ONLY)
$listSql = "
    SELECT 
        p.*,
        ar.ar_id, ar.total_amount as ar_amount, ar.balance as ar_balance, ar.due_date as ar_due,
        c.name as customer_name,
        CONCAT(c.first_name, ' ', c.last_name) as customer_fullname
    FROM penalties p
    INNER JOIN account_receivable ar ON p.reference_id = ar.ar_id AND p.reference_type = 'receivable'
    LEFT JOIN customers c ON ar.customer_id = c.customer_id
    WHERE $where
    ORDER BY p.penalty_amount DESC, p.last_calculated DESC
    LIMIT $perPage OFFSET $offset
";

$result = $conn->query($listSql);

// Get statistics
$statsSql = "
    SELECT 
        COUNT(*) as total_penalized,
        COUNT(CASE WHEN penalty_amount > 0 THEN 1 END) as active,
        COUNT(CASE WHEN penalty_amount = 0 THEN 1 END) as waived,
        SUM(CASE WHEN penalty_amount > 0 THEN penalty_amount ELSE 0 END) as total_active_amount,
        AVG(CASE WHEN penalty_amount > 0 THEN penalty_amount ELSE NULL END) as avg_penalty
    FROM penalties
";

$statsRes = $conn->query($statsSql);
$stats = $statsRes ? $statsRes->fetch_assoc() : [];

// Get waivers history
$waiverSql = "
    SELECT pw.*, u.username, u.name
    FROM penalty_waivers pw
    LEFT JOIN users u ON pw.waived_by = u.user_id
    ORDER BY pw.waived_date DESC
    LIMIT 20
";

$waiverRes = $conn->query($waiverSql);

function f($v): float { return (float)($v ?? 0); }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Penalty Management | Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<link href="../css/layout.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
    <span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>
    <div class="ms-auto dropdown">
      <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <?= h($username) ?> <small class="text-muted">(Admin)</small>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row">

<?php include '../includes/admin_sidebar.php'; ?>

<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4"></div>

  <?php if($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
  <?php endif; ?>
  <?php if($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
  <?php endif; ?>

  <!-- Page Title -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h3 class="fw-bold mb-1">Penalty Management</h3>
      <div class="text-muted">Monitor and waive penalties for overdue accounts</div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card card-kpi p-3">
        <h6>Total Penalized</h6>
        <h3><?= (int)($stats['total_penalized'] ?? 0) ?></h3>
        <div class="kpi-sub text-muted">Accounts with penalties</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card card-kpi p-3">
        <h6>Active Penalties</h6>
        <h3><?= (int)($stats['active'] ?? 0) ?></h3>
        <div class="kpi-sub text-muted">Outstanding</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card card-kpi p-3">
        <h6>Total Penalty Amount</h6>
        <h3>₱<?= number_format(f($stats['total_active_amount']),2) ?></h3>
        <div class="kpi-sub text-muted">All active penalties</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card card-kpi p-3">
        <h6>Average Penalty</h6>
        <h3>₱<?= number_format(f($stats['avg_penalty']),2) ?></h3>
        <div class="kpi-sub text-muted">Per account</div>
      </div>
    </div>
  </div>

  <!-- Filters & Search -->
  <div class="card modern-card mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET">
        <div class="col-12 col-md-6">
          <label class="form-label">Status</label>
          <select class="form-select" name="filter" onchange="this.form.submit()">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Penalties</option>
            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Only</option>
            <option value="waived" <?= $filter === 'waived' ? 'selected' : '' ?>>Waived Only</option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Search Customer</label>
          <input type="text" class="form-control" name="q" value="<?= h($search) ?>" placeholder="Customer name...">
        </div>
      </form>
    </div>
  </div>

  <!-- Penalties Table -->
  <div class="card modern-card mb-4">
    <div class="card-body table-responsive">
      <table class="table table-striped table-bordered mb-0">
        <thead class="table-dark">
          <tr>
            <th style="width: 70px;">Reference</th>
            <th>Customer Name</th>
            <th style="width: 110px;">Balance</th>
            <th style="width: 110px;">Days Late</th>
            <th style="width: 120px;">Penalty</th>
            <th style="width: 100px;">Due Date</th>
            <th style="width: 150px;">Last Calc</th>
            <th style="min-width: 200px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <?php
                $ref_id = $row['ar_id'];
                $name = $row['customer_fullname'] ?: $row['customer_name'] ?: 'N/A';
                $balance = f($row['ar_balance']);
                $due_date = $row['ar_due'];
                $days_late = (int)($row['days_late'] ?? 0);
                $penalty = f($row['penalty_amount']);
              ?>
              <tr>
                <td class="fw-bold">#<?= $ref_id ?></td>
                <td><?= h($name) ?></td>
                <td>₱<?= number_format($balance, 2) ?></td>
                <td>
                  <?php if($days_late > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $days_late ?> days</span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($penalty > 0): ?>
                    <span class="badge bg-danger">₱<?= number_format($penalty, 2) ?></span>
                  <?php else: ?>
                    <span class="badge bg-success">Waived</span>
                  <?php endif; ?>
                </td>
                <td><?= $due_date ? date('M d, Y', strtotime($due_date)) : '-' ?></td>
                <td><?= $row['last_calculated'] ? date('M d, Y', strtotime($row['last_calculated'])) : '-' ?></td>
                <td>
                  <?php if($penalty > 0): ?>
                    <button class="btn btn-sm btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#waiverModal<?= $row['penalty_id'] ?>">
                      <i class="fa-solid fa-ban me-1"></i>Waive
                    </button>
                  <?php else: ?>
                    <span class="text-muted small">No action</span>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- WAIVER MODAL -->
              <div class="modal fade" id="waiverModal<?= $row['penalty_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Waive Penalty</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                      <div class="modal-body">
                        <p class="mb-3">
                          <strong><?= h($name ?: 'N/A') ?></strong><br>
                          Penalty: <strong>₱<?= number_format($penalty, 2) ?></strong>
                        </p>
                        <input type="hidden" name="waive_penalty" value="1">
                        <input type="hidden" name="type" value="receivable">
                        <input type="hidden" name="id" value="<?= $ref_id ?>">
                        
                        <div class="mb-3">
                          <label class="form-label">Reason for Waiver</label>
                          <textarea class="form-control" name="reason" rows="3" placeholder="Enter reason for waiving this penalty" required></textarea>
                          <small class="text-muted">This will be recorded in audit log</small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-ban me-1"></i>Confirm Waiver</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No penalties found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="small text-muted">
        Showing <?= min($totalRows, $offset + 1) ?>–<?= min($totalRows, $offset + $perPage) ?> of <?= $totalRows ?> results
      </div>

      <?php if($totalPages > 1): ?>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php
              $base = "penalties.php?filter=".urlencode($filter)."&q=".urlencode($search);
            ?>
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="<?= $base ?>&page=<?= max(1,$page-1) ?>">Prev</a>
            </li>

            <?php
              $start = max(1, $page - 2);
              $end   = min($totalPages, $page + 2);
              for($i=$start; $i<=$end; $i++):
            ?>
              <li class="page-item <?= $i===$page?'active':'' ?>">
                <a class="page-link" href="<?= $base ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
              <a class="page-link" href="<?= $base ?>&page=<?= min($totalPages,$page+1) ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <!-- Waiver History -->
  <div class="card modern-card">
    <div class="card-header">
      <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Recent Waivers</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th>Customer AR #</th>
            <th>Waived By</th>
            <th>Reason</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if($waiverRes && $waiverRes->num_rows > 0): ?>
            <?php while($w = $waiverRes->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= $w['reference_id'] ?></strong></td>
                <td><?= h($w['username'] ?? $w['name'] ?? 'System') ?></td>
                <td><?= h($w['reason'] ?? '-') ?></td>
                <td><?= date('M d, Y H:i', strtotime($w['waived_date'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted py-2">No waivers recorded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
