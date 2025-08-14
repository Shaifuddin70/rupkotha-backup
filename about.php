<?php
// This is your "About Us" page, e.g., about.php

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// Fetch company name for dynamic content, with a fallback
$companyName = $settings['company_name'] ?? 'Rupkotha';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --purple-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --orange-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        --card-hover-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        --border-radius: 20px;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
    }

    .hero-section {
        background: var(--primary-gradient);
        padding: 120px 0;
        margin-bottom: 100px;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="g"><stop offset="20%" stop-color="rgba(255,255,255,0.1)"/><stop offset="50%" stop-color="rgba(255,255,255,0.05)"/><stop offset="100%" stop-color="transparent"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23g)"/><circle cx="800" cy="300" r="150" fill="url(%23g)"/><circle cx="400" cy="700" r="120" fill="url(%23g)"/><circle cx="900" cy="800" r="80" fill="url(%23g)"/></svg>');
        animation: float 20s infinite linear;
    }

    @keyframes float {
        0% {
            transform: translateX(-100px) rotate(0deg);
        }

        100% {
            transform: translateX(100px) rotate(360deg);
        }
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }

    .hero-title {
        font-size: 4rem;
        font-weight: 900;
        margin-bottom: 1.5rem;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        background: linear-gradient(45deg, #fff, #e2e8f0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 1.4rem;
        opacity: 0.95;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.7;
        font-weight: 300;
    }

    .modern-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
    }

    .modern-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--card-hover-shadow);
    }

    .story-section {
        padding: 100px 0;
        position: relative;
    }

    .story-image {
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        transition: transform 0.4s ease;
        position: relative;
        overflow: hidden;
    }

    .story-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--primary-gradient);
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: 1;
    }

    .story-image:hover::before {
        opacity: 0.2;
    }

    .story-image:hover {
        transform: scale(1.05);
    }

    .story-content h2 {
        font-size: 3rem;
        font-weight: 800;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 2rem;
    }

    .story-content .lead {
        font-size: 1.3rem;
        line-height: 1.8;
        color: #4a5568;
        margin-bottom: 2rem;
    }

    .story-content p {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #718096;
    }

    .values-section {
        padding: 100px 0;
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        position: relative;
    }

    .values-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 3rem;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .value-card {
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.4s ease;
        position: relative;
        height: 100%;
    }

    .value-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .value-card:hover::before {
        opacity: 1;
    }

    .value-card:hover {
        transform: translateY(-15px) scale(1.02);
    }

    .icon-wrapper {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        position: relative;
        z-index: 2;
        transition: all 0.4s ease;
    }

    .icon-wrapper.quality {
        background: var(--success-gradient);
    }

    .icon-wrapper.customer {
        background: var(--secondary-gradient);
    }

    .icon-wrapper.integrity {
        background: var(--orange-gradient);
    }

    .icon-wrapper i {
        font-size: 2.5rem;
        color: white;
        transition: transform 0.4s ease;
    }

    .value-card:hover .icon-wrapper {
        transform: scale(1.1) rotate(10deg);
    }

    .value-card:hover .icon-wrapper i {
        transform: scale(1.1);
    }

    .value-card h4 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #2d3748;
        position: relative;
        z-index: 2;
    }

    .value-card p {
        color: #4a5568;
        line-height: 1.7;
        font-size: 1rem;
        position: relative;
        z-index: 2;
    }

    .team-section {
        padding: 100px 0;
        position: relative;
    }

    .team-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 4rem;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .team-member {
        text-align: center;
        padding: 2rem;
        transition: all 0.4s ease;
        position: relative;
    }

    .team-member:hover {
        transform: translateY(-10px);
    }

    .team-avatar {
        position: relative;
        display: inline-block;
        margin-bottom: 2rem;
    }

    .team-avatar::before {
        content: '';
        position: absolute;
        top: -10px;
        left: -10px;
        right: -10px;
        bottom: -10px;
        background: var(--primary-gradient);
        border-radius: 50%;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .team-member:hover .team-avatar::before {
        opacity: 1;
    }

    .team-avatar img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        transition: all 0.4s ease;
        position: relative;
        z-index: 2;
    }

    .team-member:hover .team-avatar img {
        transform: scale(1.05);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .team-member h5 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }

    .team-member .position {
        color: #667eea;
        font-weight: 500;
        font-size: 1rem;
    }

    .floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: hidden;
    }

    .floating-elements::before,
    .floating-elements::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.1);
        animation: float-slow 15s infinite ease-in-out;
    }

    .floating-elements::before {
        width: 200px;
        height: 200px;
        top: 10%;
        left: -100px;
        animation-delay: 0s;
    }

    .floating-elements::after {
        width: 150px;
        height: 150px;
        bottom: 20%;
        right: -75px;
        animation-delay: 7s;
        background: rgba(240, 147, 251, 0.1);
    }

    @keyframes float-slow {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-30px) rotate(180deg);
        }
    }

    .section-divider {
        width: 100px;
        height: 4px;
        background: var(--primary-gradient);
        border-radius: 2px;
        margin: 3rem auto;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
        }

        .story-content h2,
        .values-title,
        .team-title {
            font-size: 2.2rem;
        }

        .story-section,
        .values-section,
        .team-section {
            padding: 60px 0;
        }

        .value-card {
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
        }

        .team-avatar img {
            width: 150px;
            height: 150px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">About <?= esc_html($companyName) ?></h1>
            <p class="hero-subtitle">Crafting exceptional experiences through innovation, dedication, and an unwavering commitment to excellence.</p>
        </div>
    </div>
</section>

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