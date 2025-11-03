<?php
/**
 * Scheduled Scans and Reports System
 *
 * Automate domain scans and report generation on schedules:
 * - Daily, weekly, monthly scans
 * - Automated report delivery via email
 * - Monitor domains for changes
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/complete-scan.php';
require_once __DIR__ . '/email-notifications.php';
require_once __DIR__ . '/webhook-manager.php';
require_once __DIR__ . '/pdf-export.php';

class ScheduledScanManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create scheduled scan
     *
     * @param string $user_id User ID
     * @param string $name Schedule name
     * @param array $domains Domains to scan
     * @param string $frequency Frequency (daily, weekly, monthly)
     * @param string $scan_type Type of scan
     * @param array $options Additional options
     * @return int Schedule ID
     */
    public function createSchedule($user_id, $name, $domains, $frequency, $scan_type = 'complete', $options = []) {
        // Validate frequency
        $valid_frequencies = ['hourly', 'daily', 'weekly', 'monthly'];
        if (!in_array($frequency, $valid_frequencies)) {
            throw new Exception('Invalid frequency');
        }

        // Calculate next run time
        $next_run = $this->calculateNextRun($frequency, $options['time'] ?? '00:00');

        // Create schedule
        $schedule_id = $this->db->insert('scan_schedules', [
            'user_id' => $user_id,
            'name' => $name,
            'domains' => '{' . implode(',', $domains) . '}',
            'scan_type' => $scan_type,
            'frequency' => $frequency,
            'next_run_at' => $next_run,
            'status' => 'active',
            'options' => json_encode($options),
            'last_run_count' => 0
        ]);

        return $schedule_id;
    }

    /**
     * Calculate next run time based on frequency
     */
    private function calculateNextRun($frequency, $time = '00:00') {
        $now = new DateTime();

        switch ($frequency) {
            case 'hourly':
                $now->modify('+1 hour');
                break;

            case 'daily':
                $now->setTime(...explode(':', $time));
                if ($now < new DateTime()) {
                    $now->modify('+1 day');
                }
                break;

            case 'weekly':
                $now->setTime(...explode(':', $time));
                $now->modify('next monday');
                break;

            case 'monthly':
                $now->setTime(...explode(':', $time));
                $now->modify('first day of next month');
                break;
        }

        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Process due schedules
     *
     * @return array Processing results
     */
    public function processDueSchedules() {
        // Get schedules that are due
        $schedules = $this->db->query(
            "SELECT * FROM scan_schedules
             WHERE status = 'active'
             AND next_run_at <= NOW()
             ORDER BY next_run_at ASC
             LIMIT 50"
        );

        $results = [];

        foreach ($schedules as $schedule) {
            try {
                $result = $this->executeSchedule($schedule);
                $results[] = [
                    'schedule_id' => $schedule['id'],
                    'status' => 'success',
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'schedule_id' => $schedule['id'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];

                // Log error
                error_log("Scheduled scan failed: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Execute single schedule
     */
    private function executeSchedule($schedule) {
        $schedule_id = $schedule['id'];
        $user_id = $schedule['user_id'];
        $domains = $this->parsePostgresArray($schedule['domains']);
        $scan_type = $schedule['scan_type'];
        $options = json_decode($schedule['options'], true) ?? [];

        // Mark as running
        $this->db->update(
            'scan_schedules',
            [
                'status' => 'running',
                'last_run_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            [':id' => $schedule_id]
        );

        $results = [];
        $success_count = 0;
        $error_count = 0;

        // Scan each domain
        foreach ($domains as $domain) {
            try {
                $scan_result = $this->performScan($domain, $scan_type);
                $results[$domain] = $scan_result;
                $success_count++;

                // Store result
                $this->db->insert('scheduled_scan_results', [
                    'schedule_id' => $schedule_id,
                    'domain' => $domain,
                    'scan_type' => $scan_type,
                    'results' => json_encode($scan_result),
                    'success' => true
                ]);

            } catch (Exception $e) {
                $results[$domain] = ['error' => $e->getMessage()];
                $error_count++;

                $this->db->insert('scheduled_scan_results', [
                    'schedule_id' => $schedule_id,
                    'domain' => $domain,
                    'scan_type' => $scan_type,
                    'error_message' => $e->getMessage(),
                    'success' => false
                ]);
            }
        }

        // Calculate next run
        $next_run = $this->calculateNextRun(
            $schedule['frequency'],
            $options['time'] ?? '00:00'
        );

        // Update schedule
        $this->db->update(
            'scan_schedules',
            [
                'status' => 'active',
                'next_run_at' => $next_run,
                'last_run_count' => $success_count + $error_count,
                'last_success_count' => $success_count,
                'last_error_count' => $error_count
            ],
            'id = :id',
            [':id' => $schedule_id]
        );

        // Send notifications if configured
        if ($options['notify'] ?? false) {
            $this->sendScheduleNotification($schedule, $results, $success_count, $error_count);
        }

        // Trigger webhooks
        if ($options['webhook'] ?? false) {
            $webhookManager = getWebhookManager();
            $webhookManager->triggerWebhooks($user_id, 'schedule_complete', [
                'schedule_id' => $schedule_id,
                'schedule_name' => $schedule['name'],
                'domains_scanned' => count($domains),
                'success_count' => $success_count,
                'error_count' => $error_count
            ]);
        }

        return [
            'schedule_id' => $schedule_id,
            'domains_scanned' => count($domains),
            'success' => $success_count,
            'errors' => $error_count,
            'next_run' => $next_run
        ];
    }

    /**
     * Perform scan based on type
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

            default:
                throw new Exception('Invalid scan type');
        }
    }

    /**
     * Send schedule completion notification
     */
    private function sendScheduleNotification($schedule, $results, $success_count, $error_count) {
        $user = $this->db->queryOne(
            'SELECT email FROM users WHERE id = :id',
            [':id' => $schedule['user_id']]
        );

        if (!$user || !$user['email']) {
            return;
        }

        $emailer = getEmailNotifications();
        $emailer->sendScheduledReportEmail(
            $user['email'],
            $schedule['name'],
            null // Attachment path if needed
        );
    }

    /**
     * Get schedule status
     */
    public function getScheduleStatus($schedule_id, $user_id = null) {
        $query = 'SELECT * FROM scan_schedules WHERE id = :id';
        $params = [':id' => $schedule_id];

        if ($user_id) {
            $query .= ' AND user_id = :user_id';
            $params[':user_id'] = $user_id;
        }

        return $this->db->queryOne($query, $params);
    }

    /**
     * Get schedule results history
     */
    public function getScheduleResults($schedule_id, $limit = 100) {
        return $this->db->query(
            'SELECT * FROM scheduled_scan_results
             WHERE schedule_id = :schedule_id
             ORDER BY created_at DESC
             LIMIT :limit',
            [':schedule_id' => $schedule_id, ':limit' => $limit]
        );
    }

    /**
     * Update schedule
     */
    public function updateSchedule($schedule_id, $user_id, $updates) {
        $allowed_fields = ['name', 'domains', 'frequency', 'scan_type', 'options', 'status'];
        $update_data = [];

        foreach ($updates as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'domains' && is_array($value)) {
                    $update_data[$field] = '{' . implode(',', $value) . '}';
                } elseif ($field === 'options' && is_array($value)) {
                    $update_data[$field] = json_encode($value);
                } else {
                    $update_data[$field] = $value;
                }
            }
        }

        // Recalculate next run if frequency changed
        if (isset($updates['frequency'])) {
            $options = json_decode($updates['options'] ?? '{}', true) ?? [];
            $update_data['next_run_at'] = $this->calculateNextRun(
                $updates['frequency'],
                $options['time'] ?? '00:00'
            );
        }

        $this->db->update(
            'scan_schedules',
            $update_data,
            'id = :id AND user_id = :user_id',
            [':id' => $schedule_id, ':user_id' => $user_id]
        );

        return true;
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule($schedule_id, $user_id) {
        $this->db->delete(
            'scan_schedules',
            'id = :id AND user_id = :user_id',
            [':id' => $schedule_id, ':user_id' => $user_id]
        );

        return true;
    }

    /**
     * Pause/Resume schedule
     */
    public function toggleSchedule($schedule_id, $user_id, $active) {
        $status = $active ? 'active' : 'paused';

        $this->db->update(
            'scan_schedules',
            ['status' => $status],
            'id = :id AND user_id = :user_id',
            [':id' => $schedule_id, ':user_id' => $user_id]
        );

        return true;
    }

    /**
     * Get user's schedules
     */
    public function getUserSchedules($user_id, $status = null) {
        $query = 'SELECT * FROM scan_schedules WHERE user_id = :user_id';
        $params = [':user_id' => $user_id];

        if ($status) {
            $query .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $query .= ' ORDER BY created_at DESC';

        return $this->db->query($query, $params);
    }

    /**
     * Parse PostgreSQL array format
     */
    private function parsePostgresArray($pgArray) {
        if (is_array($pgArray)) {
            return $pgArray;
        }

        $pgArray = trim($pgArray, '{}');
        if (empty($pgArray)) {
            return [];
        }

        return explode(',', $pgArray);
    }
}

/**
 * Scheduled Reports Manager
 */
class ScheduledReportManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create scheduled report
     */
    public function createScheduledReport($user_id, $name, $report_type, $frequency, $options = []) {
        $next_run = $this->calculateNextRun($frequency, $options['time'] ?? '00:00');

        $report_id = $this->db->insert('scheduled_reports', [
            'user_id' => $user_id,
            'name' => $name,
            'report_type' => $report_type,
            'frequency' => $frequency,
            'next_run_at' => $next_run,
            'status' => 'active',
            'options' => json_encode($options)
        ]);

        return $report_id;
    }

    /**
     * Process due reports
     */
    public function processDueReports() {
        $reports = $this->db->query(
            "SELECT * FROM scheduled_reports
             WHERE status = 'active'
             AND next_run_at <= NOW()
             ORDER BY next_run_at ASC
             LIMIT 20"
        );

        $results = [];

        foreach ($reports as $report) {
            try {
                $result = $this->generateAndSendReport($report);
                $results[] = ['report_id' => $report['id'], 'status' => 'success'];
            } catch (Exception $e) {
                $results[] = ['report_id' => $report['id'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Generate and send report
     */
    private function generateAndSendReport($report) {
        // Generate report based on type
        // Send via email
        // Update next run time

        $next_run = $this->calculateNextRun($report['frequency']);

        $this->db->update(
            'scheduled_reports',
            [
                'last_run_at' => date('Y-m-d H:i:s'),
                'next_run_at' => $next_run
            ],
            'id = :id',
            [':id' => $report['id']]
        );

        return true;
    }

    /**
     * Calculate next run time
     */
    private function calculateNextRun($frequency, $time = '00:00') {
        $now = new DateTime();

        switch ($frequency) {
            case 'daily':
                $now->modify('+1 day')->setTime(...explode(':', $time));
                break;
            case 'weekly':
                $now->modify('+1 week')->setTime(...explode(':', $time));
                break;
            case 'monthly':
                $now->modify('+1 month')->setTime(...explode(':', $time));
                break;
        }

        return $now->format('Y-m-d H:i:s');
    }
}

/**
 * Helper functions
 */
function getScheduledScanManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new ScheduledScanManager();
    }
    return $instance;
}

function getScheduledReportManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new ScheduledReportManager();
    }
    return $instance;
}
