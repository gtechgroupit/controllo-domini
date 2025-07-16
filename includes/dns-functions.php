<?php
/**
 * Funzioni per l'analisi DNS - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage DNS
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Ottiene tutti i record DNS per un dominio
 * 
 * @param string $domain Dominio da analizzare
 * @return array Array con record DNS e eventuali errori
 */
function getAllDnsRecords($domain) {
    $results = array();
    $errors = array();
    $stats = array(
        'total_records' => 0,
        'record_types' => array(),
        'analysis_time' => microtime(true)
    );
    
    // Lista dei domini da controllare
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
        // Usa DNS_ALL per ottenere tutti i record possibili
        try {
            $all_records = @dns_get_record($check_domain, DNS_ALL);
            
            if ($all_records !== false && !empty($all_records)) {
                foreach ($all_records as $record) {
                    if (isset($record['type'])) {
                        $type = $record['type'];
                        if (!isset($results[$type])) {
                            $results[$type] = array();
                            $stats['record_types'][] = $type;
                        }
                        
                        // Evita duplicati
                        if (!isDuplicateRecord($results[$type], $record)) {
                            $results[$type][] = $record;
                            $stats['total_records']++;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Errore nel recupero record per {$check_domain}: " . $e->getMessage();
        }
        
        // Prova anche record specifici per maggiore affidabilità
        $specific_types = getDnsRecordTypes();
        
        foreach ($specific_types as $type => $constant) {
            try {
                $records = @dns_get_record($check_domain, $constant);
                if ($records !== false && !empty($records)) {
                    if (!isset($results[$type])) {
                        $results[$type] = array();
                        if (!in_array($type, $stats['record_types'])) {
                            $stats['record_types'][] = $type;
                        }
                    }
                    
                    foreach ($records as $record) {
                        if (!isDuplicateRecord($results[$type], $record)) {
                            $results[$type][] = $record;
                            $stats['total_records']++;
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignora errori per tipi specifici non supportati
            }
        }
    }
    
    // Controlla sottodomini comuni per servizi
    $common_subdomains = getCommonSubdomains();
    
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
                            if (!in_array($type, $stats['record_types'])) {
                                $stats['record_types'][] = $type;
                            }
                        }
                        
                        if (!isDuplicateRecord($results[$type], $record)) {
                            $results[$type][] = $record;
                            $stats['total_records']++;
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
    
    // Calcola tempo di analisi
    $stats['analysis_time'] = round((microtime(true) - $stats['analysis_time']) * 1000, 2);
    
    return array(
        'records' => $results, 
        'errors' => $errors,
        'stats' => $stats
    );
}

/**
 * Verifica se un record è duplicato
 * 
 * @param array $existing Record esistenti
 * @param array $new Nuovo record
 * @return bool True se duplicato
 */
function isDuplicateRecord($existing, $new) {
    foreach ($existing as $record) {
        if (json_encode($record) == json_encode($new)) {
            return true;
        }
    }
    return false;
}

/**
 * Ottiene i tipi di record DNS supportati
 * 
 * @return array Mappa tipo => costante PHP
 */
function getDnsRecordTypes() {
    return array(
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
}

/**
 * Ottiene i sottodomini comuni da verificare
 * 
 * @return array Lista sottodomini
 */
function getCommonSubdomains() {
    return array(
        'autodiscover', 'mail', 'webmail', 'smtp', 'imap', 'pop', 'ftp',
        'cpanel', 'webdisk', 'ns1', 'ns2', '_dmarc', '_domainkey',
        'calendar', 'vpn', 'remote', 'portal', 'admin', 'api'
    );
}

/**
 * Analizza i record DNS per identificare configurazioni email
 * 
 * @param array $dns_results Risultati DNS
 * @return array Informazioni email configuration
 */
function analyzeEmailConfiguration($dns_results) {
    $email_config = array(
        'has_mx' => false,
        'mx_count' => 0,
        'mx_servers' => array(),
        'has_spf' => false,
        'spf_record' => null,
        'has_dkim' => false,
        'dkim_selectors' => array(),
        'has_dmarc' => false,
        'dmarc_policy' => null,
        'email_provider' => null,
        'recommendations' => array()
    );
    
    // Analizza record MX
    if (isset($dns_results['MX']) && !empty($dns_results['MX'])) {
        $email_config['has_mx'] = true;
        $email_config['mx_count'] = count($dns_results['MX']);
        
        foreach ($dns_results['MX'] as $mx) {
            $email_config['mx_servers'][] = array(
                'priority' => $mx['pri'],
                'server' => $mx['target'],
                'provider' => identifyEmailProvider($mx['target'])
            );
        }
        
        // Determina il provider principale
        $providers = array_column($email_config['mx_servers'], 'provider');
        $providers = array_filter($providers);
        if (!empty($providers)) {
            $email_config['email_provider'] = reset($providers);
        }
    } else {
        $email_config['recommendations'][] = 'Nessun record MX trovato. Configura i record MX per ricevere email.';
    }
    
    // Analizza record TXT per SPF, DKIM, DMARC
    if (isset($dns_results['TXT'])) {
        foreach ($dns_results['TXT'] as $txt) {
            $txt_value = $txt['txt'];
            
            // SPF
            if (strpos($txt_value, 'v=spf1') === 0) {
                $email_config['has_spf'] = true;
                $email_config['spf_record'] = $txt_value;
                
                // Analizza qualità SPF
                $spf_analysis = analyzeSPFRecord($txt_value);
                if (!empty($spf_analysis['warnings'])) {
                    $email_config['recommendations'] = array_merge(
                        $email_config['recommendations'], 
                        $spf_analysis['warnings']
                    );
                }
            }
            
            // DKIM
            if (strpos($txt_value, 'v=DKIM1') !== false || strpos($txt_value, 'k=rsa') !== false) {
                $email_config['has_dkim'] = true;
                // Estrai selector dal nome host
                if (preg_match('/^([^.]+)\._domainkey/', $txt['host'], $matches)) {
                    $email_config['dkim_selectors'][] = $matches[1];
                }
            }
            
            // DMARC
            if (strpos($txt_value, 'v=DMARC1') === 0) {
                $email_config['has_dmarc'] = true;
                $email_config['dmarc_policy'] = extractDMARCPolicy($txt_value);
            }
        }
    }
    
    // Raccomandazioni
    if (!$email_config['has_spf'] && $email_config['has_mx']) {
        $email_config['recommendations'][] = 'Record SPF non trovato. Aggiungi un record SPF per migliorare la deliverability.';
    }
    
    if (!$email_config['has_dkim'] && $email_config['has_mx']) {
        $email_config['recommendations'][] = 'DKIM non configurato. Implementa DKIM per autenticare le email.';
    }
    
    if (!$email_config['has_dmarc'] && $email_config['has_mx']) {
        $email_config['recommendations'][] = 'Record DMARC non trovato. Aggiungi DMARC per proteggere il dominio da spoofing.';
    }
    
    return $email_config;
}

/**
 * Identifica il provider email dal server MX
 * 
 * @param string $mx_server Server MX
 * @return string|null Provider identificato
 */
function identifyEmailProvider($mx_server) {
    $providers = array(
        'Microsoft 365' => array('outlook.com', 'mail.protection.outlook.com'),
        'Google Workspace' => array('google.com', 'googlemail.com'),
        'Zoho Mail' => array('zoho.com', 'zohomail.com'),
        'ProtonMail' => array('protonmail.ch'),
        'FastMail' => array('fastmail.com', 'messagingengine.com'),
        'Yandex' => array('yandex.ru', 'yandex.net'),
        'Mail.ru' => array('mail.ru'),
        'Amazon WorkMail' => array('awsapps.com'),
        'GoDaddy' => array('secureserver.net'),
        'Rackspace' => array('emailsrvr.com'),
        'OVH' => array('ovh.net'),
        'Aruba' => array('aruba.it', 'arubapec.it')
    );
    
    foreach ($providers as $provider => $patterns) {
        foreach ($patterns as $pattern) {
            if (stripos($mx_server, $pattern) !== false) {
                return $provider;
            }
        }
    }
    
    return null;
}

/**
 * Analizza un record SPF
 * 
 * @param string $spf_record Record SPF
 * @return array Analisi con warnings
 */
function analyzeSPFRecord($spf_record) {
    $analysis = array(
        'mechanisms' => array(),
        'modifiers' => array(),
        'warnings' => array()
    );
    
    // Controlla se termina con -all (recommended)
    if (!preg_match('/-all$/', $spf_record)) {
        if (preg_match('/~all$/', $spf_record)) {
            $analysis['warnings'][] = 'SPF usa ~all (softfail). Considera -all per una protezione più forte.';
        } elseif (preg_match('/\?all$/', $spf_record)) {
            $analysis['warnings'][] = 'SPF usa ?all (neutral). Fortemente consigliato usare -all.';
        } elseif (preg_match('/\+all$/', $spf_record)) {
            $analysis['warnings'][] = 'SPF usa +all (pass all). ATTENZIONE: questo rende SPF inefficace!';
        }
    }
    
    // Controlla numero di lookup DNS (limite 10)
    $lookup_mechanisms = array('include:', 'a:', 'mx:', 'ptr:', 'exists:', 'redirect=');
    $lookup_count = 0;
    foreach ($lookup_mechanisms as $mechanism) {
        $lookup_count += substr_count($spf_record, $mechanism);
    }
    
    if ($lookup_count > 10) {
        $analysis['warnings'][] = "SPF ha {$lookup_count} meccanismi che richiedono lookup DNS. Il limite è 10.";
    } elseif ($lookup_count > 7) {
        $analysis['warnings'][] = "SPF ha {$lookup_count} lookup DNS. Vicino al limite di 10.";
    }
    
    // Controlla lunghezza (limite 255 caratteri per singolo string)
    if (strlen($spf_record) > 255) {
        $analysis['warnings'][] = 'Record SPF troppo lungo. Considera di ottimizzarlo.';
    }
    
    return $analysis;
}

/**
 * Estrae la policy DMARC
 * 
 * @param string $dmarc_record Record DMARC
 * @return string Policy DMARC
 */
function extractDMARCPolicy($dmarc_record) {
    if (preg_match('/p=([^;]+)/', $dmarc_record, $matches)) {
        $policy = trim($matches[1]);
        switch ($policy) {
            case 'none':
                return 'Monitor Only';
            case 'quarantine':
                return 'Quarantine';
            case 'reject':
                return 'Reject';
            default:
                return $policy;
        }
    }
    return 'Unknown';
}

/**
 * Verifica la presenza di record di sicurezza
 * 
 * @param array $dns_results Risultati DNS
 * @return array Informazioni sicurezza
 */
function analyzeSecurityRecords($dns_results) {
    $security = array(
        'has_caa' => false,
        'caa_records' => array(),
        'has_tlsa' => false,
        'has_dnssec' => false,
        'security_score' => 0,
        'recommendations' => array()
    );
    
    // CAA Records
    if (isset($dns_results['CAA']) && !empty($dns_results['CAA'])) {
        $security['has_caa'] = true;
        $security['security_score'] += 20;
        
        foreach ($dns_results['CAA'] as $caa) {
            if (isset($caa['value'])) {
                $security['caa_records'][] = $caa['value'];
            }
        }
    } else {
        $security['recommendations'][] = 'Considera l\'aggiunta di record CAA per controllare quali CA possono emettere certificati.';
    }
    
    // DNSSEC (verifica indiretta)
    if (isset($dns_results['DNSKEY']) || isset($dns_results['DS']) || isset($dns_results['RRSIG'])) {
        $security['has_dnssec'] = true;
        $security['security_score'] += 30;
    }
    
    // TLSA Records (DANE)
    if (isset($dns_results['TLSA'])) {
        $security['has_tlsa'] = true;
        $security['security_score'] += 20;
    }
    
    return $security;
}

/**
 * Analizza performance DNS
 * 
 * @param array $dns_results Risultati DNS
 * @return array Metriche performance
 */
function analyzeDnsPerformance($dns_results) {
    $performance = array(
        'ttl_analysis' => array(),
        'ns_count' => 0,
        'ns_distribution' => array(),
        'recommendations' => array()
    );
    
    // Analizza TTL
    $ttl_values = array();
    foreach ($dns_results as $type => $records) {
        foreach ($records as $record) {
            if (isset($record['ttl'])) {
                $ttl_values[$type][] = $record['ttl'];
            }
        }
    }
    
    foreach ($ttl_values as $type => $ttls) {
        $avg_ttl = array_sum($ttls) / count($ttls);
        $performance['ttl_analysis'][$type] = array(
            'average' => $avg_ttl,
            'min' => min($ttls),
            'max' => max($ttls)
        );
        
        // Raccomandazioni TTL
        if ($type == 'A' && $avg_ttl < 300) {
            $performance['recommendations'][] = "TTL molto basso per record A (" . formatTTL($avg_ttl) . "). Considera di aumentarlo per ridurre il carico DNS.";
        }
        if ($type == 'MX' && $avg_ttl < 3600) {
            $performance['recommendations'][] = "TTL basso per record MX. I record MX cambiano raramente, considera un TTL più alto.";
        }
    }
    
    // Analizza nameserver
    if (isset($dns_results['NS'])) {
        $performance['ns_count'] = count($dns_results['NS']);
        
        if ($performance['ns_count'] < 2) {
            $performance['recommendations'][] = 'Solo ' . $performance['ns_count'] . ' nameserver configurato. Usa almeno 2 per ridondanza.';
        }
        
        // Verifica distribuzione geografica (basata sul nome)
        foreach ($dns_results['NS'] as $ns) {
            $ns_name = strtolower($ns['target']);
            if (preg_match('/ns[0-9]+\./', $ns_name)) {
                $performance['ns_distribution'][] = $ns_name;
            }
        }
    }
    
    return $performance;
}

/**
 * Genera report DNS in formato strutturato
 * 
 * @param array $dns_results Risultati DNS
 * @return array Report strutturato
 */
function generateDnsReport($dns_results) {
    $report = array(
        'summary' => array(
            'total_records' => 0,
            'record_types' => array(),
            'has_ipv6' => false,
            'has_email' => false,
            'has_security' => false
        ),
        'details' => array(),
        'score' => 0
    );
    
    // Conta record e tipi
    foreach ($dns_results as $type => $records) {
        $count = count($records);
        $report['summary']['total_records'] += $count;
        $report['summary']['record_types'][$type] = $count;
        
        // Verifica IPv6
        if ($type == 'AAAA' && $count > 0) {
            $report['summary']['has_ipv6'] = true;
            $report['score'] += 10;
        }
        
        // Verifica email
        if ($type == 'MX' && $count > 0) {
            $report['summary']['has_email'] = true;
            $report['score'] += 10;
        }
    }
    
    // Verifica sicurezza base
    if (isset($dns_results['TXT'])) {
        foreach ($dns_results['TXT'] as $txt) {
            if (strpos($txt['txt'], 'v=spf1') !== false) {
                $report['summary']['has_security'] = true;
                $report['score'] += 10;
                break;
            }
        }
    }
    
    return $report;
}

/**
 * Ottiene suggerimenti DNS personalizzati
 * 
 * @param array $dns_results Risultati DNS
 * @param string $domain Dominio analizzato
 * @return array Lista suggerimenti
 */
function getDnsSuggestions($dns_results, $domain) {
    $suggestions = array();
    
    // Suggerimenti base
    if (!isset($dns_results['A']) || empty($dns_results['A'])) {
        $suggestions[] = array(
            'type' => 'critical',
            'message' => 'Nessun record A trovato. Il dominio potrebbe non essere raggiungibile.',
            'action' => 'Aggiungi almeno un record A che punta all\'IP del tuo server web.'
        );
    }
    
    if (!isset($dns_results['MX']) || empty($dns_results['MX'])) {
        $suggestions[] = array(
            'type' => 'warning',
            'message' => 'Nessun record MX configurato.',
            'action' => 'Aggiungi record MX se vuoi ricevere email su questo dominio.'
        );
    }
    
    if (!isset($dns_results['AAAA']) || empty($dns_results['AAAA'])) {
        $suggestions[] = array(
            'type' => 'info',
            'message' => 'Nessun supporto IPv6 rilevato.',
            'action' => 'Considera l\'aggiunta di record AAAA per supportare IPv6.'
        );
    }
    
    // Suggerimenti avanzati
    $email_config = analyzeEmailConfiguration($dns_results);
    if ($email_config['has_mx'] && !$email_config['has_spf']) {
        $suggestions[] = array(
            'type' => 'warning',
            'message' => 'Email configurate ma SPF mancante.',
            'action' => 'Aggiungi un record TXT SPF per prevenire lo spoofing.'
        );
    }
    
    return $suggestions;
}
?>
