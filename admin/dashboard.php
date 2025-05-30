<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// İstatistikleri çek
try {
    // Toplam etkinlik sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Toplam kullanıcı sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Toplam bilet satışı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $totalTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Toplam gelir
    $stmt = $pdo->query("
        SELECT SUM(e.price) as total 
        FROM tickets t 
        JOIN events e ON t.event_id = e.id
    ");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Son 5 bilet satışı
    $stmt = $pdo->query("
        SELECT t.id, u.username, u.full_name, e.title as event_title, 
               t.purchase_date, e.price, e.date as event_date
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        JOIN events e ON t.event_id = e.id
        ORDER BY t.purchase_date DESC
        LIMIT 5
    ");
    $recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aylık satış verileri (son 6 ay)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(t.purchase_date, '%Y-%m') as month,
            COUNT(*) as ticket_count,
            SUM(e.price) as revenue
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE t.purchase_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(t.purchase_date, '%Y-%m')
        ORDER BY month
    ");
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kategori dağılımı
    $stmt = $pdo->query("
        SELECT c.name, COUNT(t.id) as ticket_count
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        LEFT JOIN categories c ON e.category_id = c.id
        GROUP BY c.id, c.name
    ");
    $categoryDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BiletGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <?php include '../includes/admin_navbar.php'; ?>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalEvents; ?></div>
                        <div class="stat-label">Toplam Etkinlik</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-label">Kayıtlı Kullanıcı</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-ticket"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalTickets; ?></div>
                        <div class="stat-label">Satılan Bilet</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-value">₺<?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
                        <div class="stat-label">Toplam Gelir</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4">Aylık Satış Grafiği</h5>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4">Kategori Dağılımı</h5>
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Sales Table -->
            <div class="custom-table">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Son Bilet Satışları</h5>
                    <a href="tickets.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı</th>
                                <th>Etkinlik</th>
                                <th>Satış Tarihi</th>
                                <th>Fiyat</th>
                                <th>Etkinlik Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo str_pad($ticket['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($ticket['full_name'] ?? $ticket['username']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['event_title']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($ticket['purchase_date'])); ?></td>
                                <td>₺<?php echo number_format($ticket['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $eventDate = new DateTime($ticket['event_date']);
                                    $now = new DateTime();
                                    if ($eventDate < $now) {
                                        echo '<span class="badge bg-secondary">Geçmiş</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Yaklaşan</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // PHP'den gelen verileri JavaScript'e aktar
        const monthlySalesData = <?php echo json_encode($monthlySales); ?>;
        const categoryData = <?php echo json_encode($categoryDistribution); ?>;

        // Aylık satış grafiği verilerini hazırla
        const months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        const salesLabels = monthlySalesData.map(item => {
            const [year, month] = item.month.split('-');
            return months[parseInt(month) - 1] + ' ' + year;
        });
        const salesValues = monthlySalesData.map(item => item.revenue);

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Gelir (₺)',
                    data: salesValues,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₺' + value.toLocaleString('tr-TR');
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryLabels = categoryData.map(item => item.name || 'Kategorisiz');
        const categoryValues = categoryData.map(item => item.ticket_count);

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#6366f1',
                        '#22c55e',
                        '#fb923c',
                        '#ef4444',
                        '#94a3b8',
                        '#a855f7',
                        '#3b82f6',
                        '#06b6d4'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>