<?php
/**
 * Funzioni per l'analisi WHOIS - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage WHOIS
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Ottiene informazioni WHOIS complete per un dominio
 * 
 * @param string $domain Dominio da verificare
 * @param bool $debug Modalità debug
 * @return array Informazioni WHOIS strutturate
 */
function getWhoisInfo($domain, $debug = false) {
    $GLOBALS['debug_mode'] = $debug;
    
    // Struttura dati di default
    $info = array(
        'registrar' => 'Non disponibile',
        'created' => 'Non disponibile',
        'expires' => 'Non disponibile',
        'updated' => 'Non disponibile',
        'status' => 'Active',
        'owner' => 'Non disponibile',
        'registrant_country' => 'Non disponibile',
        'registrant_org' => 'Non disponibile',
        'registrant_email' => 'Privacy Protected',
        'registrant_phone' => 'Privacy Protected',
        'nameservers' => array(),
        'dnssec' => false,
        'raw_data' => '',
        'source' => 'unknown',
        'query_time' => microtime(true)
    );
    
    // Tenta diversi metodi di recupero WHOIS
    $whois_data = '';
    
    // Metodo 1: Connessione diretta ai server WHOIS
    $whois_data = getWhoisViaSocket($domain);
    if ($whois_data) {
        $info['source'] = 'socket';
    }
    
    // Metodo 2: Shell exec (se disponibile)
    if (!$whois_data && isShellExecAvailable()) {
        $whois_data = @shell_exec("whois " . escapeshellarg($domain) . " 2>&1");
        if ($whois_data) {
            $info['source'] = 'shell';
        }
    }
    
    // Metodo 3: Server WHOIS alternativo
    if (!$whois_data) {
        $whois_data = getWhoisFromInternic($domain);
        if ($whois_data) {
            $info['source'] = 'internic';
        }
    }
    
    // Metodo 4: API/Web fallback
    if (!$whois_data) {
        $whois_data = getWhoisViaCurl($domain);
        if ($whois_data) {
            $info['source'] = 'web';
        }
    }
    
    // Se abbiamo dati, parsali
    if ($whois_data) {
        $info['raw_data'] = $whois_data;
        $info = parseWhoisData($whois_data, $info, $domain);
    }
    
    // Se non abbiamo nameserver dal WHOIS, prova dai DNS
    if (empty($info['nameservers'])) {
        $ns_records = @dns_get_record($domain, DNS_NS);
        if ($ns_records) {
            foreach ($ns_records as $ns) {
                if (isset($ns['target'])) {
                    $info['nameservers'][] = strtolower($ns['target']);
                }
            }
        }
    }
    
    // Verifica se il dominio esiste almeno nei DNS
    if ($info['owner'] == 'Non disponibile' && $info['registrar'] == 'Non disponibile') {
        $has_dns = @dns_get_record($domain, DNS_ANY);
        if ($has_dns) {
            $info['owner'] = 'Informazioni protette (GDPR)';
            $info['status'] = 'Active';
        }
    }
    
    // Calcola tempo query
    $info['query_time'] = round((microtime(true) - $info['query_time']) * 1000, 2);
    
    // Debug info
    if ($debug && $whois_data) {
        $info['_debug'] = array(
            'raw_preview' => substr($whois_data, 0, 500) . '...',
            'source' => $info['source'],
            'query_time_ms' => $info['query_time']
        );
    }
    
    return $info;
}

/**
 * Verifica se shell_exec è disponibile
 * 
 * @return bool
 */
function isShellExecAvailable() {
    if (!function_exists('shell_exec')) {
        return false;
    }
    
    $disabled = explode(',', ini_get('disable_functions'));
    return !in_array('shell_exec', array_map('trim', $disabled));
}

/**
 * Ottiene WHOIS via socket diretta
 * 
 * @param string $domain Dominio
 * @return string|false Dati WHOIS o false
 */
