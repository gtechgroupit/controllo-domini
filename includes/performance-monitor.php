<?php
/**
 * Performance Monitor
 *
 * Monitora e traccia performance dell'applicazione:
 * - Execution time
 * - Memory usage
 * - Cache hit/miss rates
 * - Slow queries
 *
 * @package ControlloDomin
 * @version 1.0
 */

class PerformanceMonitor {

    /** @var float Start time */
    private $start_time;

    /** @var float Start memory */
    private $start_memory;

    /** @var array Query log */
    private $queries = [];

    /** @var array Metrics */
    private $metrics = [];

    /** @var bool Monitoring enabled */
    private $enabled = true;

    /**
     * Constructor
     */
    public function __construct() {
        $this->enabled = defined('PERFORMANCE_MONITORING') ? PERFORMANCE_MONITORING : true;

        if ($this->enabled) {
            $this->start_time = microtime(true);
            $this->start_memory = memory_get_usage();
        }
    }

    /**
     * Start query timing
     *
     * @param string $type Query type
     * @param string $identifier Query identifier
     * @return string Query ID
     */
    public function startQuery($type, $identifier) {
        if (!$this->enabled) {
            return '';
        }

        $query_id = uniqid($type . '_', true);

        $this->queries[$query_id] = [
            'type' => $type,
            'identifier' => $identifier,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'end_time' => null,
            'duration' => null,
            'memory_used' => null,
            'from_cache' => false
        ];

        return $query_id;
    }

    /**
     * End query timing
     *
     * @param string $query_id Query ID
     * @param bool $from_cache Se da cache
     * @return float Duration
     */
    public function endQuery($query_id, $from_cache = false) {
        if (!$this->enabled || !isset($this->queries[$query_id])) {
            return 0;
        }

        $query = &$this->queries[$query_id];
        $query['end_time'] = microtime(true);
        $query['duration'] = $query['end_time'] - $query['start_time'];
        $query['memory_used'] = memory_get_usage() - $query['start_memory'];
        $query['from_cache'] = $from_cache;

        // Log slow queries
        $threshold = defined('SLOW_QUERY_THRESHOLD') ? SLOW_QUERY_THRESHOLD : 5.0;
        if ($query['duration'] > $threshold) {
            $this->logSlowQuery($query);
        }

        return $query['duration'];
    }

    /**
     * Add custom metric
     *
     * @param string $name Metric name
     * @param mixed $value Metric value
     */
    public function addMetric($name, $value) {
        if (!$this->enabled) {
            return;
        }

        $this->metrics[$name] = $value;
    }

    /**
     * Get performance report
     *
     * @return array Performance data
     */
    public function getReport() {
        if (!$this->enabled) {
            return [];
        }

        $total_time = microtime(true) - $this->start_time;
        $total_memory = memory_get_usage() - $this->start_memory;
        $peak_memory = memory_get_peak_usage();

        // Calculate query stats
        $query_stats = $this->calculateQueryStats();

        return [
            'execution_time' => round($total_time, 4),
            'memory_used' => $this->formatBytes($total_memory),
            'memory_peak' => $this->formatBytes($peak_memory),
            'queries' => [
                'total' => count($this->queries),
                'from_cache' => $query_stats['cached'],
                'from_source' => $query_stats['not_cached'],
                'cache_hit_rate' => $query_stats['cache_hit_rate'],
                'average_duration' => $query_stats['avg_duration'],
                'slowest' => $query_stats['slowest']
            ],
            'custom_metrics' => $this->metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get detailed query log
     *
     * @return array Query log
     */
    public function getQueryLog() {
        return $this->queries;
    }

    /**
     * Calculate query statistics
     *
     * @return array Stats
     */
    private function calculateQueryStats() {
        $cached = 0;
        $not_cached = 0;
        $total_duration = 0;
        $slowest = null;

        foreach ($this->queries as $query) {
            if ($query['from_cache']) {
                $cached++;
            } else {
                $not_cached++;
            }

            if ($query['duration'] !== null) {
                $total_duration += $query['duration'];

                if ($slowest === null || $query['duration'] > $slowest['duration']) {
                    $slowest = $query;
                }
            }
        }

        $total = count($this->queries);
        $cache_hit_rate = $total > 0 ? round(($cached / $total) * 100, 2) : 0;
        $avg_duration = $total > 0 ? round($total_duration / $total, 4) : 0;

        return [
            'cached' => $cached,
            'not_cached' => $not_cached,
            'cache_hit_rate' => $cache_hit_rate . '%',
            'avg_duration' => $avg_duration . 's',
            'slowest' => $slowest ? [
                'type' => $slowest['type'],
                'identifier' => $slowest['identifier'],
                'duration' => round($slowest['duration'], 4) . 's'
            ] : null
        ];
    }

    /**
     * Log slow query
     *
     * @param array $query Query data
     */
    private function logSlowQuery($query) {
        $log_file = __DIR__ . '/../logs/slow-queries.log';
        $log_dir = dirname($log_file);

        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        $log_entry = sprintf(
            "[%s] SLOW QUERY: %s | %s | Duration: %.4fs | Memory: %s\n",
            date('Y-m-d H:i:s'),
            $query['type'],
            $query['identifier'],
            $query['duration'],
            $this->formatBytes($query['memory_used'])
        );

        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes Bytes
     * @return string Formatted
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Output performance report as HTML comment
     */
    public function outputReport() {
        if (!$this->enabled) {
            return;
        }

        $report = $this->getReport();

        echo "\n<!-- PERFORMANCE REPORT\n";
        echo "Execution Time: " . $report['execution_time'] . "s\n";
        echo "Memory Used: " . $report['memory_used'] . "\n";
        echo "Memory Peak: " . $report['memory_peak'] . "\n";
        echo "Total Queries: " . $report['queries']['total'] . "\n";
        echo "From Cache: " . $report['queries']['from_cache'] . "\n";
        echo "From Source: " . $report['queries']['from_source'] . "\n";
        echo "Cache Hit Rate: " . $report['queries']['cache_hit_rate'] . "\n";
        echo "Average Query Time: " . $report['queries']['average_duration'] . "\n";

        if ($report['queries']['slowest']) {
            echo "Slowest Query: " . $report['queries']['slowest']['type'] . " - " .
                 $report['queries']['slowest']['duration'] . "\n";
        }

        echo "-->\n";
    }
}

/**
 * Get global performance monitor instance
 *
 * @return PerformanceMonitor Instance
 */
function getPerformanceMonitor() {
    static $monitor = null;

    if ($monitor === null) {
        $monitor = new PerformanceMonitor();
    }

    return $monitor;
}

/**
 * Start query timing (helper)
 *
 * @param string $type Query type
 * @param string $identifier Query identifier
 * @return string Query ID
 */
function perf_start($type, $identifier) {
    return getPerformanceMonitor()->startQuery($type, $identifier);
}

/**
 * End query timing (helper)
 *
 * @param string $query_id Query ID
 * @param bool $from_cache From cache
 * @return float Duration
 */
function perf_end($query_id, $from_cache = false) {
    return getPerformanceMonitor()->endQuery($query_id, $from_cache);
}

/**
 * Add metric (helper)
 *
 * @param string $name Metric name
 * @param mixed $value Metric value
 */
function perf_metric($name, $value) {
    getPerformanceMonitor()->addMetric($name, $value);
}

/**
 * Get performance report (helper)
 *
 * @return array Report
 */
function perf_report() {
    return getPerformanceMonitor()->getReport();
}
