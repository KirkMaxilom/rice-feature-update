<?php
// expects: $activePage (string) e.g. 'dashboard', 'products', 'sales'
?>
<nav id="sidebarMenu" class="col-lg-2 d-lg-block sidebar collapse">
  <div class="pt-4">
    <ul class="nav flex-column gap-1">

      <li class="nav-item">
        <a class="nav-link <?= ($activePage==='dashboard'?'active':'') ?>" href="dashboard.php">
          <i class="fas fa-home me-2"></i>Dashboard
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= in_array($activePage,['products','stock_in','stock_adjust','inv_logs'])?'active':'' ?>"
           data-bs-toggle="collapse" href="#inventoryMenu">
          <i class="fas fa-warehouse me-2"></i>Stock Monitoring
          <i class="fas fa-chevron-down float-end"></i>
        </a>

        <div class="collapse submenu <?= in_array($activePage,['products','stock_in','stock_adjust','inv_logs'])?'show':'' ?>" id="inventoryMenu">
          <a href="products.php">Products</a>
          <a href="../inventory/add_stock.php">Stock In (Receiving)</a>
          <a href="../inventory/adjust_stock.php">Stock Adjustment</a>
          <a href="../inventory/inventory.php">Stock Logs</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= ($activePage==='users'?'active':'') ?>" href="users.php">
          <i class="fas fa-users me-2"></i>User Management
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= ($activePage==='sales'?'active':'') ?>" href="sales.php">
          <i class="fas fa-cash-register me-2"></i>Sales Overview
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= ($activePage==='analytics'?'active':'') ?>" href="analytics.php">
          <i class="fas fa-chart-line me-2"></i>Analytics & Forecasting
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= ($activePage==='logs'?'active':'') ?>" href="system_logs.php">
          <i class="fas fa-archive me-2"></i>System Logs
        </a>
      </li>

    </ul>
  </div>
</nav>
