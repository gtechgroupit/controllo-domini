<?php
/**
 * Performance Benchmark Script
 *
 * Tests performance improvements with and without caching
 *
 * Usage: php scripts/benchmark.php
 */

// Setup paths
define('ABSPATH', dirname(__DIR__) . '/');

// Include dependencies
require_once ABSPATH . 'config/config.php';
require_once ABSPATH . 'config/performance.php';
require_once ABSPATH . 'includes/utilities.php';
require_once ABSPATH . 'includes/cache.php';
require_once ABSPATH . 'includes/performance-monitor.php';
require_once ABSPATH . 'includes/optimized-wrapper.php';

// Test domains
$test_domains = [
    'google.com',
    'github.com',
    'cloudflare.com'
];

/**
 * Format time
 */
function formatTime($seconds) {
    if ($seconds < 0.001) {
        return round($seconds * 1000000, 2) . 'μs';
    } elseif ($seconds < 1) {
        return round($seconds * 1000, 2) . 'ms';
    } else {
        return round($seconds, 3) . 's';
    }
}

/**
 * Format memory
 */
function formatMemory($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Run benchmark test
 */
function runBenchmark($name, $callback, $iterations = 1) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Test: $name\n";
    echo str_repeat("=", 60) . "\n";

    $times = [];
    $memories = [];

    for ($i = 0; $i < $iterations; $i++) {
        // Clear PHP's internal caches
        clearstatcache();

        // Measure
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        $result = $callback();

        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        $duration = $end_time - $start_time;
        $memory_used = $end_memory - $start_memory;

        $times[] = $duration;
        $memories[] = $memory_used;

        if ($iterations == 1) {
            echo "Time: " . formatTime($duration) . "\n";
            echo "Memory: " . formatMemory($memory_used) . "\n";
        }
    }

    if ($iterations > 1) {
        $avg_time = array_sum($times) / count($times);
        $avg_memory = array_sum($memories) / count($memories);

        echo "Iterations: $iterations\n";
        echo "Average Time: " . formatTime($avg_time) . "\n";
        echo "Average Memory: " . formatMemory($avg_memory) . "\n";
        echo "Min Time: " . formatTime(min($times)) . "\n";
        echo "Max Time: " . formatTime(max($times)) . "\n";
    }

    return [
        'times' => $times,
        'memories' => $memories,
        'avg_time' => array_sum($times) / count($times),
        'avg_memory' => array_sum($memories) / count($memories)
    ];
}

// ============================================
// MAIN BENCHMARK
// ============================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  CONTROLLO DOMINI - PERFORMANCE BENCHMARK                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";

echo "\nPHP Version: " . phpversion() . "\n";
echo "OPcache: " . (function_exists('opcache_get_status') && opcache_get_status() !== false ? 'Enabled' : 'Disabled') . "\n";
echo "Redis: " . (class_exists('Redis') ? 'Available' : 'Not Available') . "\n";

// Clear cache before starting
echo "\nClearing cache...";
cache_clear();
echo " Done!\n";

$all_results = [];

// ============================================
// TEST 1: DNS Lookup - Cold Cache
// ============================================

$domain = $test_domains[0];

$result = runBenchmark(
    "DNS Lookup (Cold Cache) - $domain",
    function() use ($domain) {
        require_once ABSPATH . 'includes/dns-functions.php';
        return getAllDnsRecords($domain);
    }
);

$all_results['dns_cold'] = $result;
sleep(1);

// ============================================
// TEST 2: DNS Lookup - Warm Cache (via optimized wrapper)
// ============================================

$result = runBenchmark(
    "DNS Lookup (Warm Cache) - $domain",
    function() use ($domain) {
        return optimized_getAllDnsRecords($domain);
    }
);

$all_results['dns_warm'] = $result;

// Calculate improvement
$cold_time = $all_results['dns_cold']['avg_time'];
$warm_time = $all_results['dns_warm']['avg_time'];
$improvement = round($cold_time / $warm_time, 1);
echo "Cache Improvement: {$improvement}x faster!\n";

sleep(1);

// ============================================
// TEST 3: WHOIS Lookup - Cold Cache
// ============================================

echo "\n";
$result = runBenchmark(
    "WHOIS Lookup (Cold Cache) - $domain",
    function() use ($domain) {
        require_once ABSPATH . 'includes/whois-functions.php';
        return getWhoisInfo($domain);
    }
);

$all_results['whois_cold'] = $result;
sleep(1);

// ============================================
// TEST 4: WHOIS Lookup - Warm Cache
// ============================================

$result = runBenchmark(
    "WHOIS Lookup (Warm Cache) - $domain",
    function() use ($domain) {
        return optimized_getWhoisInfo($domain);
    }
);

