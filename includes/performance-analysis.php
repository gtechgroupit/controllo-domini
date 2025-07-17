<?php
/**
 * Funzioni per l'analisi delle performance e velocità pagina
 * 
 * @package ControlDomini
 * @subpackage Performance
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza le performance di un sito web
 * 
 * @param string $url URL da analizzare
 * @return array Metriche di performance
 */
function analyzePerformance($url) {
    $results = array(
        'url' => $url,
        'metrics' => array(),
        'resources' => array(),
        'waterfall' => array(),
        'opportunities' => array(),
        'diagnostics' => array(),
        'score' => 0,
        'grade' => 'F',
        'scan_time' => date('Y-m-d H:i:s')
    );
    
    // Recupera dati della pagina
    $page_data = fetchPageWithTiming($url);
    
    if (!$page_data['success']) {
        $results['error'] = 'Impossibile accedere alla pagina';
        return $results;
    }
    
    // Core Web Vitals e metriche principali
    $results['metrics'] = calculatePerformanceMetrics($page_data);
    
    // Analisi risorse
    $results['resources'] = analyzePageResources($page_data['html'], $url);
    
    // Waterfall timing
    $results['waterfall'] = generateWaterfall($page_data, $results['resources']);
    
    // Analisi immagini
    $results['images_analysis'] = analyzeImages($page_data['html'], $url);
    
    // Analisi JavaScript e CSS
    $results['js_css_analysis'] = analyzeJsCss($page_data['html']);
    
    // Analisi cache
    $results['cache_analysis'] = analyzeCaching($page_data['headers']);
    
    // Analisi compressione
    $results['compression_analysis'] = analyzeCompression($page_data);
    
    // Third-party resources
    $results['third_party'] = analyzeThirdPartyResources($results['resources'], $url);
    
    // Calcola opportunità di ottimizzazione
    $results['opportunities'] = identifyOptimizationOpportunities($results);
    
    // Diagnostica problemi
    $results['diagnostics'] = runDiagnostics($results);
    
    // Calcola score e grade
    $scoring = calculatePerformanceScore($results);
    $results['score'] = $scoring['score'];
    $results['grade'] = $scoring['grade'];
    $results['score_breakdown'] = $scoring['breakdown'];
    
    // Genera raccomandazioni
    $results['recommendations'] = generatePerformanceRecommendations($results);
    
    return $results;
}

/**
 * Recupera pagina con timing dettagliato
 * 
 * @param string $url URL da analizzare
 * @return array Dati pagina con timing
 */
function fetchPageWithTiming($url) {
    $result = array(
        'success' => false,
        'html' => '',
        'headers' => array(),
        'timing' => array(),
        'size' => 0,
        'response_code' => 0
    );
    
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
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ));
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $end_time = microtime(true);
    
    curl_close($ch);
    
    if ($info['http_code'] == 200) {
        $result['success'] = true;
        $result['response_code'] = $info['http_code'];
        
        // Separa headers e body
        $header_size = $info['header_size'];
        $header_string = substr($response, 0, $header_size);
        $result['html'] = substr($response, $header_size);
        $result['headers'] = parseHeaders($header_string);
        
        // Timing dettagliato
        $result['timing'] = array(
            'dns_lookup' => round($info['namelookup_time'] * 1000, 2),
            'tcp_connection' => round(($info['connect_time'] - $info['namelookup_time']) * 1000, 2),
            'ssl_handshake' => isset($info['appconnect_time']) ? 
                round(($info['appconnect_time'] - $info['connect_time']) * 1000, 2) : 0,
            'ttfb' => round($info['starttransfer_time'] * 1000, 2),
            'download' => round(($info['total_time'] - $info['starttransfer_time']) * 1000, 2),
            'total' => round($info['total_time'] * 1000, 2)
        );
        
        $result['size'] = strlen($response);
        $result['download_size'] = $info['size_download'];
        $result['speed_download'] = $info['speed_download'];
    }
    
    return $result;
}

/**
 * Calcola metriche di performance
 * 
 * @param array $page_data Dati della pagina
 * @return array Metriche
 */
