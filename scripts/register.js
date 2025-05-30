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

    // Password strength checker
    function checkPasswordStrength(password) {
      const strengthIndicator = document.getElementById('passwordStrength');
      let strength = 0;
      let message = '';

      if (password.length >= 6) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;

      switch (strength) {
        case 0:
        case 1:
          message = 'Çok zayıf şifre';
          strengthIndicator.className = 'password-strength strength-weak';
          break;
        case 2:
        case 3:
          message = 'Orta güçlükte şifre';
          strengthIndicator.className = 'password-strength strength-medium';
          break;
        case 4:
        case 5:
          message = 'Güçlü şifre';
          strengthIndicator.className = 'password-strength strength-strong';
          break;
      }

      strengthIndicator.textContent = password.length > 0 ? message : '';
    }

    // Initialize particles
    createParticles();

    // Form animation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('.register-form');
      const passwordInput = document.getElementById('password');
      
      form.style.opacity = '0';
      form.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        form.style.transition = 'all 0.6s ease';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
      }, 100);

      // Password strength checker
      passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
      });

      // Form validation
      document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        if (password.length < 6) {
          e.preventDefault();
          alert('Şifreniz en az 6 karakter olmalıdır.');
        }
      });
    });