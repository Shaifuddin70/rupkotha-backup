<?php
// This is your "About Us" page, e.g., about.php

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// Fetch company name for dynamic content, with a fallback
$companyName = $settings['company_name'] ?? 'Rupkotha';
?>

<!-- Custom CSS for this page -->
<style>
    .page-header {
        background-color: #f8f9fa;
        padding: 2rem 0;
        margin-bottom: 3rem;
    }
    .about-section {
        padding: 3rem 0;
    }
    .icon-box {
        font-size: 2.5rem;
        color: #0d6efd;
    }
    .team-member img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">About <?= esc_html($companyName) ?></h1>
    </div>
</div>

<main class="container my-5">

    <!-- Our Story Section -->
    <section class="about-section">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://placehold.co/600x400/EBF8FF/3182CE?text=Our+Journey" alt="Our Company Journey" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="fw-bold mb-3">Our Story</h2>
                <p class="lead text-muted">Founded in Dhaka with a passion for quality and excellence, <?= esc_html($companyName) ?> began as a small venture with a big dream: to provide the people of Bangladesh with access to premium products and unparalleled service.</p>
                <p>From our humble beginnings, we have grown into a trusted name in e-commerce, always staying true to our core mission. We believe that every customer deserves the best, and we work tirelessly to source and deliver products that meet our high standards of quality, craftsmanship, and value.</p>
            </div>
        </div>
    </section>

    <hr class="my-5">

    <!-- Our Values Section -->
    <section class="about-section text-center">
        <h2 class="fw-bold mb-5">Our Core Values</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="icon-box mb-3">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <h4 class="fw-bold">Quality First</h4>
                <p class="text-muted">We are obsessed with quality. Every product in our catalog is carefully selected and tested to ensure it meets our rigorous standards and your high expectations.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="icon-box mb-3">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h4 class="fw-bold">Customer Commitment</h4>
                <p class="text-muted">Our customers are at the heart of everything we do. We are dedicated to providing exceptional service and building lasting relationships based on trust and satisfaction.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="icon-box mb-3">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <h4 class="fw-bold">Integrity & Trust</h4>
                <p class="text-muted">We operate with honesty and transparency. From our pricing to our policies, we believe in being straightforward and earning the trust of our community every single day.</p>
            </div>
        </div>
    </section>

    <hr class="my-5">

    <!-- Meet the Team Section -->
    <section class="about-section bg-light py-5 rounded">
        <div class="container text-center">
            <h2 class="fw-bold mb-5">Meet Our Team</h2>
            <div class="row justify-content-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member">
                        <img src="https://placehold.co/150x150/2D3748/E2E8F0?text=CEO" class="rounded-circle" alt="Team Member">
                        <h5 class="mt-3 mb-0">Shaifuddin Rokib</h5>
                        <p class="text-muted">Founder & CEO</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member">
                        <img src="https://placehold.co/150x150/2D3748/E2E8F0?text=COO" class="rounded-circle" alt="Team Member">
                        <h5 class="mt-3 mb-0">Jane Doe</h5>
                        <p class="text-muted">Chief Operating Officer</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member">
                        <img src="https://placehold.co/150x150/2D3748/E2E8F0?text=Head" class="rounded-circle" alt="Team Member">
                        <h5 class="mt-3 mb-0">John Smith</h5>
                        <p class="text-muted">Head of Products</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
