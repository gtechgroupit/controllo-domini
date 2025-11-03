<?php
/**
 * Advanced Web Technology Detection for Web Agencies
 *
 * Comprehensive technology stack detection including CMS, frameworks,
 * analytics, marketing tools, hosting, CDN, and more
 *
 * @package ControlloDomin
 * @version 4.3.0
 */

class WebTechnologyAdvanced {
    private $url;
    private $html;
    private $headers;
    private $dom;
    private $xpath;

    // Technology signatures database
    private $technologies = [
        'cms' => [
            'WordPress' => [
                'html' => ['/wp-content/', '/wp-includes/', 'wp-json'],
                'meta' => ['generator' => 'WordPress'],
                'headers' => [],
                'scripts' => ['wp-includes/js'],
                'icon' => '🔷'
            ],
            'Joomla' => [
                'html' => ['/components/com_', '/media/jui/'],
                'meta' => ['generator' => 'Joomla'],
                'headers' => [],
                'icon' => '🟦'
            ],
            'Drupal' => [
                'html' => ['/sites/default/', '/misc/drupal'],
                'meta' => ['generator' => 'Drupal'],
                'headers' => ['X-Drupal-Cache', 'X-Generator' => 'Drupal'],
                'icon' => '💧'
            ],
            'Shopify' => [
                'html' => ['cdn.shopify.com', 'shopify-analytics'],
                'meta' => [],
                'headers' => ['X-ShopId'],
                'icon' => '🛍️'
            ],
            'Magento' => [
                'html' => ['/skin/frontend/', 'Mage.Cookies'],
                'meta' => [],
                'headers' => [],
                'icon' => '🛒'
            ],
            'PrestaShop' => [
                'html' => ['/modules/blockuserinfo', 'prestashop'],
                'meta' => ['generator' => 'PrestaShop'],
                'headers' => [],
                'icon' => '🏪'
            ],
            'Wix' => [
                'html' => ['static.wixstatic.com', '_wix'],
                'meta' => ['generator' => 'Wix'],
                'headers' => [],
                'icon' => '⚡'
            ],
            'Squarespace' => [
                'html' => ['static1.squarespace.com', 'squarespace'],
                'meta' => ['generator' => 'Squarespace'],
                'headers' => [],
                'icon' => '⬛'
            ],
            'Webflow' => [
                'html' => ['webflow.com', 'data-wf-'],
                'meta' => ['generator' => 'Webflow'],
                'headers' => [],
                'icon' => '🌊'
            ]
        ],
        'frameworks' => [
            'React' => [
                'html' => ['react', '_reactRoot', 'data-reactroot'],
                'scripts' => ['react.js', 'react-dom'],
                'icon' => '⚛️'
            ],
            'Vue.js' => [
                'html' => ['data-v-', 'vue.js', '__vue__'],
                'scripts' => ['vue.js', 'vue.min.js'],
                'icon' => '💚'
            ],
            'Angular' => [
                'html' => ['ng-version', 'ng-app', '_ngcontent'],
                'scripts' => ['angular.js', '@angular'],
                'icon' => '🅰️'
            ],
            'Next.js' => [
                'html' => ['__NEXT_DATA__', '_next/static'],
                'scripts' => ['_next/'],
                'icon' => '▲'
            ],
            'Nuxt.js' => [
                'html' => ['__NUXT__', '_nuxt/'],
                'scripts' => ['_nuxt/'],
                'icon' => '💚'
            ],
            'Svelte' => [
                'html' => ['svelte'],
                'scripts' => ['svelte'],
                'icon' => '🔥'
            ],
            'jQuery' => [
                'scripts' => ['jquery'],
                'icon' => '📘'
            ],
            'Bootstrap' => [
                'html' => ['bootstrap'],
                'styles' => ['bootstrap.css'],
                'icon' => '🅱️'
            ],
            'Tailwind CSS' => [
                'html' => ['tailwindcss'],
                'styles' => ['tailwind'],
                'icon' => '🌪️'
            ]
        ],
        'analytics' => [
            'Google Analytics' => [
                'html' => ['google-analytics.com', 'ga.js', 'analytics.js', 'gtag.js'],
                'pattern' => '/UA-\d+-\d+|G-[A-Z0-9]+/',
                'icon' => '📊'
            ],
            'Google Tag Manager' => [
                'html' => ['googletagmanager.com', 'gtm.js'],
                'pattern' => '/GTM-[A-Z0-9]+/',
                'icon' => '🏷️'
            ],
            'Facebook Pixel' => [
                'html' => ['connect.facebook.net', 'fbq('],
                'pattern' => '/facebook pixel|fbq/',
                'icon' => '📘'
            ],
            'Hotjar' => [
                'html' => ['static.hotjar.com', 'hjid'],
                'icon' => '🔥'
            ],
            'Mixpanel' => [
                'html' => ['mixpanel.com', 'mixpanel'],
                'icon' => '📈'
            ],
            'Segment' => [
                'html' => ['segment.com', 'analytics.load'],
                'icon' => '🔗'
            ],
            'Matomo' => [
                'html' => ['matomo', 'piwik'],
                'icon' => '📊'
            ],
            'Plausible' => [
                'html' => ['plausible.io'],
                'icon' => '📊'
            ]
        ],
        'marketing' => [
            'HubSpot' => [
                'html' => ['hubspot', 'hs-script'],
                'icon' => '🧲'
            ],
            'Mailchimp' => [
                'html' => ['mailchimp', 'mc.js'],
                'icon' => '📧'
            ],
            'Intercom' => [
                'html' => ['intercom', 'widget.intercom.io'],
                'icon' => '💬'
            ],
            'Drift' => [
                'html' => ['drift.com', 'driftt.com'],
                'icon' => '💬'
            ],
            'Zendesk' => [
                'html' => ['zendesk', 'zdassets'],
                'icon' => '🎫'
            ],
            'LiveChat' => [
                'html' => ['livechatinc.com', 'livechat'],
                'icon' => '💬'
            ],
            'Tawk.to' => [
                'html' => ['tawk.to', 'tawkto'],
                'icon' => '💬'
            ],
            'Crisp' => [
                'html' => ['crisp.chat'],
                'icon' => '💬'
            ]
        ],
        'ecommerce' => [
            'WooCommerce' => [
                'html' => ['woocommerce', 'wc-'],
                'icon' => '🛒'
            ],
            'Stripe' => [
                'html' => ['stripe.com', 'stripe.js'],
                'icon' => '💳'
            ],
            'PayPal' => [
                'html' => ['paypal.com', 'paypal'],
                'icon' => '💰'
            ],
            'Snipcart' => [
                'html' => ['snipcart'],
                'icon' => '🛒'
            ]
        ],
        'cdn' => [
            'Cloudflare' => [
                'headers' => ['CF-RAY', 'cf-request-id'],
                'html' => ['cloudflare'],
                'icon' => '☁️'
            ],
            'Amazon CloudFront' => [
                'headers' => ['X-Amz-Cf-Id', 'X-Amz-Cf-Pop'],
                'html' => ['cloudfront.net'],
                'icon' => '☁️'
            ],
            'Fastly' => [
                'headers' => ['X-Served-By' => 'fastly', 'Fastly'],
                'html' => ['fastly.net'],
                'icon' => '⚡'
            ],
            'Akamai' => [
                'headers' => ['X-Akamai'],
                'icon' => '🌐'
            ],
            'KeyCDN' => [
                'headers' => ['X-Edge-Location'],
                'html' => ['keycdn.com'],
                'icon' => '🔑'
            ]
        ],
        'hosting' => [
            'Vercel' => [
                'headers' => ['x-vercel-id'],
                'html' => ['vercel'],
                'icon' => '▲'
            ],
            'Netlify' => [
                'headers' => ['x-nf-request-id'],
                'html' => ['netlify'],
                'icon' => '🌐'
            ],
            'GitHub Pages' => [
                'headers' => ['x-github-request-id'],
                'html' => ['github.io'],
                'icon' => '🐙'
            ],
            'AWS' => [
                'headers' => ['x-amz-', 'X-Amz-'],
                'icon' => '☁️'
            ]
        ],
        'security' => [
            'reCAPTCHA' => [
                'html' => ['recaptcha', 'g-recaptcha'],
                'icon' => '🛡️'
            ],
            'hCaptcha' => [
                'html' => ['hcaptcha'],
                'icon' => '🛡️'
            ],
            'Cloudflare Turnstile' => [
                'html' => ['turnstile'],
                'icon' => '🛡️'
            ],
            'Sucuri' => [
                'headers' => ['X-Sucuri'],
                'icon' => '🛡️'
            ],
            'Wordfence' => [
                'headers' => ['X-Wordfence'],
                'icon' => '🛡️'
            ]
        ],
        'fonts' => [
            'Google Fonts' => [
                'html' => ['fonts.googleapis.com'],
                'icon' => '🔤'
            ],
            'Adobe Fonts' => [
                'html' => ['use.typekit.net', 'typekit'],
                'icon' => '🔤'
            ],
            'Font Awesome' => [
                'html' => ['fontawesome', 'fa-'],
                'icon' => '⭐'
            ]
        ],
        'video' => [
            'YouTube' => [
                'html' => ['youtube.com', 'youtu.be'],
                'icon' => '📹'
            ],
            'Vimeo' => [
                'html' => ['vimeo.com'],
                'icon' => '📹'
            ],
            'Wistia' => [
                'html' => ['wistia.com'],
                'icon' => '📹'
            ]
        ],
        'maps' => [
            'Google Maps' => [
                'html' => ['maps.googleapis.com', 'maps.google.com'],
                'icon' => '🗺️'
            ],
            'Mapbox' => [
                'html' => ['mapbox.com'],
                'icon' => '🗺️'
            ],
            'OpenStreetMap' => [
                'html' => ['openstreetmap.org'],
                'icon' => '🗺️'
            ]
        ]
    ];

    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Perform comprehensive technology detection
     */
    public function detect() {
        $this->fetchPage();

        $detected = [
            'cms' => $this->detectCategory('cms'),
            'frameworks' => $this->detectCategory('frameworks'),
            'analytics' => $this->detectCategory('analytics'),
            'marketing' => $this->detectCategory('marketing'),
            'ecommerce' => $this->detectCategory('ecommerce'),
            'cdn' => $this->detectCategory('cdn'),
            'hosting' => $this->detectCategory('hosting'),
            'security' => $this->detectCategory('security'),
            'fonts' => $this->detectCategory('fonts'),
            'video' => $this->detectCategory('video'),
            'maps' => $this->detectCategory('maps'),
            'server' => $this->detectServer(),
            'programming_languages' => $this->detectProgrammingLanguages(),
            'ssl_certificate' => $this->getSSLInfo(),
            'performance' => $this->getPerformanceMetrics()
        ];

        // Add summary
        $detected['summary'] = $this->generateSummary($detected);

        return $detected;
    }

