<?php
/**
 * Funzioni per il controllo Blacklist - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage Blacklist
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Controlla un dominio contro le principali blacklist
 * 
 * @param string $domain Dominio da verificare
 * @param array $options Opzioni di controllo
 * @return array Risultati del controllo blacklist
 */
function checkBlacklists($domain, $options = array()) {
    // Opzioni di default
    $defaults = array(
        'check_www' => true,
        'check_mail' => true,
        'check_all_ips' => false,
        'timeout' => 2,
        'parallel' => true
    );
    
    $options = array_merge($defaults, $options);
    
    // Inizializza risultati
    $results = array(
        'domain' => $domain,
        'check_time' => date('Y-m-d H:i:s'),
        'ips_checked' => array(),
        'blacklists_checked' => array(),
        'listings' => array(),
        'clean' => array(),
        'errors' => array(),
        'statistics' => array(
            'total_checks' => 0,
            'total_listings' => 0,
            'check_duration' => microtime(true)
        ),
        'reputation' => array(
            'score' => 0,
            'rating' => 'Unknown',
            'color' => 'gray'
        )
    );
    
    // Ottieni gli IP da controllare
    $ips_to_check = getIpsToCheck($domain, $options);
    
    if (empty($ips_to_check)) {
        $results['errors'][] = 'Nessun IP trovato per il dominio ' . $domain;
        return $results;
    }
    
    $results['ips_checked'] = $ips_to_check;
    
    // Ottieni lista blacklist
    $blacklists = getBlacklistServers();
    $results['blacklists_checked'] = array_values($blacklists);
    
    // Esegui controlli
    if ($options['parallel'] && function_exists('curl_multi_init')) {
        $results = checkBlacklistsParallel($ips_to_check, $blacklists, $results, $options);
    } else {
        $results = checkBlacklistsSequential($ips_to_check, $blacklists, $results, $options);
    }
    
    // Calcola statistiche e reputazione
    $results = calculateBlacklistStatistics($results);
    $results = calculateReputation($results);
    
    // Tempo totale di controllo
    $results['statistics']['check_duration'] = round((microtime(true) - $results['statistics']['check_duration']) * 1000, 2);
    
    return $results;
}

/**
 * Ottiene gli IP da controllare per un dominio
 * 
 * @param string $domain Dominio
 * @param array $options Opzioni
 * @return array Lista IP
 */
function getIpsToCheck($domain, $options) {
    $ips = array();
    
    // IP del dominio principale
    $main_ips = getIpAddresses($domain);
    foreach ($main_ips as $ip) {
        $ips[$ip] = $domain;
    }
    
    // IP di www.domain
    if ($options['check_www'] && strpos($domain, 'www.') !== 0) {
        $www_ips = getIpAddresses('www.' . $domain);
        foreach ($www_ips as $ip) {
            $ips[$ip] = 'www.' . $domain;
        }
    }
    
    // IP del mail server
    if ($options['check_mail']) {
        $mx_ips = getMxIpAddresses($domain);
        foreach ($mx_ips as $ip => $mx_host) {
            $ips[$ip] = $mx_host . ' (MX)';
        }
    }
    
    // Se richiesto, controlla tutti i sottodomini comuni
    if ($options['check_all_ips']) {
        $subdomains = array('mail', 'smtp', 'pop', 'imap', 'webmail', 'ftp');
        foreach ($subdomains as $sub) {
            $sub_ips = getIpAddresses($sub . '.' . $domain);
            foreach ($sub_ips as $ip) {
                $ips[$ip] = $sub . '.' . $domain;
            }
        }
    }
    
    return $ips;
}

/**
 * Ottiene gli indirizzi IP per un hostname
 * Usa function_exists per evitare ridichiarazione
 * 
 * @param string $hostname Hostname
 * @return array Lista IP
 */
if (!function_exists('getIpAddresses')) {
    function getIpAddresses($hostname) {
        $ips = array();
        
        // IPv4
        $a_records = @dns_get_record($hostname, DNS_A);
        if ($a_records) {
            foreach ($a_records as $record) {
                if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $record['ip'];
                }
            }
        }
        
        // IPv6 (opzionale - molte blacklist non supportano IPv6)
        /*
        $aaaa_records = @dns_get_record($hostname, DNS_AAAA);
        if ($aaaa_records) {
            foreach ($aaaa_records as $record) {
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
        */
        
        return array_unique($ips);
    }
}

