# SECURITY FIXES - Checklist di Implementazione

## Status: TODO - Implementare entro 72 ore

---

## PHASE 1: CRITICO (72 ore)

### 1.1 Implementare CSRF Protection
**Severity:** CRITICO
**Time:** 2-3 ore
**Files to modify:**
- [ ] `/includes/utilities.php` - Verificare funzioni generateCSRFToken() e verifyCSRFToken()
- [ ] `/index.php` - Aggiungere token CSRF al form POST
- [ ] `/login.php` - Aggiungere token CSRF al form POST
- [ ] `/register.php` - Aggiungere token CSRF al form POST
- [ ] Tutti gli altri file con form POST

**Implementation:**
```php
// In ogni form POST HTML
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// In ogni handler POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        die(json_encode(['error' => 'CSRF token validation failed']));
    }
}
```

**Verification:**
```bash
# Test senza token (dovrebbe fallire)
curl -X POST http://localhost/index.php -d "domain=example.com"
# Risultato atteso: 403 Forbidden
```

---

### 1.2 Fixare CORS Permission
**Severity:** CRITICO
**Time:** 30 minuti
**File:** `/api/v2/index.php` linea 13

**Change from:**
```php
header('Access-Control-Allow-Origin: *');
```

**Change to:**
```php
$allowed_origins = [
    'https://controllodomini.it',
    'https://www.controllodomini.it',
    'https://api.controllodomini.it'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    http_response_code(403);
    exit('Access denied');
}
```

**Verification:**
```bash
# Test con dominio non autorizzato
curl -H "Origin: http://evil.com" http://localhost/api/v2/index.php
# Dovrebbe non ritornare Access-Control-Allow-Origin
```

---

### 1.3 Rimuovere API Key da URL
**Severity:** CRITICO
**Time:** 1 ora
**File:** `/api/v2/index.php` linea 80

**Change from:**
```php
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
```

**Change to:**
```php
// SOLO da header Authorization
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
    $api_key = $matches[1];
} else {
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? null;
}

if (!$api_key) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required in Authorization header']);
    exit;
}
```

**Verification:**
```bash
# Test corretto con header
curl -H "Authorization: Bearer API_KEY_HERE" http://localhost/api/v2/index.php

# Test scorretto con URL (dovrebbe fallire)
curl "http://localhost/api/v2/index.php?api_key=API_KEY_HERE"
# Risultato atteso: 401 Unauthorized
```

---

### 1.4 Implementare CSP Header
**Severity:** CRITICO (XSS mitigation)
**Time:** 1 ora
**File:** `/config/config.php` (dopo linea 78)

**Add:**
```php
// Content Security Policy - Prevenire XSS
header("Content-Security-Policy: default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com; " .
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
       "font-src 'self' https://fonts.gstatic.com; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self' https:; " .
       "frame-ancestors 'self'; " .
       "base-uri 'self'; " .
       "form-action 'self'");
```

**Verification:**
```bash
curl -I http://localhost/ | grep -i content-security-policy
# Dovrebbe ritornare il CSP header
```

---

## PHASE 2: IMPORTANTE (1 settimana)

### 2.1 Implementare HSTS Header
**Severity:** MEDIO
**Time:** 30 minuti
**File:** `/config/config.php` (dopo CSP header)

**Add:**
```php
// HTTPS only - Strict-Transport-Security
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
```

### 2.2 Disabilitare DEBUG_MODE da GET/POST
**Severity:** MEDIO-ALTO
**Time:** 30 minuti
**File:** `/config/config.php` linea 169

**Change from:**
```php
define('DEBUG_MODE', isset($_GET['debug']) || isset($_POST['debug']));
```

**Change to:**
```php
// DEBUG_MODE dovrebbe essere configurato solo via environment variable
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true');
```

### 2.3 Aggiungere Input Length Validation
**Severity:** MEDIO
**Time:** 1-2 ore
**Files:**
- [ ] `/login.php`
- [ ] `/register.php`
- [ ] Tutti gli altri form

**Example:**
```php
// login.php
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validazione lunghezza
if (strlen($email) > 255 || strlen($email) < 5) {
    die(json_encode(['error' => 'Invalid email length']));
}

if (strlen($password) > 256 || strlen($password) < 8) {
    die(json_encode(['error' => 'Invalid password length']));
}
```

