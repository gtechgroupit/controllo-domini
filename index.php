<?php
/**
 * DNS Mapper Tool - Enterprise Edition
 * Advanced DNS analysis with cloud service detection and WHOIS lookup
 * 
 * @author G Tech Group
 * @version 4.0
 */

// Headers per evitare problemi di cache e sicurezza
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Configurazione PHP sicura per Plesk
@ini_set('display_errors', 0);
@ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Funzione per calcolare il tempo di risposta DNS
function measureDnsResponseTime($domain) {
    $start = microtime(true);
    @dns_get_record($domain, DNS_A);
    $end = microtime(true);
    return round(($end - $start) * 1000, 2); // in millisecondi
}

// Funzione avanzata per ottenere informazioni WHOIS
function getWhoisInfo($domain, $debug = false) {
    $GLOBALS['debug_mode'] = $debug;
    $info = array(
        'registrar' => 'Non disponibile',
        'created' => 'Non disponibile',
        'expires' => 'Non disponibile',
        'status' => 'Active',
        'owner' => 'Non disponibile',
        'registrant_country' => 'Non disponibile',
        'registrant_org' => 'Non disponibile',
        'registrant_email' => 'Privacy Protected',
        'nameservers' => array()
    );
    
    // Metodo 1: Connessione diretta ai server WHOIS
    $whois_data = getWhoisViaSocket($domain);
    
    // Metodo 2: Se il socket fallisce, prova con shell_exec
    if (!$whois_data && function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $whois_data = @shell_exec("whois " . escapeshellarg($domain) . " 2>&1");
    }
    
    // Metodo 3: Se ancora nulla, prova con fsockopen a whois.internic.net
    if (!$whois_data) {
        $whois_data = getWhoisFromInternic($domain);
    }
    
    // Metodo 4: Usa cURL per API WHOIS pubbliche (fallback)
    if (!$whois_data) {
        $whois_data = getWhoisViaCurl($domain);
    }
    
    // Parse dei dati WHOIS
    if ($whois_data) {
        // Patterns multipli per diversi formati WHOIS
        $patterns = array(
            'registrar' => array(
                '/Registrar:\s*(.+)/i',
                '/Registrar Name:\s*(.+)/i',
                '/Sponsoring Registrar:\s*(.+)/i',
                '/Registrar Organization:\s*(.+)/i'
            ),
            'created' => array(
                '/Creation Date:\s*(.+)/i',
                '/Created:\s*(.+)/i',
                '/Created On:\s*(.+)/i',
                '/Domain Registration Date:\s*(.+)/i',
                '/Registered on:\s*(.+)/i',
                '/created:\s*(.+)/i',
                '/Registration Time:\s*(.+)/i'
            ),
            'expires' => array(
                '/Expiry Date:\s*(.+)/i',
                '/Expires:\s*(.+)/i',
                '/Expiration Date:\s*(.+)/i',
                '/Registry Expiry Date:\s*(.+)/i',
                '/Registrar Registration Expiration Date:\s*(.+)/i',
                '/Expires On:\s*(.+)/i',
                '/Expiration Time:\s*(.+)/i',
                '/expire:\s*(.+)/i'
            ),
            'owner' => array(
                '/Registrant Name:\s*(.+)/i',
                '/Registrant:\s*(.+)/i',
                '/Registrant Contact Name:\s*(.+)/i',
                '/Owner Name:\s*(.+)/i',
                '/Organization:\s*(.+)/i',
                '/Registrant Organization:\s*(.+)/i'
            ),
            'registrant_org' => array(
                '/Registrant Organization:\s*(.+)/i',
                '/Registrant Organisation:\s*(.+)/i',
                '/Organization:\s*(.+)/i',
                '/Registrant Company:\s*(.+)/i',
                '/Company Name:\s*(.+)/i'
            ),
            'registrant_country' => array(
                '/Registrant Country:\s*(.+)/i',
                '/Country:\s*(.+)/i',
                '/Registrant Country\/Economy:\s*(.+)/i',
                '/Registrant Country Code:\s*(.+)/i'
            ),
            'status' => array(
                '/Domain Status:\s*(.+)/i',
                '/Status:\s*(.+)/i',
                '/domain status:\s*(.+)/i',
                '/EPP Status:\s*(.+)/i'
            )
        );
        
        // Applica tutti i pattern
        foreach ($patterns as $key => $pattern_list) {
            foreach ($pattern_list as $pattern) {
                if (preg_match($pattern, $whois_data, $matches)) {
                    $value = trim($matches[1]);
                    
                    // Formatta le date
                    if (in_array($key, array('created', 'expires')) && $value && $value != 'Not Available') {
                        // Prova diversi formati di data
                        $timestamp = strtotime($value);
                        if (!$timestamp) {
                            // Prova formato italiano
                            $value = str_replace('/', '-', $value);
                            $timestamp = strtotime($value);
                        }
                        if (!$timestamp && preg_match('/(\d{4})[.\-\/](\d{2})[.\-\/](\d{2})/', $value, $date_parts)) {
                            // Prova a ricostruire la data
                            $timestamp = strtotime($date_parts[1] . '-' . $date_parts[2] . '-' . $date_parts[3]);
                        }
                        if ($timestamp && $timestamp > 0) {
                            $value = date('d/m/Y', $timestamp);
                        }
                    }
                    
                    // Pulisci lo status
                    if ($key == 'status') {
                        $value = ucfirst(strtolower(explode(' ', $value)[0]));
                        if (strpos(strtolower($value), 'ok') !== false || strpos(strtolower($value), 'active') !== false) {
                            $value = 'Active';
                        }
                    }
                    
                    if ($value && $value != '' && !preg_match('/not available|redacted|privacy|data protected/i', $value)) {
                        $info[$key] = $value;
                    }
                    break; // Esci dal loop se trovato
                }
            }
        }
        
        // Cerca nameservers
        if (preg_match_all('/Name Server:\s*(.+)|Nameserver:\s*(.+)|nserver:\s*(.+)|NS:\s*(.+)/i', $whois_data, $matches)) {
            foreach ($matches[0] as $match) {
                if (preg_match('/:\s*(.+)/', $match, $ns_match)) {
                    $ns = trim(strtolower($ns_match[1]));
                    if ($ns && !in_array($ns, $info['nameservers']) && strpos($ns, '.') !== false) {
                        $info['nameservers'][] = $ns;
                    }
                }
            }
        }
        
        // Se non troviamo il proprietario, cerca pattern italiani o altri
        if ($info['owner'] == 'Non disponibile') {
            // Pattern italiano
            if (preg_match('/Registrante[\s\S]*?Nome:\s*(.+)/i', $whois_data, $matches)) {
                $info['owner'] = trim($matches[1]);
            }
            // Pattern con organization come fallback
            elseif ($info['registrant_org'] != 'Non disponibile') {
                $info['owner'] = $info['registrant_org'];
            }
        }
        
        // Gestione privacy/GDPR
        if (preg_match('/REDACTED FOR PRIVACY|Data Protected|Privacy Protected|GDPR Masked|GDPR Redacted/i', $whois_data)) {
            if ($info['owner'] == 'Non disponibile') {
                $info['owner'] = 'Protetto da Privacy/GDPR';
            }
            if ($info['registrant_email'] == 'Privacy Protected') {
                $info['registrant_email'] = 'Protetto da Privacy/GDPR';
            }
        }
        
        // Se abbiamo trovato pochi dati, potrebbe essere un dominio .it con formato diverso
        if ($info['owner'] == 'Non disponibile' || $info['owner'] == 'Protetto da Privacy/GDPR') {
            // Cerca sezione Organization per domini .it
            if (preg_match('/Organization:\s*(.+)$/mi', $whois_data, $matches)) {
                $org = trim($matches[1]);
                if ($org && !preg_match('/not available|redacted|privacy/i', $org)) {
                    $info['owner'] = $org;
                }
            }
        }
        
        // Parsing speciale per domini .it
        if (strpos($domain, '.it') !== false) {
            // Status per domini .it
            if (preg_match('/Status:\s*(.+)/i', $whois_data, $matches)) {
                $status = trim($matches[1]);
                if (stripos($status, 'ok') !== false || stripos($status, 'active') !== false) {
                    $info['status'] = 'Active';
                } elseif (stripos($status, 'pendingDelete') !== false) {
                    $info['status'] = 'Pending Delete';
                } elseif (stripos($status, 'inactive') !== false) {
                    $info['status'] = 'Inactive';
                } else {
                    $info['status'] = ucfirst($status);
                }
            }
            
            // Data di creazione per .it
            if ($info['created'] == 'Non disponibile' && preg_match('/Created:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
                $info['created'] = date('d/m/Y', strtotime($matches[1]));
            }
            
            // Data di scadenza per .it
            if ($info['expires'] == 'Non disponibile' && preg_match('/Expire Date:\s*(\d{4}-\d{2}-\d{2})/i', $whois_data, $matches)) {
                $info['expires'] = date('d/m/Y', strtotime($matches[1]));
            }
            
            // Registrar per .it
            if ($info['registrar'] == 'Non disponibile' && preg_match('/Registrar[\s\S]*?Organization:\s*(.+)/i', $whois_data, $matches)) {
                $info['registrar'] = trim($matches[1]);
            }
        }
        
        // Ulteriore tentativo di parsing per informazioni mancanti
        if ($info['owner'] == 'Non disponibile' || $info['owner'] == 'Protetto da Privacy/GDPR') {
            // Cerca Contact Name generico
            if (preg_match('/Contact Name:\s*(.+)/i', $whois_data, $matches)) {
                $name = trim($matches[1]);
                if ($name && !preg_match('/not available|redacted|privacy/i', $name)) {
                    $info['owner'] = $name;
                }
            }
        }
        
        // Se ancora non abbiamo date, prova formati alternativi
        if ($info['created'] == 'Non disponibile') {
            if (preg_match('/registered:\s*(.+)/i', $whois_data, $matches)) {
                $date = trim($matches[1]);
                $timestamp = strtotime($date);
                if ($timestamp) {
                    $info['created'] = date('d/m/Y', $timestamp);
                }
            }
        }
        
        if ($info['expires'] == 'Non disponibile') {
            if (preg_match('/paid-till:\s*(.+)/i', $whois_data, $matches)) {
                $date = trim($matches[1]);
                $timestamp = strtotime($date);
                if ($timestamp) {
                    $info['expires'] = date('d/m/Y', $timestamp);
                }
            }
        }
        
        // Parsing per domini con protezione privacy
        if (stripos($whois_data, 'whoisguard') !== false || 
            stripos($whois_data, 'privacy protect') !== false ||
            stripos($whois_data, 'domain privacy') !== false ||
            stripos($whois_data, 'identity protect') !== false) {
            if ($info['owner'] == 'Non disponibile') {
                $info['owner'] = 'Servizio Privacy Attivo';
            }
        }
    }
    }
    
    // Se non abbiamo nameserver dal WHOIS, prova a prenderli dai record DNS NS
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
    
    // Se non abbiamo ancora dati significativi, almeno proviamo a determinare se il dominio esiste
    if ($info['owner'] == 'Non disponibile' && $info['registrar'] == 'Non disponibile') {
        // Verifica se il dominio ha almeno record DNS
        $has_dns = @dns_get_record($domain, DNS_ANY);
        if ($has_dns) {
            $info['owner'] = 'Informazioni protette';
            $info['status'] = 'Active';
        }
    }
    
    // Debug output
    if (isset($GLOBALS['debug_mode']) && $GLOBALS['debug_mode'] && $whois_data) {
        $info['_debug'] = substr($whois_data, 0, 500) . '...';
    }
    }
    
    return $info;
}