    /**
     * Fetch page content and headers
     */
    private function fetchPage() {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (compatible; TechDetector/1.0)\r\n",
                'timeout' => 30,
                'follow_location' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $this->html = @file_get_contents($this->url, false, $context);
        $this->headers = $http_response_header ?? [];

        if ($this->html) {
            $this->dom = new DOMDocument();
            @$this->dom->loadHTML($this->html);
            $this->xpath = new DOMXPath($this->dom);
        }
    }

    /**
     * Detect technologies in a category
     */
    private function detectCategory($category) {
        $detected = [];

        if (!isset($this->technologies[$category])) {
            return $detected;
        }

        foreach ($this->technologies[$category] as $name => $signatures) {
            $confidence = 0;
            $matches = [];

            // Check HTML patterns
            if (isset($signatures['html'])) {
                foreach ($signatures['html'] as $pattern) {
                    if (stripos($this->html, $pattern) !== false) {
                        $confidence += 30;
                        $matches[] = "HTML pattern: $pattern";
                    }
                }
            }

            // Check headers
            if (isset($signatures['headers'])) {
                foreach ($signatures['headers'] as $key => $value) {
                    if (is_numeric($key)) {
                        // Just check header exists
                        if ($this->hasHeader($value)) {
                            $confidence += 40;
                            $matches[] = "Header: $value";
                        }
                    } else {
                        // Check header key and value
                        if ($this->hasHeader($key, $value)) {
                            $confidence += 40;
                            $matches[] = "Header: $key = $value";
                        }
                    }
                }
            }

            // Check meta tags
            if (isset($signatures['meta'])) {
                foreach ($signatures['meta'] as $name_attr => $content) {
                    if ($this->hasMeta($name_attr, $content)) {
                        $confidence += 50;
                        $matches[] = "Meta: $name_attr = $content";
                    }
                }
            }

            // Check scripts
            if (isset($signatures['scripts'])) {
                foreach ($signatures['scripts'] as $script) {
                    if ($this->hasScript($script)) {
                        $confidence += 30;
                        $matches[] = "Script: $script";
                    }
                }
            }

            // Check styles
            if (isset($signatures['styles'])) {
                foreach ($signatures['styles'] as $style) {
                    if ($this->hasStyle($style)) {
                        $confidence += 30;
                        $matches[] = "Style: $style";
                    }
                }
            }

            // Check regex patterns
            if (isset($signatures['pattern'])) {
                if (preg_match($signatures['pattern'], $this->html)) {
                    $confidence += 40;
                    $matches[] = "Pattern match";
                }
            }

            if ($confidence > 0) {
                $detected[] = [
                    'name' => $name,
                    'confidence' => min(100, $confidence),
                    'icon' => $signatures['icon'] ?? '🔧',
                    'matches' => $matches,
                    'version' => $this->detectVersion($name)
                ];
            }
        }

        // Sort by confidence
        usort($detected, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });

