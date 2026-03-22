<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ../login.php"); exit; }
if(strtolower($_SESSION['role'] ?? '') !== 'owner'){ header("Location: ../login.php"); exit; }

$username = $_SESSION['username'] ?? 'Owner';
include '../config/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$LEAD_TIME_DAYS = 2;
$SAFETY_STOCK_KG = 10;
$TARGET_DAYS_COVER= 7;
$RESTOCK_LOOKBACK_DAYS = 30;

$MONTHS_HISTORY = 12;
$FORECAST_MONTHS = 3;

$VALID_SALE_STATUSES = "('paid','unpaid')";

$salesPerProduct = [];
$sql = "
SELECT
  p.product_id,
  CONCAT(p.variety,' - ',p.grade) AS product_label,
  SUM(si.qty_kg) AS total_sold_kg,
  SUM(si.line_total) AS total_revenue
FROM sales_items si
JOIN sales s ON si.sale_id = s.sale_id
JOIN products p ON si.product_id = p.product_id
WHERE p.archived=0
  AND LOWER(s.status) IN $VALID_SALE_STATUSES
GROUP BY p.product_id
ORDER BY total_sold_kg DESC
";
$res = $conn->query($sql);
if($res){
  while($row = $res->fetch_assoc()){
    $row['total_sold_kg'] = (float)($row['total_sold_kg'] ?? 0);
    $row['total_revenue'] = (float)($row['total_revenue'] ?? 0);
    $salesPerProduct[] = $row;
  }
}

$months = [];
$salesKgData = [];
$salesRevData = [];

// used for correct forecast labels (base from last visible month)
$monthsY = [];
$monthsM = [];
$monthMapKg = [];
$monthMapRev = [];

$sql = "
SELECT
  DATE_FORMAT(s.sale_date,'%b %Y') AS month_label,
  YEAR(s.sale_date) AS y,
  MONTH(s.sale_date) AS m,
  COALESCE(SUM(si.qty_kg),0) AS total_kg,
  COALESCE(SUM(si.line_total),0) AS total_rev
FROM sales_items si
JOIN sales s ON si.sale_id = s.sale_id
WHERE LOWER(s.status) IN $VALID_SALE_STATUSES
  AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL {$MONTHS_HISTORY} MONTH)
  AND s.sale_date <> '0000-00-00 00:00:00'
GROUP BY y,m,month_label
ORDER BY y,m
";
$res = $conn->query($sql);
if($res){
  while($row = $res->fetch_assoc()){
    $y = (int)$row['y'];
    $m = (int)$row['m'];
    $key = sprintf('%04d-%02d', $y, $m);
    $monthMapKg[$key] = (float)$row['total_kg'];
    $monthMapRev[$key] = (float)$row['total_rev'];
  }
}

// Build continuous month series (fills missing months with 0)
$start = new DateTime('first day of this month');
$start->modify('-' . max(0, $MONTHS_HISTORY - 1) . ' months');
$end = new DateTime('first day of this month');

for($dt = clone $start; $dt <= $end; $dt->modify('+1 month')){
  $y = (int)$dt->format('Y');
  $m = (int)$dt->format('m');
  $key = sprintf('%04d-%02d', $y, $m);

  $months[] = $dt->format('M Y');
  $monthsY[] = $y;
  $monthsM[] = $m;
  $salesKgData[] = (float)($monthMapKg[$key] ?? 0);
  $salesRevData[] = (float)($monthMapRev[$key] ?? 0);
}

$topProduct = $salesPerProduct[0]['product_label'] ?? 'N/A';
$totalSoldKg = array_sum($salesKgData);
$totalRevenue = array_sum($salesRevData);

$avgSellPricePerKg = ($totalSoldKg > 0) ? ($totalRevenue / $totalSoldKg) : 0;

$totalMonths = count($salesKgData);
$growth = 0;
if($totalMonths >= 2 && (float)$salesKgData[$totalMonths-2] > 0){
  $growth = (($salesKgData[$totalMonths-1] - $salesKgData[$totalMonths-2]) / $salesKgData[$totalMonths-2]) * 100;
}

