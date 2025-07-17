<?php
/**
 * Funzioni per il rilevamento dello stack tecnologico
 * 
 * @package ControlDomini
 * @subpackage Analysis
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Rileva lo stack tecnologico di un sito web
 * 
 * @param string $domain Dominio da analizzare
 * @return array Stack tecnologico rilevato
 */
function detectTechnologyStack($domain) {
    $results = array(
        'url' => "https://{$domain}",
        'technologies' => array(),
        'categories' => array(),
        'confidence_scores' => array(),
        'raw_data' => array(),
        'scan_time' => date('Y-m-d H:i:s'),
        'total_technologies' => 0,
        'recommendations' => array()
    );
    
    // Recupera dati dal sito
    $site_data = fetchSiteData($domain);
    
    if (!$site_data['success']) {
        $results['error'] = 'Impossibile accedere al sito';
        return $results;
    }
    
    $results['raw_data'] = $site_data;
    
    // Analizza diverse fonti
    $detected = array();
    
    // 1. Analizza headers HTTP
    $header_tech = analyzeHttpHeaders($site_data['headers']);
    $detected = array_merge($detected, $header_tech);
    
    // 2. Analizza HTML
    $html_tech = analyzeHtml($site_data['html']);
    $detected = array_merge($detected, $html_tech);
    
    // 3. Analizza JavaScript
    $js_tech = analyzeJavaScript($site_data['html']);
    $detected = array_merge($detected, $js_tech);
    
    // 4. Analizza CSS
    $css_tech = analyzeCss($site_data['html']);
    $detected = array_merge($detected, $css_tech);
    
    // 5. Analizza cookies
    if (isset($site_data['cookies'])) {
        $cookie_tech = analyzeCookies($site_data['cookies']);
        $detected = array_merge($detected, $cookie_tech);
    }
    
    // 6. Analizza DNS per servizi cloud
    $dns_tech = analyzeDnsForTech($domain);
    $detected = array_merge($detected, $dns_tech);
    
    // 7. Analizza risorse esterne
    $external_tech = analyzeExternalResources($site_data['html'], $domain);
    $detected = array_merge($detected, $external_tech);
    
    // Organizza risultati
    $results = organizeTechnologyResults($detected, $results);
    
    // Genera raccomandazioni
    $results['recommendations'] = generateTechStackRecommendations($results);
    
    // Analisi sicurezza dello stack
    $results['security_analysis'] = analyzeTechStackSecurity($results['technologies']);
    
    return $results;
}

/**
 * Recupera i dati del sito
 * 
 * @param string $domain Dominio
 * @return array Dati del sito
 */
function fetchSiteData($domain) {
    $result = array(
        'success' => false,
        'html' => '',
        'headers' => array(),
        'cookies' => array(),
        'response_code' => 0
    );
    
    $url = "https://{$domain}";
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'cookie'),
        CURLOPT_COOKIEFILE => tempnam(sys_get_temp_dir(), 'cookie')
    ));
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 200) {
        $result['success'] = true;
        $result['response_code'] = $info['http_code'];
        
        // Separa headers e body
        $header_size = $info['header_size'];
        $header_string = substr($response, 0, $header_size);
        $result['html'] = substr($response, $header_size);
        
        // Parse headers
        $result['headers'] = parseHeaders($header_string);
        
        // Parse cookies
        if (isset($result['headers']['set-cookie'])) {
            $result['cookies'] = parseCookies($result['headers']['set-cookie']);
        }
    }
    
    return $result;
}

/**
 * Analizza headers HTTP per tecnologie
 * 
 * @param array $headers Headers HTTP
 * @return array Tecnologie rilevate
 */
