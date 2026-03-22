<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$user_id  = (int)($_SESSION['user_id'] ?? 0);

include '../config/db.php';

$error = "";

$OVERSTOCK_LIMIT_KG = 1000; // warehouse max capacity per product (kg)

if(isset($_POST['ajax']) && $_POST['ajax'] === 'add_supplier'){
    header('Content-Type: application/json; charset=utf-8');

    $name    = trim($_POST['supplier_name'] ?? '');
    $phone   = trim($_POST['supplier_phone'] ?? '');
    $address = trim($_POST['supplier_address'] ?? '');

    if($name === ''){
        echo json_encode(['ok'=>false, 'message'=>'Supplier name is required.']);
        exit;
    }

    // avoid duplicates
    $stmt = $conn->prepare("SELECT supplier_id, name FROM suppliers WHERE name = ? LIMIT 1");
    if(!$stmt){
        echo json_encode(['ok'=>false, 'message'=>'DB error: '.$conn->error]);
        exit;
    }
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($dup){
        echo json_encode([
            'ok'=>true,
            'supplier_id'=>(int)$dup['supplier_id'],
            'supplier_name'=>$dup['name'],
            'message'=>'Supplier already exists. Selected.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO suppliers (name, phone, address, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
    if(!$stmt){
        echo json_encode(['ok'=>false, 'message'=>'DB error: '.$conn->error]);
        exit;
    }
    $stmt->bind_param("sss", $name, $phone, $address);
    $stmt->execute();
    $newId = (int)$conn->insert_id;
    $stmt->close();

    echo json_encode([
        'ok'=>true,
        'supplier_id'=>$newId,
        'supplier_name'=>$name,
        'message'=>'Supplier added!'
    ]);
    exit;
}

if(isset($_POST['ajax']) && $_POST['ajax'] === 'last_supplier_price'){
    header('Content-Type: application/json; charset=utf-8');

    $product_id  = (int)($_POST['product_id'] ?? 0);
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);

    if($product_id <= 0 || $supplier_id <= 0){
        echo json_encode(['ok'=>false, 'message'=>'Product and supplier are required.']);
        exit;
    }

    // Get latest receiving note for this product + supplier, then parse supplier/sack=...
    $stmt = $conn->prepare("
        SELECT it.note
        FROM inventory_transactions it
        INNER JOIN purchases p ON p.purchase_id = it.reference_id
        WHERE it.product_id = ?
          AND it.reference_type = 'purchase'
          AND LOWER(it.type) = 'in'
          AND p.supplier_id = ?
        ORDER BY it.created_at DESC, it.transaction_id DESC
        LIMIT 1
    ");

    if(!$stmt){
        echo json_encode(['ok'=>false, 'message'=>'DB error: '.$conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $product_id, $supplier_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$row || !isset($row['note'])){
        echo json_encode(['ok'=>true, 'found'=>false, 'price'=>null]);
        exit;
    }

    $note = (string)$row['note'];
    $price = null;

    if(preg_match('/supplier\/sack=₱\s*([0-9]+(?:\.[0-9]+)?)/u', $note, $m)){
        $price = (float)$m[1];
    } elseif(preg_match('/supplier\/sack=PHP\s*([0-9]+(?:\.[0-9]+)?)/i', $note, $m2)){
        $price = (float)$m2[1];
    }

    if($price === null){
        echo json_encode(['ok'=>true, 'found'=>false, 'price'=>null]);
        exit;
    }

    echo json_encode(['ok'=>true, 'found'=>true, 'price'=>$price]);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])){

    $product_id    = (int)($_POST['product_id'] ?? 0);
    $supplier_id   = (int)($_POST['supplier_id'] ?? 0);
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $due_date      = $_POST['due_date'] ?? null;

    $qty_sacks     = (float)($_POST['qty_sacks'] ?? 0);
    $supplier_price_per_sack = (float)($_POST['supplier_price_per_sack'] ?? 0);
    $transport_handling = (float)($_POST['transport_handling'] ?? 0);

    // ✅ SETTABLE MARKUP
    $markup_per_kg = (float)($_POST['markup_per_kg'] ?? 0);
    if($markup_per_kg < 0) $markup_per_kg = 0;

    $note          = trim($_POST['note'] ?? '');

    if($product_id <= 0 || $supplier_id <= 0){
        $error = "Please select a product and supplier.";
    } elseif($qty_sacks <= 0){
        $error = "Please enter a valid quantity (no. of sacks).";
    } elseif($supplier_price_per_sack < 0){
        $error = "Supplier price per sack must be 0 or higher.";
    } elseif($transport_handling < 0){
        $error = "Transport/Handling must be 0 or higher.";
    } else {

        // Get product sack weight
        $stmtW = $conn->prepare("SELECT variety, grade, unit_weight_kg FROM products WHERE product_id=? AND archived=0 LIMIT 1");
        if(!$stmtW){
            $error = "DB error: ".$conn->error;
        } else {
            $stmtW->bind_param("i", $product_id);
            $stmtW->execute();
            $prodRow = $stmtW->get_result()->fetch_assoc();
            $stmtW->close();

            if(!$prodRow){
                $error = "Selected product not found or archived.";
            } else {

                $unit_weight_kg = (float)($prodRow['unit_weight_kg'] ?? 0);
                if($unit_weight_kg <= 0){
                    $error = "This product has no valid unit size (kg per sack). Please set it in Products first.";
                } else {

                    // qty_kg derived from sacks
                    $qty_kg = $qty_sacks * $unit_weight_kg;

                    // check current stock for overstock rule
                    $currentStockKg = 0.0;
                    $stmtS = $conn->prepare("
                        SELECT IFNULL(SUM(
                            CASE
                                WHEN LOWER(type)='in' THEN qty_kg
                                WHEN LOWER(type)='out' THEN -qty_kg
                                WHEN LOWER(type)='adjust' THEN qty_kg
                                ELSE 0
                            END
                        ),0) AS stock_kg
                        FROM inventory_transactions
                        WHERE product_id = ?
                    ");
                    if($stmtS){
                        $stmtS->bind_param("i", $product_id);
                        $stmtS->execute();
                        $rowS = $stmtS->get_result()->fetch_assoc();
                        $stmtS->close();
                        $currentStockKg = (float)($rowS['stock_kg'] ?? 0);
                    }

                    $projected = $currentStockKg + $qty_kg;

                    if($projected > $OVERSTOCK_LIMIT_KG){
                        $error = "Cannot receive stock. This will exceed the warehouse limit.\n"
                               . "Current stock: " . number_format($currentStockKg,2) . " kg\n"
                               . "Incoming: " . number_format($qty_kg,2) . " kg\n"
                               . "Projected: " . number_format($projected,2) . " kg\n"
                               . "Limit: " . number_format($OVERSTOCK_LIMIT_KG,2) . " kg";
                    } else {

                        // Total amount for purchase/AP (whole delivery)
                        $total_amount = ($qty_sacks * $supplier_price_per_sack) + $transport_handling;

                        // If no due date AND total_amount > 0, default +7 days
                        if(!$due_date && $total_amount > 0){
                            $due_date = date('Y-m-d', strtotime($purchase_date . ' +7 days'));
                        }

                        // Pricing computation
                        $per_sack_transport = ($qty_sacks > 0) ? ($transport_handling / $qty_sacks) : 0.0;
                        $landed_cost_per_sack = $supplier_price_per_sack + $per_sack_transport;

                        $cost_per_kg = ($unit_weight_kg > 0) ? ($landed_cost_per_sack / $unit_weight_kg) : 0.0;
                        $suggested_sell_per_kg = $cost_per_kg + $markup_per_kg;

                        // round to 2 decimals
                        $cost_per_kg = round($cost_per_kg, 2);
                        $suggested_sell_per_kg = round($suggested_sell_per_kg, 2);

                        $conn->begin_transaction();
                        try {

                            // 0) Update product prices (latest supplier + suggested retail)
                            $stmtU = $conn->prepare("
                                UPDATE products
                                SET price_per_sack = ?, price_per_kg = ?
                                WHERE product_id = ?
                            ");
                            if(!$stmtU){ throw new Exception("Prepare failed (products update): ".$conn->error); }
                            $stmtU->bind_param("ddi", $supplier_price_per_sack, $suggested_sell_per_kg, $product_id);
                            $stmtU->execute();
                            $stmtU->close();

                            // 1) Create purchase
                            $purchase_status = 'received';

                            $stmtP = $conn->prepare("
                                INSERT INTO purchases (supplier_id, purchase_date, total_amount, status, created_by, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            if(!$stmtP){ throw new Exception("Prepare failed (purchases): ".$conn->error); }
                            $stmtP->bind_param("isdsi", $supplier_id, $purchase_date, $total_amount, $purchase_status, $user_id);
                            $stmtP->execute();
                            $purchase_id = (int)$conn->insert_id;
                            $stmtP->close();

                            // 2) Accounts Payable ONLY IF total_amount > 0
                            if($total_amount > 0){
                                $amount_paid = 0.00;
                                $balance     = $total_amount;
                                $ap_status   = 'unpaid';

                                $stmtAP = $conn->prepare("
                                    INSERT INTO account_payable
                                      (purchase_id, supplier_id, total_amount, amount_paid, balance, due_date, status, approved, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
                                ");
                                if(!$stmtAP){ throw new Exception("Prepare failed (account_payable): ".$conn->error); }
                                $stmtAP->bind_param("iidddss", $purchase_id, $supplier_id, $total_amount, $amount_paid, $balance, $due_date, $ap_status);
                                $stmtAP->execute();
                                $stmtAP->close();
                            }

                            // 3) Log inventory transaction (IN) in KG
                            $reference_type = 'purchase';
                            $type = 'in';
                            if($note === ""){
                                $note = "Stock received (Receiving)";
                            }

                            // add costing info in note (optional)
                            $noteFull = $note
                                      . " | sacks=" . rtrim(rtrim(number_format($qty_sacks,2,'.',''), '0'), '.')
                                      . " | sack_size=" . rtrim(rtrim(number_format($unit_weight_kg,2,'.',''), '0'), '.')."kg"
                                      . " | supplier/sack=₱" . number_format($supplier_price_per_sack,2)
                                      . " | transport=₱" . number_format($transport_handling,2)
                                      . " | cost/kg=₱" . number_format($cost_per_kg,2)
                                      . " | markup/kg=₱" . number_format($markup_per_kg,2)
                                      . " | sell/kg=₱" . number_format($suggested_sell_per_kg,2);

                            $stmt2 = $conn->prepare("
                                INSERT INTO inventory_transactions
                                    (product_id, qty_kg, reference_id, reference_type, type, note, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ");
                            if(!$stmt2){ throw new Exception("Prepare failed (inventory_transactions): ".$conn->error); }
                            $stmt2->bind_param("idisss", $product_id, $qty_kg, $purchase_id, $reference_type, $type, $noteFull);
                            $stmt2->execute();
                            $stmt2->close();

                            $conn->commit();
                            header("Location: add_stock.php?success=received");
                            exit;

                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "Failed to receive stock: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Fetch products + suppliers
$products  = $conn->query("SELECT product_id, variety, grade, unit_weight_kg FROM products WHERE archived=0 ORDER BY variety ASC, grade ASC");
$suppliers = $conn->query("SELECT supplier_id, name FROM suppliers WHERE status='active' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stock In (Receiving) | DOHIVES</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
body { background:#f4f6f9;  padding-top: 60px; }

/* Sidebar */
.sidebar { min-height:100vh; background:#2c3e50; padding-top: 0px ;}
.sidebar .nav-link { color:#fff; padding:10px 16px; border-radius:8px; font-size:.95rem; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background:#34495e; }

/* Dropdown submenu */
.sidebar .submenu { padding-left:35px; }
.sidebar .submenu a { font-size:.9rem; padding:6px 0; display:block; color:#ecf0f1; text-decoration:none; }
.sidebar .submenu a:hover { color:#fff; }

/* Cards */
.modern-card { border-radius:14px; box-shadow:0 6px 16px rgba(0,0,0,.12); transition:.3s; }
.modern-card:hover { transform:translateY(-4px); }

/* Navbar spacing */
.main-content { padding-top:0px; }
.form-shell {
  border: 1px solid #e9ecef;
  border-radius: 14px;
  background: #fff;
}
.section-title {
  font-size: .85rem;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #6c757d;
  font-weight: 700;
  margin-bottom: 10px;
}
.section-block {
  border: 1px solid #eef1f4;
  border-radius: 10px;
  padding: 14px;
  background: #fcfdff;
}
.preview-tile {
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 12px;
  background: #f8fafc;
}
.preview-label {
  font-size: .75rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: .03em;
}
.preview-value {
  font-size: 1.05rem;
  font-weight: 700;
  color: #212529;
}
</style>
</head>

<body>

<!-- TOP NAVBAR -->
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

<!-- SIDEBAR -->
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

<!-- Main Content -->
<main class="col-lg-10 ms-sm-auto px-4 main-content">

<h2 class="mb-3">Stock In (Receiving)</h2>

<?php if($error): ?>
  <div class="alert alert-danger" style="white-space: pre-line;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if(isset($_GET['success']) && $_GET['success'] === 'received'): ?>
  <div class="alert alert-success">
    Stock received successfully! Purchase created.
    <br><small class="text-muted">
      Note: Accounts Payable is created only when Total Amount is greater than 0.
    </small>
  </div>
<?php endif; ?>

<div class="card modern-card form-shell mt-3">
  <div class="card-body p-4">
    <form method="POST">

        <div class="section-title">Selection</div>
        <div class="section-block mb-3">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label fw-semibold">Product</label>
              <select name="product_id" id="productSelect" class="form-select" required>
                <option value="">Select product</option>
                <?php while($row = $products->fetch_assoc()): ?>
                    <option value="<?= (int)$row['product_id'] ?>"
                            data-weight="<?= (float)$row['unit_weight_kg'] ?>">
                      <?= htmlspecialchars($row['variety'] . " - " . $row['grade'] . " (" . (float)$row['unit_weight_kg'] . " kg/sack)") ?>
                    </option>
                <?php endwhile; ?>
              </select>
              <div class="form-text">Unit size is shown as kg per sack.</div>
            </div>

            <div class="col-md-5">
              <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                <span>Supplier</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                + Add Supplier
                </button>
              </label>

              <select id="supplierSelect" name="supplier_id" class="form-select" required>
                <option value="">Select supplier</option>
                <?php while($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?= (int)$s['supplier_id'] ?>">
                      <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endwhile; ?>
              </select>
              <div class="form-text">Select an existing supplier or add a new one.</div>
            </div>
          </div>
        </div>

        <div class="section-title">Delivery Details</div>
        <div class="section-block mb-3">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Receiving Date</label>
            <input type="date" name="purchase_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="">
            <div class="form-text">Optional. If blank, default is +7 days (only when Total Amount > 0).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Quantity (no. of sacks)</label>
            <input type="number" step="1" min="1" name="qty_sacks" id="qtySacks" class="form-control" required>
          </div>
        </div>
        </div>

        <div class="section-title">Costing Inputs</div>
        <div class="section-block mb-3">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Supplier Price per Sack (PHP)</label>
            <input type="number" step="0.01" min="0" name="supplier_price_per_sack" id="supplierPrice" class="form-control" value="" required>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Transport / Handling Cost (PHP)</label>
            <input type="number" step="0.01" min="0" name="transport_handling" id="transportCost" class="form-control" value="0" required>
            <div class="form-text">Included in landed cost and suggested selling price per kg.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Markup per kg (PHP)</label>
            <input type="number" step="0.01" min="0" name="markup_per_kg" id="markupKg" class="form-control" value="0" required>
            <div class="form-text">Additional amount on top of cost per kg.</div>
          </div>
        </div>
        </div>

        <div class="section-title">Receiving Preview</div>
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="preview-tile">
              <div class="preview-label">Unit Size</div>
              <div class="preview-value"><span id="previewUnitWeight">0.00</span> kg/sack</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="preview-tile">
              <div class="preview-label">Total Weight</div>
              <div class="preview-value"><span id="previewTotalKg">0.00</span> kg</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="preview-tile">
              <div class="preview-label">Estimated Amount</div>
              <div class="preview-value">PHP <span id="previewAmount">0.00</span></div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="preview-tile">
              <div class="preview-label">Suggested Price</div>
              <div class="preview-value">PHP <span id="previewSellKg">0.00</span>/kg</div>
            </div>
          </div>
        </div>

        <div class="section-title">Remarks</div>
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label fw-semibold">Note</label>
            <input type="text" name="note" class="form-control" placeholder="Example: Delivered by supplier / received by staff">
          </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3 px-4">
          <i class="fas fa-arrow-down"></i> Receive Stock
        </button>
    </form>
  </div>
</div>

</main>

</div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="supplierModalAlert" class="alert d-none"></div>

        <div class="mb-3">
          <label class="form-label">Supplier Name *</label>
          <input type="text" id="modalSupplierName" class="form-control" placeholder="e.g. ABC Rice Trading">
        </div>

        <div class="mb-3">
          <label class="form-label">Phone (optional)</label>
          <input type="text" id="modalSupplierPhone" class="form-control" placeholder="e.g. 09123456789">
        </div>

        <div class="mb-3">
          <label class="form-label">Address (optional)</label>
          <textarea id="modalSupplierAddress" class="form-control" rows="2" placeholder="e.g. CDO"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="saveSupplierBtn" class="btn btn-primary">Save Supplier</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const supplierModalAlert = document.getElementById('supplierModalAlert');
const productSelect = document.getElementById('productSelect');
const qtySacks = document.getElementById('qtySacks');
const supplierPrice = document.getElementById('supplierPrice');
const transportCost = document.getElementById('transportCost');
const markupKg = document.getElementById('markupKg');

const previewUnitWeight = document.getElementById('previewUnitWeight');
const previewTotalKg = document.getElementById('previewTotalKg');
const previewAmount = document.getElementById('previewAmount');
const previewSellKg = document.getElementById('previewSellKg');

function num(v){
  const n = parseFloat(v);
  return Number.isFinite(n) ? n : 0;
}

function updatePreview(){
  const opt = productSelect.options[productSelect.selectedIndex];
  const unitWeight = num(opt?.getAttribute('data-weight'));
  const sacks = Math.max(0, num(qtySacks.value));
  const priceSack = Math.max(0, num(supplierPrice.value));
  const transport = Math.max(0, num(transportCost.value));
  const markup = Math.max(0, num(markupKg.value));

  const totalKg = sacks * unitWeight;
  const totalAmount = (sacks * priceSack) + transport;
  const landedPerSack = sacks > 0 ? priceSack + (transport / sacks) : 0;
  const costPerKg = unitWeight > 0 ? landedPerSack / unitWeight : 0;
  const suggestedSell = costPerKg + markup;

  previewUnitWeight.textContent = unitWeight.toFixed(2);
  previewTotalKg.textContent = totalKg.toFixed(2);
  previewAmount.textContent = totalAmount.toFixed(2);
  previewSellKg.textContent = suggestedSell.toFixed(2);
}

async function fillLastPriceForProductSupplier(){
  const productId = productSelect?.value || '';
  const supplierId = document.getElementById('supplierSelect')?.value || '';

  if(!productId || !supplierId){
    supplierPrice.value = '';
    updatePreview();
    return;
  }

  const formData = new FormData();
  formData.append('ajax', 'last_supplier_price');
  formData.append('product_id', productId);
  formData.append('supplier_id', supplierId);

  try{
    const res = await fetch('add_stock.php', { method:'POST', body: formData });
    const data = await res.json();

    if(data.ok && data.found && data.price !== null){
      supplierPrice.value = Number(data.price).toFixed(2);
    } else {
      supplierPrice.value = '';
    }
  } catch(err){
    supplierPrice.value = '';
  }

  updatePreview();
}

function showModalAlert(type, msg){
  supplierModalAlert.className = 'alert alert-' + type;
  supplierModalAlert.textContent = msg;
  supplierModalAlert.classList.remove('d-none');
}

document.getElementById('saveSupplierBtn').addEventListener('click', async () => {
  supplierModalAlert.classList.add('d-none');

  const name = document.getElementById('modalSupplierName').value.trim();
  const phone = document.getElementById('modalSupplierPhone').value.trim();
  const address = document.getElementById('modalSupplierAddress').value.trim();

  if(!name){
    showModalAlert('danger', 'Supplier name is required.');
    return;
  }

  const formData = new FormData();
  formData.append('ajax', 'add_supplier');
  formData.append('supplier_name', name);
  formData.append('supplier_phone', phone);
  formData.append('supplier_address', address);

  try{
    const res = await fetch('add_stock.php', { method: 'POST', body: formData });
    const data = await res.json();

    if(!data.ok){
      showModalAlert('danger', data.message || 'Failed to add supplier.');
      return;
    }

    const select = document.getElementById('supplierSelect');
    let opt = select.querySelector('option[value="' + data.supplier_id + '"]');
    if(!opt){
      opt = document.createElement('option');
      opt.value = data.supplier_id;
      opt.textContent = data.supplier_name;
      select.appendChild(opt);
    }
    select.value = data.supplier_id;

    showModalAlert('success', data.message || 'Supplier added!');

    setTimeout(() => {
      const modalEl = document.getElementById('addSupplierModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      modal.hide();

      document.getElementById('modalSupplierName').value = '';
      document.getElementById('modalSupplierPhone').value = '';
      document.getElementById('modalSupplierAddress').value = '';
      supplierModalAlert.classList.add('d-none');
    }, 600);

  } catch(err){
    showModalAlert('danger', 'Network/Server error. Please try again.');
  }
});

[productSelect, qtySacks, supplierPrice, transportCost, markupKg].forEach(el => {
  if(el) el.addEventListener('input', updatePreview);
  if(el) el.addEventListener('change', updatePreview);
});
if(productSelect){
  productSelect.addEventListener('change', fillLastPriceForProductSupplier);
}
const supplierSelectEl = document.getElementById('supplierSelect');
if(supplierSelectEl){
  supplierSelectEl.addEventListener('change', fillLastPriceForProductSupplier);
}
updatePreview();
</script>
</body>
</html>
