<?php
// Sayfa başlığını belirle
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'events.php' => 'Etkinlikler',
    'users.php' => 'Kullanıcılar',
    'tickets.php' => 'Biletler',
    'categories.php' => 'Kategoriler',
    'reports.php' => 'Raporlar',
    'settings.php' => 'Ayarlar'
];

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Admin Panel';

// Bildirim sayısını al
$notification_count = 3; // Gerçek uygulamada veritabanından çekilecek
?>
<nav class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <button class="btn btn-link text-dark mobile-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h4 class="mb-0"><?php echo $page_title; ?></h4>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
            <button class="btn btn-link text-dark position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($notification_count > 0): ?>
                <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="top: 5px; right: 5px;">
                    <?php echo $notification_count; ?>
                </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Bildirimler</h6></li>
                <li><a class="dropdown-item" href="#">5 yeni bilet satışı</a></li>
                <li><a class="dropdown-item" href="#">2 yeni kullanıcı kaydı</a></li>
                <li><a class="dropdown-item" href="#">Sistem güncellemesi mevcut</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="notifications.php">Tümünü Gör</a></li>
            </ul>
        </div>
        <div class="dropdown">
            <button class="btn btn-link text-dark d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=6366f1&color=fff" alt="Avatar" class="rounded-circle" width="32" height="32">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Ayarlar</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Çıkış Yap</a></li>
            </ul>
        </div>
    </div>
</nav>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>