/**
 * Ottiene gli IP dei server MX
 * 
 * @param string $domain Dominio
 * @return array Mappa IP => MX hostname
 */
function getMxIpAddresses($domain) {
    $mx_ips = array();
    
    $mx_records = @dns_get_record($domain, DNS_MX);
    if ($mx_records) {
        // Ordina per priorità
        usort($mx_records, function($a, $b) {
            return $a['pri'] - $b['pri'];
        });
        
        // Prendi solo i primi 3 MX
        $mx_records = array_slice($mx_records, 0, 3);
        
        foreach ($mx_records as $mx) {
            $ips = getIpAddresses($mx['target']);
            foreach ($ips as $ip) {
                $mx_ips[$ip] = $mx['target'];
            }
        }
    }
    
    return $mx_ips;
}

/**
 * Ottiene la lista dei server blacklist
 * 
 * @return array Mappa DNSBL => Nome
 */
function getBlacklistServers() {
    return array(
        // Blacklist principali
        'zen.spamhaus.org' => 'Spamhaus ZEN',
        'bl.spamcop.net' => 'SpamCop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS',
        'spam.dnsbl.sorbs.net' => 'SORBS Spam',
        'cbl.abuseat.org' => 'CBL Abuseat',
        'dnsbl-1.uceprotect.net' => 'UCEPROTECT L1',
        'psbl.surriel.com' => 'PSBL',
        'db.wpbl.info' => 'WPBL',
        'ix.dnsbl.manitu.net' => 'Manitu',
        'combined.rbl.msrbl.net' => 'MSRBL',
        'multi.surbl.org' => 'SURBL',
        'dsn.rfc-ignorant.org' => 'RFC-Ignorant DSN',
        'dul.dnsbl.sorbs.net' => 'SORBS DUL',
        'korea.services.net' => 'Korea Blocklist',
        'relays.bl.gweep.ca' => 'Gweep Relays',
        'residential.block.transip.nl' => 'TransIP Residential',
        'dynip.rothen.com' => 'Rothen Dynamic',
        'exitnodes.tor.dnsbl.sectoor.de' => 'TOR Exit Nodes',
        'ips.backscatterer.org' => 'Backscatterer',
        
        // Blacklist aggiuntive
        'bogons.cymru.com' => 'Cymru Bogons',
        'tor.dan.me.uk' => 'Dan TOR',
        'rbl.interserver.net' => 'InterServer',
        'query.senderbase.org' => 'SenderBase',
        'opm.tornevall.org' => 'Tornevall',
        'netblock.pedantic.org' => 'Pedantic',
        'access.redhawk.org' => 'RedHawk',
        'cdl.anti-spam.org.cn' => 'Anti-Spam China',
        'multi.uribl.com' => 'URIBL',
        'dnsbl.dronebl.org' => 'DroneBL',
        'truncate.gbudb.net' => 'GBUdb',
        'dnsbl.spfbl.net' => 'SPFBL',
    );
}

/**
 * Converte IP in formato reverse
 * Usa function_exists per evitare ridichiarazione se già definita in utilities.php
 * 
 * @param string $ip IP da convertire
 * @return string IP reverse
 */
if (!function_exists('reverseIP')) {
    function reverseIP($ip) {
        // Verifica se è IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return implode('.', array_reverse(explode('.', $ip)));
        }
        
        // Per IPv6 è più complesso
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Espandi l'indirizzo IPv6
            $hex = unpack("H*hex", inet_pton($ip));
            $hex = $hex['hex'];
            
            // Inverti e aggiungi punti
            $reversed = '';
            for ($i = strlen($hex) - 1; $i >= 0; $i--) {
                $reversed .= $hex[$i] . '.';
            }
            
            return rtrim($reversed, '.');
        }
        
        return $ip; // Ritorna originale se non valido
    }
}

/**
 * Controllo blacklist sequenziale
 * 
 * @param array $ips IP da controllare
 * @param array $blacklists Blacklist
 * @param array $results Risultati
 * @param array $options Opzioni
 * @return array Risultati aggiornati
 */
