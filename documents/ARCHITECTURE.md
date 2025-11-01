# Architettura Sistema - Controllo Domini

## Indice

1. [Panoramica Architetturale](#panoramica-architetturale)
2. [Pattern Architetturali](#pattern-architetturali)
3. [Struttura Directory](#struttura-directory)
4. [Layer Applicativo](#layer-applicativo)
5. [Flusso Dati](#flusso-dati)
6. [Moduli Core](#moduli-core)
7. [Gestione Dipendenze](#gestione-dipendenze)
8. [Design Pattern Utilizzati](#design-pattern-utilizzati)
9. [Performance e Scalabilità](#performance-e-scalabilità)
10. [Diagrammi Architetturali](#diagrammi-architetturali)

## Panoramica Architetturale

### Filosofia di Design

Controllo Domini è progettato secondo questi principi fondamentali:

1. **Stateless Architecture** - Nessun database, nessuna sessione persistente
2. **Lightweight Design** - Nessun framework pesante, solo PHP procedural
3. **External Data Sources** - Tutte le informazioni provengono da fonti autorevoli esterne
4. **Modularity** - Separazione delle responsabilità in moduli funzionali
5. **Performance-First** - Ottimizzazioni per tempi di risposta rapidi
6. **Security by Design** - Validazione input, protezione output, rate limiting

### Tipo di Architettura

**Architettura Web a 3 Livelli Semplificata:**

```
┌─────────────────────────────────────────┐
│      Presentation Layer (Frontend)      │
│  HTML5 + CSS3 + JavaScript (Vanilla)   │
└─────────────────────────────────────────┘
              ↕ HTTP/HTTPS
┌─────────────────────────────────────────┐
│     Application Layer (PHP Backend)     │
│  206+ Functions in 13 Modules          │
│  MVC-like (Templates + Controllers)    │
└─────────────────────────────────────────┘
              ↕ Protocols
┌─────────────────────────────────────────┐
│      External Services Layer            │
│  DNS | WHOIS | DNSBL | HTTP | SSL      │
└─────────────────────────────────────────┘
```

### Caratteristiche Architetturali

- **No Database Layer**: Tutti i dati sono query real-time a servizi esterni
- **Procedural PHP**: No OOP, pattern funzionale puro
- **Template-Based Views**: Separazione logica/presentazione
- **RESTful-Like Routing**: URL clean tramite `.htaccess`

## Pattern Architetturali

### 1. Front Controller Pattern

Il file `index.php` agisce come front controller principale:

```php
// bootstrap.php viene incluso per inizializzazione
require_once 'bootstrap.php';
require_once 'config/config.php';
require_once 'includes/utilities.php';
require_once 'templates/header.php';

// Logica applicazione
// ...

require_once 'templates/footer.php';
```

Ogni pagina segue questo pattern:
1. Bootstrap/Inizializzazione
2. Include configurazione
3. Include utilities
4. Include moduli necessari
5. Render header template
6. Esecuzione logica business
7. Render output
8. Render footer template

### 2. Module Pattern

Ogni modulo in `/includes/` è un insieme coeso di funzioni correlate:

```php
// dns-functions.php
function getAllDnsRecords($domain) { }
function getDnsRecordTypes() { }
function getCommonSubdomains() { }
function analyzeDNSSEC($domain) { }

// whois-functions.php
function getWhoisInfo($domain) { }
function getWhoisViaSocket($domain, $server) { }
function parseWhoisData($data) { }

// blacklist-functions.php
function checkBlacklists($ips, $domain) { }
function checkBlacklistsParallel($ips) { }
function calculateReputation($results) { }
```

### 3. Template Pattern

Separazione presentazione/logica:

```php
// templates/header.php - Header comune
<!DOCTYPE html>
<html lang="it">
<head>
    <title><?php echo generatePageTitle(); ?></title>
    <meta name="description" content="<?php echo generateMetaDescription(); ?>">
    // ... meta tags dinamici
</head>

// templates/footer.php - Footer comune
    <footer>...</footer>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

### 4. Service Locator Pattern (Implicito)

Configurazione centralizzata in `config/config.php`:

```php
// Configurazione globale accessibile da tutti i moduli
define('APP_NAME', 'Controllo Domini');
define('APP_VERSION', '4.0');
define('APP_URL', 'https://controllodomini.it');

// Map WHOIS servers
$whoisServers = [
    'com' => 'whois.verisign-grs.com',
    'net' => 'whois.verisign-grs.com',
    'it' => 'whois.nic.it',
    // ...
];

// Map DNSBL servers
$dnsblServers = [
    'zen.spamhaus.org',
    'bl.spamcop.net',
    // ...
];
```

## Struttura Directory

### Organizzazione Fisica

```
controllo-domini/
│
├── assets/                          # Asset statici
│   ├── css/
│   │   └── style.css               # Stile principale (~600 linee)
│   ├── js/
│   │   └── main.js                 # JavaScript principale (~400 linee)
│   └── images/
│       ├── logo.jpg
│       └── placeholder.svg
│
├── config/                          # Configurazione
│   └── config.php                  # Config globale (201 linee)
│
├── includes/                        # Moduli funzionali (Logic Layer)
│   ├── utilities.php               # Funzioni utility (1,160 linee, 36+ func)
│   ├── dns-functions.php           # DNS (586 linee, ~15 func)
│   ├── whois-functions.php         # WHOIS (1,098 linee, ~20 func)
│   ├── blacklist-functions.php     # Blacklist (852 linee, ~15 func)
│   ├── cloud-detection.php         # Cloud (999 linee, ~15 func)
│   ├── ssl-certificate.php         # SSL/TLS (731 linee, ~12 func)
│   ├── security-headers.php        # Security headers (578 linee, ~10 func)
│   ├── technology-detection.php    # Tech detection (1,190 linee, ~18 func)
│   ├── social-meta-analysis.php    # Social meta (1,037 linee, ~15 func)
│   ├── performance-analysis.php    # Performance (1,107 linee, ~20 func)
│   ├── robots-sitemap.php          # SEO (825 linee, ~12 func)
│   ├── redirect-analysis.php       # Redirects (822 linee, ~10 func)
│   └── port-scanner.php            # Port scan (869 linee, ~12 func)
│
├── templates/                       # Templates (Presentation Layer)
│   ├── header.php                  # Header HTML comune
│   └── footer.php                  # Footer HTML comune
│
├── documents/                       # Documentazione progetto
│   ├── README.md
│   ├── ARCHITECTURE.md             # Questo file
│   ├── API.md
│   ├── INSTALLATION.md
│   ├── CONFIGURATION.md
│   ├── FEATURES.md
│   ├── SECURITY.md
│   ├── DEVELOPMENT.md
│   └── DEPLOYMENT.md
│
├── Pagine Principali (Controllers)
│   ├── bootstrap.php               # Inizializzazione app
│   ├── index.php                   # Dashboard principale (2,702 linee)
│   ├── dns-check.php               # Pagina DNS
│   ├── whois-lookup.php            # Pagina WHOIS
│   ├── blacklist-check.php         # Pagina blacklist
│   ├── cloud-detection.php         # Pagina cloud
│   ├── spf-dkim-dmarc.php         # Guida email auth
│   ├── setup-microsoft-365.php    # Guida M365
│   ├── dns-guide.php              # Documentazione DNS
│   ├── tools.php                   # Directory tools
│   ├── api-docs.php               # API reference
│   ├── changelog.php              # Changelog
│   ├── 404.php                    # Error page
│   └── generate-icons.php         # Utility icons
│
├── Configurazione Server
│   ├── .htaccess                  # Apache config (URL rewriting, security)
│   └── robots.txt                 # SEO e crawler management
│
└── Version Control
    └── .git/                      # Git repository
```

### Responsabilità per Layer

#### Presentation Layer (`/templates/`, `/assets/`)
- Rendering HTML
- Styling CSS
- Interattività JavaScript
- Responsive design
- Animazioni (AOS)

#### Application Layer (`/includes/`, `/*.php`)
- Business logic
- Validazione input
- Chiamate servizi esterni
- Elaborazione dati
- Formattazione output

#### Configuration Layer (`/config/`)
- Costanti applicazione
- Mapping server esterni
- Feature flags
- Parametri configurabili

## Layer Applicativo

### Request Flow

```
1. Client HTTP Request
   ↓
2. Apache mod_rewrite (.htaccess)
   ↓ URL rewriting
3. Front Controller (es. index.php)
   ↓
4. Bootstrap & Initialization
   ↓ require config, utilities
5. Input Validation
   ↓ validateDomain()
6. Business Logic Execution
   ↓ Chiamate funzioni moduli
7. External Service Calls
   ↓ DNS, WHOIS, DNSBL, HTTP
8. Data Processing
   ↓ Parsing, formatting
9. Template Rendering
   ↓ header.php, content, footer.php
10. HTTP Response
```

### Example: DNS Check Flow

```php
// 1. Request arriva a dns-check.php
// 2. Bootstrap
require_once 'bootstrap.php';
require_once 'config/config.php';
require_once 'includes/utilities.php';
require_once 'includes/dns-functions.php';

// 3. Render header
require_once 'templates/header.php';

// 4. Input validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = $_POST['domain'] ?? '';

    // 5. Validazione
    if (!validateDomain($domain)) {
        // Error handling
    }

    // 6. Business logic
    $dnsRecords = getAllDnsRecords($domain);
    $responseTime = measureDnsResponseTime($domain);

    // 7. Rendering risultati
    // HTML output con $dnsRecords
}

// 8. Render footer
require_once 'templates/footer.php';
```

## Flusso Dati

### Data Sources (Read-Only)

L'applicazione **non scrive mai dati**, solo lettura da:

1. **DNS Servers**
   - Protocollo: DNS (UDP/TCP port 53)
   - Funzione PHP: `dns_get_record()`
   - Timeout: Configurabile
   - Retry: Configurabile

2. **WHOIS Servers**
   - Protocollo: WHOIS (TCP port 43)
   - Metodi:
     - Socket connection (primario)
     - shell_exec whois (fallback)
     - HTTP web WHOIS (ultimo fallback)
   - Timeout: 10 secondi default

3. **DNSBL Servers**
   - Protocollo: DNS lookup
   - Metodo: Reverse IP query
   - Esempio: `1.2.3.4` → `4.3.2.1.zen.spamhaus.org`
   - Parallel queries con cURL multi

4. **HTTP/HTTPS Target Sites**
   - Protocollo: HTTP/HTTPS
   - Libreria: cURL
   - Scopo:
     - Fetch robots.txt
     - Fetch sitemap.xml
     - Tech detection via headers
     - SSL certificate analysis
     - Performance metrics

### Data Flow Diagram

```
┌──────────┐
│  Client  │
└────┬─────┘
     │ HTTP POST (domain=example.com)
     ↓
┌────────────────┐
│  index.php     │ ← Front Controller
└────┬───────────┘
     │
     ├─→ validateDomain() [utilities.php]
     │
     ├─→ getAllDnsRecords() [dns-functions.php]
     │   └─→ dns_get_record() [PHP native]
     │       └─→ 8.8.8.8 (DNS Server) ← External
     │
     ├─→ getWhoisInfo() [whois-functions.php]
     │   └─→ socket_connect(whois.verisign-grs.com:43) ← External
     │
     ├─→ checkBlacklists() [blacklist-functions.php]
     │   └─→ dns_get_record(IP.zen.spamhaus.org) ← External
     │
     ├─→ analyzeSSLCertificate() [ssl-certificate.php]
     │   └─→ stream_context_create() + fopen(https://...) ← External
     │
     └─→ Render HTML + JSON responses
         └─→ Client (HTTP Response)
```

### Nessun Caching Attivo

Decisione architettural: **No caching** (configurabile ma disabilitato)

Motivazioni:
- Dati esterni cambiano frequentemente
- Query real-time garantiscono dati aggiornati
- Nessuna complessità gestione cache
- Nessun stale data problem

Future consideration:
- Redis/Memcached per caching opzionale
- TTL-based caching per WHOIS (cambiano raramente)

## Moduli Core

### 1. utilities.php (1,160 linee, 36+ funzioni)

**Responsabilità:**
- Validazione input (domain, IP)
- Formattazione output (TTL, date, numeri)
- Misurazione performance
- Rate limiting
- SEO metadata generation
- Helper generici

**Funzioni chiave:**
```php
validateDomain($domain)                    // Validazione domini
getIpAddresses($domain)                   // Risoluzione IP
measureDnsResponseTime($domain)           // Performance DNS
checkRateLimit($ip)                       // Rate limiting
formatTTL($seconds)                       // Formattazione TTL
generatePageTitle()                       // SEO title dinamico
```

### 2. dns-functions.php (586 linee, ~15 funzioni)

**Responsabilità:**
- Query DNS completa (A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA)
- Rilevamento sottodomini
- Analisi DNSSEC
- Rilevamento duplicati

**Architettura:**
```php
getAllDnsRecords($domain) {
    // Chiama dns_get_record() per ogni tipo
    foreach (['A', 'AAAA', 'MX', 'TXT', ...] as $type) {
        $records = dns_get_record($domain, constant("DNS_$type"));
        // Process records
    }

    // Check common subdomains
    $subdomains = getCommonSubdomains($domain);

    // DNSSEC validation
    $dnssec = analyzeDNSSEC($domain);

    return $compiledResults;
}
```

### 3. whois-functions.php (1,098 linee, ~20 funzioni)

**Responsabilità:**
- WHOIS lookup multi-metodo
- Parsing dati WHOIS
- Estrazione informazioni strutturate
- Fallback chain

**Fallback Chain:**
```
1. getWhoisViaSocket()      → Primario (socket TCP:43)
   ↓ (fallback on failure)
2. shell_exec('whois')      → Secondario (se disponibile)
   ↓ (fallback on failure)
3. getWhoisViaCurl()        → HTTP fallback
   ↓ (fallback on failure)
4. getWhoisFromInternic()   → Ultimo resort
```

### 4. blacklist-functions.php (852 linee, ~15 funzioni)

**Responsabilità:**
- Controllo 30+ DNSBL
- Modalità parallela (cURL multi) e sequenziale
- Calcolo reputation score
- Statistiche dettagliate

**Parallel Check Architecture:**
```php
checkBlacklistsParallel($ips) {
    $curlMulti = curl_multi_init();

    foreach ($dnsblServers as $dnsbl) {
        foreach ($ips as $ip) {
            $reverseIp = reverseIp($ip);
            $query = "$reverseIp.$dnsbl";

            // Add to multi handle
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://$query");
            curl_multi_add_handle($curlMulti, $ch);
        }
    }

    // Execute parallel requests
    curl_multi_exec($curlMulti);

    // Collect results
}
```

### 5. ssl-certificate.php (731 linee, ~12 funzioni)

**Responsabilità:**
- Analisi certificati SSL/TLS
- Validazione catena certificati
- Test protocolli e cipher
- Vulnerability checking
- Grade assignment (A-F)

**SSL Check Flow:**
```php
analyzeSSLCertificate($domain) {
    // 1. Get certificate
    $cert = getSSLCertificateInfo($domain);

    // 2. Validate chain
    $chain = getSSLChain($domain);

    // 3. Check protocols
    $protocols = checkSSLProtocols($domain); // SSLv3, TLS 1.0-1.3

    // 4. Check ciphers
    $ciphers = checkCipherSuites($domain);

    // 5. Vulnerability scan
    $vulns = checkSSLVulnerabilities($domain); // POODLE, BEAST, etc.

    // 6. Calculate grade
    $grade = calculateSSLScore($cert, $protocols, $ciphers, $vulns);

    return ['cert' => $cert, 'grade' => $grade, ...];
}
```

### 6-13. Altri Moduli

Ogni modulo segue pattern simili:
- Funzione principale che orchestra il workflow
- Funzioni helper per sottotask
- Parsing e formattazione dati
- Error handling robusto
- Timeout management

## Gestione Dipendenze

### PHP Extensions Required

```php
// Verifica in bootstrap.php
$requiredExtensions = ['json', 'curl', 'mbstring', 'openssl'];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Required PHP extension '$ext' is not loaded.");
    }
}
```

### External Libraries

**Frontend:**
- AOS 2.3.1 (CDN): https://unpkg.com/aos@2.3.1/dist/aos.css
- Google Fonts API: Poppins, Lato

**Backend:**
- **Nessuna dipendenza composer** o package manager
- Solo PHP standard library
- cURL per HTTP requests
- Socket per WHOIS

### Nessun Build Process

- No npm, no webpack, no gulp
- CSS e JS serviti direttamente
- Versioning tramite file modification time:
  ```php
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
  ```

## Design Pattern Utilizzati

### 1. Separation of Concerns

```
Presentation (templates/) ←→ Business Logic (includes/) ←→ Data (external services)
```

### 2. Single Responsibility Principle

Ogni modulo ha una responsabilità chiara:
- `dns-functions.php` → Solo DNS
- `whois-functions.php` → Solo WHOIS
- `utilities.php` → Solo funzioni generiche

### 3. DRY (Don't Repeat Yourself)

Funzioni riutilizzabili:
```php
// utilities.php
function safeHtmlspecialchars($value) {
    if (is_array($value)) return implode(', ', $value);
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Usata in tutti i moduli per output sicuro
```

### 4. Fail-Safe Defaults

```php
// Sempre con fallback
$domain = $_POST['domain'] ?? '';
$timeout = $config['timeout'] ?? 10;
$result = performQuery() ?? ['error' => 'Query failed'];
```

### 5. Strategy Pattern (Implicito)

Diverse strategie per stesso obiettivo:
```php
// WHOIS: multiple strategies
getWhoisViaSocket() OR shell_exec() OR HTTP fallback

// Blacklist: parallel OR sequential
checkBlacklistsParallel() OR checkBlacklistsSequential()
```

## Performance e Scalabilità

### Performance Optimizations

1. **Critical CSS Inline**
   ```php
   // In header.php
   <style>/* Critical CSS inlined */</style>
   ```

2. **DNS Prefetch & Preconnect**
   ```html
   <link rel="dns-prefetch" href="//fonts.googleapis.com">
   <link rel="preconnect" href="//unpkg.com">
   ```

3. **Asset Versioning**
   ```php
   ?v=<?php echo filemtime('assets/css/style.css'); ?>
   ```

4. **Gzip Compression** (via .htaccess)
   ```apache
   AddOutputFilterByType DEFLATE text/html text/css application/javascript
   ```

5. **Static Asset Caching** (via .htaccess)
   ```apache
   ExpiresActive On
   ExpiresByType image/* "access plus 1 month"
   ExpiresByType text/css "access plus 1 week"
   ```

6. **Parallel External Requests**
   ```php
   // cURL multi per DNSBL checks
   curl_multi_exec($mh);
   ```

### Scalabilità Considerations

**Limiti Correnti:**
- Stateless = infinitamente scalabile orizzontalmente
- No database = no bottleneck DB
- Rate limiting = protezione da abuse

**Bottleneck Potenziali:**
- Timeout query esterne (DNS, WHOIS)
- WHOIS server rate limits
- Bandwidth per cURL requests

**Soluzioni Scalabilità:**
1. **Load Balancing**: Multiple PHP-FPM instances
2. **Caching Layer**: Redis per WHOIS (cambiano raramente)
3. **Queue System**: RabbitMQ per long-running tasks
4. **CDN**: CloudFlare per asset statici
5. **Horizontal Scaling**: Docker containers + Kubernetes

## Diagrammi Architetturali

### Component Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT BROWSER                        │
└───────────────────────┬─────────────────────────────────┘
                        │ HTTP/HTTPS
┌───────────────────────▼─────────────────────────────────┐
│                  APACHE WEB SERVER                       │
│  ┌────────────────────────────────────────────────────┐ │
│  │              .htaccess (mod_rewrite)               │ │
│  │  URL Rewriting, Security Headers, Gzip             │ │
│  └────────────────────┬───────────────────────────────┘ │
└───────────────────────┼─────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────┐
│              PHP APPLICATION LAYER                       │
│                                                          │
│  ┌────────────────┐  ┌────────────────┐                │
│  │ bootstrap.php  │  │  config.php    │                │
│  │ Initialization │  │  Configuration │                │
│  └────────────────┘  └────────────────┘                │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │           FRONT CONTROLLERS                       │  │
│  │  index.php, dns-check.php, whois-lookup.php...   │  │
│  └──────────────────┬───────────────────────────────┘  │
│                     │                                   │
│  ┌──────────────────▼───────────────────────────────┐  │
│  │              BUSINESS LOGIC MODULES               │  │
│  │                                                    │  │
│  │  ┌──────────────┐  ┌─────────────────┐           │  │
│  │  │ utilities    │  │ dns-functions   │           │  │
│  │  └──────────────┘  └─────────────────┘           │  │
│  │  ┌──────────────┐  ┌─────────────────┐           │  │
│  │  │ whois-func   │  │ blacklist-func  │           │  │
│  │  └──────────────┘  └─────────────────┘           │  │
│  │  ┌──────────────┐  ┌─────────────────┐           │  │
│  │  │ ssl-cert     │  │ security-headers│           │  │
│  │  └──────────────┘  └─────────────────┘           │  │
│  │  ... 7 more modules ...                           │  │
│  └────────────────────────────────────────────────── ┘  │
│                     │                                   │
│  ┌──────────────────▼───────────────────────────────┐  │
│  │              PRESENTATION LAYER                   │  │
│  │  header.php, footer.php (templates)              │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
└───────────────────────┬─────────────────────────────────┘
                        │ External Protocols
        ┌───────────────┼───────────────┐
        │               │               │
┌───────▼────┐  ┌──────▼─────┐  ┌─────▼──────┐
│ DNS Servers│  │WHOIS Servers│  │DNSBL Servers│
│ 8.8.8.8    │  │whois.*.com  │  │spamhaus.org │
│ 1.1.1.1    │  │TCP:43       │  │spamcop.net  │
└────────────┘  └─────────────┘  └─────────────┘

        ┌───────────────┼───────────────┐
        │               │               │
┌───────▼────────┐ ┌───▼──────────┐ ┌─▼──────────┐
│ Target Website │ │ SSL/TLS Cert │ │ HTTP APIs  │
│ robots.txt     │ │ Certificate  │ │ Various    │
│ sitemap.xml    │ │ Chain        │ │ Services   │
└────────────────┘ └──────────────┘ └────────────┘
```

### Deployment Architecture

```
┌────────────────────────────────────────────────┐
│              INTERNET (Clients)                 │
└─────────────────────┬──────────────────────────┘
                      │
          ┌───────────▼──────────────┐
          │   Load Balancer (Future) │
          │   NGINX / HAProxy        │
          └───────────┬──────────────┘
                      │
      ┌───────────────┼───────────────┐
      │               │               │
┌─────▼─────┐  ┌─────▼─────┐  ┌─────▼─────┐
│  Apache   │  │  Apache   │  │  Apache   │
│  PHP-FPM  │  │  PHP-FPM  │  │  PHP-FPM  │
│  Instance │  │  Instance │  │  Instance │
│     #1    │  │     #2    │  │     #3    │
└───────────┘  └───────────┘  └───────────┘
  (Current: Single instance)

       Optional Future Layer:
┌──────────────────────────────────────┐
│    Redis Cache (WHOIS, DNS TTL)      │
│    Queue System (Long tasks)         │
└──────────────────────────────────────┘
```

### Data Flow Sequence

```
User → Browser → Apache → .htaccess → index.php
                                          ↓
                                    bootstrap.php
                                          ↓
                                    config.php loaded
                                          ↓
                                    utilities.php loaded
                                          ↓
                                    validateDomain()
                                          ↓
                    ┌─────────────────────┼─────────────────────┐
                    │                     │                     │
            dns-functions.php    whois-functions.php   blacklist-functions.php
                    ↓                     ↓                     ↓
              getAllDnsRecords()    getWhoisInfo()       checkBlacklists()
                    ↓                     ↓                     ↓
              dns_get_record()    socket_connect(43)    dns_get_record(DNSBL)
                    ↓                     ↓                     ↓
              [8.8.8.8:53]         [whois.server:43]     [zen.spamhaus.org]
                    ↓                     ↓                     ↓
              Parse results         Parse WHOIS data      Calculate reputation
                    │                     │                     │
                    └─────────────────────┼─────────────────────┘
                                          ↓
                                   Aggregate results
                                          ↓
                                   header.php rendered
                                          ↓
                                   HTML output generated
                                          ↓
                                   footer.php rendered
                                          ↓
                                   HTTP Response → Browser
```

## Conclusioni Architetturali

### Punti di Forza

1. ✅ **Semplicità**: Architettura comprensibile, manutenibile
2. ✅ **Stateless**: Scalabilità orizzontale illimitata
3. ✅ **No Vendor Lock-in**: Nessuna dipendenza da framework
4. ✅ **Performance**: Lightweight, fast response times
5. ✅ **Modularità**: Moduli indipendenti, testabili singolarmente

### Aree di Miglioramento Futuro

1. 🔄 **Caching Layer**: Redis per dati WHOIS (cambiano raramente)
2. 🔄 **Queue System**: Per task long-running (port scan completo)
3. 🔄 **API REST**: Separazione completa frontend/backend
4. 🔄 **Database**: Per logging, analytics, user preferences
5. 🔄 **OOP Refactor**: Class-based architecture per estensibilità

### Filosofia Finale

L'architettura di Controllo Domini privilegia:
- **Pragmatismo** su dogmatismo
- **Funzionalità** su complessità
- **Performance** su features
- **Semplicità** su over-engineering

È un'applicazione lean, focused, production-ready che fa bene ciò che deve fare senza overhead inutili.

---

**Ultimo aggiornamento**: Novembre 2025
**Versione architettura**: 4.0