function nextMonthsLabelsFromLast(array $monthsY, array $monthsM, int $n = 3){
  $labels = [];

  if(empty($monthsY) || empty($monthsM)){
    $dt = new DateTime('first day of this month');
  } else {
    $lastY = (int)end($monthsY);
    $lastM = (int)end($monthsM);
    $dt = new DateTime(sprintf('%04d-%02d-01', $lastY, $lastM));
  }

  for($i=1; $i<=$n; $i++){
    $dt->modify('+1 month');
    $labels[] = $dt->format('M Y');
  }
  return $labels;
}

$forecastLabels = nextMonthsLabelsFromLast($monthsY, $monthsM, (int)$FORECAST_MONTHS);
$forecastData = [];

if(count($salesKgData) > 0){
  $series = $salesKgData;

  for($i=0; $i<$FORECAST_MONTHS; $i++){
    $win = min(3, count($series));                  // ✅ adaptive SMA window
    $slice = array_slice($series, -$win);
    $avg = array_sum($slice) / max(1, count($slice));
    $avg = round($avg, 2);

    $forecastData[] = $avg;
    $series[] = $avg;                               // rolling forecast
  }
} else {
  $forecastData = array_fill(0, (int)$FORECAST_MONTHS, 0.00);
}

$combinedLabels = array_merge($months, $forecastLabels);
$actualPadded = array_merge($salesKgData, array_fill(0, count($forecastLabels), null));
$forecastPadded = array_merge(array_fill(0, count($months), null), $forecastData);

$forecastTable = [];
for($i=0; $i<count($forecastLabels); $i++){
  $forecastTable[] = ['month'=>$forecastLabels[$i], 'pred'=>$forecastData[$i]];
}

$daysOrder = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$daySalesMap = array_fill_keys($daysOrder, 0.0);

$sql = "
SELECT
  DAYOFWEEK(s.sale_date) AS dow,
  SUM(si.qty_kg) AS total_kg
FROM sales_items si
JOIN sales s ON si.sale_id = s.sale_id
WHERE LOWER(s.status) IN $VALID_SALE_STATUSES
  AND s.sale_date >= (CURDATE() - INTERVAL 90 DAY)
  AND s.sale_date <> '0000-00-00 00:00:00'
GROUP BY dow
";
$res = $conn->query($sql);
if($res){
  while($r = $res->fetch_assoc()){
    $dow = (int)$r['dow']; // 1=Sun ... 7=Sat
    $kg = (float)$r['total_kg'];

    $label = 'Sun';
    if($dow===2) $label='Mon';
    if($dow===3) $label='Tue';
    if($dow===4) $label='Wed';
    if($dow===5) $label='Thu';
    if($dow===6) $label='Fri';
    if($dow===7) $label='Sat';
    if($dow===1) $label='Sun';

    $daySalesMap[$label] = $kg;
  }
}
$daySalesLabels = array_keys($daySalesMap);
$daySalesData = array_values($daySalesMap);

$peakDay = 'N/A';
$peakKg = 0;
foreach($daySalesMap as $d=>$kg){
  if($kg > $peakKg){ $peakKg=$kg; $peakDay=$d; }
}

$restockRows = [];
$fastMovers = [];
$slowMovers = [];

$sql = "
SELECT
  p.product_id,
  CONCAT(p.variety,' - ',p.grade) AS product_label,

  /* CURRENT STOCK */
  IFNULL(SUM(
    CASE
      WHEN LOWER(it.type)='in' THEN it.qty_kg
      WHEN LOWER(it.type)='out' THEN -it.qty_kg
      WHEN LOWER(it.type)='adjust' THEN it.qty_kg
      ELSE 0
    END
  ), 0) AS current_stock,

  /* SOLD LAST N DAYS */
  (
    SELECT IFNULL(SUM(si.qty_kg),0)
    FROM sales_items si
    JOIN sales s ON s.sale_id = si.sale_id
    WHERE si.product_id = p.product_id
      AND LOWER(s.status) IN $VALID_SALE_STATUSES
      AND s.sale_date >= (CURDATE() - INTERVAL {$RESTOCK_LOOKBACK_DAYS} DAY)
      AND s.sale_date <> '0000-00-00 00:00:00'
  ) AS sold_last_n