// Funzione per ottenere WHOIS via socket diretta
function getWhoisViaSocket($domain) {
    // Determina il server WHOIS basato sul TLD
    $tld = strtolower(substr(strrchr($domain, '.'), 1));
    
    // Se è un dominio di secondo livello (es: .co.uk)
    if (strpos($domain, '.co.uk') !== false) $tld = 'co.uk';
    if (strpos($domain, '.org.uk') !== false) $tld = 'org.uk';
    if (strpos($domain, '.com.au') !== false) $tld = 'com.au';
    
    $whois_servers = array(
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'it' => 'whois.nic.it',
        'eu' => 'whois.eu',
        'de' => 'whois.denic.de',
        'fr' => 'whois.afnic.fr',
        'uk' => 'whois.nic.uk',
        'co.uk' => 'whois.nic.uk',
        'org.uk' => 'whois.nic.uk',
        'nl' => 'whois.domain-registry.nl',
        'es' => 'whois.nic.es',
        'ch' => 'whois.nic.ch',
        'at' => 'whois.nic.at',
        'be' => 'whois.dns.be',
        'jp' => 'whois.jprs.jp',
        'cn' => 'whois.cnnic.cn',
        'au' => 'whois.auda.org.au',
        'com.au' => 'whois.auda.org.au',
        'ca' => 'whois.cira.ca',
        'us' => 'whois.nic.us',
        'mx' => 'whois.mx',
        'br' => 'whois.registro.br',
        'io' => 'whois.nic.io',
        'me' => 'whois.nic.me',
        'tv' => 'whois.tv',
        'cc' => 'whois.nic.cc',
        'ws' => 'whois.website.ws',
        'mobi' => 'whois.dotmobiregistry.net',
        'pro' => 'whois.registry.pro',
        'edu' => 'whois.educause.edu',
        'gov' => 'whois.nic.gov'
    );
    
    $whois_server = isset($whois_servers[$tld]) ? $whois_servers[$tld] : 'whois.iana.org';
    
    // Timeout e tentativi
    $timeout = 10;
    $max_attempts = 2;
    $attempt = 0;
    $response = '';
    
    while ($attempt < $max_attempts && empty($response)) {
        $attempt++;
        $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
        
        if ($fp) {
            // Imposta timeout per la lettura
            stream_set_timeout($fp, $timeout);
            
            // Alcuni server richiedono formati specifici
            if ($whois_server == 'whois.denic.de') {
                fputs($fp, "-T dn " . $domain . "\r\n");
            } else {
                fputs($fp, $domain . "\r\n");
            }
            
            while (!feof($fp)) {
                $response .= fgets($fp, 128);
                $info = stream_get_meta_data($fp);
                if ($info['timed_out']) {
                    break;
                }
            }
            fclose($fp);
            
            // Per alcuni TLD dobbiamo fare una seconda query
            if (in_array($tld, array('com', 'net', 'tv', 'cc')) && preg_match('/Registrar WHOIS Server:\s*(.+)/i', $response, $matches)) {
                $registrar_server = trim($matches[1]);
                if ($registrar_server && $registrar_server != $whois_server && strpos($registrar_server, '.') !== false) {
                    $fp2 = @fsockopen($registrar_server, 43, $errno, $errstr, $timeout);
                    if ($fp2) {
                        stream_set_timeout($fp2, $timeout);
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
                            $response .= "\n" . $response2;
                        }
                    }
                }
            }
        }
    }
    
    return $response;
}

// Funzione fallback per Internic
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
    
    return $response;
}

// Funzione per ottenere WHOIS via cURL (usando servizi web)
function getWhoisViaCurl($domain) {
    if (!function_exists('curl_init')) {
        return false;
    }
    
    // Prova con who.is (servizio gratuito)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://who.is/whois/" . urlencode($domain));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($html && $http_code == 200) {
        // Estrai i dati WHOIS dall'HTML
        if (preg_match('/<pre[^>]*class="df-raw"[^>]*>(.+?)<\/pre>/si', $html, $matches) ||
            preg_match('/<pre[^>]*>(.+?)<\/pre>/si', $html, $matches)) {
            return html_entity_decode(strip_tags($matches[1]));
        }
    }
    
    return false;
}

// Funzione per identificare servizi cloud dai record DNS
function identifyCloudServices($dns_results) {
    $services = array();
    
    // Check Microsoft 365
    $ms365_indicators = array(
        'mx' => array('outlook.com', 'mail.protection.outlook.com'),
        'txt' => array('MS=', 'v=spf1 include:spf.protection.outlook.com'),
        'cname' => array('autodiscover.outlook.com', 'enterpriseregistration.windows.net', 'enterpriseenrollment.manage.microsoft.com')
    );
    
    // Check Google Workspace
    $google_indicators = array(
        'mx' => array('aspmx.l.google.com', 'alt1.aspmx.l.google.com', 'alt2.aspmx.l.google.com'),
        'txt' => array('google-site-verification=', 'v=spf1 include:_spf.google.com'),
        'cname' => array('ghs.google.com', 'googlehosted.com')
    );
    
    // Analizza record MX per Microsoft 365
    if (isset($dns_results['MX'])) {
        foreach ($dns_results['MX'] as $mx) {
            foreach ($ms365_indicators['mx'] as $indicator) {
                if (stripos($mx['target'], $indicator) !== false) {
                    $services['microsoft365'] = array(
                        'detected' => true,
                        'confidence' => 'high',
                        'details' => 'Record MX Microsoft 365 rilevato: ' . $mx['target']
                    );
                    break 2;
                }
            }
        }
    }
    
    // Analizza record MX per Google Workspace
    if (isset($dns_results['MX'])) {
        foreach ($dns_results['MX'] as $mx) {
            foreach ($google_indicators['mx'] as $indicator) {
                if (stripos($mx['target'], $indicator) !== false) {
                    $services['google_workspace'] = array(
                        'detected' => true,
                        'confidence' => 'high',
                        'details' => 'Record MX Google Workspace rilevato: ' . $mx['target']
                    );
                    break 2;
                }
            }
        }
    }
    
    // Analizza record TXT
    if (isset($dns_results['TXT'])) {
        foreach ($dns_results['TXT'] as $txt) {
            $txt_value = $txt['txt'];
            
            // Microsoft 365
            foreach ($ms365_indicators['txt'] as $indicator) {
                if (stripos($txt_value, $indicator) !== false) {
                    if (!isset($services['microsoft365'])) {
                        $services['microsoft365'] = array(
                            'detected' => true,
                            'confidence' => 'medium',
                            'details' => 'Configurazione Microsoft 365 rilevata'
                        );
                    }
                }
            }
            
            // Google Workspace
            foreach ($google_indicators['txt'] as $indicator) {
                if (stripos($txt_value, $indicator) !== false) {
                    if (!isset($services['google_workspace'])) {
                        $services['google_workspace'] = array(
                            'detected' => true,
                            'confidence' => 'medium',
                            'details' => 'Configurazione Google Workspace rilevata'
                        );
                    }
                }
            }
        }
    }
    
    return $services;
}