$all_results['whois_warm'] = $result;

// Calculate improvement
$cold_time = $all_results['whois_cold']['avg_time'];
$warm_time = $all_results['whois_warm']['avg_time'];
$improvement = round($cold_time / $warm_time, 1);
echo "Cache Improvement: {$improvement}x faster!\n";

sleep(1);

// ============================================
// TEST 5: Multiple DNS Lookups (Batch)
// ============================================

echo "\n";
$result = runBenchmark(
    "Batch DNS Lookups (" . count($test_domains) . " domains)",
    function() use ($test_domains) {
        $results = [];
        foreach ($test_domains as $domain) {
            $results[$domain] = optimized_getAllDnsRecords($domain);
        }
        return $results;
    }
);

$all_results['batch_dns'] = $result;

// ============================================
// TEST 6: Cache Performance
// ============================================

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "Test: Cache Read/Write Performance\n";
echo str_repeat("=", 60) . "\n";

$cache = getCache();
$iterations = 1000;

// Write test
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->set("benchmark_key_$i", ['data' => str_repeat('x', 100)], 3600);
}
$write_time = microtime(true) - $start;
$write_ops_per_sec = round($iterations / $write_time);

echo "Write Operations: $iterations in " . formatTime($write_time) . "\n";
echo "Write Speed: $write_ops_per_sec ops/sec\n";

// Read test
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->get("benchmark_key_$i");
}
$read_time = microtime(true) - $start;
$read_ops_per_sec = round($iterations / $read_time);

echo "Read Operations: $iterations in " . formatTime($read_time) . "\n";
echo "Read Speed: $read_ops_per_sec ops/sec\n";

// Cleanup
for ($i = 0; $i < $iterations; $i++) {
    $cache->delete("benchmark_key_$i");
}

// ============================================
// SUMMARY
// ============================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  SUMMARY                                                  ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";

// Calculate total time saved
$time_saved_dns = $all_results['dns_cold']['avg_time'] - $all_results['dns_warm']['avg_time'];
$time_saved_whois = $all_results['whois_cold']['avg_time'] - $all_results['whois_warm']['avg_time'];
$total_time_saved = $time_saved_dns + $time_saved_whois;

echo "\nDNS Performance:\n";
echo "  Cold: " . formatTime($all_results['dns_cold']['avg_time']) . "\n";
echo "  Warm: " . formatTime($all_results['dns_warm']['avg_time']) . "\n";
echo "  Saved: " . formatTime($time_saved_dns) . " per query\n";

echo "\nWHOIS Performance:\n";
echo "  Cold: " . formatTime($all_results['whois_cold']['avg_time']) . "\n";
echo "  Warm: " . formatTime($all_results['whois_warm']['avg_time']) . "\n";
echo "  Saved: " . formatTime($time_saved_whois) . " per query\n";

echo "\nBatch Performance:\n";
echo "  " . count($test_domains) . " domains in " . formatTime($all_results['batch_dns']['avg_time']) . "\n";

echo "\nCache Statistics:\n";
$cache_stats = get_cache_stats();
echo "  Driver: " . ($cache_stats['driver'] ?? 'unknown') . "\n";
echo "  Hits: " . ($cache_stats['hits'] ?? 0) . "\n";
echo "  Misses: " . ($cache_stats['misses'] ?? 0) . "\n";
if (($cache_stats['hits'] ?? 0) + ($cache_stats['misses'] ?? 0) > 0) {
    $hit_rate = round(($cache_stats['hits'] ?? 0) / (($cache_stats['hits'] ?? 0) + ($cache_stats['misses'] ?? 0)) * 100, 1);
    echo "  Hit Rate: $hit_rate%\n";
}

echo "\nMemory:\n";
echo "  Peak Usage: " . formatMemory(memory_get_peak_usage()) . "\n";
echo "  Current Usage: " . formatMemory(memory_get_usage()) . "\n";

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║  BENCHMARK COMPLETE                                       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Export results to JSON
$export_file = ABSPATH . 'logs/benchmark-' . date('Y-m-d-His') . '.json';
$export_dir = dirname($export_file);

if (!is_dir($export_dir)) {
    @mkdir($export_dir, 0755, true);
}

file_put_contents($export_file, json_encode([
    'timestamp' => date('c'),
    'php_version' => phpversion(),
    'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
    'redis_available' => class_exists('Redis'),
    'results' => $all_results,
    'cache_stats' => $cache_stats,
    'memory' => [
        'peak' => memory_get_peak_usage(),
        'current' => memory_get_usage()
    ]
], JSON_PRETTY_PRINT));

echo "Results exported to: $export_file\n\n";
