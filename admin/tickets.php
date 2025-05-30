<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Bilet iptali işlemi
if (isset($_POST['cancel_ticket']) && isset($_POST['ticket_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$_POST['ticket_id']]);
        header('Location: tickets.php?success=cancelled');
        exit();
    } catch (PDOException $e) {
        $error = "Bilet iptal edilemedi.";
    }
}

// Filtreleme parametreleri
$whereClause = "1=1";
$params = [];

// Tarih filtresi
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $whereClause .= " AND DATE(t.purchase_date) >= ?";
    $params[] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $whereClause .= " AND DATE(t.purchase_date) <= ?";
    $params[] = $_GET['date_to'];
}

// Etkinlik filtresi
if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $whereClause .= " AND t.event_id = ?";
    $params[] = $_GET['event_id'];
}

// Biletleri çek
try {
    $query = "
        SELECT t.*, 
               u.username, u.email as user_email, u.full_name,
               e.title as event_title, e.date as event_date, e.time as event_time,
               e.location, e.price,
               c.name as category_name
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        JOIN events e ON t.event_id = e.id
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE $whereClause
        ORDER BY t.purchase_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İstatistikler
    $stats_query = "
        SELECT 
            COUNT(*) as total_tickets,
            SUM(e.price) as total_revenue,
            COUNT(DISTINCT t.user_id) as unique_buyers,
            COUNT(DISTINCT t.event_id) as unique_events
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Etkinlik listesi (filter için)
    $stmt = $pdo->query("SELECT id, title FROM events ORDER BY date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Günlük satış grafiği için veri
    $chart_query = "
        SELECT DATE(purchase_date) as date, COUNT(*) as count, SUM(e.price) as revenue
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(purchase_date)
        ORDER BY date
    ";
    $stmt = $pdo->query($chart_query);
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletler - Admin Panel</title>
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
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch ($_GET['success']) {
                        case 'cancelled':
                            echo 'Bilet başarıyla iptal edildi!';
                            break;
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Bilet Yönetimi</h3>
                
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-ticket"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_tickets']); ?></div>
                        <div class="stat-label">Toplam Bilet</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-value">₺<?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Toplam Gelir</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['unique_buyers']); ?></div>
                        <div class="stat-label">Benzersiz Alıcı</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['unique_events']); ?></div>
                        <div class="stat-label">Farklı Etkinlik</div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container mb-4">
                <h5 class="mb-3">Son 30 Günlük Satış Grafiği</h5>
                <canvas id="ticketChart" height="100"></canvas>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo $_GET['date_from'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo $_GET['date_to'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Etkinlik</label>
                            <select name="event_id" class="form-select">
                                <option value="">Tüm Etkinlikler</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>" 
                                            <?php echo (isset($_GET['event_id']) && $_GET['event_id'] == $event['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filtrele
                            </button>
                            <a href="tickets.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Temizle
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="custom-table">
                <div class="table-responsive">
                    <table class="table table-hover" id="ticketsTable">
                        <thead>
                            <tr>
                                <th width="80">Bilet No</th>
                                <th>Alıcı</th>
                                <th>Etkinlik</th>
                                <th>Kategori</th>
                                <th>Etkinlik Tarihi</th>
                                <th>Satın Alma</th>
                                <th>Fiyat</th>
                                <th>Durum</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <?php
                                $eventDateTime = new DateTime($ticket['event_date'] . ' ' . $ticket['event_time']);
                                $now = new DateTime();
                                $isPast = $eventDateTime < $now;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ticket['full_name'] ?? $ticket['username']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ticket['user_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ticket['event_title']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ticket['location']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($ticket['category_name'] ?? 'Kategorisiz'); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $eventDateTime->format('d.m.Y H:i'); ?>
                                        <?php if ($isPast): ?>
                                            <br><span class="badge bg-secondary">Geçmiş</span>
                                        <?php else: ?>
                                            <br><span class="badge bg-success">Yaklaşan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($ticket['purchase_date'])); ?></td>
                                    <td>₺<?php echo number_format($ticket['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($isPast): ?>
                                            <span class="badge bg-secondary">Kullanıldı</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Detail Modal -->
    <div class="modal fade" id="ticketDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bilet Detayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="ticketDetailContent">
                    <!-- AJAX ile doldurulacak -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" onclick="printTicketDetail()">
                        <i class="bi bi-printer me-2"></i>Yazdır
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Ticket Form -->
    <form method="POST" id="cancelTicketForm" style="display: none;">
        <input type="hidden" name="cancel_ticket" value="1">
        <input type="hidden" name="ticket_id" id="cancelTicketId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart
        const chartData = <?php echo json_encode($chart_data); ?>;
        const ctx = document.getElementById('ticketChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'Bilet Satışı',
                    data: chartData.map(item => item.count),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Gelir (₺)',
                    data: chartData.map(item => item.revenue),
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Bilet Sayısı'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Gelir (₺)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return '₺' + value.toLocaleString('tr-TR');
                            }
                        }
                    },
                }
            }
        });

        // Functions
        function viewTicket(id) {
            // AJAX ile bilet detayını getir
            fetch('ticket_detail.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('ticketDetailContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('ticketDetailModal')).show();
                });
        }

        function printTicket(id) {
            window.open('ticket_print.php?id=' + id, '_blank', 'width=800,height=600');
        }

        function cancelTicket(id, eventTitle) {
            if (confirm(`"${eventTitle}" etkinliği için alınan bileti iptal etmek istediğinizden emin misiniz?`)) {
                document.getElementById('cancelTicketId').value = id;
                document.getElementById('cancelTicketForm').submit();
            }
        }

        

        function printTicketDetail() {
            const content = document.getElementById('ticketDetailContent').innerHTML;
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write('<html><head><title>Bilet Detayı</title>');
            printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>