<?php
/**
 * CSRF Token Helper
 * Generates and validates one-time CSRF tokens for form protection.
 */

/**
 * Generate (or return existing) CSRF token for the current session.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Output a hidden input field with the CSRF token.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST request.
 * Returns true if valid, false otherwise.
 */
function csrf_validate(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored    = $_SESSION['_csrf_token'] ?? '';

    if ($submitted === '' || $stored === '') {
        return false;
    }

    return hash_equals($stored, $submitted);
}

/**
 * Validate CSRF and die with error if invalid.
 */
function csrf_require(): void
{
    if (!csrf_validate()) {
        http_response_code(403);
        die('<div class="alert alert-danger m-4">Security token mismatch. Please go back and try again.</div>');
    }
}

/**
 * Regenerate CSRF token (call after successful form submission if needed).
 */
function csrf_regenerate(): void
{
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
