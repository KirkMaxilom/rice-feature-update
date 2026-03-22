<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'owner'){ header("Location: ../login.php"); exit; }

include "../config/db.php";

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Owner';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Load current user info (adjust columns if your users table differs)
$stmt = $conn->prepare("SELECT user_id, username, role FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user){
  die("User not found.");
}

/* =========================
Update username (optional)
========================= */
if(isset($_POST['update_profile'])){
  $new_username = trim($_POST['username'] ?? '');

  if($new_username === ''){
    header("Location: profile.php?error=" . urlencode("Username cannot be empty."));
    exit;
  }

  // prevent duplicate usernames
  $stmt = $conn->prepare("SELECT user_id FROM users WHERE username=? AND user_id<>? LIMIT 1");
  $stmt->bind_param("si", $new_username, $user_id);
  $stmt->execute();
  $dup = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if($dup){
    header("Location: profile.php?error=" . urlencode("Username already exists."));
    exit;
  }

  $stmt = $conn->prepare("UPDATE users SET username=? WHERE user_id=?");
  $stmt->bind_param("si", $new_username, $user_id);
  $stmt->execute();
  $stmt->close();

  // keep session username in sync
  $_SESSION['username'] = $new_username;

  // activity log (optional)
  if($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows > 0){
    $type = "PROFILE_UPDATE";
    $desc = "Updated profile username to '{$new_username}'";
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, created_at) VALUES (?,?,?,NOW())");
    $stmt->bind_param("iss", $user_id, $type, $desc);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: profile.php?success=" . urlencode("Profile updated."));
  exit;
}

/* =========================
Change password
========================= */
if(isset($_POST['change_password'])){
  $current = $_POST['current_password'] ?? '';
  $new1 = $_POST['new_password'] ?? '';
  $new2 = $_POST['confirm_password'] ?? '';

  if($new1 === '' || $new2 === '' || $current === ''){
    header("Location: profile.php?error=" . urlencode("Please fill up all password fields."));
    exit;
  }
  if($new1 !== $new2){
    header("Location: profile.php?error=" . urlencode("New password and confirm password do not match."));
    exit;
  }
  if(strlen($new1) < 6){
    header("Location: profile.php?error=" . urlencode("New password must be at least 6 characters."));
    exit;
  }

  // verify current password
  $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=? LIMIT 1");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $hash = $row['password'] ?? '';
  if(!$hash || !password_verify($current, $hash)){
    header("Location: profile.php?error=" . urlencode("Current password is incorrect."));
    exit;
  }

  $newHash = password_hash($new1, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
  $stmt->bind_param("si", $newHash, $user_id);
  $stmt->execute();
  $stmt->close();

  // activity log (optional)
  if($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows > 0){
    $type = "PASSWORD_CHANGE";
    $desc = "Changed account password";
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, created_at) VALUES (?,?,?,NOW())");
    $stmt->bind_param("iss", $user_id, $type, $desc);
    $stmt->execute();
    $stmt->close();
  }

  header("Location: profile.php?success=" . urlencode("Password changed successfully."));
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Owner Profile | DOHIVES</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="../css/layout.css" rel="stylesheet">

<style>
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

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
<div class="container-fluid">
<button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
<span class="navbar-brand fw-bold ms-2">DO HIVES GENERAL MERCHANDISE</span>

<div class="ms-auto dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
<?= h($_SESSION['username'] ?? 'Owner') ?> <small class="text-muted">(Owner)</small>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item active" href="profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
<li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
</ul>
</div>
</div>
</nav>

<div class="container-fluid">
<div class="row">

<?php include '../includes/owner_sidebar.php'; ?>

<!-- MAIN -->
<main class="col-lg-10 ms-sm-auto px-4">
<div class="py-4">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<div>
<h3 class="fw-bold mb-1">Owner Profile</h3>
<div class="text-muted">Manage your account details.</div>
</div>
</div>

<?php if($success): ?><div class="alert alert-success py-2"><?= h($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>

<div class="row g-4">
<!-- PROFILE INFO -->
<div class="col-12 col-xl-6">
<div class="card modern-card">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2"></i>Profile Details</h5>

<form method="post">
<input type="hidden" name="update_profile" value="1">

<div class="mb-3">
<label class="form-label fw-semibold">Role</label>
<input class="form-control" value="<?= h($user['role'] ?? 'owner') ?>" disabled>
</div>

<div class="mb-3">
<label class="form-label fw-semibold">Username</label>
<input class="form-control" name="username" value="<?= h($user['username'] ?? '') ?>" required>
<div class="text-muted small mt-1">This is the name shown in the navbar.</div>
</div>

<button class="btn btn-dark w-100">
<i class="fa-solid fa-save me-1"></i> Save Changes
</button>
</form>

</div>
</div>
</div>

<!-- CHANGE PASSWORD -->
<div class="col-12 col-xl-6">
<div class="card modern-card">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="fa-solid fa-key me-2"></i>Change Password</h5>

<form method="post">
<input type="hidden" name="change_password" value="1">

<div class="mb-3">
  <label class="form-label fw-semibold">Current Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="current_password" name="current_password" required>
    <span class="toggle-password" data-target="current_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">New Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="new_password" name="new_password" required>
    <span class="toggle-password" data-target="new_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">Confirm New Password</label>
  <div class="password-wrapper">
    <input type="password" class="form-control pe-5" id="confirm_password" name="confirm_password" required>
    <span class="toggle-password" data-target="confirm_password" aria-label="Toggle password">
      <i class="fa-solid fa-eye"></i>
    </span>
  </div>
</div>

<button class="btn btn-outline-dark w-100">
<i class="fa-solid fa-lock me-1"></i> Update Password
</button>

<div class="text-muted small mt-2">
Tip: Use at least 6 characters.
</div>
</form>

</div>
</div>
</div>

</div>

</div>
</main>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
  function toggle(el){
    const targetId = el.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if(!input) return;

    const icon = el.querySelector('i');

    if(input.type === 'password'){
      input.type = 'text';
      if(icon){ icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    }else{
      input.type = 'password';
      if(icon){ icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    }
  }

  document.querySelectorAll('.toggle-password').forEach(function(el){
    el.addEventListener('click', function(){ toggle(el); });
  });
})();
</script>

</body>
</html>