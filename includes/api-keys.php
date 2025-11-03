<?php
/**
 * API Key Management
 *
 * Functions for creating, managing, and validating API keys
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/database.php';

class APIKeyManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a new API key
     *
     * @param string $user_id User UUID
     * @param string $name Key name/description
     * @param array $scopes Permission scopes
     * @param int $rate_limit_per_hour Rate limit
     * @param string $expires_at Expiration date (optional)
     * @return array API key data
     */
    public function generateKey($user_id, $name, $scopes = [], $rate_limit_per_hour = 100, $expires_at = null) {
        // Generate random API key
        $key = 'cd_' . bin2hex(random_bytes(32)); // cd_ prefix for Controllo Domini
        $key_hash = hash('sha256', $key);
        $key_prefix = substr($key, 0, 10); // For display purposes

        try {
            // Insert API key
            $key_id = $this->db->insert('api_keys', [
                'user_id' => $user_id,
                'key_hash' => $key_hash,
                'key_prefix' => $key_prefix,
                'name' => $name,
                'scopes' => '{' . implode(',', $scopes) . '}',
                'rate_limit_per_hour' => $rate_limit_per_hour,
                'rate_limit_per_day' => $rate_limit_per_hour * 24,
                'status' => 'active',
                'expires_at' => $expires_at
            ]);

            return [
                'success' => true,
                'key_id' => $key_id,
                'api_key' => $key, // ONLY returned once!
                'key_prefix' => $key_prefix,
                'message' => 'API key created successfully. Save this key - it will not be shown again!'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create API key: ' . $e->getMessage()
            ];
        }
    }

    /**
     * List user's API keys
     *
     * @param string $user_id User UUID
     * @return array List of API keys (without actual key)
     */
    public function listKeys($user_id) {
        return $this->db->query(
            'SELECT id, key_prefix, name, scopes, rate_limit_per_hour,
                    status, last_used_at, expires_at, created_at
             FROM api_keys
             WHERE user_id = :user_id
             ORDER BY created_at DESC',
            [':user_id' => $user_id]
        );
    }

    /**
     * Revoke an API key
     *
     * @param string $key_id Key UUID
     * @param string $user_id User UUID (for security)
     * @return array Result
     */
    public function revokeKey($key_id, $user_id) {
        try {
            $affected = $this->db->update(
                'api_keys',
                ['status' => 'revoked'],
                'id = :key_id AND user_id = :user_id',
                [':key_id' => $key_id, ':user_id' => $user_id]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'API key revoked successfully'];
            } else {
                return ['success' => false, 'error' => 'API key not found'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to revoke key: ' . $e->getMessage()];
        }
    }

    /**
     * Delete an API key
     *
     * @param string $key_id Key UUID
     * @param string $user_id User UUID (for security)
     * @return array Result
     */
    public function deleteKey($key_id, $user_id) {
        try {
            $affected = $this->db->delete(
                'api_keys',
                'id = :key_id AND user_id = :user_id',
                [':key_id' => $key_id, ':user_id' => $user_id]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'API key deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'API key not found'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete key: ' . $e->getMessage()];
        }
    }

    /**
     * Update API key settings
     *
     * @param string $key_id Key UUID
     * @param string $user_id User UUID
     * @param array $data Update data
     * @return array Result
     */
    public function updateKey($key_id, $user_id, $data) {
        try {
            $allowed_fields = ['name', 'rate_limit_per_hour', 'scopes', 'expires_at'];
            $update_data = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $update_data[$field] = $value;
                }
            }

            if (empty($update_data)) {
                return ['success' => false, 'error' => 'No valid fields to update'];
            }

            $affected = $this->db->update(
                'api_keys',
                $update_data,
                'id = :key_id AND user_id = :user_id',
                [':key_id' => $key_id, ':user_id' => $user_id]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'API key updated successfully'];
            } else {
                return ['success' => false, 'error' => 'API key not found'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update key: ' . $e->getMessage()];
        }
    }

    /**
     * Get API key usage statistics
     *
     * @param string $key_id Key UUID
     * @param string $user_id User UUID
     * @return array Usage stats
     */
    public function getKeyUsage($key_id, $user_id) {
        $stats = $this->db->queryOne(
            'SELECT
                COUNT(*) as total_requests,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MAX(created_at) as last_request_at
             FROM analysis_history
             WHERE user_id = :user_id',
            [':user_id' => $user_id]
        );

        // Get rate limit usage for current hour
        $current_hour = $this->db->queryOne(
            'SELECT request_count
             FROM rate_limits
             WHERE api_key_id = :key_id
             AND window_start = :start',
            [
                ':key_id' => $key_id,
                ':start' => date('Y-m-d H:00:00')
            ]
        );

        return [
            'success' => true,
            'data' => [
                'total_requests' => intval($stats['total_requests'] ?? 0),
                'active_days' => intval($stats['active_days'] ?? 0),
                'last_request_at' => $stats['last_request_at'],
                'current_hour_usage' => intval($current_hour['request_count'] ?? 0)
            ]
        ];
    }
}

/**
 * Helper function to get APIKeyManager instance
 */
function getAPIKeyManager() {
    static $manager = null;
    if ($manager === null) {
        $manager = new APIKeyManager();
    }
    return $manager;
}
