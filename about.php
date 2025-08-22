<?php
// This is your "About Us" page, e.g., about.php

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// Fetch company name for dynamic content, with a fallback
$companyName = $settings['company_name'] ?? 'Rupkotha';
?>




<main>
    <!-- Our Story Section -->
    <section class="story-section">
        <div class="floating-elements"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="story-image">
                        <img src="https://placehold.co/600x450/EBF8FF/3182CE?text=Our+Journey"
                            alt="Our Company Journey"
                            class="img-fluid w-100">
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <div class="story-content">
                        <h2>Our Story</h2>
                        <p class="lead">Founded in Dhaka with a passion for quality and excellence, <?= esc_html($companyName) ?> began as a small venture with a big dream: to provide the people of Bangladesh with access to premium products and unparalleled service.</p>
                        <p>From our humble beginnings, we have grown into a trusted name in e-commerce, always staying true to our core mission. We believe that every customer deserves the best, and we work tirelessly to source and deliver products that meet our high standards of quality, craftsmanship, and value.</p>
                        <p>Today, we continue to innovate and expand, but our commitment remains unchanged: to serve our community with integrity, passion, and the highest level of service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="values-title">Our Core Values</h2>
                <p class="lead text-muted">The principles that guide everything we do</p>
                <div class="section-divider"></div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="modern-card value-card">
                        <div class="icon-wrapper quality">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <h4>Quality First</h4>
                        <p>We are obsessed with quality. Every product in our catalog is carefully selected and tested to ensure it meets our rigorous standards and your high expectations.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="modern-card value-card">
                        <div class="icon-wrapper customer">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <h4>Customer Commitment</h4>
                        <p>Our customers are at the heart of everything we do. We are dedicated to providing exceptional service and building lasting relationships based on trust and satisfaction.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="modern-card value-card">
                        <div class="icon-wrapper integrity">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <h4>Integrity & Trust</h4>
                        <p>We operate with honesty and transparency. From our pricing to our policies, we believe in being straightforward and earning the trust of our community every single day.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Meet the Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="team-title">Meet Our Team</h2>
                <p class="lead text-muted">The passionate individuals behind our success</p>
                <div class="section-divider"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <img src="https://placehold.co/180x180/667eea/ffffff?text=CEO"
                                alt="Shaifuddin Rokib - CEO">
                        </div>
                        <h5>Shaifuddin Rokib</h5>
                        <p class="position">Founder & CEO</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <img src="https://placehold.co/180x180/f093fb/ffffff?text=COO"
                                alt="Jane Doe - COO">
                        </div>
                        <h5>Jane Doe</h5>
                        <p class="position">Chief Operating Officer</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <img src="https://placehold.co/180x180/4facfe/ffffff?text=Head"
                                alt="John Smith - Head of Products">
                        </div>
                        <h5>John Smith</h5>
                        <p class="position">Head of Products</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>