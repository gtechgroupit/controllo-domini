<?php
/**
 * Funzioni per la scansione delle porte
 * 
 * @package ControlDomini
 * @subpackage Security
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Esegue la scansione delle porte comuni
 * 
 * @param string $domain Dominio da scansionare
 * @param array $custom_ports Porte personalizzate da scansionare (opzionale)
 * @return array Risultati della scansione
 */
function scanPorts($domain, $custom_ports = array()) {
    $results = array(
        'host' => $domain,
        'ip' => null,
        'scan_time' => date('Y-m-d H:i:s'),
        'duration' => 0,
        'open_ports' => array(),
        'closed_ports' => array(),
        'filtered_ports' => array(),
        'services' => array(),
        'vulnerabilities' => array(),
        'statistics' => array(
            'total_scanned' => 0,
            'open_count' => 0,
            'closed_count' => 0,
            'filtered_count' => 0
        )
    );
    
    $start_time = microtime(true);
    
    // Risolvi IP
    $ip = gethostbyname($domain);
    if ($ip === $domain) {
        $results['error'] = 'Impossibile risolvere il dominio';
        return $results;
    }
    
    $results['ip'] = $ip;
    
    // Definisci porte da scansionare
    $ports = !empty($custom_ports) ? $custom_ports : getCommonPorts();
    
    // Scansiona ogni porta
    foreach ($ports as $port => $service_info) {
        $port_result = checkPort($ip, $port, $service_info);
        
        $results['statistics']['total_scanned']++;
        
        switch ($port_result['status']) {
            case 'open':
                $results['open_ports'][] = $port_result;
                $results['services'][$port] = $port_result['service'];
                $results['statistics']['open_count']++;
                
                // Controlla vulnerabilità note per questa porta
                $vulns = checkPortVulnerabilities($port, $port_result);
                if (!empty($vulns)) {
                    $results['vulnerabilities'] = array_merge($results['vulnerabilities'], $vulns);
                }
                break;
                
            case 'closed':
                $results['closed_ports'][] = $port;
                $results['statistics']['closed_count']++;
                break;
                
            case 'filtered':
                $results['filtered_ports'][] = $port;
                $results['statistics']['filtered_count']++;
                break;
        }
    }
    
    // Analisi servizi rilevati
    $results['service_analysis'] = analyzeDetectedServices($results['services']);
    
    // Genera raccomandazioni
    $results['recommendations'] = generatePortScanRecommendations($results);
    
    // Calcola rischio
    $results['risk_assessment'] = assessPortRisk($results);
    
    $results['duration'] = round(microtime(true) - $start_time, 2);
    
    return $results;
}

/**
 * Ottiene la lista delle porte comuni da scansionare
 * 
 * @return array Porte e informazioni sui servizi
 */
