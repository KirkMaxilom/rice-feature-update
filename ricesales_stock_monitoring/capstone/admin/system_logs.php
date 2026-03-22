<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'] ?? 'User';
include '../config/db.php';

// Simple filters
$tab = $_GET['tab'] ?? 'activity';
$q = trim($_GET['q'] ?? '');

// Fetch Activity Logs
$activityLogs = [];
if($tab === 'activity'){
    if($q !== ''){
        $stmt = $conn->prepare("
            SELECT al.*, u.first_name, u.last_name, u.username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            WHERE (al.activity_type LIKE CONCAT('%',?,'%')
               OR al.description LIKE CONCAT('%',?,'%')
               OR u.username LIKE CONCAT('%',?,'%')
               OR u.first_name LIKE CONCAT('%',?,'%')
               OR u.last_name LIKE CONCAT('%',?,'%'))
            ORDER BY al.created_at DESC
            LIMIT 200
        ");
        $stmt->bind_param("sssss", $q, $q, $q, $q, $q);
        $stmt->execute();
        $activityLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $res = $conn->query("
            SELECT al.*, u.first_name, u.last_name, u.username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 200
        ");
        if($res) $activityLogs = $res->fetch_all(MYSQLI_ASSOC);
    }
}

// Fetch Login Logs
$loginLogs = [];
if($tab === 'login'){
    if($q !== ''){
        $stmt = $conn->prepare("
            SELECT ll.*, u.first_name, u.last_name, u.username
            FROM login_logs ll
            LEFT JOIN users u ON ll.user_id = u.user_id
            WHERE (ll.device_info LIKE CONCAT('%',?,'%')
               OR ll.ip_address LIKE CONCAT('%',?,'%')
               OR u.username LIKE CONCAT('%',?,'%')
               OR u.first_name LIKE CONCAT('%',?,'%')
               OR u.last_name LIKE CONCAT('%',?,'%'))
            ORDER BY ll.login_time DESC
            LIMIT 200
        ");
        $stmt->bind_param("sssss", $q, $q, $q, $q, $q);
        $stmt->execute();
        $loginLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $res = $conn->query("
            SELECT ll.*, u.first_name, u.last_name, u.username
            FROM login_logs ll
            LEFT JOIN users u ON ll.user_id = u.user_id
            ORDER BY ll.login_time DESC
            LIMIT 200
        ");
        if($res) $loginLogs = $res->fetch_all(MYSQLI_ASSOC);
    }
}

// Fetch Push Notif Logs (optional)
$notifLogs = [];
if($tab === 'notif'){
    if($q !== ''){
        $stmt = $conn->prepare("
            SELECT pn.*, c.first_name, c.last_name
            FROM push_notif_logs pn
            LEFT JOIN customers c ON pn.customer_id = c.customer_id
            WHERE (pn.message LIKE CONCAT('%',?,'%')
               OR pn.status LIKE CONCAT('%',?,'%')
               OR c.first_name LIKE CONCAT('%',?,'%')
               OR c.last_name LIKE CONCAT('%',?,'%'))
            ORDER BY pn.sent_at DESC
            LIMIT 200
        ");
        $stmt->bind_param("ssss", $q, $q, $q, $q);
        $stmt->execute();
        $notifLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $res = $conn->query("
            SELECT pn.*, c.first_name, c.last_name
            FROM push_notif_logs pn
            LEFT JOIN customers c ON pn.customer_id = c.customer_id
            ORDER BY pn.sent_at DESC
            LIMIT 200
        ");
        if($res) $notifLogs = $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>System Logs | DO HIYS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<link href="../css/layout.css" rel="stylesheet">
</head>

<body>

<?php include '../includes/topnav.php'; ?>

<div class="container-fluid">
<div class="row">

<?php include '../includes/admin_sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="col-lg-10 ms-sm-auto px-4 main-content">
  <h3 class="fw-bold mb-1">System Logs</h3>
  <p class="text-muted mb-4">Track user actions, logins, and notifications for accountability</p>

  <!-- Tabs + Search -->
  <div class="card modern-card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <div class="btn-group">
        <a class="btn btn-outline-primary <?= $tab==='activity'?'active':'' ?>" href="?tab=activity">Activity Logs</a>
        <a class="btn btn-outline-primary <?= $tab==='login'?'active':'' ?>" href="?tab=login">Login Logs</a>
        <a class="btn btn-outline-primary <?= $tab==='notif'?'active':'' ?>" href="?tab=notif">Notification Logs</a>
      </div>

      <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <input type="text" name="q" class="form-control" placeholder="Search logs..." value="<?= htmlspecialchars($q) ?>">
        <button class="btn btn-dark"><i class="fas fa-search"></i></button>
      </form>
    </div>
  </div>

  <!-- Logs Table -->
  <div class="card modern-card">
    <div class="card-body table-responsive">
      <?php if($tab==='activity'): ?>
        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>Date</th>
              <th>User</th>
              <th>Type</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($activityLogs)): ?>
            <tr><td colspan="4" class="text-center text-muted">No activity logs found.</td></tr>
          <?php else: ?>
            <?php foreach($activityLogs as $l): ?>
              <tr>
                <td><?= date("M d, Y h:i A", strtotime($l['created_at'])) ?></td>
                <td><?= htmlspecialchars(($l['first_name'] ?? '')." ".($l['last_name'] ?? '')." (".$l['username'].")") ?></td>
                <td><span class="badge bg-info"><?= htmlspecialchars($l['activity_type']) ?></span></td>
                <td><?= htmlspecialchars($l['description']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

      <?php elseif($tab==='login'): ?>
        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>Login Time</th>
              <th>User</th>
              <th>Device</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($loginLogs)): ?>
            <tr><td colspan="4" class="text-center text-muted">No login logs found.</td></tr>
          <?php else: ?>
            <?php foreach($loginLogs as $l): ?>
              <tr>
                <td><?= date("M d, Y h:i A", strtotime($l['login_time'])) ?></td>
                <td><?= htmlspecialchars(($l['first_name'] ?? '')." ".($l['last_name'] ?? '')." (".$l['username'].")") ?></td>
                <td><?= htmlspecialchars($l['device_info']) ?></td>
                <td><?= htmlspecialchars($l['ip_address']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

      <?php else: ?>
        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>Sent At</th>
              <th>Customer</th>
              <th>Message</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($notifLogs)): ?>
            <tr><td colspan="4" class="text-center text-muted">No notification logs found.</td></tr>
          <?php else: ?>
            <?php foreach($notifLogs as $n): ?>
              <tr>
                <td><?= date("M d, Y h:i A", strtotime($n['sent_at'])) ?></td>
                <td><?= htmlspecialchars(($n['first_name'] ?? '')." ".($n['last_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars($n['message']) ?></td>
                <td>
                  <span class="badge <?= ($n['status'] ?? '')==='SENT' ? 'bg-success' : 'bg-secondary' ?>">
                    <?= htmlspecialchars($n['status'] ?? 'N/A') ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</main>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
