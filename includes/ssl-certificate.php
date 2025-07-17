<?php
/**
 * Funzioni per l'analisi del certificato SSL/TLS
 * 
 * @package ControlDomini
 * @subpackage Security
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza il certificato SSL/TLS di un dominio
 * 
 * @param string $domain Dominio da analizzare
 * @return array Informazioni sul certificato
 */
function analyzeSSLCertificate($domain) {
    $results = array(
        'valid' => false,
        'error' => null,
        'certificate' => array(),
        'chain' => array(),
        'protocols' => array(),
        'ciphers' => array(),
        'vulnerabilities' => array(),
        'score' => 0,
        'grade' => 'F'
    );
    
    try {
        // Ottieni informazioni base del certificato
        $cert_info = getSSLCertificateInfo($domain);
        
        if ($cert_info === false) {
            $results['error'] = 'Impossibile connettersi al server SSL';
            return $results;
        }
        
        $results['certificate'] = $cert_info;
        $results['valid'] = $cert_info['valid'];
        
        // Controlla la catena di certificati
        $results['chain'] = getSSLChain($domain);
        
        // Analizza protocolli supportati
        $results['protocols'] = checkSSLProtocols($domain);
        
        // Analizza cipher suites
        $results['ciphers'] = checkCipherSuites($domain);
        
        // Controlla vulnerabilità note
        $results['vulnerabilities'] = checkSSLVulnerabilities($domain, $results);
        
        // Calcola score e grade
        $scoring = calculateSSLScore($results);
        $results['score'] = $scoring['score'];
        $results['grade'] = $scoring['grade'];
        $results['grade_details'] = $scoring['details'];
        
        // Genera raccomandazioni
        $results['recommendations'] = generateSSLRecommendations($results);
        
    } catch (Exception $e) {
        $results['error'] = 'Errore durante l\'analisi: ' . $e->getMessage();
    }
    
    return $results;
}

/**
 * Ottiene informazioni dettagliate sul certificato SSL
 * 
 * @param string $domain Dominio
 * @return array|false Informazioni certificato o false
 */