FROM products p
LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
WHERE p.archived=0
GROUP BY p.product_id
ORDER BY sold_last_n DESC
";
$res = $conn->query($sql);
$allProdForRestock = [];
if($res){
  while($r = $res->fetch_assoc()){
    $r['current_stock'] = (float)($r['current_stock'] ?? 0);
    $r['sold_last_n'] = (float)($r['sold_last_n'] ?? 0);
    $allProdForRestock[] = $r;
  }
}

foreach($allProdForRestock as &$r){
  $current = (float)$r['current_stock'];
  $soldN = (float)$r['sold_last_n'];

  $avgDaily = $soldN / max(1, $RESTOCK_LOOKBACK_DAYS);
  $reorderPoint = ($avgDaily * $LEAD_TIME_DAYS) + $SAFETY_STOCK_KG;
  $targetStock = ($avgDaily * $TARGET_DAYS_COVER);

  $suggested = $targetStock - $current;
  if($suggested < 0) $suggested = 0;

  $status = 'OK';
  if($current <= 0) $status = 'OUT';
  else if($current <= $reorderPoint) $status = 'REORDER';

  $r['_avgDaily'] = $avgDaily;
  $r['_reorderPoint'] = $reorderPoint;
  $r['_suggested'] = $suggested;
  $r['_status'] = $status;
}
unset($r);

$restockCandidates = [];
foreach($allProdForRestock as $r){
  $avgDaily = (float)($r['_avgDaily'] ?? 0);
  $current = (float)$r['current_stock'];
  $daysCover = ($avgDaily > 0) ? ($current / $avgDaily) : 9999;

  if(($r['_status'] ?? 'OK') !== 'OK'){
    $r['_daysCover'] = $daysCover;
    $restockCandidates[] = $r;
  }
}

usort($restockCandidates, function($a,$b){
  $prio = ['OUT'=>0,'REORDER'=>1,'OK'=>2];
  $pa = $prio[$a['_status']] ?? 9;
  $pb = $prio[$b['_status']] ?? 9;
  if($pa !== $pb) return $pa <=> $pb;

  $da = (float)($a['_daysCover'] ?? 9999);
  $db = (float)($b['_daysCover'] ?? 9999);
  return $da <=> $db;
});

$restockRows = array_slice($restockCandidates, 0, 5);
$fastMovers = array_slice($allProdForRestock, 0, 5);

$slowMovers = $allProdForRestock;
usort($slowMovers, fn($a,$b)=> (float)$a['sold_last_n'] <=> (float)$b['sold_last_n']);
$slowMovers = array_slice($slowMovers, 0, 5);
$growthClass = ($growth >= 0) ? 'text-success' : 'text-danger';
$growthIcon = ($growth >= 0) ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';

$topProdLabels = [];
$topProdKg = [];
foreach(array_slice($salesPerProduct, 0, 10) as $r){
  $topProdLabels[] = (string)$r['product_label'];
  $topProdKg[] = (float)$r['total_sold_kg'];
}

