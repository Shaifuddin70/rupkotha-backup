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

<div class="page-header" style="background-color: #f8f9fa; padding: 2rem 0; margin-bottom: 3rem;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">Get In Touch</h1>
    </div>
</div>

<main class="container my-5">
    <div class="row g-5">
        <!-- Contact Information Column -->
        <div class="col-lg-5">
            <h3 class="fw-bold mb-4">Contact Information</h3>
            <p class="text-muted">We're here to help! Whether you have a question about our products, an order, or just want to say hello, feel free to reach out to us through any of the methods below.</p>

            <div class="d-flex align-items-start mb-4">
                <div class="fs-4 text-primary me-3"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <h5 class="fw-bold">Our Address</h5>
                    <p class="mb-0"><?= esc_html($companyAddress) ?></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-4">
                <div class="fs-4 text-primary me-3"><i class="bi bi-envelope-fill"></i></div>
                <div>
                    <h5 class="fw-bold">Email Us</h5>
                    <p class="mb-0"><a href="mailto:<?= esc_html($companyEmail) ?>" class="text-dark"><?= esc_html($companyEmail) ?></a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-4">
                <div class="fs-4 text-primary me-3"><i class="bi bi-telephone-fill"></i></div>
                <div>
                    <h5 class="fw-bold">Call Us</h5>
                    <p class="mb-0"><a href="tel:<?= esc_html($companyPhone) ?>" class="text-dark"><?= esc_html($companyPhone) ?></a></p>
                </div>
            </div>
        </div>

        <!-- Contact Form Column -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Send Us a Message</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= esc_html($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="contact" method="post" class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?= esc_html($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Your Email</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= esc_html($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required value="<?= esc_html($_POST['subject'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= esc_html($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d116833.9535641521!2d90.33294894335938!3d23.7808874!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b087026b81%3A0x8fa563bbdd5904c2!2sDhaka!5e0!3m2!1sen!2sbd!4v1672756929452!5m2!1sen!2sbd"
                        width="100%"
                        height="450"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
