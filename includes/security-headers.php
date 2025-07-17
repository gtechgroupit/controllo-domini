<?php
/**
 * Funzioni per il controllo degli header di sicurezza HTTP
 * 
 * @package ControlDomini
 * @subpackage Security
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza gli header di sicurezza HTTP di un dominio
 * 
 * @param string $domain Dominio da analizzare
 * @return array Risultati dell'analisi
 */
function analyzeSecurityHeaders($domain) {
    $results = array(
        'headers' => array(),
        'score' => 0,
        'max_score' => 0,
        'missing' => array(),
        'warnings' => array(),
        'passed' => array(),
        'raw_headers' => array(),
        'recommendations' => array()
    );
    
    // URL da testare (sia HTTP che HTTPS)
    $urls = array(
        'https://' . $domain,
        'https://www.' . $domain
    );
    
    foreach ($urls as $url) {
        $headers = getHttpHeaders($url);
        if (!empty($headers)) {
            $results['raw_headers'] = $headers;
            break;
        }
    }
    
    if (empty($results['raw_headers'])) {
        $results['error'] = 'Impossibile recuperare gli header HTTP';
        return $results;
    }
    
    // Lista degli header di sicurezza da controllare
    $security_headers = array(
        'strict-transport-security' => array(
            'name' => 'Strict-Transport-Security (HSTS)',
            'weight' => 20,
            'description' => 'Forza l\'uso di HTTPS per proteggere dalle intercettazioni',
            'recommended' => 'max-age=31536000; includeSubDomains; preload'
        ),
        'content-security-policy' => array(
            'name' => 'Content-Security-Policy (CSP)',
            'weight' => 25,
            'description' => 'Previene attacchi XSS e injection di codice',
            'recommended' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\''
        ),
        'x-frame-options' => array(
            'name' => 'X-Frame-Options',
            'weight' => 15,
            'description' => 'Previene clickjacking impedendo l\'inclusione in iframe',
            'recommended' => 'DENY o SAMEORIGIN'
        ),
        'x-content-type-options' => array(
            'name' => 'X-Content-Type-Options',
            'weight' => 10,
            'description' => 'Previene MIME type sniffing',
            'recommended' => 'nosniff'
        ),
        'x-xss-protection' => array(
            'name' => 'X-XSS-Protection',
            'weight' => 10,
            'description' => 'Attiva la protezione XSS del browser (deprecato ma ancora utile)',
            'recommended' => '1; mode=block'
        ),
        'referrer-policy' => array(
            'name' => 'Referrer-Policy',
            'weight' => 10,
            'description' => 'Controlla le informazioni del referrer inviate',
            'recommended' => 'strict-origin-when-cross-origin'
        ),
        'permissions-policy' => array(
            'name' => 'Permissions-Policy',
            'weight' => 10,
            'description' => 'Controlla l\'accesso alle API del browser',
            'recommended' => 'geolocation=(), microphone=(), camera=()'
        )
    );
    
    // Analizza ogni header
    foreach ($security_headers as $header_key => $header_info) {
        $results['max_score'] += $header_info['weight'];
        $header_value = getHeaderValue($results['raw_headers'], $header_key);
        
        if ($header_value !== null) {
            // Header presente
            $analysis = analyzeHeaderValue($header_key, $header_value, $header_info);
            
            $results['headers'][$header_key] = array(
                'present' => true,
                'value' => $header_value,
                'name' => $header_info['name'],
                'description' => $header_info['description'],
                'status' => $analysis['status'],
                'message' => $analysis['message'],
                'score' => $analysis['score'],
                'max_score' => $header_info['weight']
            );
            
            $results['score'] += $analysis['score'];
            
            if ($analysis['status'] === 'pass') {
                $results['passed'][] = $header_info['name'];
            } elseif ($analysis['status'] === 'warning') {
                $results['warnings'][] = array(
                    'header' => $header_info['name'],
                    'message' => $analysis['message']
                );
            }
        } else {
            // Header mancante
            $results['headers'][$header_key] = array(
                'present' => false,
                'value' => null,
                'name' => $header_info['name'],
                'description' => $header_info['description'],
                'status' => 'missing',
                'message' => 'Header non configurato',
                'recommended' => $header_info['recommended'],
                'score' => 0,
                'max_score' => $header_info['weight']
            );
            
            $results['missing'][] = array(
                'header' => $header_info['name'],
                'description' => $header_info['description'],
                'recommended' => $header_info['recommended']
            );
        }
    }
    
    // Calcola percentuale
    $results['percentage'] = $results['max_score'] > 0 
        ? round(($results['score'] / $results['max_score']) * 100) 
        : 0;
    
    // Determina rating
    if ($results['percentage'] >= 90) {
        $results['rating'] = 'A+';
        $results['rating_color'] = '#00a65a';
        $results['rating_text'] = 'Eccellente';
    } elseif ($results['percentage'] >= 80) {
        $results['rating'] = 'A';
        $results['rating_color'] = '#00a65a';
        $results['rating_text'] = 'Ottimo';
    } elseif ($results['percentage'] >= 70) {
        $results['rating'] = 'B';
        $results['rating_color'] = '#39cccc';
        $results['rating_text'] = 'Buono';
    } elseif ($results['percentage'] >= 60) {
        $results['rating'] = 'C';
        $results['rating_color'] = '#f39c12';
        $results['rating_text'] = 'Sufficiente';
    } elseif ($results['percentage'] >= 40) {
        $results['rating'] = 'D';
        $results['rating_color'] = '#ff851b';
        $results['rating_text'] = 'Insufficiente';
    } else {
        $results['rating'] = 'F';
        $results['rating_color'] = '#dd4b39';
        $results['rating_text'] = 'Critico';
    }
    
    // Controlla altri header importanti
    $results['additional_headers'] = checkAdditionalHeaders($results['raw_headers']);
    
    // Genera raccomandazioni
    $results['recommendations'] = generateSecurityRecommendations($results);
    
    return $results;
}

