<?php
/**
 * Webhook Manager
 *
 * Manage and trigger webhooks for various events:
 * - Scan completion
 * - Security alerts
 * - Domain expiration
 * - Monitor triggers
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email-notifications.php';

class WebhookManager {
    private $db;
    private $max_retries = 3;
    private $timeout = 10;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Trigger webhook for event
     *
     * @param string $user_id User ID
     * @param string $event_type Event type (scan_complete, security_alert, domain_expiration, monitor_trigger)
     * @param array $payload Event data
     * @return array Webhook delivery results
     */
    public function triggerWebhooks($user_id, $event_type, $payload) {
        // Get active webhooks for this user and event
        $webhooks = $this->db->query(
            'SELECT * FROM webhooks
             WHERE user_id = :user_id
             AND event_types @> ARRAY[:event_type]::varchar[]
             AND status = :status',
            [
                ':user_id' => $user_id,
                ':event_type' => $event_type,
                ':status' => 'active'
            ]
        );

        $results = [];

        foreach ($webhooks as $webhook) {
            $result = $this->deliverWebhook($webhook, $event_type, $payload);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Deliver single webhook
     *
     * @param array $webhook Webhook configuration
     * @param string $event_type Event type
     * @param array $payload Event data
     * @return array Delivery result
     */
    private function deliverWebhook($webhook, $event_type, $payload) {
        $webhook_id = $webhook['id'];
        $url = $webhook['url'];
        $secret = $webhook['secret'] ?? null;

        // Prepare payload
        $webhook_payload = [
            'event' => $event_type,
            'timestamp' => date('c'),
            'webhook_id' => $webhook_id,
            'data' => $payload
        ];

        $json_payload = json_encode($webhook_payload);

        // Generate signature if secret is provided
        $headers = [
            'Content-Type: application/json',
            'User-Agent: ControlloDomini-Webhook/4.2',
            'X-Webhook-Event: ' . $event_type,
            'X-Webhook-ID: ' . $webhook_id
        ];

        if ($secret) {
            $signature = hash_hmac('sha256', $json_payload, $secret);
            $headers[] = 'X-Webhook-Signature: sha256=' . $signature;
        }

        // Attempt delivery with retries
        $attempt = 0;
        $success = false;
        $error_message = null;
        $response_code = null;
        $response_body = null;

        while ($attempt < $this->max_retries && !$success) {
            $attempt++;

            try {
                $start_time = microtime(true);

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $json_payload,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_FOLLOWLOCATION => false
                ]);

                $response_body = curl_exec($ch);
                $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                $response_time = round((microtime(true) - $start_time) * 1000);

                curl_close($ch);

                // Check if successful (2xx status code)
                if ($response_code >= 200 && $response_code < 300) {
                    $success = true;
                } else {
                    $error_message = "HTTP {$response_code}: " . substr($response_body, 0, 200);
                }

                if ($curl_error) {
                    $error_message = "cURL error: {$curl_error}";
                }

            } catch (Exception $e) {
                $error_message = "Exception: " . $e->getMessage();
                $response_time = 0;
            }

            // Wait before retry (exponential backoff)
            if (!$success && $attempt < $this->max_retries) {
                usleep(pow(2, $attempt) * 100000); // 200ms, 400ms, 800ms
            }
        }

        // Log webhook delivery
        $log_id = $this->db->insert('webhook_logs', [
            'webhook_id' => $webhook_id,
            'event_type' => $event_type,
            'payload' => json_encode($webhook_payload),
            'response_status' => $response_code ?? 0,
            'response_body' => substr($response_body ?? '', 0, 1000),
            'success' => $success,
            'error_message' => $error_message,
            'attempts' => $attempt,
            'response_time_ms' => $response_time ?? 0
        ]);

        // Update webhook statistics
        if ($success) {
            $this->db->execute(
                'UPDATE webhooks
                 SET last_triggered_at = NOW(),
                     success_count = success_count + 1,
                     last_status = :status
                 WHERE id = :id',
                [':id' => $webhook_id, ':status' => 'success']
            );
        } else {
            $this->db->execute(
                'UPDATE webhooks
                 SET last_triggered_at = NOW(),
                     failure_count = failure_count + 1,
                     last_status = :status,
                     last_error = :error
                 WHERE id = :id',
                [
                    ':id' => $webhook_id,
                    ':status' => 'failed',
                    ':error' => $error_message
                ]
            );

            // Disable webhook after too many failures
            $total_failures = $webhook['failure_count'] + 1;
            if ($total_failures >= 10) {
                $this->db->execute(
                    'UPDATE webhooks SET status = :status WHERE id = :id',
                    [':id' => $webhook_id, ':status' => 'disabled']
                );

                // Notify user
                $user = $this->db->queryOne(
                    'SELECT email FROM users WHERE id = :id',
                    [':id' => $webhook['user_id']]
                );

                if ($user) {
                    $emailer = getEmailNotifications();
                    $emailer->sendWebhookFailureEmail(
                        $user['email'],
                        $url,
                        "Webhook disabled after 10 consecutive failures. Last error: {$error_message}"
                    );
                }
            }
        }

        return [
            'webhook_id' => $webhook_id,
            'url' => $url,
            'success' => $success,
            'attempts' => $attempt,
            'response_code' => $response_code,
            'error' => $error_message,
            'log_id' => $log_id
        ];
    }

    /**
     * Create new webhook
     *
     * @param string $user_id User ID
     * @param string $name Webhook name
     * @param string $url Webhook URL
     * @param array $event_types Event types to subscribe to
     * @param string|null $secret Webhook secret for signature
     * @return int Webhook ID
     */
    public function createWebhook($user_id, $name, $url, $event_types, $secret = null) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid webhook URL');
        }

        // Validate event types
        $valid_events = ['scan_complete', 'security_alert', 'domain_expiration', 'monitor_trigger'];
        foreach ($event_types as $event) {
            if (!in_array($event, $valid_events)) {
                throw new Exception("Invalid event type: {$event}");
            }
        }

        // Generate secret if not provided
        if (!$secret) {
            $secret = bin2hex(random_bytes(32));
        }

        // Create webhook
        $webhook_id = $this->db->insert('webhooks', [
            'user_id' => $user_id,
            'name' => $name,
            'url' => $url,
            'event_types' => '{' . implode(',', $event_types) . '}',
            'secret' => $secret,
            'status' => 'active',
            'success_count' => 0,
            'failure_count' => 0
        ]);

        return [
            'id' => $webhook_id,
            'secret' => $secret
        ];
    }

    /**
     * Update webhook
     */
    public function updateWebhook($webhook_id, $user_id, $updates) {
        $allowed_fields = ['name', 'url', 'event_types', 'status'];
        $update_data = [];

        foreach ($updates as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'event_types' && is_array($value)) {
                    $update_data[$field] = '{' . implode(',', $value) . '}';
                } else {
                    $update_data[$field] = $value;
                }
            }
        }

        if (empty($update_data)) {
            throw new Exception('No valid fields to update');
        }

        $this->db->update(
            'webhooks',
            $update_data,
            'id = :id AND user_id = :user_id',
            [':id' => $webhook_id, ':user_id' => $user_id]
        );

        return true;
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook($webhook_id, $user_id) {
        $this->db->delete(
            'webhooks',
            'id = :id AND user_id = :user_id',
            [':id' => $webhook_id, ':user_id' => $user_id]
        );

        return true;
    }

    /**
     * Get webhook logs
     */
    public function getWebhookLogs($webhook_id, $limit = 100) {
        return $this->db->query(
            'SELECT * FROM webhook_logs
             WHERE webhook_id = :webhook_id
             ORDER BY created_at DESC
             LIMIT :limit',
            [':webhook_id' => $webhook_id, ':limit' => $limit]
        );
    }

    /**
     * Test webhook
     */
    public function testWebhook($webhook_id, $user_id) {
        $webhook = $this->db->queryOne(
            'SELECT * FROM webhooks WHERE id = :id AND user_id = :user_id',
            [':id' => $webhook_id, ':user_id' => $user_id]
        );

        if (!$webhook) {
            throw new Exception('Webhook not found');
        }

        $test_payload = [
            'test' => true,
            'message' => 'This is a test webhook from Controllo Domini',
            'timestamp' => date('c')
        ];

        return $this->deliverWebhook($webhook, 'test', $test_payload);
    }
}

/**
 * Helper function to get webhook manager instance
 */
function getWebhookManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new WebhookManager();
    }
    return $instance;
}
