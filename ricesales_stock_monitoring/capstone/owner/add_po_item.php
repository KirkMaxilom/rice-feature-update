<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$po_id = intval($_POST['po_id'] ?? 0);
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = floatval($_POST['quantity'] ?? 0);
$unit_price = floatval($_POST['unit_price'] ?? 0);

// Validation
if($po_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
  exit;
}

if($product_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
  exit;
}

if($quantity <= 0){
  echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
  exit;
}

if($unit_price < 0){
  echo json_encode(['success' => false, 'message' => 'Unit price cannot be negative']);
  exit;
}

// Verify PO exists
$po_check = $conn->prepare("SELECT po_id FROM purchase_orders WHERE po_id = ?");
$po_check->bind_param('i', $po_id);
$po_check->execute();
if(!$po_check->get_result()->fetch_assoc()){
  echo json_encode(['success' => false, 'message' => 'PO not found']);
  exit;
}

// Verify product exists
$product_check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND archived = 0");
$product_check->bind_param('i', $product_id);
$product_check->execute();
if(!$product_check->get_result()->fetch_assoc()){
  echo json_encode(['success' => false, 'message' => 'Product not found']);
  exit;
}

$subtotal = $quantity * $unit_price;

// Insert PO item
$insert = $conn->prepare("
  INSERT INTO purchase_order_items (po_id, product_id, quantity, price_per_unit, subtotal)
  VALUES (?, ?, ?, ?, ?)
");

$insert->bind_param('iiddd', $po_id, $product_id, $quantity, $unit_price, $subtotal);

if($insert->execute()){
  echo json_encode(['success' => true, 'message' => 'Item added to PO successfully']);
} else {
  echo json_encode(['success' => false, 'message' => 'Failed to add item: ' . $conn->error]);
}
?>
