<?php
// includes/topnav.php
// expects: $username, $profileLink, $logoutLink
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <button class="btn btn-outline-dark d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
    <span class="navbar-brand fw-bold ms-2">DE ORO HIYS GENERAL MERCHANDISE</span>

    <div class="ms-auto dropdown">
      <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
        <?= htmlspecialchars($username ?? 'User') ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if(!empty($profileLink)): ?>
          <li><a class="dropdown-item" href="<?= htmlspecialchars($profileLink) ?>">Profile</a></li>
        <?php endif; ?>
        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($logoutLink ?? '../logout.php') ?>">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
