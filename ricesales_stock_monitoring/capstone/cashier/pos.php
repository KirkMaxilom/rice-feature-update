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
$user_id  = (int)($_SESSION['user_id'] ?? 0);

include '../config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* Preserve only header fields across error refresh */
function pos_save_old($customer_id, $sale_type, $due_date, $discount_type, $discount_name, $discount_idno){
  $_SESSION['pos_old'] = [
    'customer_id'    => (int)$customer_id,
    'sale_type'      => (string)$sale_type,
    'due_date'       => (string)$due_date,
    'discount_type'  => (string)$discount_type,
    'discount_name'  => (string)$discount_name,
    'discount_idno'  => (string)$discount_idno,
  ];
}

function compute_kinsenas_due_date(): string {
  $today = new DateTime();
  $year  = (int)$today->format('Y');
  $month = (int)$today->format('m');
  $day   = (int)$today->format('d');

  $kinsenas = [2, 15, 25, 30];
  $lastDayThisMonth = (int)$today->format('t');

  foreach ($kinsenas as $kday) {
    $realKday = $kday;
    if ($realKday > $lastDayThisMonth) $realKday = $lastDayThisMonth;

    // ✅ strictly greater than today
    if ($day < $realKday) {
      return sprintf('%04d-%02d-%02d', $year, $month, $realKday);
    }
  }

  // If none left this month -> due on 2nd of next month
  $nextMonth = $month + 1;
  $nextYear  = $year;
  if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
  }

  return sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, 2);
}

$products = [];
$sqlProducts = "
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
  ),0) AS stock_kg
FROM products p
LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
WHERE p.archived = 0
GROUP BY p.product_id
ORDER BY p.variety ASC, p.grade ASC
";
$resP = $conn->query($sqlProducts);
if($resP){
  while($row = $resP->fetch_assoc()){
    $row['unit_weight_kg'] = (float)($row['unit_weight_kg'] ?? 0);
    $row['price_per_sack'] = (float)($row['price_per_sack'] ?? 0);
    $row['price_per_kg']   = (float)($row['price_per_kg'] ?? 0);
    $row['stock_kg']       = (float)($row['stock_kg'] ?? 0);
    $products[] = $row;
  }
}

$customers = [];
$resC = $conn->query("SELECT customer_id, first_name, last_name, phone FROM customers ORDER BY created_at DESC");
if($resC){
  while($r = $resC->fetch_assoc()) $customers[] = $r;
}

