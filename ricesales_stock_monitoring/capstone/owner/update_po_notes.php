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

$po_id = intval($_POST['po_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if($po_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
  exit;
}

// Update notes
$update = $conn->prepare("UPDATE purchase_orders SET notes = ? WHERE po_id = ?");
if(!$update){
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
  exit;
}

$update->bind_param('si', $notes, $po_id);

if($update->execute()){
  echo json_encode(['success' => true, 'message' => 'Notes updated']);
} else {
  echo json_encode(['success' => false, 'message' => 'Failed to update notes: ' . $conn->error]);
}

$update->close();
?>
