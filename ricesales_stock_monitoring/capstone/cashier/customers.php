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
$user_id = (int)($_SESSION['user_id'] ?? 0);

include '../config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if(isset($_POST['add_customer'])){
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$addr = trim($_POST['address'] ?? '');

if($first==='' || $last==='' || $phone===''){
header("Location: customers.php?error=" . urlencode("First name, last name, and phone are required."));
exit;
}

$stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone, address, created_at) VALUES (?,?,?,?,NOW())");
if(!$stmt){ die("SQL PREPARE ERROR: ".$conn->error); }
$stmt->bind_param("ssss", $first, $last, $phone, $addr);
$stmt->execute();
$stmt->close();

header("Location: customers.php?success=" . urlencode("Customer added successfully."));
exit;
}

if(isset($_POST['edit_customer'])){
$cid = (int)($_POST['customer_id'] ?? 0);
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$addr = trim($_POST['address'] ?? '');

if($cid<=0 || $first==='' || $last==='' || $phone===''){
header("Location: customers.php?error=" . urlencode("Invalid customer data."));
exit;
}

$stmt = $conn->prepare("UPDATE customers SET first_name=?, last_name=?, phone=?, address=? WHERE customer_id=?");
if(!$stmt){ die("SQL PREPARE ERROR: ".$conn->error); }
$stmt->bind_param("ssssi", $first, $last, $phone, $addr, $cid);
$stmt->execute();
$stmt->close();

header("Location: customers.php?success=" . urlencode("Customer updated."));
exit;
}

$q = trim($_GET['q'] ?? '');