$stockLabels = [];
$stockData = [];
$stockSorted = $allProdForRestock;
usort($stockSorted, fn($a,$b)=> (float)$b['current_stock'] <=> (float)$a['current_stock']);
foreach(array_slice($stockSorted, 0, 10) as $r){
  $stockLabels[] = (string)$r['product_label'];
  $stockData[] = (float)$r['current_stock'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Analytics & Forecasting | Owner</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="../css/layout.css" rel="stylesheet">

<style>
.chartWrap{ position:relative; height:260px; }
.modalChartWrap{ position:relative; height:420px; }
</style>
</head>
<body>

<?php include '../includes/topnav.php'; ?>

<div class="container-fluid">
<div class="row">

<?php include '../includes/owner_sidebar.php'; ?>

<main class="col-lg-10 ms-sm-auto px-4 main-content">
<div class="py-4">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h3 class="fw-bold mb-1">Analytics & Forecasting</h3>
    <div class="text-muted">Same dashboard as Admin (Owner view, read-only)</div>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-dark" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#moreChartsModal">
      <i class="fa-solid fa-chart-pie me-1"></i> More Charts
    </button>
  </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-3">
    <div class="card card-soft">
      <div class="card-body">
        <div class="text-muted small">Total Sold (History)</div>
        <div class="h3 fw-bold mb-0"><?= number_format((float)$totalSoldKg,2) ?> kg</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card card-soft">
      <div class="card-body">
        <div class="text-muted small">Total Revenue (History)</div>
        <div class="h3 fw-bold mb-0">₱<?= number_format((float)$totalRevenue,2) ?></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card card-soft">
      <div class="card-body">
        <div class="text-muted small">Avg Sell Price</div>
        <div class="h3 fw-bold mb-0">₱<?= number_format((float)$avgSellPricePerKg,2) ?>/kg</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card card-soft">
      <div class="card-body">
        <div class="text-muted small">Next Month Forecast</div>
        <div class="h3 fw-bold mb-0"><?= number_format((float)$forecastData[0],2) ?> kg</div>
        <div class="small mt-1 <?= $growthClass ?>"><i class="fa-solid <?= $growthIcon ?> me-1"></i>Monthly growth: <?= number_format((float)$growth,1) ?>%</div>
      </div>
    </div>
  </div>
</div>

<!-- Main Charts -->
<div class="analytics-row mb-3">
  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Monthly Sales Trend (KG)</h5>
    <div class="small-note mb-2">Last <?= (int)$MONTHS_HISTORY ?> months</div>
    <div class="chartWrap"><canvas id="monthlyKgChart"></canvas></div>
  </div>

  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Sales Over Time + Forecast</h5>
    <div class="small-note mb-2">Actual + dashed forecast (Adaptive SMA up to 3)</div>
    <div class="chartWrap"><canvas id="salesForecastChart"></canvas></div>
  </div>
</div>

<div class="analytics-row mb-3">
  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Sales by Day of Week (Last 90 Days)</h5>
    <div class="small-note">Peak: <b><?= h($peakDay) ?></b> (<?= number_format((float)$peakKg,2) ?> kg)</div>
    <div class="chartWrap"><canvas id="dowChart"></canvas></div>
  </div>

  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Suggested Restock (Top Priority)</h5>
    <div class="small-note mb-2">
      Avg Daily Sales (last <?= (int)$RESTOCK_LOOKBACK_DAYS ?> days),
      Lead Time = <?= (int)$LEAD_TIME_DAYS ?> day(s),
      Safety Stock = <?= number_format($SAFETY_STOCK_KG,0) ?>kg,
      Target Cover = <?= (int)$TARGET_DAYS_COVER ?> day(s).
    </div>

    <?php if(empty($restockRows)): ?>
      <div class="alert alert-success mb-0">
        No urgent restocks right now (based on current data).
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Product</th>
              <th class="text-end">Stock (kg)</th>
              <th class="text-end">Avg/Day (kg)</th>
              <th class="text-end">Reorder Point</th>
              <th class="text-end">Suggested Order (kg)</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($restockRows as $r): ?>
            <?php
              $badge = "success";
              if(($r['_status'] ?? 'OK') === 'REORDER') $badge = "warning";
              if(($r['_status'] ?? 'OK') === 'OUT') $badge = "danger";
            ?>
            <tr>
              <td class="fw-semibold"><?= h($r['product_label']) ?></td>
              <td class="text-end"><?= number_format((float)$r['current_stock'],2) ?></td>
              <td class="text-end"><?= number_format((float)$r['_avgDaily'],2) ?></td>
              <td class="text-end"><?= number_format((float)$r['_reorderPoint'],2) ?></td>
              <td class="text-end fw-bold"><?= number_format((float)$r['_suggested'],2) ?></td>
              <td><span class="badge bg-<?= $badge ?>"><?= h($r['_status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Movers -->
<div class="analytics-row mt-3">
  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Fast Moving Products (Last <?= (int)$RESTOCK_LOOKBACK_DAYS ?> Days)</h5>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th class="text-end">Sold (<?= (int)$RESTOCK_LOOKBACK_DAYS ?>d)</th>
            <th class="text-end">Current Stock (kg)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($fastMovers as $r): ?>
          <tr>
            <td class="fw-semibold"><?= h($r['product_label']) ?></td>
            <td class="text-end"><?= number_format((float)$r['sold_last_n'],2) ?></td>
            <td class="text-end"><?= number_format((float)$r['current_stock'],2) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="analytics-box">
    <h5 class="fw-bold mb-1">Slow Moving Products (Last <?= (int)$RESTOCK_LOOKBACK_DAYS ?> Days)</h5>
    <div class="small-note mb-2">Useful to avoid overstock</div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th class="text-end">Sold (<?= (int)$RESTOCK_LOOKBACK_DAYS ?>d)</th>
            <th class="text-end">Current Stock (kg)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($slowMovers as $r): ?>
          <tr>
            <td class="fw-semibold"><?= h($r['product_label']) ?></td>
            <td class="text-end"><?= number_format((float)$r['sold_last_n'],2) ?></td>
            <td class="text-end"><?= number_format((float)$r['current_stock'],2) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div>
</main>

</div>
</div>

<!-- MODAL: MORE CHARTS -->
<div class="modal fade" id="moreChartsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">More Charts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <h6 class="fw-bold mb-2">Monthly Revenue Trend (₱)</h6>
            <div class="modalChartWrap"><canvas id="monthlyRevChart"></canvas></div>
          </div>

          <div class="col-12 col-lg-6">
            <h6 class="fw-bold mb-2">Top Products (All Time - KG)</h6>
            <div class="modalChartWrap"><canvas id="topProductsChart"></canvas></div>
          </div>

          <div class="col-12 col-lg-6">
            <h6 class="fw-bold mb-2">Current Stock Levels (Top 10)</h6>
            <div class="modalChartWrap"><canvas id="stockChart"></canvas></div>
          </div>

          <div class="col-12 col-lg-6">
            <h6 class="fw-bold mb-2">Forecast (Next <?= (int)$FORECAST_MONTHS ?> Months)</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Month</th>
                    <th class="text-end">Predicted Demand (kg)</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach($forecastTable as $f): ?>
                  <tr>
                    <td><?= h($f['month']) ?></td>
                    <td class="text-end fw-bold"><?= number_format((float)$f['pred'],2) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-muted small mt-2">Forecast uses Adaptive SMA (window up to 3 months).</div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const D = <?= json_encode([
  'months' => $months,
  'salesKgData' => $salesKgData,
  'salesRevData' => $salesRevData,

  'combinedLabels' => $combinedLabels,
  'actualPadded' => $actualPadded,
  'forecastPadded' => $forecastPadded,

  'daySalesLabels' => $daySalesLabels,
  'daySalesData' => $daySalesData,

  'topProdLabels' => $topProdLabels,
  'topProdKg' => $topProdKg,

  'stockLabels' => $stockLabels,
  'stockData' => $stockData
], JSON_UNESCAPED_SLASHES) ?>;

function byId(id){ return document.getElementById(id); }

function fmtNum(v){
  const n = Number(v || 0);
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtKg(v){ return `${fmtNum(v)} kg`; }
function fmtPhp(v){ return `PHP ${fmtNum(v)}`; }

function baseOptions(extra = {}){
  return Object.assign({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: true },
      tooltip: {
        mode:'index',
        intersect:false,
        callbacks: {
          label: (ctx) => `${ctx.dataset.label}: ${fmtNum(ctx.parsed.y ?? ctx.parsed ?? 0)}`
        }
      }
    },
    interaction: { mode:'index', intersect:false },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { callback: (v) => fmtNum(v) }
      }
    }
  }, extra);
}

