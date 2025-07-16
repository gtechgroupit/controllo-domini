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
        if ($whois_data && !strpos($whois_data, 'command not found')) {
            $info['source'] = 'shell';
        } else {
            $whois_data = '';
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
                    $info['nameservers'][] = strtolower(trim($ns['target'], '.'));
                }
            }
        }
    }
    
    // Calcola tempo query
    $info['query_time'] = round((microtime(true) - $info['query_time']) * 1000, 2);
    
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
    
    $disabled = ini_get('disable_functions');
    if ($disabled) {
        $disabled = explode(',', $disabled);
        $disabled = array_map('trim', $disabled);
        if (in_array('shell_exec', $disabled)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Ottiene WHOIS via socket diretta
 * 
 * @param string $domain
 * @return string|false
 */
function getWhoisViaSocket($domain) {
    $tld = getTLD($domain);
    $whois_server = getWhoisServer($tld);
    
    if (!$whois_server) {
        return false;
    }
    
    logDebug("Connessione a server WHOIS: {$whois_server} per TLD: {$tld}");
    
    $fp = @fsockopen($whois_server, 43, $errno, $errstr, 10);
    
    if (!$fp) {
        logDebug("Errore connessione: {$errstr} ({$errno})");
        return false;
    }
    
    // Alcuni server richiedono comandi specifici
    $query = $domain . "\r\n";
    if (in_array($tld, array('com', 'net'))) {
        $query = "domain " . $domain . "\r\n";
    }
    
    fputs($fp, $query);
    
    $out = '';
    while (!feof($fp)) {
        $out .= fgets($fp);
    }
    fclose($fp);
    
    // Se abbiamo un riferimento ad un altro server WHOIS, seguilo
    if (preg_match('/Whois Server:\s*(.+)/i', $out, $matches)) {
        $referral_server = trim($matches[1]);
        logDebug("Seguendo referral a: {$referral_server}");
        
        $fp2 = @fsockopen($referral_server, 43, $errno, $errstr, 10);
        if ($fp2) {
            fputs($fp2, $domain . "\r\n");
            $out2 = '';
            while (!feof($fp2)) {
                $out2 .= fgets($fp2);
            }
            fclose($fp2);
            
            // Combina i risultati
            $out = $out . "\n\n--- Referral Server ---\n\n" . $out2;
        }
    }
    
    return $out;
}

/**
 * Ottiene server WHOIS per TLD
 * 
 * @param string $tld
 * @return string|false
 */
function getWhoisServer($tld) {
    // Mappa TLD -> Server WHOIS
    $servers = array(
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'it' => 'whois.nic.it',
        'eu' => 'whois.eu',
        'de' => 'whois.denic.de',
        'uk' => 'whois.nic.uk',
        'co.uk' => 'whois.nic.uk',
        'fr' => 'whois.nic.fr',
        'es' => 'whois.nic.es',
        'ch' => 'whois.nic.ch',
        'nl' => 'whois.domain-registry.nl',
        'be' => 'whois.dns.be',
        'at' => 'whois.nic.at',
        'pl' => 'whois.dns.pl',
        'pt' => 'whois.dns.pt',
        'ro' => 'whois.rotld.ro',
        'cz' => 'whois.nic.cz',
        'se' => 'whois.iis.se',
        'no' => 'whois.norid.no',
        'dk' => 'whois.dk-hostmaster.dk',
        'fi' => 'whois.fi',
        'hu' => 'whois.nic.hu',
        'gr' => 'whois.ripe.net',
        'ie' => 'whois.domainregistry.ie',
        'lu' => 'whois.dns.lu',
        'tv' => 'whois.nic.tv',
        'cc' => 'whois.nic.cc',
        'ws' => 'whois.website.ws',
        'me' => 'whois.nic.me',
        'io' => 'whois.nic.io',
        'co' => 'whois.nic.co',
        'ca' => 'whois.cira.ca',
        'au' => 'whois.auda.org.au',
        'ru' => 'whois.tcinet.ru',
        'jp' => 'whois.jprs.jp',
        'cn' => 'whois.cnnic.cn',
        'kr' => 'whois.kr',
        'in' => 'whois.inregistry.net',
        'br' => 'whois.registro.br',
        'mx' => 'whois.mx',
        'za' => 'whois.registry.net.za',
        'us' => 'whois.nic.us'
    );
    
    if (isset($servers[$tld])) {
        return $servers[$tld];
    }
    
    // Prova con whois.nic.$tld come fallback
    $fallback = "whois.nic.{$tld}";
    if (checkdnsrr($fallback, 'A')) {
        return $fallback;
    }
    
    // Server generico IANA
    return 'whois.iana.org';
}

/**
 * Ottiene TLD da dominio
 * 
 * @param string $domain
 * @return string
 */
function getTLD($domain) {
    $parts = explode('.', $domain);
    
    // Gestisci TLD a due parti (es: co.uk)
    if (count($parts) >= 2) {
        $last = array_pop($parts);
        $second_last = array_pop($parts);
        
        $two_part_tlds = array('co', 'org', 'net', 'gov', 'edu', 'ac', 'com');
        
        if (in_array($second_last, $two_part_tlds)) {
            return $second_last . '.' . $last;
        }
        
        return $last;
    }
    
    return end($parts);
}

/**
 * Ottiene WHOIS da InterNIC
 * 
 * @param string $domain
 * @return string|false
 */
function getWhoisFromInternic($domain) {
    $url = "https://www.internic.net/whois.html";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "type=domain&query=" . urlencode($domain));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; DomainCheck/1.0)');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response && preg_match('/<pre>(.*?)<\/pre>/s', $response, $matches)) {
        return html_entity_decode(strip_tags($matches[1]));
    }
    
    return false;
}