if(isset($_POST['add_customer'])){
  $fn = trim($_POST['first_name'] ?? '');
  $ln = trim($_POST['last_name'] ?? '');
  $ph = trim($_POST['phone'] ?? '');
  $ad = trim($_POST['address'] ?? '');

  if($fn !== '' && $ln !== ''){
    $stmt = $conn->prepare("INSERT INTO customers (first_name,last_name,phone,address,created_at) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param("ssss", $fn, $ln, $ph, $ad);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: pos.php?success=" . urlencode("Customer added."));
  exit;
}

$err = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

$old = $_SESSION['pos_old'] ?? [];
unset($_SESSION['pos_old']);

$old_customer_id   = (int)($old['customer_id'] ?? 0);
$old_sale_type     = strtolower(trim($old['sale_type'] ?? 'cash'));
$old_due_date      = (string)($old['due_date'] ?? '');

$old_discount_type = strtolower(trim($old['discount_type'] ?? 'none')); // none|pwd|sc
$old_discount_name = (string)($old['discount_name'] ?? '');
$old_discount_idno = (string)($old['discount_idno'] ?? '');

/* If old due date missing and old type is utang, show computed due date in UI */
if($old_sale_type === 'utang' && $old_due_date === ''){
  $old_due_date = compute_kinsenas_due_date();
}

if(isset($_POST['checkout'])){
  $customer_id = (int)($_POST['customer_id'] ?? 0);
  $sale_type   = strtolower(trim($_POST['sale_type'] ?? 'cash')); // cash | utang
  $status      = ($sale_type === 'utang') ? 'unpaid' : 'paid';

  // Discount header inputs (NO DB changes)
  $discount_type = strtolower(trim($_POST['discount_type'] ?? 'none')); // none|pwd|sc
  $discount_name = trim($_POST['discount_name'] ?? '');
  $discount_idno = trim($_POST['discount_idno'] ?? '');

  if(!in_array($discount_type, ['none','pwd','sc'], true)){
    $discount_type = 'none';
  }

  // AUTO due date for utang (NEXT kinsenas only)
  $due_date_post = '';
  if($sale_type === 'utang'){
    $due_date_post = compute_kinsenas_due_date();
  }

  // Items arrays (UPDATED)
  $product_ids = $_POST['product_id'] ?? [];
  $units       = $_POST['unit_type'] ?? [];   // kg | sack
  $qty_inputs  = $_POST['qty'] ?? [];         // number: kg or sacks
  $prices_post = $_POST['unit_price'] ?? [];  // ignored; kept for compatibility/UI

  if($customer_id <= 0){
    pos_save_old($customer_id, $sale_type, $due_date_post, $discount_type, $discount_name, $discount_idno);
    header("Location: pos.php?error=" . urlencode("Please select a customer."));
    exit;
  }
  if(!is_array($product_ids) || count($product_ids) === 0){
    pos_save_old($customer_id, $sale_type, $due_date_post, $discount_type, $discount_name, $discount_idno);
    header("Location: pos.php?error=" . urlencode("Please add at least one item."));
    exit;
  }

  // Build product map for fast lookup + server-trust pricing
  $pmap = [];
  foreach($products as $p){
    $pmap[(int)$p['product_id']] = $p;
  }

  $items = [];
  $gross_total = 0.0;

  for($i=0; $i<count($product_ids); $i++){
    $pid  = (int)($product_ids[$i] ?? 0);
    $unit = strtolower(trim((string)($units[$i] ?? 'kg')));
    $qIn  = (float)($qty_inputs[$i] ?? 0);

    if($pid <= 0 || $qIn <= 0) continue;
    if(!isset($pmap[$pid])) continue;
    if(!in_array($unit, ['kg','sack'], true)) $unit = 'kg';

    $prod = $pmap[$pid];
    $unit_weight_kg = (float)$prod['unit_weight_kg'];

    // Compute qty_kg + trusted unit_price
    if($unit === 'sack'){
      if($unit_weight_kg <= 0){
        pos_save_old($customer_id, $sale_type, $due_date_post, $discount_type, $discount_name, $discount_idno);
        header("Location: pos.php?error=" . urlencode("Product sack size is missing. Please set unit_weight_kg in Products."));
        exit;
      }
      $qty_kg = $qIn * $unit_weight_kg;
      $unit_price = (float)$prod['price_per_sack']; // price per sack
    } else {
      $qty_kg = $qIn;
      $unit_price = (float)$prod['price_per_kg'];   // price per kg
    }

    // Basic sanity
    if($qty_kg <= 0) continue;
    if($unit_price < 0) $unit_price = 0;

    $line = $qIn * $unit_price; // line total uses the selling unit
    $gross_total += $line;

    $items[] = [
      'product_id' => $pid,
      'unit_type'  => $unit,     // kg|sack (for receipt/UI memory)
      'qty_input'  => $qIn,      // qty in selling unit
      'qty_kg'     => $qty_kg,   // qty for inventory deduction
      'unit_price' => $unit_price,
      'line_total' => $line
    ];
  }

  if(count($items) === 0){
    pos_save_old($customer_id, $sale_type, $due_date_post, $discount_type, $discount_name, $discount_idno);
    header("Location: pos.php?error=" . urlencode("No valid items found. Check quantities."));
    exit;
  }

  // ✅ Discount computation (server-side)
  $discRate = ($discount_type === 'pwd' || $discount_type === 'sc') ? 0.20 : 0.0;
  $discount_amount = round($gross_total * $discRate, 2);
  $net_total = round($gross_total - $discount_amount, 2);
  if($net_total < 0) $net_total = 0;

  $conn->begin_transaction();

  try {
    // 1) Create sale (save NET total)
    $stmt = $conn->prepare("
      INSERT INTO sales (user_id, customer_id, sale_date, total_amount, status, created_at)
      VALUES (?, ?, NOW(), ?, ?, NOW())
    ");
    $stmt->bind_param("iids", $user_id, $customer_id, $net_total, $status);
    $stmt->execute();
    $sale_id = $stmt->insert_id;
    $stmt->close();

    // 2) Insert sales_items + inventory OUT
    foreach($items as $it){

      // Check stock (computed from inventory_transactions)
      $check = $conn->prepare("
        SELECT
          IFNULL(SUM(
            CASE
              WHEN LOWER(type)='in' THEN qty_kg
              WHEN LOWER(type)='out' THEN -qty_kg
              WHEN LOWER(type)='adjust' THEN qty_kg
              ELSE 0
            END
          ),0) AS stock_now
        FROM inventory_transactions
        WHERE product_id = ?
      ");
      $check->bind_param("i", $it['product_id']);
      $check->execute();
      $stock_now = (float)($check->get_result()->fetch_assoc()['stock_now'] ?? 0);
      $check->close();

      if($stock_now < $it['qty_kg']){
        throw new Exception("Insufficient stock for product ID {$it['product_id']}. Available: ".number_format($stock_now,2)." kg");
      }

      // sales_items (keep schema: qty_kg + unit_price + line_total)
      $stmt = $conn->prepare("
        INSERT INTO sales_items (sale_id, product_id, qty_kg, unit_price, line_total)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("iiddd", $sale_id, $it['product_id'], $it['qty_kg'], $it['unit_price'], $it['line_total']);
      $stmt->execute();
      $stmt->close();

      // inventory OUT transaction
      $unitLabel = ($it['unit_type'] === 'sack') ? ($it['qty_input']." sack(s)") : (number_format($it['qty_input'],2)." kg");
      $note = "Sale #{$sale_id} - {$unitLabel} deducted";
      $stmt = $conn->prepare("
        INSERT INTO inventory_transactions
          (product_id, qty_kg, reference_id, reference_type, type, note, created_at)
        VALUES (?, ?, ?, 'sale', 'out', ?, NOW())
      ");
      $stmt->bind_param("idis", $it['product_id'], $it['qty_kg'], $sale_id, $note);
      $stmt->execute();
      $stmt->close();
    }

    // ✅ Save discount details in SESSION for receipt printing (NO DB change)
    if(!isset($_SESSION['sale_discount'])) $_SESSION['sale_discount'] = [];
    $_SESSION['sale_discount'][$sale_id] = [
      'type'   => $discount_type,
      'rate'   => $discRate,
      'gross'  => round($gross_total, 2),
      'disc'   => round($discount_amount, 2),
      'net'    => round($net_total, 2),
      'name'   => $discount_name,
      'idno'   => $discount_idno,
      'units'  => array_map(function($x){
        return [
          'product_id' => (int)$x['product_id'],
          'unit_type'  => (string)$x['unit_type'],
          'qty_input'  => (float)$x['qty_input'],
        ];
      }, $items),
    ];

    // 3) If utang -> create AR + push log (use NET total)
    if($sale_type === 'utang'){
      $amount_paid = 0.0;
      $balance     = $net_total;

      $due_date = $due_date_post ?: null;
      if($due_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)){
        $due_date = null;
      }

      $stmt = $conn->prepare("
        INSERT INTO account_receivable
          (sales_id, customer_id, total_amount, amount_paid, balance, due_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'unpaid', NOW())
      ");
      $stmt->bind_param("iiddds", $sale_id, $customer_id, $net_total, $amount_paid, $balance, $due_date);
      $stmt->execute();
      $stmt->close();

      $message = "Hi! You have an unpaid balance of ₱".number_format($balance,2)." for Sale #{$sale_id}. Please settle it by ".($due_date ? $due_date : 'the due date').". Thank you!";

      $stmt = $conn->prepare("
        INSERT INTO push_notif_logs (payment_id, customer_id, message, sent_at, status)
        VALUES (NULL, ?, ?, NOW(), 'SENT')
      ");
      $stmt->bind_param("is", $customer_id, $message);
      $stmt->execute();
      $stmt->close();
    }

    $conn->commit();

    // activity log
    $desc = "Created sale #{$sale_id} (".$status.") gross ₱".number_format($gross_total,2)." disc ₱".number_format($discount_amount,2)." net ₱".number_format($net_total,2);
    $stmt = $conn->prepare("
      INSERT INTO activity_logs (user_id, activity_type, description, created_at)
      VALUES (?, 'SALE_CREATE', ?, NOW())
    ");
    $stmt->bind_param("is", $user_id, $desc);
    $stmt->execute();
    $stmt->close();

    header("Location: receipt.php?sale_id=".(int)$sale_id);
    exit;

  } catch (Exception $e){
    $conn->rollback();
    pos_save_old($customer_id, $sale_type, $due_date_post, $discount_type, $discount_name, $discount_idno);
    header("Location: pos.php?error=" . urlencode($e->getMessage()));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sale | Cashier</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="../css/layout.css" rel="stylesheet">
<style>
.pos-card{
  border: 1px solid #e9ecef;
  border-radius: 14px;
}
.pos-section-title{
  font-size: .82rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 700;
  margin-bottom: 8px;
}
.items-wrap{
  max-height: 360px;
  overflow: auto;
  border-radius: 10px;
}
.items-wrap thead th{
  position: sticky;
  top: 0;
  z-index: 2;
  white-space: nowrap;
}
.items-table td, .items-table th{
  vertical-align: middle;
}
.items-table .prod-select{
  min-width: 230px;
}
.items-table .unit-select{
  min-width: 100px;
}
.items-table .qty-input{
  min-width: 82px;
  text-align: center;
}
.items-table .price-input{
  min-width: 92px;
  text-align: right;
  background: #f8f9fa;
}
.items-table .line-text,
.items-table .deduct-text{
  white-space: nowrap;
}
.stock-pill{
  min-width: 88px;
  display: inline-block;
  text-align: center;
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

    <!-- MAIN -->
    <main class="col-lg-10 ms-sm-auto px-4 main-content">
      <div class="py-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h3 class="fw-bold mb-1">Sale</h3>
            <div class="text-muted">Create sales (Cash or Utang). Stock updates automatically.</div>
          </div>
        </div>

        <?php if($success): ?>
          <div class="alert alert-success py-2"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if($err): ?>
          <div class="alert alert-danger py-2"><?= h($err) ?></div>
        <?php endif; ?>

        <div class="row g-4">
          <!-- LEFT: SALE FORM -->
          <div class="col-12 col-xl-7">
            <div class="card modern-card pos-card">
              <div class="card-body">
                <form method="POST" id="saleForm">

                  <div class="alert alert-light border mb-3 py-2">
                    <div class="small">
                      Select customer and sale type first, then add items. Checkout is blocked if any line exceeds available stock.
                    </div>
                  </div>

                  <div class="pos-section-title">Customer & Payment</div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Customer</label>
                    <select name="customer_id" class="form-select" required>
                      <option value="">Select customer</option>
                      <?php foreach($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>"
                          <?= ((int)$c['customer_id'] === $old_customer_id) ? 'selected' : '' ?>>
                          <?= h($c['first_name'].' '.$c['last_name']) ?><?= $c['phone'] ? ' - '.h($c['phone']) : '' ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <div class="small text-muted mt-1">No customer yet? Add on the right panel.</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Sale Type</label>
                    <select name="sale_type" id="sale_type" class="form-select" required onchange="toggleDueDate()">
                      <option value="cash" <?= $old_sale_type==='cash'?'selected':'' ?>>Cash (Paid)</option>
                      <option value="utang" <?= $old_sale_type==='utang'?'selected':'' ?>>Utang (Unpaid)</option>
                    </select>
                  </div>

                  <!-- AUTO DUE DATE DISPLAY -->
                  <div class="mb-3 d-none" id="dueDateWrap">
                    <label class="form-label fw-semibold">Auto Due Date (Kinsenas)</label>
                    <input type="text" class="form-control" id="dueDateText" readonly value="<?= h($old_due_date ?: '—') ?>">
                  </div>

                  <!-- ✅ DISCOUNT (PWD/SC) -->
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Discount</label>
                    <select name="discount_type" id="discount_type" class="form-select" onchange="toggleDiscountFields(); recalcAll();">
                      <option value="none" <?= $old_discount_type==='none'?'selected':'' ?>>None</option>
                      <option value="pwd"  <?= $old_discount_type==='pwd'?'selected':''  ?>>PWD (20%)</option>
                      <option value="sc"   <?= $old_discount_type==='sc'?'selected':''   ?>>Senior Citizen (20%)</option>
                    </select>
                    <div class="small text-muted mt-1">Use only if customer is eligible.</div>
                  </div>

                  <div class="row g-2 mb-3 d-none" id="discountFields">
                    <div class="col-md-6">
                      <label class="form-label">Discount Name (optional)</label>
                      <input class="form-control" name="discount_name" id="discount_name" value="<?= h($old_discount_name) ?>" placeholder="e.g., Juan Dela Cruz">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">ID No. (optional)</label>
                      <input class="form-control" name="discount_idno" id="discount_idno" value="<?= h($old_discount_idno) ?>" placeholder="PWD/SC ID">
                    </div>
                  </div>

                  <hr>

                  <div class="pos-section-title">Items</div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Items</h5>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="addRow()">
                      <i class="fa-solid fa-plus me-1"></i>Add Item
                    </button>
                  </div>

                  <div class="items-wrap">
                    <table class="table table-sm align-middle items-table" id="itemsTable">
                      <thead class="table-dark">
                        <tr>
                          <th style="width:34%">Product</th>
                          <th style="width:12%">Stock</th>
                          <th style="width:12%">Unit</th>
                          <th style="width:10%">Qty</th>
                          <th style="width:10%" class="text-end">Price</th>
                          <th style="width:10%" class="text-end">Deduct (kg)</th>
                          <th style="width:10%" class="text-end">Line</th>
                          <th style="width:6%"></th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                  <div class="small text-muted mt-2">
                    Qty follows selected unit: <b>Per Kilo</b> = kg, <b>Per Sack</b> = number of sacks.
                  </div>

                  <!-- ✅ TOTALS (gross, discount, net) -->
                  <div id="stockWarningBox" class="alert alert-danger py-2 d-none mt-2 mb-0"></div>
                  <div class="d-flex justify-content-end mt-2">
                    <div class="text-end">
                      <div class="text-muted">Subtotal</div>
                      <div class="h5 fw-bold mb-2">₱ <span id="subTotal">0.00</span></div>

                      <div class="text-muted">PWD/SC Discount</div>
                      <div class="h6 fw-bold mb-2 text-danger">- ₱ <span id="discountAmt">0.00</span></div>

                      <div class="text-muted">Total Amount (Payable)</div>
                      <div class="h3 fw-bold mb-0">₱ <span id="grandTotal">0.00</span></div>
                    </div>
                  </div>

                  <input type="hidden" name="checkout" value="1">
                  <button type="submit" id="checkoutBtn" class="btn btn-dark w-100 mt-3">
                    <i class="fa-solid fa-check me-1"></i> Checkout
                  </button>

                  <div class="small text-muted mt-2">
                    Stock is validated before saving. If insufficient stock, sale will not be recorded.
                  </div>

                </form>
              </div>
            </div>
          </div>

          <!-- RIGHT: QUICK ADD CUSTOMER -->
          <div class="col-12 col-xl-5">
            <div class="card modern-card pos-card">
              <div class="card-body">
                <h5 class="fw-bold mb-2">Quick Add Customer</h5>
                <form method="POST">
                  <input type="hidden" name="add_customer" value="1">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label">First Name</label>
                      <input class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Last Name</label>
                      <input class="form-control" name="last_name" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Phone</label>
                      <input class="form-control" name="phone">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Address</label>
                      <input class="form-control" name="address">
                    </div>
                    <div class="col-12">
                      <button class="btn btn-outline-dark w-100 mt-2">
                        <i class="fa-solid fa-user-plus me-1"></i> Add Customer
                      </button>
                    </div>
                  </div>
                </form>

                <hr>

                <div class="small text-muted">
                  Tip: Use consistent customer names for accurate utang tracking & forecasting later.
                </div>
              </div>
            </div>
          </div>

        </div><!-- row -->
      </div>
    </main>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const products = <?= json_encode($products) ?>;

function peso(n){
  return (Number(n||0)).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
}

function formatISODate(y, m, d){
  const mm = String(m).padStart(2,'0');
  const dd = String(d).padStart(2,'0');
  return `${y}-${mm}-${dd}`;
}

function computeKinsenasDueDateClient(){
  const now = new Date();
  const year = now.getFullYear();
  const monthIndex = now.getMonth();
  const day = now.getDate();

  const kinsenas = [2, 15, 25, 30];
  const lastDay = new Date(year, monthIndex + 1, 0).getDate();

  for (const k of kinsenas){
    let kk = k;
    if (kk > lastDay) kk = lastDay;
    if(day < kk){
      return formatISODate(year, monthIndex + 1, kk);
    }
  }

  let ny = year, nm = monthIndex + 2;
  if(nm === 13){ nm = 1; ny = year + 1; }
  return formatISODate(ny, nm, 2);
}

function toggleDueDate(){
  const t = document.getElementById('sale_type').value;
  const wrap = document.getElementById('dueDateWrap');
  wrap.classList.toggle('d-none', t !== 'utang');

  if(t === 'utang'){
    const due = computeKinsenasDueDateClient();
    const dueInput = document.getElementById('dueDateText');
    if(dueInput) dueInput.value = due;
  }
}

function toggleDiscountFields(){
  const dt = document.getElementById('discount_type').value;
  const fields = document.getElementById('discountFields');
  fields.classList.toggle('d-none', (dt !== 'pwd' && dt !== 'sc'));
}

function addRow(){
  const tbody = document.querySelector('#itemsTable tbody');
  const tr = document.createElement('tr');

  // Product select
  const tdProd = document.createElement('td');
  const sel = document.createElement('select');
  sel.name = "product_id[]";
  sel.className = "form-select form-select-sm prod-select";
  sel.required = true;

  sel.innerHTML = `<option value="">Select product</option>` + products.map(p => {
    const tag = `${p.variety} - ${p.grade} (${peso(p.unit_weight_kg)}kg/sack)`;
    return `<option value="${p.product_id}"
      data-stock="${p.stock_kg}"
      data-w="${p.unit_weight_kg}"
      data-pkg="${p.price_per_kg}"
      data-psack="${p.price_per_sack}"
    >${tag}</option>`;
  }).join('');

  sel.onchange = () => fillRow(tr);
  tdProd.appendChild(sel);

  // Stock badge
  const tdStock = document.createElement('td');
  tdStock.innerHTML = `<span class="badge bg-secondary stock-pill">0.00 kg</span>`;

  // Unit type
  const tdUnit = document.createElement('td');
  const unitSel = document.createElement('select');
  unitSel.name = "unit_type[]";
  unitSel.className = "form-select form-select-sm unit-select";
  unitSel.innerHTML = `
    <option value="kg">Per Kilo</option>
    <option value="sack">Per Sack</option>
  `;
  unitSel.onchange = () => unitChanged(tr);
  tdUnit.appendChild(unitSel);

  // Qty input (generic)
  const tdQty = document.createElement('td');
  const qty = document.createElement('input');
  qty.type = "number";
  qty.step = "0.01";
  qty.min = "0";
  qty.name = "qty[]";
  qty.className = "form-control form-control-sm qty-input";
  qty.required = true;
  qty.placeholder = "0.00";
  qty.oninput = () => recalc(tr);
  tdQty.appendChild(qty);

  // Price (readonly)
  const tdPrice = document.createElement('td');
  const price = document.createElement('input');
  price.type = "number";
  price.step = "0.01";
  price.min = "0";
  price.name = "unit_price[]";
  price.className = "form-control form-control-sm price-input";
  price.readOnly = true;
  price.tabIndex = -1;
  tdPrice.appendChild(price);

  // Line total
  const tdDeduct = document.createElement('td');
  tdDeduct.className = "text-end";
  tdDeduct.innerHTML = `<span class="deduct-text text-muted">0.00</span>`;

  // Line total
  const tdLine = document.createElement('td');
  tdLine.className = "text-end";
  tdLine.innerHTML = `<span class="fw-bold line-text">₱0.00</span>`;

  // Remove btn
  const tdX = document.createElement('td');
  const btn = document.createElement('button');
  btn.type = "button";
  btn.className = "btn btn-sm btn-outline-danger";
  btn.innerHTML = `<i class="fa-solid fa-xmark"></i>`;
  btn.onclick = () => { tr.remove(); recalcAll(); };
  tdX.appendChild(btn);

  tr.appendChild(tdProd);
  tr.appendChild(tdStock);
  tr.appendChild(tdUnit);
  tr.appendChild(tdQty);
  tr.appendChild(tdPrice);
  tr.appendChild(tdDeduct);
  tr.appendChild(tdLine);
  tr.appendChild(tdX);

  tbody.appendChild(tr);

  // default row values
  unitChanged(tr);
}

function fillRow(tr){
  const sel = tr.querySelector('select[name="product_id[]"]');
  const opt = sel.options[sel.selectedIndex];

  const stock = Number(opt.getAttribute('data-stock') || 0);
  let cls = 'bg-secondary';
  if(stock > 0 && stock < 30) cls = 'bg-warning text-dark';
  if(stock >= 30) cls = 'bg-info text-dark';
  tr.children[1].innerHTML = `<span class="badge ${cls} stock-pill">${peso(stock)} kg</span>`;

  unitChanged(tr);
}

function unitChanged(tr){
  const prodSel = tr.querySelector('select[name="product_id[]"]');
  const opt = prodSel.options[prodSel.selectedIndex];

  const unitSel = tr.querySelector('select[name="unit_type[]"]');
  const unit = unitSel.value;

  const w = Number(opt.getAttribute('data-w') || 0);
  const pKg = Number(opt.getAttribute('data-pkg') || 0);
  const pSack = Number(opt.getAttribute('data-psack') || 0);

  const qtyInput = tr.querySelector('input[name="qty[]"]');
  const priceInput = tr.querySelector('input[name="unit_price[]"]');

  if(unit === 'sack'){
    qtyInput.step = "1";
    qtyInput.placeholder = "0";
    priceInput.value = pSack ? pSack : 0;
    // if user already typed decimals, keep but it will be treated as float; server will still recompute
  } else {
    qtyInput.step = "0.01";
    qtyInput.placeholder = "0.00";
    priceInput.value = pKg ? pKg : 0;
  }

  recalc(tr);
}

function recalc(tr){
  tr.classList.remove('table-danger');
  const qty = Number(tr.querySelector('input[name="qty[]"]').value || 0);
  const prc = Number(tr.querySelector('input[name="unit_price[]"]').value || 0);
  const unit = tr.querySelector('select[name="unit_type[]"]').value;
  const prodSel = tr.querySelector('select[name="product_id[]"]');
  const opt = prodSel.options[prodSel.selectedIndex];

  const stock = Number(opt?.getAttribute('data-stock') || 0);
  const w = Number(opt?.getAttribute('data-w') || 0);
  const requiredKg = (unit === 'sack') ? (qty * w) : qty;
  const line = qty * prc;

  tr.children[5].innerHTML = `<span class="fw-semibold">${peso(requiredKg)}</span>`;
  tr.children[6].innerHTML = `<span class="fw-bold">₱${peso(line)}</span>`;

  if(requiredKg > stock){
    tr.classList.add('table-danger');
  }
  recalcAll();
}

function recalcAll(){
  let subtotal = 0;
  let hasStockError = false;
  let hasAnyRow = false;
  document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
    const pid = Number(tr.querySelector('select[name="product_id[]"]').value || 0);
    const qty = Number(tr.querySelector('input[name="qty[]"]').value || 0);
    const prc = Number(tr.querySelector('input[name="unit_price[]"]').value || 0);
    const unit = tr.querySelector('select[name="unit_type[]"]').value;
    const prodSel = tr.querySelector('select[name="product_id[]"]');
    const opt = prodSel.options[prodSel.selectedIndex];
    const stock = Number(opt?.getAttribute('data-stock') || 0);
    const w = Number(opt?.getAttribute('data-w') || 0);
    const requiredKg = (unit === 'sack') ? (qty * w) : qty;

    if(pid > 0 && qty > 0) hasAnyRow = true;
    if(pid > 0 && qty > 0 && requiredKg > stock) hasStockError = true;

    subtotal += qty * prc;
  });

  const dt = document.getElementById('discount_type').value;
  const rate = (dt === 'pwd' || dt === 'sc') ? 0.20 : 0.0;
  const disc = +(subtotal * rate).toFixed(2);
  const net  = Math.max(0, +(subtotal - disc).toFixed(2));

  document.getElementById('subTotal').innerText = peso(subtotal);
  document.getElementById('discountAmt').innerText = peso(disc);
  document.getElementById('grandTotal').innerText = peso(net);

  const warn = document.getElementById('stockWarningBox');
  const checkoutBtn = document.getElementById('checkoutBtn');
  if(hasStockError){
    warn.classList.remove('d-none');
    warn.innerText = "Some rows exceed available stock. Adjust quantity before checkout.";
  } else {
    warn.classList.add('d-none');
    warn.innerText = "";
  }

  if(checkoutBtn){
    checkoutBtn.disabled = hasStockError || !hasAnyRow || net <= 0;
  }
}

// Start with 1 row
addRow();
toggleDueDate();
toggleDiscountFields();
recalcAll();
</script>

</body>
</html>