function getWhoisViaSocket($domain) {
    $tld = extractTLD($domain);
    $whois_server = getWhoisServer($tld);
    
    if (!$whois_server) {
        return false;
    }
    
    $timeout = 10;
    $max_attempts = 2;
    $attempt = 0;
    $response = '';
    
    while ($attempt < $max_attempts && empty($response)) {
        $attempt++;
        $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
        
        if ($fp) {
            stream_set_timeout($fp, $timeout);
            
            // Alcuni server richiedono formati specifici
            $query = formatWhoisQuery($domain, $whois_server);
            fputs($fp, $query . "\r\n");
            
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
                $info = stream_get_meta_data($fp);
                if ($info['timed_out']) {
                    break;
                }
            }
            fclose($fp);
            
            // Per alcuni TLD dobbiamo fare una seconda query
            $response = handleRegistrarRedirect($response, $domain, $tld);
        }
    }
    
    return $response ?: false;
}

/**
 * Ottiene il server WHOIS per un TLD
 * 
 * @param string $tld Top Level Domain
 * @return string|null Server WHOIS
 */
function getWhoisServer($tld) {
    // Usa la configurazione globale se disponibile
    if (isset($GLOBALS['whois_servers'][$tld])) {
        return $GLOBALS['whois_servers'][$tld];
    }
    
    // Server di default per TLD comuni
    $default_servers = array(
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'it' => 'whois.nic.it',
        'eu' => 'whois.eu'
    );
    
    return isset($default_servers[$tld]) ? $default_servers[$tld] : 'whois.iana.org';
}

/**
 * Formatta la query WHOIS per server specifici
 * 
 * @param string $domain Dominio
 * @param string $server Server WHOIS
 * @return string Query formattata
 */
function formatWhoisQuery($domain, $server) {
    // Server che richiedono formati speciali
    $special_formats = array(
        'whois.denic.de' => "-T dn {$domain}",
        'whois.jprs.jp' => "{$domain}/e",
        'whois.nic.it' => "{$domain}"
    );
    
    return isset($special_formats[$server]) ? $special_formats[$server] : $domain;
}

/**
 * Gestisce redirect a registrar WHOIS
 * 
 * @param string $response Risposta iniziale
 * @param string $domain Dominio
 * @param string $tld TLD
 * @return string Risposta completa
 */
function handleRegistrarRedirect($response, $domain, $tld) {
    // Solo per alcuni TLD
    if (!in_array($tld, array('com', 'net', 'tv', 'cc'))) {
        return $response;
    }
    
    // Cerca il server del registrar
    if (preg_match('/Registrar WHOIS Server:\s*(.+)/i', $response, $matches)) {
        $registrar_server = trim($matches[1]);
        
        if ($registrar_server && strpos($registrar_server, '.') !== false) {
            $fp2 = @fsockopen($registrar_server, 43, $errno, $errstr, 10);
            if ($fp2) {
                stream_set_timeout($fp2, 10);
                fputs($fp2, $domain . "\r\n");
                
                $response2 = '';
                while (!feof($fp2)) {
                    $response2 .= fgets($fp2, 128);
                    $info = stream_get_meta_data($fp2);
                    if ($info['timed_out']) {
                        break;
                    }
                }
                fclose($fp2);
                
                if ($response2) {
                    $response .= "\n\n--- Registrar WHOIS ---\n" . $response2;
                }
            }
        }
    }
    
    return $response;
}

/**
 * Query WHOIS via Internic
 * 
 * @param string $domain Dominio
 * @return string|false Dati WHOIS
 */
function getWhoisFromInternic($domain) {
    $fp = @fsockopen('whois.internic.net', 43, $errno, $errstr, 10);
    if (!$fp) return false;
    
    stream_set_timeout($fp, 10);
    fputs($fp, "domain " . $domain . "\r\n");
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 128);
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) {
            break;
        }
    }
    fclose($fp);
    
    return $response ?: false;
}

/**
 * Ottiene WHOIS via cURL da servizi web
 * 
 * @param string $domain Dominio
 * @return string|false Dati WHOIS
 */