/**
 * Ottiene WHOIS via CURL/Web API
 * 
 * @param string $domain
 * @return string|false
 */
function getWhoisViaCurl($domain) {
    // Lista di servizi WHOIS alternativi
    $services = array(
        "https://www.whois.com/whois/{$domain}",
        "https://who.is/whois/{$domain}",
        "https://whois.domaintools.com/{$domain}"
    );
    
    foreach ($services as $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            // Estrai dati WHOIS dal HTML
            if (preg_match('/<pre[^>]*>(.*?)<\/pre>/si', $response, $matches)) {
                return html_entity_decode(strip_tags($matches[1]));
            }
        }
    }
    
    return false;
}

/**
 * Parser universale per dati WHOIS
 * 
 * @param string $whois_data Dati WHOIS grezzi
 * @param array $info Array info da popolare
 * @param string $domain Dominio analizzato
 * @return array Info aggiornate
 */
function parseWhoisData($whois_data, $info, $domain) {
    if (!$whois_data) return $info;
    
    // Log per debug
    logDebug("Parsing WHOIS data per: {$domain}");
    
    // Pattern universali per estrarre informazioni
    $patterns = array(
        'registrar' => array(
            '/Registrar:\s*(.+)/i',
            '/Sponsoring Registrar:\s*(.+)/i',
            '/Registrar Name:\s*(.+)/i',
            '/registrar:\s*(.+)/i'
        ),
        'created' => array(
            '/Creation Date:\s*(.+)/i',
            '/Created:\s*(.+)/i',
            '/Domain Registration Date:\s*(.+)/i',
            '/Created On:\s*(.+)/i',
            '/created:\s*(.+)/i',
            '/Registered on:\s*(.+)/i',
            '/Registration Date:\s*(.+)/i'
        ),
        'expires' => array(
            '/Expiry Date:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/Expires:\s*(.+)/i',
            '/Expire Date:\s*(.+)/i',
            '/Domain Expiration Date:\s*(.+)/i',
            '/Registrar Registration Expiration Date:\s*(.+)/i',
            '/expire:\s*(.+)/i',
            '/Expiry date:\s*(.+)/i'
        ),
        'updated' => array(
            '/Updated Date:\s*(.+)/i',
            '/Last Updated:\s*(.+)/i',
            '/Updated:\s*(.+)/i',
            '/Last Update:\s*(.+)/i',
            '/Last Modified:\s*(.+)/i',
            '/changed:\s*(.+)/i'
        ),
        'nameservers' => array(
            '/Name Server:\s*(.+)/i',
            '/Nameserver:\s*(.+)/i',
            '/nserver:\s*(.+)/i',
            '/NS:\s*(.+)/i',
            '/Name servers:\s*(.+)/i'
        ),
        'status' => array(
            '/Status:\s*(.+)/i',
            '/Domain Status:\s*(.+)/i',
            '/state:\s*(.+)/i',
            '/EPP Status:\s*(.+)/i'
        ),
        'owner' => array(
            '/Registrant Name:\s*(.+)/i',
            '/Registrant:\s*(.+)/i',
            '/Registrant Contact Name:\s*(.+)/i',
            '/Holder:\s*(.+)/i',
            '/Registrant Organization:\s*(.+)/i',
            '/Organization:\s*(.+)/i',
            '/org:\s*(.+)/i'
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
            '/Telephone:\s*(.+)/i',
            '/phone:\s*(.+)/i'
        ),
        'registrant_country' => array(
            '/Registrant Country:\s*(.+)/i',
            '/Country:\s*(.+)/i',
            '/country:\s*(.+)/i',
            '/Registrant Country\/Economy:\s*(.+)/i'
        ),
        'registrant_org' => array(
            '/Registrant Organization:\s*(.+)/i',
            '/Organization:\s*(.+)/i',
            '/Company Name:\s*(.+)/i',
            '/org:\s*(.+)/i'
        )
    );
    
    // Estrai informazioni usando i pattern
    foreach ($patterns as $field => $field_patterns) {
        foreach ($field_patterns as $pattern) {
            if ($field == 'nameservers') {
                // I nameserver possono essere multipli
                if (preg_match_all($pattern, $whois_data, $matches)) {
                    foreach ($matches[1] as $ns) {
                        $ns = trim(strtolower($ns));
                        $ns = trim($ns, '.');
                        if (!empty($ns) && !in_array($ns, $info['nameservers'])) {
                            $info['nameservers'][] = $ns;
                        }
                    }
                }
            } else {
                // Campi singoli
                if (preg_match($pattern, $whois_data, $match)) {
                    $value = trim($match[1]);
                    if (!empty($value) && $value != 'N/A' && $value != 'Not Available') {
                        $info[$field] = $value;
                        break; // Trovato, passa al prossimo campo
                    }
                }
            }
        }
    }
    
    // Parser specifici per TLD
    $tld = getTLD($domain);
    
    switch ($tld) {
        case 'it':
            $info = parseWhoisIT($whois_data, $info);
            break;
        case 'uk':
        case 'co.uk':
            $info = parseWhoisUK($whois_data, $info);
            break;
        case 'de':
            $info = parseWhoisDE($whois_data, $info);
            break;
        case 'fr':
            $info = parseWhoisFR($whois_data, $info);
            break;
        case 'eu':
            $info = parseWhoisEU($whois_data, $info);
            break;
    }
    
    // Formatta date in formato consistente
    $date_fields = array('created', 'expires', 'updated');
    foreach ($date_fields as $field) {
        if ($info[$field] != 'Non disponibile') {
            $formatted = formatWhoisDate($info[$field]);
            if ($formatted) {
                $info[$field] = $formatted;
            }
        }
    }
    
    // Verifica DNSSEC
    if (preg_match('/dnssec:\s*(yes|active|signed|enabled)/i', $whois_data) ||
        preg_match('/DNSSEC:\s*(yes|active|signed|enabled)/i', $whois_data) ||
        strpos($whois_data, 'DS Data:') !== false ||
        strpos($whois_data, 'DNSSEC DS Data:') !== false) {
        $info['dnssec'] = true;
    }
    
    // Determina se la privacy è attiva
    $privacy_keywords = array(
        'privacy', 'protected', 'redacted', 'gdpr', 
        'data protected', 'not disclosed', 'masked',
        'whois privacy', 'privacy service', 'domains by proxy'
    );
    
    $check_fields = array('owner', 'registrant_email', 'registrant_phone');
    foreach ($check_fields as $field) {
        if (isset($info[$field])) {
            $value_lower = strtolower($info[$field]);
            foreach ($privacy_keywords as $keyword) {
                if (strpos($value_lower, $keyword) !== false) {
                    $info['privacy_protection'] = true;
                    break 2;
                }
            }
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .it
 * 
 * @param string $whois_data
 * @param array $info
 * @return array
 */
function parseWhoisIT($whois_data, $info) {
    // Status specifici .it
    if (preg_match('/Status:\s*(.+)/i', $whois_data, $match)) {
        $status = trim($match[1]);
        switch(strtolower($status)) {
            case 'active':
            case 'ok':
                $info['status'] = 'Active';
                break;
            case 'inactive':
                $info['status'] = 'Inactive';
                break;
            case 'pendingdelete':
            case 'redemptionperiod':
                $info['status'] = 'Pending Delete';
                break;
            default:
                $info['status'] = ucfirst($status);
        }
    }
    
    // Formato date italiano
    if (preg_match('/Created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['created'] = $match[1];
    }
    
    if (preg_match('/Last Update:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['updated'] = $match[1];
    }
    
    if (preg_match('/Expire Date:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['expires'] = $match[1];
    }
    
    // Registrant per .it
    if (preg_match('/Organization:\s*(.+)/i', $whois_data, $match)) {
        $info['owner'] = trim($match[1]);
        $info['registrant_org'] = trim($match[1]);
    }
    
    // Verifica se i dati sono protetti da GDPR
    if (strpos($whois_data, 'REDACTED FOR PRIVACY') !== false ||
        strpos($whois_data, 'Data Protected') !== false) {
        $info['gdpr_protected'] = true;
        
        // Se protetto, marca i campi sensibili
        if (isPrivacyProtected($info['registrant_email'])) {
            $info['registrant_email'] = 'Protetto da GDPR';
        }
        if (isPrivacyProtected($info['registrant_phone'])) {
            $info['registrant_phone'] = 'Protetto da GDPR';
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .uk
 * 
 * @param string $whois_data
 * @param array $info
 * @return array
 */
function parseWhoisUK($whois_data, $info) {
    // Date formato UK
    if (preg_match('/Registered on:\s*(\d{2}-[A-Za-z]{3}-\d{4})/i', $whois_data, $match)) {
        $info['created'] = $match[1];
    }
    
    if (preg_match('/Expiry date:\s*(\d{2}-[A-Za-z]{3}-\d{4})/i', $whois_data, $match)) {
        $info['expires'] = $match[1];
    }
    
    if (preg_match('/Last updated:\s*(\d{2}-[A-Za-z]{3}-\d{4})/i', $whois_data, $match)) {
        $info['updated'] = $match[1];
    }
    
    // Registrant
    if (preg_match('/Registrant:\s*\n\s*(.+)/i', $whois_data, $match)) {
        $info['owner'] = trim($match[1]);
    }
    
    return $info;
}

/**
 * Parser specifico per domini .de
 * 
 * @param string $whois_data
 * @param array $info
 * @return array
 */
function parseWhoisDE($whois_data, $info) {
    // I domini .de hanno un formato particolare
    if (preg_match('/Changed:\s*(.+)/i', $whois_data, $match)) {
        $info['updated'] = trim($match[1]);
    }
    
    // Holder invece di Registrant
    if (preg_match('/Holder:\s*(.+)/i', $whois_data, $match)) {
        $info['owner'] = trim($match[1]);
    }
    
    // Nserver invece di Name Server
    if (preg_match_all('/Nserver:\s*(.+)/i', $whois_data, $matches)) {
        $info['nameservers'] = array();
        foreach ($matches[1] as $ns) {
            $ns = trim(strtolower($ns));
            $ns = preg_replace('/\s+.*$/', '', $ns); // Rimuovi IP se presente
            if (!empty($ns)) {
                $info['nameservers'][] = $ns;
            }
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .fr
 * 
 * @param string $whois_data
 * @param array $info
 * @return array
 */
function parseWhoisFR($whois_data, $info) {
    // Date formato francese
    if (preg_match('/created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['created'] = $match[1];
    }
    
    if (preg_match('/anniversary:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['expires'] = $match[1];
    }
    
    if (preg_match('/last-update:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $match)) {
        $info['updated'] = $match[1];
    }
    
    // Holder
    if (preg_match('/holder-c:\s*(.+)/i', $whois_data, $match)) {
        $holder_id = trim($match[1]);
        // Cerca i dettagli del holder
        if (preg_match('/nic-hdl:\s*' . preg_quote($holder_id) . '\s*\ntype:\s*(.+)\s*\ncontact:\s*(.+)/i', $whois_data, $holder_match)) {
            $info['owner'] = trim($holder_match[2]);
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .eu
 * 
 * @param string $whois_data
 * @param array $info
 * @return array
 */
function parseWhoisEU($whois_data, $info) {
    // I domini .eu spesso nascondono le informazioni per GDPR
    if (strpos($whois_data, 'NOT DISCLOSED') !== false) {
        $info['gdpr_protected'] = true;
    }
    
    // Nameserver formato .eu
    if (preg_match_all('/Name servers:\s*\n((?:\s+.+\n)+)/i', $whois_data, $matches)) {
        $ns_block = $matches[1][0];
        $info['nameservers'] = array();
        
        $lines = explode("\n", $ns_block);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && strpos($line, '.') !== false) {
                // Rimuovi eventuali IP tra parentesi
                $ns = preg_replace('/\s*\(.*?\)\s*/', '', $line);
                $ns = trim(strtolower($ns));
                if (!empty($ns)) {
                    $info['nameservers'][] = $ns;
                }
            }
        }
    }
    
    return $info;
}

/**
 * Formatta una data WHOIS in formato consistente
 * 
 * @param string $date Data grezza
 * @return string|false Data formattata o false
 */
function formatWhoisDate($date) {
    if (empty($date) || $date == 'Non disponibile') {
        return false;
    }
    
    // Rimuovi eventuali timezone code alla fine
    $date = preg_replace('/\s+[A-Z]{3,4}$/', '', $date);
    $date = preg_replace('/\s+UTC$/', '', $date);
    $date = trim($date);
    
    // Prova diversi formati
    $formats = array(
        'Y-m-d',           // 2024-12-25
        'd/m/Y',           // 25/12/2024
        'd-m-Y',           // 25-12-2024
        'd.m.Y',           // 25.12.2024
        'd-M-Y',           // 25-Dec-2024
        'Y/m/d',           // 2024/12/25
        'Y.m.d',           // 2024.12.25
        'M d, Y',          // Dec 25, 2024
        'd M Y',           // 25 Dec 2024
        'Y-m-d\TH:i:s',    // 2024-12-25T10:30:00
        'Y-m-d H:i:s'      // 2024-12-25 10:30:00
    );
    
    foreach ($formats as $format) {
        $parsed = DateTime::createFromFormat($format, $date);
        if ($parsed !== false) {
            return $parsed->format('Y-m-d');
        }
    }
    
    // Ultimo tentativo con strtotime
    $timestamp = strtotime($date);
    if ($timestamp !== false && $timestamp > 0) {
        return date('Y-m-d', $timestamp);
    }
    
    // Se proprio non riusciamo, ritorna la data originale
    return $date;
}

/**
 * Verifica se un valore indica privacy protection
 * 
 * @param string $value
 * @return bool
 */
function isPrivacyProtected($value) {
    if (empty($value)) return false;
    
    $privacy_indicators = array(
        'privacy', 'protected', 'redacted', 'not disclosed',
        'data protected', 'gdpr', 'masked', 'hidden',
        'withheld', 'not available', 'n/a', 'xxx'
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
 * Log per debug
 * 
 * @param string $message
 */
function logDebug($message) {
    if (isset($GLOBALS['debug_mode']) && $GLOBALS['debug_mode']) {
        error_log("[WHOIS Debug] " . $message);
    }
}

/**
 * Ottiene suggerimenti basati su dati WHOIS
 * 
 * @param array $whois_info
 * @return array
 */
function getWhoisSuggestions($whois_info) {
    $suggestions = array();
    
    // Verifica scadenza
    $days_to_expiry = daysUntil($whois_info['expires']);
    
    if ($days_to_expiry !== false) {
        if ($days_to_expiry < 0) {
            $suggestions[] = array(
                'type' => 'critical',
                'message' => 'Il dominio è SCADUTO!',
                'action' => 'Rinnova immediatamente il dominio per evitare la perdita definitiva.'
            );
        } elseif ($days_to_expiry < 30) {
            $suggestions[] = array(
                'type' => 'warning', 
                'message' => "Il dominio scade tra {$days_to_expiry} giorni.",
                'action' => 'Rinnova urgentemente il dominio per evitare interruzioni.'
            );
        } elseif ($days_to_expiry < 90) {
            $suggestions[] = array(
                'type' => 'info',
                'message' => "Il dominio scade tra {$days_to_expiry} giorni.",
                'action' => 'Considera di rinnovare il dominio in anticipo.'
            );
        }
    }
    
    // Verifica DNSSEC
    if (!$whois_info['dnssec']) {
        $suggestions[] = array(
            'type' => 'info',
            'message' => 'DNSSEC non è abilitato.',
            'action' => 'Considera di abilitare DNSSEC per maggiore sicurezza.'
        );
    }
    
    // Verifica Privacy
    if (!isset($whois_info['privacy_protection']) && 
        !isset($whois_info['gdpr_protected'])) {
        $suggestions[] = array(
            'type' => 'info',
            'message' => 'La privacy WHOIS non è attiva.',
            'action' => 'Valuta l\'attivazione della privacy protection per proteggere i tuoi dati.'
        );
    }
    
    return $suggestions;
}

/**
 * Analizza health score del dominio basato su WHOIS
 * 
 * @param array $whois_info
 * @return array
 */
function analyzeWhoisHealth($whois_info) {
    $health = array(
        'score' => 100,
        'factors' => array(),
        'issues' => array()
    );
    
    // Controlla scadenza
    $days_to_expiry = daysUntil($whois_info['expires']);
    if ($days_to_expiry !== false) {
        if ($days_to_expiry < 0) {
            $health['score'] -= 50;
            $health['issues'][] = 'Dominio scaduto';
        } elseif ($days_to_expiry < 30) {
            $health['score'] -= 25;
            $health['issues'][] = 'Scadenza imminente';
        } elseif ($days_to_expiry < 90) {
            $health['score'] -= 10;
            $health['issues'][] = 'Scadenza prossima';
        } else {
            $health['factors'][] = 'Scadenza lontana';
        }
    }
    
    // Controlla DNSSEC
    if ($whois_info['dnssec']) {
        $health['factors'][] = 'DNSSEC attivo';
    } else {
        $health['score'] -= 5;
    }
    
    // Controlla età dominio
    $age = calculateDomainAge($whois_info['created']);
    if ($age !== false) {
        if ($age < 30) {
            $health['score'] -= 10;
            $health['issues'][] = 'Dominio molto recente';
        } elseif ($age > 365) {
            $health['factors'][] = 'Dominio maturo';
        }
    }
    
    // Controlla nameservers
    if (count($whois_info['nameservers']) < 2) {
        $health['score'] -= 15;
        $health['issues'][] = 'Pochi nameserver';
    } else {
        $health['factors'][] = 'Nameserver ridondanti';
    }
    
    // Non far scendere sotto 0
    $health['score'] = max(0, $health['score']);
    
    return $health;
}

/**
 * Genera report WHOIS completo
 * 
 * @param array $whois_info
 * @return array
 */
function generateWhoisReport($whois_info) {
    $report = array(
        'summary' => array(
            'domain_age' => calculateDomainAge($whois_info['created']),
            'days_to_expiry' => daysUntil($whois_info['expires']),
            'is_expiring_soon' => false,
            'is_expired' => false,
            'privacy_enabled' => isset($whois_info['privacy_protection']) || isset($whois_info['gdpr_protected']),
            'dnssec_enabled' => $whois_info['dnssec'],
            'status' => $whois_info['status']
        ),
        'risk_factors' => array(),
        'recommendations' => array(),
        'positive_factors' => array()
    );
    
    // Valuta rischi basati sulla scadenza
    if ($report['summary']['days_to_expiry'] !== false) {
        if ($report['summary']['days_to_expiry'] < 0) {
            $report['summary']['is_expired'] = true;
            $report['risk_factors'][] = 'DOMINIO SCADUTO!';
            $report['recommendations'][] = 'Il dominio è scaduto. Rinnova immediatamente per evitare la perdita definitiva.';
        } elseif ($report['summary']['days_to_expiry'] < 30) {
            $report['summary']['is_expiring_soon'] = true;
            $report['risk_factors'][] = 'Dominio in scadenza tra ' . $report['summary']['days_to_expiry'] . ' giorni';
            $report['recommendations'][] = 'Rinnova urgentemente il dominio per evitare interruzioni del servizio';
        } elseif ($report['summary']['days_to_expiry'] < 90) {
            $report['recommendations'][] = 'Considera il rinnovo del dominio (scade tra ' . $report['summary']['days_to_expiry'] . ' giorni)';
        } elseif ($report['summary']['days_to_expiry'] > 365) {
            $report['positive_factors'][] = 'Dominio rinnovato recentemente (scade tra oltre un anno)';
        }
    }
    
    // Valuta età del dominio
    if ($report['summary']['domain_age'] !== false) {
        if ($report['summary']['domain_age'] < 30) {
            $report['risk_factors'][] = 'Dominio molto recente (' . $report['summary']['domain_age'] . ' giorni)';
            $report['recommendations'][] = 'I domini nuovi possono avere minore reputazione. Costruisci gradualmente autorità e trust.';
        } elseif ($report['summary']['domain_age'] > 1825) { // 5+ anni
            $report['positive_factors'][] = 'Dominio consolidato (oltre 5 anni di età)';
        } elseif ($report['summary']['domain_age'] > 365) { // 1+ anno
            $report['positive_factors'][] = 'Dominio maturo (oltre 1 anno di età)';
        }
    }
    
    // Valuta sicurezza
    if (!$report['summary']['dnssec_enabled']) {
        $report['recommendations'][] = 'Abilita DNSSEC per proteggere il dominio da attacchi DNS';
    } else {
        $report['positive_factors'][] = 'DNSSEC abilitato per maggiore sicurezza';
    }
    
    // Valuta privacy
    if ($report['summary']['privacy_enabled']) {
        $report['positive_factors'][] = 'Privacy WHOIS attiva';
    } else {
        $report['recommendations'][] = 'Considera l\'attivazione della privacy WHOIS per proteggere i dati personali';
    }
    
    // Calcola health score
    $score = 100;
    
    // Penalità per rischi
    if ($report['summary']['is_expired']) $score -= 50;
    elseif ($report['summary']['is_expiring_soon']) $score -= 25;
    
    if ($report['summary']['domain_age'] !== false && $report['summary']['domain_age'] < 30) {
        $score -= 10;
    }
    
    if (!$report['summary']['dnssec_enabled']) $score -= 5;
    
    // Bonus per fattori positivi
    if ($report['summary']['domain_age'] !== false && $report['summary']['domain_age'] > 1825) {
        $score += 5;
    }
    
    if ($report['summary']['privacy_enabled']) $score += 5;
    
    // Normalizza score
    $score = max(0, min(100, $score));
    $report['summary']['health_score'] = $score;
    
    return $report;
}

/**
 * Calcola l'età del dominio in giorni
 * Usa function_exists per evitare ridichiarazione
 * 
 * @param string $created_date Data creazione
 * @return int|false Età in giorni o false
 */
if (!function_exists('calculateDomainAge')) {
    function calculateDomainAge($created_date) {
        if (empty($created_date) || $created_date == 'Non disponibile') {
            return false;
        }
        
        // Prova a parsare la data
        $created = strtotime(str_replace('/', '-', $created_date));
        
        if (!$created || $created < 0) {
            // Prova formati alternativi
            $formats = array('d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y');
            foreach ($formats as $format) {
                $dateObj = DateTime::createFromFormat($format, $created_date);
                if ($dateObj) {
                    $created = $dateObj->getTimestamp();
                    break;
                }
            }
        }
        
        if (!$created || $created < 0) {
            return false;
        }
        
        // Calcola differenza in giorni
        $age_days = floor((time() - $created) / 86400);
        
        // Verifica che sia un valore ragionevole
        if ($age_days < 0 || $age_days > 15000) { // Max ~41 anni
            return false;
        }
        
        return $age_days;
    }
}

/**
 * Calcola giorni fino a una data (già definita in utilities.php ma includiamo per sicurezza)
 * 
 * @param string $date Data target
 * @return int|false Giorni rimanenti o false
 */
if (!function_exists('daysUntil')) {
    function daysUntil($date) {
        if (empty($date) || $date == 'Non disponibile') {
            return false;
        }
        
        $timestamp = strtotime(str_replace('/', '-', $date));
        
        if (!$timestamp || $timestamp < 0) {
            // Prova formati alternativi
            $formats = array('d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y');
            foreach ($formats as $format) {
                $dateObj = DateTime::createFromFormat($format, $date);
                if ($dateObj) {
                    $timestamp = $dateObj->getTimestamp();
                    break;
                }
            }
        }
        
        if (!$timestamp || $timestamp < 0) {
            return false;
        }
        
        $days = floor(($timestamp - time()) / 86400);
        return $days;
    }
}
?>
