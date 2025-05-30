<?php
session_start();

// Veritabanı bağlantısı
include '../config.php';

// Hatalı giriş mesajı
$error = "";
$success = "";

// Kayıt başarılı mesajı kontrolü
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Kayıt işleminiz başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.";
}

// Form gönderildiyse işle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen veriler
    $email = $_POST['email'];
    $password = $_POST['password'];

    // E-posta ile kullanıcıyı çek
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcı varsa ve şifre doğruysa
    if ($result && password_verify($password, $result['password'])) {
        // Oturumda kullanıcı bilgilerini sakla
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['email'] = $result['email'];
        $_SESSION['name'] = $result['username'];  // Kullanıcı adı
        
        // Debug için - gerçek uygulamada kaldırın
        error_log("Session set - User ID: " . $result['id'] . ", Username: " . $result['username']);

        // Ana sayfaya yönlendir
        header('Location: ../index.php');
        exit();
    } else {
        $error = "Hatalı e-posta veya şifre.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giriş Yap - BiletGo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
  <!-- Animated Background -->
  <div class="particles" id="particles"></div>

  <!-- Home Link -->
  <a href="../index.php" class="home-link">
    <i class="fas fa-arrow-left"></i>
    Ana Sayfa
  </a>

  <div class="login-container">
    <div class="login-form">
      <div class="brand-logo">
        <i class="fas fa-ticket-alt"></i>
        <h1 class="brand-title">BiletGo</h1>
      </div>
      
      <h2 class="welcome-text">Hesabınıza giriş yapın</h2>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i>
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
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
          <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="login-btn">
          <i class="fas fa-sign-in-alt me-2"></i>
          Giriş Yap
        </button>
        
        <div class="register-link">
          <p class="mb-0">Henüz hesabınız yok mu? 
            <a href="register.php">Hemen kayıt olun</a>
          </p>
        </div>
      </form>
    </div>
  </div>

  <script src="../scripts/login.js"></script>
</body>
</html>