function analyzeHttpHeaders($headers) {
    $technologies = array();
    
    // Server
    if (isset($headers['server'])) {
        $server = $headers['server'];
        
        // Web servers
        if (stripos($server, 'apache') !== false) {
            $version = extractVersion($server, '/Apache\/([\d.]+)/i');
            $technologies[] = array(
                'name' => 'Apache',
                'category' => 'Web Server',
                'version' => $version,
                'confidence' => 100,
                'source' => 'header:server'
            );
        } elseif (stripos($server, 'nginx') !== false) {
            $version = extractVersion($server, '/nginx\/([\d.]+)/i');
            $technologies[] = array(
                'name' => 'Nginx',
                'category' => 'Web Server',
                'version' => $version,
                'confidence' => 100,
                'source' => 'header:server'
            );
        } elseif (stripos($server, 'microsoft-iis') !== false) {
            $version = extractVersion($server, '/Microsoft-IIS\/([\d.]+)/i');
            $technologies[] = array(
                'name' => 'Microsoft IIS',
                'category' => 'Web Server',
                'version' => $version,
                'confidence' => 100,
                'source' => 'header:server'
            );
        } elseif (stripos($server, 'litespeed') !== false) {
            $technologies[] = array(
                'name' => 'LiteSpeed',
                'category' => 'Web Server',
                'version' => null,
                'confidence' => 100,
                'source' => 'header:server'
            );
        }
    }
    
    // X-Powered-By
    if (isset($headers['x-powered-by'])) {
        $powered = $headers['x-powered-by'];
        
        if (stripos($powered, 'php') !== false) {
            $version = extractVersion($powered, '/PHP\/([\d.]+)/i');
            $technologies[] = array(
                'name' => 'PHP',
                'category' => 'Programming Language',
                'version' => $version,
                'confidence' => 100,
                'source' => 'header:x-powered-by'
            );
        }
        if (stripos($powered, 'asp.net') !== false) {
            $technologies[] = array(
                'name' => 'ASP.NET',
                'category' => 'Web Framework',
                'version' => null,
                'confidence' => 100,
                'source' => 'header:x-powered-by'
            );
        }
        if (stripos($powered, 'express') !== false) {
            $technologies[] = array(
                'name' => 'Express.js',
                'category' => 'Web Framework',
                'version' => null,
                'confidence' => 100,
                'source' => 'header:x-powered-by'
            );
        }
    }
    
    // CDN Detection
    if (isset($headers['cf-ray'])) {
        $technologies[] = array(
            'name' => 'Cloudflare',
            'category' => 'CDN',
            'version' => null,
            'confidence' => 100,
            'source' => 'header:cf-ray'
        );
    }
    
    if (isset($headers['x-cdn']) || isset($headers['x-served-by'])) {
        $cdn = isset($headers['x-cdn']) ? $headers['x-cdn'] : $headers['x-served-by'];
        
        if (stripos($cdn, 'cloudfront') !== false) {
            $technologies[] = array(
                'name' => 'Amazon CloudFront',
                'category' => 'CDN',
                'version' => null,
                'confidence' => 100,
                'source' => 'header:x-cdn'
            );
        } elseif (stripos($cdn, 'fastly') !== false) {
            $technologies[] = array(
                'name' => 'Fastly',
                'category' => 'CDN',
                'version' => null,
                'confidence' => 100,
                'source' => 'header:x-served-by'
            );
        }
    }
    
    // Cache headers
    if (isset($headers['x-cache']) || isset($headers['x-varnish'])) {
        $technologies[] = array(
            'name' => 'Varnish',
            'category' => 'Cache',
            'version' => null,
            'confidence' => 90,
            'source' => 'header:x-varnish'
        );
    }
    
    // Security headers that indicate technologies
    if (isset($headers['x-frame-options']) || isset($headers['x-content-type-options'])) {
        // Potrebbe indicare framework di sicurezza
    }
    
    return $technologies;
}

/**
 * Analizza HTML per tecnologie
 * 
 * @param string $html Contenuto HTML
 * @return array Tecnologie rilevate
 */
