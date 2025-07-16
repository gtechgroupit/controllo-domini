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
 * 
 * @param string $hostname Hostname
 * @return array Lista IP
 */
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
 * @return array Mappa server => nome
 */
function getBlacklistServers() {
    // Usa configurazione globale se disponibile
    if (isset($GLOBALS['dnsbl_servers'])) {
        return $GLOBALS['dnsbl_servers'];
    }
    
    // Lista di default delle principali blacklist
    return array(
        'zen.spamhaus.org' => 'Spamhaus ZEN',
        'bl.spamcop.net' => 'SpamCop',
        'b.barracudacentral.org' => 'Barracuda',
        'dnsbl.sorbs.net' => 'SORBS',
        'spam.dnsbl.sorbs.net' => 'SORBS Spam',
        'cbl.abuseat.org' => 'CBL Abuseat',
        'dnsbl-1.uceprotect.net' => 'UCEPROTECT L1',
        'dnsbl-2.uceprotect.net' => 'UCEPROTECT L2',
        'dnsbl-3.uceprotect.net' => 'UCEPROTECT L3',
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
        'spamguard.leadmon.net' => 'SpamGuard',
        'rbl.megarbl.net' => 'MegaRBL',
        'combined.abuse.ch' => 'Abuse.ch',
        'drone.abuse.ch' => 'Abuse.ch Drone',
        'httpbl.abuse.ch' => 'Abuse.ch HTTP',
        'spam.abuse.ch' => 'Abuse.ch Spam'
    );
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
    
    // Metodo 1: DNS lookup diretto
    $start_time = microtime(true);
    $response = @gethostbyname($query);
    $lookup_time = microtime(true) - $start_time;
    
    // Se il timeout è stato superato
    if ($lookup_time > $timeout) {
        $result['error'] = "Timeout checking {$name} for {$ip}";
        return $result;
    }
    
    // Se la risposta è diversa dalla query, l'IP è listato
    if ($response && $response != $query && filter_var($response, FILTER_VALIDATE_IP)) {
        $result['listed'] = true;
        $result['response'] = $response;
        
        // Interpreta la risposta
        $result['reason'] = interpretBlacklistResponse($response, $dnsbl);
    }
    
    // Metodo 2: Se il primo metodo fallisce, prova con dns_get_record
    if (!$result['listed'] && function_exists('dns_get_record')) {
        $dns_result = @dns_get_record($query, DNS_A);
        if ($dns_result && count($dns_result) > 0) {
            $result['listed'] = true;
            $result['response'] = $dns_result[0]['ip'];
            $result['reason'] = interpretBlacklistResponse($dns_result[0]['ip'], $dnsbl);
        }
    }
    
    return $result;
}

/**
 * Interpreta la risposta della blacklist
 * 
 * @param string $response Risposta IP
 * @param string $dnsbl Server DNSBL
 * @return string Motivo/categoria
 */
function interpretBlacklistResponse($response, $dnsbl) {
    // Mappe di risposta per blacklist comuni
    $response_maps = array(
        'zen.spamhaus.org' => array(
            '127.0.0.2' => 'SBL - Spammer',
            '127.0.0.3' => 'CSS - Spammer',
            '127.0.0.4' => 'XBL - Exploited/Compromised',
            '127.0.0.9' => 'PBL - Policy Block',
            '127.0.0.10' => 'PBL - ISP Maintained',
            '127.0.0.11' => 'PBL - Non-MTA IP'
        ),
        'bl.spamcop.net' => array(
            '127.0.0.2' => 'Listed for spam'
        ),
        'dnsbl.sorbs.net' => array(
            '127.0.0.2' => 'HTTP Proxy',
            '127.0.0.3' => 'SOCKS Proxy',
            '127.0.0.4' => 'Misc Proxy',
            '127.0.0.5' => 'SMTP Relay',
            '127.0.0.6' => 'Spam Source',
            '127.0.0.7' => 'Web Vulnerable',
            '127.0.0.8' => 'Block Reserved',
            '127.0.0.9' => 'Zombie Spam',
            '127.0.0.10' => 'Dynamic IP',
            '127.0.0.11' => 'Bad Config',
            '127.0.0.12' => 'No Mail Server'
        )
    );
    
    // Cerca interpretazione specifica
    if (isset($response_maps[$dnsbl]) && isset($response_maps[$dnsbl][$response])) {
        return $response_maps[$dnsbl][$response];
    }
    
    // Interpretazione generica basata sull'ultimo ottetto
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
                'percentage' => round(($stats['listings'] / $unique_blacklists) * 100, 1)
            );
        }
    }
    
    usort($most_listed_ips, function($a, $b) {
        return $b['listings'] - $a['listings'];
    });
    
    // Aggiungi alle statistiche
    $results['statistics']['total_ips'] = $total_ips;
    $results['statistics']['unique_blacklists'] = $unique_blacklists;
    $results['statistics']['ip_statistics'] = $ip_statistics;
    $results['statistics']['blacklist_statistics'] = $blacklist_statistics;
    $results['statistics']['most_listed_ips'] = array_slice($most_listed_ips, 0, 5);
    
    return $results;
}

