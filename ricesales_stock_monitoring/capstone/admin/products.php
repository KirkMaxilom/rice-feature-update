  <?php
  session_start();
  if(!isset($_SESSION['user_id'])){
      header("Location: login.php");
      exit;
  }
  $role = $_SESSION['role'] ?? '';
  $username = $_SESSION['username'] ?? 'User';
  $user_id = (int)($_SESSION['user_id'] ?? 0);

  include '../config/db.php';

  $activePage  = 'products';
  $profileLink = 'profile.php';
  $logoutLink  = '../logout.php';

  if($role !== 'admin'){
      header("Location: dashboard.php");
      exit;
  }

  $ALLOWED_GRADES = ['Premium','Special','Regular','Broken'];


  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $CSRF_TOKEN = $_SESSION['csrf_token'];

 
  function logActivity($conn, $user_id, $type, $desc){
      $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, created_at)
                              VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $user_id, $type, $desc);
      $stmt->execute();
      $stmt->close();
  }

  $DEFAULT_LOW_STOCK_THRESHOLD  = 10;
  $DEFAULT_OVERSTOCK_THRESHOLD  = 1000;

  $settingsRow = null;

  $settingsRes = $conn->query("
      SELECT low_threshold_kg, over_threshold_kg
      FROM stock_settings
      WHERE id=1
      LIMIT 1
  ");

  if($settingsRes){
      $settingsRow = $settingsRes->fetch_assoc();
  }

  $LOW_STOCK_THRESHOLD  = (float)($settingsRow['low_threshold_kg'] ?? $DEFAULT_LOW_STOCK_THRESHOLD);
  $OVERSTOCK_THRESHOLD  = (float)($settingsRow['over_threshold_kg'] ?? $DEFAULT_OVERSTOCK_THRESHOLD);

  if(isset($_POST['save_thresholds'])){

    
    if(($role ?? '') !== 'admin'){
      http_response_code(403);
      exit('Forbidden');
    }

    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if(!$postedToken || !hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)){
      header("Location: products.php?error=" . urlencode("Security check failed."));
      exit;
    }

    $admin_password = (string)($_POST['admin_password'] ?? '');
    if($admin_password === ''){
      header("Location: products.php?error=" . urlencode("Admin password required."));
      exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $urow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$urow || !password_verify($admin_password, $urow['password'])){
      header("Location: products.php?error=" . urlencode("Incorrect admin password."));
      exit;
    }

    $low_raw  = $_POST['low_stock_threshold'] ?? null;
    $over_raw = $_POST['overstock_threshold'] ?? null;

    if(!is_numeric($low_raw) || !is_numeric($over_raw)){
      header("Location: products.php?error=" . urlencode("Invalid threshold values."));
      exit;
    }

    $low  = (float)$low_raw;
    $over = (float)$over_raw;

    $MAX_LOW  = 100000;
    $MAX_OVER = 1000000;

    if($low < 0) $low = 0;
    if($low > $MAX_LOW) $low = $MAX_LOW;

    if($over <= 0) $over = $DEFAULT_OVERSTOCK_THRESHOLD;
    if($over > $MAX_OVER) $over = $MAX_OVER;

    if($over <= $low){
        $over = min($MAX_OVER, $low + 1);
    }

    $stmt = $conn->prepare("
        UPDATE stock_settings
        SET low_threshold_kg=?, over_threshold_kg=?
        WHERE id=1
    ");
    $stmt->bind_param("dd", $low, $over);
    $stmt->execute();
    $stmt->close();

    logActivity(
        $conn,
        $user_id,
        "SECURITY",
        "Updated stock thresholds (LOW={$low}, OVER={$over})"
    );

    header("Location: products.php?success=thresholds_saved");
    exit;
  }

  if(isset($_POST['add_product'])){
      $variety        = trim($_POST['variety'] ?? '');
      $grade          = trim($_POST['grade'] ?? '');
      $unit_weight_kg = (float)($_POST['unit_weight_kg'] ?? 0);

      $price_per_sack = (float)($_POST['price_per_sack'] ?? 0);
      $price_per_kg   = (float)($_POST['price_per_kg'] ?? 0);

      if($variety === '' || $grade === '' || !in_array($grade, $ALLOWED_GRADES, true) || $unit_weight_kg <= 0){
          header("Location: products.php?error=" . urlencode("Please complete all required fields."));
          exit;
      }

      $stmt = $conn->prepare("INSERT INTO products
          (variety, grade, unit_weight_kg, price_per_sack, price_per_kg, created_at, archived)
          VALUES (?,?,?,?,?,NOW(),0)");
      if(!$stmt){
          header("Location: products.php?error=" . urlencode("DB error: ".$conn->error));
          exit;
      }
      $stmt->bind_param("ssddd", $variety, $grade, $unit_weight_kg, $price_per_sack, $price_per_kg);
      $stmt->execute();
      $stmt->close();

      logActivity($conn, $user_id, "PRODUCT", "Added product: $variety - $grade ({$unit_weight_kg}kg/sack)");

      header("Location: products.php?success=added");
      exit;
  }

  if(isset($_POST['edit_product'])){
      $product_id     = (int)($_POST['product_id'] ?? 0);
      $variety        = trim($_POST['variety'] ?? '');
      $grade          = trim($_POST['grade'] ?? '');
      $unit_weight_kg = (float)($_POST['unit_weight_kg'] ?? 0);

      $price_per_sack = (float)($_POST['price_per_sack'] ?? 0);
      $price_per_kg   = (float)($_POST['price_per_kg'] ?? 0);

      if($product_id <= 0 || $variety === '' || $grade === '' || !in_array($grade, $ALLOWED_GRADES, true) || $unit_weight_kg <= 0){
          header("Location: products.php?error=" . urlencode("Invalid edit request."));
          exit;
      }

      $stmt = $conn->prepare("UPDATE products
          SET variety=?, grade=?, unit_weight_kg=?, price_per_sack=?, price_per_kg=?
          WHERE product_id=?");
      if(!$stmt){
          header("Location: products.php?error=" . urlencode("DB error: ".$conn->error));
          exit;
      }
      $stmt->bind_param("ssdddi", $variety, $grade, $unit_weight_kg, $price_per_sack, $price_per_kg, $product_id);
      $stmt->execute();
      $stmt->close();

      logActivity($conn, $user_id, "PRODUCT", "Edited product #$product_id: $variety - $grade ({$unit_weight_kg}kg/sack)");

      header("Location: products.php?success=updated");
      exit;
  }

  if(isset($_GET['archive'])){
      $archive_id = (int)($_GET['archive'] ?? 0);

      $stmt = $conn->prepare("UPDATE products SET archived=1 WHERE product_id=?");
      $stmt->bind_param("i", $archive_id);
      $stmt->execute();
      $stmt->close();

      logActivity($conn, $user_id, "PRODUCT", "Archived product #$archive_id");

      header("Location: products.php?success=archived");
      exit;
  }

  $sql = "
  SELECT
    p.*,
    IFNULL(SUM(
      CASE
        WHEN LOWER(it.type)='in' THEN it.qty_kg
        WHEN LOWER(it.type)='out' THEN -it.qty_kg
        WHEN LOWER(it.type)='adjust' THEN it.qty_kg
        ELSE 0
      END
    ), 0) AS stock_kg
  FROM products p
  LEFT JOIN inventory_transactions it ON it.product_id = p.product_id
  WHERE p.archived = 0
  GROUP BY p.product_id
  ORDER BY p.created_at DESC
  ";
  $productsRes = $conn->query($sql);
  if(!$productsRes){
      die("Query Error: " . $conn->error);
  }

  $overItems = [];
  $products = [];
  if($productsRes && $productsRes->num_rows > 0){
      while($r = $productsRes->fetch_assoc()){
          $products[] = $r;
          $st = (float)$r['stock_kg'];
          if($st >= $OVERSTOCK_THRESHOLD){
              $overItems[] = [
                  'product' => ($r['variety'].' - '.$r['grade']),
                  'stock'   => $st
              ];
          }
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Products | DO HIYS</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="../css/layout.css" rel="stylesheet">
  </head>

  <body>

  <?php include '../includes/topnav.php'; ?>

  <div class="container-fluid">
  <div class="row">

  <?php include '../includes/admin_sidebar.php'; ?>

  <main class="col-lg-10 ms-sm-auto px-4 main-content">

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2 class="mb-0">Products</h2>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#thresholdModal">
        <i class="fas fa-sliders-h"></i> Thresholds
      </button>

      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fas fa-plus"></i> Add Product
      </button>
    </div>
  </div>

  <?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger mt-3"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if(isset($_GET['success'])): ?>
    <?php if($_GET['success']==='added'): ?>
      <div class="alert alert-success mt-3">Product added successfully!</div>
    <?php elseif($_GET['success']==='updated'): ?>
      <div class="alert alert-success mt-3">Product updated successfully!</div>
    <?php elseif($_GET['success']==='archived'): ?>
      <div class="alert alert-success mt-3">Product archived successfully!</div>
    <?php elseif($_GET['success']==='thresholds_saved'): ?>
      <div class="alert alert-success mt-3">Stock thresholds updated! (Password confirmed)</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="mt-3 d-flex flex-wrap gap-2">
    <span class="badge bg-warning text-dark">LOW ≤ <?= number_format($LOW_STOCK_THRESHOLD,2) ?> kg</span>
    <span class="badge bg-danger">OVER ≥ <?= number_format($OVERSTOCK_THRESHOLD,2) ?> kg</span>
  </div>

  <div class="table-responsive mt-3">
  <table class="table table-striped table-bordered modern-card align-middle">
  <thead class="table-dark">
  <tr>
  <th>ID</th>
  <th>Variety</th>
  <th>Grade</th>
  <th>Unit Size<br><small>(kg/sack)</small></th>
  <th>Current Stock<br><small>(kg)</small></th>
  <th>Price per Sack</th>
  <th>Price per kg</th>
  <th>Status</th>
  <th>Actions</th>
  </tr>
  </thead>
  <tbody>

  <?php if(count($products) > 0): ?>
  <?php foreach($products as $row): ?>
  <?php
    $stock = (float)$row['stock_kg'];
    $unit_weight = (float)($row['unit_weight_kg'] ?? 0);

$full_sacks = 0;
$remaining_kg = 0;

if($unit_weight > 0){
    $full_sacks = floor($stock / $unit_weight);
    $remaining_kg = $stock - ($full_sacks * $unit_weight);
}

    if($stock <= 0){
      $statusBadge = "<span class='badge bg-secondary ms-2'>OUT</span>";
    } elseif($stock >= $OVERSTOCK_THRESHOLD){
      $statusBadge = "<span class='badge bg-danger ms-2'>OVER</span>";
    } elseif($stock <= $LOW_STOCK_THRESHOLD){
      $statusBadge = "<span class='badge bg-danger ms-2'>LOW</span>";
    } else {
      $statusBadge = "<span class='badge bg-success ms-2'>OK</span>";
    }

    $rowClass = ($stock >= $OVERSTOCK_THRESHOLD) ? "table-danger" : (($stock <= $LOW_STOCK_THRESHOLD && $stock > 0) ? "table-warning" : "");
  ?>
  <tr class="<?= $rowClass ?>">
  <td><?= (int)$row['product_id'] ?></td>
  <td><?= htmlspecialchars($row['variety']) ?></td>
  <td><?= htmlspecialchars($row['grade']) ?></td>
  <td><?= number_format((float)($row['unit_weight_kg'] ?? 0),0) ?> kg</td>
  <td>
  <div>
    <strong><?= number_format($stock,2) ?> kg</strong> <?= $statusBadge ?>
  </div>

  <?php if($stock > 0): ?>
    <small class="text-muted">
  Equivalent to <?= $full_sacks ?> full sack(s)
  <?= $remaining_kg > 0 ? " • ".number_format($remaining_kg,2)." kg loose" : "" ?>
</small>
  <?php endif; ?>
</td>
 <td>₱<?= number_format((float)($row['price_per_sack'] ?? 0),2) ?></td>
<td>₱<?= number_format((float)($row['price_per_kg'] ?? 0),2) ?></td>
  <td><span class="badge bg-primary">Active</span></td>
  <td class="text-nowrap">
      <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editProductModal<?= (int)$row['product_id'] ?>">
        <i class="fas fa-edit"></i>
      </button>
      <a href="products.php?archive=<?= (int)$row['product_id'] ?>" class="btn btn-sm btn-danger"
        onclick="return confirm('Archive this product?')">
        <i class="fas fa-archive"></i>
      </a>
  </td>
  </tr>

  <div class="modal fade" id="editProductModal<?= (int)$row['product_id'] ?>" tabindex="-1">
  <div class="modal-dialog">
  <div class="modal-content">
  <div class="modal-header">
  <h5 class="modal-title">Edit Product</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>

  <form method="POST" action="">
  <div class="modal-body">
  <input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>">

  <div class="mb-2">
    <label>Variety</label>
    <input class="form-control" type="text" name="variety" value="<?= htmlspecialchars($row['variety']) ?>" required>
  </div>

  <div class="mb-2">
    <label>Grade</label>
    <select class="form-select" name="grade" required>
      <?php
        foreach($ALLOWED_GRADES as $g){
          $sel = ($row['grade'] === $g) ? 'selected' : '';
          echo "<option value=\"".htmlspecialchars($g)."\" {$sel}>".htmlspecialchars($g)."</option>";
        }
      ?>
    </select>
  </div>

  <div class="mb-2">
    <label>Unit Size (kg per sack)</label>
    <select class="form-select" name="unit_weight_kg" required>
      <?php
        $sizes = [10,15,20,25,50];
        $cur = (float)($row['unit_weight_kg'] ?? 0);
        foreach($sizes as $s){
          $sel = ((float)$s === (float)$cur) ? 'selected' : '';
          echo "<option value=\"{$s}\" {$sel}>{$s} kg</option>";
        }
      ?>
    </select>
  </div>

  <div class="row g-2">
    <div class="col-md-6">
      <label>Price per Sack (₱)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="price_per_sack"
            value="<?= htmlspecialchars($row['price_per_sack'] ?? 0) ?>" required>
    </div>
    <div class="col-md-6">
      <label>Price per kg (₱)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="price_per_kg"
            value="<?= htmlspecialchars($row['price_per_kg'] ?? 0) ?>" required>
    </div>
  </div>

  <div class="alert alert-info mt-2 mb-0">
    <b>Note:</b> Stock cannot be edited in Product settings. Use <b>Stock In (Receiving)</b>, <b>Adjust Stock</b>, Sales, or Returns.
  </div>

  </div>

  <div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button>
  </div>
  </form>

  </div>
  </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <tr><td colspan="9" class="text-center text-muted">No active products found.</td></tr>
  <?php endif; ?>

  </tbody>
  </table>
  </div>

  <?php if(count($overItems) > 0): ?>
  <div class="modal fade" id="overStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title fw-bold">Overstock Alert</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-danger mb-3">
            There are <?= count($overItems) ?> product(s) at or above <?= number_format($OVERSTOCK_THRESHOLD,2) ?> kg.
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>Product</th>
                  <th class="text-end">Stock (kg)</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($overItems as $oi): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($oi['product']) ?></td>
                    <td class="text-end fw-bold"><?= number_format((float)$oi['stock'],2) ?></td>
                    <td><span class="badge bg-danger">OVERSTOCK</span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-dark" type="button" data-bs-dismiss="modal">Close</button>
        </div>

      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="modal fade" id="thresholdModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">Stock Threshold Settings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF_TOKEN) ?>">

          <div class="modal-body">

            <div class="mb-3">
              <label class="form-label">Low Stock Threshold (kg)</label>
              <input type="number" step="0.01" min="0" name="low_stock_threshold"
                    class="form-control"
                    value="<?= htmlspecialchars($LOW_STOCK_THRESHOLD) ?>" required>
              <div class="form-text">Stock at/below this becomes LOW.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Overstock Threshold (kg)</label>
              <input type="number" step="0.01" min="0.01" name="overstock_threshold"
                    class="form-control"
                    value="<?= htmlspecialchars($OVERSTOCK_THRESHOLD) ?>" required>
              <div class="form-text">Stock at/above this becomes OVERSTOCK.</div>
            </div>

            <hr>

            <div class="mb-3">
              <label class="form-label fw-bold text-danger">Admin Password Confirmation</label>
              <input type="password" name="admin_password" class="form-control"
                    placeholder="Enter admin password to confirm" required>
              <div class="form-text">Required before saving threshold changes.</div>
            </div>

            <div class="alert alert-info mb-0">
              To avoid stock manipulation abuse, password confirmation is required
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="save_thresholds" class="btn btn-dark">Save</button>
          </div>
        </form>

      </div>
    </div>
  </div>
  
  <div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog">
  <div class="modal-content">
  <div class="modal-header">
  <h5 class="modal-title">Add Product</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>

  <form method="POST" action="">
  <div class="modal-body">

  <div class="mb-2">
    <label>Variety</label>
    <input class="form-control" type="text" name="variety" required>
  </div>

  <div class="mb-2">
    <label>Grade</label>
    <select class="form-select" name="grade" required>
      <option value="">Select grade</option>
      <option value="Premium">Premium</option>
      <option value="Special">Special</option>
      <option value="Regular">Regular</option>
      <option value="Broken">Broken</option>
    </select>
  </div>

  <div class="mb-2">
    <label>Unit Size (kg per sack)</label>
    <select class="form-select" name="unit_weight_kg" required>
      <option value="">Select unit size</option>
      <option value="10">10 kg</option>
      <option value="15">15 kg</option>
      <option value="20">20 kg</option>
      <option value="25">25 kg</option>
      <option value="50">50 kg</option>
    </select>
  </div>

  <div class="row g-2">
    <div class="col-md-6">
      <label>Price per Sack (₱)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="price_per_sack" value="0" required>
    </div>
    <div class="col-md-6">
      <label>Price per kg (₱)</label>
      <input class="form-control" type="number" step="0.01" min="0" name="price_per_kg" value="0" required>
    </div>
  </div>

  <div class="alert alert-info mt-2 mb-0">
    Add product details first, then use <b>Stock In (Receiving)</b> when stock arrives.
  </div>

  </div>

  <div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
  </div>
  </form>

  </div>
  </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  document.addEventListener("DOMContentLoaded", function () {
    const overEl = document.getElementById("overStockModal");
    if(overEl){
      new bootstrap.Modal(overEl, { backdrop:'static', keyboard:false }).show();
    }
  });
  </script>

  </main>
  </div>
  </div>
  </body>
  </html>