function getWhoisViaCurl($domain) {
    if (!function_exists('curl_init')) {
        return false;
    }
    
    // Lista di servizi WHOIS web gratuiti
    $services = array(
        'https://who.is/whois/' . urlencode($domain),
        'https://www.whois.com/whois/' . urlencode($domain)
    );
    
    foreach ($services as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ControlDomini/1.0; +https://controllodomini.it)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ));
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($html && $http_code == 200) {
            // Estrai dati WHOIS dall'HTML
            if (preg_match('/<pre[^>]*class="df-raw"[^>]*>(.+?)<\/pre>/si', $html, $matches) ||
                preg_match('/<pre[^>]*>(.+?)<\/pre>/si', $html, $matches)) {
                return html_entity_decode(strip_tags($matches[1]));
            }
        }
    }
    
    return false;
}

/**
 * Parser principale per dati WHOIS
 * 
 * @param string $whois_data Dati WHOIS grezzi
 * @param array $info Array info da popolare
 * @param string $domain Dominio analizzato
 * @return array Info aggiornate
 */
function parseWhoisData($whois_data, $info, $domain) {
    // Pattern universali per diversi formati WHOIS
    $patterns = getWhoisPatterns();
    
    // Applica tutti i pattern
    foreach ($patterns as $key => $pattern_list) {
        foreach ($pattern_list as $pattern) {
            if (preg_match($pattern, $whois_data, $matches)) {
                $value = trim($matches[1]);
                
                // Processa il valore
                $value = processWhoisValue($key, $value);
                
                if ($value && !isPrivacyProtected($value)) {
                    $info[$key] = $value;
                    break;
                }
            }
        }
    }
    
    // Parsing specifico per TLD
    $tld = extractTLD($domain);
    $info = parseTldSpecific($whois_data, $info, $tld);
    
    // Estrai nameservers
    $info['nameservers'] = extractNameservers($whois_data);
    
    // Rileva DNSSEC
    $info['dnssec'] = detectDnssec($whois_data);
    
    // Gestione privacy/GDPR
    $info = handlePrivacyProtection($whois_data, $info);
    
    return $info;
}

/**
 * Ottiene i pattern WHOIS universali
 * 
 * @return array Pattern regex
 */
function getWhoisPatterns() {
    return array(
        'registrar' => array(
            '/Registrar:\s*(.+)/i',
            '/Registrar Name:\s*(.+)/i',
            '/Sponsoring Registrar:\s*(.+)/i',
            '/Registrar Organization:\s*(.+)/i',
            '/Registrar:\s*(.+)$/mi'
        ),
        'created' => array(
            '/Creation Date:\s*(.+)/i',
            '/Created:\s*(.+)/i',
            '/Created On:\s*(.+)/i',
            '/Domain Registration Date:\s*(.+)/i',
            '/Registered on:\s*(.+)/i',
            '/created:\s*(.+)/i',
            '/Registration Time:\s*(.+)/i',
            '/Registered:\s*(.+)/i'
        ),
        'expires' => array(
            '/Expiry Date:\s*(.+)/i',
            '/Expires:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/Registry Expiry Date:\s*(.+)/i',
            '/Registrar Registration Expiration Date:\s*(.+)/i',
            '/Expires On:\s*(.+)/i',
            '/Expiration Time:\s*(.+)/i',
            '/expire:\s*(.+)/i',
            '/paid-till:\s*(.+)/i'
        ),
        'updated' => array(
            '/Updated Date:\s*(.+)/i',
            '/Last Updated:\s*(.+)/i',
            '/Modified:\s*(.+)/i',
            '/Last Modified:\s*(.+)/i',
            '/Updated:\s*(.+)/i',
            '/last-update:\s*(.+)/i'
        ),
        'owner' => array(
            '/Registrant Name:\s*(.+)/i',
            '/Registrant:\s*(.+)/i',
            '/Registrant Contact Name:\s*(.+)/i',
            '/Owner Name:\s*(.+)/i',
            '/Organization:\s*(.+)/i',
            '/Registrant Organization:\s*(.+)/i',
            '/Holder:\s*(.+)/i'
        ),
        'registrant_org' => array(
            '/Registrant Organization:\s*(.+)/i',
            '/Registrant Organisation:\s*(.+)/i',
            '/Organization:\s*(.+)/i',
            '/Registrant Company:\s*(.+)/i',
            '/Company Name:\s*(.+)/i',
            '/org:\s*(.+)/i'
        ),
        'registrant_country' => array(
            '/Registrant Country:\s*(.+)/i',
            '/Country:\s*(.+)/i',
            '/Registrant Country\/Economy:\s*(.+)/i',
            '/Registrant Country Code:\s*(.+)/i',
            '/country:\s*(.+)/i'
        ),
        'registrant_email' => array(
            '/Registrant Email:\s*(.+)/i',
            '/Registrant E-mail:\s*(.+)/i',
            '/Email:\s*(.+)/i',
            '/e-mail:\s*(.+)/i'
        ),
        'registrant_phone' => array(
            '/Registrant Phone:\s*(.+)/i',
            '/Phone:\s*(.+)/i',
            '/Registrant Phone Number:\s*(.+)/i',
            '/phone:\s*(.+)/i'
        ),
        'status' => array(
            '/Domain Status:\s*(.+)/i',
            '/Status:\s*(.+)/i',
            '/domain status:\s*(.+)/i',
            '/EPP Status:\s*(.+)/i',
            '/state:\s*(.+)/i'
        )
    );
}

