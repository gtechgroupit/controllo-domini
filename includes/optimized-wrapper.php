<?php
/**
 * Optimized Function Wrappers
 *
 * Wrapper ottimizzati con caching e performance monitoring
 * per le funzioni più chiamate dell'applicazione
 *
 * @package ControlloDomin
 * @version 1.0
 */

// Include dipendenze se non già caricate
if (!function_exists('getCache')) {
    require_once __DIR__ . '/cache.php';
}

if (!function_exists('getPerformanceMonitor')) {
    require_once __DIR__ . '/performance-monitor.php';
}

if (!file_exists(__DIR__ . '/../config/performance.php')) {
    // Fallback se performance.php non esiste
    function getCacheTTL($type) {
        $defaults = [
            'dns' => 3600,
            'whois' => 86400,
            'blacklist' => 7200,
            'ssl' => 86400,
            'default' => 3600
        ];
        return $defaults[$type] ?? $defaults['default'];
    }
}

/**
 * Optimized DNS Lookup con caching
 *
 * @param string $domain Domain name
 * @return array|false DNS records or false
 */
function optimized_getAllDnsRecords($domain) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    // Cache key
    $cache_key = 'dns:' . $domain;

    // Start performance monitoring
    $query_id = $monitor->startQuery('DNS', $domain);

    // Try cache first
    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    // Load original function se non già caricata
    if (!function_exists('getAllDnsRecords')) {
        require_once __DIR__ . '/dns-functions.php';
    }

    // Execute original function
    $result = getAllDnsRecords($domain);

    // Cache result
    $ttl = getCacheTTL('dns');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Optimized WHOIS Lookup con caching
 *
 * @param string $domain Domain name
 * @return array|false WHOIS data or false
 */
function optimized_getWhoisInfo($domain) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    // Cache key
    $cache_key = 'whois:' . $domain;

    // Start monitoring
    $query_id = $monitor->startQuery('WHOIS', $domain);

    // Try cache first
    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    // Load original function
    if (!function_exists('getWhoisInfo')) {
        require_once __DIR__ . '/whois-functions.php';
    }

    // Execute
    $result = getWhoisInfo($domain);

    // Cache (WHOIS changes rarely, use long TTL)
    $ttl = getCacheTTL('whois');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Optimized Blacklist Check con caching
 *
 * @param array $ips IP addresses
 * @param string $domain Domain name
 * @return array Blacklist results
 */
function optimized_checkBlacklists($ips, $domain) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    // Cache key basato su IP e domain
    $cache_key = 'blacklist:' . md5(serialize($ips) . $domain);

    // Start monitoring
    $query_id = $monitor->startQuery('Blacklist', $domain);

    // Try cache
    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    // Load function
    if (!function_exists('checkBlacklists')) {
        require_once __DIR__ . '/blacklist-functions.php';
    }

    // Execute
    $result = checkBlacklists($ips, $domain);

    // Cache
    $ttl = getCacheTTL('blacklist');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Optimized SSL Certificate Analysis con caching
 *
 * @param string $domain Domain name
 * @param int $port Port (default 443)
 * @return array|false SSL info or false
 */
function optimized_analyzeSSLCertificate($domain, $port = 443) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    // Cache key
    $cache_key = 'ssl:' . $domain . ':' . $port;

    // Start monitoring
    $query_id = $monitor->startQuery('SSL', $domain);

    // Try cache
    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    // Load function
    if (!function_exists('analyzeSSLCertificate')) {
        require_once __DIR__ . '/ssl-certificate.php';
    }

    // Execute
    $result = analyzeSSLCertificate($domain, $port);

    // Cache (SSL certificates change rarely)
    $ttl = getCacheTTL('ssl');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Optimized Cloud Services Detection con caching
 *
 * @param string $domain Domain name
 * @return array Cloud services
 */
function optimized_identifyCloudServices($domain) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    $cache_key = 'cloud:' . $domain;
    $query_id = $monitor->startQuery('Cloud', $domain);

    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    if (!function_exists('identifyCloudServices')) {
        require_once __DIR__ . '/cloud-detection.php';
    }

    $result = identifyCloudServices($domain);

    $ttl = getCacheTTL('cloud');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Optimized Technology Detection con caching
 *
 * @param string $url URL to analyze
 * @return array Technologies
 */
function optimized_detectTechnologyStack($url) {
    $cache = getCache();
    $monitor = getPerformanceMonitor();

    $cache_key = 'tech:' . md5($url);
    $query_id = $monitor->startQuery('Technology', $url);

    $cached = $cache->get($cache_key);
    if ($cached !== null) {
        $monitor->endQuery($query_id, true);
        return $cached;
    }

    if (!function_exists('detectTechnologyStack')) {
        require_once __DIR__ . '/technology-detection.php';
    }

    $result = detectTechnologyStack($url);

    $ttl = getCacheTTL('technology');
    $cache->set($cache_key, $result, $ttl);

    $monitor->endQuery($query_id, false);

    return $result;
}

/**
 * Batch get with caching (multiple domains)
 *
 * @param array $domains Array of domains
 * @param callable $function Function to call
 * @param string $cache_prefix Cache key prefix
 * @param int $ttl TTL
 * @return array Results keyed by domain
 */
function batch_get_cached($domains, $function, $cache_prefix, $ttl = 3600) {
    $cache = getCache();
    $results = [];

    foreach ($domains as $domain) {
        $cache_key = $cache_prefix . ':' . $domain;

        // Try cache
        $cached = $cache->get($cache_key);
        if ($cached !== null) {
            $results[$domain] = $cached;
            continue;
        }

        // Execute function
        $result = $function($domain);

        // Cache
        $cache->set($cache_key, $result, $ttl);

        $results[$domain] = $result;
    }

    return $results;
}

/**
 * Invalidate cache for domain
 *
 * @param string $domain Domain name
 * @param string|null $type Specific type or null for all
 * @return bool Success
 */
function invalidate_domain_cache($domain, $type = null) {
    $cache = getCache();

    if ($type) {
        // Invalidate specific type
        $cache->delete($type . ':' . $domain);
    } else {
        // Invalidate all types for domain
        $types = ['dns', 'whois', 'blacklist', 'ssl', 'cloud', 'tech', 'social', 'performance', 'seo'];
        foreach ($types as $t) {
            $cache->delete($t . ':' . $domain);
        }
    }

    return true;
}

/**
 * Preload cache for domain (warm up)
 *
 * @param string $domain Domain to warm up
 * @param array $types Types to preload (default: all)
 * @return array Results
 */
function preload_domain_cache($domain, $types = ['dns', 'whois']) {
    $results = [];

    if (in_array('dns', $types)) {
        $results['dns'] = optimized_getAllDnsRecords($domain);
    }

    if (in_array('whois', $types)) {
        $results['whois'] = optimized_getWhoisInfo($domain);
    }

    if (in_array('ssl', $types)) {
        $results['ssl'] = optimized_analyzeSSLCertificate($domain);
    }

    if (in_array('cloud', $types)) {
        $results['cloud'] = optimized_identifyCloudServices($domain);
    }

    return $results;
}

/**
 * Get cache statistics
 *
 * @return array Stats
 */
function get_cache_stats() {
    return getCache()->getStats();
}

/**
 * Clear all application cache
 *
 * @param string|null $pattern Pattern or null for all
 * @return bool Success
 */
function clear_app_cache($pattern = null) {
    return getCache()->clear($pattern);
}
