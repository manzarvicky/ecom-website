// Mobile Navigation Toggle
const mobileMenuBtn = document.createElement('button');
mobileMenuBtn.classList.add('mobile-menu-btn');
mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';

const navbar = document.querySelector('.navbar .container');
navbar.insertBefore(mobileMenuBtn, navbar.firstChild);

mobileMenuBtn.addEventListener('click', () => {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('show');
});

// Add to Cart Animation
function addToCart(productId) {
    const cartCount = document.querySelector('.cart-count');
    let count = parseInt(cartCount.textContent || '0');
    cartCount.textContent = count + 1;

    // Animate cart icon
    const cartIcon = document.querySelector('.cart-icon');
    cartIcon.classList.add('bounce');
    setTimeout(() => cartIcon.classList.remove('bounce'), 1000);

    // Send AJAX request to add item to cart
    fetch('add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!');
        } else {
            showNotification('Error adding product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding product to cart', 'error');
    });
}

// Notification System
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Product Image Gallery
function initializeGallery() {
    const mainImage = document.querySelector('.product-main-image');
    const thumbnails = document.querySelectorAll('.product-thumbnail');

    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                mainImage.src = thumbnail.src;
                thumbnails.forEach(t => t.classList.remove('active'));
                thumbnail.classList.add('active');
            });
        });
    }
}

// Newsletter Form Submission
const newsletterForm = document.querySelector('.newsletter-form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const emailInput = newsletterForm.querySelector('input[type="email"]');
        
        fetch('subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: emailInput.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Successfully subscribed to newsletter!');
                emailInput.value = '';
            } else {
                showNotification(data.message || 'Error subscribing to newsletter', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error subscribing to newsletter', 'error');
        });
    });
}

// Initialize features when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeGallery();
});