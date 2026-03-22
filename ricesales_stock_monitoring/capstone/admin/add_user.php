<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}
if(strtolower($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: ../login.php");
    exit;
}

include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: users.php");
    exit;
}

$username   = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$role       = strtolower(trim($_POST['role'] ?? ''));
$status     = strtolower(trim($_POST['status'] ?? 'active'));

$allowedRoles = ['cashier','owner']; // ✅ admin can create cashier/owner (not another admin here)
$allowedStatus = ['active','inactive'];

if($username === '' || $password === '' || !in_array($role, $allowedRoles, true)){
    header("Location: users.php?error=" . urlencode("Invalid input. Please complete the form."));
    exit;
}

if(!in_array($status, $allowedStatus, true)){
    $status = 'active';
}

$check = $conn->prepare("SELECT user_id FROM users WHERE LOWER(username)=LOWER(?) LIMIT 1");
$check->bind_param("s", $username);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();

if($exists){
    header("Location: users.php?error=" . urlencode("Username already exists."));
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
    INSERT INTO users (username, password, first_name, last_name, phone, role, created_at, status)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
");
$stmt->bind_param("sssssss", $username, $hash, $first_name, $last_name, $phone, $role, $status);
$stmt->execute();
$stmt->close();

// Log activity
$admin_id = (int)$_SESSION['user_id'];
$desc = "Created new user ($role): $username";
$log = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, created_at) VALUES (?, 'USER_CREATE', ?, NOW())");
$log->bind_param("is", $admin_id, $desc);
$log->execute();
$log->close();

header("Location: users.php?success=" . urlencode("User created successfully."));
exit;
