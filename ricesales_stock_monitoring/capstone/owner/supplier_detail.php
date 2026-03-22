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
$supplier_id = intval($_GET['id'] ?? 0);

if($supplier_id <= 0){
  header("Location: suppliers.php");
  exit;
}

// Get supplier info
$supplierStmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$supplierStmt->bind_param('i', $supplier_id);
$supplierStmt->execute();
$supplier = $supplierStmt->get_result()->fetch_assoc();
$supplierStmt->close();

if(!$supplier){
  header("Location: suppliers.php");
  exit;
}

// Get latest POs (5)
$posStmt = $conn->prepare("
  SELECT 
    po.po_id,
    po.order_date,
    po.status,
    po.total_amount,
    COUNT(poi.po_item_id) AS item_count
  FROM purchase_orders po
  LEFT JOIN purchase_order_items poi ON po.po_id = poi.po_id
  WHERE po.supplier_id = ?
  GROUP BY po.po_id
  ORDER BY po.order_date DESC
  LIMIT 5
");
$posStmt->bind_param('i', $supplier_id);
$posStmt->execute();
$posResult = $posStmt->get_result();
$posStmt->close();

// Get total POs count
$totalPosStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM purchase_orders WHERE supplier_id = ?");
$totalPosStmt->bind_param('i', $supplier_id);
$totalPosStmt->execute();
$totalPosCnt = $totalPosStmt->get_result()->fetch_assoc()['cnt'];
$totalPosStmt->close();

// Get product ratings from purchase orders
$ratingsStmt = $conn->prepare("
  SELECT 
    po.po_id,
    po.supplier_id,
    po.rating,
    po.comment,
    po.created_at,
    po.order_date
  FROM purchase_orders po
  WHERE po.supplier_id = ? AND po.rating IS NOT NULL
  ORDER BY po.created_at DESC
");
$ratingsStmt->bind_param('i', $supplier_id);
$ratingsStmt->execute();
$ratingsResult = $ratingsStmt->get_result();
$ratingsStmt->close();

// Get average rating
$avgRatingStmt = $conn->prepare("
  SELECT 
    IFNULL(ROUND(AVG(rating), 1), 0) AS avg_rating,
    COUNT(*) AS total_ratings
  FROM purchase_orders
  WHERE supplier_id = ? AND rating IS NOT NULL
");
$avgRatingStmt->bind_param('i', $supplier_id);
$avgRatingStmt->execute();
$avgRatingData = $avgRatingStmt->get_result()->fetch_assoc();
$avgRatingStmt->close();

// Get unrated POs for rating dropdown
$unratedPOResult = $conn->prepare("
  SELECT po_id, order_date 
  FROM purchase_orders 
  WHERE supplier_id = ? AND rating IS NULL 
  ORDER BY order_date DESC
");
$unratedPOResult->bind_param('i', $supplier_id);
$unratedPOResult->execute();
$unratedPOResult = $unratedPOResult->get_result();

// Get payables summary
$payablesStmt = $conn->prepare("
  SELECT 
    IFNULL(SUM(total_amount), 0) AS total_payable,
    IFNULL(SUM(balance), 0) AS outstanding,
    MAX(last_payment_date) AS last_payment
  FROM account_payable
  WHERE supplier_id = ?
");
$payablesStmt->bind_param('i', $supplier_id);
$payablesStmt->execute();
$payables = $payablesStmt->get_result()->fetch_assoc();
$payablesStmt->close();

// Get available products for rating form
$productsForRatingStmt = $conn->prepare("
  SELECT DISTINCT p.product_id, p.variety, p.grade
  FROM products p
  INNER JOIN purchase_order_items poi ON p.product_id = poi.product_id
  INNER JOIN purchase_orders po ON poi.po_id = po.po_id
  WHERE po.supplier_id = ?
  AND p.archived = 0
  ORDER BY p.variety
");
$productsForRatingStmt->bind_param('i', $supplier_id);
$productsForRatingStmt->execute();
$productsForRatingResult = $productsForRatingStmt->get_result();
$productsForRatingStmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($supplier['name']) ?> | Suppliers</title>
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

<!-- Back & Header -->
<div class="mb-4">
  <a href="suppliers.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-chevron-left me-1"></i> Back to Suppliers</a>
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h3 class="fw-bold mb-0"><?= h($supplier['name']) ?></h3>
      <div class="text-muted small mt-1">Member since <?= date('M d, Y', strtotime($supplier['created_at'])) ?></div>
    </div>
    <div class="btn-group btn-group-sm">
      <button class="btn btn-outline-secondary" onclick="editSupplierInfo()" title="Edit"><i class="fa-solid fa-pen"></i> Edit</button>
      <button class="btn btn-outline-<?= $supplier['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleStatus()" title="Toggle Status">
        <i class="fa-solid fa-toggle-<?= $supplier['status'] === 'active' ? 'on' : 'off' ?>"></i> 
        <?= $supplier['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
      </button>
    </div>
  </div>
</div>

<!-- SECTION 1: SUPPLIER INFO -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-light">
    <h5 class="mb-0"><i class="fa-solid fa-building me-2"></i>Supplier Profile</h5>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Contact Person</div>
          <div class="fw-bold"><?= h($supplier['contact_person'] ?? '-') ?></div>
          <div class="small">Phone: <a href="tel:<?= h($supplier['phone'] ?? '') ?>" class="text-decoration-none"><?= h($supplier['phone'] ?? '-') ?></a></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Email</div>
          <div class="fw-bold">
            <?php if($supplier['email']): ?>
              <a href="mailto:<?= h($supplier['email']) ?>" class="text-decoration-none"><?= h($supplier['email']) ?></a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Address</div>
          <div><?= h($supplier['address'] ?? '-') ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Status</div>
          <div>
            <span class="badge <?= $supplier['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
              <?= ucfirst(h($supplier['status'])) ?>
            </span>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Total Orders</div>
          <div class="fw-bold"><?= $totalPosCnt ?> PO<?= $totalPosCnt !== 1 ? 's' : '' ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SECTION 2: PURCHASE ORDERS -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fa-solid fa-receipt me-2"></i>Purchase Orders</h5>
    <a href="create_purchase_order.php?supplier_id=<?= (int)$supplier_id ?>" class="btn btn-sm btn-primary">
      <i class="fa-solid fa-plus me-1"></i> Create PO
    </a>
  </div>
  <div class="card-body">
    <?php if($posResult && $posResult->num_rows > 0): ?>
    <div class="table-responsive mb-3">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>PO #</th>
            <th>Order Date</th>
            <th>Items</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($po = $posResult->fetch_assoc()): ?>
          <tr>
            <td class="fw-bold">#<?= (int)$po['po_id'] ?></td>
            <td><?= date('M d, Y', strtotime($po['order_date'])) ?></td>
            <td><span class="badge bg-light text-dark"><?= (int)$po['item_count'] ?> items</span></td>
            <td class="fw-bold">₱<?= number_format($po['total_amount'], 2) ?></td>
            <td>
              <span class="badge bg-<?= 
                $po['status'] === 'received' ? 'success' : 
                ($po['status'] === 'ordered' ? 'info' : 
                ($po['status'] === 'pending' ? 'warning' : 'danger'))
              ?>">
                <?= ucfirst(h($po['status'])) ?>
              </span>
            </td>
            <td class="text-center">
              <a href="purchase_orders.php?view=<?= (int)$po['po_id'] ?>" class="btn btn-xs btn-outline-primary" onclick="viewPODetails(<?= (int)$po['po_id'] ?>); return false;" title="View">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php if($totalPosCnt > 5): ?>
    <a href="purchase_orders.php?supplier_id=<?= (int)$supplier_id ?>" class="btn btn-sm btn-outline-secondary">
      View All <?= (int)$totalPosCnt ?> POs →
    </a>
    <?php endif; ?>
    <?php else: ?>
    <div class="py-4 text-center text-muted">
      <i class="fa-solid fa-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
      <p class="mt-2">No purchase orders yet. <a href="create_purchase_order.php?supplier_id=<?= (int)$supplier_id ?>" class="text-primary">Create one →</a></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- SECTION 3: PRODUCT RATINGS -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-light">
    <h5 class="mb-0"><i class="fa-solid fa-star me-2"></i>Product Ratings <span class="badge bg-primary"><?= $avgRatingData['total_ratings'] ?></span></h5>
  </div>
  <div class="card-body">
    <!-- Add Rating Form -->
    <div class="mb-4 p-3 border rounded bg-light">
      <h6 class="mb-3">Rate a Purchase Order</h6>
      <form onsubmit="savePORating(event)">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Select PO <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="selectPOId" required>
              <option value="">-- Select an unrated PO --</option>
              <?php 
              $unratedPOResult->data_seek(0);
              if($unratedPOResult && $unratedPOResult->num_rows > 0):
                while($po = $unratedPOResult->fetch_assoc()): 
              ?>
              <option value="<?= (int)$po['po_id'] ?>">PO #<?= (int)$po['po_id'] ?> (<?= date('M d, Y', strtotime($po['order_date'])) ?>)</option>
              <?php endwhile; else: ?>
              <option disabled>No unrated POs</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Rating <span class="text-danger">*</span></label>
            <div class="star-rating" id="ratingStars">
              <span onclick="setPORating(1)" class="me-1" style="cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></span>
              <span onclick="setPORating(2)" class="me-1" style="cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></span>
              <span onclick="setPORating(3)" class="me-1" style="cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></span>
              <span onclick="setPORating(4)" class="me-1" style="cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></span>
              <span onclick="setPORating(5)" class="me-1" style="cursor: pointer; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></span>
              <input type="hidden" id="poRating" value="0" required>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label small fw-bold">Comment (Optional)</label>
            <textarea class="form-control form-control-sm" id="poComment" rows="2" placeholder="Add feedback..."></textarea>
          </div>
          <div class="col-md-12">
            <button type="submit" class="btn btn-sm btn-success">Save Rating</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Ratings Display -->
    <?php $ratingCount = 0; ?>
    <?php while($rating = $ratingsResult->fetch_assoc()): ?>
      <?php $ratingCount++; ?>
      <div class="border-bottom pb-3 mb-3 d-flex justify-content-between align-items-start">
        <div>
          <div class="small text-muted">PO #<?= (int)$rating['po_id'] ?></div>
          <div class="text-warning mb-2">
            <?php for($i=0; $i<(int)$rating['rating']; $i++): ?>
              <i class="fa-solid fa-star"></i>
            <?php endfor; ?>
            <span class="fw-bold"><?= (int)$rating['rating'] ?>/5</span>
          </div>
          <?php if($rating['comment']): ?>
          <div class="small text-muted p-2 bg-light rounded">
            <?= h($rating['comment']) ?>
          </div>
          <?php endif; ?>
          <div class="small text-muted mt-2">Rated: <?= date('M d, Y', strtotime($rating['created_at'])) ?></div>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="clearPORating(<?= (int)$rating['po_id'] ?>)">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    <?php endwhile; ?>
    
    <?php if($ratingCount === 0): ?>
    <div class="text-center text-muted py-4">
      <i class="fa-solid fa-star" style="font-size: 2rem; opacity: 0.3;"></i>
      <p class="mt-2">No ratings yet. Add your first rating below →</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- SECTION 4: PAYABLES SUMMARY -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-light">
    <h5 class="mb-0"><i class="fa-solid fa-money-bill me-2"></i>Payables Summary</h5>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="border rounded p-3">
          <div class="small text-muted fw-bold">Total Outstanding</div>
          <div class="h4 fw-bold text-danger">₱<?= number_format($payables['outstanding'] ?? 0, 2) ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3">
          <div class="small text-muted fw-bold">Total Invoiced</div>
          <div class="h4 fw-bold">₱<?= number_format($payables['total_payable'] ?? 0, 2) ?></div>
        </div>
      </div>
      <?php if($payables['last_payment']): ?>
      <div class="col-md-12">
        <div class="border rounded p-3 bg-light">
          <div class="small text-muted fw-bold">Last Payment</div>
          <div><?= date('M d, Y', strtotime($payables['last_payment'])) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="mt-3">
      <a href="supplier_payables.php?supplier_id=<?= (int)$supplier_id ?>" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-arrow-right me-1"></i> View All Payables
      </a>
    </div>
  </div>
</div>

</div>
</main>

</div>
</div>

<!-- EDIT SUPPLIER INFO MODAL -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Edit Supplier Information</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form onsubmit="updateSupplier(event)">
<div class="modal-body">
  <input type="hidden" id="editSupplierId" value="<?= (int)$supplier_id ?>">
  
  <div class="mb-3">
    <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="editName" value="<?= h($supplier['name']) ?>" required>
  </div>
  
  <div class="mb-3">
    <label class="form-label">Contact Person <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="editContact" value="<?= h($supplier['contact_person'] ?? '') ?>" required>
  </div>
  
  <div class="mb-3">
    <label class="form-label">Phone <span class="text-danger">*</span></label>
    <input type="tel" class="form-control" id="editPhone" value="<?= h($supplier['phone'] ?? '') ?>" required>
  </div>
  
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" id="editEmail" value="<?= h($supplier['email'] ?? '') ?>">
  </div>
  
  <div class="mb-0">
    <label class="form-label">Address</label>
    <textarea class="form-control" id="editAddress" rows="2"><?= h($supplier['address'] ?? '') ?></textarea>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>
</div>

<style>
.star-rating {
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  gap: 0.2rem;
}

.star-rating span {
  color: #ccc;
  transition: color 0.2s;
  cursor: pointer;
}

.star-rating span:hover,
.star-rating span.selected {
  color: #ffc107;
}

.btn-xs {
  padding: 0.25rem 0.4rem;
  font-size: 0.75rem;
}
</style>

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
  </div>

  <!-- Notes -->
  <div class="mb-3">
    <strong>Notes:</strong>
    <p id="poNotes" class="text-muted">-</p>
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
          <tr><td colspan="4" class="text-center text-muted">No items in this order</td></tr>
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

let poRatingValue = 0;

function setPORating(value){
  poRatingValue = value;
  document.getElementById('poRating').value = value;
  
  // Update star colors
  const stars = document.getElementById('ratingStars').querySelectorAll('span');
  stars.forEach((star, idx) => {
    if(idx < value){
      star.style.color = '#FFC107';
    } else {
      star.style.color = '#DDD';
    }
  });
}

function savePORating(event){
  event.preventDefault();
  
  const poId = document.getElementById('selectPOId').value;
  const rating = poRatingValue;
  const comment = document.getElementById('poComment').value;
  
  if(!poId || rating === 0){
    alert('Please select a PO and rating');
    return;
  }
  
  const formData = new FormData();
  formData.append('po_id', poId);
  formData.append('rating', rating);
  formData.append('comment', comment);
  
  fetch('manage_supplier_rating.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success){
      alert('✓ Rating saved successfully');
      location.reload();
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to save rating'));
    }
  });
}

function clearPORating(poId){
  if(!confirm('Remove this rating?')) return;
  
  const formData = new FormData();
  formData.append('po_id', poId);
  formData.append('action', 'delete');
  
  fetch('manage_supplier_rating.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success){
      alert('✓ Rating deleted');
      location.reload();
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to delete rating'));
    }
  });
}

function editSupplierInfo(){
  const modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
  modal.show();
}

function updateSupplier(event){
  event.preventDefault();
  
  const formData = new FormData();
  formData.append('supplier_id', <?= (int)$supplier_id ?>);
  formData.append('name', document.getElementById('editName').value);
  formData.append('contact_person', document.getElementById('editContact').value);
  formData.append('phone', document.getElementById('editPhone').value);
  formData.append('email', document.getElementById('editEmail').value);
  formData.append('address', document.getElementById('editAddress').value);
  
  fetch('manage_supplier.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success){
      alert('✓ Supplier information updated');
      location.reload();
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to update'));
    }
  });
}

function toggleStatus(){
  const message = '<?= $supplier['status'] === 'active' ? "Deactivate this supplier? They will no longer appear in PO selection." : "Activate this supplier?" ?>';
  if(!confirm(message)) return;
  
  const formData = new FormData();
  formData.append('supplier_id', <?= (int)$supplier_id ?>);
  formData.append('status', '<?= $supplier['status'] === 'active' ? 'inactive' : 'active' ?>');
  formData.append('action', 'toggle_status');
  
  fetch('manage_supplier.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if(data.success){
      alert('✓ Supplier status updated');
      location.reload();
    } else {
      alert('❌ Error: ' + (data.message || 'Failed to update'));
    }
  });
}

