<?php
/**
 * Authentication Class
 *
 * Handles user registration, login, sessions, and authentication
 * Includes support for 2FA, OAuth, password reset, and email verification
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utilities.php';

class Auth {
    private $db;
    private $session_lifetime = 86400; // 24 hours
    private $remember_lifetime = 2592000; // 30 days

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    /**
     * Start secure session
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1); // HTTPS only
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');

            session_name('controllo_domini_session');
            session_start();

            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                // Regenerate every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Register a new user
     *
     * @param string $email User email
     * @param string $password User password
     * @param string $full_name User full name
     * @param string $company Company name (optional)
     * @return array Result with success status and user ID or error
     */
    public function register($email, $password, $full_name, $company = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }

        // Validate password strength
        $password_check = $this->validatePasswordStrength($password);
        if (!$password_check['valid']) {
            return ['success' => false, 'error' => $password_check['error']];
        }

        // Check if email already exists
        $existing = $this->db->queryOne(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Generate email verification token
        $verification_token = bin2hex(random_bytes(32));
        $verification_expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        try {
            // Insert user
            $user_id = $this->db->insert('users', [
                'email' => $email,
                'password_hash' => $password_hash,
                'full_name' => $full_name,
                'company' => $company,
                'plan' => 'free',
                'status' => 'pending_verification',
                'email_verification_token' => $verification_token,
                'email_verification_expires' => $verification_expires
            ]);

            // Log audit
            $this->logAudit($user_id, 'user_registered', 'user', $user_id);

            // Send verification email
            $this->sendVerificationEmail($email, $full_name, $verification_token);

            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'Registration successful. Please check your email to verify your account.'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     *
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Remember me option
     * @return array Result with success status
     */
    public function login($email, $password, $remember = false) {
        // Get user
        $user = $this->db->queryOne(
            'SELECT * FROM users WHERE email = :email',
            [':email' => $email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Check if email is verified
        if ($user['status'] === 'pending_verification') {
            return ['success' => false, 'error' => 'Please verify your email address first'];
        }

        // Check if account is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Account is ' . $user['status']];
        }

        // Check if 2FA is enabled
        if ($user['two_factor_enabled']) {
            // Store user ID in session for 2FA verification
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            return [
                'success' => false,
                'requires_2fa' => true,
                'message' => 'Please enter your 2FA code'
            ];
        }

        // Create session
        $session_result = $this->createSession($user['id'], $remember);

        if ($session_result['success']) {
            // Update last login
            $this->db->update(
                'users',
                [
                    'last_login_at' => date('Y-m-d H:i:s'),
                    'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ],
                'id = :id',
                [':id' => $user['id']]
            );

            // Log audit
            $this->logAudit($user['id'], 'user_login', 'user', $user['id']);

            return [
                'success' => true,
                'user' => $this->getUserData($user['id']),
                'message' => 'Login successful'
            ];
        }

        return $session_result;
    }

    /**
     * Verify 2FA code
     *
     * @param string $code 2FA code
     * @return array Result
     */
    public function verify2FA($code) {
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            return ['success' => false, 'error' => 'No pending 2FA verification'];
        }

        $user_id = $_SESSION['pending_2fa_user_id'];

        // Get user
        $user = $this->db->queryOne(
            'SELECT * FROM users WHERE id = :id',
            [':id' => $user_id]
        );

        if (!$user || !$user['two_factor_enabled']) {
            return ['success' => false, 'error' => 'Invalid 2FA state'];
        }

        // Verify TOTP code (using Google Authenticator compatible)
        $valid = $this->verifyTOTP($user['two_factor_secret'], $code);

        if (!$valid) {
            return ['success' => false, 'error' => 'Invalid 2FA code'];
        }

        // Clear pending 2FA
        unset($_SESSION['pending_2fa_user_id']);

        // Create session
        $session_result = $this->createSession($user_id, false);

        if ($session_result['success']) {
            $this->logAudit($user_id, '2fa_verified', 'user', $user_id);

            return [
                'success' => true,
                'user' => $this->getUserData($user_id),
                'message' => '2FA verification successful'
            ];
        }

        return $session_result;
    }

    /**
     * Create user session
     *
     * @param string $user_id User UUID
     * @param bool $remember Remember me
     * @return array Result
     */
    private function createSession($user_id, $remember = false) {
        try {
            // Generate session token
            $session_token = bin2hex(random_bytes(32));

            // Calculate expiry
            $lifetime = $remember ? $this->remember_lifetime : $this->session_lifetime;
            $expires_at = date('Y-m-d H:i:s', time() + $lifetime);

            // Insert session
            $session_id = $this->db->insert('sessions', [
                'user_id' => $user_id,
                'session_token' => hash('sha256', $session_token),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'expires_at' => $expires_at
            ]);

            // Store in PHP session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['session_token'] = $session_token;
            $_SESSION['session_id'] = $session_id;

            // Set cookie if remember me
            if ($remember) {
                setcookie(
                    'remember_token',
                    $session_token,
                    time() + $lifetime,
                    '/',
                    '',
                    true, // secure
                    true  // httponly
                );
            }

            return ['success' => true, 'session_id' => $session_id];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create session: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        if ($this->isAuthenticated()) {
            $user_id = $_SESSION['user_id'];

            // Delete session from database
            if (isset($_SESSION['session_id'])) {
                $this->db->delete(
                    'sessions',
                    'id = :id',
                    [':id' => $_SESSION['session_id']]
                );
            }

            // Log audit
            $this->logAudit($user_id, 'user_logout', 'user', $user_id);
        }

        // Clear PHP session
        $_SESSION = [];

        // Delete remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }

        // Destroy session
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated() {
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            return $this->validateSession($_SESSION['session_token']);
        }

        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateSession($_COOKIE['remember_token']);
        }

        return false;
    }

    /**
     * Validate session token
     *
     * @param string $token Session token
     * @return bool
     */
    private function validateSession($token) {
        $token_hash = hash('sha256', $token);

        $session = $this->db->queryOne(
            'SELECT * FROM sessions WHERE session_token = :token AND expires_at > NOW()',
            [':token' => $token_hash]
        );

        if ($session) {
            // Update session variables if needed
            $_SESSION['user_id'] = $session['user_id'];
            $_SESSION['session_token'] = $token;
            $_SESSION['session_id'] = $session['id'];

            return true;
        }

        return false;
    }

    /**
     * Get current user
     *
     * @return array|null User data
     */
    public function getCurrentUser() {
        if ($this->isAuthenticated() && isset($_SESSION['user_id'])) {
            return $this->getUserData($_SESSION['user_id']);
        }

        return null;
    }

    /**
     * Get user data
     *
     * @param string $user_id User UUID
     * @return array|null User data
     */
    public function getUserData($user_id) {
        $user = $this->db->queryOne(
            'SELECT id, email, full_name, company, plan, status, email_verified,
                    two_factor_enabled, last_login_at, created_at
             FROM users WHERE id = :id',
            [':id' => $user_id]
        );

        return $user ?: null;
    }

    /**
     * Verify email address
     *
     * @param string $token Verification token
     * @return array Result
     */
    public function verifyEmail($token) {
        $user = $this->db->queryOne(
            'SELECT * FROM users
             WHERE email_verification_token = :token
             AND email_verification_expires > NOW()
             AND status = :status',
            [
                ':token' => $token,
                ':status' => 'pending_verification'
            ]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid or expired verification token'];
        }

        try {
            // Update user
            $this->db->update(
                'users',
                [
                    'status' => 'active',
                    'email_verified' => true,
                    'email_verification_token' => null,
                    'email_verification_expires' => null
                ],
                'id = :id',
                [':id' => $user['id']]
            );

            $this->logAudit($user['id'], 'email_verified', 'user', $user['id']);

            return [
                'success' => true,
                'message' => 'Email verified successfully. You can now log in.'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * Request password reset
     *
     * @param string $email User email
     * @return array Result
     */
    public function requestPasswordReset($email) {
        $user = $this->db->queryOne(
            'SELECT * FROM users WHERE email = :email AND status = :status',
            [':email' => $email, ':status' => 'active']
        );

        if (!$user) {
            // Don't reveal if email exists
            return [
                'success' => true,
                'message' => 'If the email exists, a password reset link has been sent.'
            ];
        }

        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $reset_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        try {
            $this->db->update(
                'users',
                [
                    'password_reset_token' => $reset_token,
                    'password_reset_expires' => $reset_expires
                ],
                'id = :id',
                [':id' => $user['id']]
            );

            // Send reset email
            $this->sendPasswordResetEmail($email, $user['full_name'], $reset_token);

            $this->logAudit($user['id'], 'password_reset_requested', 'user', $user['id']);

            return [
                'success' => true,
                'message' => 'Password reset link has been sent to your email.'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to request password reset'];
        }
    }

    /**
     * Reset password
     *
     * @param string $token Reset token
     * @param string $new_password New password
     * @return array Result
     */
    public function resetPassword($token, $new_password) {
        $user = $this->db->queryOne(
            'SELECT * FROM users
             WHERE password_reset_token = :token
             AND password_reset_expires > NOW()',
            [':token' => $token]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        // Validate password strength
        $password_check = $this->validatePasswordStrength($new_password);
        if (!$password_check['valid']) {
            return ['success' => false, 'error' => $password_check['error']];
        }

        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $this->db->update(
                'users',
                [
                    'password_hash' => $password_hash,
                    'password_reset_token' => null,
                    'password_reset_expires' => null
                ],
                'id = :id',
                [':id' => $user['id']]
            );

            // Invalidate all sessions
            $this->db->delete('sessions', 'user_id = :user_id', [':user_id' => $user['id']]);

            $this->logAudit($user['id'], 'password_reset_completed', 'user', $user['id']);

            return [
                'success' => true,
                'message' => 'Password reset successfully. Please log in with your new password.'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to reset password'];
        }
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @return array Validation result
     */
    private function validatePasswordStrength($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => implode('. ', $errors)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Verify TOTP code (Google Authenticator)
     *
     * @param string $secret 2FA secret
     * @param string $code User provided code
     * @return bool Valid or not
     */
    private function verifyTOTP($secret, $code) {
        // Simple TOTP implementation
        // In production, use a library like OTPHP
        $time = floor(time() / 30);
        $valid_codes = [];

        // Check current time slot and 1 before/after for clock drift
        for ($i = -1; $i <= 1; $i++) {
            $valid_codes[] = $this->generateTOTP($secret, $time + $i);
        }

        return in_array($code, $valid_codes);
    }

    /**
     * Generate TOTP code
     */
    private function generateTOTP($secret, $time) {
        $key = base64_decode($secret);
        $time = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $name, $token) {
        // TODO: Implement email sending with PHPMailer
        // For now, log the verification link
        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/verify-email?token=' . $token;
        error_log("Verification link for $email: $link");
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $name, $token) {
        // TODO: Implement email sending with PHPMailer
        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/reset-password?token=' . $token;
        error_log("Password reset link for $email: $link");
    }

    /**
     * Log audit event
     */
    private function logAudit($user_id, $action, $resource_type, $resource_id, $details = null) {
        try {
            $this->db->insert('audit_logs', [
                'user_id' => $user_id,
                'action' => $action,
                'resource_type' => $resource_type,
                'resource_id' => $resource_id,
                'details' => $details ? json_encode($details) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Fail silently - don't break flow due to audit log failure
            error_log('Failed to log audit: ' . $e->getMessage());
        }
    }

    /**
     * Require authentication (middleware)
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);
            exit;
        }
    }

    /**
     * Check if user has permission
     *
     * @param string $resource Resource name
     * @param string $action Action name
     * @return bool
     */
    public function hasPermission($resource, $action) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Admin and enterprise users have all permissions
        if (in_array($user['plan'], ['enterprise'])) {
            return true;
        }

        // Check specific permissions based on plan
        $permissions = [
            'free' => [
                'analysis' => ['read', 'create'],
                'domain' => ['read'],
                'export' => []
            ],
            'pro' => [
                'analysis' => ['read', 'create', 'delete'],
                'domain' => ['read', 'create', 'update', 'delete'],
                'monitor' => ['read', 'create', 'update', 'delete'],
                'export' => ['create'],
                'api' => ['read', 'create']
            ]
        ];

        $plan = $user['plan'];
        return isset($permissions[$plan][$resource]) &&
               in_array($action, $permissions[$plan][$resource]);
    }
}

/**
 * Helper function to get Auth instance
 */
function getAuth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}