function calculatePerformanceMetrics($page_data) {
    $metrics = array(
        // Core Web Vitals (stimati)
        'lcp' => array(
            'value' => 0,
            'score' => 'needs-improvement',
            'displayValue' => ''
        ),
        'fid' => array(
            'value' => 0,
            'score' => 'good',
            'displayValue' => ''
        ),
        'cls' => array(
            'value' => 0,
            'score' => 'good',
            'displayValue' => ''
        ),
        
        // Altre metriche importanti
        'ttfb' => array(
            'value' => $page_data['timing']['ttfb'],
            'score' => $page_data['timing']['ttfb'] < 600 ? 'good' : 
                      ($page_data['timing']['ttfb'] < 1000 ? 'needs-improvement' : 'poor'),
            'displayValue' => $page_data['timing']['ttfb'] . ' ms'
        ),
        'fcp' => array(
            'value' => $page_data['timing']['ttfb'] + 100, // Stima
            'score' => 'needs-improvement',
            'displayValue' => ''
        ),
        'speed_index' => array(
            'value' => $page_data['timing']['total'],
            'score' => $page_data['timing']['total'] < 3000 ? 'good' :
                      ($page_data['timing']['total'] < 5000 ? 'needs-improvement' : 'poor'),
            'displayValue' => $page_data['timing']['total'] . ' ms'
        ),
        'page_weight' => array(
            'value' => $page_data['size'],
            'score' => $page_data['size'] < 1048576 ? 'good' :
                      ($page_data['size'] < 3145728 ? 'needs-improvement' : 'poor'),
            'displayValue' => formatBytes($page_data['size'])
        )
    );
    
    // Stima LCP basata su TTFB e download time
    $metrics['lcp']['value'] = $page_data['timing']['ttfb'] + $page_data['timing']['download'] + 500;
    $metrics['lcp']['score'] = $metrics['lcp']['value'] < 2500 ? 'good' :
                               ($metrics['lcp']['value'] < 4000 ? 'needs-improvement' : 'poor');
    $metrics['lcp']['displayValue'] = $metrics['lcp']['value'] . ' ms';
    
    // Stima FID (First Input Delay)
    $js_blocking = estimateJsBlockingTime($page_data['html']);
    $metrics['fid']['value'] = $js_blocking;
    $metrics['fid']['score'] = $js_blocking < 100 ? 'good' :
                               ($js_blocking < 300 ? 'needs-improvement' : 'poor');
    $metrics['fid']['displayValue'] = $js_blocking . ' ms';
    
    // FCP
    $metrics['fcp']['displayValue'] = $metrics['fcp']['value'] . ' ms';
    
    return $metrics;
}

/**
 * Analizza le risorse della pagina
 * 
 * @param string $html HTML della pagina
 * @param string $base_url URL base
 * @return array Analisi risorse
 */
function analyzePageResources($html, $base_url) {
    $resources = array(
        'total' => 0,
        'by_type' => array(
            'images' => array(),
            'stylesheets' => array(),
            'scripts' => array(),
            'fonts' => array(),
            'other' => array()
        ),
        'external' => array(),
        'size_by_type' => array(
            'images' => 0,
            'stylesheets' => 0,
            'scripts' => 0,
            'fonts' => 0,
            'other' => 0
        )
    );
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    // Immagini
    $images = $dom->getElementsByTagName('img');
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
            $resource = analyzeResource($src, $base_url, 'image');
            $resources['by_type']['images'][] = $resource;
            $resources['size_by_type']['images'] += $resource['size'];
            $resources['total']++;
        }
    }
    
    // CSS
    $links = $dom->getElementsByTagName('link');
    foreach ($links as $link) {
        if ($link->getAttribute('rel') === 'stylesheet') {
            $href = $link->getAttribute('href');
            if ($href) {
                $resource = analyzeResource($href, $base_url, 'stylesheet');
                $resources['by_type']['stylesheets'][] = $resource;
                $resources['size_by_type']['stylesheets'] += $resource['size'];
                $resources['total']++;
            }
        }
    }
    
    // JavaScript
    $scripts = $dom->getElementsByTagName('script');
    foreach ($scripts as $script) {
        $src = $script->getAttribute('src');
        if ($src) {
            $resource = analyzeResource($src, $base_url, 'script');
            $resource['async'] = $script->hasAttribute('async');
            $resource['defer'] = $script->hasAttribute('defer');
            $resources['by_type']['scripts'][] = $resource;
            $resources['size_by_type']['scripts'] += $resource['size'];
            $resources['total']++;
        }
    }
    
    // Fonts (in CSS)
    if (preg_match_all('/@font-face\s*{[^}]*src:\s*url\(["\']?([^"\']+)["\']?\)/i', $html, $matches)) {
        foreach ($matches[1] as $font_url) {
            $resource = analyzeResource($font_url, $base_url, 'font');
            $resources['by_type']['fonts'][] = $resource;
            $resources['size_by_type']['fonts'] += $resource['size'];
            $resources['total']++;
        }
    }
    
    // Conta risorse esterne
    foreach ($resources['by_type'] as $type => $items) {
        foreach ($items as $item) {
            if ($item['is_external']) {
                $resources['external'][] = $item;
            }
        }
    }
    
    return $resources;
}

