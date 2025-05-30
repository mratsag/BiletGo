<?php
require_once '../config.php';

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    try {
        // Aynı email var mı kontrol et
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = "Bu e-posta ile zaten bir hesap var.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);
            $success = true;
            header("Location: login.php?success=1");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kayıt Ol - BiletGo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/register.css">
</head>
<body>
  <!-- Animated Background -->
  <div class="particles" id="particles"></div>

  <!-- Home Link -->
  <a href="../index.php" class="home-link">
    <i class="fas fa-arrow-left"></i>
    Ana Sayfa
  </a>

  <div class="register-container">
    <div class="register-form">
      <div class="brand-logo">
        <i class="fas fa-ticket-alt"></i>
        <h1 class="brand-title">BiletGo</h1>
      </div>
      
      <h2 class="welcome-text">Harika etkinliklere katılmaya hazır mısınız?</h2>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i>
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST" id="registerForm">
        <div class="mb-3">
          <label for="username" class="form-label">
            <i class="fas fa-user me-2"></i>Ad Soyad
          </label>
          <input type="text" class="form-control" id="username" name="username" placeholder="Adınız ve soyadınız" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">
            <i class="fas fa-envelope me-2"></i>E-posta Adresiniz
          </label>
          <input type="email" class="form-control" id="email" name="email" placeholder="ornek@email.com" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">
            <i class="fas fa-lock me-2"></i>Şifreniz
          </label>
          <input type="password" class="form-control" id="password" name="password" placeholder="En az 6 karakter" required minlength="6">
          <div class="password-strength" id="passwordStrength"></div>
        </div>

        <button type="submit" class="register-btn">
          <i class="fas fa-user-plus me-2"></i>
          Hesap Oluştur
        </button>

        <div class="features">
          <div class="feature-item">
            <i class="fas fa-check"></i>
            <span>Binlerce etkinlik arasından seçim yapın</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check"></i>
            <span>Güvenli ödeme sistemi</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check"></i>
            <span>Anında bilet teslimatı</span>
          </div>
        </div>
        
        <div class="login-link">
          <p class="mb-0">Zaten hesabınız var mı? 
            <a href="login.php">Giriş yapın</a>
          </p>
        </div>
      </form>
    </div>
  </div>

  <script src="../scripts/register.css"></script>
</body>
</html>