$sql = "
  SELECT 
    c.*,
    IFNULL(SUM(ar.total_amount), 0) as ar_total,
    IFNULL(SUM(ar.balance), 0) as ar_balance,
    IFNULL(SUM(p.penalty_amount), 0) as total_penalties,
    MAX(ar.due_date) as latest_due_date,
    SUM(CASE WHEN ar.status != 'paid' AND ar.due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count
  FROM customers c
  LEFT JOIN account_receivable ar ON c.customer_id = ar.customer_id
  LEFT JOIN penalties p ON ar.ar_id = p.reference_id AND p.reference_type = 'receivable'
";
$params = [];
$types = "";

if($q !== ''){
  $sql .= " WHERE c.first_name LIKE CONCAT('%',?,'%')
  OR c.last_name LIKE CONCAT('%',?,'%')
  OR c.phone LIKE CONCAT('%',?,'%')
  OR c.address LIKE CONCAT('%',?,'%')";
  $params = [$q,$q,$q,$q];
  $types = "ssss";
}
$sql .= " GROUP BY c.customer_id ORDER BY c.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if(!$stmt){ die("SQL PREPARE ERROR: ".$conn->error."<br><pre>$sql</pre>"); }
if($types !== ''){
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$customers = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers | Cashier</title>

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
<h3 class="fw-bold mb-1">Customers</h3>
<div class="text-muted">Add and manage customer records (for sales & utang).</div>
</div>
<button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
<i class="fa-solid fa-user-plus me-1"></i> Add Customer
</button>
</div>

<?php if($success): ?><div class="alert alert-success py-2"><?= h($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>

<!-- SEARCH -->
<div class="card modern-card mb-3">
<div class="card-body">
<form class="row g-2 align-items-end" method="GET">
<div class="col-12 col-md-9">
<label class="form-label">Search</label>
<input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Name, phone, address...">
</div>
<div class="col-12 col-md-3 d-grid">
<button class="btn btn-outline-dark"><i class="fa-solid fa-magnifying-glass me-1"></i> Search</button>
</div>
</form>
</div>
</div>

<!-- TABLE -->
<div class="card modern-card">
<div class="card-body table-responsive">
<table class="table table-striped align-middle mb-0">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Phone</th>
<th style="width:100px;">Balance</th>
<th style="width:90px;">Penalty</th>
<th style="width:100px;">Total Due</th>
<th style="width:80px;">Status</th>
<th class="text-end">Action</th>
</tr>
</thead>
<tbody>
<?php if($customers && $customers->num_rows>0): ?>
<?php while($c = $customers->fetch_assoc()): ?>
<?php
$name = trim(($c['first_name'] ?? '').' '.($c['last_name'] ?? ''));
$ar_balance = (float)($c['ar_balance'] ?? 0);
$penalties = (float)($c['total_penalties'] ?? 0);
$total_due = $ar_balance + $penalties;
$overdue = (int)($c['overdue_count'] ?? 0);
$has_debt = $ar_balance > 0;
?>
<tr>
<td class="fw-bold"><?= (int)$c['customer_id'] ?></td>
<td><?= h($name ?: 'N/A') ?></td>
<td><?= h($c['phone'] ?? '') ?></td>
<td>
  <?php if($has_debt): ?>
    <span class="badge bg-warning text-dark">₱<?= number_format($ar_balance, 2) ?></span>
  <?php else: ?>
    <span class="text-muted">-</span>
  <?php endif; ?>
</td>
<td>
  <?php if($penalties > 0): ?>
    <span class="badge bg-danger">₱<?= number_format($penalties, 2) ?></span>
  <?php else: ?>
    <span class="text-muted">-</span>
  <?php endif; ?>
</td>
<td>
  <?php if($total_due > 0): ?>
    <strong class="<?= $overdue > 0 ? 'text-danger' : '' ?>">₱<?= number_format($total_due, 2) ?></strong>
    <?php if($overdue > 0): ?><br><small class="text-danger">⚠ <?= $overdue ?> overdue</small><?php endif; ?>
  <?php else: ?>
    <span class="text-muted">-</span>
  <?php endif; ?>
</td>
<td>
  <?php if($overdue > 0): ?>
    <span class="badge bg-danger">Overdue</span>
  <?php elseif($has_debt): ?>
    <span class="badge bg-warning text-dark">Owing</span>
  <?php else: ?>
    <span class="badge bg-success">Clear</span>
  <?php endif; ?>
</td>
<td class="text-end">
  <button class="btn btn-sm btn-info me-1" type="button" onclick="viewCustomer(<?= (int)$c['customer_id'] ?>, '<?= h($name ?: 'N/A') ?>', <?= $ar_balance ?>, <?= $penalties ?>, <?= $overdue ?>, <?= $total_due ?>)"><i class="fa-solid fa-eye"></i></button>
  <button class="btn btn-sm btn-outline-dark" type="button" onclick="editCustomer(<?= (int)$c['customer_id'] ?>, '<?= h($c['first_name'] ?? '') ?>', '<?= h($c['last_name'] ?? '') ?>', '<?= h($c['phone'] ?? '') ?>', '<?= h($c['address'] ?? '') ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
</td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8" class="text-center text-muted py-4">No customers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</main>

</div>
</div>

<!-- REUSABLE VIEW DETAILS MODAL -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="viewModalTitle">Account Details</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="border rounded p-2 bg-light">
        <div class="small text-muted">Balance</div>
        <div class="fw-bold" id="viewBalance">₱0.00</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded p-2" id="penaltyBox">
        <div class="small text-muted">Penalty <small>(₱10/day)</small></div>
        <div class="fw-bold" id="viewPenalty">₱0.00</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded p-2" id="overdueBox">
        <div class="small text-muted">Overdue Items</div>
        <div class="fw-bold" id="viewOverdue">0</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded p-2" id="totalBox">
        <div class="small text-muted">Total Due</div>
        <div class="fw-bold" id="viewTotal">₱0.00</div>
      </div>
    </div>
  </div>
  <div id="penaltyNotice"></div>

  <!-- PENALTY BREAKDOWN TABLE -->
  <div id="penaltyBreakdown" style="display:none;">
    <div class="border-top pt-3 mt-3">
      <h6 class="mb-3"><i class="fa-solid fa-receipt me-2 text-danger"></i>Penalty Breakdown</h6>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Reference #</th>
              <th>Due Date</th>
              <th>Days Late</th>
              <th>Rate/Day</th>
              <th class="text-end">Amount</th>
              <th class="text-end">Last Calc</th>
            </tr>
          </thead>
          <tbody id="penaltyTableBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

<!-- REUSABLE EDIT MODAL -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Edit Customer</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="edit_customer" value="1">
<input type="hidden" name="customer_id" id="editCustomerId" value="">

<div class="mb-2">
<label class="form-label">First Name</label>
<input class="form-control" name="first_name" id="editFirstName" required>
</div>
<div class="mb-2">
<label class="form-label">Last Name</label>
<input class="form-control" name="last_name" id="editLastName" required>
</div>
<div class="mb-2">
<label class="form-label">Phone</label>
<input class="form-control" name="phone" id="editPhone" required>
</div>
<div class="mb-2">
<label class="form-label">Address</label>
<textarea class="form-control" name="address" id="editAddress" rows="2"></textarea>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-dark"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
</div>
</form>
</div>
</div>
</div>
<div class="modal fade" id="addCustomerModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Add Customer</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="add_customer" value="1">

<div class="mb-2">
<label class="form-label">First Name</label>
<input class="form-control" name="first_name" required>
</div>
<div class="mb-2">
<label class="form-label">Last Name</label>
<input class="form-control" name="last_name" required>
</div>
<div class="mb-2">
<label class="form-label">Phone</label>
<input class="form-control" name="phone" required>
</div>
<div class="mb-2">
<label class="form-label">Address</label>
<textarea class="form-control" name="address" rows="2"></textarea>
</div>

</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-dark"><i class="fa-solid fa-user-plus me-1"></i> Add</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewCustomer(id, name, balance, penalties, overdue, total) {
  document.getElementById('viewModalTitle').textContent = name + ' - Account Details';
  document.getElementById('viewBalance').textContent = '₱' + parseFloat(balance).toFixed(2);
  document.getElementById('viewPenalty').textContent = '₱' + parseFloat(penalties).toFixed(2);
  document.getElementById('viewOverdue').textContent = overdue;
  document.getElementById('viewTotal').textContent = '₱' + parseFloat(total).toFixed(2);
  
  // Update styling based on values
  const penaltyBox = document.getElementById('penaltyBox');
  const overdueBox = document.getElementById('overdueBox');
  const totalBox = document.getElementById('totalBox');
  
  penaltyBox.className = 'border rounded p-2' + (penalties > 0 ? ' bg-danger bg-opacity-10' : ' bg-light');
  overdueBox.className = 'border rounded p-2' + (overdue > 0 ? ' bg-warning bg-opacity-10' : ' bg-light');
  totalBox.className = 'border rounded p-2' + (total > 0 ? ' bg-danger bg-opacity-10' : ' bg-light');
  
  // Update text color
  document.querySelector('#penaltyBox .fw-bold').className = 'fw-bold' + (penalties > 0 ? ' text-danger' : '');
  document.querySelector('#overdueBox .fw-bold').className = 'fw-bold' + (overdue > 0 ? ' text-danger' : '');
  document.querySelector('#totalBox .fw-bold').className = 'fw-bold' + (total > 0 ? ' text-danger' : '');
  
  // Show penalty notice if applicable
  const noticeDiv = document.getElementById('penaltyNotice');
  if(penalties > 0) {
    noticeDiv.innerHTML = '<div class="alert alert-warning mb-0"><strong>⚠ Penalty Notice:</strong> This customer has overdue accounts. A ₱10 daily penalty is charged for each day past the due date.</div>';
  } else {
    noticeDiv.innerHTML = '';
  }

  // Fetch penalty details if there are penalties
  if(penalties > 0) {
    fetch('get_penalty_details.php?customer_id=' + id)
      .then(response => response.json())
      .then(data => {
        if(data.success && data.has_penalties) {
          populatePenaltyTable(data.penalties);
        }
      })
      .catch(error => console.error('Error fetching penalties:', error));
  } else {
    document.getElementById('penaltyBreakdown').style.display = 'none';
  }
  
  const modal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
  modal.show();
}

function populatePenaltyTable(penalties) {
  const tbody = document.getElementById('penaltyTableBody');
  tbody.innerHTML = '';
  
  penalties.forEach(penalty => {
    const row = document.createElement('tr');
    const lastCalcDate = new Date(penalty.last_calculated).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const dueDate = new Date(penalty.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    
    row.innerHTML = `
      <td><strong>${penalty.reference}</strong></td>
      <td>${dueDate}</td>
      <td><span class="badge bg-warning text-dark">${penalty.days_late}</span></td>
      <td>₱${parseFloat(penalty.penalty_rate).toFixed(2)}/day</td>
      <td class="text-end text-danger"><strong>₱${parseFloat(penalty.penalty_amount).toFixed(2)}</strong></td>
      <td class="text-end"><small class="text-muted">${lastCalcDate}</small></td>
    `;
    tbody.appendChild(row);
  });
  
  document.getElementById('penaltyBreakdown').style.display = 'block';
}

function editCustomer(id, firstName, lastName, phone, address) {
  document.getElementById('editCustomerId').value = id;
  document.getElementById('editFirstName').value = firstName;
  document.getElementById('editLastName').value = lastName;
  document.getElementById('editPhone').value = phone;
  document.getElementById('editAddress').value = address;
  
  const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
  modal.show();
}
</script>
</body>
</html>