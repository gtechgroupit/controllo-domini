# Guida Sviluppo - Controllo Domini

## Indice

1. [Setup Ambiente Development](#setup-ambiente-development)
2. [Coding Standards](#coding-standards)
3. [Architettura Codice](#architettura-codice)
4. [Aggiungere Nuove Funzionalità](#aggiungere-nuove-funzionalità)
5. [Testing](#testing)
6. [Debugging](#debugging)
7. [Git Workflow](#git-workflow)
8. [Contribuire](#contribuire)

## Setup Ambiente Development

### Requisiti Development

```bash
# Installazioni consigliate
- PHP 8.2+ con estensioni
- Apache 2.4+ o Nginx
- Git
- Editor: VS Code, PHPStorm, Sublime Text
- Browser: Chrome/Firefox con DevTools
- Postman o curl per API testing
```

### Local Setup

```bash
# 1. Clone repository
git clone https://github.com/gtechgroup/controllo-domini.git
cd controllo-domini

# 2. Configura virtual host (vedi INSTALLATION.md)

# 3. Configura ambiente development
cp config/config.php config/config.local.php

# Modifica config.local.php:
define('APP_ENV', 'development');
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### VS Code Setup

**Estensioni raccomandate**:

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "xdebug.php-debug",
    "editorconfig.editorconfig",
    "streetsidesoftware.code-spell-checker",
    "esbenp.prettier-vscode"
  ]
}
```

**.vscode/settings.json**:

```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "php.suggest.basic": true,
  "editor.formatOnSave": true,
  "files.associations": {
    "*.php": "php"
  },
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  }
}
```

### Xdebug Setup

**php.ini**:

```ini
[Xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
xdebug.client_host=127.0.0.1
```

**.vscode/launch.json**:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/controllo-domini": "${workspaceFolder}"
      }
    }
  ]
}
```

## Coding Standards

### PSR-12 Compatible (Adapted)

Controllo Domini segue PSR-12 con adattamenti per procedural code:

#### 1. File Structure

```php
<?php
/**
 * File description
 *
 * @package    ControlloDomin
 * @subpackage Includes
 * @author     G Tech Group
 * @copyright  2025 G Tech Group
 */

// Require dependencies
require_once __DIR__ . '/utilities.php';

// Constants
define('MODULE_VERSION', '1.0');

// Functions (alphabetical order preferred)
function functionA() { }
function functionB() { }

// EOF - no closing ?> tag
```

#### 2. Naming Conventions

```php
// Functions: camelCase
function getAllDnsRecords($domain) { }
function getWhoisInfo($domain) { }

// Constants: UPPER_SNAKE_CASE
define('APP_NAME', 'Controllo Domini');
define('DNS_TIMEOUT', 5);

// Variables: camelCase
$domainName = 'example.com';
$dnsRecords = [];

// Arrays: descriptive
$whoisServers = [ ];
$dnsblServers = [ ];

// Temporary/loop vars: short
for ($i = 0; $i < count($items); $i++) { }
foreach ($items as $item) { }
```

#### 3. Indentation & Spacing

```php
// 4 spaces (NO tabs)
function example() {
    if ($condition) {
        // indented 4 spaces
        doSomething();
    }
}

// Spaces around operators
$result = $a + $b;
$isValid = ($x === $y);

// No space after function name
functionName($arg1, $arg2);

// Space after keywords
if ($condition) { }
foreach ($array as $item) { }
while ($condition) { }
```

#### 4. Braces Style

```php
// Opening brace on same line
function example() {
    // code
}

if ($condition) {
    // code
} else {
    // code
}

// One-liner acceptable for simple cases
if ($simple) return true;
```

#### 5. Comments

```php
/**
 * Function description (multi-line doc comment)
 *
 * Detailed explanation if needed.
 *
 * @param string $domain Domain name to analyze
 * @param array $options Optional parameters
 * @return array|false DNS records or false on failure
 */
function getAllDnsRecords($domain, $options = []) {
    // Single-line comment for implementation details
    $records = [];

    // Another comment
    foreach ($types as $type) {
        // Inline comment
        $result = dns_get_record($domain, $type); // End-of-line comment
    }

    return $records;
}
```

#### 6. Error Handling

```php
// Validate inputs first
if (!validateDomain($domain)) {
    return false;
}

// Use ternary for simple defaults
$timeout = $options['timeout'] ?? DNS_TIMEOUT;

// Suppress errors only when necessary, with fallback
$result = @dns_get_record($domain);
if ($result === false) {
    // Handle error
    logError("DNS query failed for: $domain");
    return [];
}
```

### HTML/CSS Standards

```html
<!-- HTML: lowercase, double quotes -->
<div class="container" id="main-content">
    <h1>Title</h1>
</div>

<!-- PHP in HTML: short tags OK for echo -->
<div><?php echo $title; ?></div>
<div><?= safeHtmlspecialchars($content); ?></div>

<!-- Multi-line PHP -->
<?php
if ($condition) {
    echo '<p>Content</p>';
}
?>
```

```css
/* CSS: kebab-case */
.main-container {
    display: flex;
    justify-content: center;
}

.dns-record {
    padding: 10px;
    margin-bottom: 15px;
}

/* Use CSS variables */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
}
```

### JavaScript Standards

```javascript
// ES6+ syntax
const domain = 'example.com';
let results = [];

// Functions: camelCase
function analyzeDomain(domain) {
    // code
}

// Arrow functions
const processData = (data) => {
    return data.map(item => item.value);
};

// Promises & async/await
async function fetchData(url) {
    try {
        const response = await fetch(url);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
}

// Constants: UPPER_SNAKE_CASE
const API_BASE_URL = 'https://api.example.com';
const MAX_RETRIES = 3;
```

## Architettura Codice

### Struttura Modulo

Ogni modulo in `/includes/` segue questo pattern:

```php
<?php
/**
 * Module Name
 *
 * Description of module purpose
 *
 * @package ControlloDomin
 */

// ===========================================
// CONFIGURATION
// ===========================================

// Module-specific constants
define('MODULE_TIMEOUT', 10);

// ===========================================
// MAIN FUNCTIONS
// ===========================================

/**
 * Main entry point function
 */
function mainAnalysisFunction($domain) {
    // 1. Validate input
    if (!validateDomain($domain)) {
        return false;
    }

    // 2. Perform analysis
    $results = [];

    // 3. Call helper functions
    $results['data'] = fetchData($domain);
    $results['processed'] = processData($results['data']);

    // 4. Return structured results
    return $results;
}

// ===========================================
// HELPER FUNCTIONS
// ===========================================

/**
 * Helper function 1
 */
function fetchData($domain) {
    // Implementation
}

/**
 * Helper function 2
 */
function processData($data) {
    // Implementation
}

// ===========================================
// UTILITY FUNCTIONS
// ===========================================

/**
 * Formatting function
 */
function formatOutput($data) {
    // Implementation
}
```

### Dependency Injection

Evita dipendenze globali:

```php
// ❌ BAD: global dependency
function analyzeSSL($domain) {
    global $config; // Avoid
    $timeout = $config['ssl_timeout'];
}

// ✅ GOOD: pass dependencies
function analyzeSSL($domain, $timeout = null) {
    $timeout = $timeout ?? SSL_ANALYSIS_TIMEOUT;
}

// ✅ BETTER: configuration object
function analyzeSSL($domain, $options = []) {
    $timeout = $options['timeout'] ?? SSL_ANALYSIS_TIMEOUT;
    $verify = $options['verify_cert'] ?? true;
}
```

### Return Values Consistency

```php
// Consistent return types
function getData($domain) {
    // Success: return array
    if ($success) {
        return ['data' => $result, 'status' => 'ok'];
    }

    // Failure: return false or empty array (be consistent)
    return false; // or return [];
}

// Document return type
/**
 * @return array|false Array on success, false on failure
 */
function getData($domain) {
    // ...
}
```

## Aggiungere Nuove Funzionalità

### 1. Creare Nuovo Modulo

**Esempio**: Aggiungere "HTTP/2 Analysis"

#### Step 1: Crea file modulo

```bash
touch includes/http2-analysis.php
```

#### Step 2: Implementa funzioni

```php
<?php
/**
 * HTTP/2 Analysis Module
 *
 * Analyzes HTTP/2 support and configuration
 *
 * @package ControlloDomin
 */

define('HTTP2_TIMEOUT', 10);

/**
 * Analyze HTTP/2 support
 *
 * @param string $domain Domain to analyze
 * @return array Analysis results
 */
function analyzeHTTP2($domain) {
    // Validate
    if (!validateDomain($domain)) {
        return ['error' => 'Invalid domain'];
    }

    $results = [
        'domain' => $domain,
        'http2_supported' => false,
        'http2_server_push' => false,
        'protocol_version' => null
    ];

    // Check HTTP/2 support
    $ch = curl_init("https://$domain");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => HTTP2_TIMEOUT,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
    ]);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);

    if ($info['http_version'] === CURL_HTTP_VERSION_2_0) {
        $results['http2_supported'] = true;
        $results['protocol_version'] = 'HTTP/2';
    }

    curl_close($ch);

    // Check Server Push support
    // (implementation details...)

    return $results;
}

function checkServerPush($domain) {
    // Implementation
}
```

#### Step 3: Crea pagina frontend

```bash
touch http2-analysis.php
```

```php
<?php
require_once 'bootstrap.php';
require_once 'config/config.php';
require_once 'includes/utilities.php';
require_once 'includes/http2-analysis.php';

require_once 'templates/header.php';
?>

<div class="container">
    <h1>HTTP/2 Analysis</h1>

    <form method="post">
        <input type="text" name="domain" placeholder="example.com" required>
        <button type="submit" name="analyze">Analyze</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
        $domain = $_POST['domain'] ?? '';

        if ($domain) {
            $results = analyzeHTTP2($domain);

            if (!isset($results['error'])) {
                ?>
                <div class="results">
                    <h2>Results for <?= safeHtmlspecialchars($domain); ?></h2>
                    <p>HTTP/2 Supported: <?= $results['http2_supported'] ? '✅ Yes' : '❌ No'; ?></p>
                    <p>Protocol: <?= safeHtmlspecialchars($results['protocol_version'] ?? 'HTTP/1.1'); ?></p>
                </div>
                <?php
            } else {
                echo '<div class="error">' . safeHtmlspecialchars($results['error']) . '</div>';
            }
        }
    }
    ?>
