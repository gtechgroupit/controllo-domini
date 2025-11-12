<?php
/**
 * User Registration Page
 *
 * @package ControlDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/auth.php';

$auth = getAuth();

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please reload the page and try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $company = $_POST['company'] ?? '';
        $terms = isset($_POST['terms']);

        // Validate
        if (empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match';
    } elseif (!$terms) {
        $error = 'You must accept the terms and conditions';
    } else {
        $result = $auth->register($email, $password, $full_name, $company);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
    } // Close else for CSRF check
}

$page_title = 'Register - Controllo Domini';
$page_description = 'Create your free account to start analyzing domains';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Start analyzing domains for free</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <p class="text-center mt-4">
                <a href="/login" class="btn btn-primary">Go to Login</a>
            </p>
        <?php else: ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                           placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="company">Company (Optional)</label>
                    <input type="text" id="company" name="company"
                           value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>"
                           placeholder="Your Company">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Min. 8 characters">
                    <small class="form-hint">
                        Must contain uppercase, lowercase, number, and special character
                    </small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           placeholder="Confirm your password">
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="terms" required>
                        I accept the <a href="/terms" target="_blank">Terms of Service</a> and
                        <a href="/privacy" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Log In</a></p>
            </div>

            <div class="auth-divider">
                <span>or continue with</span>
            </div>

            <div class="oauth-buttons">
                <a href="/oauth/google" class="btn btn-oauth btn-google">
                    <svg class="oauth-icon" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google
                </a>
                <a href="/oauth/github" class="btn btn-oauth btn-github">
                    <svg class="oauth-icon" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    GitHub
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="auth-benefits">
        <h3>Why Create an Account?</h3>
        <ul class="benefits-list">
            <li>
                <svg class="benefit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <strong>Save Analysis History</strong>
                    <p>Access all your previous domain analyses</p>
                </div>
            </li>
            <li>
                <svg class="benefit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <div>
                    <strong>Monitor Domains</strong>
                    <p>Get alerts when domain status changes</p>
                </div>
            </li>
            <li>
                <svg class="benefit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <div>
                    <strong>API Access</strong>
                    <p>Integrate domain analysis into your apps</p>
                </div>
            </li>
            <li>
                <svg class="benefit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                    <strong>Export Reports</strong>
                    <p>Download analysis results as PDF, CSV, JSON</p>
                </div>
            </li>
        </ul>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    padding: 40px 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    max-width: 1200px;
    margin: 0 auto;
    align-items: start;
}

.auth-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.auth-header {
    text-align: center;
    margin-bottom: 32px;
}

.auth-header h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    color: var(--text-primary, #1a1a1a);
}

.auth-header p {
    color: var(--text-secondary, #666);
    margin: 0;
}

.auth-form {
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary, #1a1a1a);
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color, #007bff);
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: var(--text-secondary, #666);
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: var(--primary-color, #007bff);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover, #0056b3);
}

.btn-block {
    width: 100%;
}

.auth-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border-color, #eee);
}

.auth-footer a {
    color: var(--primary-color, #007bff);
    text-decoration: none;
    font-weight: 500;
}

.auth-divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: calc(50% - 60px);
    height: 1px;
    background: var(--border-color, #eee);
}

.auth-divider::before {
    left: 0;
}

.auth-divider::after {
    right: 0;
}

.auth-divider span {
    color: var(--text-secondary, #666);
    font-size: 14px;
}

.oauth-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-oauth {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: white;
    border: 1px solid var(--border-color, #ddd);
    color: var(--text-primary, #1a1a1a);
}

.btn-oauth:hover {
    background: var(--bg-hover, #f8f8f8);
}

.oauth-icon {
    width: 20px;
    height: 20px;
}

.alert {
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 24px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.alert-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
}

.alert-success {
    background: #efe;
    border: 1px solid #cfc;
    color: #3c3;
}

.alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.auth-benefits {
    padding: 40px 0;
}

.auth-benefits h3 {
    font-size: 24px;
    margin: 0 0 24px 0;
    color: var(--text-primary, #1a1a1a);
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefits-list li {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    padding: 20px;
    background: var(--card-bg, #f8f9fa);
    border-radius: 8px;
}

.benefit-icon {
    width: 24px;
    height: 24px;
    color: var(--primary-color, #007bff);
    flex-shrink: 0;
}

.benefits-list li strong {
    display: block;
    margin-bottom: 4px;
    color: var(--text-primary, #1a1a1a);
}

.benefits-list li p {
    margin: 0;
    color: var(--text-secondary, #666);
    font-size: 14px;
}

@media (max-width: 968px) {
    .auth-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
