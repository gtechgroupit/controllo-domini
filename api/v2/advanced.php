<?php
/**
 * API v2.1 - Advanced Features Endpoints
 *
 * Advanced API endpoints for:
 * - Bulk scanning
 * - Webhooks management
 * - Scheduled scans
 * - Screenshots
 * - Competitive analysis
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/bulk-scan.php';
require_once __DIR__ . '/../../includes/webhook-manager.php';
require_once __DIR__ . '/../../includes/scheduled-scans.php';
require_once __DIR__ . '/../../includes/screenshot-capture.php';
require_once __DIR__ . '/../../includes/competitive-analysis.php';

class AdvancedAPI {
    private $db;
    private $user = null;
    private $api_key = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Handle API request
     */
    public function handleRequest() {
        try {
            // Authenticate
            $this->authenticate();

            // Check rate limit
            $this->checkRateLimit();

            // Route request
            $path = $this->getPath();
            $method = $_SERVER['REQUEST_METHOD'];

            $response = $this->route($path, $method);

            $this->sendResponse(200, $response);

        } catch (Exception $e) {
            $this->sendError($e->getCode() ?: 400, $e->getMessage());
        }
    }

    /**
     * Authenticate request
     */
    private function authenticate() {
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        if (!$api_key) {
            throw new Exception('API key required', 401);
        }

        $key_hash = hash('sha256', $api_key);
        $key_data = $this->db->queryOne(
            'SELECT ak.*, u.id as user_id, u.email, u.plan
             FROM api_keys ak
             JOIN users u ON ak.user_id = u.id
             WHERE ak.key_hash = :hash AND ak.status = :status',
            [':hash' => $key_hash, ':status' => 'active']
        );

        if (!$key_data) {
            throw new Exception('Invalid API key', 401);
        }

        $this->api_key = $key_data;
        $this->user = [
            'id' => $key_data['user_id'],
            'email' => $key_data['email'],
            'plan' => $key_data['plan']
        ];

        $this->db->update(
            'api_keys',
            ['last_used_at' => date('Y-m-d H:i:s')],
            'id = :id',
            [':id' => $key_data['id']]
        );
    }

    /**
     * Check rate limit
     */
    private function checkRateLimit() {
        // Simplified rate limiting
        $limit = $this->api_key['rate_limit_per_hour'];
        // Implementation would be similar to main API
    }

    /**
     * Get request path
     */
    private function getPath() {
        $path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        $path = str_replace('/api/v2/advanced', '', $path);
        $path = trim($path, '/');
        return $path;
    }

    /**
     * Route request
     */
    private function route($path, $method) {
        $parts = explode('/', $path);
        $endpoint = $parts[0] ?? 'help';
        $action = $parts[1] ?? null;

        switch ($endpoint) {
            case 'bulk':
                return $this->handleBulkScan($method, $action);
            case 'webhooks':
                return $this->handleWebhooks($method, $action);
            case 'schedules':
                return $this->handleSchedules($method, $action);
            case 'screenshots':
                return $this->handleScreenshots($method, $action);
            case 'competitive':
                return $this->handleCompetitiveAnalysis($method, $action);
            case 'help':
                return $this->getHelpMessage();
            default:
                throw new Exception('Invalid endpoint', 404);
        }
    }

    /**
     * Handle bulk scan endpoints
     */
    private function handleBulkScan($method, $action) {
        $bulkManager = getBulkScanManager();

        switch ($method) {
            case 'POST':
                if ($action === 'create' || !$action) {
                    // Create new bulk scan
                    $data = $this->getPostData();

                    $domains = $data['domains'] ?? [];
                    $scan_type = $data['scan_type'] ?? 'complete';
                    $options = $data['options'] ?? [];

                    $job_id = $bulkManager->createBulkScan(
                        $this->user['id'],
                        $domains,
                        $scan_type,
                        $options
                    );

                    return [
                        'success' => true,
                        'job_id' => $job_id,
                        'total_domains' => count($domains),
                        'message' => 'Bulk scan job created successfully'
                    ];
                }
                break;

            case 'GET':
                if ($action) {
                    // Get job status or results
                    $job_id = (int)$action;

                    if (isset($_GET['results'])) {
                        $results = $bulkManager->getJobResults($job_id, $this->user['id']);
                        return ['success' => true, 'data' => $results];
                    } else {
                        $status = $bulkManager->getJobStatus($job_id, $this->user['id']);
                        return ['success' => true, 'data' => $status];
                    }
                }
                break;

            case 'DELETE':
                if ($action) {
                    // Cancel job
                    $job_id = (int)$action;
                    $bulkManager->cancelJob($job_id, $this->user['id']);

                    return ['success' => true, 'message' => 'Job cancelled'];
                }
                break;
        }

        throw new Exception('Invalid bulk scan operation', 400);
    }

    /**
     * Handle webhook endpoints
     */
    private function handleWebhooks($method, $action) {
        $webhookManager = getWebhookManager();

        switch ($method) {
            case 'GET':
                if (!$action) {
                    // List webhooks
                    $webhooks = $this->db->query(
                        'SELECT * FROM webhooks WHERE user_id = :user_id ORDER BY created_at DESC',
                        [':user_id' => $this->user['id']]
                    );

                    return ['success' => true, 'webhooks' => $webhooks];
                } elseif ($action === 'logs') {
                    // Get webhook logs
                    $webhook_id = $_GET['webhook_id'] ?? null;
                    if (!$webhook_id) {
                        throw new Exception('webhook_id required');
                    }

                    $logs = $webhookManager->getWebhookLogs($webhook_id);
                    return ['success' => true, 'logs' => $logs];
                }
                break;

            case 'POST':
                if ($action === 'create' || !$action) {
                    // Create webhook
                    $data = $this->getPostData();

                    $result = $webhookManager->createWebhook(
                        $this->user['id'],
                        $data['name'],
                        $data['url'],
                        $data['event_types'],
                        $data['secret'] ?? null
                    );

                    return ['success' => true, 'webhook' => $result];
                } elseif ($action === 'test') {
                    // Test webhook
                    $data = $this->getPostData();
                    $webhook_id = $data['webhook_id'] ?? null;

                    if (!$webhook_id) {
                        throw new Exception('webhook_id required');
                    }

                    $result = $webhookManager->testWebhook($webhook_id, $this->user['id']);
                    return ['success' => true, 'test_result' => $result];
                }
                break;

            case 'PUT':
                if ($action) {
                    // Update webhook
                    $webhook_id = (int)$action;
                    $data = $this->getPostData();

                    $webhookManager->updateWebhook($webhook_id, $this->user['id'], $data);

                    return ['success' => true, 'message' => 'Webhook updated'];
                }
                break;

            case 'DELETE':
                if ($action) {
                    // Delete webhook
                    $webhook_id = (int)$action;
                    $webhookManager->deleteWebhook($webhook_id, $this->user['id']);

                    return ['success' => true, 'message' => 'Webhook deleted'];
                }
                break;
        }

        throw new Exception('Invalid webhook operation', 400);
    }

    /**
     * Handle scheduled scan endpoints
     */
    private function handleSchedules($method, $action) {
        $scheduleManager = getScheduledScanManager();

        switch ($method) {
            case 'GET':
                if (!$action) {
                    // List schedules
                    $schedules = $scheduleManager->getUserSchedules($this->user['id']);
                    return ['success' => true, 'schedules' => $schedules];
                } else {
                    // Get schedule status
                    $schedule_id = (int)$action;
                    $schedule = $scheduleManager->getScheduleStatus($schedule_id, $this->user['id']);

                    if (isset($_GET['results'])) {
                        $results = $scheduleManager->getScheduleResults($schedule_id);
                        return ['success' => true, 'schedule' => $schedule, 'results' => $results];
                    }

                    return ['success' => true, 'schedule' => $schedule];
                }
                break;

            case 'POST':
                if ($action === 'create' || !$action) {
                    // Create schedule
                    $data = $this->getPostData();

                    $schedule_id = $scheduleManager->createSchedule(
                        $this->user['id'],
                        $data['name'],
                        $data['domains'],
                        $data['frequency'],
                        $data['scan_type'] ?? 'complete',
                        $data['options'] ?? []
                    );

                    return [
                        'success' => true,
                        'schedule_id' => $schedule_id,
                        'message' => 'Schedule created successfully'
                    ];
                }
                break;

            case 'PUT':
                if ($action) {
                    // Update schedule
                    $schedule_id = (int)$action;
                    $data = $this->getPostData();

                    $scheduleManager->updateSchedule($schedule_id, $this->user['id'], $data);

                    return ['success' => true, 'message' => 'Schedule updated'];
                }
                break;

            case 'DELETE':
                if ($action) {
                    // Delete schedule
                    $schedule_id = (int)$action;
                    $scheduleManager->deleteSchedule($schedule_id, $this->user['id']);

                    return ['success' => true, 'message' => 'Schedule deleted'];
                }
                break;
        }

        throw new Exception('Invalid schedule operation', 400);
    }

    /**
     * Handle screenshot endpoints
     */
    private function handleScreenshots($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        $capture = new ScreenshotCapture();

        if ($action === 'responsive') {
            // Capture multiple viewports
            $screenshots = $capture->captureResponsive($domain);
            return ['success' => true, 'screenshots' => $screenshots];
        } else {
            // Capture single screenshot
            $options = [
                'width' => $_GET['width'] ?? 1920,
                'height' => $_GET['height'] ?? 1080,
                'full_page' => isset($_GET['full_page']),
                'delay' => $_GET['delay'] ?? 0
            ];

            $result = $capture->capture($domain, $options);
            return ['success' => true, 'screenshot' => $result];
        }
    }

    /**
     * Handle competitive analysis endpoints
     */
    private function handleCompetitiveAnalysis($method, $action) {
        if ($method !== 'POST') {
            throw new Exception('Method not allowed', 405);
        }

        $data = $this->getPostData();
        $domains = $data['domains'] ?? [];

        if (count($domains) < 2) {
            throw new Exception('At least 2 domains required for competitive analysis', 400);
        }

        $analysis = new CompetitiveAnalysis();
        foreach ($domains as $domain) {
            $analysis->addDomain($domain);
        }

        $result = $analysis->analyze();

        return ['success' => true, 'analysis' => $result];
    }

    /**
     * Get POST data
     */
    private function getPostData() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input', 400);
        }

        return $data ?? [];
    }

    /**
     * Send JSON response
     */
    private function sendResponse($code, $data) {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send error response
     */
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Get help message
     */
    private function getHelpMessage() {
        return [
            'message' => 'Advanced API v2.1 - Available Endpoints',
            'version' => '4.2.1',
            'endpoints' => [
                'POST /bulk/create' => 'Create bulk scan job',
                'GET /bulk/{job_id}' => 'Get bulk scan status',
                'DELETE /bulk/{job_id}' => 'Cancel bulk scan',
                'GET /webhooks' => 'List webhooks',
                'POST /webhooks/create' => 'Create webhook',
                'PUT /webhooks/{id}' => 'Update webhook',
                'DELETE /webhooks/{id}' => 'Delete webhook',
                'POST /webhooks/test' => 'Test webhook',
                'GET /schedules' => 'List schedules',
                'POST /schedules/create' => 'Create schedule',
                'PUT /schedules/{id}' => 'Update schedule',
                'DELETE /schedules/{id}' => 'Delete schedule',
                'GET /screenshots?domain={domain}' => 'Capture screenshot',
                'GET /screenshots/responsive?domain={domain}' => 'Capture responsive screenshots',
                'POST /competitive' => 'Perform competitive analysis'
            ],
            'authentication' => 'X-API-Key header or api_key parameter'
        ];
    }
}

// Initialize and handle request
$api = new AdvancedAPI();
$api->handleRequest();
