<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$po_item_id = intval($_POST['po_item_id'] ?? 0);

// Validation
if($po_item_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO Item ID']);
  exit;
}

// Verify item exists
$item_check = $conn->prepare("SELECT po_item_id FROM purchase_order_items WHERE po_item_id = ?");
$item_check->bind_param('i', $po_item_id);
$item_check->execute();
if(!$item_check->get_result()->fetch_assoc()){
  echo json_encode(['success' => false, 'message' => 'PO Item not found']);
  exit;
}

// Delete item
$delete = $conn->prepare("DELETE FROM purchase_order_items WHERE po_item_id = ?");
$delete->bind_param('i', $po_item_id);

if($delete->execute()){
  echo json_encode(['success' => true, 'message' => 'Item removed from PO successfully']);
} else {
  echo json_encode(['success' => false, 'message' => 'Failed to remove item: ' . $conn->error]);
}
?>
