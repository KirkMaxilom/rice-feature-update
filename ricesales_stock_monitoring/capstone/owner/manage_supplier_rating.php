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
$po_id = intval($_POST['po_id'] ?? 0);

if($po_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
  exit;
}

// Check if PO status is 'received' before allowing rating
$check_stmt = $conn->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
$check_stmt->bind_param('i', $po_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows === 0){
  echo json_encode(['success' => false, 'message' => 'Purchase order not found']);
  $check_stmt->close();
  exit;
}

$po = $check_result->fetch_assoc();
$check_stmt->close();

if($po['status'] !== 'received'){
  echo json_encode(['success' => false, 'message' => 'Rating can only be added after the product has been received']);
  exit;
}

if($action === 'delete'){
  // Clear rating from PO
  $update = $conn->prepare("UPDATE purchase_orders SET rating = NULL, comment = NULL WHERE po_id = ?");
  $update->bind_param('i', $po_id);
  
  if($update->execute()){
    echo json_encode(['success' => true, 'message' => 'Rating deleted']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete']);
  }
  $update->close();
} else {
  // Save or update rating
  $rating = intval($_POST['rating'] ?? 0);
  $comment = trim($_POST['comment'] ?? '');
  
  // Validate
  if($rating < 1 || $rating > 5){
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1-5']);
    exit;
  }
  
  // Update PO with rating
  $update = $conn->prepare("UPDATE purchase_orders SET rating = ?, comment = ? WHERE po_id = ?");
  $update->bind_param('isi', $rating, $comment, $po_id);
  
  if($update->execute()){
    echo json_encode(['success' => true, 'message' => 'Rating saved']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to save rating: ' . $conn->error]);
  }
  $update->close();
}
?>