const charts = {};

function safeChart(id, cfg){
  const el = byId(id);
  if(!el) return null;
  try{ return new Chart(el, cfg); }catch(e){ console.error(e); return null; }
}

// Main charts
charts.monthlyKg = safeChart('monthlyKgChart', {
  type:'line',
  data:{ labels:D.months||[], datasets:[{ label:'KG Sold', data:D.salesKgData||[], tension:0.25, borderWidth:2, pointRadius:3, borderColor:'#1f77b4', backgroundColor:'rgba(31,119,180,0.20)', fill:true }] },
  options: baseOptions({
    plugins: {
      legend: { display: true },
      tooltip: {
        mode:'index',
        intersect:false,
        callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
      }
    },
    scales: { y: { beginAtZero: true, ticks: { callback: (v) => fmtKg(v) } } }
  })
});

charts.salesForecast = safeChart('salesForecastChart', {
  type:'line',
  data:{
    labels: D.combinedLabels||[],
    datasets:[
      { label:'Actual KG', data:D.actualPadded||[], tension:0.25, borderWidth:2, pointRadius:3, borderColor:'#1f77b4' },
      { label:'Forecast KG', data:D.forecastPadded||[], tension:0.25, borderDash:[6,6], borderWidth:2, pointRadius:3, borderColor:'#ff7f0e' }
    ]
  },
  options: baseOptions({
    plugins: {
      legend: { display: true },
      tooltip: {
        mode:'index',
        intersect:false,
        callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
      }
    },
    scales: { y: { beginAtZero: true, ticks: { callback: (v) => fmtKg(v) } } }
  })
});

