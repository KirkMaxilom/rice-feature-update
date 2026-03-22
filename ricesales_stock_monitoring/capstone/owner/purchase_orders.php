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

$username = $_SESSION['username'] ?? 'Owner';

// Get metrics
$totalPosRow = $conn->query("SELECT COUNT(*) AS cnt FROM purchase_orders");
$totalPos = $totalPosRow->fetch_assoc()['cnt'];

$pendingPosRow = $conn->query("SELECT COUNT(*) AS cnt FROM purchase_orders WHERE status='pending'");
$pendingPos = $pendingPosRow->fetch_assoc()['cnt'];

$orderedPosRow = $conn->query("SELECT COUNT(*) AS cnt FROM purchase_orders WHERE status='ordered'");
$orderedPos = $orderedPosRow->fetch_assoc()['cnt'];

$receivedPosRow = $conn->query("SELECT COUNT(*) AS cnt FROM purchase_orders WHERE status='received'");
$receivedPos = $receivedPosRow->fetch_assoc()['cnt'];

$avgAmountRow = $conn->query("SELECT IFNULL(AVG(total_amount), 0) AS avg_amount FROM purchase_orders");
$avgAmount = $avgAmountRow->fetch_assoc()['avg_amount'];

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if($searchQuery){
  $whereConditions[] = "(po.po_id LIKE ? OR s.name LIKE ?)";
  $searchWildcard = "%$searchQuery%";
  $params[] = $searchWildcard;
  $params[] = $searchWildcard;
  $paramTypes .= 'ss';
}

