<?php
/**
 * Performance Configuration
 *
 * Configurazione ottimizzazioni performance:
 * - Cache TTL per tipo di dato
 * - Timeout queries
 * - Parallel processing
 * - Rate limiting
 *
 * @package ControlloDomin
 * @version 1.0
 */

// ============================================
// CACHE CONFIGURATION
// ============================================

// Cache abilitata (set to true in production)
define('CACHE_ENABLED', true);

// Cache driver: 'auto', 'redis', 'file'
define('CACHE_DRIVER', 'auto');

// Redis Configuration
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', ''); // Empty for no auth
define('REDIS_DATABASE', 0);

// File cache directory
define('CACHE_PATH', __DIR__ . '/../cache');

// Cache TTL (Time To Live) per tipo di dato (in secondi)
$cacheTTL = [
    // DNS cambiano raramente, ma meglio refresh ogni ora
    'dns' => 3600,           // 1 ora

    // WHOIS cambiano molto raramente
    'whois' => 86400,        // 24 ore

    // Blacklist può cambiare frequentemente
    'blacklist' => 7200,     // 2 ore

    // SSL certificates cambiano raramente
    'ssl' => 86400,          // 24 ore

    // Cloud detection abbastanza stabile
    'cloud' => 43200,        // 12 ore

    // Security headers possono cambiare
    'security_headers' => 21600,  // 6 ore

    // Technology detection stabile
    'technology' => 43200,   // 12 ore

    // Social meta può cambiare
    'social_meta' => 21600,  // 6 ore

    // Performance analysis necessita refresh frequente
    'performance' => 3600,   // 1 ora

    // SEO analysis
    'seo' => 21600,         // 6 ore

    // Redirects abbastanza stabili
    'redirects' => 21600,   // 6 ore

    // Port scan (pesante, cache lungo)
    'port_scan' => 86400,   // 24 ore

    // Default fallback
    'default' => 3600       // 1 ora
];

// ============================================
// QUERY TIMEOUTS
// ============================================

// Timeout per query esterne (in secondi)
$queryTimeouts = [
    'dns' => 5,              // DNS query
    'whois' => 10,           // WHOIS query
    'http' => 15,            // HTTP requests
    'ssl' => 10,             // SSL analysis
    'port_scan' => 2,        // Per porta (totale può essere lungo)
];

// ============================================
// PARALLEL PROCESSING
// ============================================

// Max richieste parallele simultanee
define('MAX_PARALLEL_REQUESTS', 10);

// Abilita parallel processing per blacklist
define('BLACKLIST_PARALLEL_ENABLED', true);

// ============================================
// PERFORMANCE LIMITS
// ============================================

// Max execution time (override php.ini)
define('MAX_EXECUTION_TIME', 60);

// Memory limit
define('MEMORY_LIMIT', '256M');

// Max sottodomini da scansionare
define('MAX_SUBDOMAINS_SCAN', 20);

// Max porte da scansionare
define('MAX_PORTS_SCAN', 50);

// ============================================
// OPCACHE SETTINGS
// ============================================

// OPcache configuration (applicare in php.ini)
$opcacheConfig = [
    'opcache.enable' => 1,
    'opcache.memory_consumption' => 128,
    'opcache.interned_strings_buffer' => 8,
    'opcache.max_accelerated_files' => 10000,
    'opcache.revalidate_freq' => 2,
    'opcache.fast_shutdown' => 1,
    'opcache.enable_cli' => 0,
    'opcache.save_comments' => 0,
];

// ============================================
// COMPRESSION
// ============================================

// Abilita output compression
define('OUTPUT_COMPRESSION', true);

// Compression level (1-9, 9 = max compression)
define('COMPRESSION_LEVEL', 6);

// ============================================
// LAZY LOADING
// ============================================

// Abilita lazy loading moduli
define('LAZY_LOADING_ENABLED', true);

// Moduli da caricare sempre
$alwaysLoadModules = [
    'utilities',
    'cache'
];

// ============================================
// RATE LIMITING (Performance Protection)
// ============================================

// Rate limit abilitato
define('RATE_LIMIT_ENABLED', false); // Set true in production

// Richieste per periodo
define('RATE_LIMIT_REQUESTS', 100);

// Periodo in secondi
define('RATE_LIMIT_PERIOD', 3600);

// ============================================
// PERFORMANCE MONITORING
// ============================================

// Abilita performance monitoring
define('PERFORMANCE_MONITORING', true);

// Log slow queries (in secondi)
define('SLOW_QUERY_THRESHOLD', 5.0);

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get cache TTL for type
 *
 * @param string $type Cache type
 * @return int TTL in seconds
 */
function getCacheTTL($type) {
    global $cacheTTL;
    return $cacheTTL[$type] ?? $cacheTTL['default'];
}

/**
 * Get query timeout for type
 *
 * @param string $type Query type
 * @return int Timeout in seconds
 */
function getQueryTimeout($type) {
    global $queryTimeouts;
    return $queryTimeouts[$type] ?? 10;
}

/**
 * Apply performance settings
 */
function applyPerformanceSettings() {
    // Set execution time
    if (defined('MAX_EXECUTION_TIME')) {
        set_time_limit(MAX_EXECUTION_TIME);
    }

    // Set memory limit
    if (defined('MEMORY_LIMIT')) {
        ini_set('memory_limit', MEMORY_LIMIT);
    }

    // Enable output compression
    if (defined('OUTPUT_COMPRESSION') && OUTPUT_COMPRESSION) {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
    }
}

// Apply settings immediately
applyPerformanceSettings();
