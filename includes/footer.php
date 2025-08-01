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
<footer class="bg-dark text-white pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row text-center text-md-start">

            <!-- Store Info Column -->
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold"><?= esc_html($settings['company_name'] ?? 'Rupkotha Properties Bangladesh') ?></h5>
                <p>
                    Your one-stop shop for premium quality products in Bangladesh. We are committed to offering the best solutions with excellent customer support.
                </p>
                <div class="mt-4">
                    <p><i class="bi bi-geo-alt-fill me-2"></i>Dhaka, Bangladesh</p>
                    <p><i class="bi bi-envelope-fill me-2"></i><?= esc_html($settings['email'] ?? 'info@rupkotha.com') ?></p>
                    <p><i class="bi bi-telephone-fill me-2"></i><?= esc_html($settings['phone'] ?? '+880 123 456 789') ?></p>
                </div>
            </div>

            <!-- Categories Column -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3 footer-category">
                <h5 class="text-uppercase mb-4 fw-bold">Categories</h5>
                <p><a href="all-products" class="text-white">All Products</a></p>
                <?php foreach ($categories as $cat): ?>
                    <p><a href="category?id=<?= $cat['id'] ?>" class="text-white"><?= esc_html($cat['name']) ?></a></p>
                <?php endforeach; ?>
            </div>

            <!-- Useful Links Column -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3 links">
                <h5 class="text-uppercase mb-4 fw-bold">Useful Links</h5>
                <p><a href="profile" class="text-white">Your Account</a></p>
                <p><a href="cart" class="text-white">View Cart</a></p>
                <p><a href="orders" class="text-white">Track My Order</a></p>
                <p><a href="contact" class="text-white">Help & Contact</a></p>
            </div>

            <!-- Newsletter Column -->
            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold">Join Our Newsletter</h5>
                <p>Get E-mail updates about our latest shop and special offers.</p>
                <form action="#" method="post">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Enter your email" aria-label="Enter your email" aria-describedby="button-addon2">
                        <button class="btn btn-primary" type="button" id="button-addon2">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>

        <hr class="my-4">

        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <!-- Copyright -->
            <div class="col-md-7 col-lg-8">
                <p>&copy; <?= date('Y') ?> Copyright:
                    <a href="index" class="text-white fw-bold"><?= esc_html($settings['company_name'] ?? 'Rupkotha') ?>.</a> All Rights Reserved.
                </p>
            </div>

            <!-- Social Links -->
            <div class="col-md-5 col-lg-4">
                <div class="text-center text-md-end">
                    <ul class="list-unstyled list-inline mb-0">
                        <?php if (!empty($settings['facebook'])): ?>
                            <li class="list-inline-item">
                                <a href="<?= esc_html($settings['facebook']) ?>" target="_blank" class="btn-floating btn-sm text-white fs-4"><i class="bi bi-facebook"></i></a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram'])): ?>
                            <li class="list-inline-item">
                                <a href="<?= esc_html($settings['instagram']) ?>" target="_blank" class="btn-floating btn-sm text-white fs-4"><i class="bi bi-instagram"></i></a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter'])): ?>
                            <li class="list-inline-item">
                                <a href="<?= esc_html($settings['twitter']) ?>" target="_blank" class="btn-floating btn-sm text-white fs-4"><i class="bi bi-twitter"></i></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