if($statusFilter){
  $whereConditions[] = "po.status = ?";
  $params[] = $statusFilter;
  $paramTypes .= 's';
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch active suppliers for modal dropdown
$suppliersResult = $conn->query("SELECT supplier_id, name FROM suppliers WHERE status='active' ORDER BY name ASC");

// SELECT query joining purchase_orders + suppliers
$sql = "
  SELECT 
    po.po_id,
    s.name AS supplier_name,
    po.order_date,
    po.status,
    po.total_amount
  FROM purchase_orders po
  INNER JOIN suppliers s ON po.supplier_id = s.supplier_id
  $whereClause
  ORDER BY po.po_id DESC
";

$stmt = $conn->prepare($sql);
if(!$stmt){
  die("SQL PREPARE ERROR: " . $conn->error);
}

if(!empty($params)){
  $stmt->bind_param($paramTypes, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Purchase Orders | Owner</title>
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

<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4">

<!-- Dashboard Metrics -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Total POs</div>
            <h4 class="fw-bold mb-0"><?= $totalPos ?></h4>
          </div>
          <i class="fa-solid fa-receipt text-primary" style="font-size: 2rem; opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Pending</div>
            <h4 class="fw-bold mb-0 text-warning"><?= $pendingPos ?></h4>
          </div>
          <i class="fa-solid fa-clock text-warning" style="font-size: 2rem; opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Received</div>
            <h4 class="fw-bold mb-0 text-success"><?= $receivedPos ?></h4>
          </div>
          <i class="fa-solid fa-circle-check text-success" style="font-size: 2rem; opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Avg. Order Value</div>
            <h4 class="fw-bold mb-0">₱<?= number_format($avgAmount, 0) ?></h4>
          </div>
          <i class="fa-solid fa-money-bill text-success" style="font-size: 2rem; opacity: 0.3;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<div>
<h3 class="fw-bold mb-1">Purchase Orders</h3>
<div class="text-muted">View and manage supplier purchase orders.</div>
</div>
<button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#createPOModal">
<i class="fa-solid fa-plus-circle me-1"></i> Add Purchase Order
</button>
</div>

<!-- Search & Filter Section -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-bold">Search</label>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="PO#, Supplier name..." value="<?= h($_GET['search'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="ordered" <?= ($_GET['status'] ?? '') === 'ordered' ? 'selected' : '' ?>>Ordered</option>
          <option value="received" <?= ($_GET['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
          <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
          <i class="fa-solid fa-magnifying-glass me-1"></i> Filter
        </button>
        <a href="purchase_orders.php" class="btn btn-sm btn-outline-secondary">
          <i class="fa-solid fa-reset"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<div class="card modern-card">
<div class="card-body table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
<thead class="table-light">
<tr>
<th>PO ID</th>
<th>Supplier</th>
<th>Order Date</th>
<th>Status</th>
<th>Total Amount</th>
<th class="text-center">Action</th>
</tr>
</thead>
<tbody>
<?php if($result && $result->num_rows > 0): ?>
  <?php while($row = $result->fetch_assoc()): ?>
  <tr>
    <td class="fw-bold"><?= h($row['po_id']) ?></td>
    <td><?= h($row['supplier_name']) ?></td>
    <td><?= h(date('M d, Y', strtotime($row['order_date']))) ?></td>
    <td>
      <?php 
        $status = strtolower($row['status']);
        $badge_class = '';
        switch($status){
          case 'pending': $badge_class = 'bg-warning text-dark'; break;
          case 'ordered': $badge_class = 'bg-info'; break;
          case 'received': $badge_class = 'bg-success'; break;
          case 'cancelled': $badge_class = 'bg-danger'; break;
          default: $badge_class = 'bg-secondary';
        }
      ?>
      <span class="badge <?= $badge_class ?>"><?= h(ucfirst($status)) ?></span>
    </td>
    <td class="text-end fw-bold">₱<?= number_format($row['total_amount'], 2) ?></td>
    <td class="text-center">
      <button class="btn btn-sm btn-info me-1" type="button" onclick="viewPODetails(<?= (int)$row['po_id'] ?>, '<?= h($row['supplier_name']) ?>')">
        <i class="fa-solid fa-eye me-1"></i> View
      </button>
      <?php if($status !== 'received'): ?>
        <button class="btn btn-sm btn-success" onclick="markReceived(<?= (int)$row['po_id'] ?>)">
          <i class="fa-solid fa-check me-1"></i> Mark Received
        </button>
      <?php else: ?>
        <span class="text-muted small">✓ Received</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
<?php else: ?>
  <tr><td colspan="6" class="text-center text-muted py-4">No purchase orders found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</main>

</div>
</div>

<!-- CREATE PO MODAL -->
<div class="modal fade" id="createPOModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2"></i>Create Purchase Order</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="createPOForm" onsubmit="createPO(event)">
<div class="modal-body">
  <!-- PO Header Section -->
  <div class="section-title mb-3 pb-2 border-bottom">
    <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Order Information</h6>
  </div>
  
  <div class="mb-3">
    <label class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
    <select class="form-select" id="newPoSupplier" required>
      <option value="">-- Select Supplier --</option>
      <?php if($suppliersResult && $suppliersResult->num_rows > 0): ?>
        <?php 
        $suppliersResult->data_seek(0);
        while($supplier = $suppliersResult->fetch_assoc()): 
        ?>
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
    <input type="date" class="form-control" id="newPoOrderDate" required value="<?= date('Y-m-d') ?>">
  </div>
  
  <div class="mb-0">
    <label class="form-label fw-bold">Notes</label>
    <textarea class="form-control" id="newPoNotes" rows="2" placeholder="Add any special instructions..."></textarea>
  </div>

  <!-- Items Section -->
  <div class="section-title mt-4 mb-3 pb-2 border-bottom">
    <h6 class="mb-0"><i class="fa-solid fa-box me-2"></i>Order Items</h6>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-5">
      <label class="form-label fw-bold">Product <span class="text-danger">*</span></label>
      <select class="form-select form-select-sm" id="itemProductId" required>
        <option value="">-- Select Product --</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
      <input type="number" class="form-control form-control-sm" id="itemQuantity" 
             placeholder="Qty" min="0.1" step="0.01" required>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-bold">Unit Price <span class="text-danger">*</span></label>
      <input type="number" class="form-control form-control-sm" id="itemPrice" 
             placeholder="Price" min="0" step="0.01" required>
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-sm btn-outline-primary w-100" id="addItemBtn" 
              onclick="addItemToForm(event)" title="Add item to order">
        <i class="fa-solid fa-plus"></i>
      </button>
    </div>
  </div>

  <!-- Items Table -->
  <div class="table-responsive mb-3">
    <table class="table table-sm table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 35%">Product</th>
          <th style="width: 15%" class="text-center">Qty</th>
          <th style="width: 20%" class="text-end">Unit Price</th>
          <th style="width: 20%" class="text-end">Subtotal</th>
          <th style="width: 10%" class="text-center">Action</th>
        </tr>
      </thead>
      <tbody id="poItemsTableBody">
        <tr id="noItemsRow">
          <td colspan="5" class="text-center text-muted py-3">
            <small>No items added yet</small>
          </td>
        </tr>
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td colspan="3" class="text-end fw-bold">Total Amount:</td>
          <td class="text-end fw-bold" id="totalAmountDisplay">₱0.00</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Hidden field for items JSON -->
  <input type="hidden" id="poItemsJson" name="items" value="[]">
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary" id="submitPoBtn">
    <i class="fa-solid fa-save me-1"></i> Create PO
  </button>
</div>
</form>
</div>
</div>
</div>

<!-- PO DETAILS MODAL -->
<div class="modal fade" id="poDetailsModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="poModalTitle">Purchase Order Details</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
  <!-- Supplier Info -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted">Supplier</div>
        <div class="fw-bold" id="poSupplierName">-</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted">Order Date</div>
        <div class="fw-bold" id="poOrderDate">-</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted">Status</div>
        <div class="fw-bold" id="poStatus">-</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted">Total Amount</div>
        <div class="fw-bold" id="poTotalAmount">-</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted d-flex justify-content-between">
          <span>Rating</span>
          <button class="btn btn-sm btn-outline-secondary p-0" onclick="toggleEditRating()" id="editRatingBtn" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-pen" style="font-size: 0.7rem;"></i>
          </button>
        </div>
        <div id="poRatingDisplay" class="fw-bold">
          <span id="poRatingValue">-</span> <i class="fa-solid fa-star" style="color: #FFC107;"></i>
        </div>
        <div id="poRatingEdit" style="display: none; margin-top: 8px;">
          <div class="star-rating" id="ratingStarsEdit">
            <span onclick="setStarRating(1)" class="me-1" style="cursor: pointer; font-size: 1.2rem; color: #DDD;"><i class="fa-solid fa-star"></i></span>
            <span onclick="setStarRating(2)" class="me-1" style="cursor: pointer; font-size: 1.2rem; color: #DDD;"><i class="fa-solid fa-star"></i></span>
            <span onclick="setStarRating(3)" class="me-1" style="cursor: pointer; font-size: 1.2rem; color: #DDD;"><i class="fa-solid fa-star"></i></span>
            <span onclick="setStarRating(4)" class="me-1" style="cursor: pointer; font-size: 1.2rem; color: #DDD;"><i class="fa-solid fa-star"></i></span>
            <span onclick="setStarRating(5)" class="me-1" style="cursor: pointer; font-size: 1.2rem; color: #DDD;"><i class="fa-solid fa-star"></i></span>
            <input type="hidden" id="poRatingInput" value="0">
          </div>
          <div class="mt-2">
            <textarea class="form-control form-control-sm mb-2" id="poCommentInput" placeholder="Add a comment..." rows="2"></textarea>
            <button class="btn btn-sm btn-success" onclick="saveRating()">Save</button>
            <button class="btn btn-sm btn-secondary" onclick="toggleEditRating()">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notes -->
  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong>Notes:</strong>
      <button class="btn btn-sm btn-outline-secondary" onclick="toggleEditNotes()" id="editNotesBtn">
        <i class="fa-solid fa-pen"></i> Edit
      </button>
    </div>
    <p id="poNotes" class="text-muted">-</p>
    <textarea class="form-control form-control-sm" id="poNotesEdit" rows="3" style="display:none;"></textarea>
    <div id="notesBtnGroup" style="display:none; margin-top: 8px;">
      <button class="btn btn-sm btn-success" onclick="saveNotes()">
        <i class="fa-solid fa-check me-1"></i> Save
      </button>
      <button class="btn btn-sm btn-secondary" onclick="toggleEditNotes()">Cancel</button>
    </div>
  </div>

  <!-- PO Items -->
  <div class="border-top pt-3">
    <h6 class="mb-3"><i class="fa-solid fa-box me-2"></i>Items in this Order</h6>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th class="text-center">Quantity</th>
            <th class="text-end">Unit Price</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody id="poItemsTable">
          <tr><td colspan="5" class="text-center text-muted">Loading items...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentPOId = null;

function viewPODetails(poId, supplierName) {
  currentPOId = poId;
  document.getElementById('poModalTitle').textContent = 'PO #' + poId + ' - ' + supplierName;
  
  // Reset edit mode
  document.getElementById('poNotes').style.display = 'block';
  document.getElementById('poNotesEdit').style.display = 'none';
  document.getElementById('editNotesBtn').style.display = 'inline-block';
  document.getElementById('notesBtnGroup').style.display = 'none';
  
  // Fetch PO details and items via AJAX
  const url = 'get_po_details.php?po_id=' + encodeURIComponent(poId);
  console.log('Fetching PO details from:', url);
  
  fetch(url)
    .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error('HTTP error, status = ' + response.status);
      }
      return response.text();
    })
    .then(text => {
      console.log('Raw response:', text);
      try {
        const data = JSON.parse(text);
        if(data.success) {
          document.getElementById('poSupplierName').textContent = data.po.supplier_name;
          document.getElementById('poOrderDate').textContent = new Date(data.po.order_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
          document.getElementById('poStatus').innerHTML = '<span class="badge bg-' + (data.po.status === 'received' ? 'success' : data.po.status === 'ordered' ? 'info' : 'warning') + '">' + data.po.status.toUpperCase() + '</span>';
          document.getElementById('poTotalAmount').textContent = '₱' + parseFloat(data.po.total_amount).toFixed(2);
          
          const notes = data.po.notes || '(No notes)';
          document.getElementById('poNotes').textContent = notes;
          document.getElementById('poNotesEdit').value = data.po.notes || '';
          
          // Populate items table
          loadPOItems(data.items);
          
          // Handle rating display based on status
          updateRatingUI(data.po);
          
          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('poDetailsModal'));
          modal.show();
        } else {
          alert('Error: ' + (data.message || 'Failed to load PO details'));
        }
      } catch(e) {
        console.error('Failed to parse JSON:', e);
        console.error('Response text:', text);
        alert('Server error: Invalid response format');
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      alert('Error: ' + error.message);
    });
}

function toggleEditNotes(){
  const display = document.getElementById('poNotes').style.display;
  const isEditing = display === 'none';
  
  if(isEditing){
    // Cancel edit - show view mode
    document.getElementById('poNotes').style.display = 'block';
    document.getElementById('poNotesEdit').style.display = 'none';
    document.getElementById('editNotesBtn').style.display = 'inline-block';
    document.getElementById('notesBtnGroup').style.display = 'none';
  } else {
    // Enter edit mode
    document.getElementById('poNotes').style.display = 'none';
    document.getElementById('poNotesEdit').style.display = 'block';
    document.getElementById('editNotesBtn').style.display = 'none';
    document.getElementById('notesBtnGroup').style.display = 'block';
    document.getElementById('poNotesEdit').focus();
  }
}

function saveNotes(){
  const notes = document.getElementById('poNotesEdit').value;
  
  const formData = new FormData();
  formData.append('po_id', currentPOId);
  formData.append('notes', notes);
  
  fetch('update_po_notes.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success){
      document.getElementById('poNotes').textContent = notes || '(No notes)';
      toggleEditNotes();
      alert('✓ Notes updated');
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to update notes'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('❌ An error occurred: ' + error.message);
  });
}

function loadPOItems(items) {
  const tbody = document.getElementById('poItemsTable');
  tbody.innerHTML = '';
  
  if(items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items added yet</td></tr>';
    return;
  }
  
  items.forEach(item => {
    const subtotal = parseFloat(item.quantity) * parseFloat(item.price_per_unit);
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${item.product_variety} (${item.product_grade})</td>
      <td class="text-center">${item.quantity}</td>
      <td class="text-end">₱${parseFloat(item.price_per_unit).toFixed(2)}</td>
      <td class="text-end fw-bold">₱${subtotal.toFixed(2)}</td>
    `;
    tbody.appendChild(row);
  });
}



function markReceived(poId) {
  if(!confirm('Mark PO #' + poId + ' as received?')) {
    return;
  }

  fetch('mark_po_received.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'po_id=' + poId
  })
  .then(response => response.json())
  .then(data => {
    if(data.success) {
      alert('✓ PO marked as received');
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred');
  });
}

function createPO(event) {
  event.preventDefault();
  
  const supplierId = document.getElementById('newPoSupplier').value;
  const orderDate = document.getElementById('newPoOrderDate').value;
  const notes = document.getElementById('newPoNotes').value;
  
  // Get items from table
  const items = getPoItems();
  
  if(!supplierId || !orderDate) {
    alert('Please fill all required fields');
    return;
  }
  
  if(items.length === 0) {
    alert('Please add at least one item to the purchase order');
    return;
  }
  
  // Calculate total amount
  const totalAmount = items.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
  
  const formData = new FormData();
  formData.append('supplier_id', supplierId);
  formData.append('order_date', orderDate);
  formData.append('notes', notes);
  formData.append('items', JSON.stringify(items));
  formData.append('total_amount', totalAmount);
  
  console.log('Creating PO with:', {supplierId, orderDate, notes, items, totalAmount});
  
  fetch('create_po_ajax.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Response status:', response.status);
    return response.text();
  })
  .then(text => {
    console.log('Raw response:', text);
    try {
      const data = JSON.parse(text);
      if(data.success) {
        alert('✓ Purchase Order #' + data.po_id + ' created successfully');
        location.reload();
      } else {
        alert('❌ Error: ' + (data.message || 'Failed to create PO'));
      }
    } catch(e) {
      console.error('Failed to parse JSON:', e);
      alert('❌ Server error: ' + text);
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    alert('❌ An error occurred: ' + error.message);
  });
}

// Get items from the items table
function getPoItems() {
  const items = [];
  const rows = document.querySelectorAll('#poItemsTableBody tr[data-item-index]');
  
  rows.forEach(row => {
    const productId = row.dataset.productId;
    const productName = row.cells[0].textContent.trim();
    const quantity = parseFloat(row.cells[1].textContent.trim());
    const unitPrice = parseFloat(row.cells[2].textContent.trim().replace('₱', ''));
    const subtotal = parseFloat(row.cells[3].textContent.trim().replace('₱', ''));
    
    items.push({
      product_id: parseInt(productId),
      product_name: productName,
      quantity: quantity,
      price_per_unit: unitPrice,
      subtotal: subtotal
    });
  });
  
  return items;
}

// Add item to from the input fields
function addItemToForm(event) {
  event.preventDefault();
  
  const productSelect = document.getElementById('itemProductId');
  const quantityInput = document.getElementById('itemQuantity');
  const priceInput = document.getElementById('itemPrice');
  
  const productId = productSelect.value;
  const productName = productSelect.options[productSelect.selectedIndex].text;
  const quantity = parseFloat(quantityInput.value);
  const unitPrice = parseFloat(priceInput.value);
  
  // Validation
  if(!productId) {
    alert('Please select a product');
    productSelect.focus();
    return;
  }
  
  if(quantity <= 0 || isNaN(quantity)) {
    alert('Please enter a valid quantity');
    quantityInput.focus();
    return;
  }
  
  if(unitPrice <= 0 || isNaN(unitPrice)) {
    alert('Please enter a valid unit price');
    priceInput.focus();
    return;
  }
  
  // Check if product already added
  const existingRow = document.querySelector(`#poItemsTableBody tr[data-product-id="${productId}"]`);
  if(existingRow) {
    alert('This product is already added. Please modify the quantity if needed.');
    return;
  }
  
  const subtotal = quantity * unitPrice;
  
  // Remove "no items" row if exists
  const noItemsRow = document.getElementById('noItemsRow');
  if(noItemsRow) noItemsRow.remove();
  
  // Add row to table
  const tbody = document.getElementById('poItemsTableBody');
  const row = document.createElement('tr');
  row.dataset.itemIndex = tbody.children.length;
  row.dataset.productId = productId;
  
  row.innerHTML = `
    <td>${escapeHtml(productName)}</td>
    <td class="text-center">${quantity.toFixed(2)}</td>
    <td class="text-end">₱${unitPrice.toFixed(2)}</td>
    <td class="text-end">₱${subtotal.toFixed(2)}</td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItemFromForm(this)" title="Remove item">
        <i class="fa-solid fa-trash"></i>
      </button>
    </td>
  `;
  
  tbody.appendChild(row);
  
  // Clear inputs
  productSelect.value = '';
  quantityInput.value = '';
  priceInput.value = '';
  
  // Update total
  updateTotalAmount();
  
  // Focus on product select for next item
  productSelect.focus();
}

// Remove item from the table
function removeItemFromForm(button) {
  const row = button.closest('tr');
  row.remove();
  
  // Check if table is empty
  const tbody = document.getElementById('poItemsTableBody');
  if(tbody.children.length === 0) {
    tbody.innerHTML = `<tr id="noItemsRow">
      <td colspan="5" class="text-center text-muted py-3">
        <small>No items added yet</small>
      </td>
    </tr>`;
  }
  
  updateTotalAmount();
}

// Update total amount display
function updateTotalAmount() {
  const items = getPoItems();
  const total = items.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
  
  document.getElementById('totalAmountDisplay').textContent = '₱' + total.toFixed(2);
  document.getElementById('poItemsJson').value = JSON.stringify(items);
}

// Load products into dropdown when modal opens
document.getElementById('createPOModal').addEventListener('show.bs.modal', function() {
  const productSelect = document.getElementById('itemProductId');
  
  // Only load if not already populated
  if(productSelect.options.length <= 1) {
    fetch('get_products.php')
      .then(response => response.json())
      .then(data => {
        if(data.success && Array.isArray(data.products)) {
          data.products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.product_id;
            option.textContent = `${product.variety} - ${product.grade} (₱${parseFloat(product.price_per_kg).toFixed(2)}/kg)`;
            option.dataset.price = product.price_per_kg;
            productSelect.appendChild(option);
          });
        }
      })
      .catch(error => console.error('Failed to load products:', error));
  }
});

// Auto-fill price when product selected
document.getElementById('itemProductId').addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  if(selectedOption.dataset.price) {
    document.getElementById('itemPrice').value = parseFloat(selectedOption.dataset.price).toFixed(2);
  }
});