function getCommonPorts() {
    return array(
        20 => array(
            'service' => 'FTP-DATA',
            'protocol' => 'tcp',
            'description' => 'FTP data transfer',
            'risk' => 'medium'
        ),
        21 => array(
            'service' => 'FTP',
            'protocol' => 'tcp',
            'description' => 'File Transfer Protocol',
            'risk' => 'high'
        ),
        22 => array(
            'service' => 'SSH',
            'protocol' => 'tcp',
            'description' => 'Secure Shell',
            'risk' => 'low'
        ),
        23 => array(
            'service' => 'Telnet',
            'protocol' => 'tcp',
            'description' => 'Telnet (insicuro)',
            'risk' => 'critical'
        ),
        25 => array(
            'service' => 'SMTP',
            'protocol' => 'tcp',
            'description' => 'Simple Mail Transfer Protocol',
            'risk' => 'medium'
        ),
        53 => array(
            'service' => 'DNS',
            'protocol' => 'tcp/udp',
            'description' => 'Domain Name System',
            'risk' => 'low'
        ),
        67 => array(
            'service' => 'DHCP',
            'protocol' => 'udp',
            'description' => 'DHCP Server',
            'risk' => 'medium'
        ),
        68 => array(
            'service' => 'DHCP',
            'protocol' => 'udp',
            'description' => 'DHCP Client',
            'risk' => 'low'
        ),
        69 => array(
            'service' => 'TFTP',
            'protocol' => 'udp',
            'description' => 'Trivial File Transfer Protocol',
            'risk' => 'high'
        ),
        80 => array(
            'service' => 'HTTP',
            'protocol' => 'tcp',
            'description' => 'Web Server',
            'risk' => 'medium'
        ),
        110 => array(
            'service' => 'POP3',
            'protocol' => 'tcp',
            'description' => 'Post Office Protocol v3',
            'risk' => 'medium'
        ),
        119 => array(
            'service' => 'NNTP',
            'protocol' => 'tcp',
            'description' => 'Network News Transfer Protocol',
            'risk' => 'low'
        ),
        123 => array(
            'service' => 'NTP',
            'protocol' => 'udp',
            'description' => 'Network Time Protocol',
            'risk' => 'low'
        ),
        135 => array(
            'service' => 'RPC',
            'protocol' => 'tcp',
            'description' => 'Microsoft RPC',
            'risk' => 'high'
        ),
        139 => array(
            'service' => 'NetBIOS',
            'protocol' => 'tcp',
            'description' => 'NetBIOS Session Service',
            'risk' => 'high'
        ),
        143 => array(
            'service' => 'IMAP',
            'protocol' => 'tcp',
            'description' => 'Internet Message Access Protocol',
            'risk' => 'medium'
        ),
        161 => array(
            'service' => 'SNMP',
            'protocol' => 'udp',
            'description' => 'Simple Network Management Protocol',
            'risk' => 'high'
        ),
        162 => array(
            'service' => 'SNMP-TRAP',
            'protocol' => 'udp',
            'description' => 'SNMP Trap',
            'risk' => 'medium'
        ),
        179 => array(
            'service' => 'BGP',
            'protocol' => 'tcp',
            'description' => 'Border Gateway Protocol',
            'risk' => 'medium'
        ),
        194 => array(
            'service' => 'IRC',
            'protocol' => 'tcp',
            'description' => 'Internet Relay Chat',
            'risk' => 'low'
        ),
        389 => array(
            'service' => 'LDAP',
            'protocol' => 'tcp',
            'description' => 'Lightweight Directory Access Protocol',
            'risk' => 'medium'
        ),
        443 => array(
            'service' => 'HTTPS',
            'protocol' => 'tcp',
            'description' => 'Secure Web Server',
            'risk' => 'low'
        ),
        445 => array(
            'service' => 'SMB',
            'protocol' => 'tcp',
            'description' => 'Server Message Block',
            'risk' => 'high'
        ),
        587 => array(
            'service' => 'SMTP-MSA',
            'protocol' => 'tcp',
            'description' => 'SMTP Mail Submission',
            'risk' => 'low'
        ),
        993 => array(
            'service' => 'IMAPS',
            'protocol' => 'tcp',
            'description' => 'IMAP over SSL',
            'risk' => 'low'
        ),
        995 => array(
            'service' => 'POP3S',
            'protocol' => 'tcp',
            'description' => 'POP3 over SSL',
            'risk' => 'low'
        ),
        1433 => array(
            'service' => 'MSSQL',
            'protocol' => 'tcp',
            'description' => 'Microsoft SQL Server',
            'risk' => 'high'
        ),
        1434 => array(
            'service' => 'MSSQL-UDP',
            'protocol' => 'udp',
            'description' => 'MSSQL Browser Service',
            'risk' => 'high'
        ),
        3000 => array(
            'service' => 'Dev-Server',
            'protocol' => 'tcp',
            'description' => 'Common development server port',
            'risk' => 'high'
        ),
        3306 => array(
            'service' => 'MySQL',
            'protocol' => 'tcp',
            'description' => 'MySQL Database',
            'risk' => 'high'
        ),
        3389 => array(
            'service' => 'RDP',
            'protocol' => 'tcp',
            'description' => 'Remote Desktop Protocol',
            'risk' => 'high'
        ),
        5060 => array(
            'service' => 'SIP',
            'protocol' => 'tcp/udp',
            'description' => 'Session Initiation Protocol',
            'risk' => 'medium'
        ),
        5432 => array(
            'service' => 'PostgreSQL',
            'protocol' => 'tcp',
            'description' => 'PostgreSQL Database',
            'risk' => 'high'
        ),
        5900 => array(
            'service' => 'VNC',
            'protocol' => 'tcp',
            'description' => 'Virtual Network Computing',
            'risk' => 'high'
        ),
        6379 => array(
            'service' => 'Redis',
            'protocol' => 'tcp',
            'description' => 'Redis Database',
            'risk' => 'high'
        ),
        8000 => array(
            'service' => 'HTTP-Alt',
            'protocol' => 'tcp',
            'description' => 'Alternative HTTP port',
            'risk' => 'medium'
        ),
        8080 => array(
            'service' => 'HTTP-Proxy',
            'protocol' => 'tcp',
            'description' => 'HTTP Proxy',
            'risk' => 'medium'
        ),
        8443 => array(
            'service' => 'HTTPS-Alt',
            'protocol' => 'tcp',
            'description' => 'Alternative HTTPS port',
            'risk' => 'low'
        ),
        8888 => array(
            'service' => 'HTTP-Alt2',
            'protocol' => 'tcp',
            'description' => 'Alternative HTTP port',
            'risk' => 'medium'
        ),
        9200 => array(
            'service' => 'Elasticsearch',
            'protocol' => 'tcp',
            'description' => 'Elasticsearch REST API',
            'risk' => 'high'
        ),
        11211 => array(
            'service' => 'Memcached',
            'protocol' => 'tcp',
            'description' => 'Memcached',
            'risk' => 'high'
        ),
        27017 => array(
            'service' => 'MongoDB',
            'protocol' => 'tcp',
            'description' => 'MongoDB Database',
            'risk' => 'high'
        )
    );
}