function analyzeHtml($html) {
    $technologies = array();
    
    // Meta generators
    if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
        $generator = $matches[1];
        
        if (stripos($generator, 'wordpress') !== false) {
            $version = extractVersion($generator, '/WordPress\s+([\d.]+)/i');
            $technologies[] = array(
                'name' => 'WordPress',
                'category' => 'CMS',
                'version' => $version,
                'confidence' => 100,
                'source' => 'meta:generator'
            );
        } elseif (stripos($generator, 'joomla') !== false) {
            $technologies[] = array(
                'name' => 'Joomla',
                'category' => 'CMS',
                'version' => null,
                'confidence' => 100,
                'source' => 'meta:generator'
            );
        } elseif (stripos($generator, 'drupal') !== false) {
            $technologies[] = array(
                'name' => 'Drupal',
                'category' => 'CMS',
                'version' => null,
                'confidence' => 100,
                'source' => 'meta:generator'
            );
        } elseif (stripos($generator, 'shopify') !== false) {
            $technologies[] = array(
                'name' => 'Shopify',
                'category' => 'E-commerce',
                'version' => null,
                'confidence' => 100,
                'source' => 'meta:generator'
            );
        }
    }
    
    // WordPress specific
    if (strpos($html, '/wp-content/') !== false || strpos($html, '/wp-includes/') !== false) {
        $technologies[] = array(
            'name' => 'WordPress',
            'category' => 'CMS',
            'version' => null,
            'confidence' => 95,
            'source' => 'html:wp-paths'
        );
        
        // WordPress plugins
        if (preg_match_all('/\/wp-content\/plugins\/([^\/]+)\//i', $html, $matches)) {
            foreach (array_unique($matches[1]) as $plugin) {
                $technologies[] = array(
                    'name' => 'WordPress Plugin: ' . $plugin,
                    'category' => 'WordPress Plugin',
                    'version' => null,
                    'confidence' => 90,
                    'source' => 'html:wp-plugin'
                );
            }
        }
        
        // WordPress themes
        if (preg_match('/\/wp-content\/themes\/([^\/]+)\//i', $html, $matches)) {
            $technologies[] = array(
                'name' => 'WordPress Theme: ' . $matches[1],
                'category' => 'WordPress Theme',
                'version' => null,
                'confidence' => 90,
                'source' => 'html:wp-theme'
            );
        }
    }
    
    // jQuery
    if (preg_match('/jquery[.-]?([\d.]+)?(?:\.min)?\.js/i', $html, $matches)) {
        $version = isset($matches[1]) ? $matches[1] : null;
        $technologies[] = array(
            'name' => 'jQuery',
            'category' => 'JavaScript Library',
            'version' => $version,
            'confidence' => 95,
            'source' => 'html:script'
        );
    }
    
    // Bootstrap
    if (strpos($html, 'bootstrap') !== false) {
        $version = null;
        if (preg_match('/bootstrap[.-]?([\d.]+)?(?:\.min)?\.(?:css|js)/i', $html, $matches)) {
            $version = isset($matches[1]) ? $matches[1] : null;
        }
        $technologies[] = array(
            'name' => 'Bootstrap',
            'category' => 'CSS Framework',
            'version' => $version,
            'confidence' => 90,
            'source' => 'html:css/js'
        );
    }
    
    // React
    if (strpos($html, 'react') !== false || strpos($html, '_react') !== false) {
        $technologies[] = array(
            'name' => 'React',
            'category' => 'JavaScript Framework',
            'version' => null,
            'confidence' => 85,
            'source' => 'html:react'
        );
    }
    
    // Vue.js
    if (strpos($html, 'vue') !== false || preg_match('/<[^>]+\sv-[a-z]+=/i', $html)) {
        $technologies[] = array(
            'name' => 'Vue.js',
            'category' => 'JavaScript Framework',
            'version' => null,
            'confidence' => 85,
            'source' => 'html:vue'
        );
    }
    
    // Angular
    if (strpos($html, 'ng-app') !== false || strpos($html, 'angular') !== false) {
        $technologies[] = array(
            'name' => 'Angular',
            'category' => 'JavaScript Framework',
            'version' => null,
            'confidence' => 85,
            'source' => 'html:angular'
        );
    }
    
    // Google Analytics
    if (strpos($html, 'google-analytics.com/ga.js') !== false || 
        strpos($html, 'googletagmanager.com/gtag/js') !== false ||
        preg_match('/UA-\d{4,}-\d{1,}/', $html)) {
        $technologies[] = array(
            'name' => 'Google Analytics',
            'category' => 'Analytics',
            'version' => strpos($html, 'gtag') !== false ? 'GA4' : 'Universal Analytics',
            'confidence' => 100,
            'source' => 'html:analytics'
        );
    }
    
    // Google Tag Manager
    if (strpos($html, 'googletagmanager.com/gtm.js') !== false ||
        preg_match('/GTM-[A-Z0-9]+/', $html)) {
        $technologies[] = array(
            'name' => 'Google Tag Manager',
            'category' => 'Tag Manager',
            'version' => null,
            'confidence' => 100,
            'source' => 'html:gtm'
        );
    }
    
    // Facebook Pixel
    if (strpos($html, 'connect.facebook.net/en_US/fbevents.js') !== false) {
        $technologies[] = array(
            'name' => 'Facebook Pixel',
            'category' => 'Analytics',
            'version' => null,
            'confidence' => 100,
            'source' => 'html:fb-pixel'
        );
    }
    
    // Font Awesome
    if (strpos($html, 'font-awesome') !== false || strpos($html, 'fontawesome') !== false) {
        $technologies[] = array(
            'name' => 'Font Awesome',
            'category' => 'Font Icons',
            'version' => null,
            'confidence' => 95,
            'source' => 'html:fontawesome'
        );
    }
    
    // reCAPTCHA
    if (strpos($html, 'recaptcha') !== false || strpos($html, 'g-recaptcha') !== false) {
        $technologies[] = array(
            'name' => 'Google reCAPTCHA',
            'category' => 'Security',
            'version' => null,
            'confidence' => 100,
            'source' => 'html:recaptcha'
        );
    }
    
    return $technologies;
}

