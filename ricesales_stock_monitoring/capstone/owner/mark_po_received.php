<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$po_id = (int)($_POST['po_id'] ?? 0);

if($po_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
  exit;
}

// Check if PO already received
$check_sql = "SELECT status FROM purchase_orders WHERE po_id = ?";
$check_stmt = $conn->prepare($check_sql);
if(!$check_stmt){
  echo json_encode(['success' => false, 'message' => 'Database error']);
  exit;
}

$check_stmt->bind_param("i", $po_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_stmt->close();

if($check_result->num_rows === 0){
  echo json_encode(['success' => false, 'message' => 'PO not found']);
  exit;
}

$po = $check_result->fetch_assoc();

if($po['status'] === 'received'){
  echo json_encode(['success' => false, 'message' => 'PO already marked as received']);
  exit;
}

// Update PO status and set received_date
$update_sql = "UPDATE purchase_orders SET status = 'received', received_date = CURDATE() WHERE po_id = ?";
$update_stmt = $conn->prepare($update_sql);
if(!$update_stmt){
  echo json_encode(['success' => false, 'message' => 'Failed to update']);
  exit;
}

$update_stmt->bind_param("i", $po_id);

if($update_stmt->execute()){
  $update_stmt->close();
  echo json_encode(['success' => true, 'message' => 'PO marked as received']);
} else {
  $update_stmt->close();
  echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
