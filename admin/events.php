<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Etkinlik silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: events.php?success=deleted');
        exit();
    } catch (PDOException $e) {
        $error = "Etkinlik silinemedi.";
    }
}

// Etkinlikleri çek
try {
    $stmt = $pdo->query("
        SELECT e.id, e.title, e.description, e.location, e.date, e.time, e.price, e.category_id, e.created_at,
               CASE WHEN e.photo IS NOT NULL THEN 1 ELSE 0 END as has_photo,
               c.name as category_name, 
               (SELECT COUNT(*) FROM tickets WHERE event_id = e.id) as ticket_count
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.id
        ORDER BY e.date DESC, e.time DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategorileri çek (form için)
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlikler - Admin Panel</title>
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
                        case 'deleted':
                            echo 'Etkinlik başarıyla silindi!';
                            break;
                        case 'created':
                            echo 'Etkinlik başarıyla oluşturuldu!';
                            break;
                        case 'updated':
                            echo 'Etkinlik başarıyla güncellendi!';
                            break;
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Etkinlik Yönetimi</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Etkinlik Ekle
                </button>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Etkinlik ara...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tüm Durumlar</option>
                        <option value="upcoming">Yaklaşan</option>
                        <option value="past">Geçmiş</option>
                    </select>
                </div>
            </div>

            <!-- Events Table -->
            <div class="custom-table">
                <div class="table-responsive">
                    <table class="table table-hover" id="eventsTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Etkinlik Adı</th>
                                <th>Kategori</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Mekan</th>
                                <th>Fiyat</th>
                                <th>Satılan</th>
                                <th>Durum</th>
                                <th width="150">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <?php
                                $eventDate = new DateTime($event['date']);
                                $now = new DateTime();
                                $isPast = $eventDate < $now;
                                ?>
                                <tr data-category="<?php echo $event['category_id']; ?>" 
                                    data-status="<?php echo $isPast ? 'past' : 'upcoming'; ?>">
                                    <td><?php echo $event['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        <?php if ($event['has_photo']): ?>
                                            <i class="bi bi-image text-primary ms-2" title="Fotoğraflı"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($event['category_name'] ?? 'Kategorisiz'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $eventDate->format('d.m.Y'); ?></td>
                                    <td><?php echo substr($event['time'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td>₺<?php echo number_format($event['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $event['ticket_count']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($isPast): ?>
                                            <span class="badge bg-secondary">Geçmiş</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Yaklaşan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editEvent(<?php echo $event['id']; ?>)" title="Düzenle">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewEvent(<?php echo $event['id']; ?>)" title="Görüntüle">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($event['has_photo']): ?>
                                            <button class="btn btn-outline-secondary" onclick="viewEventImage(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" title="Görseli Gör">
                                                <i class="bi bi-image"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger" onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" title="Sil">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="event_add.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Etkinlik Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Etkinlik Adı</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mekan</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fiyat (₺)</label>
                                <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" name="time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Etkinlik Görseli</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Maksimum 5MB, JPG/PNG formatında</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Etkinlik Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewTitle">Etkinlik Görseli</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="eventImagePreview" src="" alt="Etkinlik Görseli" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Arama fonksiyonu
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('categoryFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#eventsTable tbody tr');

            rows.forEach(row => {
                const title = row.cells[1].textContent.toLowerCase();
                const category = row.getAttribute('data-category');
                const status = row.getAttribute('data-status');
                
                let showRow = true;
                
                if (searchInput && !title.includes(searchInput)) {
                    showRow = false;
                }
                
                if (categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Etkinlik silme
        function deleteEvent(id, title) {
            if (confirm(`"${title}" etkinliğini silmek istediğinizden emin misiniz?`)) {
                window.location.href = 'events.php?delete=' + id;
            }
        }

        // Etkinlik düzenleme
        function editEvent(id) {
            window.location.href = 'event_edit.php?id=' + id;
        }

        // Etkinlik görselini görüntüle
        function viewEventImage(id, title) {
            document.getElementById('imagePreviewTitle').textContent = title + ' - Görsel';
            document.getElementById('eventImagePreview').src = 'event_image.php?id=' + id;
            new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
        }

        // Etkinlik görüntüleme
        function viewEvent(id) {
            window.location.href = 'event_view.php?id=' + id;
        }
    </script>
</body>
</html>