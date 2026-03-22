<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$sql = "
  SELECT 
    product_id,
    variety,
    grade,
    price_per_kg
  FROM products
  WHERE archived = 0
  ORDER BY variety ASC
";

$result = $conn->query($sql);

$products = [];
if($result){
  while($row = $result->fetch_assoc()){
    $products[] = $row;
  }
}

echo json_encode([
  'success' => true,
  'products' => $products
]);
?>
