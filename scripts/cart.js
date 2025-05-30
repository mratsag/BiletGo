function updateQuantity(eventId, change) {
            const input = document.getElementById('qty-' + eventId);
            let newValue = parseInt(input.value) + change;
            
            if (newValue < 1) {
                if (confirm('Bu ürünü sepetten kaldırmak istediğinizden emin misiniz?')) {
                    removeFromCart(eventId);
                }
                return;
            }
            
            input.value = newValue;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `update_quantity=1&event_id=${eventId}&quantity=${newValue}&ajax=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Sayfayı yenile
                }
            });
        }

        function removeFromCart(eventId) {
            if (!confirm('Bu ürünü sepetten kaldırmak istediğinizden emin misiniz?')) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `remove_from_cart=1&event_id=${eventId}&ajax=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Sayfayı yenile
                }
            });
        }