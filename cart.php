<?php
session_start();

// Veritabanı bağlantısını dahil et
include 'config.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$userId = $_SESSION['user_id'];

// Sepetten ürün kaldırma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $event_id = intval($_POST['event_id']);
    if (isset($_SESSION['cart'][$event_id])) {
        unset($_SESSION['cart'][$event_id]);
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'] ?? [])]);
        exit;
    }
}

// Miktar güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $event_id = intval($_POST['event_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && isset($_SESSION['cart'][$event_id])) {
        $_SESSION['cart'][$event_id] = $quantity;
    } elseif ($quantity <= 0) {
        unset($_SESSION['cart'][$event_id]);
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'] ?? [])]);
        exit;
    }
}

// Satın alma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    if (!empty($_SESSION['cart'])) {
        try {
            $pdo->beginTransaction();
            
            foreach ($_SESSION['cart'] as $event_id => $quantity) {
                for ($i = 0; $i < $quantity; $i++) {
                    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, event_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $event_id]);
                }
            }
            
            $pdo->commit();
            $_SESSION['cart'] = []; // Sepeti temizle
            $success_message = "Biletleriniz başarıyla satın alındı!";
        } catch (PDOException $e) {
            $pdo->rollback();
            $error_message = "Satın alma işlemi sırasında bir hata oluştu.";
            error_log("Purchase error: " . $e->getMessage());
        }
    }
}

// Sepetteki ürünleri getir
$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $event_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($event_ids) - 1) . '?';
    
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, c.name as category_name 
            FROM events e 
            LEFT JOIN categories c ON e.category_id = c.id 
            WHERE e.id IN ($placeholders)
        ");
        $stmt->execute($event_ids);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as $event) {
            $quantity = $_SESSION['cart'][$event['id']];
            $cart_items[] = [
                'event' => $event,
                'quantity' => $quantity,
                'subtotal' => $event['price'] * $quantity
            ];
            $total_price += $event['price'] * $quantity;
        }
    } catch (PDOException $e) {
        error_log("Cart fetch error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - Biletix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/cart.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2"></i>Biletix
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Etkinlikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">Sepet</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3" style="color: white !important; font-weight: 500;">
                        Merhaba, <?php echo htmlspecialchars($userName); ?>
                    </span>
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user me-1"></i>Profil
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="cart-container">
            <div class="cart-header">
                <h1><i class="fas fa-shopping-cart me-2"></i>Sepetim</h1>
                <p class="mb-0">Seçtiğiniz etkinlikler</p>
            </div>

            <div class="p-4">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Sepetiniz boş</h3>
                        <p>Henüz sepetinize ürün eklemediniz.</p>
                        <a href="index.php" class="continue-shopping-btn">
                            <i class="fas fa-arrow-left me-2"></i>Alışverişe Devam Et
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="event-image-small">
                                        <i class="fas fa-music"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['event']['title']); ?></h5>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($item['event']['location']); ?>
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php 
                                            $date = new DateTime($item['event']['date'] . ' ' . $item['event']['time']);
                                            echo $date->format('d M Y, H:i'); 
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['event']['id']; ?>, -1)">-</button>
                                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" 
                                               id="qty-<?php echo $item['event']['id']; ?>" min="1">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['event']['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 text-center">
                                    <div class="fw-bold">₺<?php echo number_format($item['event']['price'], 2); ?></div>
                                    <small class="text-muted">birim fiyat</small>
                                </div>
                                
                                <div class="col-md-1 text-center">
                                    <div class="fw-bold text-primary">₺<?php echo number_format($item['subtotal'], 2); ?></div>
                                </div>
                                
                                <div class="col-md-1 text-end">
                                    <button class="remove-btn" onclick="removeFromCart(<?php echo $item['event']['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Toplam Ürün:</span>
                            <span><?php echo array_sum(array_column($cart_items, 'quantity')); ?> adet</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Toplam Tutar:</span>
                            <span>₺<?php echo number_format($total_price, 2); ?></span>
                        </div>

                        <form method="POST">
                            <button type="submit" name="purchase" class="purchase-btn">
                                <i class="fas fa-credit-card me-2"></i>
                                Satın Al (₺<?php echo number_format($total_price, 2); ?>)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../scripts/cart.js"></script>
</body>
</html>