// Funzione per analizzare la salute del dominio
function analyzeDomainHealth($dns_results, $cloud_services, $blacklist_results = null) {
    $health = array(
        'score' => 0,
        'issues' => array(),
        'suggestions' => array(),
        'positives' => array()
    );
    
    $max_score = 100;
    $current_score = 100;
    
    // Controlla presenza record A
    if (!isset($dns_results['A']) || empty($dns_results['A'])) {
        $current_score -= 20;
        $health['issues'][] = "Nessun record A trovato";
        $health['suggestions'][] = "Aggiungi almeno un record A per il tuo dominio";
    } else {
        $health['positives'][] = "✓ Record A configurato correttamente";
    }
    
    // Controlla MX records per email
    if (!isset($dns_results['MX']) || empty($dns_results['MX'])) {
        $current_score -= 15;
        $health['issues'][] = "Nessun record MX configurato";
        $health['suggestions'][] = "Configura i record MX per ricevere email";
    } else {
        $health['positives'][] = "✓ Record MX configurati (" . count($dns_results['MX']) . " server)";
        
        // Bonus per servizi cloud
        if (isset($cloud_services['microsoft365'])) {
            $health['positives'][] = "✓ Microsoft 365 configurato";
        } elseif (isset($cloud_services['google_workspace'])) {
            $health['positives'][] = "✓ Google Workspace configurato";
        }
    }
    
    // Controlla NS records
    if (isset($dns_results['NS']) && count($dns_results['NS']) < 2) {
        $current_score -= 10;
        $health['issues'][] = "Solo " . count($dns_results['NS']) . " nameserver configurato";
        $health['suggestions'][] = "Usa almeno 2 nameserver per ridondanza";
    } else {
        $health['positives'][] = "✓ Nameserver ridondanti configurati";
    }
    
    // Controlla SPF record
    $has_spf = false;
    $has_dmarc = false;
    $has_dkim = false;
    
    if (isset($dns_results['TXT'])) {
        foreach ($dns_results['TXT'] as $txt) {
            if (strpos($txt['txt'], 'v=spf1') !== false) {
                $has_spf = true;
            }
            if (strpos($txt['txt'], 'v=DMARC1') !== false) {
                $has_dmarc = true;
            }
            if (strpos($txt['txt'], 'v=DKIM1') !== false || strpos($txt['txt'], 'k=rsa') !== false) {
                $has_dkim = true;
            }
        }
    }
    
    if (!$has_spf) {
        $current_score -= 10;
        $health['issues'][] = "Nessun record SPF trovato";
        $health['suggestions'][] = "Aggiungi un record SPF per migliorare la deliverability email";
    } else {
        $health['positives'][] = "✓ Record SPF configurato";
    }
    
    if (!$has_dmarc) {
        $current_score -= 5;
        $health['suggestions'][] = "Considera l'aggiunta di un record DMARC per maggiore protezione";
    } else {
        $health['positives'][] = "✓ Record DMARC configurato";
    }
    
    if ($has_dkim) {
        $health['positives'][] = "✓ DKIM configurato";
    }
    
    // Controlla HTTPS (CAA records)
    if (isset($dns_results['CAA']) && !empty($dns_results['CAA'])) {
        $health['positives'][] = "✓ Record CAA configurati per sicurezza SSL";
    }
    
    // Controlla blacklist
    if ($blacklist_results && isset($blacklist_results['listed']) && $blacklist_results['listed'] > 0) {
        $penalty = min(20, $blacklist_results['listed'] * 5);
        $current_score -= $penalty;
        $health['issues'][] = "IP presente in " . $blacklist_results['listed'] . " blacklist";
        $health['suggestions'][] = "Rimuovi gli IP dalle blacklist per migliorare la reputazione";
    } elseif ($blacklist_results && $blacklist_results['listed'] == 0) {
        $health['positives'][] = "✓ Nessuna presenza in blacklist";
    }
    
    $health['score'] = max(0, $current_score);
    return $health;
}