function checkBlacklistsSequential($ips, $blacklists, $results, $options) {
    foreach ($ips as $ip => $source) {
        $reverse_ip = reverseIP($ip);
        
        foreach ($blacklists as $dnsbl => $name) {
            $results['statistics']['total_checks']++;
            
            $check_result = checkSingleBlacklist($ip, $reverse_ip, $dnsbl, $name, $options['timeout']);
            
            if ($check_result['listed']) {
                $results['statistics']['total_listings']++;
                $results['listings'][] = array(
                    'ip' => $ip,
                    'source' => $source,
                    'blacklist' => $name,
                    'dnsbl' => $dnsbl,
                    'reason' => $check_result['reason'],
                    'response' => $check_result['response']
                );
            } else {
                $results['clean'][] = array(
                    'ip' => $ip,
                    'blacklist' => $name
                );
            }
            
            if ($check_result['error']) {
                $results['errors'][] = $check_result['error'];
            }
        }
    }
    
    return $results;
}

/**
 * Controllo blacklist parallelo usando cURL multi
 * 
 * @param array $ips IP da controllare
 * @param array $blacklists Blacklist
 * @param array $results Risultati
 * @param array $options Opzioni
 * @return array Risultati aggiornati
 */
function checkBlacklistsParallel($ips, $blacklists, $results, $options) {
    $multi_handle = curl_multi_init();
    $curl_handles = array();
    $dns_queries = array();
    
    // Prepara tutte le query DNS
    foreach ($ips as $ip => $source) {
        $reverse_ip = reverseIP($ip);
        
        foreach ($blacklists as $dnsbl => $name) {
            $query = $reverse_ip . '.' . $dnsbl;
            $dns_queries[] = array(
                'query' => $query,
                'ip' => $ip,
                'source' => $source,
                'dnsbl' => $dnsbl,
                'name' => $name
            );
        }
    }
    
    // Esegui query in batch
    $batch_size = 50;
    for ($i = 0; $i < count($dns_queries); $i += $batch_size) {
        $batch = array_slice($dns_queries, $i, $batch_size);
        
        foreach ($batch as $idx => $query_data) {
            $ch = curl_init();
            
            // Usa DoH (DNS over HTTPS) se disponibile
            $doh_url = 'https://1.1.1.1/dns-query?name=' . urlencode($query_data['query']) . '&type=A';
            
            curl_setopt_array($ch, array(
                CURLOPT_URL => $doh_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $options['timeout'],
                CURLOPT_HTTPHEADER => array('Accept: application/dns-json'),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ));
            
            curl_multi_add_handle($multi_handle, $ch);
            $curl_handles[$idx] = array('handle' => $ch, 'data' => $query_data);
        }
        
        // Esegui richieste parallele
        $running = null;
        do {
            curl_multi_exec($multi_handle, $running);
            curl_multi_select($multi_handle);
        } while ($running > 0);
        
        // Processa risultati
        foreach ($curl_handles as $idx => $handle_data) {
            $ch = $handle_data['handle'];
            $query_data = $handle_data['data'];
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $results['statistics']['total_checks']++;
            
            $listed = false;
            $reason = '';
            
            if ($response && $http_code == 200) {
                $json = json_decode($response, true);
                if (isset($json['Answer']) && count($json['Answer']) > 0) {
                    $listed = true;
                    $reason = 'Listed with response: ' . $json['Answer'][0]['data'];
                }
            }
            
            if ($listed) {
                $results['statistics']['total_listings']++;
                $results['listings'][] = array(
                    'ip' => $query_data['ip'],
                    'source' => $query_data['source'],
                    'blacklist' => $query_data['name'],
                    'dnsbl' => $query_data['dnsbl'],
                    'reason' => $reason
                );
            } else {
                $results['clean'][] = array(
                    'ip' => $query_data['ip'],
                    'blacklist' => $query_data['name']
                );
            }
            
            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }
        
        $curl_handles = array();
    }
    
    curl_multi_close($multi_handle);
    
    return $results;
}