/**
 * Analizza JavaScript per tecnologie
 * 
 * @param string $html HTML content
 * @return array Tecnologie rilevate
 */
function analyzeJavaScript($html) {
    $technologies = array();
    
    // Estrai tutti i tag script
    preg_match_all('/<script[^>]*>.*?<\/script>/is', $html, $scripts);
    $all_scripts = implode(' ', $scripts[0]);
    
    // Webpack
    if (strpos($all_scripts, 'webpackJsonp') !== false || 
        strpos($all_scripts, '__webpack_require__') !== false) {
        $technologies[] = array(
            'name' => 'Webpack',
            'category' => 'Build Tool',
            'version' => null,
            'confidence' => 90,
            'source' => 'js:webpack'
        );
    }
    
    // Modernizr
    if (strpos($all_scripts, 'Modernizr') !== false) {
        $technologies[] = array(
            'name' => 'Modernizr',
            'category' => 'JavaScript Library',
            'version' => null,
            'confidence' => 95,
            'source' => 'js:modernizr'
        );
    }
    
    // Lodash
    if (strpos($all_scripts, 'lodash') !== false || strpos($all_scripts, '_.') !== false) {
        $technologies[] = array(
            'name' => 'Lodash',
            'category' => 'JavaScript Library',
            'version' => null,
            'confidence' => 85,
            'source' => 'js:lodash'
        );
    }
    
    // Moment.js
    if (strpos($all_scripts, 'moment') !== false) {
        $technologies[] = array(
            'name' => 'Moment.js',
            'category' => 'JavaScript Library',
            'version' => null,
            'confidence' => 85,
            'source' => 'js:moment'
        );
    }
    
    // Chart.js
    if (strpos($all_scripts, 'Chart.js') !== false || strpos($all_scripts, 'new Chart') !== false) {
        $technologies[] = array(
            'name' => 'Chart.js',
            'category' => 'JavaScript Library',
            'version' => null,
            'confidence' => 90,
            'source' => 'js:chartjs'
        );
    }
    
    // Three.js
    if (strpos($all_scripts, 'THREE') !== false || strpos($all_scripts, 'three.js') !== false) {
        $technologies[] = array(
            'name' => 'Three.js',
            'category' => 'JavaScript Library',
            'version' => null,
            'confidence' => 95,
            'source' => 'js:threejs'
        );
    }
    
    // GSAP
    if (strpos($all_scripts, 'gsap') !== false || strpos($all_scripts, 'TweenMax') !== false) {
        $technologies[] = array(
            'name' => 'GSAP',
            'category' => 'Animation Library',
            'version' => null,
            'confidence' => 90,
            'source' => 'js:gsap'
        );
    }
    
    // AOS (Animate On Scroll)
    if (strpos($all_scripts, 'AOS.init') !== false) {
        $technologies[] = array(
            'name' => 'AOS',
            'category' => 'Animation Library',
            'version' => null,
            'confidence' => 95,
            'source' => 'js:aos'
        );
    }
    
    // Service Worker
    if (strpos($all_scripts, 'serviceWorker') !== false || 
        strpos($all_scripts, 'navigator.serviceWorker') !== false) {
        $technologies[] = array(
            'name' => 'Service Worker',
            'category' => 'PWA',
            'version' => null,
            'confidence' => 95,
            'source' => 'js:service-worker'
        );
    }
    
    return $technologies;
}

/**
 * Analizza CSS per tecnologie
 * 
 * @param string $html HTML content
 * @return array Tecnologie rilevate
 */
