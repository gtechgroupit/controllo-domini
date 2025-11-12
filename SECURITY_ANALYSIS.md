# REPORT ANALISI SICUREZZA - Controllo Domini v4.2.1

## Data Report: 2025-11-12
## Gravità Complessiva: MEDIA-ALTA

---

## 1. PROTEZIONE CSRF (Cross-Site Request Forgery)

### STATUS: CRITICO

**Problema identificato:**
Le funzioni CSRF esistono in `includes/utilities.php`:
- `generateCSRFToken()` - genera token
- `verifyCSRFToken()` - verifica token

Tuttavia, **NON sono implementate nei form POST**.

**File interessati:**
- `/index.php` - Form principale di ricerca dominio
- `/login.php` - Form di login
- `/register.php` - Form di registrazione
- Tutti i file che usano metodo POST

**Esempio di codice vulnerabile (index.php, linea ~240):**
```html
<form method="POST" action="" id="domainForm" class="domain-form">
    <div class="form-group">
        <input type="text" id="domain" name="domain" required>
    </div>
    <!-- MANCA: <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"> -->
    <button type="submit">Analizza</button>
</form>
```

**Codice nel server (index.php, linea ~91):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    // Non viene verificato il token CSRF
    $domain = trim($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['domain'] : $_GET['domain']);
    // ... resto del codice senza validazione CSRF
}
```

**Raccomandazioni:**
1. Aggiungere token CSRF a tutti i form POST
2. Verificare il token nella logica POST
3. Implementare CSRF middleware

**Codice suggerito:**
```php
// Nel form HTML
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <!-- resto del form -->
</form>

