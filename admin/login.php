<?php
session_start();

// Zaten giriş yapılmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Veritabanı bağlantısı
require_once '../config.php';

// Hata ve başarı mesajları
$error = "";
$success = "";

// Form gönderildiyse işle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        // Admin kullanıcıyı kontrol et
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Session bilgilerini ayarla
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['full_name'] = $admin['full_name'];

            // Başarılı giriş - dashboard'a yönlendir
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Hatalı e-posta veya şifre. Yalnızca admin kullanıcılar giriş yapabilir.";
        }
    } catch (PDOException $e) {
        $error = "Bir hata oluştu. Lütfen tekrar deneyin.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiletGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --accent-color: #ec4899;
            --dark-bg: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #1e293b;
            --text-light: #64748b;
            --gradient-admin: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-admin-2: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gradient-admin);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.3;
        }

        .particle {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-form {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .login-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-admin-2);
            border-radius: 24px 24px 0 0;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo i {
            font-size: 3rem;
            background: var(--gradient-admin-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 700;
            background: var(--gradient-admin-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .admin-badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .welcome-text {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.2);
            background: white;
        }

        .login-btn {
            width: 100%;
            background: var(--gradient-admin-2);
            border: none;
            color: white;
            padding: 0.9rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            margin-top: 1rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }

        /* Home link */
        .home-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .home-link:hover {
            color: rgba(255, 255, 255, 0.8);
            transform: translateX(-5px);
        }

        .security-notice {
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .security-notice i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-form {
                padding: 2rem;
            }
            
            .home-link {
                top: 1rem;
                left: 1rem;
            }
        }

        /* Loading state */
        .login-btn.loading {
            position: relative;
            color: transparent;
        }

        .login-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #f3f3f3;
            border-radius: 50%;
            border-top: 2px solid white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
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
                <i class="fas fa-shield-alt"></i>
                <h1 class="brand-title">Biletix</h1>
                <span class="admin-badge">Admin Panel</span>
            </div>
            
            <h2 class="welcome-text">Yönetici Girişi</h2>

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

            <form action="" method="POST" id="adminLoginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Admin E-posta
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="admin@biletix.com" required autocomplete="email">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Şifre
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Giriş Yap
                </button>
            </form>

            <div class="security-notice">
                <i class="fas fa-info-circle"></i>
                Bu sayfa sadece yetkili yöneticiler içindir.
            </div>
        </div>
    </div>

    <script>
        // Animated particles
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 4 + 2;
                const posX = Math.random() * window.innerWidth;
                const posY = Math.random() * window.innerHeight;
                const delay = Math.random() * 6;
                
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = posX + 'px';
                particle.style.top = posY + 'px';
                particle.style.animationDelay = delay + 's';
                
                particles.appendChild(particle);
            }
        }

        // Initialize particles
        createParticles();

        // Form animation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.6s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });

        // Form submission with loading state
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });
    </script>
</body>
</html>