/**
 * Processa il valore WHOIS estratto
 * 
 * @param string $key Chiave del campo
 * @param string $value Valore estratto
 * @return string Valore processato
 */
function processWhoisValue($key, $value) {
    // Formatta le date
    if (in_array($key, array('created', 'expires', 'updated'))) {
        return formatWhoisDate($value);
    }
    
    // Pulisci lo status
    if ($key == 'status') {
        return formatWhoisStatus($value);
    }
    
    // Normalizza paese
    if ($key == 'registrant_country') {
        return normalizeCountryCode($value);
    }
    
    // Email - verifica validità base
    if ($key == 'registrant_email') {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : 'Non valida';
    }
    
    return $value;
}

/**
 * Formatta una data WHOIS
 * 
 * @param string $date Data grezza
 * @return string Data formattata
 */
function formatWhoisDate($date) {
    if (!$date || $date == 'Not Available') {
        return 'Non disponibile';
    }
    
    // Prova diversi formati
    $timestamp = strtotime($date);
    
    if (!$timestamp) {
        // Prova formato italiano
        $date = str_replace('/', '-', $date);
        $timestamp = strtotime($date);
    }
    
    if (!$timestamp && preg_match('/(\d{4})[.\-\/](\d{2})[.\-\/](\d{2})/', $date, $parts)) {
        // Ricostruisci la data
        $timestamp = strtotime($parts[1] . '-' . $parts[2] . '-' . $parts[3]);
    }
    
    if ($timestamp && $timestamp > 0) {
        return date('d/m/Y', $timestamp);
    }
    
    return $date;
}

/**
 * Formatta lo status del dominio
 * 
 * @param string $status Status grezzo
 * @return string Status formattato
 */
function formatWhoisStatus($status) {
    $status = strtolower($status);
    
    // Mappa stati comuni
    $status_map = array(
        'ok' => 'Active',
        'active' => 'Active',
        'registered' => 'Active',
        'connect' => 'Active',
        'pendingdelete' => 'Pending Delete',
        'redemptionperiod' => 'Redemption Period',
        'inactive' => 'Inactive',
        'hold' => 'On Hold',
        'locked' => 'Locked',
        'suspended' => 'Suspended'
    );
    
    foreach ($status_map as $pattern => $formatted) {
        if (strpos($status, $pattern) !== false) {
            return $formatted;
        }
    }
    
    return ucfirst(explode(' ', $status)[0]);
}

/**
 * Normalizza codice paese
 * 
 * @param string $country Paese o codice
 * @return string Codice paese normalizzato
 */
