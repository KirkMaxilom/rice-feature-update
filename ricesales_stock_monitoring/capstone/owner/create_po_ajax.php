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

$supplier_id = intval($_POST['supplier_id'] ?? 0);
$order_date = trim($_POST['order_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$items = json_decode($_POST['items'] ?? '[]', true);
$total_amount = floatval($_POST['total_amount'] ?? 0);

// Validation
if($supplier_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Please select a supplier']);
  exit;
}

if($order_date === ''){
  echo json_encode(['success' => false, 'message' => 'Order date is required']);
  exit;
}

// Verify supplier exists and is active
$supplierCheck = $conn->prepare("SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND status = 'active'");
if(!$supplierCheck){
  echo json_encode(['success' => false, 'message' => 'Supplier check error: ' . $conn->error]);
  exit;
}
$supplierCheck->bind_param('i', $supplier_id);
$supplierCheck->execute();
$supplierResult = $supplierCheck->get_result();
if(!$supplierResult->fetch_assoc()){
  echo json_encode(['success' => false, 'message' => 'Invalid or inactive supplier']);
  exit;
}
$supplierCheck->close();

// Insert into purchase_orders table
$status = 'pending';
$insert = $conn->prepare("
  INSERT INTO purchase_orders (supplier_id, order_date, notes, status, total_amount, created_at)
  VALUES (?, ?, ?, ?, ?, NOW())
");

if(!$insert){
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
  exit;
}

$insert->bind_param('isssd', $supplier_id, $order_date, $notes, $status, $total_amount);

if($insert->execute()){
  $po_id = $insert->insert_id;
  $insert->close();
  
  // Insert items if provided
  if(!empty($items)){
    $itemInsert = $conn->prepare("
      INSERT INTO purchase_order_items (po_id, product_id, quantity, price_per_unit, subtotal)
      VALUES (?, ?, ?, ?, ?)
    ");
    
    if(!$itemInsert){
      echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
      exit;
    }
    
    foreach($items as $item){
      $product_id = intval($item['product_id'] ?? 0);
      $quantity = floatval($item['quantity'] ?? 0);
      $price_per_unit = floatval($item['price_per_unit'] ?? 0);
      $subtotal = floatval($item['subtotal'] ?? 0);
      
      // Validate item data
      if($product_id <= 0 || $quantity <= 0 || $price_per_unit <= 0){
        $itemInsert->close();
        echo json_encode(['success' => false, 'message' => 'Invalid item data provided']);
        exit;
      }
      
      $itemInsert->bind_param('iiddd', $po_id, $product_id, $quantity, $price_per_unit, $subtotal);
      
      if(!$itemInsert->execute()){
        $itemInsert->close();
        echo json_encode(['success' => false, 'message' => 'Failed to add item to PO: ' . $conn->error]);
        exit;
      }
    }
    $itemInsert->close();
  }
  
  echo json_encode([
    'success' => true, 
    'message' => 'Purchase Order created successfully',
    'po_id' => $po_id
  ]);
} else {
  $error_msg = $insert->error ?: $conn->error;
  $insert->close();
  echo json_encode(['success' => false, 'message' => 'Failed to create PO: ' . $error_msg]);
}
?>