function analyzeCss($html) {
    $technologies = array();
    
    // Tailwind CSS
    if (preg_match('/tailwind/i', $html) || 
        preg_match('/class="[^"]*(?:flex|grid|p-\d|m-\d|bg-|text-)[^"]*"/i', $html)) {
        $technologies[] = array(
            'name' => 'Tailwind CSS',
            'category' => 'CSS Framework',
            'version' => null,
            'confidence' => 80,
            'source' => 'css:tailwind'
        );
    }
    
    // Bulma
    if (strpos($html, 'bulma') !== false) {
        $technologies[] = array(
            'name' => 'Bulma',
            'category' => 'CSS Framework',
            'version' => null,
            'confidence' => 90,
            'source' => 'css:bulma'
        );
    }
    
    // Foundation
    if (strpos($html, 'foundation') !== false) {
        $technologies[] = array(
            'name' => 'Foundation',
            'category' => 'CSS Framework',
            'version' => null,
            'confidence' => 85,
            'source' => 'css:foundation'
        );
    }
    
    // Materialize CSS
    if (strpos($html, 'materialize') !== false) {
        $technologies[] = array(
            'name' => 'Materialize CSS',
            'category' => 'CSS Framework',
            'version' => null,
            'confidence' => 90,
            'source' => 'css:materialize'
        );
    }
    
    // Animate.css
    if (strpos($html, 'animate.css') !== false || strpos($html, 'animated ') !== false) {
        $technologies[] = array(
            'name' => 'Animate.css',
            'category' => 'CSS Library',
            'version' => null,
            'confidence' => 90,
            'source' => 'css:animate'
        );
    }
    
    // Normalize.css
    if (strpos($html, 'normalize.css') !== false) {
        $technologies[] = array(
            'name' => 'Normalize.css',
            'category' => 'CSS Library',
            'version' => null,
            'confidence' => 95,
            'source' => 'css:normalize'
        );
    }
    
    return $technologies;
}

/**
 * Analizza cookies per tecnologie
 * 
 * @param array $cookies Cookies
 * @return array Tecnologie rilevate
 */
function analyzeCookies($cookies) {
    $technologies = array();
    
    $cookie_patterns = array(
        'PHPSESSID' => array('name' => 'PHP', 'category' => 'Programming Language'),
        'ASP.NET_SessionId' => array('name' => 'ASP.NET', 'category' => 'Web Framework'),
        'JSESSIONID' => array('name' => 'Java', 'category' => 'Programming Language'),
        'laravel_session' => array('name' => 'Laravel', 'category' => 'Web Framework'),
        'ci_session' => array('name' => 'CodeIgniter', 'category' => 'Web Framework'),
        'wordpress_logged_in' => array('name' => 'WordPress', 'category' => 'CMS'),
        'wp-settings' => array('name' => 'WordPress', 'category' => 'CMS'),
        'PrestaShop' => array('name' => 'PrestaShop', 'category' => 'E-commerce'),
        'Drupal' => array('name' => 'Drupal', 'category' => 'CMS')
    );
    
    foreach ($cookies as $cookie) {
        $cookie_name = isset($cookie['name']) ? $cookie['name'] : '';
        
        foreach ($cookie_patterns as $pattern => $tech) {
            if (stripos($cookie_name, $pattern) !== false) {
                $technologies[] = array(
                    'name' => $tech['name'],
                    'category' => $tech['category'],
                    'version' => null,
                    'confidence' => 85,
                    'source' => 'cookie:' . $cookie_name
                );
            }
        }
    }
    
    return $technologies;
}

/**
 * Analizza DNS per tecnologie
 * 
 * @param string $domain Dominio
 * @return array Tecnologie rilevate
 */
