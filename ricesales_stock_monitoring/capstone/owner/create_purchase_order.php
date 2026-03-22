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

include '../config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle form submission
if(isset($_POST['create_po'])){
  $supplier_id = (int)($_POST['supplier_id'] ?? 0);
  $order_date = trim($_POST['order_date'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  // Basic validation
  if($supplier_id <= 0 || $order_date === ''){
    header("Location: create_purchase_order.php?error=" . urlencode("Supplier and Order Date are required."));
    exit;
  }

  // Insert into purchase_orders table
  $sql = "INSERT INTO purchase_orders (supplier_id, order_date, notes, status) 
          VALUES (?, ?, ?, 'pending')";

  $stmt = $conn->prepare($sql);
  if(!$stmt){
    die("SQL PREPARE ERROR: " . $conn->error);
  }

  $stmt->bind_param("iss", $supplier_id, $order_date, $notes);
  
  if($stmt->execute()){
    $stmt->close();
    header("Location: create_purchase_order.php?success=" . urlencode("Purchase Order created successfully!"));
    exit;
  } else {
    $stmt->close();
    header("Location: create_purchase_order.php?error=" . urlencode("Failed to create purchase order."));
    exit;
  }
}

// Fetch suppliers for dropdown
$suppliers_sql = "SELECT supplier_id, name FROM suppliers WHERE status='active' ORDER BY name ASC";
$suppliers_result = $conn->query($suppliers_sql);
if(!$suppliers_result){
  die("SQL ERROR: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Purchase Order</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-6">

<div class="card shadow">
<div class="card-header bg-dark text-white">
<h4 class="mb-0"><i class="fa-solid fa-plus-circle me-2"></i>Create Purchase Order</h4>
</div>

<div class="card-body">

<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= h($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if($error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= h($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="create_po" value="1">

<div class="mb-3">
  <label class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
  <select class="form-select" name="supplier_id" required>
    <option value="">-- Select Supplier --</option>
    <?php if($suppliers_result && $suppliers_result->num_rows > 0): ?>
      <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
        <option value="<?= (int)$supplier['supplier_id'] ?>">
          <?= h($supplier['name']) ?>
        </option>
      <?php endwhile; ?>
    <?php else: ?>
      <option disabled>No suppliers found</option>
    <?php endif; ?>
  </select>
</div>

<div class="mb-3">
  <label class="form-label fw-bold">Order Date <span class="text-danger">*</span></label>
  <input type="date" class="form-control" name="order_date" required value="<?= date('Y-m-d') ?>">
</div>

<div class="mb-3">
  <label class="form-label fw-bold">Notes</label>
  <textarea class="form-control" name="notes" rows="3" placeholder="Add any special instructions..."></textarea>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <a href="purchase_orders.php" class="btn btn-secondary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back
  </a>
  <button type="submit" class="btn btn-primary">
    <i class="fa-solid fa-save me-1"></i> Create PO
  </button>
</div>

</form>

</div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
