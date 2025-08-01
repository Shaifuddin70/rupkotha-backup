<?php

/**
 * functions.php
 * A central file for all helper functions used across the application.
 */

// --- Security & Authentication ---
use JetBrains\PhpStorm\NoReturn;

/**
 * Checks if a user is logged in by verifying the session.
 * @return bool True if logged in, false otherwise.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Checks if an admin is logged in.
 * @return bool True if admin is logged in, false otherwise.
 */
function isAdmin(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * A wrapper for htmlspecialchars to prevent XSS attacks.
 * Makes code cleaner and easier to read.
 *
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function esc_html(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generates a Cross-Site Request Forgery (CSRF) token and stores it in the session.
 * @return string The generated CSRF token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 * @param string|null $token The token from the form submission.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token(?string $token): bool
{
    if (isset($_SESSION['csrf_token']) && $token && hash_equals($_SESSION['csrf_token'], $token)) {
        // Token is valid, unset it to prevent reuse
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

/**
 * Returns an HTML hidden input field with the CSRF token.
 * To be used inside forms.
 * @return string The HTML input tag.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}


// --- Navigation & URL ---

/**
 * Redirects the user to a specified URL and terminates the script.
 * @param string $url The URL to redirect to.
 */
#[NoReturn] function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Checks if the current page matches a given URL to set an 'active' class for navigation links.
 * @param string $path The path to check against (e.g., 'products.php').
 * @return string Returns 'active' if it matches, otherwise an empty string.
 */
function set_nav_active(string $path): string
{
    // basename() gets the filename from the current script's path
    return basename($_SERVER['SCRIPT_NAME']) === $path ? 'active' : '';
}


// --- Formatting & Display ---

/**
 * Formats a number as a price with the Bangladeshi Taka symbol.
 * @param float $price The price to format.
 * @return string The formatted price string (e.g., '৳1,250.50').
 */
function formatPrice(float $price): string
{
    return '৳' . number_format($price, 2);
}

/**
 * Formats a timestamp into a more readable date format.
 * @param string $dateString The date string from the database.
 * @param string $format The desired output format (uses PHP date() format).
 * @return string The formatted date.
 */
function format_date(string $dateString, string $format = 'd M, Y'): string
{
    $timestamp = strtotime($dateString);
    return date($format, $timestamp);
}

/**
 * Creates a URL-friendly "slug" from a string.
 * Example: "My New Product!" becomes "my-new-product".
 * @param string $text The input string.
 * @return string The generated slug.
 */
function create_slug(string $text): string
{

    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}


// --- File Handling ---

/**
 * Handles image uploads securely and can be used by any script.
 *
 * @param array $file The $_FILES['input_name'] array from the form.
 * @param string|null $current_image The name of an existing image to be replaced.
 * @return string|false Returns the new, unique filename on success, or false on failure.
 */
function handleImageUpload(array $file, ?string $current_image = null): string|false
{
    // If no new file was uploaded, just return the name of the current image.
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $current_image;
    }

    // Check for other upload errors.
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "File upload error code: " . $file['error'];
        return false;
    }

    // --- Configuration ---
    $upload_dir = __DIR__ . '/../assets/uploads/';
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024; // 5 Megabytes

    // --- Validation ---
    if ($file['size'] > $max_file_size) {
        $_SESSION['error_message'] = "File size exceeds the 5MB limit.";
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_mime_types, true)) {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        return false;
    }

    // --- File Processing ---
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = bin2hex(random_bytes(12)) . '.' . $extension;
    $destination = $upload_dir . $new_file_name;

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
        $_SESSION['error_message'] = "Critical: Failed to create the upload directory.";
        return false;
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if ($current_image && file_exists($upload_dir . $current_image)) {
            unlink($upload_dir . $current_image);
        }
        return $new_file_name;
    }

    $_SESSION['error_message'] = "Failed to move the uploaded file.";
    return false;
}

function handleAdditionalImages(array $files, int $product_id, PDO $pdo): bool
{
    // Check if any files were uploaded
    if (empty($files['name'][0])) {
        return true;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $upload_dir = __DIR__ . '/../assets/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$key];
            $file_type = mime_content_type($tmp_name);

            if (in_array($file_type, $allowed_types)) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $new_filename = substr(md5(uniqid()), 0, 24) . '.' . $extension;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    // Insert into the new product_images table
                    try {
                        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$product_id, $new_filename]);
                    } catch (PDOException $e) {
                        // Optional: Log this error instead of halting
                        error_log("Failed to insert image for product $product_id: " . $e->getMessage());
                    }
                }
            }
        }
    }
    return true;
}