/**
 * Analizza una singola risorsa
 * 
 * @param string $url URL della risorsa
 * @param string $base_url URL base
 * @param string $type Tipo di risorsa
 * @return array Dati risorsa
 */
function analyzeResource($url, $base_url, $type) {
    $absolute_url = resolveUrl($url, $base_url);
    $parsed_base = parse_url($base_url);
    $parsed_resource = parse_url($absolute_url);
    
    $resource = array(
        'url' => $absolute_url,
        'type' => $type,
        'is_external' => ($parsed_resource['host'] !== $parsed_base['host']),
        'size' => 0,
        'load_time' => 0,
        'status_code' => 0
    );
    
    // Ottieni dimensione risorsa (con HEAD request)
    $ch = curl_init($absolute_url);
    curl_setopt_array($ch, array(
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ));
    
    $start = microtime(true);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $resource['size'] = isset($info['download_content_length']) ? 
                        $info['download_content_length'] : 0;
    $resource['load_time'] = round((microtime(true) - $start) * 1000, 2);
    $resource['status_code'] = $info['http_code'];
    
    return $resource;
}

/**
 * Genera waterfall timing
 * 
 * @param array $page_data Dati pagina
 * @param array $resources Risorse
 * @return array Waterfall
 */
function generateWaterfall($page_data, $resources) {
    $waterfall = array(
        'document' => array(
            'url' => $page_data['url'],
            'start_time' => 0,
            'dns' => $page_data['timing']['dns_lookup'],
            'connect' => $page_data['timing']['tcp_connection'],
            'ssl' => $page_data['timing']['ssl_handshake'],
            'ttfb' => $page_data['timing']['ttfb'],
            'download' => $page_data['timing']['download'],
            'total' => $page_data['timing']['total']
        ),
        'resources' => array()
    );
    
    // Simula timing per risorse (in produzione useresti Resource Timing API)
    $current_time = $page_data['timing']['total'];
    
    foreach ($resources['by_type'] as $type => $items) {
        foreach ($items as $resource) {
            $waterfall['resources'][] = array(
                'url' => $resource['url'],
                'type' => $resource['type'],
                'start_time' => $current_time,
                'duration' => $resource['load_time'],
                'size' => $resource['size'],
                'is_external' => $resource['is_external']
            );
            
            // Simula caricamento parallelo per tipo
            if ($type === 'stylesheets' || $type === 'scripts') {
                $current_time += 50; // Piccolo delay
            }
        }
    }
    
    return $waterfall;
}

/**
 * Analizza le immagini
 * 
 * @param string $html HTML
 * @param string $base_url URL base
 * @return array Analisi immagini
 */
