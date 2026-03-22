<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}
if(strtolower($_SESSION['role'] ?? '') !== 'owner'){
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Owner';
include '../config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$VALID_SALE_STATUSES = "('paid','unpaid')";
$MONTHS_HISTORY = 12;
$FORECAST_MONTHS = 3;

// Total products (not archived)
$totalProductsRow = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE archived=0");
$totalProducts = $totalProductsRow ? (int)$totalProductsRow->fetch_assoc()['cnt'] : 0;

// Total stock (estimated) from inventory_transactions (IN - OUT + ADJUST)
$totalStockRow = $conn->query("
    SELECT IFNULL(SUM(
        CASE
            WHEN LOWER(type)='in' THEN qty_kg
            WHEN LOWER(type)='out' THEN -qty_kg
            WHEN LOWER(type)='adjust' THEN qty_kg
            ELSE 0
        END
    ),0) AS total_stock
    FROM inventory_transactions
");
$totalStock = $totalStockRow ? (float)$totalStockRow->fetch_assoc()['total_stock'] : 0.0;

// Sales today (qty and revenue) — ALIGNED (paid/unpaid + revenue from line_total)
$salesTodayRow = $conn->query("
    SELECT
        IFNULL(SUM(si.qty_kg),0) AS sold_kg,
        IFNULL(SUM(si.line_total),0) AS revenue
    FROM sales s
    LEFT JOIN sales_items si ON s.sale_id = si.sale_id
    WHERE DATE(s.sale_date) = CURDATE()
      AND LOWER(s.status) IN $VALID_SALE_STATUSES
      AND s.sale_date <> '0000-00-00 00:00:00'
");
$salesToday = $salesTodayRow ? $salesTodayRow->fetch_assoc() : ['sold_kg'=>0,'revenue'=>0];

// Sales this month (revenue) — ALIGNED
$salesMonthRow = $conn->query("
    SELECT IFNULL(SUM(si.line_total),0) AS revenue_month
    FROM sales s
    JOIN sales_items si ON s.sale_id = si.sale_id
    WHERE YEAR(s.sale_date) = YEAR(CURDATE())
      AND MONTH(s.sale_date) = MONTH(CURDATE())
      AND LOWER(s.status) IN $VALID_SALE_STATUSES
      AND s.sale_date <> '0000-00-00 00:00:00'
");
$revenueMonth = $salesMonthRow ? (float)$salesMonthRow->fetch_assoc()['revenue_month'] : 0.0;

// Pending returns count
$pendingReturnsRow = $conn->query("SELECT COUNT(*) AS cnt FROM returns WHERE LOWER(status)='pending'");
$pendingReturns = $pendingReturnsRow ? (int)$pendingReturnsRow->fetch_assoc()['cnt'] : 0;

$OVERSTOCK_LIMIT_KG = 1000;

$overCountRow = $conn->query("
  SELECT COUNT(*) AS cnt
  FROM (
    SELECT p.product_id,
      (
        IFNULL(SUM(CASE WHEN LOWER(it.type)='in' THEN it.qty_kg ELSE 0 END),0)
        - IFNULL(SUM(CASE WHEN LOWER(it.type)='out' THEN it.qty_kg ELSE 0 END),0)
        + IFNULL(SUM(CASE WHEN LOWER(it.type)='adjust' THEN it.qty_kg ELSE 0 END),0)
      ) AS stock_kg
    FROM products p
    LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
    WHERE IFNULL(p.archived,0)=0
    GROUP BY p.product_id
  ) x
  WHERE stock_kg >= $OVERSTOCK_LIMIT_KG
");
$overCount = $overCountRow ? (int)$overCountRow->fetch_assoc()['cnt'] : 0;

$overstockList = $conn->query("
  SELECT p.product_id, p.variety, p.grade,
    (
      IFNULL(SUM(CASE WHEN LOWER(it.type)='in' THEN it.qty_kg ELSE 0 END),0)
      - IFNULL(SUM(CASE WHEN LOWER(it.type)='out' THEN it.qty_kg ELSE 0 END),0)
      + IFNULL(SUM(CASE WHEN LOWER(it.type)='adjust' THEN it.qty_kg ELSE 0 END),0)
    ) AS stock_kg
  FROM products p
  LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
  WHERE IFNULL(p.archived,0)=0
  GROUP BY p.product_id
  HAVING stock_kg >= $OVERSTOCK_LIMIT_KG
  ORDER BY stock_kg DESC
  LIMIT 8
");

$arRow = $conn->query("
  SELECT
    IFNULL(SUM(total_amount),0) AS total_ar,
    IFNULL(SUM(balance),0) AS balance_ar
  FROM account_receivable
");
$ar = $arRow ? $arRow->fetch_assoc() : ['total_ar'=>0,'balance_ar'=>0];

$apRow = $conn->query("
  SELECT
    IFNULL(SUM(total_amount),0) AS total_ap,
    IFNULL(SUM(balance),0) AS balance_ap
  FROM account_payable
");
$ap = $apRow ? $apRow->fetch_assoc() : ['total_ap'=>0,'balance_ap'=>0];

$topProductRow = $conn->query("
    SELECT p.variety, p.grade, IFNULL(SUM(si.qty_kg),0) AS total_sold
    FROM sales_items si
    JOIN sales s ON si.sale_id = s.sale_id
    JOIN products p ON si.product_id = p.product_id
    WHERE LOWER(s.status) IN $VALID_SALE_STATUSES
      AND IFNULL(p.archived,0)=0
      AND s.sale_date <> '0000-00-00 00:00:00'
    GROUP BY p.product_id
    ORDER BY total_sold DESC
    LIMIT 1
");
$topProduct = ($topProductRow && $topProductRow->num_rows) ? $topProductRow->fetch_assoc() : null;

$recentInventory = $conn->query("
    SELECT it.created_at, it.product_id, it.qty_kg, it.type, it.reference_type, it.reference_id, it.note,
           p.variety, p.grade
    FROM inventory_transactions it
    LEFT JOIN products p ON it.product_id = p.product_id
    ORDER BY it.created_at DESC
    LIMIT 8
");

$months = [];
$salesData = [];
$monthsY = [];
$monthsM = [];
$monthMap = [];

$monthly = $conn->query("
    SELECT
      DATE_FORMAT(s.sale_date,'%b %Y') AS month_label,
      YEAR(s.sale_date) AS y,
      MONTH(s.sale_date) AS m,
      COALESCE(SUM(si.qty_kg),0) AS total_kg
    FROM sales_items si
    JOIN sales s ON si.sale_id = s.sale_id
    WHERE LOWER(s.status) IN $VALID_SALE_STATUSES
      AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL {$MONTHS_HISTORY} MONTH)
      AND s.sale_date <> '0000-00-00 00:00:00'
    GROUP BY y,m,month_label
    ORDER BY y,m
");
if($monthly){
    while($r = $monthly->fetch_assoc()){
        $y = (int)$r['y'];
        $m = (int)$r['m'];
        $key = sprintf('%04d-%02d', $y, $m);
        $monthMap[$key] = (float)$r['total_kg'];
    }
}

// Build continuous month series (fill missing months with 0)
$start = new DateTime('first day of this month');
$start->modify('-' . max(0, $MONTHS_HISTORY - 1) . ' months');
$end = new DateTime('first day of this month');

for($dt = clone $start; $dt <= $end; $dt->modify('+1 month')){
    $y = (int)$dt->format('Y');
    $m = (int)$dt->format('m');
    $key = sprintf('%04d-%02d', $y, $m);

    $months[] = $dt->format('M Y');
    $monthsY[] = $y;
    $monthsM[] = $m;
    $salesData[] = (float)($monthMap[$key] ?? 0);
}
function nextMonthsLabelsFromLast(array $monthsY, array $monthsM, int $n = 3){
    $labels = [];

    if(empty($monthsY) || empty($monthsM)){
        $dt = new DateTime('first day of this month');
    } else {
        $lastY = (int)end($monthsY);
        $lastM = (int)end($monthsM);
        $dt = new DateTime(sprintf('%04d-%02d-01', $lastY, $lastM));
    }

    for($i=1;$i<=$n;$i++){
        $dt->modify('+1 month');
        $labels[] = $dt->format('M Y');
    }
    return $labels;
}

$forecastLabels = nextMonthsLabelsFromLast($monthsY, $monthsM, (int)$FORECAST_MONTHS);
$forecastValues = [];
$window = 3;

if(count($salesData) >= 3){
    $series = $salesData;
    for($i=0;$i<$FORECAST_MONTHS;$i++){
        $slice = array_slice($series, -$window);
        $avg = array_sum($slice) / max(1,count($slice));
        $forecastValues[] = round($avg, 2);
        $series[] = $avg;
    }
} elseif(count($salesData) > 0){
    $baseline = (float)end($salesData);
    $forecastValues = array_fill(0, $FORECAST_MONTHS, round($baseline,2));
} else {
    $forecastValues = array_fill(0, $FORECAST_MONTHS, 0);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner Dashboard | DO HIYS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="../css/layout.css" rel="stylesheet">

<style>
body{ padding-top: 70px; }

.modern-card{
  border-radius: 16px;
  box-shadow: 0 10px 24px rgba(0,0,0,.10);
  border: 1px solid rgba(0,0,0,.06);
}
.modern-card:hover{
  transform: translateY(-2px);
  transition:.2s ease;
}

.bg-gradient-primary{ background:linear-gradient(135deg,#1e3c72,#2a5298)!important; }
.bg-gradient-success{ background:linear-gradient(135deg,#11998e,#38ef7d)!important; }
.bg-gradient-danger{  background:linear-gradient(135deg,#cb2d3e,#ef473a)!important; }
.bg-gradient-info{    background:linear-gradient(135deg,#2193b0,#6dd5ed)!important; }
.bg-gradient-warning{ background:linear-gradient(135deg,#f7971e,#ffd200)!important; }

.chart-wrap{
  position: relative;
  width: 100%;
  height: 320px;
}
.chart-wrap.sm{ height: 280px; }
.chart-wrap canvas{
  width: 100% !important;
  height: 100% !important;
}

.badge-soft{
  background:#f2f2f2;
  color:#333;
  border:1px solid #e6e6e6;
}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
    <span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>

    <div class="ms-auto dropdown">
      <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
        <?= h($username) ?> <small class="text-muted">(Owner)</small>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row">

<?php include '../includes/owner_sidebar.php'; ?>

<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4">

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h3 class="fw-bold mb-1">Owner Overview</h3>
      <div class="text-muted">Monitoring & decision dashboard</div>
    </div>
    <span class="badge rounded-pill bg-dark px-3 py-2">
      <i class="fa-solid fa-chart-simple me-1"></i> Overview
    </span>
  </div>

  <!-- SUMMARY CARDS -->
  <div class="row g-4">
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-primary text-white p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Total Products</div>
            <div class="display-6 fw-bold"><?= (int)$totalProducts ?></div>
          </div>
          <i class="fas fa-box fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-success text-white p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Estimated Stock</div>
            <div class="display-6 fw-bold"><?= number_format((float)$totalStock,2) ?> <small class="fs-6">kg</small></div>
          </div>
          <i class="fas fa-warehouse fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-danger text-white p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Overstock Items</div>
            <div class="display-6 fw-bold"><?= (int)$overCount ?></div>
            <div class="small opacity-75">Limit: <?= number_format((float)$OVERSTOCK_LIMIT_KG,0) ?> kg</div>
          </div>
          <i class="fas fa-triangle-exclamation fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-info text-white p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Sales Today</div>
            <div class="h3 fw-bold mb-0"><?= number_format((float)($salesToday['sold_kg'] ?? 0),2) ?> kg</div>
            <div class="small opacity-75">₱<?= number_format((float)($salesToday['revenue'] ?? 0),2) ?></div>
          </div>
          <i class="fas fa-cash-register fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-warning text-dark p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Revenue (This Month)</div>
            <div class="h3 fw-bold mb-0">₱<?= number_format((float)$revenueMonth,2) ?></div>
            <div class="small">Paid + Unpaid only</div>
          </div>
          <i class="fas fa-chart-column fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-warning text-dark p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Money to Collect</div>
            <div class="h4 fw-bold mb-0">₱<?= number_format((float)($ar['balance_ar'] ?? 0),2) ?></div>
            <div class="small">Unpaid by customers</div>
            <div class="small text-muted">Total credit: ₱<?= number_format((float)($ar['total_ar'] ?? 0),2) ?></div>
          </div>
          <i class="fas fa-hand-holding-dollar fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-danger text-white p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Money to Pay</div>
            <div class="h4 fw-bold mb-0">₱<?= number_format((float)($ap['balance_ap'] ?? 0),2) ?></div>
            <div class="small opacity-75">Unpaid to suppliers</div>
            <div class="small opacity-75">Total credit: ₱<?= number_format((float)($ap['total_ap'] ?? 0),2) ?></div>
          </div>
          <i class="fas fa-file-invoice-dollar fa-3x opacity-75"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card modern-card bg-gradient-warning text-dark p-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-light">Pending Returns</div>
            <div class="display-6 fw-bold"><?= (int)$pendingReturns ?></div>
            <div class="small">Waiting for approval</div>
          </div>
          <i class="fas fa-rotate-left fa-3x opacity-75"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- CHART ROW -->
  <div class="row g-4 mt-1">
    <div class="col-12 col-xl-7">
      <div class="card modern-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0">Sales Over Time (kg)</h5>
            <span class="text-muted small">Last <?= (int)$MONTHS_HISTORY ?> months</span>
          </div>

          <div class="chart-wrap">
            <canvas id="salesChart"></canvas>
          </div>

          <div class="mt-3 text-muted small">
            <i class="fa-solid fa-info-circle me-1"></i>
            Uses paid+unpaid sales only
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card modern-card mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Top Selling Product</h5>
          <?php if($topProduct): ?>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="h4 mb-0"><?= h($topProduct['variety']) ?>
                  <small class="text-muted">- <?= h($topProduct['grade']) ?></small>
                </div>
                <div class="text-muted">Total Sold: <?= number_format((float)$topProduct['total_sold'],2) ?> kg</div>
              </div>
              <i class="fa-solid fa-trophy fa-2x text-warning"></i>
            </div>
          <?php else: ?>
            <div class="text-muted">No sales data yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card modern-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-bold mb-0">Forecast (SMA)</h5>
            <span class="badge badge-soft">Next <?= (int)$FORECAST_MONTHS ?> months</span>
          </div>

          <div class="chart-wrap sm">
            <canvas id="forecastChart"></canvas>
          </div>

          <div class="mt-3 text-muted small">
            <i class="fa-solid fa-flask me-1"></i>
            Forecast uses Simple Moving Average
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RECENT INVENTORY -->
  <div class="card modern-card mt-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h5 class="fw-bold mb-0">Recent Inventory Movements</h5>
        <a class="btn btn-sm btn-outline-dark" href="inventory_monitoring.php">
          <i class="fa-solid fa-arrow-right me-1"></i> View Full Monitoring
        </a>
      </div>

      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>Date</th>
              <th>Product</th>
              <th>Qty</th>
              <th>Type</th>
              <th>Reference</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
            <?php if($recentInventory && $recentInventory->num_rows > 0): ?>
              <?php while($row = $recentInventory->fetch_assoc()): ?>
                <?php
                  $dt = $row['created_at'] ? date("M d, Y h:i A", strtotime($row['created_at'])) : '';
                  $prod = trim(($row['variety'] ?? 'N/A') . " - " . ($row['grade'] ?? ''));
                  $t = strtolower(trim($row['type'] ?? ''));
                  $qtyNum = (float)($row['qty_kg'] ?? 0);

                  if($t === 'adjust'){
                      $sign = ($qtyNum >= 0) ? '+' : '-';
                      $qty = $sign . number_format(abs($qtyNum),2) . " kg";
                  } else {
                      $sign = ($t === 'in') ? '+' : (($t === 'out') ? '-' : '');
                      $qty = $sign . number_format(abs($qtyNum),2) . " kg";
                  }

                  $ref = strtoupper((string)($row['reference_type'] ?? ''));
                  $refId = $row['reference_id'] !== null ? ("#".$row['reference_id']) : '';
                  $note = $row['note'] ?? '';
                ?>
                <tr>
                  <td><?= h($dt) ?></td>
                  <td><?= h($prod) ?></td>
                  <td class="fw-bold"><?= h($qty) ?></td>
                  <td>
                    <?php if($t === 'in'): ?>
                      <span class="badge bg-success">IN</span>
                    <?php elseif($t === 'out'): ?>
                      <span class="badge bg-danger">OUT</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= h(strtoupper($t ?: 'N/A')) ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= h(trim($ref . " " . $refId)) ?></td>
                  <td><?= h($note) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-muted">No inventory transactions yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
            </div>

</div>
</main>

</div>
</div>

<script>
function fmtNum(v){
  const n = Number(v || 0);
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtKg(v){ return `${fmtNum(v)} kg`; }

// ===== SALES CHART =====
(() => {
  const el = document.getElementById('salesChart');
  if(!el) return;

  new Chart(el, {
    type: 'line',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
        label: 'Sales (kg)',
        data: <?= json_encode($salesData) ?>,
        tension: 0.35,
        fill: true,
        borderWidth: 2,
        pointRadius: 3,
        borderColor: '#1f77b4',
        backgroundColor: 'rgba(31,119,180,0.20)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? 0)}`
          }
        }
      },
      interaction: { mode:'index', intersect:false },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: (v) => fmtKg(v) }
        }
      }
    }
  });
})();

// ===== FORECAST CHART (SMA) =====
(() => {
  const el = document.getElementById('forecastChart');
  if(!el) return;

  new Chart(el, {
    type: 'bar',
    data: {
      labels: <?= json_encode($forecastLabels) ?>,
      datasets: [{
        label: 'Forecast (kg)',
        data: <?= json_encode($forecastValues) ?>,
        borderWidth: 1,
        backgroundColor: 'rgba(255,127,14,0.70)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? 0)}`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: (v) => fmtKg(v) }
        }
      }
    }
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