</div>

<?php
require_once 'templates/footer.php';
?>
```

#### Step 4: Aggiungi routing

**.htaccess**:

```apache
# HTTP/2 Analysis
RewriteRule ^http2-analysis$ http2-analysis.php [L]
```

#### Step 5: Aggiungi link navigazione

**templates/header.php**:

```php
<nav>
    <!-- ... existing links ... -->
    <a href="/http2-analysis">HTTP/2 Analysis</a>
</nav>
```

### 2. Estendere Funzionalità Esistente

**Esempio**: Aggiungere nuovo DNSBL server

**config/config.php**:

```php
$dnsblServers = [
    // ... existing servers ...
    'new-blacklist.example.org',  // Aggiungi nuovo server
];
```

Test:

```bash
# Test manualmente
dig 4.3.2.1.new-blacklist.example.org

# Test via applicazione
curl -X POST http://localhost/blacklist-check.php \
  -d "domain=example.com&analyze=1"
```

## Testing

### Manual Testing

```bash
# Test DNS lookup
curl -X POST http://localhost/dns-check.php \
  -d "domain=google.com&analyze=1"

# Test WHOIS
curl -X POST http://localhost/whois-lookup.php \
  -d "domain=google.com&analyze=1"

# Test con debug
curl "http://localhost/dns-check.php?domain=google.com&analyze=1&debug=1"
```

### PHP Unit Testing (Future)

Preparazione per unit testing:

```php
// tests/DnsTest.php
<?php
use PHPUnit\Framework\TestCase;