function analyzeImages($html, $base_url) {
    $analysis = array(
        'total_images' => 0,
        'total_size' => 0,
        'missing_alt' => 0,
        'missing_dimensions' => 0,
        'oversized' => array(),
        'unoptimized' => array(),
        'lazy_loading' => 0,
        'modern_formats' => 0
    );
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $images = $dom->getElementsByTagName('img');
    $analysis['total_images'] = $images->length;
    
    foreach ($images as $img) {
        // Alt text
        if (!$img->hasAttribute('alt') || trim($img->getAttribute('alt')) === '') {
            $analysis['missing_alt']++;
        }
        
        // Dimensioni
        if (!$img->hasAttribute('width') || !$img->hasAttribute('height')) {
            $analysis['missing_dimensions']++;
        }
        
        // Lazy loading
        if ($img->hasAttribute('loading') && $img->getAttribute('loading') === 'lazy') {
            $analysis['lazy_loading']++;
        }
        
        // Formato moderno
        $src = $img->getAttribute('src');
        if (preg_match('/\.(webp|avif)$/i', $src)) {
            $analysis['modern_formats']++;
        }
        
        // Analisi dimensione (se accessibile)
        if ($src) {
            $image_info = getImageInfo(resolveUrl($src, $base_url));
            if ($image_info) {
                $analysis['total_size'] += $image_info['size'];
                
                // Immagine troppo grande
                if ($image_info['size'] > 200000) { // 200KB
                    $analysis['oversized'][] = array(
                        'url' => $src,
                        'size' => $image_info['size'],
                        'dimensions' => $image_info['dimensions']
                    );
                }
                
                // Non ottimizzata (euristica basata su dimensioni)
                if ($image_info['size'] > 100000 && 
                    !preg_match('/\.(webp|avif)$/i', $src)) {
                    $analysis['unoptimized'][] = array(
                        'url' => $src,
                        'size' => $image_info['size'],
                        'potential_savings' => round($image_info['size'] * 0.3) // Stima 30% risparmio
                    );
                }
            }
        }
    }
    
    return $analysis;
}

/**
 * Ottiene informazioni su un'immagine
 * 
 * @param string $url URL immagine
 * @return array|false Info immagine
 */
function getImageInfo($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ));
    
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 200) {
        return array(
            'size' => $info['download_content_length'],
            'dimensions' => null // Richiederebbe download completo
        );
    }
    
    return false;
}

/**
 * Analizza JavaScript e CSS
 * 
 * @param string $html HTML
 * @return array Analisi
 */
function analyzeJsCss($html) {
    $analysis = array(
        'render_blocking_resources' => 0,
        'inline_styles' => 0,
        'inline_scripts' => 0,
        'minified_css' => 0,
        'minified_js' => 0,
        'total_css_size' => 0,
        'total_js_size' => 0
    );
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    // CSS render-blocking
    $links = $dom->getElementsByTagName('link');
    foreach ($links as $link) {
        if ($link->getAttribute('rel') === 'stylesheet' && 
            !$link->hasAttribute('media') || 
            $link->getAttribute('media') === 'all' ||
            $link->getAttribute('media') === 'screen') {
            $analysis['render_blocking_resources']++;
        }
    }
    
    // JavaScript render-blocking
    $scripts = $dom->getElementsByTagName('script');
    foreach ($scripts as $script) {
        if ($script->hasAttribute('src') && 
            !$script->hasAttribute('async') && 
            !$script->hasAttribute('defer')) {
            $analysis['render_blocking_resources']++;
        }
        
        // Inline scripts
        if (!$script->hasAttribute('src') && trim($script->textContent)) {
            $analysis['inline_scripts']++;
        }
    }
    
    // Inline styles
    $styles = $dom->getElementsByTagName('style');
    $analysis['inline_styles'] = $styles->length;
    
    // Controlla minificazione (euristica)
    if (preg_match_all('/<link[^>]+href=["\']([^"\']+\.css)["\'][^>]*>/i', $html, $matches)) {
        foreach ($matches[1] as $css_url) {
            if (strpos($css_url, '.min.css') !== false || 
                strpos($css_url, 'min.css') !== false) {
                $analysis['minified_css']++;
            }
        }
    }
    
    if (preg_match_all('/<script[^>]+src=["\']([^"\']+\.js)["\'][^>]*>/i', $html, $matches)) {
        foreach ($matches[1] as $js_url) {
            if (strpos($js_url, '.min.js') !== false || 
                strpos($js_url, 'min.js') !== false) {
                $analysis['minified_js']++;
            }
        }
    }
    
    return $analysis;
}

/**
 * Analizza caching
 * 
 * @param array $headers Headers HTTP
 * @return array Analisi cache
 */