charts.dow = safeChart('dowChart', {
  type:'bar',
  data:{ labels:D.daySalesLabels||[], datasets:[{ label:'KG Sold', data:D.daySalesData||[], borderWidth:1, backgroundColor:'rgba(214,39,40,0.65)' }] },
  options: baseOptions({
    plugins: {
      legend: { display: true },
      tooltip: {
        mode:'index',
        intersect:false,
        callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
      }
    },
    scales: { y: { beginAtZero: true, ticks: { callback: (v) => fmtKg(v) } } }
  })
});

// Modal charts build-on-open (prevents blank canvas)
function buildModalCharts(){
  if(!charts.monthlyRev){
    charts.monthlyRev = safeChart('monthlyRevChart', {
      type:'line',
      data:{ labels:D.months||[], datasets:[{ label:'Revenue', data:D.salesRevData||[], tension:0.25, borderWidth:2, pointRadius:3, borderColor:'#2ca02c', backgroundColor:'rgba(44,160,44,0.20)', fill:true }] },
      options: baseOptions({
        plugins: {
          legend: { display: true },
          tooltip: {
            mode:'index',
            intersect:false,
            callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtPhp(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
          }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: (v) => fmtPhp(v) } } }
      })
    });
  }
  if(!charts.topProducts){
    const labels = (D.topProdLabels || []).map((l) => String(l).length > 22 ? `${String(l).slice(0, 22)}...` : String(l));
    charts.topProducts = safeChart('topProductsChart', {
      type:'bar',
      data:{ labels, datasets:[{ label:'KG Sold (All Time)', data:D.topProdKg||[], borderWidth:1, backgroundColor:'rgba(255,127,14,0.70)' }] },
      options: baseOptions({
        plugins: {
          legend: { display: true },
          tooltip: {
            mode:'index',
            intersect:false,
            callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
          }
        },
        scales:{ x:{ ticks:{ autoSkip:false, maxRotation:60, minRotation:30 } }, y:{ beginAtZero:true, ticks: { callback: (v) => fmtKg(v) } } }
      })
    });
  }
  if(!charts.stock){
    const labels = (D.stockLabels || []).map((l) => String(l).length > 22 ? `${String(l).slice(0, 22)}...` : String(l));
    charts.stock = safeChart('stockChart', {
      type:'bar',
      data:{ labels, datasets:[{ label:'Current Stock', data:D.stockData||[], borderWidth:1, backgroundColor:'rgba(148,103,189,0.70)' }] },
      options: baseOptions({
        plugins: {
          legend: { display: true },
          tooltip: {
            mode:'index',
            intersect:false,
            callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmtKg(ctx.parsed.y ?? ctx.parsed ?? 0)}` }
          }
        },
        scales:{ x:{ ticks:{ autoSkip:false, maxRotation:60, minRotation:30 } }, y:{ beginAtZero:true, ticks: { callback: (v) => fmtKg(v) } } }
      })
    });
  }

  setTimeout(()=>{ charts.monthlyRev?.resize(); charts.topProducts?.resize(); charts.stock?.resize(); }, 50);
}

document.addEventListener('shown.bs.modal', function(ev){
  if(ev.target && ev.target.id === 'moreChartsModal'){
    buildModalCharts();
  }
});
</script>

</body>
</html>
