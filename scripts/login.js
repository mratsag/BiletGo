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