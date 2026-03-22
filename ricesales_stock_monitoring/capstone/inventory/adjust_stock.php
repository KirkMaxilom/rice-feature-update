<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

include '../config/db.php';
$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $product_id  = (int)($_POST['product_id'] ?? 0);
    $adjust_type = $_POST['adjust_type'] ?? 'in';
    $qty_units   = (int)($_POST['qty_value'] ?? 0);
    $note        = trim($_POST['note'] ?? '');

    if($product_id <= 0 || $qty_units <= 0){
        $error = "Invalid product or quantity.";
    } else {

        // Get product sack weight
        $stmtW = $conn->prepare("SELECT unit_weight_kg FROM products WHERE product_id=? LIMIT 1");
        $stmtW->bind_param("i", $product_id);
        $stmtW->execute();
        $productRow = $stmtW->get_result()->fetch_assoc();
        $stmtW->close();

        $unit_weight = (float)($productRow['unit_weight_kg'] ?? 0);
        if($unit_weight <= 0){
            $error = "Product has no valid sack size.";
        } else {

            // Convert sacks to KG
            $qty_kg = $qty_units * $unit_weight;

            $conn->begin_transaction();
            try{

                // Check stock if reducing
                if($adjust_type === "out"){

                    $check = $conn->prepare("
                        SELECT IFNULL(SUM(
                            CASE
                                WHEN LOWER(type)='in' THEN qty_kg
                                WHEN LOWER(type)='out' THEN -qty_kg
                                ELSE 0
                            END
                        ),0) AS stock_kg
                        FROM inventory_transactions
                        WHERE product_id=?
                    ");

                    $check->bind_param("i",$product_id);
                    $check->execute();
                    $current = $check->get_result()->fetch_assoc();
                    $check->close();

                    $current_stock = (float)($current['stock_kg'] ?? 0);

                    if($current_stock < $qty_kg){
                        throw new Exception("Insufficient stock. Current: "
                            .number_format($current_stock,2)." kg");
                    }
                }

                if($note === ""){
                    $note = "Manual adjustment";
                }

                $reference_type = "adjust";

                $stmt = $conn->prepare("
                    INSERT INTO inventory_transactions
                    (product_id, qty_kg, reference_id,
                     reference_type, type, note, created_at)
                    VALUES (?, ?, NULL, ?, ?, ?, NOW())
                ");

                $stmt->bind_param(
                    "idsss",
                    $product_id,
                    $qty_kg,
                    $reference_type,
                    $adjust_type,
                    $note
                );

                $stmt->execute();
                $stmt->close();

                $conn->commit();
                header("Location: adjust_stock.php?success=1");
                exit;

            } catch(Exception $e){
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$products = $conn->query("
SELECT
    p.product_id,
    p.variety,
    p.grade,
    p.unit_weight_kg,
    IFNULL(SUM(
        CASE
            WHEN LOWER(it.type)='in' THEN it.qty_kg
            WHEN LOWER(it.type)='out' THEN -it.qty_kg
            ELSE 0
        END
    ),0) AS stock_kg
FROM products p
LEFT JOIN inventory_transactions it
ON it.product_id=p.product_id
WHERE p.archived=0
GROUP BY p.product_id
ORDER BY p.variety ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Adjust Stock</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
    body { background: #f4f6f9; padding-top: 60px; }

.sidebar { min-height:100vh; background:#2c3e50; padding-top: 0px ;}
.sidebar .nav-link { color:#fff; padding:10px 16px; border-radius:8px; font-size:.95rem; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background:#34495e; }

.sidebar .submenu { padding-left:35px; }
.sidebar .submenu a { font-size:.9rem; padding:6px 0; display:block; color:#ecf0f1; text-decoration:none; }
.sidebar .submenu a:hover { color:#fff; }

.modern-card { border-radius:14px; box-shadow:0 6px 16px rgba(0,0,0,.12); transition:.3s; }
.modern-card:hover { transform:translateY(-4px); }

.main-content { padding-top:0px; }
.adjust-panel {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    background: #ffffff;
}
.stat-tile {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 10px 12px;
    background: #fafbfd;
}
.stat-label {
    font-size: .75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.stat-value {
    font-size: 1rem;
    font-weight: 700;
    color: #212529;
}
.mode-chip {
    display: inline-block;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: .8rem;
    font-weight: 700;
}
.mode-add {
    background: #e8f8ee;
    color: #198754;
}
.mode-reduce {
    background: #fdecec;
    color: #dc3545;
}
.qty-btn {
    min-width: 52px;
}

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
      ☰
    </button>
    <span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>

    <div class="ms-auto dropdown">
      <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
        <?= htmlspecialchars($username) ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="../admin/profile.php">Profile</a></li>
        <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row">

<nav id="sidebarMenu" class="col-lg-2 d-lg-block sidebar collapse">
<div class="pt-4">
<ul class="nav flex-column gap-1">

<li class="nav-item">
<a class="nav-link" href="../admin/dashboard.php">
<i class="fas fa-home me-2"></i>Dashboard
</a>
</li>

<li class="nav-item">
<a class="nav-link active" data-bs-toggle="collapse" href="#inventoryMenu">
<i class="fas fa-warehouse me-2"></i>Stock Monitoring
<i class="fas fa-chevron-down float-end"></i>
</a>
<div class="collapse show submenu" id="inventoryMenu">
<a href="../admin/products.php">Products</a>
<a href="../inventory/add_stock.php" class="fw-bold">Stock In (Receiving)</a>
<a href="../inventory/adjust_stock.php">Stock Adjustments</a>
<a href="../inventory/inventory.php">Stock Logs</a>
</div>
</li>

<li class="nav-item">
<a class="nav-link" href="../admin/users.php"><i class="fas fa-users me-2"></i>User Management</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../admin/sales.php">
<i class="fas fa-cash-register me-2"></i>Sales
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../admin/analytics.php">
<i class="fas fa-chart-line me-2"></i>Analytics & Forecasting
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="../admin/system_logs.php">
<i class="fas fa-archive me-2"></i>System Logs
</a>
</li>

</ul>
</div>
</nav>

<main class="col-lg-10 ms-sm-auto px-4 main-content">

<h2 class="mb-3">Adjust Stock</h2>
<?php if(isset($_GET['success'])): ?>
<div class="alert alert-success">Stock adjusted successfully!</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card modern-card adjust-panel">
<div class="card-body p-4">

<form method="POST">

<div class="mb-3">
<label class="form-label fw-semibold">Product</label>
<select name="product_id" id="productSelect" class="form-select" required>
<option value="">Select product</option>
<?php while($row=$products->fetch_assoc()): ?>
<option value="<?=$row['product_id']?>"
        data-stock="<?=$row['stock_kg']?>"
        data-weight="<?=$row['unit_weight_kg']?>">
<?= htmlspecialchars($row['variety']." - ".$row['grade']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<input type="hidden" name="adjust_type" id="adjustType" value="in">

<div class="row g-3 mb-3">
<div class="col-md-3">
<div class="stat-tile">
<div class="stat-label">Current Stock</div>
<div class="stat-value"><span id="stockValue">0.00</span> kg</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-tile">
<div class="stat-label">Unit Size (kg/sack)</div>
<div class="stat-value"><span id="weightValue">0.00</span> kg</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-tile">
<div class="stat-label">Equivalent Sacks Left</div>
<div class="stat-value"><span id="sacksLeftValue">0.00</span> sacks</div>
</div>
</div>
<div class="col-md-3">
<div class="stat-tile">
<div class="stat-label">Adjustment Total</div>
<div class="stat-value"><span id="totalKgValue">0.00</span> kg</div>
</div>
</div>
</div>

<div class="mb-3">
<label class="form-label fw-semibold">Adjust Quantity (no. of sacks)</label>
<div class="input-group" style="max-width:340px;">
<button type="button" class="btn btn-outline-danger qty-btn" onclick="decreaseQty()">−</button>
<input type="number" id="qtyInput" name="qty_value"
       class="form-control text-center"
       value="1" min="1" step="1" inputmode="numeric">
<button type="button" class="btn btn-outline-success qty-btn" onclick="increaseQty()">+</button>
</div>

<div class="mt-2 d-flex align-items-center gap-2">
<span id="modeChip" class="mode-chip mode-add">ADD</span>
<span id="modeText" class="fw-bold text-success mb-0">Mode: ADD</span>
</div>
</div>

<div class="mb-3">
<label class="form-label fw-semibold">Note</label>
<textarea name="note" class="form-control" rows="3" placeholder="Reason for adjustment (optional)"></textarea>
</div>

<div class="d-flex gap-2 align-items-center flex-wrap">
<button class="btn btn-primary px-4">Save Adjustment</button>
<small class="text-muted">
Use <b>+</b> for additional stock, and <b>-</b> for damaged/unsellable
</small>
</div>

</form>

</div>
</div>
</div>

<script>
let qty = 1;
const productSelect = document.getElementById('productSelect');
const qtyInput = document.getElementById('qtyInput');
const adjustType = document.getElementById('adjustType');
const modeText = document.getElementById('modeText');
const modeChip = document.getElementById('modeChip');
const stockValue = document.getElementById('stockValue');
const weightValue = document.getElementById('weightValue');
const sacksLeftValue = document.getElementById('sacksLeftValue');
const totalKgValue = document.getElementById('totalKgValue');

function updateProductStats(){
    const opt = productSelect.options[productSelect.selectedIndex];
    const stock = parseFloat(opt?.getAttribute('data-stock') || 0);
    const weight = parseFloat(opt?.getAttribute('data-weight') || 0);
    const sacksLeft = weight > 0 ? (stock / weight) : 0;
    const total = qty * weight;

    stockValue.textContent = stock.toFixed(2);
    weightValue.textContent = weight.toFixed(2);
    sacksLeftValue.textContent = sacksLeft.toFixed(2);
    totalKgValue.textContent = total.toFixed(2);
}

function refreshQtyAndTotals(){
    qtyInput.value = qty;
    updateProductStats();
}

function syncQtyFromInput(){
    const parsed = parseInt(qtyInput.value, 10);
    qty = Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
    qtyInput.value = qty;
    updateProductStats();
}

function increaseQty(){
    qty++;
    adjustType.value = "in";
    modeText.innerHTML = "Mode: ADD";
    modeText.className = "fw-bold text-success mb-0";
    modeChip.textContent = "ADD";
    modeChip.className = "mode-chip mode-add";
    refreshQtyAndTotals();
}

function decreaseQty(){
    qty = Math.max(1, qty - 1);
    adjustType.value = "out";
    modeText.innerHTML = "Mode: REDUCE";
    modeText.className = "fw-bold text-danger mb-0";
    modeChip.textContent = "REDUCE";
    modeChip.className = "mode-chip mode-reduce";
    refreshQtyAndTotals();
}

productSelect.addEventListener('change', updateProductStats);
qtyInput.addEventListener('input', syncQtyFromInput);
qtyInput.addEventListener('blur', syncQtyFromInput);
refreshQtyAndTotals();
</script>

</body>
</html>