function getSSLCertificateInfo($domain) {
    $context = stream_context_create(array(
        "ssl" => array(
            "capture_peer_cert" => true,
            "capture_peer_cert_chain" => true,
            "verify_peer" => false,
            "verify_peer_name" => false,
            "allow_self_signed" => true,
            "SNI_enabled" => true,
            "SNI_server_name" => $domain
        )
    ));
    
    $stream = @stream_socket_client(
        "ssl://" . $domain . ":443", 
        $errno, 
        $errstr, 
        10, 
        STREAM_CLIENT_CONNECT, 
        $context
    );
    
    if (!$stream) {
        return false;
    }
    
    $params = stream_context_get_params($stream);
    fclose($stream);
    
    if (!isset($params['options']['ssl']['peer_certificate'])) {
        return false;
    }
    
    $cert = $params['options']['ssl']['peer_certificate'];
    $cert_info = openssl_x509_parse($cert);
    
    if (!$cert_info) {
        return false;
    }
    
    // Estrai informazioni principali
    $info = array(
        'subject' => array(
            'CN' => isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : '',
            'O' => isset($cert_info['subject']['O']) ? $cert_info['subject']['O'] : '',
            'C' => isset($cert_info['subject']['C']) ? $cert_info['subject']['C'] : '',
            'ST' => isset($cert_info['subject']['ST']) ? $cert_info['subject']['ST'] : '',
            'L' => isset($cert_info['subject']['L']) ? $cert_info['subject']['L'] : ''
        ),
        'issuer' => array(
            'CN' => isset($cert_info['issuer']['CN']) ? $cert_info['issuer']['CN'] : '',
            'O' => isset($cert_info['issuer']['O']) ? $cert_info['issuer']['O'] : '',
            'C' => isset($cert_info['issuer']['C']) ? $cert_info['issuer']['C'] : ''
        ),
        'valid_from' => date('Y-m-d H:i:s', $cert_info['validFrom_time_t']),
        'valid_to' => date('Y-m-d H:i:s', $cert_info['validTo_time_t']),
        'valid_from_timestamp' => $cert_info['validFrom_time_t'],
        'valid_to_timestamp' => $cert_info['validTo_time_t'],
        'serial_number' => $cert_info['serialNumber'],
        'signature_algorithm' => isset($cert_info['signatureTypeSN']) ? $cert_info['signatureTypeSN'] : '',
        'version' => isset($cert_info['version']) ? $cert_info['version'] + 1 : 0
    );
    
    // Calcola validità
    $now = time();
    $info['is_valid'] = ($now >= $info['valid_from_timestamp'] && $now <= $info['valid_to_timestamp']);
    $info['days_remaining'] = floor(($info['valid_to_timestamp'] - $now) / 86400);
    $info['is_expired'] = $info['days_remaining'] < 0;
    $info['is_expiring_soon'] = $info['days_remaining'] > 0 && $info['days_remaining'] <= 30;
    
    // Verifica validità per il dominio
    $info['valid_for_domain'] = checkCertificateValidForDomain($cert_info, $domain);
    $info['valid'] = $info['is_valid'] && $info['valid_for_domain'] && !$info['is_expired'];
    
    // Estrai SAN (Subject Alternative Names)
    $info['san'] = array();
    if (isset($cert_info['extensions']['subjectAltName'])) {
        $san_string = $cert_info['extensions']['subjectAltName'];
        preg_match_all('/DNS:([^,\s]+)/', $san_string, $matches);
        if (isset($matches[1])) {
            $info['san'] = $matches[1];
        }
    }
    
    // Informazioni sulla chiave
    $public_key = openssl_pkey_get_public($cert);
    if ($public_key) {
        $key_details = openssl_pkey_get_details($public_key);
        $info['key'] = array(
            'bits' => $key_details['bits'],
            'type' => $key_details['type'] == OPENSSL_KEYTYPE_RSA ? 'RSA' : 
                     ($key_details['type'] == OPENSSL_KEYTYPE_EC ? 'EC' : 'Unknown'),
            'secure' => $key_details['bits'] >= 2048
        );
    }
    
    // Fingerprint
    $info['fingerprint'] = array(
        'sha1' => openssl_x509_fingerprint($cert, 'sha1'),
        'sha256' => openssl_x509_fingerprint($cert, 'sha256')
    );
    
    // Tipo di certificato
    $info['type'] = detectCertificateType($info);
    
    // Transparency logs
    if (isset($cert_info['extensions']['1.3.6.1.4.1.11129.2.4.2'])) {
        $info['has_ct'] = true;
        $info['ct_info'] = 'Certificate Transparency abilitato';
    } else {
        $info['has_ct'] = false;
    }
    
    return $info;
}

/**
 * Verifica se il certificato è valido per il dominio
 * 
 * @param array $cert_info Informazioni certificato
 * @param string $domain Dominio da verificare
 * @return bool
 */
