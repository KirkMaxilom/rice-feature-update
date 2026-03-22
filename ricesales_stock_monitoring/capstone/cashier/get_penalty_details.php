<?php
session_start();
if(!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'cashier'){
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

include '../config/db.php';

header('Content-Type: application/json');

$customer_id = (int)($_GET['customer_id'] ?? 0);
if($customer_id <= 0){
  echo json_encode(['error' => 'Invalid customer']);
  exit;
}

// Get all AR records with their penalties for this customer
$sql = "
  SELECT 
    ar.ar_id,
    ar.reference,
    ar.total_amount,
    ar.balance,
    ar.due_date,
    p.penalty_id,
    p.penalty_amount,
    p.penalty_rate,
    p.days_late,
    p.last_calculated,
    p.created_at
  FROM account_receivable ar
  LEFT JOIN penalties p ON ar.ar_id = p.reference_id AND p.reference_type = 'receivable'
  WHERE ar.customer_id = ?
  ORDER BY ar.due_date DESC
";

$stmt = $conn->prepare($sql);
if(!$stmt){
  echo json_encode(['error' => 'Query failed']);
  exit;
}

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$penalties = [];
$has_penalties = false;

while($row = $result->fetch_assoc()){
  if(!empty($row['penalty_id'])){
    $has_penalties = true;
    $penalties[] = [
      'ar_id' => (int)$row['ar_id'],
      'reference' => $row['reference'],
      'due_date' => $row['due_date'],
      'penalty_id' => (int)$row['penalty_id'],
      'penalty_amount' => (float)$row['penalty_amount'],
      'penalty_rate' => (float)$row['penalty_rate'],
      'days_late' => (int)$row['days_late'],
      'last_calculated' => $row['last_calculated'],
      'created_at' => $row['created_at']
    ];
  }
}

$stmt->close();

echo json_encode([
  'success' => true,
  'has_penalties' => $has_penalties,
  'penalties' => $penalties
]);
?>
