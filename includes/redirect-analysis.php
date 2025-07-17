<?php
/**
 * Funzioni per l'analisi delle catene di redirect
 * 
 * @package ControlDomini
 * @subpackage SEO
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza la catena di redirect di un URL
 * 
 * @param string $url URL iniziale da analizzare
 * @param array $options Opzioni di analisi
 * @return array Risultati dell'analisi
 */
function analyzeRedirectChain($url, $options = array()) {
    $defaults = array(
        'max_redirects' => 10,
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (compatible; ControlloDomin/1.0)',
        'check_variants' => true,
        'check_canonical' => true
    );
    
    $options = array_merge($defaults, $options);
    
    $results = array(
        'start_url' => $url,
        'final_url' => null,
        'chain' => array(),
        'redirect_count' => 0,
        'total_time' => 0,
        'issues' => array(),
        'http_to_https' => false,
        'www_redirect' => false,
        'canonical_issues' => array(),
        'status' => 'success',
        'recommendations' => array()
    );
    
    // Analizza redirect principale
    $chain_result = followRedirectChain($url, $options);
    $results = array_merge($results, $chain_result);
    
    // Se richiesto, controlla varianti URL
    if ($options['check_variants']) {
        $results['url_variants'] = checkUrlVariants($url);
    }
    
    // Controlla canonical
    if ($options['check_canonical'] && $results['final_url']) {
        $results['canonical_analysis'] = analyzeCanonical($results['final_url']);
    }
    
    // Analizza problemi
    $results['issues'] = detectRedirectIssues($results);
    
    // Calcola score
    $results['redirect_score'] = calculateRedirectScore($results);
    
    // Genera raccomandazioni
    $results['recommendations'] = generateRedirectRecommendations($results);
    
    return $results;
}

/**
 * Segue la catena di redirect
 * 
 * @param string $url URL iniziale
 * @param array $options Opzioni
 * @return array Catena di redirect
 */
function followRedirectChain($url, $options) {
    $result = array(
        'chain' => array(),
        'redirect_count' => 0,
        'final_url' => null,
        'total_time' => 0,
        'error' => null
    );
    
    $current_url = $url;
    $visited_urls = array();
    $start_time = microtime(true);
    
    for ($i = 0; $i <= $options['max_redirects']; $i++) {
        // Controlla loop
        if (in_array($current_url, $visited_urls)) {
            $result['error'] = 'Loop di redirect rilevato';
            $result['status'] = 'error';
            break;
        }
        
        $visited_urls[] = $current_url;
        
        // Effettua richiesta
        $response = fetchUrlWithDetails($current_url, $options);
        
        // Aggiungi alla catena
        $result['chain'][] = array(
            'url' => $current_url,
            'status_code' => $response['http_code'],
            'status_text' => getHttpStatusText($response['http_code']),
            'response_time' => $response['response_time'],
            'headers' => $response['headers'],
            'redirect_to' => $response['redirect_url'],
            'redirect_type' => classifyRedirectType($response['http_code']),
            'size' => $response['size_download']
        );
        
        // Se non è un redirect, abbiamo finito
        if (!in_array($response['http_code'], array(301, 302, 303, 307, 308))) {
            $result['final_url'] = $current_url;
            break;
        }
        
        // Controlla se c'è un URL di redirect
        if (empty($response['redirect_url'])) {
            $result['error'] = 'Redirect senza destinazione';
            $result['status'] = 'error';
            break;
        }
        
        $result['redirect_count']++;
        $current_url = $response['redirect_url'];
        
        // Controlla limiti
        if ($i == $options['max_redirects']) {
            $result['error'] = 'Troppi redirect (limite: ' . $options['max_redirects'] . ')';
            $result['status'] = 'error';
        }
    }
    
    $result['total_time'] = round((microtime(true) - $start_time) * 1000, 2);
    
    // Analizza tipi di redirect nella catena
    $result['redirect_analysis'] = analyzeRedirectTypes($result['chain']);
    
    return $result;
}

/**
 * Effettua richiesta HTTP con dettagli
 * 
 * @param string $url URL da richiedere
 * @param array $options Opzioni
 * @return array Dettagli risposta
 */
