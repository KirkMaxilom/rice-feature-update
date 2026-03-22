<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}
if(strtolower($_SESSION['role'] ?? '') !== 'owner'){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Access denied']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if($_POST['supplier_id']){
  // UPDATE existing supplier
  $supplier_id = intval($_POST['supplier_id']);
  
  if($action === 'toggle_status'){
    $status = $_POST['status'] ?? 'active';
    $status = in_array($status, ['active', 'inactive']) ? $status : 'active';
    
    $update = $conn->prepare("UPDATE suppliers SET status = ? WHERE supplier_id = ?");
    $update->bind_param('si', $status, $supplier_id);
    
    if($update->execute()){
      echo json_encode(['success' => true, 'message' => 'Status updated']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
    $update->close();
  } else {
    // Regular edit
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact_person'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if(!$name || !$contact || !$phone){
      echo json_encode(['success' => false, 'message' => 'Name, contact, and phone are required']);
      exit;
    }
    
    $update = $conn->prepare("
      UPDATE suppliers
      SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?
      WHERE supplier_id = ?
    ");
    $update->bind_param('sssssi', $name, $contact, $phone, $email, $address, $supplier_id);
    
    if($update->execute()){
      echo json_encode(['success' => true, 'message' => 'Supplier updated']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $conn->error]);
    }
    $update->close();
  }
} else {
  // INSERT new supplier
  $name = $_POST['name'] ?? '';
  $contact = $_POST['contact_person'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $email = $_POST['email'] ?? '';
  $address = $_POST['address'] ?? '';
  
  if(!$name || !$contact || !$phone){
    echo json_encode(['success' => false, 'message' => 'Name, contact, and phone are required']);
    exit;
  }
  
  $status = 'active';
  $insert = $conn->prepare("
    INSERT INTO suppliers (name, contact_person, phone, email, address, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
  ");
  $insert->bind_param('ssssss', $name, $contact, $phone, $email, $address, $status);
  
  if($insert->execute()){
    echo json_encode(['success' => true, 'message' => 'Supplier created']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to create: ' . $conn->error]);
  }
  $insert->close();
}
?>
