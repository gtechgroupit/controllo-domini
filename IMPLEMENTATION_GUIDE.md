# GUIDA DI IMPLEMENTAZIONE DELLE OTTIMIZZAZIONI
## Controllo Domini v4.2.1

## Indice Veloce

1. [Overview](#overview)
2. [Phase 1: Quick Wins (Week 1-2)](#phase-1-quick-wins)
3. [Phase 2: Major Optimizations (Week 3-4)](#phase-2-major-optimizations)
4. [Phase 3: Medium Priority (Week 5-6)](#phase-3-medium-priority)
5. [Testing & Verification](#testing--verification)
6. [Troubleshooting](#troubleshooting)

---

## Overview

Questo documento fornisce le istruzioni passo-passo per implementare le 34 ottimizzazioni identificate nel report.

**Impatto Complessivo:**
- Page Load Time: -40%
- Cache Hit Rate: +400%
- Database Throughput: +500%
- Time Estimate: 4-6 settimane

---

## PHASE 1: Quick Wins (Week 1-2)

### 1. Aggiungi Database Indices

**Tempo:** 5-10 minuti  
**Impatto:** +500% query throughput

**Steps:**

```bash
# 1. Connect al database
psql -U your_username -d your_database

# 2. Esegui gli indici
CREATE INDEX idx_bulk_scan_user_id ON bulk_scan_jobs(user_id);
CREATE INDEX idx_bulk_scan_status ON bulk_scan_jobs(status);
CREATE INDEX idx_bulk_scan_created ON bulk_scan_jobs(created_at DESC);
CREATE INDEX idx_bulk_scan_user_status ON bulk_scan_jobs(user_id, status, created_at DESC);

CREATE INDEX idx_bulk_tasks_job_id ON bulk_scan_tasks(bulk_scan_job_id);
CREATE INDEX idx_bulk_tasks_status ON bulk_scan_tasks(status);

CREATE INDEX idx_scan_domain_date ON scan_results(domain, scan_date DESC);
```

**Verifica:**
```bash
# Check indici creati
\d bulk_scan_jobs
\d bulk_scan_tasks

# Mostra statistiche
SELECT schemaname, tablename, indexname FROM pg_indexes WHERE tablename LIKE 'bulk%';
```

### 2. Minifica Assets

**Tempo:** 10-15 minuti  
**Impatto:** -70% asset transfer size

**Steps:**

```bash
# 1. Install tools
npm install -g cleancss terser

# 2. Minify CSS
cleancss -o assets/css/style.min.css assets/css/style.css
cleancss -o assets/css/modern-ui.min.css assets/css/modern-ui.css
cleancss -o assets/css/dark-mode.min.css assets/css/dark-mode.css

# 3. Minify JS
terser assets/js/main.js -o assets/js/main.min.js -c -m
terser assets/js/modern-ui.js -o assets/js/modern-ui.min.js -c -m
terser assets/js/dark-mode.js -o assets/js/dark-mode.min.js -c -m

# 4. Verify file sizes
ls -lh assets/css/*.min.css assets/js/*.min.js
```

**Update HTML templates to use minified versions:**

Edit `/templates/header.php`:
```php
// ✅ ADD conditional for minified assets
if (ENVIRONMENT === 'production' || OPTIMIZE_ASSETS) {
    // Use minified
    echo '<link rel="stylesheet" href="/assets/css/style.min.css">';
} else {
    // Use original for development
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
}
```

Or add in Web Server config:
```nginx
# nginx.conf
location ~* \.(css|js)$ {
    # Serve minified if exists
    if (-f $document_root$request_filename.min) {
        rewrite ^(.+)\.(css|js)$ $1.min.$2;
    }
    expires 31536000s;  # 1 year
    add_header Cache-Control "public, immutable";
}
```

### 3. Aggiungi Browser Cache Headers

**Tempo:** 5 minuti  
**Impatto:** 1-year browser cache

**Steps:**

Create new file `/includes/cache-headers.php`:

```php
<?php
/**
 * Set cache headers for static assets
 */

function setCacheHeaders($file_path = null) {
    if (!$file_path) {
        $file_path = $_SERVER['REQUEST_URI'];
    }
    
    // Assets cache for 1 year
    if (preg_match('/\.(jpg|jpeg|png|gif|svg|webp|css|js|woff|woff2)$/i', $file_path)) {
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . md5_file($file_path));
        exit;
    }
    
    // HTML pages - cache for 1 hour
    header('Cache-Control: public, max-age=3600');
    header('ETag: ' . md5($_SERVER['REQUEST_URI']));
    header('Vary: Accept-Encoding');
}

// Enable gzip compression
if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
    header('Content-Encoding: gzip');
}
?>
```

Call at top of each page:
```php
<?php
require_once 'includes/cache-headers.php';
setCacheHeaders();
?>
```

### 4. Fix DNS Duplicate Record Check

**Tempo:** 20-30 minuti  
**Impatto:** -500ms per scan

**File:** `/includes/dns-functions.php`  
**Lines:** 142-148

**Current Code:**
```php
function isDuplicateRecord($existing, $new) {
    foreach ($existing as $record) {
        if (json_encode($record) == json_encode($new)) {
            return true;
        }
    }
    return false;
}
```

**Replace With:**
```php
function isDuplicateRecord(&$seen_hashes, $new) {
    // Generate hash ONCE
    $new_key = md5(json_encode($new));
    
    // O(1) lookup instead of O(n)
    if (isset($seen_hashes[$new_key])) {
        return true;
    }
    
    $seen_hashes[$new_key] = true;
    return false;
}
```

**Update the function that calls this (getAllDnsRecords):**

Find this loop (around line 39-60):
```php
foreach ($domains_to_check as $check_domain) {
    $all_records = @dns_get_record($check_domain, DNS_ALL);
    
    if ($all_records !== false && !empty($all_records)) {
        foreach ($all_records as $record) {
            if (isset($record['type'])) {
                $type = $record['type'];
                if (!isset($results[$type])) {
                    $results[$type] = array();
                }
                
                // ✅ ADD hashing
                if (!isDuplicateRecord($seen_hashes, $record)) {
                    $results[$type][] = $record;
                }
            }
        }
    }
}
```

Add at function start:
```php
$seen_hashes = [];  // ✅ Track hashes instead of full records
```

**Test:**
```bash
# Test with a domain
curl "http://localhost/dns-check.php?domain=google.com"

# Check speed improved
time php -r "include 'includes/dns-functions.php'; getAllDnsRecords('google.com');"
```

### 5. Implement DNS Caching (7d TTL)

**Tempo:** 30-45 minuti  
**Impatto:** -70% DNS queries

**File:** `/includes/complete-scan.php`  
**Lines:** 77-86

**Current:**
```php
private function getDNS() {
    try {
        $cache_key = "complete_scan:dns:{$this->domain}";
        return $this->cache->remember($cache_key, function() {
            return getAllDnsRecords($this->domain);
        }, 3600);  // ❌ Only 1 hour
    }
}
```

**Update:**
```php
private function getDNS() {
    try {
        $cache_key = "dns:records:{$this->domain}";
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        $records = getAllDnsRecords($this->domain);
        
        // ✅ Smart TTL based on record types
        $ttl = $this->calculateDnsTTL($records);
        $this->cache->set($cache_key, $records, $ttl);
        
        return $records;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

private function calculateDnsTTL($records) {
    // Default 7 days
    $ttl = 604800;
    
    $type_ttls = [
        'NS' => 604800,      // 7 days
        'SOA' => 604800,     // 7 days
        'MX' => 604800,      // 7 days
        'TXT' => 86400,      // 1 day (SPF can change)
        'A' => 3600,         // 1 hour
        'AAAA' => 3600,      // 1 hour
    ];
    
    foreach ($records as $type => $list) {
        if (!empty($list) && isset($type_ttls[$type])) {
            $ttl = min($ttl, $type_ttls[$type]);
        }
    }
    
    return $ttl;
}
```

**Test:**
```bash
# Test caching
php -r "
include 'includes/cache.php';
include 'includes/dns-functions.php';
include 'includes/complete-scan.php';
\$scan = new CompleteScan('google.com');
\$dns1 = \$scan->getDNS();
echo 'First call: ' . count(\$dns1['records']) . ' records\n';

\$dns2 = \$scan->getDNS();  // Should be cached
echo 'Second call (cached): ' . count(\$dns2['records']) . ' records\n';
"
```

---

## PHASE 2: Major Optimizations (Week 3-4)

### 6. Fix WHOIS Socket Unbounded Loop

**Tempo:** 45 minutos  
**Impatto:** -45s max timeout risk

**File:** `/includes/whois-functions.php`  
**Lines:** 127-178

**Current Problematic Code:**
```php
while (!feof($fp)) {
    $out .= fgets($fp);  // ❌ No timeout, no size limit
}
```

**Updated Version:**
```php
function getWhoisViaSocket($domain, $timeout = 8, $max_size = 150000) {
    $tld = getTLD($domain);
    $whois_server = getWhoisServer($tld);
    
    if (!$whois_server) return false;
    
    // ✅ Connect with timeout
    $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
    if (!$fp) return false;
    
    // ✅ Set stream timeout
    stream_set_timeout($fp, $timeout);
    
    $query = "domain " . escapeshellarg($domain) . "\r\n";
    fputs($fp, $query);
    
    // ✅ BOUNDED LOOP with checks
    $out = '';
    $size = 0;
    $lines = 0;
    
    while (!feof($fp) && $size < $max_size && $lines < 1000) {
        $line = fgets($fp, 4096);
        if ($line === false) break;
        
        // ✅ Check timeout
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) break;
        
        $out .= $line;
        $size += strlen($line);
        $lines++;
    }
    
    fclose($fp);
    
    // ✅ Cache referral server
    $cache_key = "whois:referral:{$tld}";
    $referral = getCache()->get($cache_key);
    
    if (!$referral && preg_match('/Whois Server:\s*(.+)/i', $out, $matches)) {
        $referral = trim($matches[1]);
        getCache()->set($cache_key, $referral, 2592000);  // 30 days
    }
    
    return $out;
}
```

**Test:**
```bash
php -r "
include 'includes/whois-functions.php';
\$start = microtime(true);
\$result = getWhoisViaSocket('google.com');
echo 'Time: ' . (microtime(true) - \$start) . 's\n';
echo 'Size: ' . strlen(\$result) . ' bytes\n';
"
```

### 7. Implement WHOIS Parallel Requests

**Tempo:** 60 minutos  
**Impact:** -50s worst case

**File:** `/includes/whois-functions.php`  
**Lines:** 40-80

See PERFORMANCE_OPTIMIZATION_REPORT for complete code (too long to include here).

Key improvements:
- Parallel curl_multi execution
- Async socket connections
- Competitive racing (first to finish wins)
- Cache hit between fallback methods

---

## PHASE 3: Medium Priority (Week 5-6)

### 8. Cache Cleanup Optimization

**File:** `/includes/cache.php`  
**Lines:** 392-412

Replace the entire `cleanup()` function with batch processing version from report.

### 9. Fix Performance Analysis Multiple DOM Parsing

**File:** `/includes/performance-analysis.php`

Replace multiple HTML parsing calls with single DOMDocument parse.

### 10. Additional Optimizations

See PERFORMANCE_OPTIMIZATION_REPORT_v4.2.1.md for:
- Template compilation caching
- Email provider detector caching
- Array memory optimization
- And many more...

---

## Testing & Verification

### 1. Unit Tests

Create `/tests/performance-test.php`:

```php
<?php
require_once 'includes/dns-functions.php';
require_once 'includes/cache.php';

class PerformanceTest {
    public function testDnsDuplicateCheck() {
        $seen = [];
        $record1 = ['type' => 'A', 'ip' => '192.168.1.1'];
        $record2 = ['type' => 'A', 'ip' => '192.168.1.1'];
        
        $this->assertFalse(isDuplicateRecord($seen, $record1));
        $this->assertTrue(isDuplicateRecord($seen, $record2));
        echo "✓ DNS duplicate check test passed\n";
    }
    
    public function testDnsCaching() {
        $cache = getCache();
        $cache->set('test:dns', ['A' => ['ip' => '1.1.1.1']], 3600);
        
        $cached = $cache->get('test:dns');
        $this->assertNotNull($cached);
        echo "✓ DNS caching test passed\n";
    }
    
    public function testQueryPerformance() {
        $start = microtime(true);
        // Run query
        $time = microtime(true) - $start;
        
        $this->assertLessThan(0.050, $time);  // Less than 50ms
        echo "✓ Query performance test passed\n";
    }
}

$test = new PerformanceTest();
$test->testDnsDuplicateCheck();
$test->testDnsCaching();
$test->testQueryPerformance();
?>
```

Run:
```bash
php tests/performance-test.php
```

### 2. Load Testing

```bash
# Install Apache Bench
apt-get install apache2-utils

# Test homepage
ab -n 1000 -c 10 http://localhost/

# Test API
ab -n 100 -c 5 "http://localhost/api/v2/scan?domain=google.com" \
  -H "X-API-Key: your-key"
```

### 3. Lighthouse Audit

```bash
npm install -g lighthouse

lighthouse http://localhost --view
```

Target scores:
- Performance: > 90
- Accessibility: > 90
- Best Practices: > 90
- SEO: > 90

### 4. Database Query Monitoring

Enable slow query log:
```sql
SET log_min_duration_statement = 100;  -- Log queries > 100ms
```

Monitor:
```bash
tail -f /var/log/postgresql/postgresql.log | grep "duration:"
```

---

## Troubleshooting

### Issue: Assets not minifying

```bash
# Check cleancss installed
npm list -g cleancss

# Try with verbose
cleancss -d -o output.css input.css
```

### Issue: Database indices slow to create

On large tables, create indices in background:
```sql
CREATE INDEX CONCURRENTLY idx_name ON table(column);
```

### Issue: Cache not working

```bash
# Test Redis connection
redis-cli ping

# Check cache files
ls -la cache/
du -sh cache/

# Clear cache if needed
redis-cli FLUSHALL
rm -rf cache/*
```

### Issue: DNS queries still slow

```bash
# Check if caching working
php -r "
include 'includes/cache.php';
\$cache = getCache();
\$stats = \$cache->getStats();
print_r(\$stats);
"
```

---

## Monitoring & Metrics

### Key Metrics to Track

```php
// Add to your monitoring dashboard
$metrics = [
    'page_load_time' => $load_time,
    'cache_hit_rate' => $hits / ($hits + $misses),
    'dns_query_time' => $dns_time,
    'db_query_time' => $query_time,
    'peak_memory' => peak_memory_usage(),
];
```

### Performance Dashboard

Create `/admin/performance.php`:

```php
<?php
require_once 'includes/database.php';
require_once 'includes/cache.php';

$db = Database::getInstance();
$cache = getCache();

$stats = [
    'cache' => $cache->getStats(),
    'db' => $db->getQueryStats(),
    'uptime' => time() - $_SERVER['REQUEST_TIME'],
];

echo json_encode($stats);
?>
```

---

## Success Checklist

- [ ] Phase 1 completed and tested
- [ ] Database indices created and verified
- [ ] Assets minified and served correctly
- [ ] Cache headers in place
- [ ] DNS caching working (7d TTL)
- [ ] Phase 2 completed
- [ ] WHOIS timeouts fixed
- [ ] Parallel requests implemented
- [ ] Phase 3 completed
- [ ] All cleanup optimizations done
- [ ] Full test suite passing
- [ ] Lighthouse score > 90
- [ ] Load test successful (1000 requests, < 100ms avg)
- [ ] Cache hit rate > 75%
- [ ] Page load time < 2.1s

---

## Next Steps

1. Follow implementation in order (Phase 1 → 4)
2. Test thoroughly after each phase
3. Monitor metrics continuously
4. Document any issues found
5. Plan maintenance schedule for optimization updates

For detailed implementation guidance, refer to:
- `/PERFORMANCE_OPTIMIZATION_REPORT_v4.2.1.md` - Full technical details
- `/OPTIMIZATION_SUMMARY.txt` - Quick reference