function fetchUrlWithDetails($url, $options) {
    $ch = curl_init();
    
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => $options['timeout'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $options['user_agent'],
        CURLOPT_ENCODING => 'gzip, deflate'
    ));
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Parse headers
    $header_size = $info['header_size'];
    $header_string = substr($response, 0, $header_size);
    $headers = parseHeaders($header_string);
    
    // Estrai URL di redirect
    $redirect_url = null;
    if (isset($headers['location'])) {
        $redirect_url = $headers['location'];
        // Gestisci URL relativi
        if (!filter_var($redirect_url, FILTER_VALIDATE_URL)) {
            $redirect_url = resolveRelativeUrl($redirect_url, $url);
        }
    }
    
    return array(
        'http_code' => $info['http_code'],
        'response_time' => $response_time,
        'headers' => $headers,
        'redirect_url' => $redirect_url,
        'size_download' => $info['size_download'],
        'content_type' => isset($headers['content-type']) ? $headers['content-type'] : null,
        'error' => $error
    );
}

/**
 * Parse degli header HTTP
 * 
 * @param string $header_string Stringa header
 * @return array Header parsati
 */
function parseHeaders($header_string) {
    $headers = array();
    $lines = explode("\r\n", $header_string);
    
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            $headers[$key] = $value;
        }
    }
    
    return $headers;
}

/**
 * Risolve URL relativi
 * 
 * @param string $relative URL relativo
 * @param string $base URL base
 * @return string URL assoluto
 */
function resolveRelativeUrl($relative, $base) {
    // Se è già assoluto
    if (parse_url($relative, PHP_URL_SCHEME) != '') {
        return $relative;
    }
    
    // Parse base URL
    $parts = parse_url($base);
    $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
    $host = isset($parts['host']) ? $parts['host'] : '';
    $path = isset($parts['path']) ? $parts['path'] : '/';
    
    // URL relativi alla root
    if (substr($relative, 0, 1) == '/') {
        return $scheme . '://' . $host . $relative;
    }
    
    // URL relativi al path corrente
    $path = preg_replace('#/[^/]*$#', '', $path);
    return $scheme . '://' . $host . $path . '/' . $relative;
}

/**
 * Classifica il tipo di redirect
 * 
 * @param int $status_code Codice HTTP
 * @return string Tipo di redirect
 */
function classifyRedirectType($status_code) {
    $types = array(
        301 => 'Permanent (301)',
        302 => 'Temporary (302)',
        303 => 'See Other (303)',
        307 => 'Temporary (307)',
        308 => 'Permanent (308)'
    );
    
    return isset($types[$status_code]) ? $types[$status_code] : 'Unknown';
}

/**
 * Ottiene descrizione status HTTP
 * 
 * @param int $code Codice HTTP
 * @return string Descrizione
 */
function getHttpStatusText($code) {
    $statuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable'
    );
    
    return isset($statuses[$code]) ? $statuses[$code] : 'Unknown Status';
}

/**
 * Controlla varianti URL comuni
 * 
 * @param string $url URL originale
 * @return array Analisi varianti
 */
function checkUrlVariants($url) {
    $parsed = parse_url($url);
    $scheme = $parsed['scheme'];
    $host = $parsed['host'];
    $path = isset($parsed['path']) ? $parsed['path'] : '/';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    
    $variants = array();
    $base_domain = preg_replace('/^www\./', '', $host);
    
    // Definisci varianti da testare
    $test_variants = array(
        'http_no_www' => "http://{$base_domain}{$path}{$query}",
        'http_www' => "http://www.{$base_domain}{$path}{$query}",
        'https_no_www' => "https://{$base_domain}{$path}{$query}",
        'https_www' => "https://www.{$base_domain}{$path}{$query}"
    );
    
    foreach ($test_variants as $type => $variant_url) {
        // Non testare l'URL originale
        if ($variant_url === $url) {
            continue;
        }
        
        $response = fetchUrlWithDetails($variant_url, array(
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0 (compatible; ControlloDomin/1.0)'
        ));
        
        $variants[$type] = array(
            'url' => $variant_url,
            'status_code' => $response['http_code'],
            'redirects_to' => $response['redirect_url'],
            'response_time' => $response['response_time'],
            'accessible' => $response['http_code'] > 0
        );
    }
    
    // Analizza consistenza
    $analysis = analyzeVariantConsistency($variants, $url);
    
    return array(
        'variants' => $variants,
        'analysis' => $analysis
    );
}

/**
 * Analizza consistenza delle varianti URL
 * 
 * @param array $variants Varianti testate
 * @param string $original_url URL originale
 * @return array Analisi
 */