/**
 * Controlla se una porta è aperta
 * 
 * @param string $ip Indirizzo IP
 * @param int $port Numero porta
 * @param array $service_info Informazioni sul servizio
 * @return array Risultato del controllo
 */
function checkPort($ip, $port, $service_info) {
    $result = array(
        'port' => $port,
        'status' => 'unknown',
        'service' => $service_info,
        'banner' => null,
        'version' => null,
        'response_time' => null
    );
    
    $start_time = microtime(true);
    
    // Timeout basato sul tipo di servizio
    $timeout = in_array($port, array(80, 443, 8080, 8443)) ? 3 : 1;
    
    // Prova connessione TCP
    $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        $result['status'] = 'open';
        $result['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        // Prova a ottenere banner/versione per alcuni servizi
        stream_set_timeout($socket, 1);
        
        switch ($port) {
            case 21: // FTP
            case 25: // SMTP
            case 110: // POP3
            case 143: // IMAP
                $banner = fgets($socket, 1024);
                if ($banner) {
                    $result['banner'] = trim($banner);
                    $result['version'] = extractVersionFromBanner($banner);
                }
                break;
                
            case 22: // SSH
                $banner = fgets($socket, 1024);
                if ($banner && strpos($banner, 'SSH') !== false) {
                    $result['banner'] = trim($banner);
                    $result['version'] = extractVersionFromBanner($banner);
                }
                break;
                
            case 80: // HTTP
            case 8080:
            case 8000:
            case 8888:
                fwrite($socket, "HEAD / HTTP/1.0\r\n\r\n");
                $response = '';
                while (!feof($socket)) {
                    $response .= fgets($socket, 128);
                }
                if (preg_match('/Server:\s*(.+)/i', $response, $matches)) {
                    $result['banner'] = trim($matches[1]);
                    $result['version'] = extractVersionFromBanner($matches[1]);
                }
                break;
                
            case 443: // HTTPS
            case 8443:
                // Per HTTPS, otteniamo info dal certificato SSL
                fclose($socket);
                $cert_info = getSSLCertificateInfo($ip . ':' . $port);
                if ($cert_info) {
                    $result['banner'] = 'SSL/TLS';
                    $result['ssl_info'] = array(
                        'issuer' => $cert_info['issuer']['CN'],
                        'subject' => $cert_info['subject']['CN']
                    );
                }
                $socket = false;
                break;
        }
        
        if ($socket) {
            fclose($socket);
        }
    } else {
        // Distingui tra closed e filtered
        if ($errno === 111 || $errno === 61) { // Connection refused
            $result['status'] = 'closed';
        } elseif ($errno === 110 || $errno === 60) { // Timeout
            $result['status'] = 'filtered';
        } else {
            $result['status'] = 'closed';
        }
    }
    
    return $result;
}

/**
 * Estrae informazioni sulla versione dal banner
 * 
 * @param string $banner Banner del servizio
 * @return string|null Versione estratta
 */