function viewPODetails(poId){
  // Fetch PO details and items via AJAX
  fetch('get_po_details.php?po_id=' + poId)
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        document.getElementById('poSupplierName').textContent = data.po.supplier_name;
        document.getElementById('poOrderDate').textContent = new Date(data.po.order_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
        document.getElementById('poStatus').innerHTML = '<span class="badge bg-' + (data.po.status === 'received' ? 'success' : data.po.status === 'ordered' ? 'info' : 'warning') + '">' + data.po.status.toUpperCase() + '</span>';
        document.getElementById('poTotalAmount').textContent = '₱' + parseFloat(data.po.total_amount).toFixed(2);
        document.getElementById('poNotes').textContent = data.po.notes || '(No notes)';
        
        // Populate items table
        const tbody = document.getElementById('poItemsTable');
        tbody.innerHTML = '';
        
        if(data.items && data.items.length > 0) {
          data.items.forEach(item => {
            const subtotal = parseFloat(item.quantity) * parseFloat(item.unit_price);
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${item.product_variety} (${item.product_grade})</td>
              <td class="text-center">${item.quantity}</td>
              <td class="text-end">₱${parseFloat(item.unit_price).toFixed(2)}</td>
              <td class="text-end fw-bold">₱${subtotal.toFixed(2)}</td>
            `;
            tbody.appendChild(row);
          });
        } else {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No items in this order</td></tr>';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('poDetailsModal'));
        modal.show();
      } else {
        alert('Error: ' + (data.message || 'Failed to load PO details'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred');
    });
}

</script>

</body>
</html>