function analyzeVariantConsistency($variants, $original_url) {
    $analysis = array(
        'all_redirect_to_same' => true,
        'preferred_version' => null,
        'has_https' => false,
        'forces_https' => false,
        'has_www_consistency' => true,
        'issues' => array()
    );
    
    $final_urls = array();
    $accessible_count = 0;
    
    foreach ($variants as $type => $variant) {
        if ($variant['accessible']) {
            $accessible_count++;
            
            // Determina URL finale
            $final_url = $variant['redirects_to'] ?: $variant['url'];
            $final_urls[] = $final_url;
            
            // Controlla HTTPS
            if (strpos($variant['url'], 'https://') === 0) {
                $analysis['has_https'] = true;
            }
        }
    }
    
    // Controlla se tutti redirigono allo stesso URL
    $unique_finals = array_unique($final_urls);
    $analysis['all_redirect_to_same'] = count($unique_finals) <= 1;
    
    if (!$analysis['all_redirect_to_same']) {
        $analysis['issues'][] = array(
            'type' => 'inconsistent_redirects',
            'severity' => 'high',
            'message' => 'Le varianti URL redirigono a destinazioni diverse'
        );
    }
    
    // Determina versione preferita
    if (count($unique_finals) == 1) {
        $analysis['preferred_version'] = $unique_finals[0];
        $analysis['forces_https'] = strpos($analysis['preferred_version'], 'https://') === 0;
    }
    
    // Controlla accessibilità
    if ($accessible_count == 0) {
        $analysis['issues'][] = array(
            'type' => 'no_variants_accessible',
            'severity' => 'critical',
            'message' => 'Nessuna variante URL è accessibile'
        );
    }
    
    return $analysis;
}

/**
 * Analizza i tipi di redirect nella catena
 * 
 * @param array $chain Catena di redirect
 * @return array Analisi
 */
function analyzeRedirectTypes($chain) {
    $analysis = array(
        'permanent_count' => 0,
        'temporary_count' => 0,
        'mixed_types' => false,
        'http_to_https' => false,
        'www_normalization' => false,
        'protocol_changes' => 0,
        'domain_changes' => 0
    );
    
    $redirect_types = array();
    $previous_url = null;
    
    foreach ($chain as $step) {
        if (in_array($step['status_code'], array(301, 308))) {
            $analysis['permanent_count']++;
            $redirect_types[] = 'permanent';
        } elseif (in_array($step['status_code'], array(302, 303, 307))) {
            $analysis['temporary_count']++;
            $redirect_types[] = 'temporary';
        }
        
        if ($previous_url) {
            $prev_parsed = parse_url($previous_url);
            $curr_parsed = parse_url($step['url']);
            
            // Cambio protocollo
            if ($prev_parsed['scheme'] != $curr_parsed['scheme']) {
                $analysis['protocol_changes']++;
                if ($prev_parsed['scheme'] == 'http' && $curr_parsed['scheme'] == 'https') {
                    $analysis['http_to_https'] = true;
                }
            }
            
            // Cambio dominio
            if ($prev_parsed['host'] != $curr_parsed['host']) {
                $analysis['domain_changes']++;
                
                // Controlla normalizzazione www
                $prev_no_www = preg_replace('/^www\./', '', $prev_parsed['host']);
                $curr_no_www = preg_replace('/^www\./', '', $curr_parsed['host']);
                if ($prev_no_www == $curr_no_www) {
                    $analysis['www_normalization'] = true;
                }
            }
        }
        
        $previous_url = $step['url'];
    }
    
    // Controlla tipi misti
    $unique_types = array_unique($redirect_types);
    $analysis['mixed_types'] = count($unique_types) > 1;
    
    return $analysis;
}

/**
 * Analizza canonical URL
 * 
 * @param string $url URL da analizzare
 * @return array Analisi canonical
 */
function analyzeCanonical($url) {
    $result = array(
        'has_canonical' => false,
        'canonical_url' => null,
        'is_self_referencing' => false,
        'issues' => array()
    );
    
    // Recupera contenuto HTML
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ));
    
    $html = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 200 && $html) {
        // Cerca canonical link
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches) ||
            preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\'][^>]*>/i', $html, $matches)) {
            
            $result['has_canonical'] = true;
            $result['canonical_url'] = $matches[1];
            
            // Risolvi URL relativo
            if (!filter_var($result['canonical_url'], FILTER_VALIDATE_URL)) {
                $result['canonical_url'] = resolveRelativeUrl($result['canonical_url'], $url);
            }
            
            // Controlla self-referencing
            $result['is_self_referencing'] = ($result['canonical_url'] == $url);
            
            // Analizza problemi
            if (!$result['is_self_referencing']) {
                // Verifica se canonical è accessibile
                $canonical_check = fetchUrlWithDetails($result['canonical_url'], array('timeout' => 5));
                if ($canonical_check['http_code'] != 200) {
                    $result['issues'][] = array(
                        'type' => 'canonical_not_accessible',
                        'severity' => 'high',
                        'message' => 'URL canonical non accessibile (HTTP ' . $canonical_check['http_code'] . ')'
                    );
                }
            }
        } else {
            $result['issues'][] = array(
                'type' => 'missing_canonical',
                'severity' => 'medium',
                'message' => 'Tag canonical non trovato nella pagina'
            );
        }
    }
    
    return $result;
}