function extractVersionFromBanner($banner) {
    // Pattern comuni per versioni
    $patterns = array(
        '/(\d+\.\d+\.\d+)/',
        '/v?(\d+\.\d+)/',
        '/(\d+\.\d+[a-zA-Z]\d+)/',
        '/(OpenSSH_[\d.]+)/',
        '/(Apache\/[\d.]+)/',
        '/(nginx\/[\d.]+)/',
        '/(Microsoft-IIS\/[\d.]+)/',
        '/(ProFTPD\s+[\d.]+)/',
        '/(vsftpd\s+[\d.]+)/'
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $banner, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Controlla vulnerabilità note per una porta
 * 
 * @param int $port Numero porta
 * @param array $port_result Risultato scansione porta
 * @return array Vulnerabilità trovate
 */
function checkPortVulnerabilities($port, $port_result) {
    $vulnerabilities = array();
    
    // Vulnerabilità generiche per porta
    $port_vulns = array(
        21 => array(
            'name' => 'FTP Clear Text',
            'severity' => 'high',
            'description' => 'FTP trasmette credenziali in chiaro',
            'solution' => 'Usa SFTP o FTPS invece di FTP'
        ),
        23 => array(
            'name' => 'Telnet Clear Text',
            'severity' => 'critical',
            'description' => 'Telnet trasmette tutto in chiaro incluse le password',
            'solution' => 'Disabilita Telnet e usa SSH'
        ),
        69 => array(
            'name' => 'TFTP No Authentication',
            'severity' => 'high',
            'description' => 'TFTP non richiede autenticazione',
            'solution' => 'Disabilita TFTP se non necessario'
        ),
        135 => array(
            'name' => 'RPC Exposure',
            'severity' => 'high',
            'description' => 'Microsoft RPC esposto può essere sfruttato',
            'solution' => 'Blocca la porta 135 dal firewall'
        ),
        139 => array(
            'name' => 'NetBIOS Exposure',
            'severity' => 'high',
            'description' => 'NetBIOS può esporre informazioni sensibili',
            'solution' => 'Disabilita NetBIOS o blocca dal firewall'
        ),
        445 => array(
            'name' => 'SMB Exposure',
            'severity' => 'high',
            'description' => 'SMB esposto a Internet è pericoloso',
            'solution' => 'Blocca SMB dal firewall esterno'
        ),
        1433 => array(
            'name' => 'MSSQL Exposure',
            'severity' => 'critical',
            'description' => 'Database SQL Server esposto',
            'solution' => 'Non esporre database a Internet'
        ),
        3306 => array(
            'name' => 'MySQL Exposure',
            'severity' => 'critical',
            'description' => 'Database MySQL esposto',
            'solution' => 'Non esporre database a Internet'
        ),
        3389 => array(
            'name' => 'RDP Exposure',
            'severity' => 'high',
            'description' => 'Remote Desktop esposto a attacchi brute force',
            'solution' => 'Usa VPN per accesso RDP'
        ),
        5432 => array(
            'name' => 'PostgreSQL Exposure',
            'severity' => 'critical',
            'description' => 'Database PostgreSQL esposto',
            'solution' => 'Non esporre database a Internet'
        ),
        5900 => array(
            'name' => 'VNC Exposure',
            'severity' => 'high',
            'description' => 'VNC può avere autenticazione debole',
            'solution' => 'Usa VPN per accesso VNC'
        ),
        6379 => array(
            'name' => 'Redis Exposure',
            'severity' => 'critical',
            'description' => 'Redis senza autenticazione è critico',
            'solution' => 'Configura autenticazione Redis'
        ),
        9200 => array(
            'name' => 'Elasticsearch Exposure',
            'severity' => 'critical',
            'description' => 'Elasticsearch può esporre dati sensibili',
            'solution' => 'Configura autenticazione e limita accesso'
        ),
        11211 => array(
            'name' => 'Memcached Exposure',
            'severity' => 'high',
            'description' => 'Memcached può essere usato per DDoS',
            'solution' => 'Non esporre Memcached a Internet'
        ),
        27017 => array(
            'name' => 'MongoDB Exposure',
            'severity' => 'critical',
            'description' => 'MongoDB senza auth è critico',
            'solution' => 'Configura autenticazione MongoDB'
        )
    );
    
    if (isset($port_vulns[$port])) {
        $vulnerabilities[] = $port_vulns[$port];
    }
    
    // Vulnerabilità specifiche per versione (se disponibile)
    if ($port_result['version']) {
        $version_vulns = checkVersionVulnerabilities(
            $port_result['service']['service'], 
            $port_result['version']
        );
        if (!empty($version_vulns)) {
            $vulnerabilities = array_merge($vulnerabilities, $version_vulns);
        }
    }
    
    return $vulnerabilities;
}

/**
 * Controlla vulnerabilità specifiche per versione
 * 
 * @param string $service Nome servizio
 * @param string $version Versione
 * @return array Vulnerabilità
 */
function checkVersionVulnerabilities($service, $version) {
    $vulnerabilities = array();
    
    // Database vulnerabilità note (semplificato)
    $known_vulns = array(
        'Apache' => array(
            '2.4.49' => array(
                'name' => 'Apache Path Traversal',
                'cve' => 'CVE-2021-41773',
                'severity' => 'critical'
            ),
            '2.4.50' => array(
                'name' => 'Apache Path Traversal',
                'cve' => 'CVE-2021-42013',
                'severity' => 'critical'
            )
        ),
        'nginx' => array(
            '1.3.9-1.4.0' => array(
                'name' => 'nginx DNS Resolver Vulnerability',
                'cve' => 'CVE-2021-23017',
                'severity' => 'high'
            )
        ),
        'OpenSSH' => array(
            '7.6' => array(
                'name' => 'OpenSSH User Enumeration',
                'cve' => 'CVE-2018-15473',
                'severity' => 'medium'
            )
        )
    );
    
    // Controlla vulnerabilità note
    foreach ($known_vulns as $vuln_service => $versions) {
        if (stripos($service, $vuln_service) !== false) {
            foreach ($versions as $vuln_version => $vuln_info) {
                if (version_compare($version, $vuln_version, '<=')) {
                    $vulnerabilities[] = array(
                        'name' => $vuln_info['name'],
                        'cve' => $vuln_info['cve'],
                        'severity' => $vuln_info['severity'],
                        'description' => "Versione vulnerabile: $version",
                        'solution' => "Aggiorna $service all'ultima versione"
                    );
                }
            }
        }
    }
    
    return $vulnerabilities;
}

/**
 * Analizza i servizi rilevati
 * 
 * @param array $services Servizi trovati
 * @return array Analisi
 */
function analyzeDetectedServices($services) {
    $analysis = array(
        'web_services' => array(),
        'mail_services' => array(),
        'database_services' => array(),
        'remote_services' => array(),
        'development_services' => array(),
        'other_services' => array()
    );
    
    foreach ($services as $port => $service) {
        $service_type = $service['service'];
        
        // Categorizza servizi
        if (in_array($port, array(80, 443, 8080, 8443, 8000, 8888))) {
            $analysis['web_services'][] = array('port' => $port, 'service' => $service_type);
        } elseif (in_array($port, array(25, 110, 143, 587, 993, 995))) {
            $analysis['mail_services'][] = array('port' => $port, 'service' => $service_type);
        } elseif (in_array($port, array(1433, 3306, 5432, 6379, 9200, 11211, 27017))) {
            $analysis['database_services'][] = array('port' => $port, 'service' => $service_type);
        } elseif (in_array($port, array(22, 23, 3389, 5900))) {
            $analysis['remote_services'][] = array('port' => $port, 'service' => $service_type);
        } elseif (in_array($port, array(3000))) {
            $analysis['development_services'][] = array('port' => $port, 'service' => $service_type);
        } else {
            $analysis['other_services'][] = array('port' => $port, 'service' => $service_type);
        }
    }
    
    return $analysis;
}

/**
 * Genera raccomandazioni basate sulla scansione
 * 
 * @param array $results Risultati scansione
 * @return array Raccomandazioni
 */
function generatePortScanRecommendations($results) {
    $recommendations = array();
    
    // Raccomandazioni per vulnerabilità critiche
    foreach ($results['vulnerabilities'] as $vuln) {
        if ($vuln['severity'] === 'critical') {
            $recommendations[] = array(
                'priority' => 'critical',
                'title' => $vuln['name'],
                'description' => $vuln['description'],
                'solution' => $vuln['solution']
            );
        }
    }
    
    // Database esposti
    if (!empty($results['service_analysis']['database_services'])) {
        $recommendations[] = array(
            'priority' => 'critical',
            'title' => 'Database esposti a Internet',
            'description' => 'Sono stati rilevati servizi database accessibili da Internet',
            'solution' => 'I database non dovrebbero mai essere esposti direttamente a Internet. Usa firewall e VPN.'
        );
    }
    
    // Servizi di sviluppo
    if (!empty($results['service_analysis']['development_services'])) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Servizi di sviluppo esposti',
            'description' => 'Porte tipiche di ambienti di sviluppo sono aperte',
            'solution' => 'Assicurati che non siano ambienti di sviluppo in produzione'
        );
    }
    
    // Servizi obsoleti
    $obsolete_services = array();
    foreach ($results['open_ports'] as $port_info) {
        if (in_array($port_info['port'], array(21, 23, 69))) {
            $obsolete_services[] = $port_info['service']['service'];
        }
    }
    
    if (!empty($obsolete_services)) {
        $recommendations[] = array(
            'priority' => 'high',
            'title' => 'Protocolli obsoleti in uso',
            'description' => 'Sono attivi protocolli considerati insicuri: ' . implode(', ', $obsolete_services),
            'solution' => 'Sostituisci con alternative sicure (SSH invece di Telnet, SFTP invece di FTP)'
        );
    }
    
    // Troppe porte aperte
    if ($results['statistics']['open_count'] > 10) {
        $recommendations[] = array(
            'priority' => 'medium',
            'title' => 'Molte porte aperte',
            'description' => 'Sono state trovate ' . $results['statistics']['open_count'] . ' porte aperte',
            'solution' => 'Riduci la superficie di attacco chiudendo le porte non necessarie'
        );
    }
    
    return $recommendations;
}

