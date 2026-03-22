<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$user_id  = (int)($_SESSION['user_id'] ?? 0);

include '../config/db.php';

// --- Handle Approve Return ---
if(isset($_POST['approve_return'])) {
    $return_id = (int)($_POST['return_id'] ?? 0);

    // Get return details
    $stmt = $conn->prepare("SELECT return_id, sale_id, product_id, qty_returned, status FROM returns WHERE return_id = ?");
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $ret = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$ret){
        $message = "Return not found.";
    } else {
        $product_id    = (int)$ret['product_id'];
        $qty_returned  = (float)$ret['qty_returned'];

        // Use transaction so stock update + log always match
        $conn->begin_transaction();
        try {
            // Update return status (pending/approved/rejected)
            $stmt = $conn->prepare("UPDATE returns SET status='approved' WHERE return_id=?");
            $stmt->bind_param("i", $return_id);
            $stmt->execute();
            $stmt->close();

            // Update stock
            $stmt = $conn->prepare("UPDATE products SET stock_kg = stock_kg + ? WHERE product_id = ?");
            $stmt->bind_param("di", $qty_returned, $product_id);
            $stmt->execute();
            $stmt->close();

            // Log inventory transaction
            $reference_type = "return";
            $type = "in";
            $note = "Customer return approved";

            $stmt = $conn->prepare("
                INSERT INTO inventory_transactions
                    (product_id, qty_kg, reference_id, reference_type, type, note, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("idisss", $product_id, $qty_returned, $return_id, $reference_type, $type, $note);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = "Return approved and stock updated!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to approve return: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stock Logs | DE ORO HIYS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
body { background:#f4f6f9; padding-top: 60px;}
.sidebar { min-height:100vh; background:#2c3e50; padding-top: 0px; }
.sidebar .nav-link { color:#fff; padding:10px 16px; border-radius:8px; font-size:.95rem; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background:#34495e; }
.sidebar .submenu { padding-left:35px; }
.sidebar .submenu a { font-size:.9rem; padding:6px 0; display:block; color:#ecf0f1; text-decoration:none; }
.sidebar .submenu a:hover { color:#fff; }

.modern-card { border-radius:14px; box-shadow:0 6px 16px rgba(0,0,0,.12); transition:.3s; }
.modern-card:hover { transform:translateY(-4px); }
.main-content { padding-top:0px; }

.table td, .table th { padding:0.5rem; vertical-align: middle; }
.table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    white-space: nowrap;
}
.table-wrap {
    max-height: 70vh;
    overflow: auto;
    border-radius: 12px;
}
.qty-in { color:#198754; font-weight:700; }
.qty-out { color:#dc3545; font-weight:700; }
.toolbar {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 10px;
    background: #fbfcfe;
}
.table-note {
    font-size: .85rem;
    color: #6c757d;
}

.badge-return { background-color:#198754; }
.badge-sale { background-color:#dc3545; }
.badge-purchase { background-color:#0d6efd; }
.badge-adjust { background-color:#fd7e14; }

.type-in { color:#198754; font-weight:700; }
.type-out { color:#dc3545; font-weight:700; }
.type-adjust { color:#fd7e14; font-weight:700; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
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
<li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>

<li class="nav-item">
  <a class="nav-link active" data-bs-toggle="collapse" href="#inventoryMenu">
    <i class="fas fa-warehouse me-2"></i>Stock Monitoring
    <i class="fas fa-chevron-down float-end"></i>
  </a>
  <div class="collapse show submenu" id="inventoryMenu">
    <a href="../admin/products.php">Products</a>
    <a href="../inventory/add_stock.php">Stock In (Receiving)</a>
    <a href="../inventory/adjust_stock.php">Stock Adjustments</a>
    <a href="../inventory/inventory.php" class="fw-bold">Stock Logs</a>
  </div>
</li>

<li class="nav-item"><a class="nav-link" href="../admin/users.php"><i class="fas fa-users me-2"></i>User Management</a></li>
<li class="nav-item"><a class="nav-link" href="../admin/sales.php"><i class="fas fa-cash-register me-2"></i>Sales</a></li>
<li class="nav-item"><a class="nav-link" href="../admin/analytics.php"><i class="fas fa-chart-line me-2"></i>Analytics & Forecasting</a></li>
<li class="nav-item"><a class="nav-link" href="../admin/system_logs.php"><i class="fas fa-archive me-2"></i>System Logs</a></li>
</ul>
</div>
</nav>

<main class="col-lg-10 ms-auto main-content">
<div class="container-fluid py-4">

<h3 class="mb-4">Stocks Overview Timeline</h3>

<?php if(isset($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="toolbar mb-3">
  <div class="row g-2">
    <div class="col-md-5">
      <input type="text" id="logSearch" class="form-control" placeholder="Search product, customer, supplier, note...">
    </div>
    <div class="col-md-3">
      <select id="typeFilter" class="form-select">
        <option value="">All Types</option>
        <option value="in">IN</option>
        <option value="out">OUT</option>
        <option value="adjust">ADJUST</option>
      </select>
    </div>
    <div class="col-md-3">
      <select id="refFilter" class="form-select">
        <option value="">All References</option>
        <option value="purchase">PURCHASE</option>
        <option value="sale">SALE</option>
        <option value="return">RETURN</option>
        <option value="adjust">ADJUST</option>
      </select>
    </div>
    <div class="col-md-1 d-grid">
      <button type="button" id="clearFilter" class="btn btn-outline-secondary">Clear</button>
    </div>
  </div>
  <div class="table-note mt-2">
    Showing <span id="visibleCount">0</span> of <span id="totalCount">0</span> records
  </div>
</div>

<div class="card modern-card shadow-sm">
<div class="card-body">
<div class="table-wrap">
<table class="table table-striped table-bordered align-middle mb-0" id="inventoryTable">
<thead class="table-dark">
<tr>
  <th>Date</th>
  <th>Product</th>
  <th>Customer</th>
  <th>Supplier</th>
  <th>Qty (kg)</th>
  <th>Type</th>
  <th>Reference</th>
  <th>Note</th>
  <th>Action</th>
</tr>
</thead>
<tbody>
<?php
$sql = "
SELECT 
    it.*,
    p.variety,
    p.grade,

    r.return_id,
    r.status AS return_status,

    sa.sale_id,
    c.first_name,
    c.last_name,

    -- ✅ Return customer (via returns.sale_id -> sales -> customers)
    sa_r.sale_id AS return_sale_id,
    c_r.first_name AS return_first_name,
    c_r.last_name  AS return_last_name,

    pu.purchases_id,
    sup.name AS supplier_name

FROM inventory_transactions it
LEFT JOIN products p ON it.product_id = p.product_id

LEFT JOIN returns r
    ON it.reference_type='return' AND it.reference_id = r.return_id

LEFT JOIN sales sa
    ON it.reference_type='sale' AND it.reference_id = sa.sale_id
LEFT JOIN customers c
    ON sa.customer_id = c.customer_id

LEFT JOIN sales sa_r
    ON r.sale_id = sa_r.sale_id
LEFT JOIN customers c_r
    ON sa_r.customer_id = c_r.customer_id

LEFT JOIN purchases pu
    ON it.reference_type='purchase' AND it.reference_id = pu.purchases_id
LEFT JOIN suppliers sup
    ON pu.supplier_id = sup.supplier_id

ORDER BY it.created_at DESC
";

$result = $conn->query($sql);
if(!$result){
    die("Query Error: " . $conn->error);
}

while($row = $result->fetch_assoc()){
    $date = date("M d, Y h:i A", strtotime($row['created_at']));
    $product = trim(($row['variety'] ?? '')." - ".($row['grade'] ?? ''));
    if($product === "-") $product = "N/A";

    // ✅ Customer: SALE uses c.*, RETURN uses c_r.*
    $customer = "N/A";
    if(!empty($row['first_name'])){
        $customer = $row['first_name']." ".$row['last_name'];
    } elseif(!empty($row['return_first_name'])){
        $customer = $row['return_first_name']." ".$row['return_last_name'];
    }

    $type = strtolower(trim($row['type'] ?? ''));
    $isIn = ($type === 'in');

    // define $ref first
    $ref = strtoupper(trim($row['reference_type'] ?? ''));

    // qty sign rules (adjust uses its own sign)
    $qtyNum = (float)($row['qty_kg'] ?? 0);
    if($type === 'adjust'){
        $qty = ($qtyNum >= 0 ? '+' : '-') . number_format(abs($qtyNum), 2);
    } else {
        $qty = ($isIn ? '+' : '-') . number_format(abs($qtyNum), 2);
    }
    $qtyClass = $isIn ? "qty-in" : "qty-out";
    if($type === 'adjust') $qtyClass = "type-adjust";

    $typeText = strtoupper($type);
    if($type === 'adjust') $typeClass = "type-adjust";
    else $typeClass = $isIn ? "type-in" : "type-out";

    // Supplier only for PURCHASE + IN
    $supplier = (!empty($row['supplier_name'])) ? $row['supplier_name'] : 'N/A';
    $showSupplier = ($isIn && $ref === "PURCHASE") ? $supplier : "N/A";

    // Reference badge
    $refBadge = "";
    if($ref === "RETURN") $refBadge = "<span class='badge badge-return'>RETURN #".(int)$row['reference_id']."</span>";
    else if($ref === "SALE") $refBadge = "<span class='badge badge-sale'>SALE #".(int)$row['reference_id']."</span>";
    else if($ref === "PURCHASE") $refBadge = "<span class='badge badge-purchase'>PURCHASE #".(int)$row['reference_id']."</span>";
    else if($ref === "ADJUST") $refBadge = "<span class='badge badge-adjust'>ADJUST</span>";
    else $refBadge = "<span class='badge bg-secondary'>".$ref."</span>";

    $noteRaw = (string)($row['note'] ?? '');
    $note = htmlspecialchars($noteRaw);
    $searchBlob = strtolower($product . " " . $customer . " " . $showSupplier . " " . $noteRaw);
    $safeSearchBlob = htmlspecialchars($searchBlob, ENT_QUOTES);
    $safeType = htmlspecialchars($type, ENT_QUOTES);
    $safeRef = htmlspecialchars(strtolower($ref), ENT_QUOTES);

    echo "<tr data-type='{$safeType}' data-ref='{$safeRef}' data-search='{$safeSearchBlob}'>
        <td>{$date}</td>
        <td>".htmlspecialchars($product)."</td>
        <td>".htmlspecialchars($customer)."</td>
        <td>".htmlspecialchars($showSupplier)."</td>
        <td class='{$qtyClass}'>{$qty}</td>
        <td class='{$typeClass}'>{$typeText}</td>
        <td>{$refBadge}</td>
        <td>{$note}</td>
        <td>";

    // Show approve button only if RETURN is still pending
    if($ref === "RETURN" && isset($row['return_id']) && strtolower($row['return_status'] ?? '') === 'pending'){
        echo "<form method='POST' class='m-0'>
                <input type='hidden' name='return_id' value='".(int)$row['return_id']."'>
                <button type='submit' name='approve_return' class='btn btn-sm btn-success'>Approve</button>
              </form>";
    } else {
        echo "-";
    }

    echo "</td></tr>";
}
?>
</tbody>
</table>
</div>
</div>
</div>

</div>
</main>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchEl = document.getElementById('logSearch');
const typeEl = document.getElementById('typeFilter');
const refEl = document.getElementById('refFilter');
const clearEl = document.getElementById('clearFilter');
const rows = Array.from(document.querySelectorAll('#inventoryTable tbody tr'));
const visibleCountEl = document.getElementById('visibleCount');
const totalCountEl = document.getElementById('totalCount');

function applyFilters(){
  const q = (searchEl.value || '').trim().toLowerCase();
  const t = (typeEl.value || '').trim().toLowerCase();
  const r = (refEl.value || '').trim().toLowerCase();
  let visible = 0;

  rows.forEach((row) => {
    const rowType = (row.dataset.type || '').toLowerCase();
    const rowRef = (row.dataset.ref || '').toLowerCase();
    const rowSearch = (row.dataset.search || '').toLowerCase();

    const okSearch = !q || rowSearch.includes(q);
    const okType = !t || rowType === t;
    const okRef = !r || rowRef === r;
    const show = okSearch && okType && okRef;

    row.style.display = show ? '' : 'none';
    if(show) visible++;
  });

  visibleCountEl.textContent = String(visible);
  totalCountEl.textContent = String(rows.length);
}

searchEl.addEventListener('input', applyFilters);
typeEl.addEventListener('change', applyFilters);
refEl.addEventListener('change', applyFilters);
clearEl.addEventListener('click', () => {
  searchEl.value = '';
  typeEl.value = '';
  refEl.value = '';
  applyFilters();
});

applyFilters();
</script>
</body>
</html>