function analyzeDnsForTech($domain) {
    $technologies = array();
    
    // MX records per provider email
    $mx_records = dns_get_record($domain, DNS_MX);
    if ($mx_records) {
        foreach ($mx_records as $mx) {
            $target = strtolower($mx['target']);
            
            if (strpos($target, 'google.com') !== false || strpos($target, 'googlemail.com') !== false) {
                $technologies[] = array(
                    'name' => 'Google Workspace',
                    'category' => 'Email Service',
                    'version' => null,
                    'confidence' => 100,
                    'source' => 'dns:mx'
                );
            } elseif (strpos($target, 'outlook.com') !== false || strpos($target, 'microsoft.com') !== false) {
                $technologies[] = array(
                    'name' => 'Microsoft 365',
                    'category' => 'Email Service',
                    'version' => null,
                    'confidence' => 100,
                    'source' => 'dns:mx'
                );
            } elseif (strpos($target, 'zoho.com') !== false) {
                $technologies[] = array(
                    'name' => 'Zoho Mail',
                    'category' => 'Email Service',
                    'version' => null,
                    'confidence' => 100,
                    'source' => 'dns:mx'
                );
            }
        }
    }
    
    // TXT records per servizi
    $txt_records = dns_get_record($domain, DNS_TXT);
    if ($txt_records) {
        foreach ($txt_records as $txt) {
            $txt_value = strtolower($txt['txt']);
            
            if (strpos($txt_value, 'v=spf1') !== false) {
                if (strpos($txt_value, 'sendgrid.net') !== false) {
                    $technologies[] = array(
                        'name' => 'SendGrid',
                        'category' => 'Email Service',
                        'version' => null,
                        'confidence' => 95,
                        'source' => 'dns:txt:spf'
                    );
                } elseif (strpos($txt_value, 'mailgun.org') !== false) {
                    $technologies[] = array(
                        'name' => 'Mailgun',
                        'category' => 'Email Service',
                        'version' => null,
                        'confidence' => 95,
                        'source' => 'dns:txt:spf'
                    );
                }
            }
            
            if (strpos($txt_value, 'google-site-verification') !== false) {
                $technologies[] = array(
                    'name' => 'Google Search Console',
                    'category' => 'SEO Tool',
                    'version' => null,
                    'confidence' => 100,
                    'source' => 'dns:txt:verification'
                );
            }
            
            if (strpos($txt_value, 'facebook-domain-verification') !== false) {
                $technologies[] = array(
                    'name' => 'Facebook Business',
                    'category' => 'Marketing Tool',
                    'version' => null,
                    'confidence' => 100,
                    'source' => 'dns:txt:verification'
                );
            }
        }
    }
    
    return $technologies;
}

/**
 * Analizza risorse esterne
 * 
 * @param string $html HTML content
 * @param string $domain Dominio principale
 * @return array Tecnologie rilevate
 */
function analyzeExternalResources($html, $domain) {
    $technologies = array();
    
    // CDN e risorse esterne
    $external_patterns = array(
        'jsdelivr.net' => array('name' => 'jsDelivr CDN', 'category' => 'CDN'),
        'unpkg.com' => array('name' => 'unpkg CDN', 'category' => 'CDN'),
        'cdnjs.cloudflare.com' => array('name' => 'cdnjs', 'category' => 'CDN'),
        'maxcdn.bootstrapcdn.com' => array('name' => 'MaxCDN', 'category' => 'CDN'),
        'code.jquery.com' => array('name' => 'jQuery CDN', 'category' => 'CDN'),
        'fonts.googleapis.com' => array('name' => 'Google Fonts', 'category' => 'Web Font'),
        'fonts.gstatic.com' => array('name' => 'Google Fonts', 'category' => 'Web Font'),
        'use.fontawesome.com' => array('name' => 'Font Awesome CDN', 'category' => 'Font Icons'),
        'kit.fontawesome.com' => array('name' => 'Font Awesome Kit', 'category' => 'Font Icons'),
        'maps.googleapis.com' => array('name' => 'Google Maps', 'category' => 'Maps Service'),
        'youtube.com' => array('name' => 'YouTube', 'category' => 'Video Platform'),
        'vimeo.com' => array('name' => 'Vimeo', 'category' => 'Video Platform'),
        'disqus.com' => array('name' => 'Disqus', 'category' => 'Comment System'),
        'stripe.com' => array('name' => 'Stripe', 'category' => 'Payment Processor'),
        'paypal.com' => array('name' => 'PayPal', 'category' => 'Payment Processor')
    );
    
    foreach ($external_patterns as $pattern => $tech) {
        if (strpos($html, $pattern) !== false) {
            $technologies[] = array(
                'name' => $tech['name'],
                'category' => $tech['category'],
                'version' => null,
                'confidence' => 95,
                'source' => 'external:' . $pattern
            );
        }
    }
    
    return $technologies;
}

/**
 * Estrae versione da una stringa
 * 
 * @param string $string Stringa da analizzare
 * @param string $pattern Pattern regex
 * @return string|null Versione estratta
 */