function analyzeCaching($headers) {
    $analysis = array(
        'cache_control' => null,
        'expires' => null,
        'etag' => null,
        'last_modified' => null,
        'cache_score' => 0,
        'issues' => array()
    );
    
    // Cache-Control
    if (isset($headers['cache-control'])) {
        $analysis['cache_control'] = $headers['cache-control'];
        
        // Analizza direttive
        if (strpos($headers['cache-control'], 'no-cache') !== false ||
            strpos($headers['cache-control'], 'no-store') !== false) {
            $analysis['issues'][] = 'Cache disabilitata';
        } elseif (preg_match('/max-age=(\d+)/', $headers['cache-control'], $matches)) {
            $max_age = (int)$matches[1];
            if ($max_age < 3600) {
                $analysis['issues'][] = 'Cache duration troppo breve';
            } else {
                $analysis['cache_score'] += 40;
            }
        }
    } else {
        $analysis['issues'][] = 'Header Cache-Control mancante';
    }
    
    // Expires
    if (isset($headers['expires'])) {
        $analysis['expires'] = $headers['expires'];
        $expires_time = strtotime($headers['expires']);
        if ($expires_time && $expires_time > time()) {
            $analysis['cache_score'] += 20;
        }
    }
    
    // ETag
    if (isset($headers['etag'])) {
        $analysis['etag'] = $headers['etag'];
        $analysis['cache_score'] += 20;
    }
    
    // Last-Modified
    if (isset($headers['last-modified'])) {
        $analysis['last_modified'] = $headers['last-modified'];
        $analysis['cache_score'] += 20;
    }
    
    if ($analysis['cache_score'] == 0) {
        $analysis['issues'][] = 'Nessun meccanismo di cache implementato';
    }
    
    return $analysis;
}

/**
 * Analizza compressione
 * 
 * @param array $page_data Dati pagina
 * @return array Analisi compressione
 */
function analyzeCompression($page_data) {
    $analysis = array(
        'content_encoding' => null,
        'original_size' => $page_data['size'],
        'compressed_size' => $page_data['download_size'],
        'compression_ratio' => 0,
        'savings' => 0,
        'is_compressed' => false
    );
    
    if (isset($page_data['headers']['content-encoding'])) {
        $analysis['content_encoding'] = $page_data['headers']['content-encoding'];
        $analysis['is_compressed'] = true;
        
        if ($analysis['original_size'] > 0) {
            $analysis['compression_ratio'] = round(
                (1 - ($analysis['compressed_size'] / $analysis['original_size'])) * 100, 
                1
            );
            $analysis['savings'] = $analysis['original_size'] - $analysis['compressed_size'];
        }
    }
    
    return $analysis;
}

/**
 * Analizza risorse di terze parti
 * 
 * @param array $resources Risorse
 * @param string $base_url URL base
 * @return array Analisi third-party
 */
function analyzeThirdPartyResources($resources, $base_url) {
    $analysis = array(
        'total' => 0,
        'total_size' => 0,
        'by_domain' => array(),
        'categories' => array()
    );
    
    $parsed_base = parse_url($base_url);
    
    foreach ($resources['external'] as $resource) {
        $parsed = parse_url($resource['url']);
        $domain = $parsed['host'];
        
        if (!isset($analysis['by_domain'][$domain])) {
            $analysis['by_domain'][$domain] = array(
                'count' => 0,
                'size' => 0,
                'category' => categorizeThirdPartyDomain($domain)
            );
        }
        
        $analysis['by_domain'][$domain]['count']++;
        $analysis['by_domain'][$domain]['size'] += $resource['size'];
        $analysis['total']++;
        $analysis['total_size'] += $resource['size'];
        
        // Categorizza
        $category = $analysis['by_domain'][$domain]['category'];
        if (!isset($analysis['categories'][$category])) {
            $analysis['categories'][$category] = 0;
        }
        $analysis['categories'][$category]++;
    }
    
    return $analysis;
}

/**
 * Categorizza dominio di terze parti
 * 
 * @param string $domain Dominio
 * @return string Categoria
 */
