<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$po_id = intval($_GET['po_id'] ?? 0);

if($po_id <= 0){
  echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
  exit;
}

// Verify connection
if(!$conn || $conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

try {
  // Get PO details with supplier info
  $po_sql = "SELECT po.po_id, po.supplier_id, s.name as supplier_name, po.order_date, po.status, po.total_amount, po.notes, po.rating, po.comment FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = ?";

  $po_stmt = $conn->prepare($po_sql);
  if(!$po_stmt){
    throw new Exception('PO Prepare error: ' . $conn->error);
  }

  if(!$po_stmt->bind_param("i", $po_id)){
    throw new Exception('PO Bind param error: ' . $po_stmt->error);
  }
  
  if(!$po_stmt->execute()){
    throw new Exception('PO Execute error: ' . $po_stmt->error);
  }

  $po_result = $po_stmt->get_result();
  if(!$po_result){
    throw new Exception('PO Get result error: ' . $po_stmt->error);
  }
  
  if($po_result->num_rows === 0){
    $po_stmt->close();
    echo json_encode(['success' => false, 'message' => 'PO not found']);
    exit;
  }

  $po = $po_result->fetch_assoc();
  if(!$po){
    throw new Exception('PO Fetch error');
  }
  $po_stmt->close();

  // Get PO items with product details
  $items_sql = "SELECT poi.po_item_id, poi.quantity, poi.price_per_unit, p.product_id, p.variety as product_variety, p.grade as product_grade FROM purchase_order_items poi LEFT JOIN products p ON poi.product_id = p.product_id WHERE poi.po_id = ? ORDER BY poi.po_item_id";

  $items_stmt = $conn->prepare($items_sql);
  if(!$items_stmt){
    throw new Exception('Items Prepare error: ' . $conn->error);
  }

  if(!$items_stmt->bind_param("i", $po_id)){
    throw new Exception('Items Bind param error: ' . $items_stmt->error);
  }
  
  if(!$items_stmt->execute()){
    throw new Exception('Items Execute error: ' . $items_stmt->error);
  }

  $items_result = $items_stmt->get_result();
  if(!$items_result){
    throw new Exception('Items Get result error: ' . $items_stmt->error);
  }

  $items = array();
  while($item = $items_result->fetch_assoc()){
    $items[] = $item;
  }
  $items_stmt->close();

  echo json_encode(array(
    'success' => true,
    'po' => $po,
    'items' => $items
  ));

} catch(Exception $e) {
  error_log('PO Details Error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
