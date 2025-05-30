<?php
session_start();
require_once '../config.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Form verilerini al
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $photo = null;

        // Görsel yükleme işlemi (BLOB olarak oku)
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                    $photo = file_get_contents($_FILES['photo']['tmp_name']);
                } else {
                    throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
                }
            } else {
                throw new Exception('Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.');
            }
        }

        // Veritabanına ekle (photo_url yerine photo alanına kaydediyoruz)
        $sql = "INSERT INTO events (title, description, location, date, time, price, category_id, photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $location, $date, $time, $price, $category_id, $photo]);

        header('Location: events.php?success=created');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: events.php');
        exit();
    }
} else {
    header('Location: events.php');
    exit();
}
?>