/**
 * Controlla un singolo IP contro una blacklist
 * 
 * @param string $ip IP da controllare
 * @param string $reverse_ip IP reverse
 * @param string $dnsbl Server DNSBL
 * @param string $name Nome blacklist
 * @param int $timeout Timeout
 * @return array Risultato controllo
 */
function checkSingleBlacklist($ip, $reverse_ip, $dnsbl, $name, $timeout) {
    $result = array(
        'listed' => false,
        'reason' => '',
        'response' => '',
        'error' => null
    );
    
    $query = $reverse_ip . '.' . $dnsbl;
    
    // Timeout più basso per query DNS
    $old_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', $timeout);
    
    try {
        // Esegui query DNS
        $response = @gethostbyname($query);
        
        if ($response && $response != $query) {
            // L'IP è listato
            $result['listed'] = true;
            $result['response'] = $response;
            
            // Interpreta il codice di risposta
            $result['reason'] = interpretBlacklistResponse($response, $dnsbl);
        }
    } catch (Exception $e) {
        $result['error'] = "Errore nel controllo {$name}: " . $e->getMessage();
    }
    
    // Ripristina timeout
    ini_set('default_socket_timeout', $old_timeout);
    
    return $result;
}

/**
 * Interpreta la risposta della blacklist
 * 
 * @param string $response Risposta IP
 * @param string $dnsbl Server DNSBL
 * @return string Descrizione
 */
function interpretBlacklistResponse($response, $dnsbl) {
    // Interpretazioni specifiche per blacklist note
    if (strpos($dnsbl, 'spamhaus') !== false) {
        return interpretSpamhausResponse($response);
    }
    
    if (strpos($dnsbl, 'barracuda') !== false) {
        return 'Listed in Barracuda Reputation Block List';
    }
    
    if (strpos($dnsbl, 'spamcop') !== false) {
        return 'Listed in SpamCop Block List';
    }
    
    if (strpos($dnsbl, 'sorbs') !== false) {
        return interpretSorbsResponse($response);
    }
    
    // Default
    return 'Listed (Response: ' . $response . ')';
}

/**
 * Interpreta risposta Spamhaus
 * 
 * @param string $response Risposta
 * @return string Descrizione
 */
function interpretSpamhausResponse($response) {
    $parts = explode('.', $response);
    $code = end($parts);
    
    switch ($code) {
        case '2':
            return 'Listed in Spamhaus SBL (Spam source)';
        case '3':
            return 'Listed in Spamhaus CSS (Snowshoe spam)';
        case '4':
        case '5':
        case '6':
        case '7':
        case '8':
            return 'Listed in Spamhaus XBL (Exploited/Compromised)';
        case '9':
            return 'Listed in Spamhaus DROP/EDROP';
        case '10':
        case '11':
            return 'Listed in Spamhaus PBL (Policy Block)';
        default:
            return 'Listed in Spamhaus (Code: ' . $code . ')';
    }
}

/**
 * Interpreta risposta SORBS
 * 
 * @param string $response Risposta
 * @return string Descrizione
 */
function interpretSorbsResponse($response) {
    $parts = explode('.', $response);
    $last_octet = end($parts);
    
    switch ($last_octet) {
        case '2':
            return 'Listed (Spam Source)';
        case '3':
            return 'Listed (Proxy/Relay)';
        case '4':
            return 'Listed (Compromised)';
        case '9':
            return 'Listed (Dynamic/Residential)';
        default:
            return 'Listed (Code: ' . $response . ')';
    }
}

/**
 * Calcola statistiche blacklist
 * 
 * @param array $results Risultati
 * @return array Risultati con statistiche
 */