// Nel server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    // Prosegui con l'elaborazione
}
```

---

## 2. VALIDAZIONE INPUT INCOMPLETA

### STATUS: MEDIO-ALTO

**Problemi identificati:**

**A) Parametro GET "debug" controllabile:**
File: `config/config.php`, linea 169
```php
define('DEBUG_MODE', isset($_GET['debug']) || isset($_POST['debug']));
```

Chiunque può abilitare debug mode aggiungendo `?debug=1` all'URL.

**B) Accesso diretto a $_GET senza validazione:**
File: `index.php`, linea 92
```php
(isset($_GET['domain']) && isset($_GET['analyze']))) {
    $domain = trim($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['domain'] : $_GET['domain']);
```

Anche se `validateDomain()` viene chiamato, il parametro GET viene direttamente da input non affidabile.

**C) Missing input length validation:**
I campi email/password non hanno limiti di lunghezza lato server:
```php
// login.php, linea 38
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
// Nessun controllo di lunghezza massima
```

**Raccomandazioni:**
1. Controllare la lunghezza degli input
2. Non permettere GET per l'accesso a debug mode
3. Validare tutti gli input anche se viene chiamata una funzione di validazione

**Codice suggerito:**
```php
// Disabilitare debug mode da GET/POST
define('DEBUG_MODE', false); // Solo da .env o config server

// Validare lunghezza email
$email = $_POST['email'] ?? '';
if (strlen($email) > 255 || strlen($email) < 5) {
    die('Invalid email format');
}

// Validare password
$password = $_POST['password'] ?? '';
if (strlen($password) > 256 || strlen($password) < 1) {
    die('Invalid password');
}
```

---

## 3. HEADER DI SICUREZZA HTTP INCOMPLETI

### STATUS: MEDIO

**Headers attualmente impostati (config/config.php, linee 74-78):**
```php
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');           // OK
header('X-Frame-Options: SAMEORIGIN');               // OK
header('X-XSS-Protection: 1; mode=block');           // OK ma DEPRECATO
header('Referrer-Policy: strict-origin-when-cross-origin'); // OK
```

**Headers MANCANTI e critici:**

**A) Content-Security-Policy (CSP)** - MANCANTE
- Essenziale per prevenire XSS
- Attualmente il sito è vulnerabile a injection di script

**B) Strict-Transport-Security (HSTS)** - MANCANTE
- Previene downgrade a HTTP
- Essenziale per HTTPS-only

**C) X-Permitted-Cross-Domain-Policies** - MANCANTE
- Protegge da flash/PDF exploits

**D) X-Content-Type-Options** - PRESENTE ma potrebbe essere potenziato

**Raccomandazioni:**
Aggiungere a `config/config.php`:

```php
// HTTPS only
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Content Security Policy - CRITICO
header("Content-Security-Policy: default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com; " .
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
       "font-src 'self' https://fonts.gstatic.com; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self' https:; " .
       "frame-ancestors 'self'; " .
       "base-uri 'self'; " .
       "form-action 'self'");

// Cross-domain policies
header('X-Permitted-Cross-Domain-Policies: none');

// Permission Policy (formerly Feature Policy)
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
```

---

## 4. SESSION MANAGEMENT

### STATUS: MEDIO

**Implementazione buona ma con miglioramenti possibili:**

**Aspetti POSITIVI (auth.php):**
✓ Session regeneration ogni 30 minuti (linea 42-45)
✓ HttpOnly cookie (linea 31)
✓ Secure flag per HTTPS (linea 32)
✓ Use only cookies (linea 33)

**Problemi identificati:**

**A) SameSite è 'Lax' invece che 'Strict':**
File: `includes/auth.php`, linea 34
```php
ini_set('session.cookie_samesite', 'Lax');  // DOVREBBE ESSERE 'Strict'
```

'Lax' permette ancora invii in GET. 'Strict' è più sicuro per form CSRF.

**B) Password reset senza protezione aggiuntiva:**
File: `includes/auth.php`, linea 453-495
```php
public function requestPasswordReset($email) {
    // Il token viene mandato per email, ma se compromesso...
    // Non c'è rate limiting sul reset
}
```

**C) No rate limiting su login attempts:**
`login.php` non ha protezione contro brute force.

**Raccomandazioni:**
```php
// Cambiare SameSite a Strict
ini_set('session.cookie_samesite', 'Strict');

// Aggiungere rate limiting su login
function checkLoginRateLimit($email, $ip) {
    $attempts = getRedis()->get("login_attempts:{$email}:{$ip}");
    if ($attempts >= 5) {
        sleep(2); // Delay exponenziale
        return false;
    }
    return true;
}

// Aggiungere rate limiting su password reset
function checkPasswordResetRateLimit($email) {
    $count = getRedis()->get("password_reset:{$email}");
    if ($count >= 3) { // Max 3 reset per ora
        return false;
    }
    return true;
}
```

---

## 5. SICUREZZA API

### STATUS: CRITICO

**File: `/api/v2/index.php`**

**Problema A: CORS TROPPO PERMISSIVO (Linea 13)**
```php
header('Access-Control-Allow-Origin: *');  // MOLTO PERICOLOSO
```

Permette a qualsiasi dominio di fare richieste all'API.

**Problema B: API Key in URL (GET Parameter)**
File: `/api/v2/index.php`, linea 80
```php
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
```

Le API key in GET vengono loggati nei server logs, proxy, CDN, browser history.

**Problema C: Mancanza di rate limiting reale**
Metodo `checkRateLimit()` non è implementato visibilmente.

**Raccomandazioni:**

```php
// CORS - Whitelist specifico
header('Access-Control-Allow-Origin: https://controllodomini.it');

// API Key SOLO da header
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!$api_key) {
    http_response_code(401);
    die(json_encode(['error' => 'API key required']));
}

// Rate limiting
function checkAPIRateLimit($api_key, $limit = 100, $window = 3600) {
    $key = "api_rate_limit:{$api_key}";
    $count = $redis->incr($key);
    if ($count === 1) {
        $redis->expire($key, $window);
    }
    if ($count > $limit) {
        http_response_code(429);
        die(json_encode(['error' => 'Rate limit exceeded']));
    }
}
```

---

## 6. VULNERABILITA' XSS (Cross-Site Scripting)

### STATUS: MEDIO-BASSO (Mitigato ma non completo)

**Aspetti POSITIVI:**
✓ `htmlspecialchars()` usato in output
✓ `sanitizeOutput()` funzione presente
✓ No eval() o funzioni pericolose

**Problema: XSS in attributi HTML**

Esempio da `login.php`, linea 108:
```php
<input type="email" id="email" name="email" required
       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
