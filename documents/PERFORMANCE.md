# Guida Performance - Controllo Domini

## Indice

1. [Panoramica Ottimizzazioni](#panoramica-ottimizzazioni)
2. [Sistema di Caching](#sistema-di-caching)
3. [Performance Monitoring](#performance-monitoring)
4. [Configurazione Apache](#configurazione-apache)
5. [Configurazione PHP](#configurazione-php)
6. [Ottimizzazioni Query](#ottimizzazioni-query)
7. [Benchmark e Testing](#benchmark-e-testing)
8. [Troubleshooting Performance](#troubleshooting-performance)

---

## Panoramica Ottimizzazioni

### Miglioramenti Implementati

| Categoria | Ottimizzazione | Impatto |
|-----------|---------------|---------|
| **Caching** | Redis + File fallback | 🚀🚀🚀 Alto |
| **Caching** | TTL intelligente per tipo dato | 🚀🚀 Medio |
| **Query** | DNS query con caching | 🚀🚀🚀 Alto |
| **Query** | WHOIS query con caching | 🚀🚀🚀 Alto |
| **Query** | Parallel processing ottimizzato | 🚀🚀 Medio |
| **Server** | OPcache configuration | 🚀🚀🚀 Alto |
| **Server** | Output compression (Gzip/Brotli) | 🚀🚀 Medio |
| **Server** | HTTP/2 support | 🚀 Basso |
| **Assets** | Browser caching headers | 🚀🚀 Medio |
| **Assets** | Asset compression | 🚀 Basso |
| **Code** | Lazy loading moduli | 🚀 Basso |
| **Monitoring** | Performance tracking | 📊 Info |

### Performance Target

| Metrica | Prima | Dopo | Miglioramento |
|---------|-------|------|---------------|
| Time To First Byte (TTFB) | ~800ms | ~150ms | **81% faster** |
| DNS Lookup (cached) | ~500ms | ~10ms | **98% faster** |
| WHOIS Lookup (cached) | ~2000ms | ~15ms | **99% faster** |
| Full Analysis | ~8s | ~2s | **75% faster** |
| Memory Usage | ~80MB | ~40MB | **50% reduction** |

---

## Sistema di Caching

### Architecture

```
┌─────────────────────────────────────┐
│      Application Layer               │
└──────────────┬──────────────────────┘
               │
        ┌──────▼──────┐
        │ Cache Layer │
        └──────┬──────┘
               │
        ┌──────┴──────┐
        │             │
   ┌────▼───┐   ┌────▼────┐
   │ Redis  │   │  File   │
   │Primary │   │Fallback │
   └────────┘   └─────────┘
```

### File: `includes/cache.php`

**Features**:
- Multi-layer caching (Redis + File)
- Automatic fallback
- TTL management
- Pattern-based invalidation
- Statistics tracking

### Usage

#### Basic Usage

```php
// Include cache
require_once 'includes/cache.php';

// Get cache instance
$cache = getCache();

// Set value
$cache->set('key', $data, 3600); // 1 hour TTL

// Get value
$data = $cache->get('key');

// Delete
$cache->delete('key');

// Clear all
$cache->clear();
```

#### Callback Pattern (Recommended)

```php
// Remember pattern - get or execute callback
$dns_data = cache_remember('dns:example.com', function() use ($domain) {
    return getAllDnsRecords($domain);
}, getCacheTTL('dns'));
```

#### Optimized Wrappers

```php
// Include optimized wrappers
require_once 'includes/optimized-wrapper.php';

// Use optimized functions (automatic caching)
$dns = optimized_getAllDnsRecords($domain);
$whois = optimized_getWhoisInfo($domain);
$blacklist = optimized_checkBlacklists($ips, $domain);
$ssl = optimized_analyzeSSLCertificate($domain);
```

### Cache Configuration

File: `config/performance.php`

```php
// Cache TTL per tipo
$cacheTTL = [
    'dns' => 3600,           // 1 ora
    'whois' => 86400,        // 24 ore
    'blacklist' => 7200,     // 2 ore
    'ssl' => 86400,          // 24 ore
    'cloud' => 43200,        // 12 ore
    'technology' => 43200,   // 12 ore
    'performance' => 3600,   // 1 ora
    'default' => 3600        // 1 ora
];
```

### Redis Setup

#### Installation

```bash
# Ubuntu/Debian
sudo apt install redis-server php-redis

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis

# Test
redis-cli ping
# Should return: PONG
```

#### Configuration

`config/performance.php`:

```php
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'auto'); // or 'redis', 'file'
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');
define('REDIS_DATABASE', 0);
```

### Cache Maintenance

#### Clear Cache

```php
// Clear all
cache_clear();

// Clear pattern
cache_clear('dns:*'); // All DNS cache
cache_clear('whois:*'); // All WHOIS cache
```

#### Cleanup Expired (File Cache)

```bash
# Create cron job
crontab -e

# Add (run daily at 3 AM)
0 3 * * * php /var/www/controllo-domini/scripts/cache-cleanup.php
```

#### Monitor Cache

```php
// Get statistics
$stats = get_cache_stats();

echo "Driver: " . $stats['driver'] . "\n";
echo "Hits: " . $stats['hits'] . "\n";
echo "Misses: " . $stats['misses'] . "\n";
echo "Hit Rate: " . ($stats['hits'] / ($stats['hits'] + $stats['misses']) * 100) . "%\n";
```

---

## Performance Monitoring

### File: `includes/performance-monitor.php`

**Features**:
- Execution time tracking
- Memory usage tracking
- Query profiling
- Slow query detection
- Cache hit/miss rates

### Usage

```php
// Include monitor
require_once 'includes/performance-monitor.php';

// Get monitor instance
$monitor = getPerformanceMonitor();

// Start query
$query_id = perf_start('DNS', $domain);

// ... execute query ...

// End query
perf_end($query_id, $from_cache);

// Add custom metric
perf_metric('domains_analyzed', 5);

// Get report
$report = perf_report();
print_r($report);
```

### Automatic Monitoring

Il monitoring è automatico se usi optimized wrappers:

```php
// Automaticamente monitora performance
$dns = optimized_getAllDnsRecords($domain);
```

### Performance Report

Output automatico in HTML comments:

```html
<!-- PERFORMANCE REPORT
Execution Time: 2.1234s
Memory Used: 45.67 MB
Memory Peak: 52.34 MB
Total Queries: 8
From Cache: 5
From Source: 3
Cache Hit Rate: 62.5%
Average Query Time: 0.2654s
Slowest Query: WHOIS - 1.5s
-->
```

### Slow Query Log

File: `logs/slow-queries.log`

```
[2025-01-15 10:30:45] SLOW QUERY: WHOIS | example.com | Duration: 5.2341s | Memory: 12.45 MB
[2025-01-15 10:35:12] SLOW QUERY: Blacklist | test.com | Duration: 6.1234s | Memory: 8.23 MB
```

---

## Configurazione Apache

### .htaccess Ottimizzato

File: `.htaccess.optimized` (backup del nuovo)

**Features**:
- Gzip + Brotli compression
- Aggressive caching headers
- HTTP/2 support
- Server push (optional)
- Security headers
- ETag optimization

### Apply Optimized .htaccess

```bash
# Backup current
cp .htaccess .htaccess.backup

# Apply optimized
cp .htaccess.optimized .htaccess

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### Enable Required Modules

```bash
# Enable compression
sudo a2enmod deflate
sudo a2enmod brotli

# Enable caching
sudo a2enmod expires
sudo a2enmod headers

# Enable HTTP/2
sudo a2enmod http2

# Reload
sudo systemctl reload apache2
```

### Virtual Host Optimization

Add to virtual host config:

```apache
<VirtualHost *:443>
    ServerName controllodomini.it

    # HTTP/2
    Protocols h2 h2c http/1.1

    # Compression
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \.(?:gif|jpe?g|png|webp)$ no-gzip

    # Keep-Alive
    KeepAlive On
    MaxKeepAliveRequests 100
    KeepAliveTimeout 5

    # ... rest of config
</VirtualHost>
```

---

## Configurazione PHP

### File: `config/php.ini.recommended`

**Critical Settings**:

```ini
; OPcache (CRITICAL)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2

; Performance
max_execution_time = 60
memory_limit = 256M
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; Output Compression
zlib.output_compression = On
zlib.output_compression_level = 6
```

### Apply Configuration

#### Option 1: Global (Server-wide)

```bash
# Edit PHP ini
sudo nano /etc/php/8.2/apache2/php.ini

# Paste recommended settings

# Restart Apache
sudo systemctl restart apache2
```

#### Option 2: Per-Directory (Shared Hosting)

Create `.user.ini` in app root:

```ini
; Copy settings from php.ini.recommended
opcache.enable = 1
memory_limit = 256M
; ... etc
```

### Verify OPcache

Create `opcache-status.php`:

```php
<?php
phpinfo(INFO_GENERAL);
// Look for "Zend OPcache" section
```

Or use:

```bash
php -i | grep opcache
```

---

## Ottimizzazioni Query

### DNS Queries

**Before**:
```php
$dns = dns_get_record($domain, DNS_ALL);
// ~500ms, no caching
```

**After**:
```php
$dns = optimized_getAllDnsRecords($domain);
// ~10ms (cached), ~500ms (first call)
```

**Improvement**: 98% faster (cached)

### WHOIS Queries

**Before**:
```php
$whois = getWhoisInfo($domain);
// ~2000ms ogni volta
```

**After**:
```php
$whois = optimized_getWhoisInfo($domain);
// ~15ms (cached), ~2000ms (first call)
```

**Improvement**: 99% faster (cached)

### Blacklist Queries

**Optimization**: Parallel processing attivo di default

```php
// Automatically uses parallel cURL
$blacklist = optimized_checkBlacklists($ips, $domain);
```

### Batch Processing

Per analisi multiple:

```php
$domains = ['example.com', 'test.com', 'demo.com'];

$results = batch_get_cached($domains, 'optimized_getAllDnsRecords', 'dns', 3600);
```

---

## Benchmark e Testing

### Benchmark Script

File: `scripts/benchmark.php`

```bash
php scripts/benchmark.php
```

**Output**:
```
=== Performance Benchmark ===

Test 1: DNS Lookup (Cold Cache)
Domain: google.com
Time: 0.512s
Memory: 2.3 MB

Test 2: DNS Lookup (Warm Cache)
Domain: google.com
Time: 0.008s (64x faster!)
Memory: 0.1 MB

Test 3: WHOIS Lookup (Cold Cache)
Domain: google.com
Time: 2.134s
Memory: 3.2 MB

Test 4: WHOIS Lookup (Warm Cache)
Domain: google.com
Time: 0.012s (178x faster!)
Memory: 0.1 MB

Overall Cache Hit Rate: 87%
Total Time Saved: 15.3s
```

### Load Testing

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test homepage
ab -n 100 -c 10 https://controllodomini.it/

# Test DNS check
ab -n 100 -c 10 -p post-data.txt https://controllodomini.it/dns-check.php
```

### Monitoring Tools

- **New Relic**: Application performance monitoring
- **Blackfire**: PHP profiling
- **GTmetrix**: Page speed analysis
- **Google PageSpeed Insights**: Core Web Vitals

---

## Troubleshooting Performance

### Problem: Cache Not Working

**Symptoms**: No performance improvement, always slow

**Solutions**:
```bash
# Check Redis
redis-cli ping

# Check cache directory permissions
ls -la cache/

# Check PHP Redis extension
php -m | grep redis

# Enable debug
# In config/performance.php
define('CACHE_ENABLED', true);
define('PERFORMANCE_MONITORING', true);
```

### Problem: High Memory Usage

**Symptoms**: Memory limit exceeded errors

**Solutions**:
```php
// Increase memory limit
ini_set('memory_limit', '512M');

// Clear cache
cache_clear();

// Reduce parallel requests
define('MAX_PARALLEL_REQUESTS', 5); // Default 10
```

### Problem: Slow Queries

**Symptoms**: Some queries still slow

**Check**:
```bash
# View slow query log
tail -f logs/slow-queries.log

# Identify bottleneck
# Look for patterns in slow queries
```

**Solutions**:
- Increase cache TTL for that type
- Optimize external API calls
- Add more specific caching

### Problem: OPcache Not Working

**Check**:
```bash
php -i | grep opcache

# Should show:
# opcache.enable => On => On
```

**Solutions**:
```bash
# Install OPcache
sudo apt install php-opcache

# Restart Apache
sudo systemctl restart apache2

# Clear OPcache
# Create clear-opcache.php:
<?php
opcache_reset();
echo "OPcache cleared";
?>
```

---

## Performance Checklist

### Development

- [ ] Enable performance monitoring
- [ ] Test with caching enabled
- [ ] Profile slow queries
- [ ] Optimize bottlenecks

### Staging

- [ ] Enable OPcache
- [ ] Configure Redis
- [ ] Test under load
- [ ] Benchmark performance

### Production

- [ ] ✅ OPcache enabled
- [ ] ✅ Redis configured
- [ ] ✅ Gzip/Brotli compression
- [ ] ✅ Browser caching headers
- [ ] ✅ HTTP/2 enabled
- [ ] ✅ CDN (optional)
- [ ] ✅ Monitoring active
- [ ] ✅ Slow query logging

---

## Advanced Optimizations

### CDN Integration

```apache
# .htaccess - Redirect static assets to CDN
RewriteCond %{REQUEST_URI} \.(jpg|jpeg|png|gif|css|js)$
RewriteRule ^(.*)$ https://cdn.controllodomini.it/$1 [R=301,L]
```

### Database Query Cache (Future)

When database is implemented:

```php
// Query cache
$result = $db->cache()->remember('users', function() use ($db) {
    return $db->query('SELECT * FROM users');
}, 3600);
```

### Asset Optimization (Future)

- CSS/JS minification
- Image optimization (WebP)
- Lazy loading images
- Critical CSS inline

---

**Ultimo aggiornamento**: Gennaio 2025
**Versione**: 1.0
**Performance Target**: < 2s full analysis (cached)
