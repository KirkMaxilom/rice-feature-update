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

include "../config/db.php";

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Cashier';

function h($v){
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

/* =====================
Fetch cashier data
===================== */
$stmt = $conn->prepare("
  SELECT user_id, username, first_name, last_name, phone, password, role, status
  FROM users
  WHERE user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user){
  header("Location: ../logout.php");
  exit;
}

/* =====================
Update profile info
===================== */
if(isset($_POST['update_profile'])){
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  if($first === '' || $last === ''){
    header("Location: cashier_profile.php?error=" . urlencode("First and last name are required."));
    exit;
  }

  $up = $conn->prepare("
    UPDATE users
    SET first_name = ?, last_name = ?, phone = ?
    WHERE user_id = ?
  ");
  $up->bind_param("sssi", $first, $last, $phone, $user_id);
  $up->execute();
  $up->close();

  header("Location: cashier_profile.php?success=" . urlencode("Profile updated successfully."));
  exit;
}

/* =====================
Change password
===================== */
if(isset($_POST['change_password'])){
  $old = $_POST['old_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $con = $_POST['confirm_password'] ?? '';

  if($old === '' || $new === '' || $con === ''){
    header("Location: cashier_profile.php?error=" . urlencode("All password fields are required."));
    exit;
  }

  if($new !== $con){
    header("Location: cashier_profile.php?error=" . urlencode("Passwords do not match."));
    exit;
  }

  if(strlen($new) < 6){
    header("Location: cashier_profile.php?error=" . urlencode("Password must be at least 6 characters."));
    exit;
  }

  if(!password_verify($old, $user['password'])){
    header("Location: cashier_profile.php?error=" . urlencode("Old password is incorrect."));
    exit;
  }

  $hashed = password_hash($new, PASSWORD_DEFAULT);

  $up = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
  $up->bind_param("si", $hashed, $user_id);
  $up->execute();
  $up->close();

  header("Location: cashier_profile.php?success=" . urlencode("Password changed successfully."));
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cashier Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
body{ background:#f4f6f9; }
.main-content{ padding-top:90px; }
.card{ border-radius:14px; box-shadow:0 6px 16px rgba(0,0,0,.12); }

/* eye toggle */
.password-wrapper{ position:relative; }
.password-wrapper .toggle-password{
  position:absolute;
  right:15px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  color:#6c757d;
  font-size:18px;
  user-select:none;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
<div class="container-fluid">
<span class="navbar-brand fw-bold">DE ORO HIYS GENERAL MERCHANDISE</span>

<div class="ms-auto dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
<?= h($username) ?> (Cashier)
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
<li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
</ul>
</div>
</div>
</nav>

<div class="container main-content">
<div class="row g-4">

<?php if($success): ?>
<div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="col-md-6">
<div class="card">
<div class="card-body">
<h5 class="fw-bold mb-3">Profile Information</h5>

<form method="post">
<input type="hidden" name="update_profile" value="1">

<div class="mb-2">
<label class="form-label">Username</label>
<input class="form-control" value="<?= h($user['username']) ?>" disabled>
</div>

<div class="mb-2">
<label class="form-label">First Name</label>
<input class="form-control" name="first_name" value="<?= h($user['first_name']) ?>" required>
</div>

<div class="mb-2">
<label class="form-label">Last Name</label>
<input class="form-control" name="last_name" value="<?= h($user['last_name']) ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Phone</label>
<input class="form-control" name="phone" value="<?= h($user['phone']) ?>">
</div>

<button class="btn btn-dark w-100">Save Profile</button>
</form>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card">
<div class="card-body">
<h5 class="fw-bold mb-3">Change Password</h5>

<form method="post">
<input type="hidden" name="change_password" value="1">

<div class="mb-2">
  <label class="form-label">Old Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="old_password" name="old_password" required>
    <span class="toggle-password" data-target="old_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<div class="mb-2">
  <label class="form-label">New Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="new_password" name="new_password" required>
    <span class="toggle-password" data-target="new_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<div class="mb-3">
  <label class="form-label">Confirm New Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="confirm_password" name="confirm_password" required>
    <span class="toggle-password" data-target="confirm_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<button class="btn btn-outline-dark w-100">Update Password</button>
</form>
</div>
</div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
  document.querySelectorAll('.toggle-password').forEach(function(btn){
    btn.addEventListener('click', function(){
      const id = btn.getAttribute('data-target');
      const input = document.getElementById(id);
      if(!input) return;

      const icon = btn.querySelector('i');

      if(input.type === 'password'){
        input.type = 'text';
        if(icon){ icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
      }else{
        input.type = 'password';
        if(icon){ icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
      }
    });
  });
})();
</script>

</body>
</html>