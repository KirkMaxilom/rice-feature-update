<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header("Location: ../login.php");
  exit;
}
if(strtolower($_SESSION['role'] ?? '') !== 'cashier'){
  header("Location: ../login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Cashier';
include '../config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
/* =========================
   LOAD GLOBAL STOCK SETTINGS
========================= */

$DEFAULT_LOW  = 10;
$DEFAULT_OVER = 1000;

$LOW_STOCK_KG  = $DEFAULT_LOW;
$OVER_STOCK_KG = $DEFAULT_OVER;

$settingsRes = $conn->query("
    SELECT low_threshold_kg, over_threshold_kg
    FROM stock_settings
    WHERE id = 1
    LIMIT 1
");

if($settingsRes && $row = $settingsRes->fetch_assoc()){
    $LOW_STOCK_KG  = (float)$row['low_threshold_kg'];
    $OVER_STOCK_KG = (float)$row['over_threshold_kg'];
}

// Fetch products with computed stock (no SKU / no unit_price)
$sql = "
SELECT
  p.product_id,
  p.variety,
  p.grade,
  p.unit_weight_kg,
  p.price_per_kg,
  p.price_per_sack,
  IFNULL(SUM(
    CASE
      WHEN LOWER(it.type)='in' THEN it.qty_kg
      WHEN LOWER(it.type)='out' THEN -it.qty_kg
      WHEN LOWER(it.type)='adjust' THEN it.qty_kg
      ELSE 0
    END
  ),0) AS stock_kg
FROM products p
LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
WHERE p.archived = 0
GROUP BY p.product_id
ORDER BY stock_kg ASC, p.variety ASC, p.grade ASC
";
$result = $conn->query($sql);
if(!$result){
  die("Query Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stocks View | Cashier</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="../css/layout.css" rel="stylesheet">
</head>

<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
    <span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>

    <div class="ms-auto dropdown">
      <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <?= h($username) ?> <small class="text-muted">(Cashier)</small>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="cashier_profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">

    <?php include '../includes/cashier_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
  <div>
    <h3 class="fw-bold mb-1">Stocks View</h3>
    <div class="text-muted">Read-only stock monitoring for cashier reference.</div>
  </div>
</div>

<!-- THRESHOLD BADGES -->
<div class="mt-3 d-flex flex-wrap gap-2">
  <span class="badge bg-warning text-dark">
    LOW ≤ <?= number_format($LOW_STOCK_KG,2) ?> kg
  </span>

  <span class="badge bg-danger">
    OVER ≥ <?= number_format($OVER_STOCK_KG,2) ?> kg
  </span>
</div>

<!-- CARD -->
<div class="card modern-card mt-3">
<div class="card-body">

<div class="table-responsive">
<table class="table table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>Product</th>
<th class="text-end">Stock (kg)</th>
<th class="text-end">Est. Sacks</th>
<th class="text-end">Sell / Kg</th>
<th class="text-end">Sell / Sack</th>
<th>Status</th>
</tr>
</thead>
<tbody>

                <?php if($result && $result->num_rows > 0): ?>
                  <?php while($row = $result->fetch_assoc()):
                    $stock = (float)($row['stock_kg'] ?? 0);
                    $w     = (float)($row['unit_weight_kg'] ?? 0);
                    $pKg   = (float)($row['price_per_kg'] ?? 0);
                    $pSack = (float)($row['price_per_sack'] ?? 0);

                    $isLow  = ($stock > 0 && $stock <= $LOW_STOCK_KG);
                    $isOver = ($stock >= $OVER_STOCK_KG);

// Estimate sacks from kg (display only)
$estSacks = ($w > 0) ? ($stock / $w) : 0;

// Status badge
if($stock <= 0){
    $badge = "<span class='badge bg-secondary'>Out of Stock</span>";
    $rowClass = "table-secondary";

} elseif($isOver){
    $badge = "<span class='badge bg-danger'>Overstock</span>";
    $rowClass = "table-danger";

} elseif($isLow){
    $badge = "<span class='badge bg-warning text-dark'>Low</span>";
    $rowClass = "table-warning";

} else {
    $badge = "<span class='badge bg-success'>Available</span>";
    $rowClass = "";
}
?>
                    <tr class="<?= $rowClass ?>">
                      <td class="fw-semibold">
                        <?= h(($row['variety'] ?? '').' - '.($row['grade'] ?? '')) ?>
                        <?php if($w > 0): ?>
                          <div class="text-muted small">Sack size: <?= number_format($w,0) ?> kg</div>
                        <?php else: ?>
                          <div class="text-muted small">Sack size: —</div>
                        <?php endif; ?>
                      </td>

                      <td class="text-end fw-bold"><?= number_format($stock,2) ?></td>

                      <td class="text-end">
                        <?php if($w > 0): ?>
                          <?= number_format($estSacks,2) ?>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>

                      <td class="text-end">
                        <?php if($pKg > 0): ?>
                          ₱<?= number_format($pKg,2) ?>
                        <?php else: ?>
                          <span class="text-muted">0.00</span>
                        <?php endif; ?>
                      </td>

                      <td class="text-end">
                        <?php if($pSack > 0): ?>
                          ₱<?= number_format($pSack,2) ?>
                        <?php else: ?>
                          <span class="text-muted">0.00</span>
                        <?php endif; ?>
                      </td>

                      <td><?= $badge ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-center text-muted">No products found.</td></tr>
                <?php endif; ?>

                </tbody>
              </table>
            </div>

            <div class="text-muted small mt-2">
              Low stock threshold: <?= number_format($LOW_STOCK_KG,2) ?> kg
              <span class="mx-1">•</span>
              “Est. Sacks” is computed as <b>stock_kg ÷ unit_weight_kg</b> (display only).
            </div>

          </div>
        </div>

      </div>
    </main>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>