function extractVersion($string, $pattern) {
    if (preg_match($pattern, $string, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Parse dei cookies
 * 
 * @param string|array $cookie_header Header Set-Cookie
 * @return array Cookies parsati
 */
function parseCookies($cookie_header) {
    $cookies = array();
    
    if (!is_array($cookie_header)) {
        $cookie_header = array($cookie_header);
    }
    
    foreach ($cookie_header as $cookie_string) {
        $parts = explode(';', $cookie_string);
        if (count($parts) > 0) {
            $name_value = explode('=', trim($parts[0]), 2);
            if (count($name_value) == 2) {
                $cookies[] = array(
                    'name' => $name_value[0],
                    'value' => $name_value[1]
                );
            }
        }
    }
    
    return $cookies;
}

/**
 * Organizza i risultati delle tecnologie
 * 
 * @param array $detected Tecnologie rilevate
 * @param array $results Risultati base
 * @return array Risultati organizzati
 */
function organizeTechnologyResults($detected, $results) {
    // Rimuovi duplicati mantenendo la confidence più alta
    $unique_tech = array();
    
    foreach ($detected as $tech) {
        $key = $tech['name'] . '-' . $tech['category'];
        
        if (!isset($unique_tech[$key]) || 
            $unique_tech[$key]['confidence'] < $tech['confidence']) {
            $unique_tech[$key] = $tech;
        }
    }
    
    // Organizza per categoria
    foreach ($unique_tech as $tech) {
        $category = $tech['category'];
        
        if (!isset($results['categories'][$category])) {
            $results['categories'][$category] = array();
        }
        
        $results['categories'][$category][] = $tech;
        $results['technologies'][] = $tech;
        $results['confidence_scores'][$tech['name']] = $tech['confidence'];
    }
    
    $results['total_technologies'] = count($results['technologies']);
    
    // Ordina categorie per importanza
    $category_order = array(
        'CMS', 'E-commerce', 'Web Framework', 'Programming Language',
        'Web Server', 'Database', 'Cache', 'CDN', 'JavaScript Framework',
        'CSS Framework', 'JavaScript Library', 'Analytics', 'Tag Manager'
    );
    
    $ordered_categories = array();
    foreach ($category_order as $cat) {
        if (isset($results['categories'][$cat])) {
            $ordered_categories[$cat] = $results['categories'][$cat];
        }
    }
    
    // Aggiungi categorie rimanenti
    foreach ($results['categories'] as $cat => $techs) {
        if (!isset($ordered_categories[$cat])) {
            $ordered_categories[$cat] = $techs;
        }
    }
    
    $results['categories'] = $ordered_categories;
    
    return $results;
}

/**
 * Genera raccomandazioni per lo stack tecnologico
 * 
 * @param array $results Risultati analisi
 * @return array Raccomandazioni
 */
function generateTechStackRecommendations($results) {
    $recommendations = array();
    
    // Controlla versioni obsolete
    foreach ($results['technologies'] as $tech) {
        if ($tech['version']) {
            $outdated = checkOutdatedVersion($tech['name'], $tech['version']);
            if ($outdated) {
                $recommendations[] = array(
                    'priority' => 'high',
                    'category' => 'security',
                    'title' => 'Aggiorna ' . $tech['name'],
                    'description' => 'La versione ' . $tech['version'] . ' è obsoleta',
                    'solution' => 'Aggiorna all\'ultima versione stabile'
                );
            }
        }
    }
    
    // Sicurezza headers
    $has_security_headers = false;
    foreach ($results['technologies'] as $tech) {
        if (in_array($tech['category'], array('Security', 'WAF'))) {
            $has_security_headers = true;
            break;
        }
    }
    
    if (!$has_security_headers) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'security',
            'title' => 'Implementa header di sicurezza',
            'description' => 'Non sono stati rilevati header di sicurezza o WAF',
            'solution' => 'Implementa CSP, X-Frame-Options, X-Content-Type-Options'
        );
    }
    
    // Performance
    $has_cdn = false;
    $has_cache = false;
    
    foreach ($results['categories'] as $category => $techs) {
        if ($category === 'CDN') $has_cdn = true;
        if ($category === 'Cache') $has_cache = true;
    }
    
    if (!$has_cdn) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'performance',
            'title' => 'Considera l\'uso di un CDN',
            'description' => 'Non è stato rilevato l\'uso di un CDN',
            'solution' => 'Un CDN può migliorare significativamente le performance'
        );
    }
    
    if (!$has_cache) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'performance',
            'title' => 'Implementa caching',
            'description' => 'Non è stato rilevato un sistema di cache',
            'solution' => 'Implementa Varnish, Redis o un altro sistema di cache'
        );
    }
    
    // Analytics
    $has_analytics = isset($results['categories']['Analytics']);
    if (!$has_analytics) {
        $recommendations[] = array(
            'priority' => 'low',
            'category' => 'analytics',
            'title' => 'Aggiungi analytics',
            'description' => 'Non è stato rilevato alcun sistema di analytics',
            'solution' => 'Implementa Google Analytics o un\'alternativa'
        );
    }
    
    return $recommendations;
}