/**
 * Rileva problemi nei redirect
 * 
 * @param array $results Risultati analisi
 * @return array Issues trovate
 */
function detectRedirectIssues($results) {
    $issues = array();
    
    // Troppi redirect
    if ($results['redirect_count'] > 3) {
        $issues[] = array(
            'type' => 'too_many_redirects',
            'severity' => 'high',
            'message' => 'Catena di redirect troppo lunga (' . $results['redirect_count'] . ' redirect)',
            'impact' => 'Impatto negativo su SEO e performance'
        );
    }
    
    // Redirect chain lenta
    if ($results['total_time'] > 2000) {
        $issues[] = array(
            'type' => 'slow_redirects',
            'severity' => 'medium',
            'message' => 'Catena di redirect lenta (' . round($results['total_time'] / 1000, 1) . 's)',
            'impact' => 'Rallenta il caricamento della pagina'
        );
    }
    
    // Mix di redirect permanenti e temporanei
    if (isset($results['redirect_analysis']['mixed_types']) && 
        $results['redirect_analysis']['mixed_types']) {
        $issues[] = array(
            'type' => 'mixed_redirect_types',
            'severity' => 'medium',
            'message' => 'Mix di redirect permanenti e temporanei',
            'impact' => 'Può confondere i motori di ricerca'
        );
    }
    
    // Redirect temporanei per cambi permanenti
    if (isset($results['redirect_analysis']['temporary_count']) && 
        $results['redirect_analysis']['temporary_count'] > 0 &&
        ($results['redirect_analysis']['http_to_https'] || 
         $results['redirect_analysis']['www_normalization'])) {
        $issues[] = array(
            'type' => 'wrong_redirect_type',
            'severity' => 'medium',
            'message' => 'Usa redirect temporanei per cambi che dovrebbero essere permanenti',
            'impact' => 'I motori di ricerca non trasferiranno il ranking'
        );
    }
    
    // Loop di redirect
    if (isset($results['error']) && strpos($results['error'], 'Loop') !== false) {
        $issues[] = array(
            'type' => 'redirect_loop',
            'severity' => 'critical',
            'message' => 'Loop di redirect rilevato',
            'impact' => 'La pagina non è accessibile'
        );
    }
    
    // Varianti URL inconsistenti
    if (isset($results['url_variants']['analysis']['all_redirect_to_same']) && 
        !$results['url_variants']['analysis']['all_redirect_to_same']) {
        $issues[] = array(
            'type' => 'inconsistent_variants',
            'severity' => 'high',
            'message' => 'Le varianti URL non redirigono tutte alla stessa destinazione',
            'impact' => 'Possibili contenuti duplicati e confusione per i crawler'
        );
    }
    
    // Manca HTTPS
    if (isset($results['url_variants']['analysis']['has_https']) && 
        !$results['url_variants']['analysis']['forces_https']) {
        $issues[] = array(
            'type' => 'no_https_redirect',
            'severity' => 'medium',
            'message' => 'Non forza HTTPS',
            'impact' => 'Problemi di sicurezza e SEO'
        );
    }
    
    // Canonical issues
    if (isset($results['canonical_analysis']['issues'])) {
        foreach ($results['canonical_analysis']['issues'] as $canonical_issue) {
            $issues[] = $canonical_issue;
        }
    }
    
    return $issues;
}

/**
 * Calcola score dei redirect
 * 
 * @param array $results Risultati analisi
 * @return int Score (0-100)
 */
function calculateRedirectScore($results) {
    $score = 100;
    
    // Penalità per numero di redirect
    if ($results['redirect_count'] > 0) {
        $score -= min(30, $results['redirect_count'] * 10);
    }
    
    // Penalità per tempo
    if ($results['total_time'] > 1000) {
        $score -= min(20, floor($results['total_time'] / 100));
    }
    
    // Penalità per issues
    foreach ($results['issues'] as $issue) {
        switch ($issue['severity']) {
            case 'critical':
                $score -= 30;
                break;
            case 'high':
                $score -= 20;
                break;
            case 'medium':
                $score -= 10;
                break;
            case 'low':
                $score -= 5;
                break;
        }
    }
    
    return max(0, $score);
}

