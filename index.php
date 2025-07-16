<?php
/**
 * DNS Mapper Tool - Premium Edition
 * Advanced DNS analysis tool with stunning visual design
 * 
 * @author G Tech Group
 * @version 3.0
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

// Funzione per ottenere informazioni whois base
function getBasicWhoisInfo($domain) {
    $info = array(
        'registrar' => 'N/A',
        'created' => 'N/A',
        'expires' => 'N/A',
        'status' => 'Active'
    );
    
    // Qui potresti implementare una vera query whois
    // Per ora restituiamo dati simulati per demo
    return $info;
}

// Funzione per analizzare la salute del dominio
function analyzeDomainHealth($dns_results) {
    $health = array(
        'score' => 0,
        'issues' => array(),
        'suggestions' => array()
    );
    
    $max_score = 100;
    $current_score = 100;
    
    // Controlla presenza record A
    if (!isset($dns_results['A']) || empty($dns_results['A'])) {
        $current_score -= 20;
        $health['issues'][] = "Nessun record A trovato";
        $health['suggestions'][] = "Aggiungi almeno un record A per il tuo dominio";
    }
    
    // Controlla MX records per email
    if (!isset($dns_results['MX']) || empty($dns_results['MX'])) {
        $current_score -= 15;
        $health['issues'][] = "Nessun record MX configurato";
        $health['suggestions'][] = "Configura i record MX per ricevere email";
    }
    
    // Controlla NS records
    if (isset($dns_results['NS']) && count($dns_results['NS']) < 2) {
        $current_score -= 10;
        $health['issues'][] = "Solo " . count($dns_results['NS']) . " nameserver configurato";
        $health['suggestions'][] = "Usa almeno 2 nameserver per ridondanza";
    }
    
    // Controlla SPF record
    $has_spf = false;
    if (isset($dns_results['TXT'])) {
        foreach ($dns_results['TXT'] as $txt) {
            if (strpos($txt['txt'], 'v=spf1') !== false) {
                $has_spf = true;
                break;
            }
        }
    }
    if (!$has_spf) {
        $current_score -= 10;
        $health['issues'][] = "Nessun record SPF trovato";
        $health['suggestions'][] = "Aggiungi un record SPF per migliorare la deliverability email";
    }
    
    $health['score'] = max(0, $current_score);
    return $health;
}

// Funzione per formattare i risultati DNS con più dettagli
function formatDnsRecord($type, $records) {
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
            'SRV' => '🔧'
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
                    break;
                case 'TXT':
                    $txt_value = isset($record['txt']) ? $record['txt'] : '-';
                    $value = '<span class="txt-record">' . htmlspecialchars(substr($txt_value, 0, 100)) . 
                            (strlen($txt_value) > 100 ? '...' : '') . '</span>';
                    if (strpos($txt_value, 'v=spf1') !== false) {
                        $info = '<span class="info-badge spf">SPF</span>';
                    } elseif (strpos($txt_value, 'v=DKIM1') !== false) {
                        $info = '<span class="info-badge dkim">DKIM</span>';
                    } elseif (strpos($txt_value, 'v=DMARC1') !== false) {
                        $info = '<span class="info-badge dmarc">DMARC</span>';
                    }
                    break;
                case 'NS':
                case 'CNAME':
                    $value = isset($record['target']) ? htmlspecialchars($record['target']) : '-';
                    break;
                case 'SOA':
                    $value = isset($record['mname']) ? 
                        "<div class='soa-details'>" .
                        "<span class='soa-item'><strong>Primary NS:</strong> " . htmlspecialchars($record['mname']) . "</span>" .
                        "<span class='soa-item'><strong>Email:</strong> " . htmlspecialchars(str_replace('.', '@', $record['rname'])) . "</span>" .
                        "<span class='soa-item'><strong>Serial:</strong> " . $record['serial'] . "</span>" .
                        "</div>" : '-';
                    $info = '<span class="info-badge">Authority</span>';
                    break;
                case 'SRV':
                    $value = isset($record['target']) ? 
                        htmlspecialchars($record['target']) . ':' . $record['port'] : '-';
                    $info = '<span class="priority-badge">Priority: ' . $record['pri'] . 
                            ', Weight: ' . $record['weight'] . '</span>';
                    break;
                default:
                    $value = print_r($record, true);
            }
            
            $output .= "<tr class='dns-row'><td class='host-cell'>{$host}</td><td class='ttl-cell'>{$ttl}</td><td class='value-cell'>{$value}</td><td class='info-cell'>{$info}</td></tr>\n";
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

// Funzione principale per ottenere tutti i record DNS
function getAllDnsRecords($domain) {
    $results = array();
    $errors = array();
    
    // Lista dei tipi di record DNS da verificare
    $record_types = array(
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
    
    foreach ($record_types as $type => $constant) {
        try {
            $records = @dns_get_record($domain, $constant);
            if ($records !== false && !empty($records)) {
                $results[$type] = $records;
            }
        } catch (Exception $e) {
            $errors[] = "Errore nel recupero record {$type}: " . $e->getMessage();
        }
    }
    
    // Se non trova nulla, prova anche con www
    if (empty($results) && strpos($domain, 'www.') !== 0) {
        $www_domain = 'www.' . $domain;
        foreach ($record_types as $type => $constant) {
            try {
                $records = @dns_get_record($www_domain, $constant);
                if ($records !== false && !empty($records)) {
                    $results[$type] = $records;
                }
            } catch (Exception $e) {
                // Ignora errori per www
            }
        }
    }
    
    return array('records' => $results, 'errors' => $errors);
}

// Gestione del form
$domain = '';
$dns_results = null;
$error_message = '';
$response_time = 0;
$domain_health = null;
$whois_info = null;

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
                // Analizza la salute del dominio
                $domain_health = analyzeDomainHealth($dns_results['records']);
                
                // Ottieni info whois base
                $whois_info = getBasicWhoisInfo($domain);
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
    <title>DNS Check Premium - G Tech Group</title>
    
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
        
        .theme-toggle {
            background: var(--gray-light);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .theme-toggle:hover {
            background: var(--gray-medium);
            transform: rotate(180deg);
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
            100% { background-position: 0% 50%
