<?php
/**
 * User Login Page
 *
 * @package ControlDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';

$auth = getAuth();

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$requires_2fa = false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please reload the page and try again.';
    } elseif (isset($_POST['code'])) {
        // 2FA verification
        $result = $auth->verify2FA($_POST['code']);

        if ($result['success']) {
            header('Location: /dashboard');
            exit;
        } else {
            $error = $result['error'];
            $requires_2fa = true;
        }
    } else {
        // Regular login
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password';
        } else {
            // Validate and sanitize email
            $email_check = validateEmail($email, ['check_mx' => false]);
            if (!$email_check['valid']) {
                $error = $email_check['error'];
            } else {
                $result = $auth->login($email_check['sanitized'], $password, $remember);

                if ($result['success']) {
                    header('Location: /dashboard');
                    exit;
                } elseif (isset($result['requires_2fa']) && $result['requires_2fa']) {
                    $requires_2fa = true;
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
}

$page_title = 'Login - Controllo Domini';
$page_description = 'Log in to your account to access your domain analyses';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><?php echo $requires_2fa ? 'Two-Factor Authentication' : 'Welcome Back'; ?></h1>
            <p><?php echo $requires_2fa ? 'Enter your 6-digit code' : 'Log in to your account'; ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($requires_2fa): ?>
            <!-- 2FA Form -->
            <form method="POST" class="auth-form" id="twoFactorForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label for="code">Authentication Code</label>
                    <input type="text" id="code" name="code" required
                           pattern="[0-9]{6}" maxlength="6"
                           placeholder="000000" autocomplete="one-time-code"
                           autofocus style="text-align: center; font-size: 24px; letter-spacing: 0.5em;"
                           aria-label="Six digit authentication code"
                           aria-required="true"
                           aria-describedby="code_hint">
                    <small class="form-hint" id="code_hint">
                        Enter the 6-digit code from your authenticator app
                    </small>
                    <div class="field-error" id="code_error" role="alert" aria-live="polite"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="verify2FABtn">
                    <span class="btn-text">Verify Code</span>
                    <span class="btn-spinner" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        </svg>
                        Verifying...
                    </span>
                </button>

                <div class="text-center mt-3">
                    <a href="/login" class="text-link">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Login Form -->
            <form method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="john@example.com" autofocus
                           maxlength="254"
                           aria-label="Email address"
                           aria-required="true">
                    <div class="field-error" id="email_error" role="alert" aria-live="polite"></div>
                </div>

                <div class="form-group">
                    <label for="password">
                        Password
                        <a href="/forgot-password" class="float-right text-link">Forgot password?</a>
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required
                               placeholder="Enter your password"
                               aria-label="Password"
                               aria-required="true">
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <svg class="eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="field-error" id="password_error" role="alert" aria-live="polite"></div>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="remember"
                               aria-label="Remember me for 30 days">
                        Remember me for 30 days
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    <span class="btn-text">Log In</span>
                    <span class="btn-spinner" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        </svg>
                        Logging in...
                    </span>
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/register">Sign Up</a></p>
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

    <div class="auth-info">
        <h2>Analyze Domains Like a Pro</h2>
        <div class="info-stats">
            <div class="stat-card">
                <div class="stat-number">1M+</div>
                <div class="stat-label">Domains Analyzed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">🔍</div>
                <h3>Complete Analysis</h3>
                <p>DNS, WHOIS, SSL, Security Headers, and more in one place</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">⚡</div>
                <h3>Lightning Fast</h3>
                <p>Cached results and optimized queries for instant responses</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🔒</div>
                <h3>Secure & Private</h3>
                <p>Your data is encrypted and never shared with third parties</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📊</div>
                <h3>Advanced Reporting</h3>
                <p>Export detailed reports in PDF, CSV, and JSON formats</p>
            </div>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    padding: 40px 20px;
    display: grid;
    grid-template-columns: 500px 1fr;
    gap: 80px;
    max-width: 1400px;
    margin: 0 auto;
    align-items: center;
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

.float-right {
    float: right;
}

.text-link {
    color: var(--primary-color, #007bff);
    text-decoration: none;
    font-weight: normal;
    font-size: 14px;
}

.text-link:hover {
    text-decoration: underline;
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
    box-sizing: border-box;
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

.text-center {
    text-align: center;
}

.mt-3 {
    margin-top: 16px;
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
    padding: 12px;
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

.alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.auth-info {
    padding: 40px 0;
}

.auth-info h2 {
    font-size: 42px;
    margin: 0 0 40px 0;
    color: var(--text-primary, #1a1a1a);
    line-height: 1.2;
}

.info-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 60px;
}

.stat-card {
    background: var(--card-bg, #f8f9fa);
    padding: 24px;
    border-radius: 12px;
    text-align: center;
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary-color, #007bff);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary, #666);
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}

.feature-item {
    text-align: left;
}

.feature-icon {
    font-size: 40px;
    margin-bottom: 16px;
}

.feature-item h3 {
    font-size: 20px;
    margin: 0 0 8px 0;
    color: var(--text-primary, #1a1a1a);
}

.feature-item p {
    margin: 0;
    color: var(--text-secondary, #666);
    font-size: 14px;
    line-height: 1.6;
}

@media (max-width: 1200px) {
    .auth-container {
        grid-template-columns: 1fr;
        gap: 60px;
    }

    .auth-card {
        max-width: 500px;
        margin: 0 auto;
    }

    .auth-info {
        max-width: 800px;
        margin: 0 auto;
    }
}

/* Enhanced form styles */
.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--text-secondary, #666);
}

