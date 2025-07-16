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
            'query_time_ms' => $info['query_time'],
            'raw_length' => strlen($whois_data)
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
    $disabled = array_map('trim', $disabled);
    
    if (in_array('shell_exec', $disabled)) {
        return false;
    }
    
    // Test effettivo
    $test = @shell_exec('echo test');
    return ($test === "test\n" || $test === "test");
}

/**
 * Ottiene WHOIS via socket diretta
 * 
 * @param string $domain Dominio
 * @return string|false Dati WHOIS o false
 */
function getWhoisViaSocket($domain) {
    // Estrai TLD - verifica se la funzione esiste
    if (!function_exists('extractTLD')) {
        $tld = strtolower(substr(strrchr($domain, '.'), 1));
    } else {
        $tld = extractTLD($domain);
    }
    
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
        $errno = null;
        $errstr = null;
        $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
        
        if ($fp) {
            stream_set_timeout($fp, $timeout);
            
            // Alcuni server richiedono formati specifici
            $query = formatWhoisQuery($domain, $whois_server);
            fputs($fp, $query . "\r\n");
            
            while (!feof($fp)) {
                $line = fgets($fp, 128);
                if ($line === false) break;
                $response .= $line;
                
                $info = stream_get_meta_data($fp);
                if ($info['timed_out']) {
                    break;
                }
            }
            fclose($fp);
            
            // Per alcuni TLD dobbiamo fare una seconda query
            if ($response) {
                $response = handleRegistrarRedirect($response, $domain, $tld);
            }
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
    // Server completi per tutti i TLD principali
    $whois_servers = array(
        // gTLD
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'name' => 'whois.nic.name',
        'edu' => 'whois.educause.edu',
        'gov' => 'whois.dotgov.gov',
        'mil' => 'whois.nic.mil',
        'int' => 'whois.iana.org',
        
        // ccTLD Europa
        'it' => 'whois.nic.it',
        'eu' => 'whois.eu',
        'de' => 'whois.denic.de',
        'uk' => 'whois.nic.uk',
        'co.uk' => 'whois.nic.uk',
        'fr' => 'whois.nic.fr',
        'es' => 'whois.nic.es',
        'nl' => 'whois.domain-registry.nl',
        'be' => 'whois.dns.be',
        'ch' => 'whois.nic.ch',
        'at' => 'whois.nic.at',
        'pt' => 'whois.dns.pt',
        'pl' => 'whois.dns.pl',
        'cz' => 'whois.nic.cz',
        'se' => 'whois.iis.se',
        'no' => 'whois.norid.no',
        'dk' => 'whois.dk-hostmaster.dk',
        'fi' => 'whois.fi',
        'ie' => 'whois.domainregistry.ie',
        'gr' => 'whois.ripe.net',
        'ro' => 'whois.rotld.ro',
        'hu' => 'whois.nic.hu',
        
        // Altri ccTLD
        'us' => 'whois.nic.us',
        'ca' => 'whois.cira.ca',
        'au' => 'whois.auda.org.au',
        'jp' => 'whois.jprs.jp',
        'cn' => 'whois.cnnic.cn',
        'kr' => 'whois.kr',
        'ru' => 'whois.tcinet.ru',
        'br' => 'whois.registro.br',
        'mx' => 'whois.mx',
        'ar' => 'whois.nic.ar',
        'cl' => 'whois.nic.cl',
        'za' => 'whois.registry.net.za',
        'ae' => 'whois.aeda.net.ae',
        'sa' => 'whois.nic.sa',
        'in' => 'whois.inregistry.net',
        'sg' => 'whois.sgnic.sg',
        'hk' => 'whois.hkirc.hk',
        'tw' => 'whois.twnic.net.tw',
        'nz' => 'whois.srs.net.nz',
        
        // Nuovi gTLD
        'shop' => 'whois.nic.shop',
        'online' => 'whois.nic.online',
        'store' => 'whois.nic.store',
        'site' => 'whois.nic.site',
        'tech' => 'whois.nic.tech',
        'app' => 'whois.nic.google',
        'dev' => 'whois.nic.google',
        'cloud' => 'whois.nic.cloud',
        'digital' => 'whois.nic.digital',
        'company' => 'whois.nic.company',
        'agency' => 'whois.nic.agency',
        'email' => 'whois.nic.email',
        'ltd' => 'whois.nic.ltd',
        'solutions' => 'whois.nic.solutions',
        'services' => 'whois.nic.services',
        'media' => 'whois.nic.media',
        'world' => 'whois.nic.world',
        'life' => 'whois.nic.life',
        'live' => 'whois.nic.live',
        'today' => 'whois.nic.today',
        'technology' => 'whois.nic.technology',
        'design' => 'whois.nic.design',
        'software' => 'whois.nic.software',
        'expert' => 'whois.nic.expert',
        'work' => 'whois.nic.work',
        'business' => 'whois.nic.business',
        'top' => 'whois.nic.top',
        'xyz' => 'whois.nic.xyz',
        'club' => 'whois.nic.club',
        'space' => 'whois.nic.space',
        'blog' => 'whois.nic.blog',
        'news' => 'whois.nic.news',
        'pro' => 'whois.registrypro.pro',
        'mobi' => 'whois.dotmobiregistry.net',
        'tel' => 'whois.nic.tel',
        'asia' => 'whois.nic.asia',
        'tv' => 'tvwhois.verisign-grs.com',
        'cc' => 'ccwhois.verisign-grs.com',
        'me' => 'whois.nic.me',
        'io' => 'whois.nic.io',
        'co' => 'whois.nic.co'
    );
    
    // Controlla la configurazione globale
    if (isset($GLOBALS['whois_servers'][$tld])) {
        return $GLOBALS['whois_servers'][$tld];
    }
    
    // Usa la lista locale
    if (isset($whois_servers[$tld])) {
        return $whois_servers[$tld];
    }
    
    // Server di default per TLD sconosciuti
    return 'whois.iana.org';
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
        'whois.nic.it' => "{$domain}",
        'whois.nic.br' => "{$domain}",
        'whois.eu' => "{$domain}",
        'whois.dk-hostmaster.dk' => "--charset=utf-8 {$domain}",
        'whois.nic.hu' => "{$domain}",
        'whois.norid.no' => "{$domain}"
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
    // Solo per alcuni TLD che supportano il redirect
    if (!in_array($tld, array('com', 'net', 'tv', 'cc', 'jobs', 'biz', 'mobi'))) {
        return $response;
    }
    
    // Cerca il server del registrar
    $registrar_server = '';
    if (preg_match('/Registrar WHOIS Server:\s*(.+)/i', $response, $matches)) {
        $registrar_server = trim($matches[1]);
    } elseif (preg_match('/Whois Server:\s*(.+)/i', $response, $matches)) {
        $registrar_server = trim($matches[1]);
    }
    
    if ($registrar_server && strpos($registrar_server, '.') !== false && $registrar_server != 'whois.iana.org') {
        $errno = null;
        $errstr = null;
        $fp2 = @fsockopen($registrar_server, 43, $errno, $errstr, 10);
        if ($fp2) {
            stream_set_timeout($fp2, 10);
            fputs($fp2, $domain . "\r\n");
            
            $response2 = '';
            while (!feof($fp2)) {
                $line = fgets($fp2, 128);
                if ($line === false) break;
                $response2 .= $line;
                
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
    
    return $response;
}

/**
 * Query WHOIS via Internic
 * 
 * @param string $domain Dominio
 * @return string|false Dati WHOIS
 */
function getWhoisFromInternic($domain) {
    $errno = null;
    $errstr = null;
    $fp = @fsockopen('whois.internic.net', 43, $errno, $errstr, 10);
    if (!$fp) return false;
    
    stream_set_timeout($fp, 10);
    fputs($fp, "domain " . $domain . "\r\n");
    
    $response = '';
    while (!feof($fp)) {
        $line = fgets($fp, 128);
        if ($line === false) break;
        $response .= $line;
        
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
    
    // Lista di servizi WHOIS web gratuiti (solo per fallback)
    $services = array(
        'https://www.whois.com/whois/' . urlencode($domain),
        'https://who.is/whois/' . urlencode($domain)
    );
    
    foreach ($services as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ),
            CURLOPT_ENCODING => 'gzip, deflate'
        ));
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($html && $http_code == 200) {
            // Decodifica se necessario
            if (substr($html, 0, 2) == "\x1f\x8b") {
                $html = gzdecode($html);
            }
            
            // Estrai dati WHOIS dall'HTML
            // Pattern per whois.com
            if (preg_match('/<pre[^>]*class="df-raw"[^>]*>(.+?)<\/pre>/si', $html, $matches)) {
                return html_entity_decode(strip_tags($matches[1]));
            }
            // Pattern per who.is
            if (preg_match('/<pre[^>]*class="rawWhois"[^>]*>(.+?)<\/pre>/si', $html, $matches)) {
                return html_entity_decode(strip_tags($matches[1]));
            }
            // Pattern generico
            if (preg_match('/<pre[^>]*>(.+?)<\/pre>/si', $html, $matches)) {
                $content = html_entity_decode(strip_tags($matches[1]));
                // Verifica che sia effettivamente WHOIS
                if (stripos($content, 'domain') !== false || stripos($content, 'registr') !== false) {
                    return $content;
                }
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
    if (empty($whois_data)) {
        return $info;
    }
    
    // Pattern universali per diversi formati WHOIS
    $patterns = getWhoisPatterns();
    
    // Applica tutti i pattern
    foreach ($patterns as $key => $pattern_list) {
        if (!is_array($pattern_list)) {
            $pattern_list = array($pattern_list);
        }
        
        foreach ($pattern_list as $pattern) {
            if (preg_match($pattern, $whois_data, $matches)) {
                $value = isset($matches[1]) ? trim($matches[1]) : '';
                
                if (empty($value)) continue;
                
                // Processa il valore
                $value = processWhoisValue($key, $value);
                
                if ($value && !isPrivacyProtected($value)) {
                    $info[$key] = $value;
                    break; // Trovato valore valido, passa al prossimo campo
                }
            }
        }
    }
    
    // Parsing specifico per TLD
    $tld = function_exists('extractTLD') ? extractTLD($domain) : strtolower(substr(strrchr($domain, '.'), 1));
    $info = parseTldSpecific($whois_data, $info, $tld);
    
    // Estrai nameservers
    $nameservers = extractNameservers($whois_data);
    if (!empty($nameservers)) {
        $info['nameservers'] = $nameservers;
    }
    
    // Rileva DNSSEC
    $info['dnssec'] = detectDnssec($whois_data);
    
    // Gestione privacy/GDPR
    $info = handlePrivacyProtection($whois_data, $info);
    
    // Se lo status è ancora il default, prova a determinarlo
    if ($info['status'] == 'Active') {
        $info['status'] = extractDomainStatus($whois_data);
    }
    
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
            '/registrar:\s*(.+)/mi',
            '/Registrar Handle:\s*(.+)/i',
            '/Registrar Company:\s*(.+)/i'
        ),
        'created' => array(
            '/Creation Date:\s*(.+)/i',
            '/Created:\s*(.+)/i',
            '/Created On:\s*(.+)/i',
            '/Domain Registration Date:\s*(.+)/i',
            '/Registered on:\s*(.+)/i',
            '/created:\s*(.+)/mi',
            '/Registration Time:\s*(.+)/i',
            '/Registered:\s*(.+)/i',
            '/Domain Create Date:\s*(.+)/i',
            '/Domain Created:\s*(.+)/i',
            '/Creation date:\s*(.+)/i'
        ),
        'expires' => array(
            '/Expir\w+ Date:\s*(.+)/i',
            '/Expires:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/Registry Expiry Date:\s*(.+)/i',
            '/Registrar Registration Expiration Date:\s*(.+)/i',
            '/Expires On:\s*(.+)/i',
            '/Expiration Time:\s*(.+)/i',
            '/expire:\s*(.+)/mi',
            '/paid-till:\s*(.+)/i',
            '/Valid Until:\s*(.+)/i',
            '/Renewal Date:\s*(.+)/i',
            '/Domain Expiration Date:\s*(.+)/i'
        ),
        'updated' => array(
            '/Updated Date:\s*(.+)/i',
            '/Last Updated:\s*(.+)/i',
            '/Modified:\s*(.+)/i',
            '/Last Modified:\s*(.+)/i',
            '/Updated:\s*(.+)/i',
            '/last-update:\s*(.+)/i',
            '/Domain Last Updated:\s*(.+)/i',
            '/Last Update:\s*(.+)/i',
            '/changed:\s*(.+)/i'
        ),
        'owner' => array(
            '/Registrant Name:\s*(.+)/i',
            '/Registrant:\s*(.+)/i',
            '/Registrant Contact Name:\s*(.+)/i',
            '/Owner Name:\s*(.+)/i',
            '/Registrant Organization:\s*(.+)/i',
            '/Holder:\s*(.+)/i',
            '/Registrant Person:\s*(.+)/i',
            '/Domain Holder:\s*(.+)/i',
            '/owner-name:\s*(.+)/i',
            '/org-name:\s*(.+)/i'
        ),
        'registrant_org' => array(
            '/Registrant Organization:\s*(.+)/i',
            '/Registrant Organisation:\s*(.+)/i',
            '/Organization:\s*(.+)/i',
            '/Registrant Company:\s*(.+)/i',
            '/Company Name:\s*(.+)/i',
            '/org:\s*(.+)/mi',
            '/Organisation Name:\s*(.+)/i',
            '/owner-organization:\s*(.+)/i'
        ),
        'registrant_country' => array(
            '/Registrant Country:\s*(.+)/i',
            '/Country:\s*(.+)/i',
            '/Registrant Country\/Economy:\s*(.+)/i',
            '/Registrant Country Code:\s*(.+)/i',
            '/country:\s*(.+)/mi',
            '/Registrant CountryCode:\s*(.+)/i',
            '/owner-country:\s*(.+)/i'
        ),
        'registrant_email' => array(
            '/Registrant Email:\s*(.+)/i',
            '/Registrant E-mail:\s*(.+)/i',
            '/Email:\s*(.+)/i',
            '/e-mail:\s*(.+)/mi',
            '/Registrant Contact Email:\s*(.+)/i',
            '/owner-email:\s*(.+)/i'
        ),
        'registrant_phone' => array(
            '/Registrant Phone:\s*(.+)/i',
            '/Phone:\s*(.+)/i',
            '/Registrant Phone Number:\s*(.+)/i',
            '/phone:\s*(.+)/mi',
            '/Registrant Telephone:\s*(.+)/i',
            '/owner-phone:\s*(.+)/i'
        ),
        'status' => array(
            '/Domain Status:\s*(.+)/i',
            '/Status:\s*(.+)/i',
            '/domain status:\s*(.+)/mi',
            '/EPP Status:\s*(.+)/i',
            '/state:\s*(.+)/i',
            '/Registration Status:\s*(.+)/i',
            '/Current Status:\s*(.+)/i'
        )
    );
}

/**
 * Estrae lo status del dominio
 * 
 * @param string $whois_data Dati WHOIS
 * @return string Status del dominio
 */
function extractDomainStatus($whois_data) {
    // Cerca indicatori multipli di status
    $status_indicators = array(
        'clientTransferProhibited' => 'Active (Transfer Locked)',
        'clientDeleteProhibited' => 'Active (Delete Locked)',
        'clientUpdateProhibited' => 'Active (Update Locked)',
        'clientRenewProhibited' => 'Active (Renew Locked)',
        'ok' => 'Active',
        'active' => 'Active',
        'registered' => 'Active',
        'pendingDelete' => 'Pending Delete',
        'pendingTransfer' => 'Pending Transfer',
        'redemptionPeriod' => 'Redemption Period',
        'serverHold' => 'Server Hold',
        'clientHold' => 'Client Hold',
        'inactive' => 'Inactive',
        'pendingVerification' => 'Pending Verification'
    );
    
    $whois_lower = strtolower($whois_data);
    
    // Cerca status multipli
    $found_statuses = array();
    foreach ($status_indicators as $indicator => $status) {
        if (stripos($whois_data, $indicator) !== false) {
            $found_statuses[] = $status;
        }
    }
    
    // Se trovati status multipli, usa il più significativo
    if (!empty($found_statuses)) {
        // Priorità agli stati problematici
        $priority_statuses = array('Pending Delete', 'Redemption Period', 'Server Hold', 'Client Hold', 'Inactive');
        foreach ($priority_statuses as $priority) {
            if (in_array($priority, $found_statuses)) {
                return $priority;
            }
        }
        // Altrimenti usa il primo trovato
        return $found_statuses[0];
    }
    
    // Default
    return 'Active';
}

/**
 * Processa il valore WHOIS estratto
 * 
 * @param string $key Chiave del campo
 * @param string $value Valore estratto
 * @return string Valore processato
 */
function processWhoisValue($key, $value) {
    // Rimuovi caratteri di controllo e spazi extra
    $value = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', $value));
    $value = preg_replace('/\s+/', ' ', $value);
    
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
        $value = strtolower($value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : 'Non valida';
    }
    
    // Telefono - rimuovi caratteri non numerici per validazione
    if ($key == 'registrant_phone') {
        // Mantieni formato originale ma verifica che ci siano numeri
        $numbers_only = preg_replace('/[^0-9]/', '', $value);
        return strlen($numbers_only) >= 7 ? $value : 'Non disponibile';
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
    if (!$date || $date == 'Not Available' || strlen($date) < 6) {
        return 'Non disponibile';
    }
    
    // Rimuovi timezone se presente tra parentesi
    $date = preg_replace('/\s*\([^)]+\)\s*$/', '', $date);
    
    // Rimuovi T e Z per date ISO
    $date = str_replace(array('T', 'Z'), ' ', $date);
    
    // Prova diversi formati
    $timestamp = strtotime($date);
    
    if (!$timestamp || $timestamp < 0) {
        // Prova formato italiano
        $date = str_replace('/', '-', $date);
        $timestamp = strtotime($date);
    }
    
    // Prova con formati specifici
    if (!$timestamp || $timestamp < 0) {
        $formats = array(
            'Y-m-d H:i:s',
            'Y.m.d H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'd.m.Y H:i:s',
            'Y-m-d',
            'Y.m.d',
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'Ymd',
            'dmY'
        );
        
        foreach ($formats as $format) {
            $dateObj = DateTime::createFromFormat($format, $date);
            if ($dateObj) {
                $timestamp = $dateObj->getTimestamp();
                break;
            }
        }
    }
    
    // Se abbiamo un timestamp valido
    if ($timestamp && $timestamp > 0) {
        // Verifica che la data sia ragionevole (1990-2100)
        $year = date('Y', $timestamp);
        if ($year >= 1990 && $year <= 2100) {
            return date('d/m/Y', $timestamp);
        }
    }
    
    // Se tutto fallisce ma sembra una data
    if (preg_match('/\d{4}/', $date)) {
        return $date; // Ritorna come è
    }
    
    return 'Non disponibile';
}

/**
 * Formatta lo status del dominio
 * 
 * @param string $status Status grezzo
 * @return string Status formattato
 */
function formatWhoisStatus($status) {
    $status = strtolower(trim($status));
    
    // Rimuovi prefissi comuni
    $status = preg_replace('/^(client|server|ok\s*\(|epp\s+status:\s*)/i', '', $status);
    $status = trim($status, ' ()');
    
    // Mappa stati comuni
    $status_map = array(
        'ok' => 'Active',
        'active' => 'Active',
        'registered' => 'Active',
        'connect' => 'Active',
        'paid' => 'Active',
        'transferprohibited' => 'Active (Transfer Locked)',
        'deleteprohibited' => 'Active (Delete Locked)',
        'updateprohibited' => 'Active (Update Locked)',
        'renewprohibited' => 'Active (Renew Locked)',
        'pendingdelete' => 'Pending Delete',
        'pendingtransfer' => 'Pending Transfer',
        'pendingrenew' => 'Pending Renewal',
        'pendingupdate' => 'Pending Update',
        'redemptionperiod' => 'Redemption Period',
        'inactive' => 'Inactive',
        'hold' => 'On Hold',
        'locked' => 'Locked',
        'suspended' => 'Suspended',
        'expired' => 'Expired'
    );
    
    // Cerca corrispondenze
    foreach ($status_map as $pattern => $formatted) {
        if (strpos($status, $pattern) !== false) {
            return $formatted;
        }
    }
    
    // Se non trovato, capitalizza la prima lettera
    return ucfirst($status);
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
    if (preg_match('/^[A-Z]{2}$/', $country)) {
        return $country;
    }
    
    // Mappa paesi comuni (estesa)
    $country_map = array(
        // Europa
        'ITALY' => 'IT',
        'ITALIA' => 'IT',
        'UNITED KINGDOM' => 'GB',
        'GREAT BRITAIN' => 'GB',
        'ENGLAND' => 'GB',
        'GERMANY' => 'DE',
        'DEUTSCHLAND' => 'DE',
        'FRANCE' => 'FR',
        'SPAIN' => 'ES',
        'ESPAÑA' => 'ES',
        'NETHERLANDS' => 'NL',
        'HOLLAND' => 'NL',
        'BELGIUM' => 'BE',
        'SWITZERLAND' => 'CH',
        'AUSTRIA' => 'AT',
        'PORTUGAL' => 'PT',
        'POLAND' => 'PL',
        'CZECH REPUBLIC' => 'CZ',
        'SLOVAKIA' => 'SK',
        'HUNGARY' => 'HU',
        'ROMANIA' => 'RO',
        'BULGARIA' => 'BG',
        'GREECE' => 'GR',
        'SWEDEN' => 'SE',
        'NORWAY' => 'NO',
        'DENMARK' => 'DK',
        'FINLAND' => 'FI',
        'IRELAND' => 'IE',
        'LUXEMBOURG' => 'LU',
        'MALTA' => 'MT',
        'CYPRUS' => 'CY',
        'CROATIA' => 'HR',
        'SLOVENIA' => 'SI',
        'ESTONIA' => 'EE',
        'LATVIA' => 'LV',
        'LITHUANIA' => 'LT',
        
        // Americhe
        'UNITED STATES' => 'US',
        'USA' => 'US',
        'CANADA' => 'CA',
        'MEXICO' => 'MX',
        'BRAZIL' => 'BR',
        'BRASIL' => 'BR',
        'ARGENTINA' => 'AR',
        'CHILE' => 'CL',
        'COLOMBIA' => 'CO',
        'PERU' => 'PE',
        'VENEZUELA' => 'VE',
        'URUGUAY' => 'UY',
        'PARAGUAY' => 'PY',
        'ECUADOR' => 'EC',
        'BOLIVIA' => 'BO',
        
        // Asia
        'CHINA' => 'CN',
        'JAPAN' => 'JP',
        'SOUTH KOREA' => 'KR',
        'KOREA' => 'KR',
        'INDIA' => 'IN',
        'SINGAPORE' => 'SG',
        'HONG KONG' => 'HK',
        'TAIWAN' => 'TW',
        'THAILAND' => 'TH',
        'MALAYSIA' => 'MY',
        'INDONESIA' => 'ID',
        'PHILIPPINES' => 'PH',
        'VIETNAM' => 'VN',
        'UNITED ARAB EMIRATES' => 'AE',
        'UAE' => 'AE',
        'SAUDI ARABIA' => 'SA',
        'ISRAEL' => 'IL',
        'TURKEY' => 'TR',
        
        // Oceania
        'AUSTRALIA' => 'AU',
        'NEW ZEALAND' => 'NZ',
        
        // Africa
        'SOUTH AFRICA' => 'ZA',
        'EGYPT' => 'EG',
        'MOROCCO' => 'MA',
        'NIGERIA' => 'NG',
        'KENYA' => 'KE',
        
        // Altri
        'RUSSIA' => 'RU',
        'RUSSIAN FEDERATION' => 'RU',
        'UKRAINE' => 'UA'
    );
    
    // Cerca corrispondenza esatta
    if (isset($country_map[$country])) {
        return $country_map[$country];
    }
    
    // Cerca corrispondenza parziale
    foreach ($country_map as $name => $code) {
        if (stripos($country, $name) !== false || stripos($name, $country) !== false) {
            return $code;
        }
    }
    
    // Ritorna il valore originale se non trovato
    return substr($country, 0, 50); // Limita lunghezza
}

/**
 * Verifica se un valore è protetto da privacy
 * 
 * @param string $value Valore da verificare
 * @return bool True se protetto
 */
function isPrivacyProtected($value) {
    if (empty($value) || strlen($value) < 3) {
        return true;
    }
    
    $privacy_indicators = array(
        'not available',
        'n/a',
        'redacted',
        'privacy',
        'protected',
        'data protected',
        'gdpr',
        'masked',
        'hidden',
        'withheld',
        'confidential',
        'private',
        'xxx',
        '***',
        'none',
        'null',
        'not disclosed',
        'not shown',
        'upon request',
        'contact registry',
        'see registry',
        'whoisguard',
        'proxy',
        'anonymou',
        'redacted for privacy',
        'data redacted'
    );
    
    $value_lower = strtolower($value);
    foreach ($privacy_indicators as $indicator) {
        if (strpos($value_lower, $indicator) !== false) {
            return true;
        }
    }
    
    // Controlla se è solo numeri o caratteri speciali
    if (preg_match('/^[0-9\-\_\.\s]+$/', $value)) {
        return true;
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
        case 'uk':
        case 'co.uk':
            $info = parseWhoisUK($whois_data, $info);
            break;
        case 'fr':
            $info = parseWhoisFR($whois_data, $info);
            break;
        case 'nl':
            $info = parseWhoisNL($whois_data, $info);
            break;
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
    // Status specifico .it
    if (preg_match('/Status:\s*(.+)/i', $whois_data, $matches)) {
        $status = trim($matches[1]);
        if (stripos($status, 'ok') !== false || stripos($status, 'active') !== false) {
            $info['status'] = 'Active';
        } elseif (stripos($status, 'pendingDelete') !== false || stripos($status, 'redemptionPeriod') !== false) {
            $info['status'] = 'Pending Delete';
        } elseif (stripos($status, 'inactive') !== false) {
            $info['status'] = 'Inactive';
        } else {
            $info['status'] = ucfirst($status);
        }
    }
    
    // Organizzazione per .it
    if ($info['owner'] == 'Non disponibile' || isPrivacyProtected($info['owner'])) {
        if (preg_match('/Organization:\s*(.+)$/mi', $whois_data, $matches)) {
            $org = trim($matches[1]);
            if ($org && !isPrivacyProtected($org)) {
                $info['owner'] = $org;
                if ($info['registrant_org'] == 'Non disponibile') {
                    $info['registrant_org'] = $org;
                }
            }
        }
    }
    
    // Date formato .it
    if (preg_match('/Created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['created'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    if (preg_match('/Expire Date:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['expires'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    // Registrar per .it
    if (preg_match('/Registrar\s+Organization:\s*(.+)/i', $whois_data, $matches)) {
        $reg = trim($matches[1]);
        if ($reg && !isPrivacyProtected($reg)) {
            $info['registrar'] = $reg;
        }
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
        if ($info['registrar'] == 'Non disponibile') {
            $info['registrar'] = 'EURid (Registry)';
        }
        if ($info['owner'] == 'Non disponibile') {
            $info['owner'] = 'Protetto da GDPR (EURid)';
        }
    }
    
    // Nameserver formato .eu
    if (preg_match_all('/Name servers:\s*(.+?)(?:\s+\(.+?\))?\s*$/mi', $whois_data, $matches)) {
        foreach ($matches[1] as $ns) {
            $ns = trim(strtolower($ns));
            if (!in_array($ns, $info['nameservers'])) {
                $info['nameservers'][] = $ns;
            }
        }
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
    // Status .de
    if (preg_match('/Status:\s*(.+)/i', $whois_data, $matches)) {
        $status = trim($matches[1]);
        if (stripos($status, 'connect') !== false) {
            $info['status'] = 'Active';
        } else {
            $info['status'] = formatWhoisStatus($status);
        }
    }
    
    // Nameserver formato DENIC
    if (preg_match_all('/Nserver:\s*(.+?)(?:\s+[\d\.]+)?$/mi', $whois_data, $matches)) {
        foreach ($matches[1] as $ns) {
            $ns = trim(strtolower($ns));
            if (!in_array($ns, $info['nameservers']) && strpos($ns, '.') !== false) {
                $info['nameservers'][] = $ns;
            }
        }
    }
    
    // Data ultimo aggiornamento per .de
    if (preg_match('/Changed:\s*(.+)/i', $whois_data, $matches)) {
        $info['updated'] = formatWhoisDate($matches[1]);
    }
    
    return $info;
}

/**
 * Parser specifico per domini .uk
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisUK($whois_data, $info) {
    // Date formato UK
    if (preg_match('/Registered on:\s*(.+)/i', $whois_data, $matches)) {
        $info['created'] = formatWhoisDate($matches[1]);
    }
    
    if (preg_match('/Expiry date:\s*(.+)/i', $whois_data, $matches)) {
        $info['expires'] = formatWhoisDate($matches[1]);
    }
    
    if (preg_match('/Last updated:\s*(.+)/i', $whois_data, $matches)) {
        $info['updated'] = formatWhoisDate($matches[1]);
    }
    
    // Registrar per .uk
    if (preg_match('/Registrar:\s*(.+?)(?:\s+\[.+?\])?$/mi', $whois_data, $matches)) {
        $reg = trim($matches[1]);
        if ($reg && !isPrivacyProtected($reg)) {
            $info['registrar'] = $reg;
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .fr
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisFR($whois_data, $info) {
    // Date formato francese
    if (preg_match('/created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['created'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    if (preg_match('/anniversary:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
        $info['expires'] = date('d/m/Y', strtotime($matches[1]));
    }
    
    // Holder per .fr
    if (preg_match('/holder-c:\s*(.+)/i', $whois_data, $matches)) {
        $holder = trim($matches[1]);
        if ($holder && !isPrivacyProtected($holder) && $info['owner'] == 'Non disponibile') {
            $info['owner'] = $holder;
        }
    }
    
    return $info;
}

/**
 * Parser specifico per domini .nl
 * 
 * @param string $whois_data Dati WHOIS
 * @param array $info Info correnti
 * @return array Info aggiornate
 */
function parseWhoisNL($whois_data, $info) {
    // Status .nl
    if (preg_match('/Status:\s*(.+)/i', $whois_data, $matches)) {
        $status = trim($matches[1]);
        if (stripos($status, 'active') !== false) {
            $info['status'] = 'Active';
        } else {
            $info['status'] = formatWhoisStatus($status);
        }
    }
    
    // I domini .nl spesso nascondono informazioni
    if (stripos($whois_data, 'is a domainname of') !== false) {
        if ($info['owner'] == 'Non disponibile') {
            $info['owner'] = 'Informazioni protette (SIDN)';
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
    
    // Pattern multipli per catturare diversi formati
    $patterns = array(
        '/Name Server:\s*(.+)/i',
        '/Nameserver:\s*(.+)/i',
        '/nserver:\s*(.+?)(?:\s+[\d\.]+)?$/mi',  // Formato DENIC
        '/NS:\s*(.+)/i',
        '/dns[0-9]*:\s*(.+)/i',
        '/Name servers:\s*(.+)/i',  // Formato EURid
        '/DNS:\s*(.+)/i',
        '/host:\s*(.+)/i'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $whois_data, $matches)) {
            foreach ($matches[1] as $ns) {
                // Pulisci il nameserver
                $ns = trim(strtolower($ns));
                
                // Rimuovi IP se presente dopo il nome
                $ns = preg_replace('/\s+[\d\.]+$/', '', $ns);
                $ns = preg_replace('/\s+\(.+?\)$/', '', $ns);
                
                // Rimuovi trailing dot
                $ns = rtrim($ns, '.');
                
                // Verifica validità base
                if ($ns && strpos($ns, '.') !== false && 
                    !in_array($ns, $nameservers) && 
                    strlen($ns) > 3 &&
                    !is_numeric(str_replace('.', '', $ns))) {
                    $nameservers[] = $ns;
                }
            }
        }
    }
    
    // Rimuovi duplicati e ordina
    $nameservers = array_unique($nameservers);
    sort($nameservers);
    
    return $nameservers;
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
        'dnssec: signedDelegation',
        'dnssec: unsigned',
        'ds record',
        'dnskey record',
        'DS Data:',
        'DNSSEC DS Data:',
        'DNSSEC:',
        'signed: yes'
    );
    
    $whois_lower = strtolower($whois_data);
    
    // Cerca indicatori positivi
    foreach ($dnssec_indicators as $indicator) {
        if (stripos($whois_data, $indicator) !== false) {
            // Verifica che non sia "unsigned" o "no"
            $context = substr($whois_data, stripos($whois_data, $indicator), 50);
            if (!preg_match('/unsigned|no|disabled|inactive/i', $context)) {
                return true;
            }
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
    // Servizi di privacy comuni
    $privacy_services = array(
        'whoisguard' => 'WhoisGuard Protected',
        'privacy protect' => 'Privacy Protection Service',
        'domain privacy' => 'Domain Privacy Service',
        'identity protect' => 'Identity Protection Service',
        'perfect privacy' => 'Perfect Privacy LLC',
        'domains by proxy' => 'Domains By Proxy',
        'contact privacy' => 'Contact Privacy Service',
        'whois privacy' => 'WHOIS Privacy Service',
        'privacy guardian' => 'Privacy Guardian',
        'protected domain services' => 'Protected Domain Services',
        'anonymize' => 'Anonymize Service',
        'privacy hero' => 'Privacy Hero Inc',
        'whois agent' => 'Whois Agent',
        'privacy protect, llc' => 'Privacy Protect LLC',
        'domain protection services' => 'Domain Protection Services'
    );
    
    $whois_lower = strtolower($whois_data);
    
    // Cerca servizi di privacy
    foreach ($privacy_services as $service => $name) {
        if (strpos($whois_lower, $service) !== false) {
            if ($info['owner'] == 'Non disponibile' || isPrivacyProtected($info['owner'])) {
                $info['owner'] = $name;
            }
            $info['privacy_protection'] = true;
            break;
        }
    }
    
    // Indicatori GDPR
    $gdpr_indicators = array(
        'REDACTED FOR PRIVACY',
        'Data Protected',
        'Privacy Protected',
        'GDPR Masked',
        'GDPR Redacted',
        'Personal data',
        'not disclosed',
        'GDPR protected',
        'EU data protection',
        'data protection laws',
        'privacy reasons',
        'personal data protection'
    );
    
    foreach ($gdpr_indicators as $indicator) {
        if (stripos($whois_data, $indicator) !== false) {
            if ($info['owner'] == 'Non disponibile' || isPrivacyProtected($info['owner'])) {
                $info['owner'] = 'Protetto da GDPR';
            }
            $info['gdpr_protected'] = true;
            break;
        }
    }
    
    // Se molti campi sono protetti, marca tutto come privacy protected
    $protected_count = 0;
    foreach (array('owner', 'registrant_email', 'registrant_phone', 'registrant_org') as $field) {
        if (isPrivacyProtected($info[$field]) || 
            stripos($info[$field], 'protett') !== false ||
            stripos($info[$field], 'privacy') !== false) {
            $protected_count++;
        }
    }
    
    if ($protected_count >= 2) {
        $info['privacy_protection'] = true;
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
        } elseif ($report['summary']['domain_age'] < 180) {
            $report['risk_factors'][] = 'Dominio relativamente nuovo (' . round($report['summary']['domain_age'] / 30) . ' mesi)';
        } elseif ($report['summary']['domain_age'] > 3650) {
            $report['positive_factors'][] = 'Dominio storico (oltre ' . floor($report['summary']['domain_age'] / 365) . ' anni)';
        } elseif ($report['summary']['domain_age'] > 1825) {
            $report['positive_factors'][] = 'Dominio consolidato (' . floor($report['summary']['domain_age'] / 365) . ' anni)';
        }
    }
    
    // Valuta status
    $problematic_statuses = array('Pending Delete', 'Redemption Period', 'Server Hold', 'Client Hold', 'Inactive', 'Expired');
    if (in_array($whois_info['status'], $problematic_statuses)) {
        $report['risk_factors'][] = 'Status problematico: ' . $whois_info['status'];
        $report['recommendations'][] = 'Verifica con il tuo registrar lo status del dominio';
    }
    
    // DNSSEC
    if (!$whois_info['dnssec']) {
        $report['recommendations'][] = 'Considera l\'implementazione di DNSSEC per maggiore sicurezza DNS';
    } else {
        $report['positive_factors'][] = 'DNSSEC attivo per maggiore sicurezza';
    }
    
    // Privacy
    if ($report['summary']['privacy_enabled']) {
        $report['positive_factors'][] = 'Privacy WHOIS attiva per protezione dati';
    }
    
    // Nameserver
    if (count($whois_info['nameservers']) < 2) {
        $report['risk_factors'][] = 'Meno di 2 nameserver configurati';
        $report['recommendations'][] = 'Usa almeno 2 nameserver per garantire ridondanza';
    } elseif (count($whois_info['nameservers']) >= 2) {
        $report['positive_factors'][] = 'Nameserver ridondanti configurati';
    }
    
    // Calcola score complessivo
    $score = 100;
    $score -= count($report['risk_factors']) * 15;
    $score += count($report['positive_factors']) * 5;
    $score = max(0, min(100, $score));
    $report['summary']['health_score'] = $score;
    
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
