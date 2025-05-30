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

// Event ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit();
}

$event_id = $_GET['id'];

// Form gönderildiyse güncelle
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];

        // Temel güncelleme sorgusu
        $sql = "UPDATE events SET title = ?, description = ?, location = ?, date = ?, time = ?, price = ?, category_id = ?";
        $params = [$title, $description, $location, $date, $time, $price, $category_id];

        // Yeni görsel yüklendiyse
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                    $photo = file_get_contents($_FILES['photo']['tmp_name']);
                    $sql .= ", photo = ?";
                    $params[] = $photo;
                } else {
                    throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
                }
            } else {
                throw new Exception('Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.');
            }
        }
        
        // Görsel silme işlemi
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
            $sql .= ", photo = NULL";
        }

        $sql .= " WHERE id = ?";
        $params[] = $event_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header('Location: events.php?success=updated');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Etkinlik bilgilerini çek
try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               CASE WHEN e.photo IS NOT NULL THEN 1 ELSE 0 END as has_photo
        FROM events e 
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events.php');
        exit();
    }
    
    // Kategorileri çek
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
    <title>Etkinlik Düzenle - Admin Panel</title>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Etkinlik Düzenle</h3>
                <a href="events.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri Dön
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Etkinlik Adı</label>
                                <input type="text" name="title" class="form-control" required 
                                       value="<?php echo htmlspecialchars($event['title']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $event['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mekan</label>
                                <input type="text" name="location" class="form-control" required 
                                       value="<?php echo htmlspecialchars($event['location']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fiyat (₺)</label>
                                <input type="number" name="price" class="form-control" min="0" step="0.01" required 
                                       value="<?php echo $event['price']; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="date" class="form-control" required 
                                       value="<?php echo $event['date']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" name="time" class="form-control" required 
                                       value="<?php echo substr($event['time'], 0, 5); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Etkinlik Görseli</label>
                            
                            <?php if ($event['has_photo']): ?>
                                <div class="mb-3">
                                    <img src="event_image.php?id=<?php echo $event['id']; ?>" 
                                         alt="Mevcut Görsel" class="img-thumbnail" style="max-width: 200px;">
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="remove_photo" value="1" class="form-check-input" id="removePhoto">
                                        <label class="form-check-label" for="removePhoto">
                                            Görseli Kaldır
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Maksimum 5MB, JPG/PNG formatında. Yeni görsel yüklerseniz mevcut görsel değiştirilecektir.</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Güncelle
                            </button>
                            <a href="events.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>