        return $detected;
    }

    /**
     * Check if header exists
     */
    private function hasHeader($name, $value = null) {
        foreach ($this->headers as $header) {
            if (stripos($header, $name) !== false) {
                if ($value === null) {
                    return true;
                }
                if (stripos($header, $value) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if meta tag exists
     */
    private function hasMeta($name, $content) {
        $metas = $this->xpath->query("//meta[@name='$name']");
        foreach ($metas as $meta) {
            if (stripos($meta->getAttribute('content'), $content) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if script exists
     */
    private function hasScript($pattern) {
        $scripts = $this->xpath->query('//script[@src]');
        foreach ($scripts as $script) {
            if (stripos($script->getAttribute('src'), $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if style exists
     */
    private function hasStyle($pattern) {
        $links = $this->xpath->query('//link[@rel="stylesheet"]');
        foreach ($links as $link) {
            if (stripos($link->getAttribute('href'), $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Try to detect version
     */
    private function detectVersion($technology) {
        // WordPress
        if ($technology === 'WordPress') {
            if (preg_match('/WordPress ([0-9.]+)/i', $this->html, $matches)) {
                return $matches[1];
            }
        }

        // jQuery
        if ($technology === 'jQuery') {
            if (preg_match('/jquery[.-]([0-9.]+)/i', $this->html, $matches)) {
                return $matches[1];
            }
        }

        // Google Analytics
        if ($technology === 'Google Analytics') {
            if (preg_match('/(UA-\d+-\d+|G-[A-Z0-9]+)/', $this->html, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Detect server technology
     */
    private function detectServer() {
        $server = [
            'software' => null,
            'language' => null,
            'powered_by' => null
        ];

        foreach ($this->headers as $header) {
            if (stripos($header, 'Server:') === 0) {
                $server['software'] = trim(substr($header, 7));
            }
            if (stripos($header, 'X-Powered-By:') === 0) {
                $server['powered_by'] = trim(substr($header, 13));
            }
        }

        return $server;
    }

    /**
     * Detect programming languages
     */
    private function detectProgrammingLanguages() {
        $languages = [];

        // PHP
        if (stripos($this->html, '<?php') !== false ||
            $this->hasHeader('X-Powered-By', 'PHP') ||
            preg_match('/\.php/', $this->url)) {
            $languages[] = 'PHP';
        }

        // ASP.NET
        if ($this->hasHeader('X-Powered-By', 'ASP.NET') ||
            $this->hasHeader('X-AspNet-Version') ||
            stripos($this->html, 'ViewState') !== false) {
            $languages[] = 'ASP.NET';
        }

        // Node.js
        if ($this->hasHeader('X-Powered-By', 'Express') ||
            stripos($this->html, 'node.js') !== false) {
            $languages[] = 'Node.js';
        }

        // Python
        if ($this->hasHeader('Server', 'Python') ||
            stripos($this->html, 'django') !== false ||
            stripos($this->html, 'flask') !== false) {
            $languages[] = 'Python';
        }

        // Ruby
        if ($this->hasHeader('X-Powered-By', 'Phusion Passenger') ||
            stripos($this->html, 'ruby') !== false) {
            $languages[] = 'Ruby';
        }

        return $languages;
    }

    /**
     * Get SSL certificate info
     */
    private function getSSLInfo() {
        $ssl = [
            'enabled' => strpos($this->url, 'https://') === 0,
            'certificate' => null
        ];

        if ($ssl['enabled']) {
            $domain = parse_url($this->url, PHP_URL_HOST);
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $socket = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket) {
                $params = stream_context_get_params($socket);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

                $ssl['certificate'] = [
                    'issuer' => $cert['issuer']['O'] ?? 'Unknown',
                    'valid_from' => date('Y-m-d', $cert['validFrom_time_t']),
                    'valid_to' => date('Y-m-d', $cert['validTo_time_t']),
                    'days_remaining' => ceil(($cert['validTo_time_t'] - time()) / 86400)
                ];

                fclose($socket);
            }
        }

        return $ssl;
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics() {
        return [
            'html_size' => strlen($this->html),
            'html_size_kb' => round(strlen($this->html) / 1024, 2),
            'load_time' => null, // Would need actual timing
            'compression' => $this->hasHeader('Content-Encoding', 'gzip') || $this->hasHeader('Content-Encoding', 'br'),
            'http_version' => $this->detectHTTPVersion()
        ];
    }

    /**
     * Detect HTTP version
     */
    private function detectHTTPVersion() {
        if (!empty($this->headers[0])) {
            if (strpos($this->headers[0], 'HTTP/2') !== false) {
                return 'HTTP/2';
            }
            if (strpos($this->headers[0], 'HTTP/3') !== false) {
                return 'HTTP/3';
            }
            if (strpos($this->headers[0], 'HTTP/1.1') !== false) {
                return 'HTTP/1.1';
            }
        }
        return 'Unknown';
    }

    /**
     * Generate summary
     */
    private function generateSummary($detected) {
        $total = 0;
        $by_category = [];

        foreach ($detected as $category => $technologies) {
            if (is_array($technologies) && !in_array($category, ['summary', 'server', 'programming_languages', 'ssl_certificate', 'performance'])) {
                $count = count($technologies);
                $total += $count;
                $by_category[$category] = $count;
            }
        }

        return [
            'total_technologies' => $total,
            'by_category' => $by_category,
            'cms_detected' => !empty($detected['cms']),
            'cms_name' => !empty($detected['cms']) ? $detected['cms'][0]['name'] : null,
            'has_analytics' => !empty($detected['analytics']),
            'has_cdn' => !empty($detected['cdn']),
            'is_ecommerce' => !empty($detected['ecommerce'])
        ];
    }
}

/**
 * Helper function
 */
function detectWebTechnologyAdvanced($url) {
    $detector = new WebTechnologyAdvanced($url);
    return $detector->detect();
}
