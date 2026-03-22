<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'cashier'){ header("Location: ../login.php"); exit; }

include '../config/db.php';
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$sale_id = (int)($_GET['sale_id'] ?? 0);
if($sale_id <= 0){ die("Invalid sale."); }

$companyName = "DE ORO HIYS GENERAL MERCHANDISE";
$companyAddr = "V Castro St, Carmen";
$vatTin      = "000-000-000-000";
$posSn       = "POS01-SN: XXXXXXXX";
$minNo       = "MIN#XXXXXXXXXXXX";

$VAT_MODE = "EXEMPT"; // EXEMPT | VATABLE
$VAT_RATE = 0.12;

$stmt = $conn->prepare("
  SELECT s.sale_id, s.sale_date, s.total_amount, s.status,
         c.customer_id, c.first_name, c.last_name, c.phone, c.address,
         u.username AS cashier_name
  FROM sales s
  LEFT JOIN customers c ON s.customer_id = c.customer_id
  LEFT JOIN users u ON s.user_id = u.user_id
  WHERE s.sale_id = ?
  LIMIT 1
");
if(!$stmt){ die("DB error (sale): ".$conn->error); }
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$sale){ die("Sale not found."); }

$stmt = $conn->prepare("
  SELECT
    si.sales_item_id,
    si.product_id,
    si.qty_kg,
    si.unit_price,
    si.line_total,
    p.variety,
    p.grade,
    p.unit_weight_kg
  FROM sales_items si
  LEFT JOIN products p ON si.product_id = p.product_id
  WHERE si.sale_id = ?
  ORDER BY si.sales_item_id ASC
");
if(!$stmt){ die("DB error (items): ".$conn->error); }
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$resItems = $stmt->get_result();
$items = [];
while($r = $resItems->fetch_assoc()){ $items[] = $r; }
$stmt->close();

$isUtang = (strtolower((string)$sale['status']) === 'unpaid');
$ar = null;

if($isUtang){
  $stmt = $conn->prepare("
    SELECT total_amount, amount_paid, balance, due_date, status
    FROM account_receivable
    WHERE sales_id = ?
    LIMIT 1
  ");
  if($stmt){
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $ar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

$payment = null;
if(!$isUtang){
  $stmt = $conn->prepare("
    SELECT payment_id, amount, method, paid_at
    FROM payments
    WHERE sale_id = ?
    ORDER BY paid_at DESC
    LIMIT 1
  ");
  if($stmt){
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
}

$discType = 'none';
$discRate = 0.0;
$discAmt  = 0.0;
$discName = '';
$discIdNo = '';

$unitMap = []; // product_id => ['unit_type'=>..., 'qty_input'=>...]
if(isset($_SESSION['sale_discount'][$sale_id]) && is_array($_SESSION['sale_discount'][$sale_id])){
  $d = $_SESSION['sale_discount'][$sale_id];

  $discType = strtolower(trim((string)($d['type'] ?? 'none')));
  $discRate = (float)($d['rate'] ?? 0);
  $discAmt  = (float)($d['disc'] ?? 0);
  $discName = (string)($d['name'] ?? '');
  $discIdNo = (string)($d['idno'] ?? '');

  if(isset($d['units']) && is_array($d['units'])){
    foreach($d['units'] as $u){
      $pid = (int)($u['product_id'] ?? 0);
      if($pid > 0){
        $unitMap[$pid] = [
          'unit_type' => strtolower((string)($u['unit_type'] ?? 'kg')),
          'qty_input' => (float)($u['qty_input'] ?? 0),
        ];
      }
    }
  }
}

$subtotal = 0.0;
foreach($items as $it){
  $line = (float)($it['line_total'] ?? 0);
  if($line <= 0) $line = (float)$it['qty_kg'] * (float)$it['unit_price'];
  $subtotal += $line;
}
if($subtotal < 0) $subtotal = 0;

$gross = $subtotal;
$net   = (float)($sale['total_amount'] ?? 0);
if($net <= 0) $net = max(0, $gross - $discAmt);

/* If session discount missing but net < gross, infer */
if($discType === 'none' && $gross > 0 && $net < $gross){
  $discAmt = max(0, round($gross - $net, 2));
}

/* VAT breakdown display */
$vatableSales = 0.0;
$vatExemptSales = 0.0;
$vatZeroRatedSales = 0.0;
$vatAmount = 0.0;
$lessVat = 0.0;

if($VAT_MODE === "VATABLE"){
  $salesWithoutVat = $net / (1 + $VAT_RATE);
  $lessVat = $net - $salesWithoutVat;

  if(in_array($discType, ['pwd','sc'], true)){
    $vatableSales = 0.0;
    $vatAmount = 0.0;
    $vatExemptSales = $salesWithoutVat;
  } else {
    $vatableSales = $salesWithoutVat;
    $vatAmount = $lessVat;
    $vatExemptSales = 0.0;
  }
} else {
  $vatExemptSales = $net;
}

$receiptTitle = $isUtang ? "SALES INVOICE (UTANG)" : "OFFICIAL RECEIPT (CASH)";
$customerName = trim(($sale['first_name'] ?? '').' '.($sale['last_name'] ?? ''));
if($customerName === '') $customerName = 'Walk-in';

/* Optional manual cash input without DB change: ?cash=500 */
$cashGiven = null;
$change = null;
if(isset($_GET['cash']) && is_numeric($_GET['cash'])){
  $cashGiven = (float)$_GET['cash'];
  $change = $cashGiven - $net;
}

function peso($n){
  return number_format((float)$n, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt #<?= (int)$sale_id ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
  --paper-w: 420px;
  --muted: #6c757d;
}
body{ background:#f4f6f9; }
.paper{
  max-width: var(--paper-w);
  margin: 18px auto;
  background: #fff;
  padding: 18px 18px 14px;
  border-radius: 14px;
  box-shadow: 0 10px 24px rgba(0,0,0,.12);
}
.mono{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
               "Liberation Mono", "Courier New", monospace;
}
.small2{ font-size: 12px; line-height: 1.25; }
.small3{ font-size: 11px; line-height: 1.2; color: var(--muted); }
.hr-dash{ border-top:1px dashed #888; margin: 10px 0; }
.kv{
  display:flex; justify-content:space-between; gap:10px;
}
.kv span:first-child{ color: var(--muted); }
.badge-pill{
  display:inline-block; padding:4px 8px; border-radius:999px; font-size:11px;
}
.badge-paid{ background:#e7f7ee; color:#127a3c; border:1px solid #bfe7ce; }
.badge-ut{ background:#fff3cd; color:#7a5a00; border:1px solid #ffe69c; }
.totalBox{
  border:1px solid #e9ecef;
  border-radius: 12px;
  padding: 10px 12px;
  background: #fafbfc;
}
.totalDue{
  font-size: 22px;
  font-weight: 800;
  letter-spacing: .2px;
}
.tableMini{
  width:100%;
  border-collapse: collapse;
}
.tableMini td{
  padding: 6px 0;
  vertical-align: top;
}
.tableMini tr + tr td{
  border-top: 1px dashed #ddd;
}
.amount{
  text-align:right; white-space:nowrap;
}
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
    <div class="small3"><?= h($companyAddr) ?></div>
    <div class="small3">VAT REG TIN: <?= h($vatTin) ?></div>
    <div class="small3"><?= h($posSn) ?> • <?= h($minNo) ?></div>

    <div class="mt-2 fw-bold"><?= h($receiptTitle) ?></div>

    <div class="mt-2 d-flex justify-content-center gap-2 flex-wrap">
      <?php if(!$isUtang): ?>
        <span class="badge-pill badge-paid">PAID</span>
      <?php else: ?>
        <span class="badge-pill badge-ut">UTANG / UNPAID</span>
      <?php endif; ?>

      <?php if(in_array($discType, ['pwd','sc'], true)): ?>
        <span class="badge-pill" style="background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;">
          <?= strtoupper($discType) ?> 20% DISCOUNT
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="hr-dash"></div>

  <!-- META -->
  <div class="small2">
    <div class="kv"><span>Sale #</span><span><b><?= (int)$sale['sale_id'] ?></b></span></div>
    <div class="kv"><span>Date</span><span><?= h(date("m/d/Y h:i A", strtotime($sale['sale_date']))) ?></span></div>
    <div class="kv"><span>Cashier</span><span><?= h($sale['cashier_name'] ?: 'Cashier') ?></span></div>
    <div class="kv"><span>Customer</span><span><?= h($customerName) ?></span></div>
    <?php if(!empty($sale['phone'])): ?>
      <div class="kv"><span>Phone</span><span><?= h($sale['phone']) ?></span></div>
    <?php endif; ?>

    <?php if(in_array($discType, ['pwd','sc'], true) && (trim($discName) !== '' || trim($discIdNo) !== '')): ?>
      <div class="mt-2 p-2 rounded" style="background:#f8fafc;border:1px solid #e5e7eb;">
        <div class="small2"><b>Discount Details</b></div>
        <?php if(trim($discName) !== ''): ?><div class="small3">Name: <?= h($discName) ?></div><?php endif; ?>
        <?php if(trim($discIdNo) !== ''): ?><div class="small3">ID No: <?= h($discIdNo) ?></div><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="hr-dash"></div>

  <!-- ITEMS -->
  <div class="small2 fw-bold d-flex justify-content-between">
    <span>ITEMS</span><span>AMOUNT</span>
  </div>

  <table class="tableMini small2 mt-1">
    <?php foreach($items as $it): ?>
      <?php
        $pid  = (int)($it['product_id'] ?? 0);
        $name = trim(($it['variety'] ?? 'Rice') . ' - ' . ($it['grade'] ?? ''));

        $qtyKg = (float)($it['qty_kg'] ?? 0);
        $unitPrice = (float)($it['unit_price'] ?? 0);
        $line = (float)($it['line_total'] ?? 0);
        if($line <= 0) $line = $qtyKg * $unitPrice;

        $sackW = (float)($it['unit_weight_kg'] ?? 0);

        // Determine unit display (from session saved in pos.php)
        $uType = 'kg';
        $qtyInput = 0.0;
        if(isset($unitMap[$pid])){
          $uType = ($unitMap[$pid]['unit_type'] === 'sack') ? 'sack' : 'kg';
          $qtyInput = (float)$unitMap[$pid]['qty_input'];
        }

        // Fallbacks if session missing:
        if($qtyInput <= 0){
          if($uType === 'sack' && $sackW > 0){
            $qtyInput = $qtyKg / $sackW;
          } else {
            $uType = 'kg';
            $qtyInput = $qtyKg;
          }
        }

        $qtyLabel = ($uType === 'sack')
          ? (number_format($qtyInput,0) . " sack(s)")
          : (number_format($qtyInput,2) . " kg");

        $unitLabel = ($uType === 'sack') ? "/sack" : "/kg";
      ?>
      <tr>
        <td style="padding-right:8px;">
          <div class="fw-semibold"><?= h($name) ?></div>
          <div class="small3">
            <?= h($qtyLabel) ?> × <?= peso($unitPrice) ?><?= h($unitLabel) ?>
            <?php if($uType === 'sack'): ?>
              • <?= number_format($qtyKg,2) ?> kg total
            <?php endif; ?>
          </div>
        </td>
        <td class="amount fw-bold"><?= peso($line) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="hr-dash"></div>

  <!-- TOTALS -->
  <div class="totalBox">
    <div class="small2 kv"><span>Subtotal</span><span><?= peso($gross) ?></span></div>

    <?php if($discAmt > 0.00001): ?>
      <div class="small2 kv text-danger">
        <span>Less <?= (in_array($discType,['pwd','sc'],true) ? strtoupper($discType).' Discount' : 'Discount') ?></span>
        <span>- <?= peso($discAmt) ?></span>
      </div>
    <?php endif; ?>

    <?php if($VAT_MODE === "VATABLE" && !in_array($discType, ['pwd','sc'], true)): ?>
      <div class="small2 kv">
        <span>VAT (12%)</span>
        <span><?= peso($lessVat) ?></span>
      </div>
    <?php endif; ?>

    <div class="hr-dash"></div>

    <div class="d-flex justify-content-between align-items-end">
      <div class="small2 text-muted">TOTAL DUE</div>
      <div class="totalDue">₱ <?= peso($net) ?></div>
    </div>

    <?php if(!$isUtang): ?>
      <div class="small3 mt-2">
        Payment: <?= h(strtoupper($payment['method'] ?? 'CASH')) ?>
        <?php if($cashGiven !== null): ?>
          • Cash: <?= peso($cashGiven) ?> • Change: <?= peso(max(0,$change)) ?>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="small3 mt-2">
        Paid: <?= peso((float)($ar['amount_paid'] ?? 0)) ?>
        • Balance: <b><?= peso((float)($ar['balance'] ?? $net)) ?></b>
        <?php if(!empty($ar['due_date'])): ?>
          • Due: <?= h(date("m/d/Y", strtotime($ar['due_date']))) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="hr-dash"></div>

  <!-- VAT BREAKDOWN -->
  <div class="small2">
    <div class="kv"><span>VATable Sales</span><span><?= peso($vatableSales) ?></span></div>
    <div class="kv"><span>VAT-Exempt Sales</span><span><?= peso($vatExemptSales) ?></span></div>
    <div class="kv"><span>VAT Zero-Rated Sales</span><span><?= peso($vatZeroRatedSales) ?></span></div>
    <div class="kv"><span>VAT Amount</span><span><?= peso($vatAmount) ?></span></div>
  </div>

  <div class="text-center small3 mt-3">
    — END OF RECEIPT —
  </div>

  <div class="no-print d-grid gap-2 mt-3">
    <button class="btn btn-dark" onclick="window.print()">Print Receipt</button>
    <a href="pos.php" class="btn btn-outline-secondary">Back to Sale</a>
  </div>

</div>
</body>
</html>