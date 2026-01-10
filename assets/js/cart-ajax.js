// AJAX Cart Management
(function() {
    'use strict';

    // Function to update cart badge
    function updateCartBadge() {
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let badges = document.querySelectorAll('.cart-badge');
                    
                    // If no badges exist, create them
                    if (badges.length === 0) {
                        const cartLinks = document.querySelectorAll('a[href="cart"], a[href*="cart"]');
                        cartLinks.forEach(link => {
                            if (link.querySelector('.cart-badge') === null) {
                                const badge = document.createElement('span');
                                badge.className = 'cart-badge';
                                link.classList.add('position-relative');
                                link.appendChild(badge);
                            }
                        });
                        badges = document.querySelectorAll('.cart-badge');
                    }
                    
                    badges.forEach(badge => {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    });
                }
            })
            .catch(error => console.error('Error updating cart badge:', error));
    }

    // Function to show notification
    function showCartNotification(type, message) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.cart-ajax-notification');
        existingNotifications.forEach(n => n.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show cart-ajax-notification`;
        notification.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; max-width: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        
        const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle';
        notification.innerHTML = `
            <i class="bi bi-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 150);
            }
        }, 3000);
    }

    // Handle add to cart via AJAX
    function handleAddToCart(event) {
        event.preventDefault();
        
        const link = event.currentTarget;
        const productId = link.getAttribute('href').match(/[?&]id=(\d+)/)?.[1] || 
                         link.dataset.productId;
        
        if (!productId) {
            showCartNotification('error', 'Invalid product ID');
            return;
        }

        // Disable button and show loading state
        const originalHTML = link.innerHTML;
        link.style.opacity = '0.6';
        link.style.pointerEvents = 'none';
        link.innerHTML = '<i class="bi bi-hourglass-split"></i>';

        // Send AJAX request
        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('quantity', '1');
        formData.append('ajax', '1');

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            // Restore button
            link.style.opacity = '1';
            link.style.pointerEvents = 'auto';
            link.innerHTML = originalHTML;

            if (data.success) {
                // Update cart badge
                updateCartBadge();
                
                // Show success notification
                const notificationType = data.warning ? 'warning' : 'success';
                showCartNotification(notificationType, data.message);
                
                // Add animation to button
                link.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    link.style.transform = 'scale(1)';
                }, 200);
            } else {
                showCartNotification('error', data.message || 'Failed to add item to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            link.style.opacity = '1';
            link.style.pointerEvents = 'auto';
            link.innerHTML = originalHTML;
            showCartNotification('error', 'An error occurred. Please try again.');
        });
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Attach event listeners to all cart buttons
        const cartButtons = document.querySelectorAll('a.cart-button[href*="add_to_cart"]');
        cartButtons.forEach(button => {
            button.addEventListener('click', handleAddToCart);
        });

        // Also handle dynamically added buttons (if any)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const newCartButtons = node.querySelectorAll ? 
                            node.querySelectorAll('a.cart-button[href*="add_to_cart"]') : [];
                        newCartButtons.forEach(button => {
                            button.addEventListener('click', handleAddToCart);
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();

