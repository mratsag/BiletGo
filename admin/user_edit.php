<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Geçersiz kullanıcı ID.");
}

$user_id = (int)$_GET['id'];

// Kendi bilgilerini düzenlemeyi engelle
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Kendi bilgilerinizi bu sayfadan düzenleyemezsiniz!";
    header("Location: users.php");
    exit();
}

// Kullanıcı bilgilerini çek
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Kullanıcı bulunamadı.");
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $role, $user_id]);

        header("Location: users.php?success=updated");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Kullanıcı güncellenemedi.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>

<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content">
        <!-- Top Navbar -->
        <?php include '../includes/admin_navbar.php'; ?>
    <div class="container mt-5">
        <h3>Kullanıcı Düzenle - <?php echo htmlspecialchars($user['username']); ?></h3>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">Ad Soyad</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telefon</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select name="role" class="form-select">
                    <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>Kullanıcı</option>
                    <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Kaydet</button>
            <a href="users.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>
</body>
</html>