function calculateBlacklistStatistics($results) {
    $total_ips = count($results['ips_checked']);
    $unique_blacklists = count($results['blacklists_checked']);
    
    // Statistiche per IP
    $ip_statistics = array();
    foreach ($results['ips_checked'] as $ip => $source) {
        $ip_statistics[$ip] = array(
            'source' => $source,
            'listings' => 0,
            'blacklists' => array()
        );
    }
    
    // Conta listing per IP
    foreach ($results['listings'] as $listing) {
        $ip = $listing['ip'];
        if (isset($ip_statistics[$ip])) {
            $ip_statistics[$ip]['listings']++;
            $ip_statistics[$ip]['blacklists'][] = $listing['blacklist'];
        }
    }
    
    // Statistiche per blacklist
    $blacklist_statistics = array();
    foreach ($results['blacklists_checked'] as $bl_name) {
        $blacklist_statistics[$bl_name] = 0;
    }
    
    foreach ($results['listings'] as $listing) {
        $blacklist_statistics[$listing['blacklist']]++;
    }
    
    // IP più problematici
    $most_listed_ips = array();
    foreach ($ip_statistics as $ip => $stats) {
        if ($stats['listings'] > 0) {
            $most_listed_ips[] = array(
                'ip' => $ip,
                'source' => $stats['source'],
                'listings' => $stats['listings'],
                'percentage' => round(($stats['listings'] / $unique_blacklists) * 100, 1),
                'blacklists' => $stats['blacklists']
            );
        }
    }
    
    // Ordina per numero di listing
    usort($most_listed_ips, function($a, $b) {
        return $b['listings'] - $a['listings'];
    });
    
    // Blacklist più severe
    $strict_blacklists = array();
    foreach ($blacklist_statistics as $bl_name => $count) {
        if ($count > 0) {
            $strict_blacklists[] = array(
                'name' => $bl_name,
                'listings' => $count,
                'percentage' => round(($count / $total_ips) * 100, 1)
            );
        }
    }
    
    // Ordina per severità
    usort($strict_blacklists, function($a, $b) {
        return $b['listings'] - $a['listings'];
    });
    
    // Aggiungi statistiche
    $results['statistics']['total_ips'] = $total_ips;
    $results['statistics']['unique_blacklists'] = $unique_blacklists;
    $results['statistics']['most_listed_ips'] = array_slice($most_listed_ips, 0, 5);
    $results['statistics']['strict_blacklists'] = array_slice($strict_blacklists, 0, 10);
    $results['statistics']['listing_percentage'] = $total_ips > 0 ? 
        round(($results['statistics']['total_listings'] / ($total_ips * $unique_blacklists)) * 100, 2) : 0;
    
    return $results;
}

/**
 * Calcola score di reputazione
 * 
 * @param array $results Risultati blacklist
 * @return array Risultati con reputazione
 */
function calculateReputation($results) {
    $total_checks = $results['statistics']['total_checks'];
    $total_listings = $results['statistics']['total_listings'];
    
    if ($total_checks == 0) {
        $results['reputation']['score'] = 0;
        $results['reputation']['rating'] = 'Unknown';
        $results['reputation']['color'] = 'gray';
        return $results;
    }
    
    // Calcola percentuale di listing
    $listing_percentage = ($total_listings / $total_checks) * 100;
    
    // Score basato su percentuale inversa
    $score = 100 - $listing_percentage;
    $score = max(0, min(100, round($score)));
    
    // Determina rating e colore
    if ($score >= 95) {
        $rating = 'Excellent';
        $color = 'green';
    } elseif ($score >= 85) {
        $rating = 'Good';
        $color = 'lightgreen';
    } elseif ($score >= 70) {
        $rating = 'Fair';
        $color = 'yellow';
    } elseif ($score >= 50) {
        $rating = 'Poor';
        $color = 'orange';
    } else {
        $rating = 'Critical';
        $color = 'red';
    }
    
    // Aggiorna reputazione
    $results['reputation']['score'] = $score;
    $results['reputation']['rating'] = $rating;
    $results['reputation']['color'] = $color;
    $results['reputation']['listing_percentage'] = round($listing_percentage, 2);
    
    // Aggiungi raccomandazioni
    $results['reputation']['recommendations'] = getReputationRecommendations($score, $results);
    
    return $results;
}

/**
 * Genera raccomandazioni basate sulla reputazione
 * 
 * @param int $score Score reputazione
 * @param array $results Risultati completi
 * @return array Raccomandazioni
 */