// Escape HTML to prevent XSS
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

// Clear form when modal is hidden
document.getElementById('createPOModal').addEventListener('hidden.bs.modal', function() {
  document.getElementById('createPOForm').reset();
  document.getElementById('poItemsTableBody').innerHTML = `<tr id="noItemsRow">
    <td colspan="5" class="text-center text-muted py-3">
      <small>No items added yet</small>
    </td>
  </tr>`;
  updateTotalAmount();
});

// Rating UI Management Functions
function updateRatingUI(po) {
  const isReceived = po.status === 'received';
  const editRatingBtn = document.getElementById('editRatingBtn');
  const poRatingDisplay = document.getElementById('poRatingDisplay');
  const poRatingValue = document.getElementById('poRatingValue');
  
  if(isReceived) {
    // Enable rating editing for received orders
    editRatingBtn.style.display = 'inline-block';
    editRatingBtn.disabled = false;
    
    if(po.rating && po.rating > 0) {
      // Display existing rating
      poRatingValue.textContent = po.rating;
      poRatingDisplay.title = po.comment ? po.comment : '';
      document.getElementById('poRatingInput').value = po.rating;
      document.getElementById('poCommentInput').value = po.comment || '';
      updateStarDisplay(po.rating);
    } else {
      // No rating yet
      poRatingValue.textContent = '-';
      document.getElementById('poRatingInput').value = 0;
      document.getElementById('poCommentInput').value = '';
    }
  } else {
    // Disable rating editing for non-received orders
    editRatingBtn.style.display = 'none';
    editRatingBtn.disabled = true;
    poRatingValue.textContent = '-';
    document.getElementById('poRatingInput').value = 0;
    document.getElementById('poCommentInput').value = '';
  }
}