/**
 * Calcola la reputazione basata sui risultati blacklist
 * 
 * @param array $results Risultati
 * @return array Risultati con reputazione
 */
function calculateReputation($results) {
    $total_checks = $results['statistics']['total_checks'];
    $total_listings = $results['statistics']['total_listings'];
    
    if ($total_checks == 0) {
        return $results;
    }
    
    // Calcola percentuale pulita
    $clean_percentage = (($total_checks - $total_listings) / $total_checks) * 100;
    $score = round($clean_percentage);
    
    // Determina rating e colore
    if ($score == 100) {
        $rating = 'Eccellente';
        $color = 'success';
        $description = 'Nessuna presenza in blacklist rilevata';
    } elseif ($score >= 95) {
        $rating = 'Ottima';
        $color = 'success';
        $description = 'Presenza minima in blacklist';
    } elseif ($score >= 90) {
        $rating = 'Buona';
        $color = 'info';
        $description = 'Alcune presenze in blacklist minori';
    } elseif ($score >= 80) {
        $rating = 'Discreta';
        $color = 'warning';
        $description = 'Presenza moderata in blacklist';
    } elseif ($score >= 70) {
        $rating = 'Sufficiente';
        $color = 'warning';
        $description = 'Diverse presenze in blacklist';
    } elseif ($score >= 50) {
        $rating = 'Scarsa';
        $color = 'error';
        $description = 'Molte presenze in blacklist';
    } else {
        $rating = 'Critica';
        $color = 'error';
        $description = 'Presenza critica in blacklist';
    }
    
    $results['reputation'] = array(
        'score' => $score,
        'rating' => $rating,
        'color' => $color,
        'description' => $description,
        'clean_percentage' => round($clean_percentage, 2),
        'listed_percentage' => round(100 - $clean_percentage, 2)
    );
    
    // Aggiungi raccomandazioni
    $results['recommendations'] = getBlacklistRecommendations($results);
    
    return $results;
}

/**
 * Genera raccomandazioni basate sui risultati
 * 
 * @param array $results Risultati blacklist
 * @return array Raccomandazioni
 */
function getBlacklistRecommendations($results) {
    $recommendations = array();
    
    if ($results['reputation']['score'] == 100) {
        $recommendations[] = array(
            'type' => 'success',
            'message' => 'Ottimo lavoro! Gli IP del dominio hanno una reputazione pulita.',
            'action' => 'Continua a monitorare regolarmente per mantenere questa reputazione.'
        );
        return $recommendations;
    }
    
    // Analizza i pattern di listing
    $listing_patterns = analyzeListingPatterns($results);
    
    // Raccomandazioni per tipo di blacklist
    if (isset($listing_patterns['spam']) && $listing_patterns['spam'] > 0) {
        $recommendations[] = array(
            'type' => 'error',
            'message' => 'IP listati in blacklist anti-spam.',
            'action' => 'Verifica che il server non stia inviando spam. Controlla log email e configurazione.'
        );
    }
    
    if (isset($listing_patterns['proxy']) && $listing_patterns['proxy'] > 0) {
        $recommendations[] = array(
            'type' => 'warning',
            'message' => 'IP identificati come proxy aperti.',
            'action' => 'Verifica la configurazione del server e chiudi eventuali proxy aperti.'
        );
    }
    
    if (isset($listing_patterns['dynamic']) && $listing_patterns['dynamic'] > 0) {
        $recommendations[] = array(
            'type' => 'info',
            'message' => 'IP identificati come dinamici/residenziali.',
            'action' => 'Per server email, considera l\'uso di IP statici business.'
        );
    }
    
    // Raccomandazioni per blacklist specifiche
    $critical_blacklists = array('Spamhaus', 'SpamCop', 'Barracuda');
    foreach ($results['listings'] as $listing) {
        foreach ($critical_blacklists as $critical) {
            if (stripos($listing['blacklist'], $critical) !== false) {
                $recommendations[] = array(
                    'type' => 'error',
                    'message' => "Presenza in {$critical} - blacklist ad alto impatto.",
                    'action' => "Priorità alta: richiedi rimozione da {$critical}. Visita il loro sito per la procedura."
                );
                break 2;
            }
        }
    }
    
    // Raccomandazione generale
    if ($results['reputation']['score'] < 90) {
        $recommendations[] = array(
            'type' => 'warning',
            'message' => 'La reputazione IP necessita di miglioramento.',
            'action' => 'Implementa SPF, DKIM e DMARC. Monitora i log per attività sospette.'
        );
    }
    
    // Link utili per delisting
    if (count($results['listings']) > 0) {
        $recommendations[] = array(
            'type' => 'info',
            'message' => 'Procedure di rimozione disponibili.',
            'action' => 'Visita i siti delle blacklist per richiedere la rimozione. Risolvi prima la causa del listing.'
        );
    }
    
    return $recommendations;
}