// Funzione per controllare le blacklist
function checkBlacklists($domain) {
    $blacklists = array(
        'reputation' => array(),
        'issues' => array(),
        'clean' => array(),
        'checked' => 0,
        'listed' => 0
    );
    
    // Lista delle principali blacklist DNS
    $dnsbl_servers = array(
        'zen.spamhaus.org' => 'Spamhaus ZEN',
        'bl.spamcop.net' => 'SpamCop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS',
        'spam.dnsbl.sorbs.net' => 'SORBS Spam',
        'cbl.abuseat.org' => 'CBL Abuseat',
        'dnsbl-1.uceprotect.net' => 'UCEPROTECT Level 1',
        'psbl.surriel.com' => 'PSBL',
        'db.wpbl.info' => 'WPBL',
        'ix.dnsbl.manitu.net' => 'Manitu',
        'combined.rbl.msrbl.net' => 'MSRBL',
        'multi.spamhaus.org' => 'Spamhaus Multi',
        'bogons.cymru.com' => 'Team Cymru Bogons',
        'tor.dan.me.uk' => 'TOR Exit Nodes',
        'rbl.spamlab.com' => 'SpamLab',
        'noptr.spamrats.com' => 'SpamRats NoPtr',
        'spam.spamrats.com' => 'SpamRats Spam',
        'virbl.dnsbl.bit.nl' => 'VirBL',
        'wormrbl.imp.ch' => 'Worm RBL',
        'spamguard.leadmon.net' => 'SpamGuard'
    );
    
    // Ottieni gli IP del dominio
    $ips = array();
    
    // Record A (IPv4)
    $a_records = @dns_get_record($domain, DNS_A);
    if ($a_records) {
        foreach ($a_records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
        }
    }
    
    // Se non ci sono IP diretti, prova con www
    if (empty($ips)) {
        $www_records = @dns_get_record('www.' . $domain, DNS_A);
        if ($www_records) {
            foreach ($www_records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }
    }
    
    // Controlla ogni IP contro le blacklist
    foreach ($ips as $ip) {
        $reverse_ip = implode('.', array_reverse(explode('.', $ip)));
        
        foreach ($dnsbl_servers as $dnsbl => $name) {
            $blacklists['checked']++;
            $query = $reverse_ip . '.' . $dnsbl;
            
            // Controlla se l'IP è listato
            $result = @gethostbyname($query);
            
            if ($result && $result != $query) {
                // IP è listato in questa blacklist
                $blacklists['listed']++;
                $blacklists['issues'][] = array(
                    'ip' => $ip,
                    'blacklist' => $name,
                    'dnsbl' => $dnsbl,
                    'status' => 'listed'
                );
            } else {
                $blacklists['clean'][] = array(
                    'ip' => $ip,
                    'blacklist' => $name
                );
            }
        }
    }
    
    // Calcola la reputazione
    if ($blacklists['checked'] > 0) {
        $clean_percentage = (($blacklists['checked'] - $blacklists['listed']) / $blacklists['checked']) * 100;
        
        if ($clean_percentage == 100) {
            $blacklists['reputation'] = array(
                'score' => 100,
                'status' => 'Eccellente',
                'color' => 'success'
            );
        } elseif ($clean_percentage >= 95) {
            $blacklists['reputation'] = array(
                'score' => round($clean_percentage),
                'status' => 'Buona',
                'color' => 'info'
            );
        } elseif ($clean_percentage >= 80) {
            $blacklists['reputation'] = array(
                'score' => round($clean_percentage),
                'status' => 'Attenzione',
                'color' => 'warning'
            );
        } else {
            $blacklists['reputation'] = array(
                'score' => round($clean_percentage),
                'status' => 'Critica',
                'color' => 'error'
            );
        }
    } else {
        $blacklists['reputation'] = array(
            'score' => 0,
            'status' => 'Non verificabile',
            'color' => 'gray'
        );
    }
    
    $blacklists['ips_checked'] = $ips;
    
    return $blacklists;
}

// Funzione per formattare i risultati DNS con più dettagli
function formatDnsRecord($type, $records, $cloud_services = array()) {
    $output = "";
    if (!empty($records)) {
        $icon_map = array(
            'A' => '🌐',
            'AAAA' => '🌍',
            'CNAME' => '🔗',
            'MX' => '📧',
            'TXT' => '📝',
            'NS' => '🖥️',
            'SOA' => '👑',
            'SRV' => '🔧',
            'CAA' => '🔐'
        );
        
        $icon = isset($icon_map[$type]) ? $icon_map[$type] : '📌';
        
        $output .= "<div class='record-type' data-aos='fade-up'>\n";
        $output .= "<div class='record-header'>\n";
        $output .= "<span class='record-icon'>{$icon}</span>\n";
        $output .= "<h3 class='record-title'>{$type} Records</h3>\n";
        $output .= "<span class='record-count'>" . count($records) . " record" . (count($records) > 1 ? 's' : '') . "</span>\n";
        $output .= "</div>\n";
        $output .= "<div class='table-wrapper'>\n";
        $output .= "<table class='dns-table'>\n";
        $output .= "<thead><tr><th>Host</th><th>TTL</th><th>Valore</th><th>Info</th></tr></thead>\n";
        $output .= "<tbody>\n";
        
        foreach ($records as $record) {
            $host = isset($record['host']) ? htmlspecialchars($record['host']) : '-';
            $ttl = isset($record['ttl']) ? formatTTL($record['ttl']) : '-';
            $info = '';
            $row_class = 'dns-row';
            
            switch($type) {
                case 'A':
                case 'AAAA':
                    $value = isset($record['ip']) ? htmlspecialchars($record['ip']) : 
                            (isset($record['ipv6']) ? htmlspecialchars($record['ipv6']) : '-');
                    $info = '<span class="info-badge">IPv' . ($type == 'A' ? '4' : '6') . '</span>';
                    break;
                    
                case 'MX':
                    $value = isset($record['target']) ? htmlspecialchars($record['target']) : '-';
                    $info = '<span class="priority-badge">Priority: ' . $record['pri'] . '</span>';
                    
                    // Identifica servizi cloud
                    if (stripos($value, 'outlook.com') !== false || stripos($value, 'protection.outlook.com') !== false) {
                        $info .= ' <span class="info-badge microsoft">Microsoft 365</span>';
                        $row_class .= ' highlight-microsoft';
                    } elseif (stripos($value, 'google.com') !== false) {
                        $info .= ' <span class="info-badge google">Google Workspace</span>';
                        $row_class .= ' highlight-google';
                    }
                    break;
                    
                case 'TXT':
                    $txt_value = isset($record['txt']) ? $record['txt'] : '-';
                    $value = '<span class="txt-record">' . htmlspecialchars($txt_value) . '</span>';
                    
                    // Identifica tipo di record TXT
                    if (strpos($txt_value, 'v=spf1') !== false) {
                        $info = '<span class="info-badge spf">SPF</span>';
                        if (strpos($txt_value, 'protection.outlook.com') !== false) {
                            $info .= ' <span class="info-badge microsoft">Microsoft 365</span>';
                        } elseif (strpos($txt_value, '_spf.google.com') !== false) {
                            $info .= ' <span class="info-badge google">Google</span>';
                        }
                    } elseif (strpos($txt_value, 'v=DKIM1') !== false || strpos($txt_value, 'k=rsa') !== false) {
                        $info = '<span class="info-badge dkim">DKIM</span>';
                    } elseif (strpos($txt_value, 'v=DMARC1') !== false) {
                        $info = '<span class="info-badge dmarc">DMARC</span>';
                    } elseif (strpos($txt_value, 'MS=') !== false) {
                        $info = '<span class="info-badge microsoft">Microsoft Verification</span>';
                    } elseif (strpos($txt_value, 'google-site-verification=') !== false) {
                        $info = '<span class="info-badge google">Google Verification</span>';
                    } elseif (strpos($txt_value, 'facebook-domain-verification=') !== false) {
                        $info = '<span class="info-badge facebook">Facebook</span>';
                    }
                    break;
                    
                case 'NS':
                case 'CNAME':
                    $value = isset($record['target']) ? htmlspecialchars($record['target']) : '-';
                    
                    // Identifica CNAME specifici
                    if ($type == 'CNAME') {
                        if (stripos($value, 'outlook.com') !== false) {
                            $info = '<span class="info-badge microsoft">Microsoft 365</span>';
                        } elseif (stripos($value, 'google.com') !== false || stripos($value, 'googlehosted.com') !== false) {
                            $info = '<span class="info-badge google">Google</span>';
                        } elseif (stripos($host, 'autodiscover') !== false) {
                            $info = '<span class="info-badge">Autodiscover</span>';
                        } elseif (stripos($host, 'mail') !== false || stripos($host, 'webmail') !== false) {
                            $info = '<span class="info-badge">Email Service</span>';
                        }
                    }
                    break;
                    
                case 'SOA':
                    $value = isset($record['mname']) ? 
                        "<div class='soa-details'>" .
                        "<span class='soa-item'><strong>Primary NS:</strong> " . htmlspecialchars($record['mname']) . "</span>" .
                        "<span class='soa-item'><strong>Email:</strong> " . htmlspecialchars(str_replace('.', '@', $record['rname'])) . "</span>" .
                        "<span class='soa-item'><strong>Serial:</strong> " . $record['serial'] . "</span>" .
                        "<span class='soa-item'><strong>Refresh:</strong> " . formatTTL($record['refresh']) . "</span>" .
                        "<span class='soa-item'><strong>Retry:</strong> " . formatTTL($record['retry']) . "</span>" .
                        "<span class='soa-item'><strong>Expire:</strong> " . formatTTL($record['expire']) . "</span>" .
                        "</div>" : '-';
                    $info = '<span class="info-badge">Authority</span>';
                    break;
                    
                case 'SRV':
                    $value = isset($record['target']) ? 
                        htmlspecialchars($record['target']) . ':' . $record['port'] : '-';
                    $info = '<span class="priority-badge">Priority: ' . $record['pri'] . 
                            ', Weight: ' . $record['weight'] . '</span>';
                    
                    // Identifica servizi SRV comuni
                    if (stripos($host, '_sip') !== false) {
                        $info .= ' <span class="info-badge">SIP/VoIP</span>';
                    } elseif (stripos($host, '_xmpp') !== false) {
                        $info .= ' <span class="info-badge">XMPP/Jabber</span>';
                    }
                    break;
                    
                case 'CAA':
                    $value = isset($record['value']) ? htmlspecialchars($record['value']) : '-';
                    $info = '<span class="info-badge">Certificate Authority</span>';
                    break;
                    
                default:
                    $value = print_r($record, true);
            }
            
            $output .= "<tr class='{$row_class}'><td class='host-cell'>{$host}</td><td class='ttl-cell'>{$ttl}</td><td class='value-cell'>{$value}</td><td class='info-cell'>{$info}</td></tr>\n";
        }
        
        $output .= "</tbody></table></div></div>\n";
    }
    return $output;
}

// Funzione per formattare TTL in formato leggibile
function formatTTL($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return round($seconds / 60) . 'm';
    } elseif ($seconds < 86400) {
        return round($seconds / 3600) . 'h';
    } else {
        return round($seconds / 86400) . 'd';
    }
}

