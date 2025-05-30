// Animated particles
function createParticles() {
  const particles = document.getElementById('particles');
  const particleCount = 20;
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    particle.style.width = Math.random() * 4 + 2 + 'px';
    particle.style.height = particle.style.width;
    particle.style.animationDelay = Math.random() * 6 + 's';
    particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
    particles.appendChild(particle);
  }
}

// Add to cart functionality
document.addEventListener('DOMContentLoaded', function() {
  createParticles();
  
  // Add to cart forms
  document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const btn = this.querySelector('.buy-btn');
      const eventId = this.dataset.eventId;
      const originalContent = btn.innerHTML;
      
      // Show loading
      btn.innerHTML = '<div class="loading"></div> Ekleniyor...';
      btn.disabled = true;
      
      // Send AJAX request
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `add_to_cart=1&event_id=${eventId}&ajax=1`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update cart count
          const cartBadge = document.querySelector('.cart-badge');
          if (cartBadge) {
            cartBadge.textContent = data.cart_count;
          } else if (data.cart_count > 0) {
            const cartBtn = document.querySelector('.cart-btn');
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.textContent = data.cart_count;
            cartBtn.appendChild(badge);
          }
          
          // Success animation
          btn.innerHTML = '<i class="fas fa-check me-2"></i>Eklendi!';
          btn.style.background = '#10b981';
          
          setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.background = '';
            btn.disabled = false;
          }, 2000);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalContent;
        btn.disabled = false;
      });
    });
  });

  // Image lazy loading for event images
  const images = document.querySelectorAll('.event-image img');
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.classList.add('fade-in');
          imageObserver.unobserve(img);
        }
      });
    });

    images.forEach(img => imageObserver.observe(img));
  }
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.navbar');
  if (window.scrollY > 100) {
    navbar.style.background = 'rgba(15, 23, 42, 0.98)';
    navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
  } else {
    navbar.style.background = 'rgba(15, 23, 42, 0.95)';
    navbar.style.boxShadow = 'none';
  }
});

// Prevent image drag
document.querySelectorAll('.event-image img').forEach(img => {
  img.addEventListener('dragstart', (e) => e.preventDefault());
});

// Add hover effect to event cards
document.querySelectorAll('.event-card').forEach(card => {
  card.addEventListener('mouseenter', () => {
    card.style.transform = 'translateY(-10px)';
  });
  
  card.addEventListener('mouseleave', () => {
    card.style.transform = 'translateY(0)';
  });
});