function getReputationRecommendations($score, $results) {
    $recommendations = array();
    
    if ($score < 95 && $results['statistics']['total_listings'] > 0) {
        $recommendations[] = array(
            'type' => 'warning',
            'message' => 'Alcuni IP sono presenti in blacklist',
            'action' => 'Verifica la configurazione email e richiedi la rimozione dalle blacklist'
        );
    }
    
    if ($score < 70) {
        $recommendations[] = array(
            'type' => 'critical',
            'message' => 'Reputazione compromessa',
            'action' => 'Contatta urgentemente il tuo provider per risolvere i problemi di reputazione'
        );
    }
    
    // Raccomandazioni specifiche per tipo di blacklist
    foreach ($results['listings'] as $listing) {
        if (strpos($listing['blacklist'], 'Spamhaus') !== false) {
            $recommendations[] = array(
                'type' => 'info',
                'message' => 'Presente in Spamhaus',
                'action' => 'Visita www.spamhaus.org per richiedere la rimozione'
            );
            break;
        }
    }
    
    if ($score == 100) {
        $recommendations[] = array(
            'type' => 'success',
            'message' => 'Reputazione eccellente',
            'action' => 'Mantieni le buone pratiche di invio email'
        );
    }
    
    return array_unique($recommendations, SORT_REGULAR);
}

/**
 * Formatta risultati blacklist per output
 * 
 * @param array $results Risultati
 * @return array Risultati formattati
 */
function formatBlacklistResults($results) {
    $formatted = array(
        'summary' => array(
            'checked' => count($results['ips_checked']),
            'listed' => $results['statistics']['total_listings'],
            'clean' => $results['statistics']['total_checks'] - $results['statistics']['total_listings'],
            'reputation_score' => $results['reputation']['score'],
            'reputation_rating' => $results['reputation']['rating']
        ),
        'issues' => array(),
        'clean_ips' => array()
    );
    
    // Raggruppa per IP
    $ip_issues = array();
    foreach ($results['listings'] as $listing) {
        if (!isset($ip_issues[$listing['ip']])) {
            $ip_issues[$listing['ip']] = array(
                'ip' => $listing['ip'],
                'source' => $listing['source'],
                'blacklists' => array()
            );
        }
        $ip_issues[$listing['ip']]['blacklists'][] = $listing['blacklist'];
    }
    
    $formatted['issues'] = array_values($ip_issues);
    
    // IP puliti
    $clean_ips = array();
    foreach ($results['ips_checked'] as $ip => $source) {
        $is_clean = true;
        foreach ($results['listings'] as $listing) {
            if ($listing['ip'] == $ip) {
                $is_clean = false;
                break;
            }
        }
        if ($is_clean) {
            $clean_ips[] = $ip;
        }
    }
    $formatted['clean_ips'] = $clean_ips;
    
    return $formatted;
}

/**
 * Genera report blacklist
 * 
 * @param array $results Risultati blacklist
 * @return array Report
 */
function generateBlacklistReport($results) {
    $report = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'domain' => $results['domain'],
        'executive_summary' => '',
        'detailed_findings' => array(),
        'recommendations' => array(),
        'technical_details' => array()
    );
    
    // Executive Summary
    if ($results['reputation']['score'] >= 95) {
        $report['executive_summary'] = 'Il dominio ha un\'eccellente reputazione. Nessun problema rilevato.';
    } elseif ($results['reputation']['score'] >= 70) {
        $report['executive_summary'] = 'Il dominio ha una buona reputazione con alcuni problemi minori.';
    } else {
        $report['executive_summary'] = 'Il dominio ha seri problemi di reputazione che richiedono attenzione immediata.';
    }
    
    // Detailed Findings
    if (!empty($results['listings'])) {
        $report['detailed_findings'][] = array(
            'finding' => 'IP presenti in blacklist',
            'severity' => 'high',
            'details' => sprintf(
                '%d IP su %d totali sono presenti in una o più blacklist',
                count($results['statistics']['most_listed_ips']),
                count($results['ips_checked'])
            )
        );
    }
    
    // Recommendations
    if (!empty($results['reputation']['recommendations'])) {
        $report['recommendations'] = $results['reputation']['recommendations'];
    }
    
    // Technical Details
    $report['technical_details'] = array(
        'ips_checked' => array_keys($results['ips_checked']),
        'blacklists_checked' => $results['blacklists_checked'],
        'check_duration' => $results['statistics']['check_duration'] . 'ms',
        'listing_details' => $results['listings']
    );
    
    return $report;
}
?>
