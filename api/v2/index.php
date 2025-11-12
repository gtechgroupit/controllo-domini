<?php
/**
 * API v2.1 Router
 *
 * RESTful API for Controllo Domini
 * Handles authentication, rate limiting, and routing
 *
 * @package ControlloDomin
 * @version 4.2.1
 */

// CORS headers - Whitelist approach for security
$allowed_origins = [
    'https://controllodomini.it',
    'https://www.controllodomini.it',
    'http://localhost',
    'http://127.0.0.1'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Access-Control-Max-Age: 86400'); // 24 hours
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cache.php';
require_once __DIR__ . '/../../includes/dns-functions.php';
require_once __DIR__ . '/../../includes/whois-functions.php';
require_once __DIR__ . '/../../includes/blacklist-functions.php';
require_once __DIR__ . '/../../includes/ssl-certificate.php';
require_once __DIR__ . '/../../includes/complete-scan.php';

class APIRouter {
    private $db;
    private $auth;
    private $cache;
    private $user = null;
    private $api_key = null;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->cache = getCache();
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

            // Parse route
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $this->getPath();

            // Route to handler
            $response = $this->route($method, $path);

            // Log request
            $this->logRequest($path, $method, true);

            // Send response
            $this->sendResponse(200, $response);

        } catch (Exception $e) {
            $this->logRequest($path ?? '/', $method ?? 'GET', false, $e->getMessage());
            $this->sendError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    /**
     * Authenticate API request
     */
    private function authenticate() {
        // Check for API key in header only (not in GET for security)
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$api_key) {
            throw new Exception('API key required', 401);
        }

        // Validate API key
        $key_hash = hash('sha256', $api_key);
        $key_data = $this->db->queryOne(
            'SELECT ak.*, u.id as user_id, u.email, u.plan, u.status
             FROM api_keys ak
             JOIN users u ON ak.user_id = u.id
             WHERE ak.key_hash = :hash AND ak.status = :status',
            [':hash' => $key_hash, ':status' => 'active']
        );

        if (!$key_data) {
            throw new Exception('Invalid API key', 401);
        }

        // Check if key expired
        if ($key_data['expires_at'] && strtotime($key_data['expires_at']) < time()) {
            throw new Exception('API key expired', 401);
        }

        // Check user status
        if ($key_data['status'] !== 'active') {
            throw new Exception('User account is not active', 403);
        }

        $this->api_key = $key_data;
        $this->user = [
            'id' => $key_data['user_id'],
            'email' => $key_data['email'],
            'plan' => $key_data['plan']
        ];

        // Update last used
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
        $limit_per_hour = $this->api_key['rate_limit_per_hour'];
        $window_start = date('Y-m-d H:00:00');

        // Get current usage
        $usage = $this->db->queryOne(
            'SELECT request_count
             FROM rate_limits
             WHERE api_key_id = :key_id AND window_start = :start',
            [':key_id' => $this->api_key['id'], ':start' => $window_start]
        );

        $current_count = $usage['request_count'] ?? 0;

        // Check limit
        if ($current_count >= $limit_per_hour) {
            throw new Exception('Rate limit exceeded. Limit: ' . $limit_per_hour . '/hour', 429);
        }

        // Increment counter
        if ($usage) {
            $this->db->execute(
                'UPDATE rate_limits
                 SET request_count = request_count + 1
                 WHERE api_key_id = :key_id AND window_start = :start',
                [':key_id' => $this->api_key['id'], ':start' => $window_start]
            );
        } else {
            $this->db->insert('rate_limits', [
                'api_key_id' => $this->api_key['id'],
                'user_id' => $this->user['id'],
                'request_count' => 1,
                'window_start' => $window_start,
                'window_end' => date('Y-m-d H:59:59')
            ]);
        }

        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $limit_per_hour);
        header('X-RateLimit-Remaining: ' . ($limit_per_hour - $current_count - 1));
        header('X-RateLimit-Reset: ' . strtotime($window_start . ' +1 hour'));
    }

    /**
     * Get request path
     */
    private function getPath() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove /api/v2 prefix
        return preg_replace('#^/api/v2#', '', $uri);
    }

    /**
     * Route request to handler
     */
    private function route($method, $path) {
        // Parse path
        $parts = array_filter(explode('/', $path));
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';

        // Route to appropriate handler
        switch ($resource) {
            case 'dns':
                return $this->handleDNS($method, $action);
            case 'whois':
                return $this->handleWhois($method, $action);
            case 'blacklist':
                return $this->handleBlacklist($method, $action);
            case 'ssl':
                return $this->handleSSL($method, $action);
            case 'bulk':
                return $this->handleBulk($method, $action);
            case 'history':
                return $this->handleHistory($method, $action);
            case 'usage':
                return $this->handleUsage($method, $action);
            case 'domains':
                return $this->handleDomains($method, $action);
            case 'monitors':
                return $this->handleMonitors($method, $action);
            case 'alerts':
                return $this->handleAlerts($method, $action);
            case 'export':
                return $this->handleExport($method, $action);
            case 'complete':
            case 'scan':
                return $this->handleCompleteScan($method, $action);
            default:
                throw new Exception('Invalid endpoint', 404);
        }
    }

    /**
     * Handle DNS endpoints
     */
    private function handleDNS($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        // Check cache
        $cache_key = "api:dns:$domain";
        $cached = $this->cache->get($cache_key);
        if ($cached) {
            return ['success' => true, 'data' => $cached, 'from_cache' => true];
        }

        // Get DNS records
        $results = getAllDnsRecords($domain);

        // Cache result
        $this->cache->set($cache_key, $results, 3600);

        // Log to history
        $this->logAnalysis('dns', $domain, $results);

        return ['success' => true, 'data' => $results, 'from_cache' => false];
    }

    /**
     * Handle WHOIS endpoints
     */
    private function handleWhois($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        $cache_key = "api:whois:$domain";
        $cached = $this->cache->get($cache_key);
        if ($cached) {
            return ['success' => true, 'data' => $cached, 'from_cache' => true];
        }

        $results = getWhoisInfo($domain);
        $this->cache->set($cache_key, $results, 86400);
        $this->logAnalysis('whois', $domain, $results);

        return ['success' => true, 'data' => $results, 'from_cache' => false];
    }

    /**
     * Handle Blacklist endpoints
     */
    private function handleBlacklist($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        $cache_key = "api:blacklist:$domain";
        $cached = $this->cache->get($cache_key);
        if ($cached) {
            return ['success' => true, 'data' => $cached, 'from_cache' => true];
        }

        $results = checkBlacklists($domain);
        $this->cache->set($cache_key, $results, 7200);
        $this->logAnalysis('blacklist', $domain, $results);

        return ['success' => true, 'data' => $results, 'from_cache' => false];
    }

    /**
     * Handle SSL endpoints
     */
    private function handleSSL($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        $cache_key = "api:ssl:$domain";
        $cached = $this->cache->get($cache_key);
        if ($cached) {
            return ['success' => true, 'data' => $cached, 'from_cache' => true];
        }

        $results = analyzeSSLCertificate($domain);
        $this->cache->set($cache_key, $results, 86400);
        $this->logAnalysis('ssl', $domain, $results);

        return ['success' => true, 'data' => $results, 'from_cache' => false];
    }

    /**
     * Handle bulk operations
     */
    private function handleBulk($method, $action) {
        if ($method !== 'POST') {
            throw new Exception('Method not allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $domains = $input['domains'] ?? [];
        $type = $input['type'] ?? $action;

        if (empty($domains) || !is_array($domains)) {
            throw new Exception('Domains array required', 400);
        }

        if (count($domains) > 50) {
            throw new Exception('Maximum 50 domains per request', 400);
        }

        $results = [];
        foreach ($domains as $domain) {
            try {
                switch ($type) {
                    case 'dns':
                        $results[$domain] = getAllDnsRecords($domain);
                        break;
                    case 'whois':
                        $results[$domain] = getWhoisInfo($domain);
                        break;
                    case 'blacklist':
                        $results[$domain] = checkBlacklists($domain);
                        break;
                    case 'ssl':
                        $results[$domain] = analyzeSSLCertificate($domain);
                        break;
                    default:
                        throw new Exception('Invalid bulk type', 400);
                }
            } catch (Exception $e) {
                $results[$domain] = ['error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'data' => $results];
    }

    /**
     * Handle history endpoints
     */
    private function handleHistory($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $limit = min(intval($_GET['limit'] ?? 50), 100);
        $offset = intval($_GET['offset'] ?? 0);
        $domain = $_GET['domain'] ?? null;

        $query = 'SELECT * FROM analysis_history WHERE user_id = :user_id';
        $params = [':user_id' => $this->user['id']];

        if ($domain) {
            $query .= ' AND domain = :domain';
            $params[':domain'] = $domain;
        }

        $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $results = $this->db->query($query, $params);

        return ['success' => true, 'data' => $results, 'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => count($results)
        ]];
    }

    /**
     * Handle usage endpoints
     */
    private function handleUsage($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $stats = $this->db->queryOne(
            'SELECT
                COUNT(*) as total_requests,
                SUM(CASE WHEN from_cache THEN 1 ELSE 0 END) as cached_requests,
                AVG(execution_time_ms) as avg_execution_time
             FROM analysis_history
             WHERE user_id = :user_id
             AND created_at >= NOW() - INTERVAL \'30 days\'',
            [':user_id' => $this->user['id']]
        );

        return ['success' => true, 'data' => $stats];
    }

    /**
     * Handle domains endpoints
     */
    private function handleDomains($method, $action) {
        if ($method === 'GET') {
            // List saved domains
            $domains = $this->db->query(
                'SELECT * FROM saved_domains WHERE user_id = :user_id ORDER BY created_at DESC',
                [':user_id' => $this->user['id']]
            );
            return ['success' => true, 'data' => $domains];

        } elseif ($method === 'POST') {
            // Save domain
            $input = json_decode(file_get_contents('php://input'), true);
            $domain = $input['domain'] ?? '';
            $tags = $input['tags'] ?? [];
            $notes = $input['notes'] ?? '';

            if (empty($domain)) {
                throw new Exception('Domain required', 400);
            }

            $id = $this->db->insert('saved_domains', [
                'user_id' => $this->user['id'],
                'domain' => $domain,
                'tags' => '{' . implode(',', $tags) . '}',
                'notes' => $notes
            ]);

            return ['success' => true, 'data' => ['id' => $id]];

        } elseif ($method === 'DELETE') {
            // Delete domain
            $domain = $_GET['domain'] ?? '';
            if (empty($domain)) {
                throw new Exception('Domain required', 400);
            }

            $this->db->delete(
                'saved_domains',
                'user_id = :user_id AND domain = :domain',
                [':user_id' => $this->user['id'], ':domain' => $domain]
            );

            return ['success' => true, 'message' => 'Domain deleted'];
        }

        throw new Exception('Method not allowed', 405);
    }

    /**
     * Handle monitors endpoints
     */
    private function handleMonitors($method, $action) {
        // Similar implementation to domains
        return ['success' => true, 'data' => [], 'message' => 'Monitors endpoint - Coming soon'];
    }

    /**
     * Handle alerts endpoints
     */
    private function handleAlerts($method, $action) {
        // Similar implementation
        return ['success' => true, 'data' => [], 'message' => 'Alerts endpoint - Coming soon'];
    }

    /**
     * Handle export endpoints
     */
    private function handleExport($method, $action) {
        // Export implementation
        return ['success' => true, 'data' => [], 'message' => 'Export endpoint - Coming soon'];
    }

    /**
     * Handle Complete Scan endpoints
     */
    private function handleCompleteScan($method, $action) {
        if ($method !== 'GET') {
            throw new Exception('Method not allowed', 405);
        }

        $domain = $_GET['domain'] ?? '';
        if (empty($domain)) {
            throw new Exception('Domain parameter required', 400);
        }

        // Validate domain
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#/.*$#', '', $domain);

        // Check cache (longer TTL since complete scan is resource-intensive)
        $cache_key = "api:complete_scan:$domain";
        $cached = $this->cache->get($cache_key);
        if ($cached) {
            return ['success' => true, 'data' => $cached, 'from_cache' => true];
        }

        // Track start time
        $start_time = microtime(true);

        // Perform complete scan
        $results = performCompleteScan($domain);

        // Calculate execution time
        $execution_time = round((microtime(true) - $start_time) * 1000);
        $results['execution_time_ms'] = $execution_time;

        // Cache result (cache for 6 hours since this is expensive)
        $this->cache->set($cache_key, $results, 21600);

        // Log to history
        $this->logAnalysis('complete_scan', $domain, $results);

        return ['success' => true, 'data' => $results, 'from_cache' => false];
    }

    /**
     * Log analysis to history
     */
    private function logAnalysis($type, $domain, $results) {
        try {
            $this->db->insert('analysis_history', [
                'user_id' => $this->user['id'],
                'domain' => $domain,
                'analysis_type' => $type,
                'results' => json_encode($results),
                'execution_time_ms' => 0,
                'from_cache' => false,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Fail silently
        }
    }

    /**
     * Log API request
     */
    private function logRequest($path, $method, $success, $error = null) {
        // Log to database or file
        error_log(sprintf(
            '[API] %s %s - User: %s - Success: %s - Error: %s',
            $method,
            $path,
            $this->user['email'] ?? 'Unknown',
            $success ? 'Yes' : 'No',
            $error ?? 'None'
        ));
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
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Initialize and handle request
$router = new APIRouter();
$router->handleRequest();
