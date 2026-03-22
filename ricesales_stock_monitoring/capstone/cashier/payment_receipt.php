<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'cashier'){ header("Location: ../login.php"); exit; }

include '../config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$payment_id = (int)($_GET['payment_id'] ?? 0);
if($payment_id <= 0){
  die("Invalid payment.");
}

$companyName = "DE ORO HIYS GENERAL MERCHANDISE";
$companyAddr = "V Castro St, Carmen";
$vatTin      = "000-000-000-000";
$posSn       = "POS01-SN: XXXXXXXX";
$minNo       = "MIN#XXXXXXXXXXXX";

$discountType = strtoupper(trim((string)($_GET['discount'] ?? ''))); // PWD | SC | ''
$hasDiscount  = in_array($discountType, ['PWD','SC'], true);

$stmt = $conn->prepare("
  SELECT
    p.payment_id, p.sale_id, p.amount, p.method, p.status, p.paid_at, p.external_ref,
    s.total_amount, s.status AS sale_status, s.sale_date,
    c.first_name, c.last_name, c.phone, c.address,
    u.username AS cashier_name,
    ar.amount_paid, ar.balance, ar.due_date, ar.status AS ar_status
  FROM payments p
  JOIN sales s ON p.sale_id = s.sale_id
  LEFT JOIN account_receivable ar ON ar.sales_id = s.sale_id
  LEFT JOIN customers c ON s.customer_id = c.customer_id
  LEFT JOIN users u ON s.user_id = u.user_id
  WHERE p.payment_id = ?
  LIMIT 1
");
if(!$stmt){ die("Prepare failed: ".$conn->error); }

$stmt->bind_param("i", $payment_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$data){
  die("Payment not found.");
}

$customer = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
if($customer === '') $customer = 'Walk-in';

$remaining = (float)($data['balance'] ?? 0);
$arStatus  = strtolower(trim((string)($data['ar_status'] ?? ''))); // unpaid/partial/paid
$fullyPaid = ($arStatus === 'paid' || $remaining <= 0.00001);

$payLabel = $fullyPaid ? "PAYMENT RECEIPT (FULLY PAID)" : "PAYMENT RECEIPT (PARTIAL)";

$cashGiven = null;
$change = null;
if(isset($_GET['cash']) && is_numeric($_GET['cash'])){
  $cashGiven = (float)$_GET['cash'];
  $change = $cashGiven - (float)$data['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment Receipt #<?= (int)$payment_id ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{ background:#f4f6f9; }
.paper{
  max-width:420px;
  margin:20px auto;
  background:#fff;
  padding:18px;
  border-radius:10px;
  box-shadow:0 6px 16px rgba(0,0,0,.12);
}
.mono{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
               "Liberation Mono", "Courier New", monospace;
}
.small2{ font-size:12px; }
.dash{ border-top:1px dashed #777; margin:10px 0; }
@media print{
  body{ background:#fff; }
  .no-print{ display:none !important; }
  .paper{ box-shadow:none; border-radius:0; margin:0; max-width:100%; }
}
</style>
</head>

<body>
<div class="paper mono">

  <!-- HEADER -->
  <div class="text-center">
    <div class="fw-bold"><?= h($companyName) ?></div>
    <div class="small2"><?= h($companyAddr) ?></div>
    <div class="small2">VAT REG TIN: <?= h($vatTin) ?></div>
    <div class="small2"><?= h($posSn) ?></div>
    <div class="small2"><?= h($minNo) ?></div>
    <div class="fw-bold mt-2"><?= h($payLabel) ?></div>
  </div>

  <div class="dash"></div>

  <!-- DETAILS -->
  <div class="small2">
    <div><b>Payment #:</b> <?= (int)$data['payment_id'] ?></div>
    <div><b>Invoice / Sale #:</b> <?= (int)$data['sale_id'] ?></div>
    <div><b>Sale Date:</b> <?= !empty($data['sale_date']) ? h(date("m/d/Y H:i", strtotime($data['sale_date']))) : '—' ?></div>
    <div><b>Payment Date:</b> <?= !empty($data['paid_at']) ? h(date("m/d/Y H:i", strtotime($data['paid_at']))) : '—' ?></div>
    <div><b>Cashier:</b> <?= h($data['cashier_name'] ?: 'Cashier') ?></div>
    <div><b>Customer:</b> <?= h($customer) ?></div>
    <?php if(!empty($data['phone'])): ?>
      <div><b>Phone:</b> <?= h($data['phone']) ?></div>
    <?php endif; ?>
  </div>

  <?php if($hasDiscount): ?>
    <div class="small2 mt-1 text-muted">
      <b>Note:</b> Discount applied on original sale: <b>LESS <?= h($discountType) ?> DISC</b>
    </div>
  <?php endif; ?>

  <div class="dash"></div>

  <!-- PAYMENT -->
  <div class="small2">
    <div class="d-flex justify-content-between fw-bold">
      <span>PAYMENT AMOUNT</span>
      <span>₱<?= number_format((float)$data['amount'],2) ?></span>
    </div>

    <div class="d-flex justify-content-between">
      <span>Method</span>
      <span><?= h(strtoupper((string)($data['method'] ?? 'CASH'))) ?></span>
    </div>

    <?php if(!empty($data['external_ref'])): ?>
      <div class="d-flex justify-content-between">
        <span>Reference</span>
        <span><?= h($data['external_ref']) ?></span>
      </div>
    <?php endif; ?>

    <?php if($cashGiven !== null): ?>
      <div class="d-flex justify-content-between">
        <span>CASH</span>
        <span>₱<?= number_format($cashGiven,2) ?></span>
      </div>
      <div class="d-flex justify-content-between">
        <span>CHANGE</span>
        <span>₱<?= number_format(max(0,$change),2) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <div class="dash"></div>

  <!-- BALANCE SUMMARY -->
  <div class="small2">
    <div class="d-flex justify-content-between">
      <span>Total Utang (Invoice Total)</span>
      <span>₱<?= number_format((float)$data['total_amount'],2) ?></span>
    </div>
    <div class="d-flex justify-content-between">
      <span>Total Paid So Far</span>
      <span>₱<?= number_format((float)($data['amount_paid'] ?? 0),2) ?></span>
    </div>
    <div class="d-flex justify-content-between fw-bold">
      <span>Remaining Balance</span>
      <span>₱<?= number_format(max(0,$remaining),2) ?></span>
    </div>

    <?php if(!empty($data['due_date'])): ?>
      <div class="d-flex justify-content-between">
        <span>Due Date</span>
        <span><?= h(date("m/d/Y", strtotime($data['due_date']))) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <div class="dash"></div>

  <!-- STATUS -->
  <?php if($fullyPaid): ?>
    <div class="alert alert-success py-2 small2 mb-2">
      <div class="fw-bold">STATUS: FULLY PAID</div>
      <div class="text-muted">Utang cleared. Thank you!</div>
    </div>
  <?php else: ?>
    <div class="alert alert-warning py-2 small2 mb-2">
      <div class="fw-bold">STATUS: PARTIAL PAYMENT</div>
      <div class="text-muted">Please pay remaining balance on/before due date.</div>
    </div>
  <?php endif; ?>

  <div class="text-center small2 text-muted mt-2">
    --- END OF PAYMENT RECEIPT ---
  </div>

  <div class="no-print d-grid gap-2 mt-3">
    <button class="btn btn-dark" onclick="window.print()">Print Receipt</button>
    <a class="btn btn-outline-secondary" href="payments.php">Back to Payments</a>
  </div>

</div>
</body>
</html>