function categorizeThirdPartyDomain($domain) {
    $categories = array(
        'analytics' => array('google-analytics.com', 'googletagmanager.com', 'segment.com'),
        'cdn' => array('cloudflare.com', 'cloudfront.net', 'akamaihd.net', 'fastly.net'),
        'fonts' => array('fonts.googleapis.com', 'fonts.gstatic.com', 'typekit.net'),
        'ads' => array('doubleclick.net', 'googlesyndication.com', 'adsystem.com'),
        'social' => array('facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com'),
        'video' => array('youtube.com', 'vimeo.com', 'wistia.com'),
        'maps' => array('maps.googleapis.com', 'maps.google.com'),
        'payment' => array('stripe.com', 'paypal.com', 'checkout.com')
    );
    
    foreach ($categories as $category => $domains) {
        foreach ($domains as $known_domain) {
            if (strpos($domain, $known_domain) !== false) {
                return $category;
            }
        }
    }
    
    return 'other';
}

/**
 * Identifica opportunità di ottimizzazione
 * 
 * @param array $results Risultati analisi
 * @return array Opportunità
 */
function identifyOptimizationOpportunities($results) {
    $opportunities = array();
    
    // Immagini non ottimizzate
    if (!empty($results['images_analysis']['unoptimized'])) {
        $total_savings = array_sum(array_column($results['images_analysis']['unoptimized'], 'potential_savings'));
        $opportunities[] = array(
            'id' => 'optimize-images',
            'title' => 'Ottimizza immagini',
            'savings' => formatBytes($total_savings),
            'impact' => 'high',
            'effort' => 'low',
            'description' => 'Comprimi e converti le immagini in formati moderni'
        );
    }
    
    // Risorse render-blocking
    if ($results['js_css_analysis']['render_blocking_resources'] > 2) {
        $opportunities[] = array(
            'id' => 'eliminate-render-blocking',
            'title' => 'Elimina risorse che bloccano il rendering',
            'savings' => ($results['js_css_analysis']['render_blocking_resources'] * 100) . 'ms',
            'impact' => 'high',
            'effort' => 'medium',
            'description' => 'Usa async/defer per JS e carica CSS critici inline'
        );
    }
    
    // Compressione mancante
    if (!$results['compression_analysis']['is_compressed']) {
        $potential_savings = round($results['compression_analysis']['original_size'] * 0.7);
        $opportunities[] = array(
            'id' => 'enable-compression',
            'title' => 'Abilita compressione testo',
            'savings' => formatBytes($potential_savings),
            'impact' => 'high',
            'effort' => 'low',
            'description' => 'Abilita gzip o brotli sul server'
        );
    }
    
    // Cache non ottimale
    if ($results['cache_analysis']['cache_score'] < 60) {
        $opportunities[] = array(
            'id' => 'optimize-caching',
            'title' => 'Ottimizza policy di cache',
            'savings' => 'Riduci richieste del 30%',
            'impact' => 'medium',
            'effort' => 'low',
            'description' => 'Implementa cache headers appropriati'
        );
    }
    
    // Troppe risorse di terze parti
    if ($results['third_party']['total'] > 10) {
        $opportunities[] = array(
            'id' => 'reduce-third-party',
            'title' => 'Riduci impatto di terze parti',
            'savings' => formatBytes($results['third_party']['total_size']),
            'impact' => 'medium',
            'effort' => 'medium',
            'description' => 'Valuta necessità di ogni risorsa esterna'
        );
    }
    
    // JavaScript non minificato
    $total_js = count($results['resources']['by_type']['scripts']);
    if ($total_js > 0 && $results['js_css_analysis']['minified_js'] < $total_js) {
        $opportunities[] = array(
            'id' => 'minify-javascript',
            'title' => 'Minifica JavaScript',
            'savings' => 'Riduci dimensione del 20-30%',
            'impact' => 'medium',
            'effort' => 'low',
            'description' => 'Minifica tutti i file JavaScript'
        );
    }
    
    return $opportunities;
}

/**
 * Esegue diagnostica
 * 
 * @param array $results Risultati
 * @return array Diagnostica
 */
