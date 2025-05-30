<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-ticket-perforated"></i> BiletGo</h3>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="events.php" class="sidebar-item <?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i> Etkinlikler
        </a>
        <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Kullanıcılar
        </a>
        <a href="tickets.php" class="sidebar-item <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
            <i class="bi bi-ticket"></i> Biletler
        </a>
        <a href="categories.php" class="sidebar-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Kategoriler
        </a>
        <hr class="text-secondary my-3">
        <a href="logout.php" class="sidebar-item text-danger">
            <i class="bi bi-box-arrow-left"></i> Çıkış Yap
        </a>
    </div>
</nav>