function toggleEditRating() {
  const editBtn = document.getElementById('editRatingBtn');
  if(editBtn.disabled) {
    alert('Rating can only be added after the product has been received');
    return;
  }
  
  const display = document.getElementById('poRatingDisplay').style.display;
  const isEditing = display === 'none';
  
  if(isEditing) {
    // Cancel edit - show view mode
    document.getElementById('poRatingDisplay').style.display = 'block';
    document.getElementById('poRatingEdit').style.display = 'none';
    document.getElementById('editRatingBtn').style.display = 'inline-block';
  } else {
    // Enter edit mode
    document.getElementById('poRatingDisplay').style.display = 'none';
    document.getElementById('poRatingEdit').style.display = 'block';
    document.getElementById('editRatingBtn').style.display = 'none';
  }
}

function setStarRating(rating) {
  document.getElementById('poRatingInput').value = rating;
  updateStarDisplay(rating);
}

function updateStarDisplay(rating) {
  const stars = document.querySelectorAll('#ratingStarsEdit span i');
  stars.forEach((star, index) => {
    const starNum = index + 1;
    if(starNum <= rating) {
      star.parentElement.style.color = '#FFC107';
    } else {
      star.parentElement.style.color = '#DDD';
    }
  });
}

function saveRating() {
  const rating = parseInt(document.getElementById('poRatingInput').value);
  
  if(rating < 1 || rating > 5) {
    alert('Please select a rating between 1-5 stars');
    return;
  }
  
  const comment = document.getElementById('poCommentInput')?.value || '';
  
  const formData = new FormData();
  formData.append('po_id', currentPOId);
  formData.append('rating', rating);
  formData.append('comment', comment);
  
  fetch('manage_supplier_rating.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success) {
      document.getElementById('poRatingValue').textContent = rating;
      updateStarDisplay(rating);
      toggleEditRating();
      alert('✓ Rating saved successfully');
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to save rating'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('❌ An error occurred: ' + error.message);
  });
}
</script>
