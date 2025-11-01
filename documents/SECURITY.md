# Guida Sicurezza - Controllo Domini

## Indice

1. [Panoramica Sicurezza](#panoramica-sicurezza)
2. [Threat Model](#threat-model)
3. [Input Validation](#input-validation)
4. [Output Encoding](#output-encoding)
5. [HTTP Security Headers](#http-security-headers)
6. [Rate Limiting](#rate-limiting)
7. [HTTPS e TLS](#https-e-tls)
8. [File Security](#file-security)
9. [Dependency Security](#dependency-security)
10. [Logging e Monitoring](#logging-e-monitoring)
11. [Incident Response](#incident-response)
12. [Security Checklist](#security-checklist)

## Panoramica Sicurezza

### Filosofia di Sicurezza

Controllo Domini è progettato con **Security by Design**:

1. **Defense in Depth**: Multiple layer di sicurezza
2. **Least Privilege**: Permessi minimi necessari
3. **Fail Secure**: Errori non espongono dati sensibili
4. **Security by Default**: Configurazione secure out-of-the-box

### Security Posture

```
┌─────────────────────────────────────┐
│     SECURITY LAYERS                 │
├─────────────────────────────────────┤
│ 1. Network (Firewall, DDoS)        │
│ 2. Transport (HTTPS/TLS 1.3)       │
│ 3. Application (Headers, CSP)      │
│ 4. Input Validation                 │
│ 5. Output Encoding                  │
│ 6. Rate Limiting                    │
│ 7. Logging & Monitoring             │
└─────────────────────────────────────┘
```

## Threat Model

### Minacce Principali

#### 1. Injection Attacks

**Cross-Site Scripting (XSS)**:
- **Rischio**: Alto
- **Vettore**: Input domain non sanitizzato
- **Mitigazione**: Output encoding, CSP headers

**Code Injection**:
- **Rischio**: Basso (no database)
- **Vettore**: shell_exec in WHOIS lookup
- **Mitigazione**: Comando whitelistato, input validation strict

#### 2. Denial of Service (DoS)

**Application DoS**:
- **Rischio**: Medio
- **Vettore**: Query massive DNS/WHOIS
- **Mitigazione**: Rate limiting, timeout

**DNS Amplification**:
- **Rischio**: Basso
- **Vettore**: Uso come reflector DNS
- **Mitigazione**: Non è resolver aperto

#### 3. Information Disclosure

**Error Messages**:
- **Rischio**: Basso
- **Vettore**: display_errors = On in produzione
- **Mitigazione**: Error logging, custom error pages

**Path Disclosure**:
- **Rischio**: Basso
- **Vettore**: Stack trace in errori
- **Mitigazione**: Error handling appropriato

#### 4. Clickjacking

**Rischio**: Basso
**Vettore**: Iframe embedding
**Mitigazione**: X-Frame-Options: SAMEORIGIN

#### 5. CSRF (Cross-Site Request Forgery)

**Rischio**: Molto Basso (read-only app)
**Vettore**: Form submission da siti terzi
**Mitigazione**: Non necessario (no state-changing operations)

### Attack Surface

```
Public Interfaces:
├── Web Forms (domain input)         [HIGH RISK]
├── HTTP Endpoints                   [MEDIUM RISK]
├── Error Messages                   [LOW RISK]
└── Static Assets                    [VERY LOW RISK]

External Dependencies:
├── DNS Servers                      [TRUSTED]
├── WHOIS Servers                    [SEMI-TRUSTED]
├── DNSBL Servers                    [SEMI-TRUSTED]
└── Target Websites (HTTP)           [UNTRUSTED]
```

## Input Validation

### Domain Validation

**File**: `includes/utilities.php`

```php
function validateDomain($domain) {
    // 1. Remove whitespace
    $domain = trim($domain);

    // 2. Convert to lowercase
    $domain = strtolower($domain);

    // 3. Remove protocol if present
    $domain = preg_replace('#^https?://#', '', $domain);

    // 4. Remove path if present
    $domain = preg_replace('#/.*$#', '', $domain);

    // 5. Check length
    if (strlen($domain) > 253) {
        return false; // Max domain length
    }

    // 6. Check format
    if (!preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)) {
        return false;
    }

    // 7. Check each label
    $labels = explode('.', $domain);
    foreach ($labels as $label) {
        if (strlen($label) > 63) {
            return false; // Max label length
        }
        if (strpos($label, '--') === 0) {
            return false; // Can't start with --
        }
    }

    // 8. Additional checks
    if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
        return false;
    }

    return $domain; // Return sanitized domain
}
```

### IP Validation

```php
function validateIP($ip) {
    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ip;
    }

    // IPv6
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $ip;
    }

    return false;
}
```

### URL Validation

```php
function validateUrl($url) {
    // Basic validation
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Parse URL
    $parsed = parse_url($url);

    // Check scheme
    if (!in_array($parsed['scheme'], ['http', 'https'])) {
        return false;
    }

    // Validate host
    if (!validateDomain($parsed['host']) && !validateIP($parsed['host'])) {
        return false;
    }

    return $url;
}
```

### Command Injection Prevention

**WHOIS shell_exec**:

```php
function getWhoisViaShellExec($domain) {
    // 1. Validate domain first
    $domain = validateDomain($domain);
    if (!$domain) {
        return false;
    }

    // 2. Escape shell argument
    $domain = escapeshellarg($domain);

    // 3. Whitelist command
    $whoisCmd = '/usr/bin/whois'; // Full path

    // 4. Check command exists
    if (!file_exists($whoisCmd)) {
        return false;
    }

    // 5. Execute with timeout
    $cmd = $whoisCmd . ' ' . $domain . ' 2>&1';
    $output = shell_exec($cmd);

    return $output;
}
```

**Key Protections**:
- ✅ Input validation before exec
- ✅ escapeshellarg() usage
- ✅ Full path to command (no PATH injection)
- ✅ Command whitelisting
- ✅ Timeout enforcement

## Output Encoding

### HTML Encoding

```php
// Safe HTML output wrapper
function safeHtmlspecialchars($value) {
    // Handle arrays
    if (is_array($value)) {
        return implode(', ', array_map('safeHtmlspecialchars', $value));
    }

    // Handle non-strings
    if (!is_string($value)) {
        $value = (string)$value;
    }

    // Encode
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
```

**Uso**:

```php
// ❌ UNSAFE
<div><?php echo $domain; ?></div>

// ✅ SAFE
<div><?php echo safeHtmlspecialchars($domain); ?></div>
```

### JSON Encoding

```php
// Safe JSON output
function safeJsonEncode($data) {
    return json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
}
```

### URL Encoding

```php
// URL parameters
$param = urlencode($userInput);

// URL paths
$path = rawurlencode($userInput);
```

## HTTP Security Headers

### Implementazione

**Via Apache** (`.htaccess`):

```apache
# Security Headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# HSTS (HTTPS only)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS

# CSP
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'"
```

**Via PHP** (`templates/header.php`):

```php
<?php
// Send security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// HSTS (solo HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}
?>
```

### Content Security Policy (CSP)

**Configurazione Strict**:

```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' https://unpkg.com https://www.googletagmanager.com;
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com;
  img-src 'self' data: https:;
  connect-src 'self';
  frame-ancestors 'self';
  base-uri 'self';
  form-action 'self';
```

**Note**: `'unsafe-inline'` in `style-src` è necessario per AOS library. Considera migrazione a inline styles per rimuoverlo.

## Rate Limiting

### Implementazione

**File**: `includes/utilities.php`

```php
function checkRateLimit($ip) {
    // Check if enabled
    if (!defined('RATE_LIMIT_ENABLED') || !RATE_LIMIT_ENABLED) {
        return true;
    }

    // Whitelist check
    if (in_array($ip, $GLOBALS['rateLimitWhitelist'] ?? [])) {
        return true;
    }

    // Rate limit file path
    $rateLimitFile = __DIR__ . '/../cache/rate-limit/' . md5($ip) . '.json';
    $rateLimitDir = dirname($rateLimitFile);

    // Create directory if not exists
    if (!is_dir($rateLimitDir)) {
        mkdir($rateLimitDir, 0755, true);
    }

    // Load existing rate limit data
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
    } else {
        $data = ['count' => 0, 'reset' => time() + RATE_LIMIT_PERIOD];
    }

    // Reset if period expired
    if (time() > $data['reset']) {
        $data = ['count' => 0, 'reset' => time() + RATE_LIMIT_PERIOD];
    }

    // Increment count
    $data['count']++;

    // Check limit
    if ($data['count'] > RATE_LIMIT_REQUESTS) {
        // Rate limit exceeded
        http_response_code(429);
        header('Retry-After: ' . ($data['reset'] - time()));
        die(json_encode([
            'error' => 'Rate limit exceeded',
            'limit' => RATE_LIMIT_REQUESTS,
            'reset' => $data['reset']
        ]));
    }

    // Save updated data
    file_put_contents($rateLimitFile, json_encode($data));

    return true;
}

// Usage in index.php
$visitorIP = getVisitorIP();
checkRateLimit($visitorIP);
```

### Configurazione

```php
// config/config.php
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100);    // Richieste
define('RATE_LIMIT_PERIOD', 3600);     // 1 ora

$rateLimitWhitelist = [
    '127.0.0.1',
    '::1',
    // Aggiungi IP fidati
];
```

### Alternative: Nginx Rate Limiting

```nginx
# nginx.conf
http {
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/h;

    server {
        location / {
            limit_req zone=api burst=10 nodelay;
            # ...
        }
    }
}
```

## HTTPS e TLS

### Configurazione SSL/TLS

**Apache Virtual Host**:

```apache
<VirtualHost *:443>
    ServerName controllodomini.it

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/controllodomini.it/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/controllodomini.it/privkey.pem

    # Modern SSL configuration
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    SSLSessionTickets off

    # OCSP Stapling
    SSLUseStapling on
    SSLStaplingCache shmcb:/var/run/ocsp(128000)

    # ...
</VirtualHost>
```

### Redirect HTTP → HTTPS

```apache
<VirtualHost *:80>
    ServerName controllodomini.it
    ServerAlias www.controllodomini.it

    # Redirect all to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

### Certificate Renewal

```bash
# Auto-renewal via certbot
sudo certbot renew --dry-run

# Cron job (già configurato da certbot)
0 */12 * * * certbot renew --quiet
```

## File Security

### Permessi File

```bash
# Directory: 755 (rwxr-xr-x)
find /var/www/controllo-domini -type d -exec chmod 755 {} \;

# File: 644 (rw-r--r--)
find /var/www/controllo-domini -type f -exec chmod 644 {} \;

# Config sensibili: 640 (rw-r-----)
chmod 640 /var/www/controllo-domini/config/config.php
chmod 640 /var/www/controllo-domini/.htaccess

# Ownership
chown -R www-data:www-data /var/www/controllo-domini
```

### Protezione Directory

**.htaccess in `/config/`**:

```apache
# Deny all access
Require all denied
```

**.htaccess in `/includes/`**:

```apache
# Deny direct access to PHP files
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
```

**.htaccess principale**:

```apache
# Protect sensitive files
<FilesMatch "^(\.env|\.git|composer\.json|composer\.lock|package\.json)">
    Require all denied
</FilesMatch>

# Block directory listing
Options -Indexes

# Block access to backup files
<FilesMatch "(\.(bak|config|dist|fla|inc|ini|log|psd|sh|sql|swp)|~)$">
    Require all denied
</FilesMatch>
```

### robots.txt Protection

```
# Protect sensitive paths
User-agent: *
Disallow: /config/
Disallow: /includes/
Disallow: /templates/
Disallow: /cache/
Disallow: /.git/
```

## Dependency Security

### External Dependencies

**JavaScript**:
```html
<!-- AOS - Animate On Scroll -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
```

**Subresource Integrity (SRI)**:

```html
<!-- Con SRI hash -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"
        integrity="sha384-..."
        crossorigin="anonymous"></script>
```

**Generare SRI hash**:

```bash
curl https://unpkg.com/aos@2.3.1/dist/aos.js | \
  openssl dgst -sha384 -binary | \
  openssl base64 -A
```

### PHP Extensions Security

```php
// Verifica estensioni caricate
$requiredExtensions = ['json', 'curl', 'mbstring', 'openssl'];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Security: Required extension '$ext' not loaded");
    }
}

// Verifica versioni
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("Security: PHP 7.4+ required");
}
```

## Logging e Monitoring

### Error Logging

**php.ini / .htaccess**:

```ini
# Production settings
display_errors = Off
log_errors = On
error_log = /var/log/php/controllo-domini-error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

### Access Logging

**Apache**:

```apache
CustomLog ${APACHE_LOG_DIR}/controllodomini-access.log combined
ErrorLog ${APACHE_LOG_DIR}/controllodomini-error.log
```

### Application Logging

```php
// Custom application logger
function logSecurity($message, $level = 'INFO') {
    $logFile = '/var/log/controllo-domini/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getVisitorIP();
    $entry = "[$timestamp] [$level] [IP:$ip] $message\n";

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// Usage
logSecurity("Rate limit exceeded for IP: $ip", 'WARNING');
logSecurity("Invalid domain input: $domain", 'WARNING');
```

### Monitoring Alerts

**Esempi eventi da monitorare**:
- ✅ Rate limit exceeded (> 100/hour per IP)
- ✅ Invalid input attempts (> 10/hour per IP)
- ✅ Repeated errors (same error > 50/hour)
- ✅ SSL certificate expiring (< 30 days)

## Incident Response

### Incident Response Plan

#### 1. Detection

- Monitor error logs
- Monitor access logs
- Monitor rate limiting triggers
- External monitoring (Uptime Robot, Pingdom)

#### 2. Analysis

```bash
# Check recent access
tail -100 /var/log/apache2/controllodomini-access.log

# Check errors
tail -100 /var/log/apache2/controllodomini-error.log

# Check suspicious IPs
grep "429" /var/log/apache2/controllodomini-access.log | \
  awk '{print $1}' | sort | uniq -c | sort -rn

# Check attack patterns
grep -E "(\.\.\/|union|select|script)" /var/log/apache2/controllodomini-access.log
```

#### 3. Containment

```bash
# Block IP via firewall
sudo ufw deny from 1.2.3.4

# Block IP via Apache
echo "Require not ip 1.2.3.4" >> /etc/apache2/conf-available/block-ips.conf
sudo systemctl reload apache2

# Disable specific feature
# Edit config/config.php
define('PORT_SCAN_ENABLED', false);
```

#### 4. Eradication

- Patch vulnerability
- Update dependencies
- Rotate credentials if compromised

#### 5. Recovery

- Restore from backup if needed
- Re-enable services
- Monitor closely

#### 6. Lessons Learned

- Document incident
- Update security measures
- Update this document

## Security Checklist

### Pre-Deployment Checklist

- [ ] **HTTPS configurato** con TLS 1.2+
- [ ] **Security headers** presenti (HSTS, CSP, X-Frame-Options)
- [ ] **display_errors** = Off in produzione
- [ ] **error_log** configurato
- [ ] **File permissions** corretti (755 dir, 644 file)
- [ ] **Rate limiting** abilitato
- [ ] **.env** non committato in git
- [ ] **robots.txt** protegge directory sensibili
- [ ] **.htaccess** blocca file sensibili
- [ ] **Input validation** su tutti i form
- [ ] **Output encoding** su tutti gli output
- [ ] **Backup** configurato
- [ ] **Monitoring** attivo
- [ ] **Firewall** configurato (UFW, fail2ban)
- [ ] **SSL certificate** auto-renewal attivo

### Regular Security Audit (Mensile)

- [ ] Verifica log per attività sospette
- [ ] Controlla scadenza SSL certificate
- [ ] Verifica dipendenze JavaScript per vulnerabilità
- [ ] Test security headers (securityheaders.com)
- [ ] Test SSL configuration (ssllabs.com)
- [ ] Review rate limiting effectiveness
- [ ] Backup restoration test
- [ ] Update sistema operativo e PHP

### Security Tools

**Header Testing**:
- https://securityheaders.com
- https://observatory.mozilla.org

**SSL Testing**:
- https://www.ssllabs.com/ssltest/
- `testssl.sh` script

**Vulnerability Scanning**:
- OWASP ZAP
- Nikto
- Nmap

**Dependency Checking**:
- Snyk
- npm audit (per JS dependencies)

---

**Ultimo aggiornamento**: Novembre 2025
**Versione guida**: 1.0
**Security Contact**: security@controllodomini.it
