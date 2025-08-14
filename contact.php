<?php
// This is your "Contact Us" page, e.g., contact.php

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// --- Handle Contact Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $errors = [];

    // Validation
    if (empty($name)) $errors[] = "Your name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (empty($subject)) $errors[] = "A subject is required.";
    if (empty($message)) $errors[] = "A message is required.";

    if (empty($errors)) {
        // --- Email Sending Logic ---
        // In a real application, you would use a library like PHPMailer to send an email.
        // For this example, we will just simulate a successful submission.

        // $to = $settings['email'] ?? 'your-email@example.com';
        // $email_subject = "New Contact Form Message: " . $subject;
        // $email_body = "You have received a new message from your website contact form.\n\n";
        // $email_body .= "Name: $name\n";
        // $email_body .= "Email: $email\n";
        // $email_body .= "Message:\n$message\n";
        // $headers = "From: noreply@yourdomain.com\n";
        // $headers .= "Reply-To: $email";
        // mail($to, $email_subject, $email_body, $headers);

        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Thank you for your message! We will get back to you shortly.'];
        redirect('contact');
    }
}

// Fetch company contact details from settings
$companyPhone = $settings['phone'] ?? '+880 123 456 789';
$companyEmail = $settings['email'] ?? 'info@rupkotha.com';
$companyAddress = $settings['address'] ?? 'Dhaka, Bangladesh';

?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        --card-hover-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        --border-radius: 20px;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
    }

    .hero-section {
        background: var(--primary-gradient);
        padding: 80px 0;
        margin-bottom: 80px;
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
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .hero-subtitle {
        font-size: 1.3rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .modern-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
    }

    .modern-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--card-hover-shadow);
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
    }

    .contact-info-item {
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .contact-info-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .contact-info-item:hover::before {
        opacity: 1;
    }

    .contact-info-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .icon-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        position: relative;
        z-index: 2;
    }

    .icon-wrapper i {
        font-size: 1.8rem;
        color: white;
    }

    .form-control {
        border: 2px solid #e8ecef;
        border-radius: 12px;
        padding: 15px 20px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
        background: white;
    }

    .form-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .btn-modern {
        background: var(--primary-gradient);
        border: none;
        border-radius: 12px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 1.1rem;
        color: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
    }

    .alert-modern {
        border: none;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 2rem;
        position: relative;
    }

    .alert-danger {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
    }

    .alert-success {
        background: var(--success-gradient);
        color: white;
    }

    .map-container {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        margin-top: 60px;
    }

    .map-container iframe {
        border-radius: var(--border-radius);
        filter: grayscale(20%) contrast(1.2);
        transition: filter 0.3s ease;
    }

    .map-container:hover iframe {
        filter: grayscale(0%) contrast(1);
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
            font-size: 1.1rem;
        }

        .contact-info-item {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
    }
</style>



<main class="container">
    <div class="row g-5 mt-4">
        <!-- Contact Information Column -->
        <div class="col-lg-5">
            <div class="contact-info-item modern-card">
                <div class="icon-wrapper">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-3">Visit Our Office</h4>
                    <p class="text-muted mb-0 lh-lg"><?= esc_html($companyAddress) ?></p>
                </div>
            </div>

            <div class="contact-info-item modern-card">
                <div class="icon-wrapper">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-3">Email Us</h4>
                    <p class="mb-0">
                        <a href="mailto:<?= esc_html($companyEmail) ?>"
                            class="text-decoration-none fw-semibold"
                            style="color: #667eea;"><?= esc_html($companyEmail) ?></a>
                    </p>
                </div>
            </div>

            <div class="contact-info-item modern-card">
                <div class="icon-wrapper">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-3">Call Us</h4>
                    <p class="mb-0">
                        <a href="tel:<?= esc_html($companyPhone) ?>"
                            class="text-decoration-none fw-semibold"
                            style="color: #667eea;"><?= esc_html($companyPhone) ?></a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Form Column -->
        <div class="col-lg-7">
            <div class="modern-card">
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold mb-3">Send Us a Message</h2>
                        <p class="text-muted">Fill out the form below and we'll get back to you within 24 hours.</p>
                        <div class="section-divider"></div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-modern alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                                <div>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= esc_html($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="contact" method="post" class="row g-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                required
                                placeholder="Enter your full name"
                                value="<?= esc_html($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                required
                                placeholder="your@email.com"
                                value="<?= esc_html($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text"
                                class="form-control"
                                id="subject"
                                name="subject"
                                required
                                placeholder="What is this about?"
                                value="<?= esc_html($_POST['subject'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control"
                                id="message"
                                name="message"
                                rows="6"
                                required
                                placeholder="Tell us more about your inquiry..."><?= esc_html($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 text-center pt-3">
                            <button type="submit" class="btn btn-modern btn-lg px-5">
                                <i class="bi bi-send-fill me-2"></i>
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="row">
        <div class="col-12">
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d116833.9535641521!2d90.33294894335938!3d23.7808874!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b087026b81%3A0x8fa563bbdd5904c2!2sDhaka!5e0!3m2!1sen!2sbd!4v1672756929452!5m2!1sen!2sbd"
                    width="100%"
                    height="500"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>