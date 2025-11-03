# Guida Configurazione - Controllo Domini

## Indice

1. [File di Configurazione](#file-di-configurazione)
2. [Configurazione Applicazione](#configurazione-applicazione)
3. [Configurazione SEO](#configurazione-seo)
4. [Configurazione Servizi Esterni](#configurazione-servizi-esterni)
5. [Configurazione Security](#configurazione-security)
6. [Configurazione Performance](#configurazione-performance)
7. [Variabili d'Ambiente](#variabili-dambiente)
8. [Configurazione Multi-Tenant](#configurazione-multi-tenant)

## File di Configurazione

### Struttura File Config

L'applicazione ha un unico file di configurazione centralizzato:

```
config/
└── config.php          # Configurazione globale (201 linee)
```

### config/config.php

Questo file contiene tutte le configurazioni dell'applicazione.

**Sezioni principali**:
1. Costanti applicazione
2. Configurazione SEO
3. Timezone
4. Server WHOIS
5. Server DNSBL
6. Indicatori Cloud Services
7. Security headers
8. Feature flags

## Configurazione Applicazione

### Costanti Base

Localizza in `config/config.php`:

```php
<?php
// ============================================
// APPLICATION CONFIGURATION
// ============================================

// Application Info
define('APP_NAME', 'Controllo Domini');
define('APP_VERSION', '4.0');
define('APP_URL', 'https://controllodomini.it');
define('APP_AUTHOR', 'G Tech Group');
define('APP_AUTHOR_URL', 'https://gtechgroup.it');

// Contact Information
define('APP_EMAIL', 'info@controllodomini.it');
define('APP_SUPPORT_EMAIL', 'support@controllodomini.it');

// Environment
define('APP_ENV', 'production'); // production | development | staging
define('DEBUG_MODE', isset($_GET['debug'])); // Abilita con ?debug

// Timezone
date_default_timezone_set('Europe/Rome');
```

### Personalizzazione Nome e Branding

Per cambiare nome applicazione e branding:

```php
// Modifica queste costanti:
define('APP_NAME', 'Il Tuo Nome App');
define('APP_URL', 'https://tuo-dominio.it');
define('APP_AUTHOR', 'Tua Azienda');
define('APP_AUTHOR_URL', 'https://tua-azienda.it');

// Logo: sostituisci assets/images/logo.jpg
// Favicon: sostituisci assets/images/favicon.ico
```

### Configurazione Email

```php
// Email Configuration
define('APP_EMAIL', 'info@tuo-dominio.it');
define('APP_SUPPORT_EMAIL', 'support@tuo-dominio.it');
define('APP_ADMIN_EMAIL', 'admin@tuo-dominio.it');

// Email notifiche (future implementation)
define('EMAIL_NOTIFICATIONS', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### Feature Flags

```php
// Feature Flags
define('CACHE_ENABLED', false);              // Abilita caching (future)
define('RATE_LIMIT_ENABLED', false);         // Abilita rate limiting
define('ANALYTICS_ENABLED', true);           // Google Analytics
define('API_ENABLED', false);                // API REST (future)
define('ADMIN_PANEL_ENABLED', false);        // Admin panel (future)

// Advanced Features
define('PORT_SCAN_ENABLED', true);           // Scansione porte
define('SUBDOMAIN_SCAN_ENABLED', true);      // Scan sottodomini
define('PERFORMANCE_ANALYSIS_ENABLED', true); // Analisi performance
```

## Configurazione SEO

### Meta Tags

```php
// ============================================
// SEO CONFIGURATION
// ============================================

// Default SEO
define('SEO_TITLE', 'Controllo Domini - Analisi Completa Domini Web');
define('SEO_DESCRIPTION', 'Strumento professionale per analisi domini: DNS, WHOIS, Blacklist, SSL, Performance, SEO. Analisi completa gratuita di domini e siti web.');
define('SEO_KEYWORDS', 'dns lookup, whois, controllo dominio, analisi dns, blacklist check, ssl checker, domain analysis');

// Social Media
define('SEO_IMAGE', APP_URL . '/assets/images/social-preview.jpg');
define('SEO_IMAGE_WIDTH', '1200');
define('SEO_IMAGE_HEIGHT', '630');

// Language
define('SEO_LANGUAGE', 'it_IT');
define('SEO_LOCALE', 'it_IT');
```

### Open Graph Tags

```php
// Open Graph Configuration
$ogTags = [
    'og:site_name' => APP_NAME,
    'og:type' => 'website',
    'og:title' => SEO_TITLE,
    'og:description' => SEO_DESCRIPTION,
    'og:url' => APP_URL,
    'og:image' => SEO_IMAGE,
    'og:image:width' => SEO_IMAGE_WIDTH,
    'og:image:height' => SEO_IMAGE_HEIGHT,
    'og:locale' => SEO_LOCALE
];
```

### Twitter Cards

```php
// Twitter Card Configuration
$twitterTags = [
    'twitter:card' => 'summary_large_image',
    'twitter:site' => '@ControlloDomin',  // Tuo handle Twitter
    'twitter:creator' => '@ControlloDomin',
    'twitter:title' => SEO_TITLE,
    'twitter:description' => SEO_DESCRIPTION,
    'twitter:image' => SEO_IMAGE
];
```

### Schema.org Structured Data

```php
// Schema.org Configuration
$schemaOrg = [
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => APP_NAME,
    'description' => SEO_DESCRIPTION,
    'url' => APP_URL,
    'applicationCategory' => 'DeveloperApplication',
    'operatingSystem' => 'Web Browser',
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'EUR'
    ],
    'author' => [
        '@type' => 'Organization',
        'name' => APP_AUTHOR,
        'url' => APP_AUTHOR_URL
    ]
];
```

### Google Analytics

```php
// Google Analytics
define('GA_TRACKING_ID', 'UA-XXXXXXXXX-X');  // Tuo GA ID
define('GA4_MEASUREMENT_ID', 'G-XXXXXXXXXX'); // Tuo GA4 ID

// Usage in templates/footer.php:
if (ANALYTICS_ENABLED && defined('GA4_MEASUREMENT_ID')) {
    ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA4_MEASUREMENT_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GA4_MEASUREMENT_ID; ?>');
    </script>
    <?php
}
```

## Configurazione Servizi Esterni

### Server WHOIS

Mappa TLD → WHOIS server in `config/config.php`:

```php
// ============================================
// WHOIS SERVERS CONFIGURATION
// ============================================

$whoisServers = [
    // Generic TLDs
    'com' => 'whois.verisign-grs.com',
    'net' => 'whois.verisign-grs.com',
    'org' => 'whois.pir.org',
    'info' => 'whois.afilias.net',
    'biz' => 'whois.biz',

    // Country Code TLDs - Europe
    'it' => 'whois.nic.it',
    'eu' => 'whois.eu',
    'de' => 'whois.denic.de',
    'fr' => 'whois.nic.fr',
    'uk' => 'whois.nic.uk',
    'nl' => 'whois.domain-registry.nl',
    'es' => 'whois.nic.es',
    'ch' => 'whois.nic.ch',
    'at' => 'whois.nic.at',
    'be' => 'whois.dns.be',

    // Country Code TLDs - Americas
    'us' => 'whois.nic.us',
    'ca' => 'whois.cira.ca',
    'mx' => 'whois.mx',
    'br' => 'whois.registro.br',

    // Country Code TLDs - Asia Pacific
    'jp' => 'whois.jprs.jp',
    'cn' => 'whois.cnnic.cn',
    'au' => 'whois.auda.org.au',
    'in' => 'whois.registry.in',

    // New gTLDs
    'io' => 'whois.nic.io',
    'me' => 'whois.nic.me',
    'tv' => 'whois.nic.tv',
    'cc' => 'whois.nic.cc',
    'ws' => 'whois.website.ws',
    'mobi' => 'whois.dotmobiregistry.net',
    'pro' => 'whois.registrypro.pro',

    // Special TLDs
    'edu' => 'whois.educause.edu',
    'gov' => 'whois.dotgov.gov',
    'mil' => 'whois.nic.mil',

    // Aggiungi altri TLD secondo necessità
];

// WHOIS Configuration
define('WHOIS_TIMEOUT', 10);           // Timeout in secondi
define('WHOIS_PORT', 43);              // Porta WHOIS standard
define('WHOIS_FALLBACK_ENABLED', true); // Abilita fallback HTTP
```

**Aggiungere nuovo TLD**:

```php
// Esempio: aggiungere .xyz
$whoisServers['xyz'] = 'whois.nic.xyz';
```

### Server DNSBL (Blacklist)

Lista server blacklist email:

```php
// ============================================
// DNSBL SERVERS CONFIGURATION
// ============================================

$dnsblServers = [
    // Major Blacklists
    'zen.spamhaus.org',           // Spamhaus ZEN (multi-list)
    'bl.spamcop.net',             // SpamCop
    'b.barracudacentral.org',     // Barracuda
    'dnsbl.sorbs.net',            // SORBS
    'bl.spamcannibal.org',        // SpamCannibal

    // UCEPROTECT Lists
    'dnsbl-1.uceprotect.net',     // UCEPROTECT Level 1
    'dnsbl-2.uceprotect.net',     // UCEPROTECT Level 2
    'dnsbl-3.uceprotect.net',     // UCEPROTECT Level 3

    // Other Popular Lists
    'psbl.surriel.com',           // Passive Spam Block List
    'dnsbl.dronebl.org',          // DroneBL
    'rbl.efnetrbl.org',           // EFnet RBL
    'spam.dnsbl.anonmails.de',    // Anonmails
    'all.s5h.net',                // S5H
    'bl.emailbasura.org',         // EmailBasura
    'combined.abuse.ch',          // abuse.ch
    'cbl.abuseat.org',            // Composite Blocking List

    // Spamhaus Individual Lists
    'sbl.spamhaus.org',           // Spamhaus SBL
    'xbl.spamhaus.org',           // Spamhaus XBL
    'pbl.spamhaus.org',           // Spamhaus PBL

    // Commercial Lists
    'wpbl.info',                  // Weighted Private Block List
    'db.wpbl.info',               // WPBL Database
    'query.senderbase.org',       // SenderBase (Cisco)

    // Emerging Threats
    'dnsbl.abuse.ch',             // abuse.ch Blocklist
    'ubl.unsubscore.com',         // LashBack UBL
    'dyna.spamrats.com',          // SpamRats Dyna
    'noptr.spamrats.com',         // SpamRats NoPtr
    'spam.spamrats.com',          // SpamRats Spam

    // Regional Lists
    'korea.services.net',         // Korean RBL
    'virus.rbl.jp',               // Japan Virus RBL

    // Aggiungi altri DNSBL secondo necessità
];

// DNSBL Configuration
define('DNSBL_TIMEOUT', 5);            // Timeout per query (secondi)
define('DNSBL_PARALLEL_ENABLED', true); // Abilita check paralleli
define('DNSBL_MAX_PARALLEL', 10);      // Max query parallele simultanee
```

**Aggiungere/Rimuovere DNSBL**:

```php
// Aggiungere
$dnsblServers[] = 'nuovo.blacklist.example';

// Rimuovere (commenta o elimina)
// 'dnsbl-3.uceprotect.net',  // Troppo aggressivo
```

### Indicatori Cloud Services

Configura rilevamento provider cloud:

```php
// ============================================
// CLOUD SERVICES INDICATORS
// ============================================

// Microsoft 365 / Office 365
$cloudIndicators['microsoft365'] = [
    'mx_patterns' => [
        'mail.protection.outlook.com',
        'eo.outlook.com'
    ],
    'spf_includes' => [
        'spf.protection.outlook.com',
        'spf.protection.microsoft.com'
    ],
    'txt_records' => [
        'MS=',
        'v=msv1'
    ]
];

// Google Workspace
$cloudIndicators['google_workspace'] = [
    'mx_patterns' => [
        'aspmx.l.google.com',
        'alt1.aspmx.l.google.com',
        'googlemail.com'
    ],
    'spf_includes' => [
        '_spf.google.com',
        'include:_spf.google.com'
    ],
    'txt_records' => [
        'google-site-verification='
    ]
];

// AWS Services
$cloudIndicators['aws'] = [
    'nameservers' => [
        'awsdns',
        'route53'
    ],
    'ip_ranges' => [
        // AWS IP ranges (simplified)
        '52.', '54.', '3.', '18.'
    ],
    'cname_patterns' => [
        '.amazonaws.com',
        '.cloudfront.net',
        '.elasticbeanstalk.com'
    ]
];

// Cloudflare
$cloudIndicators['cloudflare'] = [
    'nameservers' => [
        'cloudflare.com'
    ],
    'http_headers' => [
        'CF-RAY',
        'cf-cache-status'
    ],
    'ip_ranges' => [
        '173.245.', '103.21.', '103.22.',
        '103.31.', '141.101.', '108.162.',
        '190.93.', '188.114.', '197.234.',
        '198.41.', '162.158.', '104.16.',
        '104.17.', '104.18.', '104.19.',
        '104.20.', '104.21.', '104.22.',
        '104.23.', '104.24.', '104.25.',
        '104.26.', '104.27.', '104.28.'
    ]
];

// Aggiungi altri provider secondo necessità
```

## Configurazione Security

### Security Headers

Configura HTTP security headers in `config/config.php`:

```php
// ============================================
// SECURITY CONFIGURATION
// ============================================

// Security Headers (applicati via .htaccess e templates)
$securityHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
];

// HTTPS Only (Production)
if (APP_ENV === 'production') {
    $securityHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
}

// Content Security Policy
$csp = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' https://unpkg.com https://www.googletagmanager.com",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com",
    "font-src 'self' https://fonts.gstatic.com",
    "img-src 'self' data: https:",
    "connect-src 'self'",
    "frame-ancestors 'self'"
];
$securityHeaders['Content-Security-Policy'] = implode('; ', $csp);
```

### Rate Limiting

```php
// Rate Limiting Configuration
define('RATE_LIMIT_ENABLED', false);   // Abilita in produzione
define('RATE_LIMIT_REQUESTS', 100);    // Richieste per periodo
define('RATE_LIMIT_PERIOD', 3600);     // Periodo in secondi (1 ora)
define('RATE_LIMIT_METHOD', 'ip');     // 'ip' | 'session' | 'user'

// Whitelist IP (non soggetti a rate limit)
$rateLimitWhitelist = [
    '127.0.0.1',
    '::1',
    // Aggiungi IP fidati
];

// Implementation in utilities.php:
function checkRateLimit($ip) {
    if (!RATE_LIMIT_ENABLED) return true;
    if (in_array($ip, $GLOBALS['rateLimitWhitelist'])) return true;

    // Implementa logica rate limiting
    // (file-based, Redis, Memcached, ecc.)
}
```

### Input Validation

```php
// Validation Rules
define('DOMAIN_MAX_LENGTH', 253);      // Max lunghezza dominio
define('DOMAIN_LABEL_MAX_LENGTH', 63); // Max lunghezza label
define('IP_VALIDATION_STRICT', true);  // Validazione IP strict

// Allowed characters in domain
define('DOMAIN_ALLOWED_CHARS', 'a-zA-Z0-9.-');

// IDN (Internationalized Domain Names) support
define('IDN_SUPPORT_ENABLED', true);
```

## Configurazione Performance

### Timeout Settings

```php
// ============================================
// PERFORMANCE CONFIGURATION
// ============================================

// Timeout Settings
define('DNS_QUERY_TIMEOUT', 5);        // Timeout query DNS (secondi)
define('WHOIS_QUERY_TIMEOUT', 10);     // Timeout query WHOIS (secondi)
define('HTTP_REQUEST_TIMEOUT', 15);    // Timeout richieste HTTP (secondi)
define('SSL_ANALYSIS_TIMEOUT', 10);    // Timeout analisi SSL (secondi)
define('PORT_SCAN_TIMEOUT', 2);        // Timeout per porta (secondi)

// Max execution time (sovrascrivi php.ini)
set_time_limit(60);                    // 60 secondi max
```

### Caching Configuration

```php
// Cache Configuration (future implementation)
define('CACHE_ENABLED', false);
define('CACHE_DRIVER', 'file');        // file | redis | memcached
define('CACHE_TTL', 3600);             // TTL default (1 ora)
define('CACHE_PATH', __DIR__ . '/../cache');

// Cache TTL per tipo di dato
$cacheTTL = [
    'whois' => 86400,      // 24 ore (cambiano raramente)
    'dns' => 3600,         // 1 ora (rispetta TTL DNS)
    'blacklist' => 7200,   // 2 ore
    'ssl' => 86400,        // 24 ore
    'performance' => 3600  // 1 ora
];

// Redis Configuration (se CACHE_DRIVER === 'redis')
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');
define('REDIS_DATABASE', 0);

// Memcached Configuration (se CACHE_DRIVER === 'memcached')
define('MEMCACHED_HOST', '127.0.0.1');
define('MEMCACHED_PORT', 11211);
```

### Compression

Configurato in `.htaccess`:

```apache
# Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Brotli Compression (se disponibile)
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

## Variabili d'Ambiente

### File .env (Opzionale)

Crea `.env` per configurazioni sensibili:

```bash
# .env file
APP_ENV=production
APP_DEBUG=false
APP_URL=https://controllodomini.it

# Database (future)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=controllo_domini
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

# Analytics
GA_TRACKING_ID=UA-XXXXXXXXX-X
GA4_MEASUREMENT_ID=G-XXXXXXXXXX

# API Keys
API_KEY_SECRET=your-secret-key-here
```

### Carica .env

Crea `config/env-loader.php`:

```php
<?php
// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes
        $value = trim($value, '"\'');

        // Set environment variable
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Load .env
loadEnv(__DIR__ . '/../.env');
```

Include in `config/config.php`:

```php
// Load environment variables
require_once __DIR__ . '/env-loader.php';

// Use environment variables
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', getenv('APP_URL') ?: 'https://controllodomini.it');
define('GA4_MEASUREMENT_ID', getenv('GA4_MEASUREMENT_ID') ?: '');
```

## Configurazione Multi-Tenant

### Setup Multi-Tenant (Future)

Per gestire multiple istanze:

```php
// config/tenants.php

$tenants = [
    'tenant1.controllodomini.it' => [
        'name' => 'Tenant 1',
        'theme' => 'default',
        'logo' => 'tenant1-logo.jpg',
        'analytics_id' => 'GA-TENANT1',
        'features' => ['dns', 'whois', 'blacklist', 'ssl']
    ],
    'tenant2.controllodomini.it' => [
        'name' => 'Tenant 2',
        'theme' => 'dark',
        'logo' => 'tenant2-logo.jpg',
        'analytics_id' => 'GA-TENANT2',
        'features' => ['dns', 'whois', 'ssl', 'performance']
    ]
];

// Auto-detect tenant by hostname
$currentHost = $_SERVER['HTTP_HOST'] ?? 'controllodomini.it';
$currentTenant = $tenants[$currentHost] ?? $tenants['controllodomini.it'];

// Apply tenant config
define('TENANT_NAME', $currentTenant['name']);
define('TENANT_THEME', $currentTenant['theme']);
// ...
```

## Best Practices

### 1. Configurazioni Sensibili

✅ **Fare**:
- Usa file `.env` per credenziali
- Non committare `.env` in git
- Usa permessi file restrittivi (600 per .env)

❌ **Non fare**:
- Non hardcodare password in config.php
- Non committare API keys
- Non esporre credenziali in error messages

### 2. Environment-Specific Config

```php
// config/config.php
switch (APP_ENV) {
    case 'development':
        define('DEBUG_MODE', true);
        define('CACHE_ENABLED', false);
        define('RATE_LIMIT_ENABLED', false);
        ini_set('display_errors', 1);
        break;

    case 'staging':
        define('DEBUG_MODE', false);
        define('CACHE_ENABLED', true);
        define('RATE_LIMIT_ENABLED', true);
        ini_set('display_errors', 0);
        break;

    case 'production':
        define('DEBUG_MODE', false);
        define('CACHE_ENABLED', true);
        define('RATE_LIMIT_ENABLED', true);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        break;
}
```

### 3. Verifica Configurazione

Crea script di verifica:

```php
// check-config.php
<?php
require_once 'config/config.php';

echo "=== Configuration Check ===\n\n";

// Check required constants
$required = ['APP_NAME', 'APP_VERSION', 'APP_URL'];
foreach ($required as $const) {
    echo "$const: " . (defined($const) ? '✅ ' . constant($const) : '❌ NOT DEFINED') . "\n";
}

// Check WHOIS servers
echo "\nWHOIS Servers: " . count($whoisServers) . " TLDs configured\n";

// Check DNSBL servers
echo "DNSBL Servers: " . count($dnsblServers) . " blacklists configured\n";

// Check file permissions
echo "\nFile Permissions:\n";
echo "config/config.php: " . substr(sprintf('%o', fileperms('config/config.php')), -4) . "\n";
echo ".htaccess: " . substr(sprintf('%o', fileperms('.htaccess')), -4) . "\n";
```

---

**Ultimo aggiornamento**: Novembre 2025
**Versione guida**: 1.0