.toggle-password:hover {
    color: var(--primary-color, #007bff);
}

.eye-icon {
    width: 20px;
    height: 20px;
}

.field-error {
    color: #dc3545;
    font-size: 13px;
    margin-top: 6px;
    display: none;
}

.field-error.show {
    display: block;
}

.form-group input.error {
    border-color: #dc3545;
}

.btn-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.spinner {
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
}

.spinner-circle {
    stroke-dasharray: 60;
    stroke-dashoffset: 60;
    animation: spinCircle 1.5s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes spinCircle {
    0% { stroke-dashoffset: 60; }
    50% { stroke-dashoffset: 15; }
    100% { stroke-dashoffset: 60; }
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .info-stats {
        grid-template-columns: 1fr;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .auth-info h2 {
        font-size: 32px;
    }
}
</style>

<script>
// Enhanced login form with validation and loading states
(function() {
    'use strict';

    // Login form handling
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorDiv = document.getElementById(inputId + '_error');
            if (input && errorDiv) {
                input.classList.add('error');
                errorDiv.textContent = message;
                errorDiv.classList.add('show');
            }
        }

        function clearError(inputId) {
            const input = document.getElementById(inputId);
            const errorDiv = document.getElementById(inputId + '_error');
            if (input && errorDiv) {
                input.classList.remove('error');
                errorDiv.classList.remove('show');
            }
        }

        // Real-time validation
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                if (this.value && !validateEmail(this.value)) {
                    showError('email', 'Please enter a valid email address');
                } else if (this.value) {
                    clearError('email');
                }
            });
        }

        // Password visibility toggle
        const toggleBtn = loginForm.querySelector('.toggle-password');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
            });
        }

        // Form submission
        loginForm.addEventListener('submit', function(e) {
            let hasErrors = false;

            if (!emailInput.value.trim() || !validateEmail(emailInput.value)) {
                showError('email', 'Please enter a valid email address');
                hasErrors = true;
            }

            if (!passwordInput.value) {
                showError('password', 'Please enter your password');
                hasErrors = true;
            }

            if (hasErrors) {
                e.preventDefault();
                return false;
            }

            // Show loading state
            loginBtn.disabled = true;
            loginBtn.querySelector('.btn-text').style.display = 'none';
            loginBtn.querySelector('.btn-spinner').style.display = 'flex';
        });
    }

    // 2FA form handling
    const twoFactorForm = document.getElementById('twoFactorForm');
    if (twoFactorForm) {
        const codeInput = document.getElementById('code');
        const verifyBtn = document.getElementById('verify2FABtn');

        // Auto-submit when 6 digits entered
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');

                // Auto-submit when 6 digits
                if (this.value.length === 6) {
                    twoFactorForm.submit();
                }
            });
        }

        // Form submission
        twoFactorForm.addEventListener('submit', function(e) {
            if (codeInput.value.length !== 6) {
                e.preventDefault();
                const errorDiv = document.getElementById('code_error');
                if (errorDiv) {
                    errorDiv.textContent = 'Please enter a 6-digit code';
                    errorDiv.classList.add('show');
                }
                return false;
            }

            // Show loading state
            verifyBtn.disabled = true;
            verifyBtn.querySelector('.btn-text').style.display = 'none';
            verifyBtn.querySelector('.btn-spinner').style.display = 'flex';
        });
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