/**
 * Valuta il rischio basato sui risultati
 * 
 * @param array $results Risultati scansione
 * @return array Valutazione rischio
 */
function assessPortRisk($results) {
    $risk_score = 0;
    $risk_factors = array();
    
    // Fattori di rischio
    foreach ($results['open_ports'] as $port_info) {
        switch ($port_info['service']['risk']) {
            case 'critical':
                $risk_score += 25;
                $risk_factors[] = $port_info['service']['service'] . ' (critico)';
                break;
            case 'high':
                $risk_score += 15;
                $risk_factors[] = $port_info['service']['service'] . ' (alto)';
                break;
            case 'medium':
                $risk_score += 5;
                break;
        }
    }
    
    // Vulnerabilità
    foreach ($results['vulnerabilities'] as $vuln) {
        switch ($vuln['severity']) {
            case 'critical':
                $risk_score += 30;
                break;
            case 'high':
                $risk_score += 20;
                break;
            case 'medium':
                $risk_score += 10;
                break;
        }
    }
    
    // Normalizza score
    $risk_score = min(100, $risk_score);
    
    // Determina livello di rischio
    if ($risk_score >= 75) {
        $risk_level = 'Critico';
        $risk_color = '#dc3545';
    } elseif ($risk_score >= 50) {
        $risk_level = 'Alto';
        $risk_color = '#fd7e14';
    } elseif ($risk_score >= 25) {
        $risk_level = 'Medio';
        $risk_color = '#ffc107';
    } elseif ($risk_score >= 10) {
        $risk_level = 'Basso';
        $risk_color = '#28a745';
    } else {
        $risk_level = 'Minimo';
        $risk_color = '#20c997';
    }
    
    return array(
        'score' => $risk_score,
        'level' => $risk_level,
        'color' => $risk_color,
        'factors' => $risk_factors,
        'summary' => count($results['vulnerabilities']) . ' vulnerabilità, ' . 
                     $results['statistics']['open_count'] . ' porte aperte'
    );
}

/**
 * Scansione veloce delle porte principali
 * 
 * @param string $domain Dominio
 * @return array Risultati veloci
 */
function quickPortScan($domain) {
    // Solo porte web e mail principali per una scansione veloce
    $quick_ports = array(
        80 => array('service' => 'HTTP', 'protocol' => 'tcp', 'risk' => 'low'),
        443 => array('service' => 'HTTPS', 'protocol' => 'tcp', 'risk' => 'low'),
        25 => array('service' => 'SMTP', 'protocol' => 'tcp', 'risk' => 'medium'),
        22 => array('service' => 'SSH', 'protocol' => 'tcp', 'risk' => 'low'),
        21 => array('service' => 'FTP', 'protocol' => 'tcp', 'risk' => 'high')
    );
    
    return scanPorts($domain, $quick_ports);
}