/**
 * Recupera gli header HTTP di un URL
 * 
 * @param string $url URL da controllare
 * @return array Header HTTP
 */
function getHttpHeaders($url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ControlloDomin/1.0)'
    ));
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($response === false || $info['http_code'] == 0) {
        return array();
    }
    
    // Estrai gli header
    $header_size = $info['header_size'];
    $header_string = substr($response, 0, $header_size);
    
    // Converti in array
    $headers = array();
    $lines = explode("\r\n", $header_string);
    
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            
            // Gestisci header multipli
            if (isset($headers[$key])) {
                if (!is_array($headers[$key])) {
                    $headers[$key] = array($headers[$key]);
                }
                $headers[$key][] = $value;
            } else {
                $headers[$key] = $value;
            }
        } elseif (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $line, $matches)) {
            $headers['http_code'] = (int)$matches[1];
        }
    }
    
    return $headers;
}

/**
 * Recupera il valore di un header specifico
 * 
 * @param array $headers Array degli header
 * @param string $header_name Nome dell'header
 * @return string|null Valore dell'header o null
 */
function getHeaderValue($headers, $header_name) {
    $header_name = strtolower($header_name);
    
    if (isset($headers[$header_name])) {
        if (is_array($headers[$header_name])) {
            return implode('; ', $headers[$header_name]);
        }
        return $headers[$header_name];
    }
    
    return null;
}

/**
 * Analizza il valore di un header di sicurezza
 * 
 * @param string $header_key Chiave dell'header
 * @param string $header_value Valore dell'header
 * @param array $header_info Informazioni sull'header
 * @return array Risultato dell'analisi
 */
