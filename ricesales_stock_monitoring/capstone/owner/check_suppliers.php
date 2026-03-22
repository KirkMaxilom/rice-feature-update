<?php
include '../config/db.php';

echo "Checking suppliers in database...\n";
echo "==================================\n\n";

$result = $conn->query("SELECT supplier_id, name, status FROM suppliers");

if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    echo "ID: {$row['supplier_id']} | Name: {$row['name']} | Status: {$row['status']}\n";
  }
} else {
  echo "No suppliers found in database.\n";
}

echo "\n\nActive suppliers only:\n";
$result2 = $conn->query("SELECT supplier_id, name FROM suppliers WHERE status='active'");

if($result2 && $result2->num_rows > 0){
  while($row = $result2->fetch_assoc()){
    echo "ID: {$row['supplier_id']} | Name: {$row['name']}\n";
  }
} else {
  echo "No active suppliers found.\n";
}
?>
