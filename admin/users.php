<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Kullanıcı silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Admin kullanıcısının kendini silmesini engelle
        if ($_GET['delete'] == $_SESSION['user_id']) {
            $_SESSION['error'] = "Kendinizi silemezsiniz!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            header('Location: users.php?success=deleted');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Kullanıcı silinemedi.";
    }
}

// Rol değiştirme işlemi
if (isset($_POST['change_role'])) {
    try {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        // Admin kullanıcısının kendi rolünü değiştirmesini engelle
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Kendi rolünüzü değiştiremezsiniz!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            header('Location: users.php?success=role_changed');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Rol değiştirilemedi.";
    }
}

// Kullanıcıları çek
try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(DISTINCT t.id) as ticket_count,
               SUM(e.price) as total_spent
        FROM users u
        LEFT JOIN tickets t ON u.id = t.user_id
        LEFT JOIN events e ON t.event_id = e.id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcılar - Admin Panel</title>
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
                            echo 'Kullanıcı başarıyla silindi!';
                            break;
                        case 'role_changed':
                            echo 'Kullanıcı rolü başarıyla değiştirildi!';
                            break;
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Kullanıcı Yönetimi</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-circle me-2"></i>Yeni Kullanıcı
                    </button>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Kullanıcı ara...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter">
                        <option value="">Tüm Roller</option>
                        <option value="user">Kullanıcı</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-value"><?php echo count($users); ?></div>
                        <div class="stat-label">Toplam Kullanıcı</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($users, function($u) { return $u['role'] == 'user'; })); ?>
                        </div>
                        <div class="stat-label">Normal Kullanıcı</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count(array_filter($users, function($u) { return $u['role'] == 'admin'; })); ?>
                        </div>
                        <div class="stat-label">Admin</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="bi bi-calendar-plus"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $today = date('Y-m-d');
                            echo count(array_filter($users, function($u) use ($today) { 
                                return date('Y-m-d', strtotime($u['created_at'])) == $today; 
                            })); 
                            ?>
                        </div>
                        <div class="stat-label">Bugünkü Kayıtlar</div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="custom-table">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Kullanıcı</th>
                                <th>E-posta</th>
                                <th>Telefon</th>
                                <th>Rol</th>
                                <th>Bilet Sayısı</th>
                                <th>Harcama</th>
                                <th>Kayıt Tarihi</th>
                                <th width="150">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-role="<?php echo $user['role']; ?>">
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=6366f1&color=fff" 
                                                 alt="Avatar" class="user-avatar me-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                                                <br>
                                                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Kullanıcı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $user['ticket_count']; ?></span>
                                    </td>
                                    <td>₺<?php echo number_format($user['total_spent'] ?? 0, 0, ',', '.'); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                İşlemler
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="user_detail.php?id=<?php echo $user['id']; ?>">
                                                        <i class="bi bi-eye me-2"></i>Detay
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="user_edit.php?id=<?php echo $user['id']; ?>">
                                                        <i class="bi bi-pencil me-2"></i>Düzenle
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="changeRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                                        <i class="bi bi-shield me-2"></i>Rol Değiştir
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                        <i class="bi bi-trash me-2"></i>Sil
                                                    </a>
                                                </li>
                                            </ul>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="user_add.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="user">Kullanıcı</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kullanıcı Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Rol Değiştir</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="changeRoleUserId">
                        <div class="mb-3">
                            <label class="form-label">Yeni Rol</label>
                            <select name="new_role" class="form-select" id="newRoleSelect">
                                <option value="user">Kullanıcı</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="change_role" class="btn btn-primary">Değiştir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Arama ve filtreleme
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('roleFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const role = row.getAttribute('data-role');
                
                let showRow = true;
                
                if (searchInput && !username.includes(searchInput) && !email.includes(searchInput)) {
                    showRow = false;
                }
                
                if (roleFilter && role !== roleFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Kullanıcı silme
        function deleteUser(id, username) {
            if (confirm(`"${username}" kullanıcısını silmek istediğinizden emin misiniz?`)) {
                window.location.href = 'users.php?delete=' + id;
            }
        }

        // Rol değiştirme
        function changeRole(userId, currentRole) {
            document.getElementById('changeRoleUserId').value = userId;
            document.getElementById('newRoleSelect').value = currentRole;
            new bootstrap.Modal(document.getElementById('changeRoleModal')).show();
        }

        
    </script>
</body>
</html>