function checkCertificateValidForDomain($cert_info, $domain) {
    $valid_names = array();
    
    // Aggiungi CN
    if (isset($cert_info['subject']['CN'])) {
        $valid_names[] = $cert_info['subject']['CN'];
    }
    
    // Aggiungi SAN
    if (isset($cert_info['extensions']['subjectAltName'])) {
        preg_match_all('/DNS:([^,\s]+)/', $cert_info['extensions']['subjectAltName'], $matches);
        if (isset($matches[1])) {
            $valid_names = array_merge($valid_names, $matches[1]);
        }
    }
    
    // Controlla ogni nome valido
    foreach ($valid_names as $valid_name) {
        if ($valid_name === $domain) {
            return true;
        }
        
        // Supporto wildcard
        if (strpos($valid_name, '*.') === 0) {
            $wildcard_domain = substr($valid_name, 2);
            if (substr($domain, -strlen($wildcard_domain)) === $wildcard_domain) {
                $subdomain = substr($domain, 0, -strlen($wildcard_domain) - 1);
                if (strpos($subdomain, '.') === false) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Ottiene la catena di certificati
 * 
 * @param string $domain Dominio
 * @return array Catena di certificati
 */
function getSSLChain($domain) {
    $chain = array();
    
    $context = stream_context_create(array(
        "ssl" => array(
            "capture_peer_cert_chain" => true,
            "verify_peer" => false,
            "verify_peer_name" => false,
            "allow_self_signed" => true
        )
    ));
    
    $stream = @stream_socket_client(
        "ssl://" . $domain . ":443", 
        $errno, 
        $errstr, 
        10, 
        STREAM_CLIENT_CONNECT, 
        $context
    );
    
    if (!$stream) {
        return $chain;
    }
    
    $params = stream_context_get_params($stream);
    fclose($stream);
    
    if (isset($params['options']['ssl']['peer_certificate_chain'])) {
        foreach ($params['options']['ssl']['peer_certificate_chain'] as $cert) {
            $cert_info = openssl_x509_parse($cert);
            if ($cert_info) {
                $chain[] = array(
                    'subject' => isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : 'Unknown',
                    'issuer' => isset($cert_info['issuer']['CN']) ? $cert_info['issuer']['CN'] : 'Unknown',
                    'valid_from' => date('Y-m-d', $cert_info['validFrom_time_t']),
                    'valid_to' => date('Y-m-d', $cert_info['validTo_time_t']),
                    'is_ca' => isset($cert_info['extensions']['basicConstraints']) && 
                              strpos($cert_info['extensions']['basicConstraints'], 'CA:TRUE') !== false
                );
            }
        }
    }
    
    return $chain;
}

/**
 * Controlla i protocolli SSL/TLS supportati
 * 
 * @param string $domain Dominio
 * @return array Protocolli supportati
 */
function checkSSLProtocols($domain) {
    $protocols = array(
        'SSLv2' => array('supported' => false, 'secure' => false, 'deprecated' => true),
        'SSLv3' => array('supported' => false, 'secure' => false, 'deprecated' => true),
        'TLSv1.0' => array('supported' => false, 'secure' => false, 'deprecated' => true),
        'TLSv1.1' => array('supported' => false, 'secure' => false, 'deprecated' => true),
        'TLSv1.2' => array('supported' => false, 'secure' => true, 'deprecated' => false),
        'TLSv1.3' => array('supported' => false, 'secure' => true, 'deprecated' => false)
    );
    
    // Mappa versioni OpenSSL
    $protocol_versions = array(
        'SSLv2' => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
        'SSLv3' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
        'TLSv1.0' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
        'TLSv1.1' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
        'TLSv1.2' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
    );
    
    // TLS 1.3 potrebbe non essere disponibile in tutte le versioni PHP
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
        $protocol_versions['TLSv1.3'] = STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
    }
    
    foreach ($protocol_versions as $protocol => $method) {
        $context = stream_context_create();
        $socket = @stream_socket_client(
            "tcp://" . $domain . ":443", 
            $errno, 
            $errstr, 
            5, 
            STREAM_CLIENT_CONNECT, 
            $context
        );
        
        if ($socket) {
            $result = @stream_socket_enable_crypto($socket, true, $method);
            if ($result === true) {
                $protocols[$protocol]['supported'] = true;
            }
            fclose($socket);
        }
    }
    
    return $protocols;
}

/**
 * Controlla le cipher suites supportate
 * 
 * @param string $domain Dominio
 * @return array Cipher suites
 */
function checkCipherSuites($domain) {
    $ciphers = array();
    
    // Lista delle cipher suites comuni da testare
    $test_ciphers = array(
        // Sicure
        'ECDHE-RSA-AES256-GCM-SHA384' => array('strength' => 'strong', 'bits' => 256),
        'ECDHE-RSA-AES128-GCM-SHA256' => array('strength' => 'strong', 'bits' => 128),
        'ECDHE-ECDSA-AES256-GCM-SHA384' => array('strength' => 'strong', 'bits' => 256),
        'ECDHE-ECDSA-AES128-GCM-SHA256' => array('strength' => 'strong', 'bits' => 128),
        
        // Medie
        'AES256-GCM-SHA384' => array('strength' => 'medium', 'bits' => 256),
        'AES128-GCM-SHA256' => array('strength' => 'medium', 'bits' => 128),
        'AES256-SHA256' => array('strength' => 'medium', 'bits' => 256),
        'AES128-SHA256' => array('strength' => 'medium', 'bits' => 128),
        
        // Deboli
        'DES-CBC3-SHA' => array('strength' => 'weak', 'bits' => 112),
        'RC4-SHA' => array('strength' => 'weak', 'bits' => 128),
        'RC4-MD5' => array('strength' => 'weak', 'bits' => 128)
    );
    
    foreach ($test_ciphers as $cipher => $info) {
        $context = stream_context_create(array(
            "ssl" => array(
                "ciphers" => $cipher,
                "verify_peer" => false,
                "verify_peer_name" => false,
                "capture_peer_cert" => true
            )
        ));
        
        $stream = @stream_socket_client(
            "ssl://" . $domain . ":443", 
            $errno, 
            $errstr, 
            5, 
            STREAM_CLIENT_CONNECT, 
            $context
        );
        
        if ($stream) {
            $ciphers[] = array(
                'name' => $cipher,
                'strength' => $info['strength'],
                'bits' => $info['bits'],
                'secure' => $info['strength'] !== 'weak'
            );
            fclose($stream);
        }
    }
    
    return $ciphers;
}

/**
 * Controlla vulnerabilità SSL/TLS note
 * 
 * @param string $domain Dominio
 * @param array $ssl_info Informazioni SSL già raccolte
 * @return array Vulnerabilità trovate
 */
function checkSSLVulnerabilities($domain, $ssl_info) {
    $vulnerabilities = array();
    
    // Heartbleed (CVE-2014-0160)
    // Difficile da testare senza tool specifici, quindi basato su versione
    
    // POODLE (SSLv3)
    if (isset($ssl_info['protocols']['SSLv3']['supported']) && 
        $ssl_info['protocols']['SSLv3']['supported']) {
        $vulnerabilities[] = array(
            'name' => 'POODLE',
            'severity' => 'high',
            'description' => 'SSLv3 è vulnerabile all\'attacco POODLE',
            'cve' => 'CVE-2014-3566',
            'solution' => 'Disabilita SSLv3'
        );
    }
    
    // BEAST (TLS 1.0)
    if (isset($ssl_info['protocols']['TLSv1.0']['supported']) && 
        $ssl_info['protocols']['TLSv1.0']['supported']) {
        $vulnerabilities[] = array(
            'name' => 'BEAST',
            'severity' => 'medium',
            'description' => 'TLS 1.0 è potenzialmente vulnerabile all\'attacco BEAST',
            'cve' => 'CVE-2011-3389',
            'solution' => 'Preferisci TLS 1.2 o superiore'
        );
    }
    
    // Weak ciphers
    $weak_ciphers = array();
    foreach ($ssl_info['ciphers'] as $cipher) {
        if ($cipher['strength'] === 'weak') {
            $weak_ciphers[] = $cipher['name'];
        }
    }
    
    if (!empty($weak_ciphers)) {
        $vulnerabilities[] = array(
            'name' => 'Weak Ciphers',
            'severity' => 'medium',
            'description' => 'Supporta cipher suite deboli: ' . implode(', ', $weak_ciphers),
            'solution' => 'Disabilita le cipher suite deboli'
        );
    }
    
    // Certificate issues
    if ($ssl_info['certificate']['days_remaining'] < 0) {
        $vulnerabilities[] = array(
            'name' => 'Expired Certificate',
            'severity' => 'critical',
            'description' => 'Il certificato è scaduto',
            'solution' => 'Rinnova immediatamente il certificato'
        );
    } elseif ($ssl_info['certificate']['days_remaining'] < 30) {
        $vulnerabilities[] = array(
            'name' => 'Expiring Certificate',
            'severity' => 'high',
            'description' => 'Il certificato scade tra ' . $ssl_info['certificate']['days_remaining'] . ' giorni',
            'solution' => 'Pianifica il rinnovo del certificato'
        );
    }
    
    // Weak key
    if (isset($ssl_info['certificate']['key']['bits']) && 
        $ssl_info['certificate']['key']['bits'] < 2048) {
        $vulnerabilities[] = array(
            'name' => 'Weak Key',
            'severity' => 'high',
            'description' => 'Chiave RSA debole (' . $ssl_info['certificate']['key']['bits'] . ' bit)',
            'solution' => 'Usa chiavi RSA di almeno 2048 bit'
        );
    }
    
    // Self-signed
    if ($ssl_info['certificate']['issuer']['CN'] === $ssl_info['certificate']['subject']['CN']) {
        $vulnerabilities[] = array(
            'name' => 'Self-Signed Certificate',
            'severity' => 'medium',
            'description' => 'Il certificato è auto-firmato',
            'solution' => 'Usa un certificato firmato da una CA riconosciuta'
        );
    }
    
    return $vulnerabilities;
}

/**
 * Calcola score e grade SSL
 * 
 * @param array $results Risultati analisi
 * @return array Score e grade
 */
function calculateSSLScore($results) {
    $score = 100;
    $details = array();
    
    // Certificato valido (-40 se non valido)
    if (!$results['valid']) {
        $score -= 40;
        $details[] = '-40: Certificato non valido';
    }
    
    // Protocolli insicuri
    if ($results['protocols']['SSLv2']['supported']) {
        $score -= 20;
        $details[] = '-20: SSLv2 supportato';
    }
    if ($results['protocols']['SSLv3']['supported']) {
        $score -= 20;
        $details[] = '-20: SSLv3 supportato';
    }
    if ($results['protocols']['TLSv1.0']['supported']) {
        $score -= 10;
        $details[] = '-10: TLS 1.0 supportato';
    }
    if ($results['protocols']['TLSv1.1']['supported']) {
        $score -= 10;
        $details[] = '-10: TLS 1.1 supportato';
    }
    
    // Bonus per TLS moderni
    if ($results['protocols']['TLSv1.3']['supported']) {
        $score += 5;
        $details[] = '+5: TLS 1.3 supportato';
    }
    
    // Cipher deboli
    $weak_count = 0;
    foreach ($results['ciphers'] as $cipher) {
        if ($cipher['strength'] === 'weak') {
            $weak_count++;
        }
    }
    if ($weak_count > 0) {
        $penalty = min(20, $weak_count * 5);
        $score -= $penalty;
        $details[] = "-$penalty: $weak_count cipher deboli";
    }
    
    // Vulnerabilità
    foreach ($results['vulnerabilities'] as $vuln) {
        switch ($vuln['severity']) {
            case 'critical':
                $score -= 30;
                $details[] = '-30: ' . $vuln['name'];
                break;
            case 'high':
                $score -= 20;
                $details[] = '-20: ' . $vuln['name'];
                break;
            case 'medium':
                $score -= 10;
                $details[] = '-10: ' . $vuln['name'];
                break;
        }
    }
    
    // Normalizza score
    $score = max(0, min(100, $score));
    
    // Determina grade
    if ($score >= 95) {
        $grade = 'A+';
    } elseif ($score >= 85) {
        $grade = 'A';
    } elseif ($score >= 75) {
        $grade = 'B';
    } elseif ($score >= 65) {
        $grade = 'C';
    } elseif ($score >= 50) {
        $grade = 'D';
    } elseif ($score >= 35) {
        $grade = 'E';
    } else {
        $grade = 'F';
    }
    
    return array(
        'score' => $score,
        'grade' => $grade,
        'details' => $details
    );
}

/**
 * Genera raccomandazioni SSL
 * 
 * @param array $results Risultati analisi
 * @return array Raccomandazioni
 */
function generateSSLRecommendations($results) {
    $recommendations = array();
    
    // Certificato
    if (!$results['valid']) {
        $recommendations[] = array(
            'priority' => 'critical',
            'category' => 'certificate',
            'title' => 'Certificato non valido',
            'description' => 'Il certificato presenta problemi di validità',
            'solution' => 'Verifica e correggi i problemi del certificato'
        );
    }
    
    if ($results['certificate']['days_remaining'] < 30 && $results['certificate']['days_remaining'] > 0) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'certificate',
            'title' => 'Certificato in scadenza',
            'description' => 'Il certificato scade tra ' . $results['certificate']['days_remaining'] . ' giorni',
            'solution' => 'Rinnova il certificato prima della scadenza'
        );
    }
    
    // Protocolli
    $insecure_protocols = array();
    foreach (array('SSLv2', 'SSLv3', 'TLSv1.0', 'TLSv1.1') as $protocol) {
        if ($results['protocols'][$protocol]['supported']) {
            $insecure_protocols[] = $protocol;
        }
    }
    
    if (!empty($insecure_protocols)) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'protocol',
            'title' => 'Protocolli insicuri attivi',
            'description' => 'Supporta protocolli obsoleti: ' . implode(', ', $insecure_protocols),
            'solution' => 'Disabilita i protocolli obsoleti e mantieni solo TLS 1.2 e 1.3'
        );
    }
    
    if (!$results['protocols']['TLSv1.3']['supported']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'protocol',
            'title' => 'TLS 1.3 non supportato',
            'description' => 'Il server non supporta TLS 1.3',
            'solution' => 'Abilita TLS 1.3 per migliori performance e sicurezza'
        );
    }
    
    // Cipher suites
    $weak_ciphers = array();
    foreach ($results['ciphers'] as $cipher) {
        if ($cipher['strength'] === 'weak') {
            $weak_ciphers[] = $cipher['name'];
        }
    }
    
    if (!empty($weak_ciphers)) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'cipher',
            'title' => 'Cipher suite deboli',
            'description' => 'Supporta cipher suite insicure',
            'solution' => 'Disabilita: ' . implode(', ', $weak_ciphers)
        );
    }
    
    // Certificate Transparency
    if (!$results['certificate']['has_ct']) {
        $recommendations[] = array(
            'priority' => 'low',
            'category' => 'certificate',
            'title' => 'Certificate Transparency non attivo',
            'description' => 'Il certificato non include SCT (Signed Certificate Timestamps)',
            'solution' => 'Richiedi un certificato con supporto Certificate Transparency'
        );
    }
    
    return $recommendations;
}

/**
 * Rileva il tipo di certificato
 * 
 * @param array $cert_info Informazioni certificato
 * @return string Tipo di certificato
 */
function detectCertificateType($cert_info) {
    $san_count = count($cert_info['san']);
    
    // Wildcard
    foreach ($cert_info['san'] as $san) {
        if (strpos($san, '*.') === 0) {
            return 'Wildcard';
        }
    }
    
    // Multi-domain
    if ($san_count > 1) {
        return 'Multi-Domain (SAN)';
    }
    
    // EV indicators
    if (isset($cert_info['subject']['O']) && !empty($cert_info['subject']['O']) &&
        isset($cert_info['subject']['businessCategory'])) {
        return 'Extended Validation (EV)';
    }
    
    // OV indicators
    if (isset($cert_info['subject']['O']) && !empty($cert_info['subject']['O'])) {
        return 'Organization Validation (OV)';
    }
    
    return 'Domain Validation (DV)';
}
