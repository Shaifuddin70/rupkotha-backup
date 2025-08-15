<?php

if (!isset($settings)) {
    $settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
if (!isset($categories)) {
    $categories_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name LIMIT 5");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- ======================= FOOTER SECTION ======================= -->
<footer class="modern-footer">
    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="container">
            <div class="row">
                <!-- Company Info Column -->
                <div class="col-lg-4 col-md-6 footer-column">
                    <div class="footer-brand">
                        <div class="brand-logo">
                            <img src="assets/images/logo.jpg" alt="<?= esc_html($settings['company_name'] ?? 'Rupkotha') ?>" class="footer-logo">
                        </div>
                        <h3 class="brand-name"><?= esc_html($settings['company_name'] ?? 'Rupkotha Properties Bangladesh') ?></h3>
                        <p class="brand-description">
                            Your trusted partner for premium quality products in Bangladesh. We deliver excellence with every purchase and exceptional customer service.
                        </p>
                    </div>

                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">Address</span>
                                <span class="contact-value">Dhaka, Bangladesh</span>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">Email</span>
                                <a href="mailto:<?= esc_html($settings['email'] ?? 'info@rupkotha.com') ?>" class="contact-value"><?= esc_html($settings['email'] ?? 'info@rupkotha.com') ?></a>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">Phone</span>
                                <a href="tel:<?= esc_html($settings['phone'] ?? '+880123456789') ?>" class="contact-value"><?= esc_html($settings['phone'] ?? '+880 123 456 789') ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links Column -->
                <div class="col-lg-2 col-md-6 footer-column">
                    <div class="footer-widget">
                        <h4 class="widget-title">
                            <i class="bi bi-link-45deg me-2"></i>
                            Quick Links
                        </h4>
                        <ul class="footer-links">
                            <li><a href="index"><i class="bi bi-house"></i>Home</a></li>
                            <li><a href="all-products"><i class="bi bi-grid"></i>All Products</a></li>
                            <li><a href="about"><i class="bi bi-info-circle"></i>About Us</a></li>
                            <li><a href="contact"><i class="bi bi-envelope"></i>Contact</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Categories Column -->
                <div class="col-lg-2 col-md-6 footer-column">
                    <div class="footer-widget">
                        <h4 class="widget-title">
                            <i class="bi bi-tags me-2"></i>
                            Categories
                        </h4>
                        <ul class="footer-links">
                            <?php foreach ($categories as $cat): ?>
                                <li><a href="category?id=<?= $cat['id'] ?>"><i class="bi bi-arrow-right"></i><?= esc_html($cat['name']) ?></a></li>
                            <?php endforeach; ?>
                            <?php if (count($categories) < 5): ?>
                                <li><a href="all-products"><i class="bi bi-plus-circle"></i>View All</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Account & Newsletter Column -->
                <div class="col-lg-4 col-md-6 footer-column">
                    <div class="footer-widget">
                        <h4 class="widget-title">
                            <i class="bi bi-person-circle me-2"></i>
                            Your Account
                        </h4>
                        <ul class="footer-links account-links">
                            <li><a href="profile"><i class="bi bi-person"></i>My Profile</a></li>
                            <li><a href="orders"><i class="bi bi-box-seam"></i>My Orders</a></li>
                            <li><a href="cart"><i class="bi bi-cart"></i>Shopping Cart</a></li>
                        </ul>
                    </div>

                    <div class="footer-widget newsletter-widget">
                        <h4 class="widget-title">
                            <i class="bi bi-envelope-heart me-2"></i>
                            Stay Updated
                        </h4>
                        <p class="newsletter-text">Subscribe to get special offers, free giveaways, and exclusive deals.</p>
                        <form class="newsletter-form" action="#" method="post">
                            <div class="newsletter-input-group">
                                <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                                <button type="submit" class="newsletter-btn">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="copyright">
                            <p>&copy; <?= date('Y') ?> <span class="brand-highlight"><?= esc_html($settings['company_name'] ?? 'Rupkotha') ?></span>. All Rights Reserved.</p>
                            <div class="legal-links">
                                <a href="#" class="legal-link">Privacy Policy</a>
                                <span class="divider">•</span>
                                <a href="#" class="legal-link">Terms of Service</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="footer-extras">
                            <!-- Payment Methods -->
                            <div class="payment-methods">
                                <span class="payment-label">We Accept:</span>
                                <div class="payment-icons">
                                    <img src="assets/images/bkash.svg" alt="bKash" class="payment-icon">
                                    <img src="assets/images/nagad.svg" alt="Nagad" class="payment-icon">
                                    <img src="assets/images/rocket.png" alt="Rocket" class="payment-icon">
                                </div>
                            </div>

                            <!-- Social Media -->
                            <div class="social-links">
                                <?php if (!empty($settings['facebook'])): ?>
                                    <a href="<?= esc_html($settings['facebook']) ?>" target="_blank" class="social-link facebook" title="Facebook">
                                        <i class="bi bi-facebook"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($settings['instagram'])): ?>
                                    <a href="<?= esc_html($settings['instagram']) ?>" target="_blank" class="social-link instagram" title="Instagram">
                                        <i class="bi bi-instagram"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($settings['twitter'])): ?>
                                    <a href="<?= esc_html($settings['twitter']) ?>" target="_blank" class="social-link twitter" title="Twitter">
                                        <i class="bi bi-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="social-link whatsapp" title="WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" title="Back to Top">
        <i class="bi bi-arrow-up"></i>
    </button>
</footer>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Footer Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Back to Top Button
        const backToTopBtn = document.getElementById('backToTop');

        if (backToTopBtn) {
            // Show/hide back to top button based on scroll position
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            });

            // Smooth scroll to top when clicked
            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Newsletter form submission
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const emailInput = this.querySelector('.newsletter-input');
                const email = emailInput.value.trim();

                if (email) {
                    // Show success message
                    const btn = this.querySelector('.newsletter-btn');
                    const originalContent = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                    btn.style.background = '#28a745';

                    // Reset after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.style.background = '';
                        emailInput.value = '';
                    }, 2000);
                }
            });
        }
    });
</script>

</body>

</html>