function runDiagnostics($results) {
    $diagnostics = array();
    
    // TTFB alto
    if ($results['metrics']['ttfb']['value'] > 1000) {
        $diagnostics[] = array(
            'id' => 'high-ttfb',
            'severity' => 'warning',
            'title' => 'Time to First Byte elevato',
            'description' => 'Il server impiega troppo tempo a rispondere',
            'value' => $results['metrics']['ttfb']['displayValue']
        );
    }
    
    // Pagina troppo pesante
    if ($results['metrics']['page_weight']['value'] > 3145728) { // 3MB
        $diagnostics[] = array(
            'id' => 'large-page-size',
            'severity' => 'warning',
            'title' => 'Dimensione pagina elevata',
            'description' => 'La pagina è troppo pesante per connessioni lente',
            'value' => $results['metrics']['page_weight']['displayValue']
        );
    }
    
    // Troppe richieste
    if ($results['resources']['total'] > 100) {
        $diagnostics[] = array(
            'id' => 'too-many-requests',
            'severity' => 'warning',
            'title' => 'Troppe richieste HTTP',
            'description' => 'Riduci il numero di richieste per migliorare le performance',
            'value' => $results['resources']['total'] . ' richieste'
        );
    }
    
    // Immagini senza lazy loading
    $lazy_percentage = $results['images_analysis']['total_images'] > 0 ?
        ($results['images_analysis']['lazy_loading'] / $results['images_analysis']['total_images']) * 100 : 0;
    
    if ($lazy_percentage < 50 && $results['images_analysis']['total_images'] > 5) {
        $diagnostics[] = array(
            'id' => 'missing-lazy-loading',
            'severity' => 'info',
            'title' => 'Lazy loading non implementato',
            'description' => 'Usa lazy loading per le immagini fuori viewport',
            'value' => round($lazy_percentage) . '% delle immagini'
        );
    }
    
    return $diagnostics;
}

/**
 * Calcola score performance
 * 
 * @param array $results Risultati
 * @return array Score e grade
 */
function calculatePerformanceScore($results) {
    $score = 100;
    $breakdown = array();
    
    // Metriche Core Web Vitals (40% del punteggio)
    $cwv_score = 0;
    if ($results['metrics']['lcp']['score'] === 'good') $cwv_score += 15;
    elseif ($results['metrics']['lcp']['score'] === 'needs-improvement') $cwv_score += 8;
    
    if ($results['metrics']['fid']['score'] === 'good') $cwv_score += 15;
    elseif ($results['metrics']['fid']['score'] === 'needs-improvement') $cwv_score += 8;
    
    if ($results['metrics']['cls']['score'] === 'good') $cwv_score += 10;
    elseif ($results['metrics']['cls']['score'] === 'needs-improvement') $cwv_score += 5;
    
    $breakdown['core_web_vitals'] = $cwv_score;
    
    // TTFB (15%)
    $ttfb_score = 15;
    if ($results['metrics']['ttfb']['value'] > 600) $ttfb_score -= 5;
    if ($results['metrics']['ttfb']['value'] > 1000) $ttfb_score -= 5;
    if ($results['metrics']['ttfb']['value'] > 1500) $ttfb_score -= 5;
    $breakdown['ttfb'] = max(0, $ttfb_score);
    
    // Peso pagina (15%)
    $weight_score = 15;
    if ($results['metrics']['page_weight']['value'] > 1048576) $weight_score -= 5;
    if ($results['metrics']['page_weight']['value'] > 2097152) $weight_score -= 5;
    if ($results['metrics']['page_weight']['value'] > 4194304) $weight_score -= 5;
    $breakdown['page_weight'] = max(0, $weight_score);
    
    // Ottimizzazione risorse (15%)
    $resource_score = 15;
    if ($results['js_css_analysis']['render_blocking_resources'] > 2) $resource_score -= 5;
    if ($results['resources']['total'] > 100) $resource_score -= 5;
    if ($results['third_party']['total'] > 20) $resource_score -= 5;
    $breakdown['resources'] = max(0, $resource_score);
    
    // Cache e compressione (15%)
    $optimization_score = 15;
    if (!$results['compression_analysis']['is_compressed']) $optimization_score -= 8;
    if ($results['cache_analysis']['cache_score'] < 60) $optimization_score -= 7;
    $breakdown['optimization'] = max(0, $optimization_score);
    
    // Calcola totale
    $score = array_sum($breakdown);
    
    // Determina grade
    if ($score >= 90) $grade = 'A';
    elseif ($score >= 80) $grade = 'B';
    elseif ($score >= 70) $grade = 'C';
    elseif ($score >= 60) $grade = 'D';
    elseif ($score >= 50) $grade = 'E';
    else $grade = 'F';
    
    return array(
        'score' => $score,
        'grade' => $grade,
        'breakdown' => $breakdown
    );
}