/**
 * Genera raccomandazioni per i redirect
 * 
 * @param array $results Risultati analisi
 * @return array Raccomandazioni
 */
function generateRedirectRecommendations($results) {
    $recommendations = array();
    
    // Riduci numero di redirect
    if ($results['redirect_count'] > 1) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Riduci il numero di redirect',
            'description' => 'Hai ' . $results['redirect_count'] . ' redirect. Idealmente dovresti averne al massimo 1.',
            'solution' => 'Configura redirect diretti dalla sorgente alla destinazione finale'
        );
    }
    
    // Usa redirect permanenti
    if (isset($results['redirect_analysis']['temporary_count']) && 
        $results['redirect_analysis']['temporary_count'] > 0) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Usa redirect permanenti (301)',
            'description' => 'Stai usando redirect temporanei che non trasferiscono il ranking SEO',
            'solution' => 'Cambia i redirect 302/307 in 301 per cambi permanenti'
        );
    }
    
    // Implementa HTTPS
    if (isset($results['url_variants']['analysis']) && 
        !$results['url_variants']['analysis']['forces_https']) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Implementa redirect HTTPS',
            'description' => 'Il sito non forza l\'uso di HTTPS',
            'solution' => 'Configura redirect da HTTP a HTTPS per tutte le pagine'
        );
    }
    
    // Canonical mancante
    if (isset($results['canonical_analysis']) && 
        !$results['canonical_analysis']['has_canonical']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Aggiungi tag canonical',
            'description' => 'La pagina finale non ha un tag canonical',
            'solution' => 'Aggiungi <link rel="canonical" href="URL"> nell\'head della pagina'
        );
    }
    
    // Unifica varianti URL
    if (isset($results['url_variants']['analysis']['all_redirect_to_same']) && 
        !$results['url_variants']['analysis']['all_redirect_to_same']) {
        $recommendations[] = array(
            'priority' => 'critical',
            'title' => 'Unifica le varianti URL',
            'description' => 'Le diverse versioni del tuo URL non redirigono tutte allo stesso posto',
            'solution' => 'Configura tutti i redirect per puntare a una singola versione canonica'
        );
    }
    
    // Performance
    if ($results['total_time'] > 1500) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Ottimizza la velocità dei redirect',
            'description' => 'I redirect impiegano troppo tempo (' . round($results['total_time'] / 1000, 1) . 's)',
            'solution' => 'Riduci il numero di redirect e ottimizza la configurazione del server'
        );
    }
    
    return $recommendations;
}

/**
 * Analizza redirect per un intero sito
 * 
 * @param string $domain Dominio base
 * @param array $urls Lista di URL da testare
 * @return array Risultati analisi sito
 */
function analyzeSiteRedirects($domain, $urls = array()) {
    // Se non sono forniti URL, usa quelli comuni
    if (empty($urls)) {
        $urls = array(
            '/',
            '/index.html',
            '/index.php',
            '/home',
            '/default.aspx'
        );
    }
    
    $results = array(
        'domain' => $domain,
        'total_urls_tested' => count($urls),
        'redirect_chains' => array(),
        'common_issues' => array(),
        'statistics' => array(
            'total_redirects' => 0,
            'permanent_redirects' => 0,
            'temporary_redirects' => 0,
            'avg_chain_length' => 0,
            'max_chain_length' => 0
        )
    );
    
    foreach ($urls as $path) {
        $test_url = "https://{$domain}{$path}";
        $chain_result = analyzeRedirectChain($test_url, array('check_variants' => false));
        
        $results['redirect_chains'][$path] = $chain_result;
        
        // Aggiorna statistiche
        $results['statistics']['total_redirects'] += $chain_result['redirect_count'];
        $results['statistics']['max_chain_length'] = max(
            $results['statistics']['max_chain_length'],
            $chain_result['redirect_count']
        );
        
        if (isset($chain_result['redirect_analysis'])) {
            $results['statistics']['permanent_redirects'] += $chain_result['redirect_analysis']['permanent_count'];
            $results['statistics']['temporary_redirects'] += $chain_result['redirect_analysis']['temporary_count'];
        }
    }
    
    // Calcola media
    $results['statistics']['avg_chain_length'] = count($urls) > 0 
        ? round($results['statistics']['total_redirects'] / count($urls), 1)
        : 0;
    
    return $results;
}
