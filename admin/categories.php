<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = "";
$success = "";

// Kategori ekleme
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    
    try {
        // Aynı isimde kategori var mı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Bu kategori zaten mevcut!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "Kategori başarıyla eklendi!";
        }
    } catch (PDOException $e) {
        $error = "Kategori eklenirken hata oluştu.";
    }
}

// Kategori güncelleme
if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);
    
    try {
        // Aynı isimde başka kategori var mı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Bu isimde başka bir kategori zaten mevcut!";
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $success = "Kategori başarıyla güncellendi!";
        }
    } catch (PDOException $e) {
        $error = "Kategori güncellenirken hata oluştu.";
    }
}

// Kategori silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Bu kategoriye ait etkinlik var mı kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE category_id = ?");
        $stmt->execute([$_GET['delete']]);
        $eventCount = $stmt->fetchColumn();
        
        if ($eventCount > 0) {
            $error = "Bu kategoriye ait etkinlikler bulunduğu için silinemez!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $success = "Kategori başarıyla silindi!";
        }
    } catch (PDOException $e) {
        $error = "Kategori silinirken hata oluştu.";
    }
}

// Kategorileri çek
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(DISTINCT e.id) as event_count,
               COUNT(DISTINCT t.id) as ticket_count,
               SUM(CASE WHEN t.id IS NOT NULL THEN e.price ELSE 0 END) as total_revenue
        FROM categories c
        LEFT JOIN events e ON c.id = e.category_id
        LEFT JOIN tickets t ON e.id = t.event_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İstatistikler
    $totalCategories = count($categories);
    $totalEvents = array_sum(array_column($categories, 'event_count'));
    $totalTickets = array_sum(array_column($categories, 'ticket_count'));
    $totalRevenue = array_sum(array_column($categories, 'total_revenue'));
    
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriler - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/admin.css">
    <style>
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .category-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .category-stat {
            text-align: center;
        }

        .category-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .category-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
        }
    </style>
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
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Kategori Yönetimi</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle me-2"></i>Yeni Kategori
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-tags"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalCategories; ?></div>
                        <div class="stat-label">Toplam Kategori</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalEvents; ?></div>
                        <div class="stat-label">Toplam Etkinlik</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-ticket"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalTickets; ?></div>
                        <div class="stat-label">Satılan Bilet</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-value">₺<?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
                        <div class="stat-label">Toplam Gelir</div>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 mb-4">
                        <div class="category-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="category-icon">
                                        <?php
                                        // Kategori ismine göre ikon belirle
                                        $icons = [
                                            'Konser' => 'bi-music-note-beamed',
                                            'Tiyatro' => 'bi-mask',
                                            'Stand-up' => 'bi-mic',
                                            'Festival' => 'bi-star',
                                            'Klasik Müzik' => 'bi-vinyl',
                                            'Rock' => 'bi-lightning',
                                            'Pop' => 'bi-heart',
                                            'Jazz' => 'bi-music-note'
                                        ];
                                        $icon = $icons[$category['name']] ?? 'bi-tag';
                                        ?>
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" 
                                               onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                <i class="bi bi-pencil me-2"></i>Düzenle
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="events.php?category_id=<?php echo $category['id']; ?>">
                                                <i class="bi bi-calendar-event me-2"></i>Etkinlikleri Gör
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['event_count']; ?>)">
                                                <i class="bi bi-trash me-2"></i>Sil
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="category-stats">
                                <div class="category-stat">
                                    <div class="category-stat-value"><?php echo $category['event_count']; ?></div>
                                    <div class="category-stat-label">Etkinlik</div>
                                </div>
                                <div class="category-stat">
                                    <div class="category-stat-value"><?php echo $category['ticket_count']; ?></div>
                                    <div class="category-stat-label">Bilet</div>
                                </div>
                                <div class="category-stat">
                                    <div class="category-stat-value">₺<?php echo number_format($category['total_revenue'], 0, ',', '.'); ?></div>
                                    <div class="category-stat-label">Gelir</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Henüz kategori eklenmemiş.</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle me-2"></i>İlk Kategoriyi Ekle
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Kategori Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kategori Adı</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Örn: Konser, Tiyatro, Spor...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    <div class="modal-header">
                        <h5 class="modal-title">Kategori Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kategori Adı</label>
                            <input type="text" name="name" id="editCategoryName" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="update_category" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(id, name) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryName').value = name;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        function deleteCategory(id, name, eventCount) {
            if (eventCount > 0) {
                alert(`"${name}" kategorisine ait ${eventCount} etkinlik bulunduğu için silinemez!`);
                return;
            }
            
            if (confirm(`"${name}" kategorisini silmek istediğinizden emin misiniz?`)) {
                window.location.href = 'categories.php?delete=' + id;
            }
        }
    </script>
</body>
</html>