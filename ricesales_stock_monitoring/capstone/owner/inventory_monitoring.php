<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'owner'){ header("Location: ../login.php"); exit; }

$username = $_SESSION['username'] ?? 'Owner';
include '../config/db.php';

$DEFAULT_LOW = 10.0;
$DEFAULT_OVER = 1000.0;

$settingsRow = null;
$settingsRes = $conn->query("SELECT low_threshold_kg, over_threshold_kg FROM stock_settings WHERE id=1 LIMIT 1");
if($settingsRes){
$settingsRow = $settingsRes->fetch_assoc();
}

$dbLow = (float)($settingsRow['low_threshold_kg'] ?? $DEFAULT_LOW);
$dbOver = (float)($settingsRow['over_threshold_kg'] ?? $DEFAULT_OVER);

/* allow optional override via GET (still defaults to admin settings) */
$search = trim($_GET['search'] ?? '');
$low = (float)($_GET['low'] ?? $dbLow);
$over = (float)($_GET['over'] ?? $dbOver);

$AGE_LIMIT_DAYS = (int)($_GET['age'] ?? 30); // aging threshold

if($low < 0) $low = 0;
if($over <= 0) $over = $dbOver;
if($over <= $low) $over = $low + 1;

$productsRows = [];

$sql = "
SELECT
p.product_id,
p.variety,
p.grade,
p.unit_weight_kg,
p.price_per_sack,
p.price_per_kg,

IFNULL(SUM(
CASE
WHEN LOWER(it.type)='in' THEN it.qty_kg
WHEN LOWER(it.type)='out' THEN -it.qty_kg
WHEN LOWER(it.type)='adjust' THEN it.qty_kg
ELSE 0
END
),0) AS stock_kg,

MAX(CASE WHEN LOWER(it.type)='in'
THEN DATE(it.created_at)
END) AS last_in_date,

CASE
WHEN MAX(CASE WHEN LOWER(it.type)='in'
THEN DATE(it.created_at)
END) IS NULL
THEN NULL
ELSE DATEDIFF(
CURDATE(),
MAX(CASE WHEN LOWER(it.type)='in'
THEN DATE(it.created_at)
END)
)
END AS age_days

FROM products p
LEFT JOIN inventory_transactions it
ON it.product_id = p.product_id

WHERE p.archived = 0
";

/* Prepared search */
$params = [];
$types = "";

if($search !== ''){
$sql .= " AND (p.variety LIKE ? OR p.grade LIKE ?) ";
$like = "%{$search}%";
$params[] = $like;
$params[] = $like;
$types .= "ss";
}

$sql .= "
GROUP BY
p.product_id, p.variety, p.grade, p.unit_weight_kg, p.price_per_sack, p.price_per_kg, p.stock_kg
ORDER BY stock_kg ASC, p.variety ASC
";

