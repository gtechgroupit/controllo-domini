<?php
/**
 * Screenshot Capture System
 *
 * Capture website screenshots for visual analysis and reporting
 * Supports multiple methods: API services, headless browsers, wkhtmltoimage
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

class ScreenshotCapture {
    private $screenshot_dir;
    private $cache_ttl = 86400; // 24 hours

    private $api_key;
    private $use_api = false;

    public function __construct() {
        $this->screenshot_dir = __DIR__ . '/../screenshots';

        if (!is_dir($this->screenshot_dir)) {
            mkdir($this->screenshot_dir, 0755, true);
        }

        // Check for API key (e.g., Screenshot API, ScreenshotLayer, etc.)
        $this->api_key = getenv('SCREENSHOT_API_KEY');
        $this->use_api = !empty($this->api_key);
    }

    /**
     * Capture screenshot of a domain
     *
     * @param string $domain Domain to capture
     * @param array $options Screenshot options
     * @return array Screenshot data
     */
    public function capture($domain, $options = []) {
        // Clean domain
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#/.*$#', '', $domain);

        $url = 'https://' . $domain;

        // Default options
        $defaults = [
            'width' => 1920,
            'height' => 1080,
            'full_page' => false,
            'delay' => 0,
            'format' => 'png'
        ];

        $options = array_merge($defaults, $options);

        // Check cache
        $cache_key = $this->getCacheKey($domain, $options);
        $cached = $this->getFromCache($cache_key);

        if ($cached) {
            return [
                'success' => true,
                'screenshot_path' => $cached,
                'from_cache' => true,
                'url' => $url
            ];
        }

        // Capture screenshot
        try {
            if ($this->use_api) {
                $screenshot_path = $this->captureViaAPI($url, $options);
            } elseif ($this->checkWkhtmltoimage()) {
                $screenshot_path = $this->captureViaWkhtmltoimage($url, $options);
            } else {
                // Fallback: save placeholder
                $screenshot_path = $this->createPlaceholder($domain, $options);
            }

            // Save to cache
            $this->saveToCache($cache_key, $screenshot_path);

            return [
                'success' => true,
                'screenshot_path' => $screenshot_path,
                'from_cache' => false,
                'url' => $url,
                'method' => $this->use_api ? 'api' : ($this->checkWkhtmltoimage() ? 'wkhtmltoimage' : 'placeholder')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Capture via external API
     */
    private function captureViaAPI($url, $options) {
        // Example using a generic screenshot API
        // Replace with actual API endpoint

        $api_url = getenv('SCREENSHOT_API_URL') ?: 'https://api.screenshotlayer.com/api/capture';

        $params = http_build_query([
            'access_key' => $this->api_key,
            'url' => $url,
            'viewport' => $options['width'] . 'x' . $options['height'],
            'fullpage' => $options['full_page'] ? '1' : '0',
            'delay' => $options['delay'],
            'format' => $options['format']
        ]);

        $api_request_url = $api_url . '?' . $params;

        // Download screenshot
        $screenshot_data = file_get_contents($api_request_url);

        if (!$screenshot_data) {
            throw new Exception('Failed to capture screenshot via API');
        }

        // Save to file
        $filename = $this->generateFilename($url, $options);
        $filepath = $this->screenshot_dir . '/' . $filename;

        file_put_contents($filepath, $screenshot_data);

        return $filepath;
    }

    /**
     * Capture via wkhtmltoimage
     */
    private function captureViaWkhtmltoimage($url, $options) {
        $filename = $this->generateFilename($url, $options);
        $filepath = $this->screenshot_dir . '/' . $filename;

        $cmd = sprintf(
            'wkhtmltoimage --width %d --height %d %s %s %s 2>&1',
            $options['width'],
            $options['height'],
            $options['delay'] > 0 ? '--javascript-delay ' . ($options['delay'] * 1000) : '',
            escapeshellarg($url),
            escapeshellarg($filepath)
        );

        exec($cmd, $output, $return_code);

        if ($return_code !== 0 || !file_exists($filepath)) {
            throw new Exception('wkhtmltoimage failed: ' . implode("\n", $output));
        }

        return $filepath;
    }

    /**
     * Check if wkhtmltoimage is available
     */
    private function checkWkhtmltoimage() {
        static $available = null;

        if ($available === null) {
            exec('which wkhtmltoimage 2>&1', $output, $return_code);
            $available = ($return_code === 0);
        }

        return $available;
    }

    /**
     * Create placeholder image
     */
    private function createPlaceholder($domain, $options) {
        $filename = $this->generateFilename($domain, $options);
        $filepath = $this->screenshot_dir . '/' . $filename;

        // Create a simple placeholder image
        $width = $options['width'];
        $height = $options['height'];

        $image = imagecreatetruecolor($width, $height);

        // Background
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bg_color);

        // Text
        $text_color = imagecolorallocate($image, 100, 100, 100);
        $text = "Screenshot not available\n{$domain}";

        // Center text
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($domain);
        $text_height = imagefontheight($font_size);

        imagestring($image, $font_size, ($width - $text_width) / 2, ($height - $text_height) / 2 - 10, "Screenshot not available", $text_color);
        imagestring($image, $font_size, ($width - imagefontwidth($font_size) * strlen($domain)) / 2, ($height - $text_height) / 2 + 10, $domain, $text_color);

        // Save
        imagepng($image, $filepath);
        imagedestroy($image);

        return $filepath;
    }

    /**
     * Generate filename for screenshot
     */
    private function generateFilename($url, $options) {
        $hash = md5($url . json_encode($options));
        return 'screenshot_' . $hash . '.' . $options['format'];
    }

    /**
     * Get cache key
     */
    private function getCacheKey($domain, $options) {
        return 'screenshot:' . md5($domain . json_encode($options));
    }

    /**
     * Get from cache
     */
    private function getFromCache($cache_key) {
        $cache_file = $this->screenshot_dir . '/.cache_' . md5($cache_key);

        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);

            if ($cache_data && isset($cache_data['expires']) && $cache_data['expires'] > time()) {
                if (file_exists($cache_data['path'])) {
                    return $cache_data['path'];
                }
            }
        }

        return null;
    }

    /**
     * Save to cache
     */
    private function saveToCache($cache_key, $screenshot_path) {
        $cache_file = $this->screenshot_dir . '/.cache_' . md5($cache_key);

        $cache_data = [
            'path' => $screenshot_path,
            'expires' => time() + $this->cache_ttl
        ];

        file_put_contents($cache_file, json_encode($cache_data));
    }

    /**
     * Capture multiple viewports (desktop, tablet, mobile)
     */
    public function captureResponsive($domain) {
        $viewports = [
            'desktop' => ['width' => 1920, 'height' => 1080],
            'laptop' => ['width' => 1366, 'height' => 768],
            'tablet' => ['width' => 768, 'height' => 1024],
            'mobile' => ['width' => 375, 'height' => 667]
        ];

        $screenshots = [];

        foreach ($viewports as $device => $viewport) {
            $result = $this->capture($domain, $viewport);
            $screenshots[$device] = $result;
        }

        return $screenshots;
    }

    /**
     * Delete old screenshots
     */
    public function cleanupOldScreenshots($days = 7) {
        $files = glob($this->screenshot_dir . '/screenshot_*');
        $cutoff = time() - ($days * 86400);
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        // Clean cache files
        $cache_files = glob($this->screenshot_dir . '/.cache_*');
        foreach ($cache_files as $cache_file) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            if ($cache_data && isset($cache_data['expires']) && $cache_data['expires'] < time()) {
                unlink($cache_file);
            }
        }

        return $deleted;
    }
}

/**
 * Helper function
 */
function captureScreenshot($domain, $options = []) {
    $capture = new ScreenshotCapture();
    return $capture->capture($domain, $options);
}

function captureResponsiveScreenshots($domain) {
    $capture = new ScreenshotCapture();
    return $capture->captureResponsive($domain);
}
