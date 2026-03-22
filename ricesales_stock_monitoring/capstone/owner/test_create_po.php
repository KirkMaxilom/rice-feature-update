<?php
// Test file to debug create_po_ajax.php
session_start();

// Simulate owner session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

include '../config/db.php';

// Test the create_po_ajax.php logic
$supplier_id = 4; // Use a known supplier ID
$order_date = '2026-03-20';
$notes = 'Test PO';

echo "Testing Purchase Order Creation\n";
echo "================================\n";
echo "Supplier ID: $supplier_id\n";
echo "Order Date: $order_date\n";
echo "Notes: $notes\n\n";

// Check if supplier exists
$supplierCheck = $conn->prepare("SELECT supplier_id, name FROM suppliers WHERE supplier_id = ? AND status = 'active'");
$supplierCheck->bind_param('i', $supplier_id);
$supplierCheck->execute();
$result = $supplierCheck->get_result();
if($supplier = $result->fetch_assoc()){
  echo "✓ Supplier found: {$supplier['name']}\n\n";
} else {
  echo "✗ Supplier not found or inactive\n";
  exit;
}
$supplierCheck->close();

// Try to insert PO
echo "Attempting to insert PO...\n";
$status = 'pending';
$insert = $conn->prepare("
  INSERT INTO purchase_orders (supplier_id, order_date, notes, status, created_at)
  VALUES (?, ?, ?, ?, NOW())
");

if(!$insert){
  echo "✗ Prepare error: " . $conn->error . "\n";
  exit;
}

echo "Prepared statement OK\n";

$insert->bind_param('isss', $supplier_id, $order_date, $notes, $status);
echo "Binding OK\n";

if($insert->execute()){
  $po_id = $insert->insert_id;
  echo "✓ Success! PO created with ID: $po_id\n";
  echo "JSON Response:\n";
  echo json_encode([
    'success' => true, 
    'message' => 'Purchase Order created successfully',
    'po_id' => $po_id
  ]);
} else {
  echo "✗ Execute error: " . $conn->error . "\n";
  echo "Execute error code: " . $insert->errno . "\n";
  echo "JSON Response:\n";
  echo json_encode(['success' => false, 'message' => 'Failed to create PO: ' . $conn->error]);
}
$insert->close();
?>