### 2.4 Cambiare SameSite a Strict
**Severity:** MEDIO
**Time:** 15 minuti
**File:** `/includes/auth.php` linea 34

**Change from:**
```php
ini_set('session.cookie_samesite', 'Lax');
```

**Change to:**
```php
ini_set('session.cookie_samesite', 'Strict');
```

---

## PHASE 3: MEDIUM (1 mese)

### 3.1 Aggiungere Rate Limiting su Login
**Severity:** BASSO
**Time:** 2-3 ore
**File:** `/includes/auth.php` método login()

**Implementation:**
```php
// Verificare rate limit prima del login
private function checkLoginRateLimit($email, $ip) {
    $cache_key = "login_attempts:{$email}:{$ip}";
    $attempts = getCache()->get($cache_key) ?? 0;
    
    if ($attempts >= 5) {
        // Delay progressivo
        sleep(2 * $attempts);
        return false;
    }
    
    getCache()->set($cache_key, $attempts + 1, 900); // 15 min
    return true;
}
```

### 3.2 Rimuovere Token dai Log
**Severity:** BASSO
**Time:** 30 minuti
**File:** `/includes/auth.php` linee 635-645

**Change from:**
```php
error_log("Verification link for $email: $link");
```

**Change to:**
```php
error_log("Email verification sent for user: " . substr($email, 0, 3) . "***");
```

### 3.3 Aggiungere Backup Codes 2FA
**Severity:** BASSO
**Time:** 3-4 ore
**File:** `/includes/auth.php`

---

## Testing Commands

### Test CSRF
```bash
# WITHOUT token (should fail)
curl -X POST http://localhost/index.php \
  -d "domain=example.com" \
  -H "Content-Type: application/x-www-form-urlencoded"

# Expected: 403 Forbidden
```

### Test CORS
```bash
curl -H "Origin: http://evil.com" \
  -H "Access-Control-Request-Method: POST" \
  http://localhost/api/v2/index.php -v

# Should NOT return Access-Control-Allow-Origin header
```

### Test API Key
```bash
# Wrong - in URL (vulnerable)
curl "http://localhost/api/v2/index.php?api_key=test"

# Correct - in header
curl -H "Authorization: Bearer test_api_key" \
  http://localhost/api/v2/index.php
```

### Test Headers
```bash
# Check CSP
curl -I http://localhost/ | grep -i content-security-policy

# Check HSTS
curl -I http://localhost/ | grep -i strict-transport-security

# Check X-Frame-Options
curl -I http://localhost/ | grep -i x-frame-options
```

---

## Progress Tracking

### Phase 1 (CRITICO)
- [ ] CSRF Implementation
- [ ] CORS Fix
- [ ] API Key Migration
- [ ] CSP Header
- **Deadline:** 72 hours
- **Responsible:** Security Team

### Phase 2 (IMPORTANTE)
- [ ] HSTS Header
- [ ] DEBUG_MODE Fix
- [ ] Input Validation
- [ ] SameSite Update
- **Deadline:** 7 days
- **Responsible:** Backend Team

### Phase 3 (MEDIUM)
- [ ] Rate Limiting
- [ ] Log Cleanup
- [ ] Backup Codes
- **Deadline:** 30 days
- **Responsible:** Security Team

---

## Rollback Plan

If issues arise after implementation:

1. **CSRF Errors in Forms:**
   ```php
   // Temporarily allow both with and without token
   if ($_POST['csrf_token'] && !verifyCSRFToken($_POST['csrf_token'])) {
       // Log suspicious activity
       error_log('CSRF validation failure from: ' . $_SERVER['REMOTE_ADDR']);
   }
   ```

2. **CORS Errors:**
   ```php
   // Add temporary logging
   error_log('CORS blocked from: ' . $_SERVER['HTTP_ORIGIN']);
   // List approved origins for verification
   ```

3. **API Key Errors:**
   ```php
   // Support both during transition
   if (!$api_key) {
       $api_key = $_GET['api_key'] ?? null;
       error_log('API Key from URL - should be header only');
   }
   ```

---

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP CSP](https://owasp.org/www-project-secure-coding-practices-quick-reference-guide/)
- [MDN Security Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers)

---

Last Updated: 2025-11-12
Report by: Security Analysis Team