/**
 * Genera raccomandazioni performance
 * 
 * @param array $results Risultati
 * @return array Raccomandazioni
 */
function generatePerformanceRecommendations($results) {
    $recommendations = array();
    
    // Raccomandazioni basate su score
    if ($results['score'] < 50) {
        $recommendations[] = array(
            'priority' => 'critical',
            'title' => 'Performance critiche',
            'description' => 'Il sito ha gravi problemi di performance che impattano l\'esperienza utente',
            'actions' => array(
                'Implementa le ottimizzazioni suggerite con priorità alta',
                'Considera un audit professionale delle performance',
                'Monitora continuamente le metriche Core Web Vitals'
            )
        );
    }
    
    // TTFB
    if ($results['metrics']['ttfb']['value'] > 1000) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Riduci il Time to First Byte',
            'description' => 'Il server impiega ' . $results['metrics']['ttfb']['displayValue'] . ' per rispondere',
            'actions' => array(
                'Ottimizza le query del database',
                'Implementa caching server-side',
                'Considera un hosting più performante',
                'Usa un CDN per ridurre la latenza'
            )
        );
    }
    
    // LCP
    if ($results['metrics']['lcp']['score'] !== 'good') {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Migliora Largest Contentful Paint',
            'description' => 'LCP attuale: ' . $results['metrics']['lcp']['displayValue'],
            'actions' => array(
                'Ottimizza le immagini hero',
                'Preload risorse critiche',
                'Riduci JavaScript blocking',
                'Implementa lazy loading per contenuti non critici'
            )
        );
    }
    
    // Immagini
    if (!empty($results['images_analysis']['unoptimized'])) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Ottimizza le immagini',
            'description' => count($results['images_analysis']['unoptimized']) . ' immagini possono essere ottimizzate',
            'actions' => array(
                'Comprimi le immagini senza perdita di qualità',
                'Usa formati moderni (WebP, AVIF)',
                'Implementa responsive images con srcset',
                'Abilita lazy loading per immagini fuori viewport'
            )
        );
    }
    
    // JavaScript
    if ($results['js_css_analysis']['render_blocking_resources'] > 2) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Elimina JavaScript e CSS che bloccano il rendering',
            'description' => $results['js_css_analysis']['render_blocking_resources'] . ' risorse bloccano il rendering',
            'actions' => array(
                'Usa async o defer per JavaScript non critico',
                'Inline CSS critico nel <head>',
                'Carica CSS non critico in modo asincrono',
                'Rimuovi JavaScript e CSS non utilizzati'
            )
        );
    }
    
    // Third-party
    if ($results['third_party']['total'] > 10) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Riduci l\'impatto delle risorse di terze parti',
            'description' => $results['third_party']['total'] . ' risorse esterne caricate',
            'actions' => array(
                'Valuta la necessità di ogni servizio esterno',
                'Carica script di terze parti in modo asincrono',
                'Usa facade per widget social e video',
                'Considera self-hosting per risorse critiche'
            )
        );
    }
    
    return $recommendations;
}

/**
 * Stima tempo di blocking JavaScript
 * 
 * @param string $html HTML
 * @return int Tempo stimato in ms
 */
function estimateJsBlockingTime($html) {
    // Euristica semplice basata su quantità di JS inline
    $inline_js_size = 0;
    
    if (preg_match_all('/<script[^>]*>([^<]+)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $js) {
            $inline_js_size += strlen($js);
        }
    }
    
    // Stima ~1ms per KB di JS da parsare
    return round($inline_js_size / 1024);
}

/**
 * Risolve URL relativo in assoluto
 * 
 * @param string $url URL potenzialmente relativo
 * @param string $base URL base
 * @return string URL assoluto
 */
function resolveUrl($url, $base) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    
    $parsed_base = parse_url($base);
    
    if (strpos($url, '//') === 0) {
        return $parsed_base['scheme'] . ':' . $url;
    }
    
    if (strpos($url, '/') === 0) {
        return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
    }
    
    $path = isset($parsed_base['path']) ? dirname($parsed_base['path']) : '';
    return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $path . '/' . $url;
}

/**
 * Formatta bytes in formato leggibile
 * 
 * @param int $bytes Bytes
 * @return string Formato leggibile
 */
function formatBytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}