function analyzeHeaderValue($header_key, $header_value, $header_info) {
    $result = array(
        'status' => 'fail',
        'message' => '',
        'score' => 0
    );
    
    switch ($header_key) {
        case 'strict-transport-security':
            if (preg_match('/max-age=(\d+)/i', $header_value, $matches)) {
                $max_age = (int)$matches[1];
                
                if ($max_age >= 31536000) { // 1 anno
                    $result['score'] = $header_info['weight'];
                    $result['status'] = 'pass';
                    $result['message'] = 'HSTS configurato correttamente';
                    
                    if (stripos($header_value, 'includeSubDomains') !== false) {
                        $result['message'] .= ' (include sottodomini)';
                    }
                    if (stripos($header_value, 'preload') !== false) {
                        $result['message'] .= ' (preload attivo)';
                    }
                } elseif ($max_age >= 86400) { // 1 giorno
                    $result['score'] = $header_info['weight'] * 0.5;
                    $result['status'] = 'warning';
                    $result['message'] = 'max-age troppo basso (consigliato: 31536000)';
                } else {
                    $result['status'] = 'fail';
                    $result['message'] = 'max-age troppo basso';
                }
            }
            break;
            
        case 'content-security-policy':
            if (strlen($header_value) > 20) {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'CSP configurato';
                
                // Analisi base delle direttive
                if (stripos($header_value, 'default-src') !== false) {
                    $result['message'] .= ' (default-src presente)';
                }
                if (stripos($header_value, 'unsafe-inline') !== false) {
                    $result['score'] = $header_info['weight'] * 0.7;
                    $result['status'] = 'warning';
                    $result['message'] .= ' (attenzione: unsafe-inline)';
                }
            }
            break;
            
        case 'x-frame-options':
            $value_lower = strtolower($header_value);
            if ($value_lower === 'deny' || $value_lower === 'sameorigin') {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'Protezione clickjacking attiva (' . $header_value . ')';
            } else {
                $result['status'] = 'warning';
                $result['score'] = $header_info['weight'] * 0.5;
                $result['message'] = 'Valore non ottimale';
            }
            break;
            
        case 'x-content-type-options':
            if (strtolower($header_value) === 'nosniff') {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'MIME type sniffing disabilitato';
            }
            break;
            
        case 'x-xss-protection':
            if (preg_match('/1.*mode=block/i', $header_value)) {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'Protezione XSS attiva';
            } elseif ($header_value === '1') {
                $result['score'] = $header_info['weight'] * 0.7;
                $result['status'] = 'warning';
                $result['message'] = 'Protezione XSS attiva ma senza mode=block';
            }
            break;
            
        case 'referrer-policy':
            $valid_policies = array(
                'no-referrer', 'no-referrer-when-downgrade', 
                'origin', 'origin-when-cross-origin',
                'same-origin', 'strict-origin',
                'strict-origin-when-cross-origin'
            );
            
            if (in_array(strtolower($header_value), $valid_policies)) {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'Policy configurata: ' . $header_value;
            }
            break;
            
        case 'permissions-policy':
            if (strlen($header_value) > 10) {
                $result['score'] = $header_info['weight'];
                $result['status'] = 'pass';
                $result['message'] = 'Permissions Policy configurata';
            }
            break;
    }
    
    return $result;
}

/**
 * Controlla altri header importanti
 * 
 * @param array $headers Header HTTP
 * @return array Header aggiuntivi trovati
 */
function checkAdditionalHeaders($headers) {
    $additional = array();
    
    // Server header
    if (isset($headers['server'])) {
        // Gestisci il caso in cui server possa essere un array
        $server_value = is_array($headers['server']) ? $headers['server'][0] : $headers['server'];
        
        $additional['server'] = array(
            'value' => $server_value,
            'warning' => strpos($server_value, '/') !== false,
            'message' => strpos($server_value, '/') !== false 
                ? 'Espone versione del server' 
                : 'Non espone versione'
        );
    }
    
    // X-Powered-By
    if (isset($headers['x-powered-by'])) {
        // Gestisci il caso in cui x-powered-by possa essere un array
        $powered_by_value = is_array($headers['x-powered-by']) ? $headers['x-powered-by'][0] : $headers['x-powered-by'];
        
        $additional['x-powered-by'] = array(
            'value' => $powered_by_value,
            'warning' => true,
            'message' => 'Espone tecnologia utilizzata (consigliato rimuovere)'
        );
    }
    
    // Set-Cookie security
    if (isset($headers['set-cookie'])) {
        $cookies = is_array($headers['set-cookie']) ? $headers['set-cookie'] : array($headers['set-cookie']);
        $secure_cookies = 0;
        $httponly_cookies = 0;
        $samesite_cookies = 0;
        
        foreach ($cookies as $cookie) {
            // Assicurati che $cookie sia una stringa
            $cookie_str = is_array($cookie) ? implode('; ', $cookie) : $cookie;
            
            if (stripos($cookie_str, 'secure') !== false) $secure_cookies++;
            if (stripos($cookie_str, 'httponly') !== false) $httponly_cookies++;
            if (stripos($cookie_str, 'samesite') !== false) $samesite_cookies++;
        }
        
        $total_cookies = count($cookies);
        $additional['cookies'] = array(
            'total' => $total_cookies,
            'secure' => $secure_cookies,
            'httponly' => $httponly_cookies,
            'samesite' => $samesite_cookies,
            'warning' => ($secure_cookies < $total_cookies || $httponly_cookies < $total_cookies),
            'message' => "Cookies totali: $total_cookies (Secure: $secure_cookies, HttpOnly: $httponly_cookies, SameSite: $samesite_cookies)"
        );
    }
    
    // Cache-Control
    if (isset($headers['cache-control'])) {
        $cache_value = is_array($headers['cache-control']) ? implode(', ', $headers['cache-control']) : $headers['cache-control'];
        
        $additional['cache-control'] = array(
            'value' => $cache_value,
            'warning' => false,
            'message' => 'Cache control configurato'
        );
    }
    
    // Expires
    if (isset($headers['expires'])) {
        $expires_value = is_array($headers['expires']) ? $headers['expires'][0] : $headers['expires'];
        
        $additional['expires'] = array(
            'value' => $expires_value,
            'warning' => false,
            'message' => 'Header expires presente'
        );
    }
    
    return $additional;
}

