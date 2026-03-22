<nav id="sidebarMenu" class="col-lg-2 d-lg-block sidebar collapse">
  <div class="pt-4">
    <ul class="nav flex-column gap-1">

      <li class="nav-item">
        <a class="nav-link active" href="dashboard.php">
          <i class="fas fa-gauge-high me-2"></i>Owner Dashboard
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="inventory_monitoring.php">
          <i class="fas fa-boxes-stacked me-2"></i>Stock Monitoring
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="sales_report.php">
          <i class="fas fa-receipt me-2"></i>Sales Reports
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#financeMenu">
          <i class="fas fa-coins me-2"></i>Finance
          <i class="fas fa-chevron-down float-end"></i>
        </a>
        <div class="collapse submenu" id="financeMenu">
          <a href="../owner/purchase_orders.php">Purchase Orders</a>
          <a href="../owner/supplier_payables.php">Supplier Payables</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="returns_report.php">
          <i class="fas fa-rotate-left me-2"></i>Returns Report
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="analytics.php">
          <i class="fas fa-chart-line me-2"></i>Analytics & Forecasting
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="system_logs.php">
          <i class="fas fa-file-shield me-2"></i>System Logs
        </a>
      </li>

    </ul>

    <div class="px-3 mt-4">
      <div class="alert alert-light small mb-0">
        <i class="fa-solid fa-circle-info me-1"></i>
        Owner access is <b>monitoring + finance approval</b>.
      </div>
    </div>
  </div>
</nav>