/**
 * Analizza la sicurezza dello stack tecnologico
 * 
 * @param array $technologies Tecnologie rilevate
 * @return array Analisi sicurezza
 */
function analyzeTechStackSecurity($technologies) {
    $security_analysis = array(
        'risk_level' => 'low',
        'vulnerabilities' => array(),
        'exposed_technologies' => array(),
        'recommendations' => array()
    );
    
    $risk_score = 0;
    
    foreach ($technologies as $tech) {
        // Tecnologie che espongono versione
        if ($tech['version'] && $tech['confidence'] > 90) {
            $security_analysis['exposed_technologies'][] = array(
                'name' => $tech['name'],
                'version' => $tech['version'],
                'risk' => 'Version exposure'
            );
            $risk_score += 5;
        }
        
        // Tecnologie con vulnerabilità note
        $vulns = checkKnownVulnerabilities($tech['name'], $tech['version']);
        if (!empty($vulns)) {
            $security_analysis['vulnerabilities'] = array_merge(
                $security_analysis['vulnerabilities'],
                $vulns
            );
            $risk_score += count($vulns) * 10;
        }
        
        // Tecnologie obsolete
        if (isObsoleteTechnology($tech['name'])) {
            $security_analysis['recommendations'][] = array(
                'technology' => $tech['name'],
                'issue' => 'Tecnologia obsoleta',
                'recommendation' => 'Considera la migrazione a una soluzione moderna'
            );
            $risk_score += 15;
        }
    }
    
    // Determina livello di rischio
    if ($risk_score >= 50) {
        $security_analysis['risk_level'] = 'high';
    } elseif ($risk_score >= 20) {
        $security_analysis['risk_level'] = 'medium';
    }
    
    return $security_analysis;
}

/**
 * Controlla se una versione è obsoleta
 * 
 * @param string $technology Nome tecnologia
 * @param string $version Versione
 * @return bool True se obsoleta
 */
function checkOutdatedVersion($technology, $version) {
    // Database semplificato di versioni minime consigliate
    $min_versions = array(
        'PHP' => '7.4',
        'WordPress' => '5.8',
        'jQuery' => '3.5',
        'Bootstrap' => '4.6',
        'Angular' => '10',
        'React' => '17',
        'Vue.js' => '3'
    );
    
    if (isset($min_versions[$technology]) && $version) {
        return version_compare($version, $min_versions[$technology], '<');
    }
    
    return false;
}

/**
 * Controlla vulnerabilità note
 * 
 * @param string $technology Nome tecnologia
 * @param string $version Versione
 * @return array Vulnerabilità
 */
function checkKnownVulnerabilities($technology, $version) {
    $vulnerabilities = array();
    
    // Database semplificato di vulnerabilità note
    $known_vulns = array(
        'WordPress' => array(
            '5.0-5.7.1' => array('CVE-2021-29447' => 'XXE vulnerability'),
            '4.0-4.7.1' => array('CVE-2017-5487' => 'REST API vulnerability')
        ),
        'jQuery' => array(
            '1.0-1.11.3' => array('CVE-2015-9251' => 'XSS vulnerability'),
            '2.0-2.2.4' => array('CVE-2015-9251' => 'XSS vulnerability')
        )
    );
    
    if (isset($known_vulns[$technology]) && $version) {
        foreach ($known_vulns[$technology] as $vuln_range => $cves) {
            list($min, $max) = explode('-', $vuln_range);
            if (version_compare($version, $min, '>=') && 
                version_compare($version, $max, '<=')) {
                foreach ($cves as $cve => $desc) {
                    $vulnerabilities[] = array(
                        'cve' => $cve,
                        'description' => $desc,
                        'technology' => $technology,
                        'version' => $version
                    );
                }
            }
        }
    }
    
    return $vulnerabilities;
}

/**
 * Controlla se una tecnologia è obsoleta
 * 
 * @param string $technology Nome tecnologia
 * @return bool True se obsoleta
 */
function isObsoleteTechnology($technology) {
    $obsolete = array(
        'Flash', 'Silverlight', 'Java Applet',
        'ASP Classic', 'ColdFusion'
    );
    
    return in_array($technology, $obsolete);
}