/**
 * Genera raccomandazioni per migliorare la sicurezza
 * 
 * @param array $analysis Risultati dell'analisi
 * @return array Raccomandazioni
 */
function generateSecurityRecommendations($analysis) {
    $recommendations = array();
    
    // Header mancanti critici
    if (!empty($analysis['missing'])) {
        foreach ($analysis['missing'] as $missing) {
            if (in_array($missing['header'], array('Strict-Transport-Security (HSTS)', 'Content-Security-Policy (CSP)'))) {
                $recommendations[] = array(
                    'priority' => 'high',
                    'title' => 'Implementa ' . $missing['header'],
                    'description' => $missing['description'],
                    'solution' => 'Aggiungi: ' . $missing['recommended']
                );
            } else {
                $recommendations[] = array(
                    'priority' => 'medium',
                    'title' => 'Considera l\'aggiunta di ' . $missing['header'],
                    'description' => $missing['description'],
                    'solution' => 'Valore consigliato: ' . $missing['recommended']
                );
            }
        }
    }
    
    // Warning su header esistenti
    if (!empty($analysis['warnings'])) {
        foreach ($analysis['warnings'] as $warning) {
            $recommendations[] = array(
                'priority' => 'medium',
                'title' => 'Ottimizza ' . $warning['header'],
                'description' => $warning['message'],
                'solution' => 'Rivedi la configurazione attuale'
            );
        }
    }
    
    // Server info exposure
    if (isset($analysis['additional_headers']['server']) && $analysis['additional_headers']['server']['warning']) {
        $recommendations[] = array(
            'priority' => 'low',
            'title' => 'Nascondi versione del server',
            'description' => 'Il server espone informazioni sulla versione',
            'solution' => 'Configura il server per nascondere la versione'
        );
    }
    
    // X-Powered-By
    if (isset($analysis['additional_headers']['x-powered-by'])) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Rimuovi header X-Powered-By',
            'description' => 'L\'header X-Powered-By espone la tecnologia utilizzata',
            'solution' => 'Rimuovi questo header nella configurazione del server'
        );
    }
    
    // Cookie security
    if (isset($analysis['additional_headers']['cookies']) && $analysis['additional_headers']['cookies']['warning']) {
        $cookies_info = $analysis['additional_headers']['cookies'];
        if ($cookies_info['secure'] < $cookies_info['total']) {
            $recommendations[] = array(
                'priority' => 'high',
                'title' => 'Aggiungi flag Secure ai cookie',
                'description' => 'Non tutti i cookie hanno il flag Secure',
                'solution' => 'Imposta il flag Secure su tutti i cookie'
            );
        }
        if ($cookies_info['httponly'] < $cookies_info['total']) {
            $recommendations[] = array(
                'priority' => 'medium',
                'title' => 'Aggiungi flag HttpOnly ai cookie',
                'description' => 'Non tutti i cookie hanno il flag HttpOnly',
                'solution' => 'Imposta il flag HttpOnly sui cookie che non devono essere accessibili via JavaScript'
            );
        }
        if ($cookies_info['samesite'] < $cookies_info['total']) {
            $recommendations[] = array(
                'priority' => 'medium',
                'title' => 'Aggiungi attributo SameSite ai cookie',
                'description' => 'Non tutti i cookie hanno l\'attributo SameSite',
                'solution' => 'Imposta SameSite=Lax o SameSite=Strict sui cookie'
            );
        }
    }
    
    return $recommendations;
}

/**
 * Alias per compatibilità
 * 
 * @param string $domain Dominio da analizzare
 * @return array Risultati dell'analisi
 */
function checkSecurityHeaders($domain) {
    return analyzeSecurityHeaders($domain);
}
?>