class DnsTest extends TestCase {
    public function testGetAllDnsRecords() {
        $domain = 'google.com';
        $records = getAllDnsRecords($domain);

        $this->assertIsArray($records);
        $this->assertArrayHasKey('A', $records);
        $this->assertNotEmpty($records['A']);
    }

    public function testValidateDomain() {
        $this->assertEquals('example.com', validateDomain('example.com'));
        $this->assertEquals('example.com', validateDomain('EXAMPLE.COM'));
        $this->assertFalse(validateDomain('invalid..domain'));
        $this->assertFalse(validateDomain(''));
    }
}
```

Run tests:

```bash
./vendor/bin/phpunit tests/
```

### Integration Testing

```php
// tests/Integration/FullAnalysisTest.php
class FullAnalysisTest extends TestCase {
    public function testFullDomainAnalysis() {
        $domain = 'google.com';

        // DNS
        $dns = getAllDnsRecords($domain);
        $this->assertIsArray($dns);

        // WHOIS
        $whois = getWhoisInfo($domain);
        $this->assertIsArray($whois);
        $this->assertArrayHasKey('registrar', $whois);

        // Blacklist
        $ips = getIpAddresses($domain);
        $blacklist = checkBlacklists($ips, $domain);
        $this->assertIsArray($blacklist);
    }
}
```

## Debugging

### Error Logging

```php
// Enable detailed errors (development only!)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/logs/error-' . date('Y-m-d') . '.log';
    $message = "[" . date('Y-m-d H:i:s') . "] [$errno] $errstr in $errfile:$errline\n";
    file_put_contents($logFile, $message, FILE_APPEND);

    // Display in dev mode
    if (DEBUG_MODE) {
        echo "<pre>Error: $errstr in $errfile:$errline</pre>";
    }
}