/**
 * Analizza i pattern di listing
 * 
 * @param array $results Risultati
 * @return array Pattern identificati
 */
function analyzeListingPatterns($results) {
    $patterns = array(
        'spam' => 0,
        'proxy' => 0,
        'dynamic' => 0,
        'compromised' => 0,
        'other' => 0
    );
    
    foreach ($results['listings'] as $listing) {
        $reason = strtolower($listing['reason']);
        
        if (strpos($reason, 'spam') !== false) {
            $patterns['spam']++;
        } elseif (strpos($reason, 'proxy') !== false || strpos($reason, 'relay') !== false) {
            $patterns['proxy']++;
        } elseif (strpos($reason, 'dynamic') !== false || strpos($reason, 'residential') !== false) {
            $patterns['dynamic']++;
        } elseif (strpos($reason, 'compromised') !== false || strpos($reason, 'exploited') !== false) {
            $patterns['compromised']++;
        } else {
            $patterns['other']++;
        }
    }
    
    return $patterns;
}

/**
 * Genera report blacklist per export
 * 
 * @param array $results Risultati blacklist
 * @return array Report formattato
 */
function generateBlacklistReport($results) {
    $report = array(
        'executive_summary' => array(
            'domain' => $results['domain'],
            'check_date' => $results['check_time'],
            'reputation_score' => $results['reputation']['score'],
            'reputation_rating' => $results['reputation']['rating'],
            'total_ips_checked' => $results['statistics']['total_ips'],
            'total_blacklists_checked' => $results['statistics']['unique_blacklists'],
            'total_listings' => $results['statistics']['total_listings']
        ),
        'ip_details' => array(),
        'blacklist_details' => array(),
        'recommendations' => $results['recommendations']
    );
    
    // Dettagli per IP
    foreach ($results['statistics']['ip_statistics'] as $ip => $stats) {
        $report['ip_details'][] = array(
            'ip' => $ip,
            'hostname' => $stats['source'],
            'listings' => $stats['listings'],
            'status' => $stats['listings'] == 0 ? 'Clean' : 'Listed',
            'blacklists' => $stats['blacklists']
        );
    }
    
    // Dettagli per blacklist
    foreach ($results['statistics']['blacklist_statistics'] as $bl_name => $count) {
        if ($count > 0) {
            $report['blacklist_details'][] = array(
                'blacklist' => $bl_name,
                'listings' => $count,
                'severity' => getBlacklistSeverity($bl_name)
            );
        }
    }
    
    return $report;
}

/**
 * Determina la severità di una blacklist
 * 
 * @param string $blacklist Nome blacklist
 * @return string Livello severità
 */
function getBlacklistSeverity($blacklist) {
    $high_severity = array('Spamhaus', 'SpamCop', 'Barracuda', 'SURBL', 'URIBL');
    $medium_severity = array('SORBS', 'CBL', 'PSBL', 'Manitu');
    
    foreach ($high_severity as $pattern) {
        if (stripos($blacklist, $pattern) !== false) {
            return 'High';
        }
    }
    
    foreach ($medium_severity as $pattern) {
        if (stripos($blacklist, $pattern) !== false) {
            return 'Medium';
        }
    }
    
    return 'Low';
}
?>
