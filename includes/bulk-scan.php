<?php
/**
 * Bulk Scan System
 *
 * Perform bulk domain scans for multiple domains simultaneously
 * with progress tracking and result aggregation
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/complete-scan.php';
require_once __DIR__ . '/email-notifications.php';
require_once __DIR__ . '/webhook-manager.php';

class BulkScanManager {
    private $db;
    private $cache;
    private $max_concurrent = 5;
    private $max_domains_per_batch = 100;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->cache = getCache();
    }

    /**
     * Create bulk scan job
     *
     * @param string $user_id User ID
     * @param array $domains Array of domains to scan
     * @param string $scan_type Type of scan (dns, whois, complete, etc.)
     * @param array $options Scan options
     * @return int Bulk scan job ID
     */
    public function createBulkScan($user_id, $domains, $scan_type = 'complete', $options = []) {
        // Validate domains
        if (empty($domains)) {
            throw new Exception('No domains provided');
        }

        if (count($domains) > $this->max_domains_per_batch) {
            throw new Exception("Maximum {$this->max_domains_per_batch} domains per batch");
        }

        // Clean and validate domain list
        $cleaned_domains = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#/.*$#', '', $domain);

            if (!empty($domain) && $this->isValidDomain($domain)) {
                $cleaned_domains[] = $domain;
            }
        }

        if (empty($cleaned_domains)) {
            throw new Exception('No valid domains after filtering');
        }

        // Create bulk scan job
        $job_id = $this->db->insert('bulk_scan_jobs', [
            'user_id' => $user_id,
            'scan_type' => $scan_type,
            'total_domains' => count($cleaned_domains),
            'completed_domains' => 0,
            'failed_domains' => 0,
            'status' => 'pending',
            'options' => json_encode($options),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Create individual scan tasks
        foreach ($cleaned_domains as $domain) {
            $this->db->insert('bulk_scan_tasks', [
                'bulk_scan_job_id' => $job_id,
                'domain' => $domain,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $job_id;
    }

    /**
     * Process bulk scan job
     *
     * @param int $job_id Bulk scan job ID
     * @return array Processing results
     */
    public function processBulkScan($job_id) {
        // Get job details
        $job = $this->db->queryOne(
            'SELECT * FROM bulk_scan_jobs WHERE id = :id',
            [':id' => $job_id]
        );

        if (!$job) {
            throw new Exception('Bulk scan job not found');
        }

        if ($job['status'] === 'completed') {
            throw new Exception('Bulk scan already completed');
        }

        // Update job status
        $this->db->update(
            'bulk_scan_jobs',
            [
                'status' => 'processing',
                'started_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            [':id' => $job_id]
        );

        // Get pending tasks
        $tasks = $this->db->query(
            'SELECT * FROM bulk_scan_tasks
             WHERE bulk_scan_job_id = :job_id AND status = :status
             ORDER BY created_at ASC',
            [':job_id' => $job_id, ':status' => 'pending']
        );

        $results = [];
        $completed = 0;
        $failed = 0;

        // Process tasks in batches
        foreach ($tasks as $task) {
            try {
                // Update task status
                $this->db->update(
                    'bulk_scan_tasks',
                    ['status' => 'processing', 'started_at' => date('Y-m-d H:i:s')],
                    'id = :id',
                    [':id' => $task['id']]
                );

                // Perform scan based on type
                $scan_result = $this->performScan($task['domain'], $job['scan_type']);

                // Update task with results
                $this->db->update(
                    'bulk_scan_tasks',
                    [
                        'status' => 'completed',
                        'results' => json_encode($scan_result),
                        'completed_at' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    [':id' => $task['id']]
                );

                $completed++;
                $results[] = [
                    'domain' => $task['domain'],
                    'status' => 'success',
                    'result' => $scan_result
                ];

            } catch (Exception $e) {
                // Update task with error
                $this->db->update(
                    'bulk_scan_tasks',
                    [
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'completed_at' => date('Y-m-d H:i:s')
                    ],
                    'id = :id',
                    [':id' => $task['id']]
                );

                $failed++;
                $results[] = [
                    'domain' => $task['domain'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }

            // Update job progress
            $this->db->update(
                'bulk_scan_jobs',
                [
                    'completed_domains' => $completed,
                    'failed_domains' => $failed,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                [':id' => $job_id]
            );
        }

        // Mark job as completed
        $this->db->update(
            'bulk_scan_jobs',
            [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            [':id' => $job_id]
        );

        // Send completion notification
        $user = $this->db->queryOne(
            'SELECT email FROM users WHERE id = :id',
            [':id' => $job['user_id']]
        );

        if ($user && $user['email']) {
            $emailer = getEmailNotifications();
            // Could send a bulk scan completion email here
        }

        return [
            'job_id' => $job_id,
            'total' => count($tasks),
            'completed' => $completed,
            'failed' => $failed,
            'results' => $results
        ];
    }

    /**
     * Perform individual scan
     */
    private function performScan($domain, $scan_type) {
        switch ($scan_type) {
            case 'complete':
                return performCompleteScan($domain);

            case 'dns':
                require_once __DIR__ . '/dns-functions.php';
                return getAllDnsRecords($domain);

            case 'whois':
                require_once __DIR__ . '/whois-functions.php';
                return getWhoisInfo($domain);

            case 'ssl':
                require_once __DIR__ . '/ssl-certificate.php';
                return analyzeSSLCertificate($domain);

            case 'blacklist':
                require_once __DIR__ . '/blacklist-functions.php';
                return checkDomainBlacklist($domain);

            default:
                throw new Exception('Invalid scan type');
        }
    }

    /**
     * Get bulk scan job status
     */
    public function getJobStatus($job_id, $user_id = null) {
        $query = 'SELECT * FROM bulk_scan_jobs WHERE id = :id';
        $params = [':id' => $job_id];

        if ($user_id) {
            $query .= ' AND user_id = :user_id';
            $params[':user_id'] = $user_id;
        }

        $job = $this->db->queryOne($query, $params);

        if (!$job) {
            throw new Exception('Bulk scan job not found');
        }

        // Get task breakdown
        $task_stats = $this->db->queryOne(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM bulk_scan_tasks
             WHERE bulk_scan_job_id = :job_id",
            [':job_id' => $job_id]
        );

        return [
            'job' => $job,
            'stats' => $task_stats,
            'progress' => $task_stats['total'] > 0
                ? round(($task_stats['completed'] + $task_stats['failed']) / $task_stats['total'] * 100, 2)
                : 0
        ];
    }

    /**
     * Get bulk scan results
     */
    public function getJobResults($job_id, $user_id = null) {
        // Verify job ownership
        $query = 'SELECT * FROM bulk_scan_jobs WHERE id = :id';
        $params = [':id' => $job_id];

        if ($user_id) {
            $query .= ' AND user_id = :user_id';
            $params[':user_id'] = $user_id;
        }

        $job = $this->db->queryOne($query, $params);

        if (!$job) {
            throw new Exception('Bulk scan job not found');
        }

        // Get all tasks
        $tasks = $this->db->query(
            'SELECT * FROM bulk_scan_tasks
             WHERE bulk_scan_job_id = :job_id
             ORDER BY completed_at DESC',
            [':job_id' => $job_id]
        );

        return [
            'job' => $job,
            'tasks' => $tasks
        ];
    }

    /**
     * Cancel bulk scan job
     */
    public function cancelJob($job_id, $user_id) {
        $this->db->update(
            'bulk_scan_jobs',
            [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id AND user_id = :user_id AND status IN (:status1, :status2)',
            [
                ':id' => $job_id,
                ':user_id' => $user_id,
                ':status1' => 'pending',
                ':status2' => 'processing'
            ]
        );

        return true;
    }

    /**
     * Validate domain name
     */
    private function isValidDomain($domain) {
        return preg_match('/^([a-z0-9]+([\-a-z0-9]*[a-z0-9]+)?\.)+[a-z]{2,}$/i', $domain);
    }

    /**
     * Export bulk scan results
     */
    public function exportResults($job_id, $user_id, $format = 'csv') {
        $data = $this->getJobResults($job_id, $user_id);

        $export_dir = __DIR__ . '/../exports';
        if (!is_dir($export_dir)) {
            mkdir($export_dir, 0755, true);
        }

        $filename = "bulk_scan_{$job_id}_" . date('Y-m-d_His') . ".{$format}";
        $filepath = $export_dir . '/' . $filename;

        switch ($format) {
            case 'csv':
                return $this->exportCSV($data, $filepath);
            case 'json':
                return $this->exportJSON($data, $filepath);
            default:
                throw new Exception('Invalid export format');
        }
    }

    /**
     * Export to CSV
     */
    private function exportCSV($data, $filepath) {
        $fp = fopen($filepath, 'w');

        // Write header
        fputcsv($fp, ['Domain', 'Status', 'Completed At', 'Error Message']);

        // Write data
        foreach ($data['tasks'] as $task) {
            fputcsv($fp, [
                $task['domain'],
                $task['status'],
                $task['completed_at'] ?? '',
                $task['error_message'] ?? ''
            ]);
        }

        fclose($fp);
        return $filepath;
    }

    /**
     * Export to JSON
     */
    private function exportJSON($data, $filepath) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filepath, $json);
        return $filepath;
    }
}

/**
 * Helper function to get bulk scan manager instance
 */
function getBulkScanManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new BulkScanManager();
    }
    return $instance;
}