function normalizeCountryCode($country) {
    $country = strtoupper(trim($country));
    
    // Se già codice ISO
    if (strlen($country) == 2) {
        return $country;
    }
    
    // Mappa paesi comuni
    $country_map = array(
        'ITALY' => 'IT',
        'ITALIA' => 'IT',
        'UNITED STATES' => 'US',
        'UNITED KINGDOM' => 'GB',
        'GERMANY' => 'DE',
        'FRANCE' => 'FR',
        'SPAIN' => 'ES',
        'NETHERLANDS' => 'NL',
        'BELGIUM' => 'BE',
        'SWITZERLAND' => 'CH',
        'AUSTRIA' => 'AT'
    );
    
    return isset($country_map[$country]) ? $country_map[$country] : $country;
}

/**
 * Verifica se un valore è protetto da privacy
 * 
 * @param string $value Valore da verificare
 * @return bool True se protetto
 */
function isPrivacyProtected($value) {
    $privacy_indicators = array(
        'not available',
        'redacted',
        'privacy',
        'protected',
        'data protected',
        'gdpr',
        'masked',
        'hidden',
        'withheld',
        'confidential'
    );
    
    $value_lower = strtolower($value);
    foreach ($privacy_indicators as $indicator) {
        if (strpos($value_lower, $indicator) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Parsing specifico per TLD
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @param string $tld TLD
 * @return array Info aggiornate
 */
function parseTldSpecific($whois_data, $info, $tld) {
    switch ($tld) {
        case 'it':
            $info = parseWhoisIT($whois_data, $info);
            break;
        case 'eu':
            $info = parseWhoisEU($whois_data, $info);
            break;
        case 'de':
            $info = parseWhoisDE($whois_data, $info);
            break;
        // Aggiungi altri TLD specifici
    }
    
    return $info;
}

/**
 * Parser specifico per domini .it
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisIT($whois_data, $info) {
    // Pattern specifici per .it
    if (preg_match('/Status:\s*(.+)/i', $whois_data, $matches)) {
        $status = trim($matches[1]);
        if (stripos($status, 'ok') !== false) {
            $info['status'] = 'Active';
        } elseif (stripos($status, 'pendingDelete') !== false) {
            $info['status'] = 'Pending Delete';
        } else {
            $info['status'] = ucfirst($status);
        }
    }
    
    // Cerca organizzazione in formato italiano
    if ($info['owner'] == 'Non disponibile') {
        if (preg_match('/Organization:\s*(.+)$/mi', $whois_data, $matches)) {
            $org = trim($matches[1]);
            if ($org && !isPrivacyProtected($org)) {
                $info['owner'] = $org;
            }
        }
    }
    
    // Data formato .it
    if (preg_match('/Created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['created'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    if (preg_match('/Expire Date:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['expires'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    return $info;
}

/**
 * Parser specifico per domini .eu
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisEU($whois_data, $info) {
    // I domini .eu hanno spesso informazioni limitate per GDPR
    if (stripos($whois_data, 'Visit www.eurid.eu') !== false) {
        $info['registrar'] = 'EURid (Registry)';
    }
    
    return $info;
}

/**
 * Parser specifico per domini .de
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisDE($whois_data, $info) {
    // Pattern specifici DENIC
    if (preg_match('/Nserver:\s*(.+)/i', $whois_data, $matches)) {
        // I nameserver .de sono in formato diverso
        $ns = trim(strtolower($matches[1]));
        if (!in_array($ns, $info['nameservers'])) {
            $info['nameservers'][] = $ns;
        }
    }
    
    return $info;
}

/**
 * Estrae nameservers dal WHOIS
 * 
 * @param string $whois_data Dati WHOIS
 * @return array Lista nameservers
 */
function extractNameservers($whois_data) {
    $nameservers = array();
    
    $patterns = array(
        '/Name Server:\s*(.+)/i',
        '/Nameserver:\s*(.+)/i',
        '/nserver:\s*(.+)/i',
        '/NS:\s*(.+)/i',
        '/dns[0-9]*:\s*(.+)/i'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $whois_data, $matches)) {
            foreach ($matches[1] as $ns) {
                $ns = trim(strtolower($ns));
                if ($ns && !in_array($ns, $nameservers) && strpos($ns, '.') !== false) {
                    $nameservers[] = $ns;
                }
            }
        }
    }
    
    return array_unique($nameservers);
}

/**
 * Rileva DNSSEC dal WHOIS
 * 
 * @param string $whois_data Dati WHOIS
 * @return bool True se DNSSEC attivo
 */
function detectDnssec($whois_data) {
    $dnssec_indicators = array(
        'dnssec:',
        'dnssec signed',
        'dnssec: yes',
        'dnssec: active',
        'dnssec: signed',
        'ds record',
        'dnskey record'
    );
    
    $whois_lower = strtolower($whois_data);
    foreach ($dnssec_indicators as $indicator) {
        if (strpos($whois_lower, $indicator) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Gestisce indicazioni di privacy protection
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function handlePrivacyProtection($whois_data, $info) {
    $privacy_services = array(
        'whoisguard' => 'WhoisGuard Protected',
        'privacy protect' => 'Privacy Protection Service',
        'domain privacy' => 'Domain Privacy Service',
        'identity protect' => 'Identity Protection Service',
        'perfect privacy' => 'Perfect Privacy LLC',
        'domains by proxy' => 'Domains By Proxy',
        'contact privacy' => 'Contact Privacy Service'
    );
    
    $whois_lower = strtolower($whois_data);
    
    foreach ($privacy_services as $service => $name) {
        if (strpos($whois_lower, $service) !== false) {
            if ($info['owner'] == 'Non disponibile') {
                $info['owner'] = $name;
            }
            $info['privacy_protection'] = true;
            break;
        }
    }
    
    // GDPR
    if (preg_match('/REDACTED FOR PRIVACY|Data Protected|Privacy Protected|GDPR Masked|GDPR Redacted/i', $whois_data)) {
        if ($info['owner'] == 'Non disponibile') {
            $info['owner'] = 'Protetto da GDPR';
        }
        $info['gdpr_protected'] = true;
    }
    
    return $info;
}

/**
 * Genera report WHOIS strutturato
 * 
 * @param array $whois_info Informazioni WHOIS
 * @return array Report strutturato
 */
function generateWhoisReport($whois_info) {
    $report = array(
        'summary' => array(
            'domain_age' => calculateDomainAge($whois_info['created']),
            'days_to_expiry' => daysUntil($whois_info['expires']),
            'is_expiring_soon' => false,
            'privacy_enabled' => isset($whois_info['privacy_protection']) || isset($whois_info['gdpr_protected']),
            'dnssec_enabled' => $whois_info['dnssec']
        ),
        'risk_factors' => array(),
        'recommendations' => array()
    );
    
    // Valuta rischi
    if ($report['summary']['days_to_expiry'] !== false) {
        if ($report['summary']['days_to_expiry'] < 30) {
            $report['summary']['is_expiring_soon'] = true;
            $report['risk_factors'][] = 'Dominio in scadenza tra ' . $report['summary']['days_to_expiry'] . ' giorni';
            $report['recommendations'][] = 'Rinnova urgentemente il dominio per evitare la perdita';
        } elseif ($report['summary']['days_to_expiry'] < 90) {
            $report['recommendations'][] = 'Considera il rinnovo del dominio (scade tra ' . $report['summary']['days_to_expiry'] . ' giorni)';
        }
    }
    
    // Età dominio
    if ($report['summary']['domain_age'] !== false) {
        if ($report['summary']['domain_age'] < 30) {
            $report['risk_factors'][] = 'Dominio molto recente (' . $report['summary']['domain_age'] . ' giorni)';
        }
    }
    
    // DNSSEC
    if (!$whois_info['dnssec']) {
        $report['recommendations'][] = 'Considera l\'implementazione di DNSSEC per maggiore sicurezza';
    }
    
    return $report;
}

/**
 * Calcola l'età del dominio in giorni
 * 
 * @param string $created_date Data creazione
 * @return int|false Età in giorni o false
 */
function calculateDomainAge($created_date) {
    if (empty($created_date) || $created_date == 'Non disponibile') {
        return false;
    }
    
    $created = strtotime(str_replace('/', '-', $created_date));
    if (!$created) {
        return false;
    }
    
    return floor((time() - $created) / 86400);
}
?>