// Funzione migliorata per ottenere TUTTI i record DNS
function getAllDnsRecords($domain) {
    $results = array();
    $errors = array();
    
    // Prova prima con il dominio così com'è
    $domains_to_check = array($domain);
    
    // Aggiungi www se non presente
    if (strpos($domain, 'www.') !== 0) {
        $domains_to_check[] = 'www.' . $domain;
    }
    
    // Rimuovi www se presente per controllare il dominio root
    if (strpos($domain, 'www.') === 0) {
        $domains_to_check[] = substr($domain, 4);
    }
    
    foreach ($domains_to_check as $check_domain) {
        // Usa DNS_ALL per ottenere TUTTI i record possibili
        try {
            $all_records = @dns_get_record($check_domain, DNS_ALL);
            
            if ($all_records !== false && !empty($all_records)) {
                // Organizza i record per tipo
                foreach ($all_records as $record) {
                    if (isset($record['type'])) {
                        $type = $record['type'];
                        if (!isset($results[$type])) {
                            $results[$type] = array();
                        }
                        
                        // Evita duplicati
                        $is_duplicate = false;
                        foreach ($results[$type] as $existing) {
                            if (json_encode($existing) == json_encode($record)) {
                                $is_duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$is_duplicate) {
                            $results[$type][] = $record;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Errore nel recupero record per {$check_domain}: " . $e->getMessage();
        }
        
        // Prova anche record specifici per maggiore affidabilità
        $specific_types = array(
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            'NS' => DNS_NS,
            'SOA' => DNS_SOA,
            'SRV' => DNS_SRV,
            'CAA' => DNS_CAA
        );
        
        foreach ($specific_types as $type => $constant) {
            try {
                $records = @dns_get_record($check_domain, $constant);
                if ($records !== false && !empty($records)) {
                    if (!isset($results[$type])) {
                        $results[$type] = array();
                    }
                    
                    foreach ($records as $record) {
                        // Evita duplicati
                        $is_duplicate = false;
                        foreach ($results[$type] as $existing) {
                            if (json_encode($existing) == json_encode($record)) {
                                $is_duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$is_duplicate) {
                            $results[$type][] = $record;
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignora errori per tipi specifici
            }
        }
    }
    
    // Controlla anche sottodomini comuni per servizi
    $common_subdomains = array(
        'autodiscover', 'mail', 'webmail', 'smtp', 'imap', 'pop', 'ftp',
        'cpanel', 'webdisk', 'ns1', 'ns2', '_dmarc', '_domainkey'
    );
    
    foreach ($common_subdomains as $subdomain) {
        $check_domain = $subdomain . '.' . $domain;
        try {
            $records = @dns_get_record($check_domain, DNS_ALL);
            if ($records !== false && !empty($records)) {
                foreach ($records as $record) {
                    if (isset($record['type'])) {
                        $type = $record['type'];
                        if (!isset($results[$type])) {
                            $results[$type] = array();
                        }
                        
                        // Evita duplicati
                        $is_duplicate = false;
                        foreach ($results[$type] as $existing) {
                            if (json_encode($existing) == json_encode($record)) {
                                $is_duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$is_duplicate) {
                            $results[$type][] = $record;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignora errori per sottodomini
        }
    }
    
    // Ordina i risultati
    ksort($results);
    
    return array('records' => $results, 'errors' => $errors);
}

// Gestione del form
$domain = '';
$dns_results = null;
$error_message = '';
$response_time = 0;
$domain_health = null;
$whois_info = null;
$cloud_services = null;
$blacklist_results = null;
$debug_mode = isset($_GET['debug']) || isset($_POST['debug']) ? true : false; // Aggiungi ?debug alla URL per debug

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    
    // Validazione dominio
    if (empty($domain)) {
        $error_message = 'Inserisci un nome di dominio valido.';
    } else {
        // Pulizia del dominio (rimuove http://, https://, www.)
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = trim($domain, '/');
        
        // Verifica formato dominio base
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}$/i', $domain)) {
            $error_message = 'Il formato del dominio non è valido.';
        } else {
            // Misura tempo di risposta
            $response_time = measureDnsResponseTime($domain);
            
            // Recupera i record DNS
            $dns_results = getAllDnsRecords($domain);
            
            if (empty($dns_results['records'])) {
                $error_message = 'Nessun record DNS trovato per questo dominio o il dominio non esiste.';
            } else {
                // Identifica servizi cloud
                $cloud_services = identifyCloudServices($dns_results['records']);
                
                // Controlla blacklist
                $blacklist_results = checkBlacklists($domain);
                
                // Analizza la salute del dominio
                $domain_health = analyzeDomainHealth($dns_results['records'], $cloud_services, $blacklist_results);
                
                // Ottieni info whois
                $whois_info = getWhoisInfo($domain, $debug_mode);
                
                // Se il WHOIS non ha trovato molto, aggiungi informazioni dai DNS
                if ($whois_info['owner'] == 'Non disponibile' || $whois_info['owner'] == 'Informazioni protette') {
                    // Prova a dedurre qualcosa dai record MX
                    if (isset($cloud_services['microsoft365'])) {
                        $whois_info['owner'] = 'Dominio Microsoft 365 (dedotto)';
                    } elseif (isset($cloud_services['google_workspace'])) {
                        $whois_info['owner'] = 'Dominio Google Workspace (dedotto)';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>DNS Check Enterprise - G Tech Group</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #5d8ecf;
            --primary-dark: #4a7ab8;
            --primary-light: #7fa8db;
            --secondary: #264573;
            --secondary-light: #3a5a8f;
            --text-dark: #000222;
            --text-white: #FFFFFF;
            --gray-light: #f8fafc;
            --gray-medium: #e1e8ed;
            --gray-dark: #657786;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --purple: #8b5cf6;
            --microsoft: #00BCF2;
            --google: #4285F4;
            --facebook: #1877F2;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
            --shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.25);
            --radius: 20px;
            --radius-sm: 12px;
            --radius-xs: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f4f8;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated gradient background */
        .background-gradient {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.05;
            z-index: -2;
        }
        
        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            z-index: -1;
            animation: float 20s ease-in-out infinite;
        }
        
        .orb1 {
            width: 600px;
            height: 600px;
            background: var(--primary);
            top: -300px;
            right: -200px;
        }
        
        .orb2 {
            width: 400px;
            height: 400px;
            background: var(--secondary);
            bottom: -200px;
            left: -100px;
            animation-delay: 5s;
            animation-direction: reverse;
        }
        
        .orb3 {
            width: 300px;
            height: 300px;
            background: var(--purple);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translate(0, 0) scale(1) rotate(0deg); 
            }
            25% { 
                transform: translate(-30px, -30px) scale(1.1) rotate(90deg); 
            }
            50% { 
                transform: translate(30px, -60px) scale(0.9) rotate(180deg); 
            }
            75% { 
                transform: translate(-60px, 30px) scale(1.05) rotate(270deg); 
            }
        }
        
        /* Navigation */
        nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        nav.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        /* Hero Section */
        .hero {
            padding: 140px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(3rem, 8vw, 5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 5s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.6rem);
            color: var(--gray-dark);
            font-weight: 300;
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.3s forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Main container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Form Section */
        .form-section {
            margin-bottom: 60px;
        }
        
        .form-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: clamp(40px, 6vw, 60px);
            border-radius: var(--radius);
            box-shadow: var(--shadow-2xl);
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }
        
        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 50%, var(--purple) 100%);
            animation: gradient-shift 5s ease infinite;
            background-size: 200% 200%;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--secondary);
            font-size: 1.1rem;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            font-size: 24px;
            opacity: 0.6;
            z-index: 1;
        }
        
        .domain-input {
            width: 100%;
            padding: 20px 20px 20px 60px;
            border: 2px solid var(--gray-medium);
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 18px;
            font-family: 'Lato', sans-serif;
            transition: var(--transition);
            color: var(--text-dark);
        }
        
        .domain-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(93, 142, 207, 0.1);
            transform: translateY(-2px);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 50px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 18px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            width: 100%;
            margin-top: 20px;
            box-shadow: var(--shadow-lg);
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .submit-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 5px;
            line-height: 1.2;
        }
        
        .stat-label {
            color: var(--gray-dark);
            font-size: 1rem;
        }
        
        /* WHOIS Section */
        .whois-section {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 40px;
        }
        
        .whois-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-medium);
        }
        
        .whois-icon {
            font-size: 36px;
        }
        
        .whois-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            color: var(--secondary);
            flex: 1;
        }
        
        .whois-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .whois-item {
            padding: 20px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }
        
        .whois-label {
            font-size: 0.9rem;
            color: var(--gray-dark);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        
        .whois-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .whois-value.text-danger {
            color: var(--error);
        }
        
        .whois-value.text-warning {
            color: var(--warning);
        }
        
        .whois-value small {
            font-weight: 400;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .nameserver-item {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 2px;
        }
        
        .whois-notice {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .whois-notice p {
            margin: 0;
            color: var(--info);
        }
        
        /* Blacklist Section */
        .blacklist-section {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 40px;
        }
        
        .blacklist-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .blacklist-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .reputation-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .reputation-badge.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.2) 100%);
            color: var(--success);
            border: 2px solid var(--success);
        }
        
        .reputation-badge.info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%);
            color: var(--info);
            border: 2px solid var(--info);
        }
        
        .reputation-badge.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%);
            color: var(--warning);
            border: 2px solid var(--warning);
        }
        
        .reputation-badge.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.2) 100%);
            color: var(--error);
            border: 2px solid var(--error);
        }
        
        .reputation-badge.gray {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.1) 0%, rgba(107, 114, 128, 0.2) 100%);
            color: var(--gray-dark);
            border: 2px solid var(--gray-dark);
        }
        
        .blacklist-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .blacklist-stat {
            text-align: center;
            padding: 20px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
        }
        
        .blacklist-stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .blacklist-stat-label {
            color: var(--gray-dark);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .blacklist-results {
            margin-top: 30px;
        }
        
        .blacklist-issues {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .blacklist-issues h4 {
            color: var(--error);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .blacklist-item {
            background: white;
            padding: 15px;
            border-radius: var(--radius-xs);
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }
        
        .blacklist-item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .blacklist-item-ip {
            font-family: 'SF Mono', Monaco, monospace;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .blacklist-item-name {
            color: var(--gray-dark);
        }
        
        .blacklist-item-status {
            background: var(--error);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .blacklist-clean {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            color: var(--success);
        }
        
        .blacklist-clean h4 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .blacklist-ips {
            margin-top: 20px;
            padding: 20px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
        }
        
        .blacklist-ips h5 {
            font-family: 'Poppins', sans-serif;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        .ip-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.9rem;
            margin: 4px;
        }
        
        /* Cloud Services Section */
        .cloud-services {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            border-radius: var(--radius);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .cloud-services::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        .cloud-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .cloud-icon {
            font-size: 48px;
        }
        
        .cloud-content {
            position: relative;
            z-index: 1;
        }
        
        .cloud-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .cloud-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .cloud-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }
        
        .cloud-card:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }
        
        .cloud-service-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .service-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Domain Health */
        .health-section {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 40px;
        }
        
        .health-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .health-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            color: var(--secondary);
        }
        
        .health-score {
            position: relative;
            width: 120px;
            height: 120px;
        }
        
        .health-score-circle {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .health-score-bg {
            fill: none;
            stroke: var(--gray-medium);
            stroke-width: 8;
        }
        
        .health-score-progress {
            fill: none;
            stroke: var(--primary);
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }
        
        .health-score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .health-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .health-item {
            padding: 20px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
        }
        
        .health-item.issue {
            border-left-color: var(--error);
            background: rgba(239, 68, 68, 0.05);
        }
        
        .health-item.suggestion {
            border-left-color: var(--warning);
            background: rgba(245, 158, 11, 0.05);
        }
        
        .health-item.positive {
            border-left-color: var(--success);
            background: rgba(16, 185, 129, 0.05);
        }
        
        .health-item h4 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Results Section */
        .results-section {
            margin-top: 60px;
        }
        
        .results-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            border-radius: var(--radius) var(--radius) 0 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .results-header::before,
        .results-header::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
        }
        
        .results-header::before {
            width: 200px;
            height: 200px;
            top: -100px;
            right: -50px;
            animation: pulse 4s ease-in-out infinite;
        }
        
        .results-header::after {
            width: 150px;
            height: 150px;
            bottom: -75px;
            left: -30px;
            animation: pulse 4s ease-in-out infinite reverse;
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1); 
                opacity: 0.3; 
            }
            50% { 
                transform: scale(1.2); 
                opacity: 0.1; 
            }
        }
        
        .results-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .results-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .results-body {
            background: white;
            padding: 40px;
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: var(--shadow-xl);
        }
        
        /* DNS Records */
        .record-type {
            margin-bottom: 40px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .record-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-medium);
        }
        
        .record-icon {
            font-size: 32px;
        }
        
        .record-title {
            font-family: 'Poppins', sans-serif;
            color: var(--secondary);
            font-size: 1.6rem;
            margin: 0;
            flex: 1;
        }
        
        .record-count {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            background: white;
            border: 1px solid var(--gray-medium);
        }
        
        .dns-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }
        
        .dns-table th {
            background: var(--gray-light);
            color: var(--secondary);
            padding: 18px 20px;
            text-align: left;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--gray-medium);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .dns-table td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--gray-medium);
            vertical-align: top;
        }
        
        .dns-table tr:last-child td {
            border-bottom: none;
        }
        
        .dns-row {
            transition: var(--transition);
            position: relative;
        }
        
        .dns-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .dns-row:hover {
            background: var(--gray-light);
        }
        
        .dns-row:hover::before {
            transform: scaleY(1);
        }
        
        .dns-row.highlight-microsoft {
            background: rgba(0, 188, 242, 0.05);
        }
        
        .dns-row.highlight-google {
            background: rgba(66, 133, 244, 0.05);
        }
        
        /* Cell styles */
        .host-cell {
            font-weight: 600;
            color: var(--secondary);
            font-family: 'SF Mono', Monaco, monospace;
        }
        
        .ttl-cell {
            color: var(--gray-dark);
            font-family: 'SF Mono', Monaco, monospace;
            font-weight: 500;
        }
        
        .value-cell {
            word-break: break-word;
            max-width: 400px;
        }
        
        .info-cell {
            white-space: nowrap;
        }
        
        /* Badges */
        .info-badge,
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 2px;
        }
        
        .info-badge {
            background: var(--primary);
            color: white;
        }
        
        .info-badge.spf {
            background: var(--success);
        }
        
        .info-badge.dkim {
            background: var(--info);
        }
        
        .info-badge.dmarc {
            background: var(--purple);
        }
        
        .info-badge.microsoft {
            background: var(--microsoft);
        }
        
        .info-badge.google {
            background: var(--google);
        }
        
        .info-badge.facebook {
            background: var(--facebook);
        }
        
        .priority-badge {
            background: var(--gray-light);
            color: var(--secondary);
            border: 1px solid var(--gray-medium);
        }
        
        .txt-record {
            background: var(--gray-light);
            padding: 8px 16px;
            border-radius: var(--radius-xs);
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            display: inline-block;
            max-width: 100%;
            overflow-wrap: break-word;
            border: 1px solid var(--gray-medium);
        }
        
        .soa-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .soa-item {
            font-size: 14px;
            color: var(--gray-dark);
        }
        
        .soa-item strong {
            color: var(--secondary);
            font-weight: 600;
            margin-right: 8px;
        }
        
        /* Info Cards */
        .info-section {
            margin: 60px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }
        
        .info-card {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .info-card:hover::before {
            transform: scaleX(1);
        }
        
        .info-card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 20px;
        }
        
        .info-card h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .info-card p {
            color: var(--gray-dark);
            line-height: 1.8;
        }
        
        /* Tips Section */
        .tips-section {
            background: linear-gradient(135deg, var(--gray-light) 0%, white 100%);
            padding: 60px 20px;
            margin-top: 80px;
        }
        
        .tips-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .tips-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .tips-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(2rem, 5vw, 3rem);
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .tip-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .tip-number {
            position: absolute;
            top: -20px;
            right: 20px;
            font-family: 'Poppins', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            color: var(--gray-medium);
            opacity: 0.3;
        }
        
        .tip-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .tip-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .tip-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        /* Messages */
        .message {
            padding: 20px 24px;
            border-radius: var(--radius-sm);
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 16px;
            animation: slideIn 0.4s ease-out;
            box-shadow: var(--shadow-md);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .message-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        /* Footer */
        footer {
            background: var(--secondary);
            color: white;
            padding: 60px 20px 30px;
            margin-top: 100px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }
        
        .footer-section p,
        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            line-height: 1.8;
            transition: var(--transition);
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Loading state */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
        }
        
        .no-results-icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.3;
        }
        
        .no-results h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .no-results p {
            color: var(--gray-dark);
            font-size: 1.1rem;
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--secondary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero {
                padding: 120px 20px 60px;
            }
            
            .form-card {
                padding: 30px 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .health-header {
                flex-direction: column;
                text-align: center;
            }
            
            .info-grid,
            .tips-grid,
            .whois-grid,
            .cloud-grid {
                grid-template-columns: 1fr;
            }
            
            .dns-table {
                font-size: 14px;
            }
            
            .dns-table th,
            .dns-table td {
                padding: 12px 15px;
            }
            
            .results-header {
                padding: 30px 20px;
            }
            
            .results-body {
                padding: 30px 20px;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="background-gradient"></div>
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>
    
    <!-- Navigation -->
    <nav id="navbar">
        <div class="nav-container">
            <a href="#" class="logo">DNS Check Enterprise</a>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#features">Funzionalità</a>
                <a href="#tips">Consigli</a>
                <a href="#about">Chi Siamo</a>
            </div>
            <button class="mobile-menu-btn">☰</button>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>DNS Check Enterprise</h1>
            <p class="hero-subtitle">Analisi completa DNS con rilevamento servizi cloud e dati WHOIS</p>
        </div>
    </section>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Form Section -->
        <section class="form-section">
            <div class="form-card" data-aos="zoom-in">
                <form method="POST" action="" id="dnsForm">
                    <div class="form-group">
                        <label class="form-label" for="domain">Inserisci il dominio da analizzare</label>
                        <div class="input-group">
                            <span class="input-icon">🌐</span>
                            <input type="text" 
                                   id="domain" 
                                   name="domain" 
                                   class="domain-input"
                                   placeholder="esempio.com" 
                                   value="<?php echo htmlspecialchars($domain); ?>" 
                                   required
                                   autocomplete="off">
                        </div>
                    </div>
                    <?php if ($debug_mode): ?>
                    <input type="hidden" name="debug" value="1">
                    <?php endif; ?>
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <span>Avvia Analisi Completa</span>
                    </button>
                </form>
                
                <?php if ($error_message): ?>
                    <div class="message error">
                        <span class="message-icon">⚠️</span>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <?php if ($dns_results && !empty($dns_results['records'])): ?>
            
            <!-- Stats Section -->
            <section class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-value"><?php echo $response_time; ?><span style="font-size: 0.5em; font-weight: 400;">ms</span></div>
                    <div class="stat-label">Tempo di risposta DNS</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo array_sum(array_map('count', $dns_results['records'])); ?></div>
                    <div class="stat-label">Record DNS totali</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-value"><?php echo count($dns_results['records']); ?></div>
                    <div class="stat-label">Tipi di record trovati</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $whois_info['status']; ?></div>
                    <div class="stat-label">Stato dominio</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value" style="font-size: <?php echo ($whois_info['expires'] == 'Non disponibile' || $whois_info['expires'] == 'N/D') ? '1.8rem' : '1.2rem'; ?>;"><?php 
                        if ($whois_info['expires'] != 'Non disponibile' && $whois_info['expires'] != 'N/D') {
                            $parts = explode('/', $whois_info['expires']);
                            if (count($parts) == 3) {
                                echo $parts[0] . '/' . $parts[1] . '<br><span style="font-size: 0.7em; font-weight: 400;">' . $parts[2] . '</span>';
                            } else {
                                echo $whois_info['expires'];
                            }
                        } else {
                            echo 'Non disponibile';
                        }
                    ?></div>
                    <div class="stat-label">Scadenza dominio</div>
                </div>
                <?php if ($blacklist_results && isset($blacklist_results['reputation'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">🛡️</div>
                    <div class="stat-value"><?php echo $blacklist_results['reputation']['score']; ?><span style="font-size: 0.5em; font-weight: 400;">%</span></div>
                    <div class="stat-label">Reputazione</div>
                </div>
                <?php endif; ?>
            </section>
            
            <!-- WHOIS Information Section -->
            <?php if ($whois_info): ?>
            <section class="whois-section" data-aos="fade-up">
                <div class="whois-header">
                    <span class="whois-icon">👤</span>
                    <h2 class="whois-title">Informazioni Intestatario Dominio</h2>
                </div>
                
                <div class="whois-grid">
                    <div class="whois-item">
                        <div class="whois-label">Intestatario</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['owner']); ?></div>
                    </div>
                    <?php if (isset($whois_info['registrant_org']) && $whois_info['registrant_org'] != 'Non disponibile'): ?>
                    <div class="whois-item">
                        <div class="whois-label">Organizzazione</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['registrant_org']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="whois-item">
                        <div class="whois-label">Registrar</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['registrar']); ?></div>
                    </div>
                    <div class="whois-item">
                        <div class="whois-label">Data Registrazione</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['created']); ?></div>
                    </div>
                    <div class="whois-item">
                        <div class="whois-label">Data Scadenza</div>
                        <div class="whois-value <?php 
                            if ($whois_info['expires'] != 'Non disponibile') {
                                $exp_timestamp = strtotime(str_replace('/', '-', $whois_info['expires']));
                                $days_until = floor(($exp_timestamp - time()) / 86400);
                                if ($days_until < 30) echo 'text-danger';
                                elseif ($days_until < 90) echo 'text-warning';
                            }
                        ?>"><?php 
                            echo htmlspecialchars($whois_info['expires']);
                            if ($whois_info['expires'] != 'Non disponibile') {
                                $exp_timestamp = strtotime(str_replace('/', '-', $whois_info['expires']));
                                $days_until = floor(($exp_timestamp - time()) / 86400);
                                if ($days_until >= 0) {
                                    echo " <small>(" . $days_until . " giorni)</small>";
                                }
                            }
                        ?></div>
                    </div>
                    <div class="whois-item">
                        <div class="whois-label">Paese Registrante</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['registrant_country']); ?></div>
                    </div>
                    <div class="whois-item">
                        <div class="whois-label">Stato Dominio</div>
                        <div class="whois-value"><?php echo htmlspecialchars($whois_info['status']); ?></div>
                    </div>
                    <?php if (!empty($whois_info['nameservers'])): ?>
                    <div class="whois-item" style="grid-column: 1 / -1;">
                        <div class="whois-label">Nameservers Registrati</div>
                        <div class="whois-value">
                            <?php foreach ($whois_info['nameservers'] as $ns): ?>
                                <span class="nameserver-item"><?php echo htmlspecialchars($ns); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($whois_info['owner'] == 'Non disponibile' && $whois_info['registrar'] == 'Non disponibile'): ?>
                <div class="whois-notice">
                    <span class="message-icon">ℹ️</span>
                    <p>I dati WHOIS potrebbero non essere disponibili a causa di limitazioni del server o privacy GDPR. 
                    Per informazioni complete, consulta un servizio WHOIS dedicato.</p>
                </div>
                <?php endif; ?>
                
                <?php if ($debug_mode): ?>
                <div class="whois-notice" style="background: #f0f0f0; color: #333;">
                    <span class="message-icon">🔧</span>
                    <p><strong>Debug Mode:</strong> 
                    Socket: <?php echo function_exists('fsockopen') ? '✓' : '✗'; ?> | 
                    Shell: <?php echo (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) ? '✓' : '✗'; ?> | 
                    cURL: <?php echo function_exists('curl_init') ? '✓' : '✗'; ?>
                    </p>
                    <?php if (isset($whois_info['_debug'])): ?>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer;">Raw WHOIS Data (first 500 chars)</summary>
                        <pre style="background: white; padding: 10px; margin-top: 10px; font-size: 12px; overflow-x: auto;"><?php echo htmlspecialchars($whois_info['_debug']); ?></pre>
                    </details>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
            
            <!-- Blacklist Check Section -->
            <?php if ($blacklist_results && !empty($blacklist_results['ips_checked'])): ?>
            <section class="blacklist-section" data-aos="fade-up">
                <div class="blacklist-header">
                    <h2 class="blacklist-title">
                        <span>🛡️</span> Controllo Blacklist e Reputazione
                    </h2>
                    <div class="reputation-badge <?php echo $blacklist_results['reputation']['color']; ?>">
                        <span style="font-size: 1.5rem;"><?php echo $blacklist_results['reputation']['score']; ?>%</span>
                        <span><?php echo $blacklist_results['reputation']['status']; ?></span>
                    </div>
                </div>
                
                <div class="blacklist-stats">
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value"><?php echo count($blacklist_results['ips_checked']); ?></div>
                        <div class="blacklist-stat-label">IP Controllati</div>
                    </div>
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value"><?php echo count(array_unique(array_column($blacklist_results['clean'], 'blacklist'))); ?></div>
                        <div class="blacklist-stat-label">Blacklist Verificate</div>
                    </div>
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value" style="color: <?php echo $blacklist_results['listed'] > 0 ? 'var(--error)' : 'var(--success)'; ?>">
                            <?php echo $blacklist_results['listed']; ?>
                        </div>
                        <div class="blacklist-stat-label">Presenze in Blacklist</div>
                    </div>
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value"><?php echo $blacklist_results['checked']; ?></div>
                        <div class="blacklist-stat-label">Controlli Totali</div>
                    </div>
                </div>
                
                <div class="blacklist-results">
                    <?php if (!empty($blacklist_results['issues'])): ?>
                        <div class="blacklist-issues">
                            <h4><span>⚠️</span> IP Presenti in Blacklist</h4>
                            <?php foreach ($blacklist_results['issues'] as $issue): ?>
                                <div class="blacklist-item">
                                    <div class="blacklist-item-info">
                                        <span class="blacklist-item-ip"><?php echo htmlspecialchars($issue['ip']); ?></span>
                                        <span class="blacklist-item-name"><?php echo htmlspecialchars($issue['blacklist']); ?></span>
                                    </div>
                                    <span class="blacklist-item-status">Listato</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="blacklist-clean">
                            <h4><span>✅</span> Nessuna presenza in blacklist rilevata!</h4>
                            <p>Tutti gli IP del dominio hanno una reputazione pulita.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="blacklist-ips">
                        <h5>IP del dominio verificati:</h5>
                        <?php foreach ($blacklist_results['ips_checked'] as $ip): ?>
                            <span class="ip-badge"><?php echo htmlspecialchars($ip); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Cloud Services Detection -->
            <?php if (!empty($cloud_services)): ?>
            <section class="cloud-services" data-aos="fade-up">
                <div class="cloud-header">
                    <span class="cloud-icon">☁️</span>
                    <div class="cloud-content">
                        <h2 class="cloud-title">Servizi Cloud Rilevati</h2>
                        <p>Abbiamo identificato i seguenti servizi cloud configurati per questo dominio:</p>
                    </div>
                </div>
                
                <div class="cloud-grid">
                    <?php if (isset($cloud_services['microsoft365'])): ?>
                    <div class="cloud-card">
                        <div class="cloud-service-name">
                            <span>🏢</span> Microsoft 365
                            <span class="service-badge">Enterprise</span>
                        </div>
                        <p>Il dominio utilizza Microsoft 365 per email e servizi di produttività. Questo include Exchange Online, Teams, SharePoint e OneDrive.</p>
                        <p><strong>Dettagli:</strong> <?php echo htmlspecialchars($cloud_services['microsoft365']['details']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($cloud_services['google_workspace'])): ?>
                    <div class="cloud-card">
                        <div class="cloud-service-name">
                            <span>🔷</span> Google Workspace
                            <span class="service-badge">Business</span>
                        </div>
                        <p>Il dominio è configurato con Google Workspace per email e collaborazione. Include Gmail, Drive, Docs e Meet.</p>
                        <p><strong>Dettagli:</strong> <?php echo htmlspecialchars($cloud_services['google_workspace']['details']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Domain Health Section -->
            <?php if ($domain_health): ?>
            <section class="health-section" data-aos="fade-up">
                <div class="health-header">
                    <h2 class="health-title">🏥 Salute del Dominio</h2>
                    <div class="health-score">
                        <svg class="health-score-circle" viewBox="0 0 120 120">
                            <circle class="health-score-bg" cx="60" cy="60" r="54" />
                            <circle class="health-score-progress" 
                                    cx="60" cy="60" r="54"
                                    stroke-dasharray="339.292"
                                    stroke-dashoffset="<?php echo 339.292 - (339.292 * $domain_health['score'] / 100); ?>" />
                        </svg>
                        <div class="health-score-text"><?php echo $domain_health['score']; ?></div>
                    </div>
                </div>
                
                <div class="health-details">
                    <?php if (!empty($domain_health['positives'])): ?>
                        <?php foreach ($domain_health['positives'] as $positive): ?>
                        <div class="health-item positive">
                            <h4><?php echo $positive; ?></h4>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($domain_health['issues'])): ?>
                        <?php foreach ($domain_health['issues'] as $issue): ?>
                        <div class="health-item issue">
                            <h4><span>❌</span> <?php echo htmlspecialchars($issue); ?></h4>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($domain_health['suggestions'])): ?>
                        <?php foreach ($domain_health['suggestions'] as $suggestion): ?>
                        <div class="health-item suggestion">
                            <h4><span>💡</span> Suggerimento</h4>
                            <p><?php echo htmlspecialchars($suggestion); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Results Section -->
            <section class="results-section">
                <div class="results-header">
                    <h2>Risultati completi per <?php echo htmlspecialchars($domain); ?></h2>
                    <p>Analisi completata il <?php echo date('d/m/Y \a\l\l\e H:i:s'); ?></p>
                </div>
                
                <div class="results-body">
                    <?php
                    // Ordine preferito per la visualizzazione
                    $preferred_order = array('A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SOA', 'SRV', 'CAA');
                    
                    // Mostra prima i record nell'ordine preferito
                    foreach ($preferred_order as $type) {
                        if (isset($dns_results['records'][$type])) {
                            echo formatDnsRecord($type, $dns_results['records'][$type], $cloud_services);
                        }
                    }
                    
                    // Mostra eventuali altri tipi di record non nell'ordine preferito
                    foreach ($dns_results['records'] as $type => $records) {
                        if (!in_array($type, $preferred_order)) {
                            echo formatDnsRecord($type, $records, $cloud_services);
                        }
                    }
                    ?>
                    
                    <?php if (!empty($dns_results['errors'])): ?>
                        <div class="message error">
                            <span class="message-icon">⚠️</span>
                            <div>
                                <h4>Alcuni record potrebbero non essere stati recuperati:</h4>
                                <ul style="margin-top: 8px; padding-left: 20px;">
                                    <?php foreach ($dns_results['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        <?php elseif ($dns_results !== null): ?>
            <div class="no-results" data-aos="fade-up">
                <div class="no-results-icon">🔍</div>
                <h3>Nessun record DNS trovato</h3>
                <p>Verifica che il dominio sia corretto e attivo.</p>
            </div>
        <?php endif; ?>
        
        <!-- Info Cards Section -->
        <section class="info-section" id="features">
            <div class="info-grid">
                <div class="info-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card-icon">🔒</div>
                    <h3>Sicurezza DNS</h3>
                    <p>Verifica la presenza di record SPF, DKIM e DMARC per proteggere il tuo dominio da spoofing e phishing. Identifichiamo automaticamente le configurazioni di sicurezza per Microsoft 365 e Google Workspace.</p>
                </div>
                <div class="info-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card-icon">☁️</div>
                    <h3>Rilevamento Cloud</h3>
                    <p>Identifichiamo automaticamente se il dominio utilizza servizi cloud come Microsoft 365, Google Workspace, o altri provider enterprise. Questo ti aiuta a capire l'infrastruttura IT utilizzata.</p>
                </div>
                <div class="info-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card-icon">👤</div>
                    <h3>Dati WHOIS</h3>
                    <p>Accedi alle informazioni sull'intestatario del dominio, data di registrazione e scadenza. Questi dati sono essenziali per la due diligence e la verifica della proprietà del dominio.</p>
                </div>
                <div class="info-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="info-card-icon">🛡️</div>
                    <h3>Controllo Blacklist</h3>
                    <p>Verifichiamo la presenza degli IP del dominio in oltre 20 blacklist principali. Essenziale per garantire che le email vengano consegnate e il sito non sia bloccato.</p>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Tips Section -->
    <section class="tips-section" id="tips">
        <div class="tips-container">
            <div class="tips-header" data-aos="fade-up">
                <h2>7 Best Practices DNS Enterprise</h2>
                <p>Ottimizza la configurazione DNS per ambienti business</p>
            </div>
            
            <div class="tips-grid">
                <div class="tip-card" data-aos="fade-up" data-aos-delay="100">
                    <span class="tip-number">01</span>
                    <div class="tip-icon">🔄</div>
                    <h3 class="tip-title">Ridondanza Nameserver</h3>
                    <p>Utilizza almeno 2-4 nameserver distribuiti geograficamente per garantire alta disponibilità anche in caso di guasti.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="200">
                    <span class="tip-number">02</span>
                    <div class="tip-icon">📧</div>
                    <h3 class="tip-title">Email Authentication</h3>
                    <p>Implementa SPF, DKIM e DMARC per proteggere il tuo dominio. Essenziale per Microsoft 365 e Google Workspace.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="300">
                    <span class="tip-number">03</span>
                    <div class="tip-icon">🛡️</div>
                    <h3 class="tip-title">DNSSEC</h3>
                    <p>Implementa DNSSEC per proteggere il tuo dominio da attacchi di cache poisoning e garantire l'autenticità delle risposte DNS.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="400">
                    <span class="tip-number">04</span>
                    <div class="tip-icon">☁️</div>
                    <h3 class="tip-title">Cloud Integration</h3>
                    <p>Verifica regolarmente i record DNS richiesti dai tuoi servizi cloud. Microsoft e Google aggiornano spesso i requisiti.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="500">
                    <span class="tip-number">05</span>
                    <div class="tip-icon">🔐</div>
                    <h3 class="tip-title">CAA Records</h3>
                    <p>Usa record CAA per specificare quali Certificate Authority possono emettere certificati SSL per il tuo dominio.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="600">
                    <span class="tip-number">06</span>
                    <div class="tip-icon">📊</div>
                    <h3 class="tip-title">Monitoring</h3>
                    <p>Monitora costantemente i tuoi record DNS per rilevare modifiche non autorizzate o problemi di configurazione.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="700">
                    <span class="tip-number">07</span>
                    <div class="tip-icon">🛡️</div>
                    <h3 class="tip-title">Blacklist Monitoring</h3>
                    <p>Controlla regolarmente la presenza dei tuoi IP nelle blacklist. Una presenza può danneggiare la deliverability delle email e la reputazione online.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer id="about">
        <div class="footer-content">
            <div class="footer-section">
                <h3>G Tech Group</h3>
                <p>Leader nell'analisi enterprise DNS. Forniamo strumenti professionali per la gestione, il monitoraggio e l'ottimizzazione delle infrastrutture DNS aziendali.</p>
            </div>
            <div class="footer-section">
                <h3>Servizi Enterprise</h3>
                <p>
                    <a href="#">Analisi DNS Avanzata</a><br>
                    <a href="#">Rilevamento Servizi Cloud</a><br>
                    <a href="#">Monitoraggio WHOIS</a><br>
                    <a href="#">Consulenza DNS Enterprise</a>
                </p>
            </div>
            <div class="footer-section">
                <h3>Risorse</h3>
                <p>
                    <a href="#">Documentazione API</a><br>
                    <a href="#">Guide Microsoft 365</a><br>
                    <a href="#">Guide Google Workspace</a><br>
                    <a href="#">Best Practices DNS</a>
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> G Tech Group - DNS Check Enterprise v4.0</p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Form submission
        document.getElementById('dnsForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<span>Analisi in corso</span><span class="loading"></span>';
            btn.disabled = true;
        });
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 100;
                    const targetPosition = target.offsetTop - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Animate numbers
        function animateValue(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Animate stats on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statValue = entry.target.querySelector('.stat-value');
                    if (statValue && !statValue.classList.contains('animated')) {
                        statValue.classList.add('animated');
                        const finalValue = parseInt(statValue.textContent);
                        if (!isNaN(finalValue)) {
                            animateValue(statValue, 0, finalValue, 1000);
                        }
                    }
                }
            });
        });
        
        document.querySelectorAll('.stat-card').forEach(card => {
            observer.observe(card);
        });
        
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            // Implementazione menu mobile
            alert('Menu mobile in sviluppo!');
        });
    </script>
</body>
</html>