set_error_handler('customErrorHandler');
```

### Debug Helper Functions

```php
// includes/utilities.php

/**
 * Debug dump
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Debug dump (no die)
 */
function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

/**
 * Debug log to file
 */
function debug_log($message, $var = null) {
    if (!DEBUG_MODE) return;

    $logFile = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $message\n";

    if ($var !== null) {
        $entry .= print_r($var, true) . "\n";
    }

    file_put_contents($logFile, $entry, FILE_APPEND);
}
```

Usage:

```php
// Quick debug
dd($dnsRecords); // Dump and die

// Continue execution
dump($whoisData);

// Log to file
debug_log('DNS query result', $records);
```

### Browser DevTools

```javascript
// main.js - Console logging
console.log('Domain:', domain);
console.table(dnsRecords);
console.time('analysis');
// ... code ...
console.timeEnd('analysis');
```

## Git Workflow

### Branch Strategy

```
main (production)
  ↑
develop (integration)
  ↑
feature/new-feature (feature branches)
hotfix/bug-fix (hotfix branches)
```

### Workflow

```bash
# 1. Create feature branch
git checkout develop
git pull origin develop
git checkout -b feature/http2-analysis

# 2. Develop feature
# ... make changes ...
git add .
git commit -m "feat: add HTTP/2 analysis module"

# 3. Push to remote
git push origin feature/http2-analysis

# 4. Create Pull Request on GitHub
# ... code review ...

# 5. Merge to develop
git checkout develop
git merge feature/http2-analysis

# 6. Delete feature branch
git branch -d feature/http2-analysis
git push origin --delete feature/http2-analysis
```

### Commit Message Convention

Segui [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting, missing semicolons, etc.
- `refactor`: Code restructuring
- `perf`: Performance improvements
- `test`: Adding tests
- `chore`: Updating build tasks, package manager configs, etc.

**Examples**:

```bash
git commit -m "feat(dns): add IPv6 support"
git commit -m "fix(whois): handle timeout errors"
git commit -m "docs: update API documentation"
git commit -m "perf(blacklist): optimize parallel queries"
```

## Contribuire

### Pull Request Process

1. **Fork** repository
2. **Create** feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** changes (`git commit -m 'feat: add amazing feature'`)
4. **Push** to branch (`git push origin feature/amazing-feature`)
5. **Open** Pull Request

### PR Checklist

- [ ] Code segue coding standards
- [ ] Funzionalità testata manualmente
- [ ] Documentazione aggiornata
- [ ] CHANGELOG.md aggiornato
- [ ] No conflitti con `develop`
- [ ] Commit messages seguono convention
- [ ] Code review richiesta

### Code Review Guidelines

**Reviewer**:
- Controlla coding standards
- Verifica logic errors
- Suggerisci miglioramenti
- Approva o richiedi modifiche

**Author**:
- Risponde a feedback
- Implementa modifiche richieste
- Aggiorna PR

---

**Ultimo aggiornamento**: Novembre 2025
**Versione guida**: 1.0