$stmt = $conn->prepare($sql);
if(!$stmt){
die("SQL Prepare Error: " . htmlspecialchars($conn->error));
}
if(!empty($params)){
$stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

if($res){
while($row = $res->fetch_assoc()){
$productsRows[] = $row;
}
}
$stmt->close();

/* =========================
PREP: ALERT LISTS
========================= */
$lowItems = [];
$overItems = [];
$agingItems = [];

foreach($productsRows as $row){
$stock = (float)($row['stock_kg'] ?? 0);
$ageDays = ($row['age_days'] !== null) ? (int)$row['age_days'] : null;

$label = ($row['variety'].' - '.$row['grade']);
$sackSize = (float)($row['unit_weight_kg'] ?? 0);

if($stock >= $over){
$overItems[] = [
'product' => $label,
'sack' => $sackSize,
'stock' => $stock
];
}

if($stock <= $low){
$lowItems[] = [
'product' => $label,
'sack' => $sackSize,
'stock' => $stock
];
}

if($stock > 0 && $ageDays !== null && $ageDays >= $AGE_LIMIT_DAYS){
$agingItems[] = [
'product' => $label,
'sack' => $sackSize,
'stock' => $stock,
'age_days' => $ageDays,
'last_in_date' => $row['last_in_date'] ?? null,
];
}
}

$autoModal = null;
if(count($overItems) > 0) $autoModal = 'overStockModal';
elseif(count($lowItems) > 0) $autoModal = 'lowStockModal';
elseif(count($agingItems) > 0) $autoModal = 'agingStockModal';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stocks Monitoring | Owner</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="../css/layout.css" rel="stylesheet">
<style>
.filter-card{
  border-radius: 14px;
  border: 1px solid #e8ebef;
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
}
.filter-label{
  font-size: .82rem;
  font-weight: 700;
  color: #5f6b7a;
  letter-spacing: .01em;
}
.filter-input{
  border-radius: 12px;
}
.filter-action{
  border-radius: 12px;
}
</style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
<div class="container-fluid">
<button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
<span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>

<div class="ms-auto dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
<?= h($username) ?> <small class="text-muted">(Owner)</small>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
</ul>
</div>
</div>
</nav>

<div class="container-fluid">
<div class="row">

<?php include '../includes/owner_sidebar.php'; ?>

<!-- MAIN -->
<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
<div>
<h3 class="fw-bold mb-1">Stocks Monitoring</h3>
</div>
<button class="btn btn-outline-dark" onclick="window.print()">
<i class="fa-solid fa-print me-1"></i> Print
</button>
</div>

<div class="mt-2 d-flex flex-wrap gap-2">
<span class="badge bg-warning text-dark">LOW ≤ <?= number_format($low,2) ?> kg</span>
<span class="badge bg-danger">OVER ≥ <?= number_format($over,2) ?> kg</span>
<span class="badge bg-secondary">AGING ≥ <?= (int)$AGE_LIMIT_DAYS ?> days (since last stock-in)</span>
</div>

<!-- ALERT BUTTONS -->
<div class="d-flex flex-wrap gap-2 mb-3 mt-3">
<button type="button" class="btn btn-dark"
data-bs-toggle="modal" data-bs-target="#overStockModal"
<?= count($overItems) ? "" : "disabled" ?>>
<i class="fa-solid fa-triangle-exclamation me-1"></i>
Overstock
<span class="badge bg-danger ms-1"><?= (int)count($overItems) ?></span>
</button>

<button type="button" class="btn btn-outline-dark"
data-bs-toggle="modal" data-bs-target="#lowStockModal"
<?= count($lowItems) ? "" : "disabled" ?>>
<i class="fa-solid fa-cart-shopping me-1"></i>
Low Stock
<span class="badge bg-warning text-dark ms-1"><?= (int)count($lowItems) ?></span>
</button>

<button type="button" class="btn btn-outline-dark"
data-bs-toggle="modal" data-bs-target="#agingStockModal"
<?= count($agingItems) ? "" : "disabled" ?>>
<i class="fa-solid fa-clock me-1"></i>
Aging Stock
<span class="badge bg-secondary ms-1"><?= (int)count($agingItems) ?></span>
</button>
</div>

<!-- FILTER CARD -->
<div class="card filter-card mb-3 border-0">
  <div class="card-body py-3">
    <form class="row g-3" method="get">

      <!-- SEARCH -->
      <div class="col-12 col-lg-5">
        <label class="form-label filter-label mb-1">Find Product (Variety or Grade)</label>
        <input class="form-control form-control-lg"
               name="search"
               value="<?= h($search) ?>"
               placeholder="Try: Valencia, Jasmine, Premium, Regular">
        <small class="text-muted">Type a variety or grade to narrow the list.</small>
      </div>

      <!-- LOW -->
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label filter-label mb-1">Low (kg)</label>
        <input class="form-control form-control-lg text-center filter-input"
               type="number" step="0.01"
               name="low"
               value="<?= h((string)$low) ?>">
        <small class="text-muted">Products at or below this are marked LOW.</small>
      </div>

      <!-- OVER -->
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label filter-label mb-1">Over (kg)</label>
        <input class="form-control form-control-lg text-center filter-input"
               type="number" step="0.01"
               name="over"
               value="<?= h((string)$over) ?>">
        <small class="text-muted">Products at or above this are marked OVER.</small>
      </div>

      <!-- AGING -->
      <div class="col-6 col-md-2 col-lg-1">
        <label class="form-label filter-label mb-1">Aging (days)</label>
        <input class="form-control form-control-lg text-center filter-input"
               type="number" step="1"
               name="age"
               value="<?= h((string)$AGE_LIMIT_DAYS) ?>">
        <small class="text-muted">Days since last stock-in.</small>
      </div>

      <!-- APPLY -->
      <div class="col-6 col-md-2 col-lg-2">
        <label class="form-label filter-label mb-1 invisible">Apply</label>
        <div class="d-flex gap-2">
        <button class="btn btn-dark btn-lg w-100 filter-action">
          <i class="fa-solid fa-magnifying-glass me-2"></i>Apply Filters
        </button>
        <a class="btn btn-outline-secondary btn-lg filter-action" href="inventory_monitoring.php" title="Reset filters">
          <i class="fa-solid fa-rotate-left"></i>
        </a>
        </div>
        <!-- reserve same helper height -->
        <small class="text-muted invisible">.</small>
      </div>

    </form>
  </div>
</div>

<!-- TABLE -->
<div class="card modern-card" id="stockTable">
<div class="card-body">
<div class="table-responsive">
<table class="table table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>Product</th>
<th class="text-end">Sack Size</th>
<th class="text-end">Stock (kg)</th>
<th class="text-end">Price / Sack</th>
<th class="text-end">Price / Kg</th>
<th>Last Stock-in</th>
<th class="text-end">Age (days)</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php if(count($productsRows) > 0): ?>
<?php foreach($productsRows as $p):
$stock = (float)($p['stock_kg'] ?? 0);
$ageDays = ($p['age_days'] !== null) ? (int)$p['age_days'] : null;

$isLow = ($stock <= $low);
$isOver = ($stock >= $over);
$isAging = ($stock > 0 && $ageDays !== null && $ageDays >= $AGE_LIMIT_DAYS);

$rowClass = '';
if($isOver) $rowClass = 'table-danger';
elseif($isLow) $rowClass = 'table-warning';
elseif($isAging) $rowClass = 'table-secondary';
?>
<tr class="<?= $rowClass ?>">
<td class="fw-bold"><?= h($p['variety'].' - '.$p['grade']) ?></td>
<td class="text-end"><?= number_format((float)($p['unit_weight_kg'] ?? 0),0) ?> kg</td>
<td class="text-end fw-bold"><?= number_format($stock,2) ?></td>
<td class="text-end">₱<?= number_format((float)($p['price_per_sack'] ?? 0),2) ?></td>
<td class="text-end">₱<?= number_format((float)($p['price_per_kg'] ?? 0),2) ?></td>
<td>
<?php if(!empty($p['last_in_date'])): ?>
<?= h(date("M d, Y", strtotime($p['last_in_date']))) ?>
<?php else: ?>
—
<?php endif; ?>
</td>
<td class="text-end"><?= ($ageDays !== null ? (int)$ageDays : '—') ?></td>
<td>
<?php if($stock <= 0): ?>
<span class="badge bg-secondary">OUT</span>
<?php elseif($isOver): ?>
<span class="badge bg-danger">OVER</span>
<?php elseif($isLow): ?>
<span class="badge bg-warning text-dark">LOW</span>
<?php elseif($isAging): ?>
<span class="badge bg-secondary">AGING</span>
<?php else: ?>
<span class="badge bg-success">OK</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="8" class="text-center text-muted">No active products found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div class="text-muted small mt-2">
Tip: Threshold defaults come from Admin settings. Aging is based on each product's last stock-in date.
</div>
</div>
</div>

</div>
</main>

</div>
</div>

<!-- =========================
MODALS
========================= -->

<!-- LOW STOCK MODAL -->
<div class="modal fade" id="lowStockModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold">Low Stock Alert</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<?php if(count($lowItems) === 0): ?>
<div class="alert alert-success mb-0">No low stock items right now.</div>
<?php else: ?>
<div class="alert alert-warning mb-3">
There are <?= (int)count($lowItems) ?> product(s) at or below <?= number_format($low,2) ?> kg.
</div>
<div class="table-responsive">
<table class="table table-sm table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>Product</th>
<th class="text-end">Sack Size</th>
<th class="text-end">Remaining (kg)</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($lowItems as $li): ?>
<tr>
<td class="fw-semibold"><?= h($li['product']) ?></td>
<td class="text-end"><?= number_format((float)$li['sack'],0) ?> kg</td>
<td class="text-end fw-bold"><?= number_format((float)$li['stock'],2) ?></td>
<td>
<?php if((float)$li['stock'] <= 0): ?>
<span class="badge bg-secondary">OUT</span>
<?php else: ?>
<span class="badge bg-warning text-dark">LOW</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<div class="modal-footer">
<button class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
<a class="btn btn-dark" href="#stockTable">Go to table</a>
</div>
</div>
</div>
</div>

<!-- OVERSTOCK MODAL -->
<div class="modal fade" id="overStockModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold">Overstock Alert</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<?php if(count($overItems) === 0): ?>
<div class="alert alert-success mb-0">No overstock items right now.</div>
<?php else: ?>
<div class="alert alert-danger mb-3">
There are <?= (int)count($overItems) ?> product(s) at or above <?= number_format($over,2) ?> kg.
</div>
<div class="table-responsive">
<table class="table table-sm table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>Product</th>
<th class="text-end">Sack Size</th>
<th class="text-end">Stock (kg)</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($overItems as $oi): ?>
<tr>
<td class="fw-semibold"><?= h($oi['product']) ?></td>
<td class="text-end"><?= number_format((float)$oi['sack'],0) ?> kg</td>
<td class="text-end fw-bold"><?= number_format((float)$oi['stock'],2) ?></td>
<td><span class="badge bg-danger">OVER</span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<div class="modal-footer">
<button class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
<a class="btn btn-dark" href="#stockTable">Go to table</a>
</div>
</div>
</div>
</div>

<!-- AGING MODAL -->
<div class="modal fade" id="agingStockModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold">Aging Stock Alert</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<?php if(count($agingItems) === 0): ?>
<div class="alert alert-success mb-0">No aging stock found for current threshold.</div>
<?php else: ?>
<div class="alert alert-secondary mb-3">
There are <?= (int)count($agingItems) ?> product(s) aged at least <?= (int)$AGE_LIMIT_DAYS ?> day(s) since last stock-in.
</div>
<div class="table-responsive">
<table class="table table-sm table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>Product</th>
<th class="text-end">Sack Size</th>
<th class="text-end">Stock (kg)</th>
<th class="text-end">Age</th>
<th>Last Stock-in</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($agingItems as $ai): ?>
<tr>
<td class="fw-semibold"><?= h($ai['product']) ?></td>
<td class="text-end"><?= number_format((float)$ai['sack'],0) ?> kg</td>
<td class="text-end fw-bold"><?= number_format((float)$ai['stock'],2) ?></td>
<td class="text-end fw-bold"><?= (int)$ai['age_days'] ?></td>
<td>
<?php if(!empty($ai['last_in_date'])): ?>
<?= h(date("M d, Y", strtotime($ai['last_in_date']))) ?>
<?php else: ?>—<?php endif; ?>
</td>
<td><span class="badge bg-secondary">AGING</span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<div class="modal-footer">
<button class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
<a class="btn btn-dark" href="#stockTable">Go to table</a>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if($autoModal): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
const modalEl = document.getElementById("<?= $autoModal ?>");
if(modalEl){
const modal = new bootstrap.Modal(modalEl);
modal.show();
}
});
</script>
<?php endif; ?>

</body>
</html>