```

Se un attaccante inviasse: `"></input><script>alert('XSS')</script>`
Potrebbe non essere completamente sanitizzato in certi contesti.

**Raccomandazioni:**

```php
// Usare ENT_QUOTES | ENT_HTML5 per attributi
function sanitizeAttribute($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Nell'HTML
<input type="email" value="<?php echo sanitizeAttribute($_POST['email'] ?? ''); ?>">

// Implementare CSP (vedi sezione 3)
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
```

---

## 7. ADDITIONAL SECURITY ISSUES

### A) Informazioni sensibili nei log (BASSO)
File: `includes/auth.php`, linee 635-645
```php
private function sendVerificationEmail($email, $name, $token) {
    $link = 'https://' . $_SERVER['HTTP_HOST'] . '/verify-email?token=' . $token;
    error_log("Verification link for $email: $link");  // Log contiene token!
}
```

**Fix:** Non loggare token completi, loggare solo l'evento.

### B) Mancanza di password reset complexity checks (MEDIO)
Email verification usa 24 ore di timeout, ma password reset usa solo 1 ora.
Inconsistente.

### C) 2FA non ha backup codes (BASSO)
L'implementazione 2FA è buona ma manca backup codes in caso di smarrimento dispositivo.

---

## RIEPILOGO VULNERABILITA'

| Severità | Tipo | File | Linea | Azione |
|----------|------|------|-------|--------|
| **CRITICO** | CSRF | index.php, login.php, register.php | ~240, ~82, ~82 | Implementare token CSRF in tutti i form |
| **CRITICO** | CORS | api/v2/index.php | 13 | Whitelist specifico dominio |
| **CRITICO** | API Key in GET | api/v2/index.php | 80 | Usare solo header Authorization |
| **MEDIO-ALTO** | Debug controllabile | config/config.php | 169 | Disabilitare debug da GET/POST |
| **MEDIO** | CSP mancante | config/config.php | N/A | Implementare CSP header |
| **MEDIO** | HSTS mancante | config/config.php | N/A | Implementare HSTS header |
| **MEDIO** | Validazione input | login.php | 38-39 | Aggiungere length check |
| **MEDIO** | SameSite Lax | includes/auth.php | 34 | Cambiare a Strict |
| **BASSO** | Token nei log | includes/auth.php | 636-645 | Non loggare token completi |

---

## PRIORITA' DI FIX

### URGENTE (Implementare immediatamente):
1. ✅ Aggiungere CSRF token protection a tutti i form POST
2. ✅ Fixare CORS (whitelist dominio)
3. ✅ Implementare CSP header
4. ✅ Rimuovere API key da URL (GET parameters)

### IMPORTANTE (Entro 1 settimana):
5. ✅ Implementare HSTS header
6. ✅ Aggiungere input length validation
7. ✅ Disabilitare debug mode da GET/POST
8. ✅ Cambiare SameSite a Strict

### MEDIUM (Entro 1 mese):
9. ✅ Aggiungere rate limiting su login/password reset
10. ✅ Rimuovere token dai log
11. ✅ Aggiungere backup codes 2FA

---

## TEST CONSIGLIATI

```bash
# Test CSRF (dovrebbe fallire senza token)
curl -X POST http://localhost/index.php \
  -d "domain=example.com" \
  -H "Content-Type: application/x-www-form-urlencoded"

# Test CORS
curl -H "Origin: http://evil.com" \
  -H "Access-Control-Request-Method: GET" \
  http://localhost/api/v2/index.php

# Test API key in URL (vulnerability)
curl "http://localhost/api/v2/index.php?api_key=secret_key_exposed"

# Test CSP
curl -I http://localhost/ | grep Content-Security-Policy

# Test HSTS
curl -I http://localhost/ | grep Strict-Transport-Security
```

---

