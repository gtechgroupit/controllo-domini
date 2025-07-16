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
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        }
        
        .stat-label {
            color: var(--gray-dark);
            font-size: 1rem;
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
        }
        
        .health-item.suggestion {
            border-left-color: var(--warning);
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            .tips-grid {
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
            <a href="#" class="logo">DNS Check Pro</a>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#features">Funzionalità</a>
                <a href="#tips">Consigli</a>
                <a href="#about">Chi Siamo</a>
                <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
            </div>
            <button class="mobile-menu-btn">☰</button>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>DNS Check Premium</h1>
            <p class="hero-subtitle">Analisi completa e professionale dei record DNS con insights avanzati</p>
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
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <span>Avvia Analisi DNS</span>
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
                    <div class="stat-value"><?php echo $response_time; ?>ms</div>
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
            </section>
            
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
                    <h2>Risultati per <?php echo htmlspecialchars($domain); ?></h2>
                    <p>Analisi completata il <?php echo date('d/m/Y \a\l\l\e H:i:s'); ?></p>
                </div>
                
                <div class="results-body">
                    <?php
                    foreach ($dns_results['records'] as $type => $records) {
                        echo formatDnsRecord($type, $records);
                    }
                    ?>
                    
                    <?php if (!empty($dns_results['errors'])): ?>
                        <div class="message error">
                            <span class="message-icon">⚠️</span>
                            <div>
                                <h4>Alcuni record non sono stati recuperati:</h4>
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
                    <p>Verifica la presenza di record SPF, DKIM e DMARC per proteggere il tuo dominio da spoofing e phishing. Questi record sono essenziali per la sicurezza delle email.</p>
                </div>
                <div class="info-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card-icon">⚡</div>
                    <h3>Performance</h3>
                    <p>Monitora i tempi di risposta DNS e ottimizza la configurazione dei tuoi nameserver per garantire prestazioni ottimali del tuo sito web.</p>
                </div>
                <div class="info-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card-icon">📧</div>
                    <h3>Email Delivery</h3>
                    <p>Assicurati che i record MX siano configurati correttamente per ricevere email. Verifica anche SPF e DKIM per migliorare la deliverability.</p>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Tips Section -->
    <section class="tips-section" id="tips">
        <div class="tips-container">
            <div class="tips-header" data-aos="fade-up">
                <h2>Best Practices DNS</h2>
                <p>Ottimizza la configurazione DNS del tuo dominio</p>
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
                    <div class="tip-icon">⏰</div>
                    <h3 class="tip-title">TTL Ottimizzato</h3>
                    <p>Imposta TTL appropriati: bassi (300-600s) durante le migrazioni, alti (3600-86400s) per configurazioni stabili.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="300">
                    <span class="tip-number">03</span>
                    <div class="tip-icon">🛡️</div>
                    <h3 class="tip-title">DNSSEC</h3>
                    <p>Implementa DNSSEC per proteggere il tuo dominio da attacchi di cache poisoning e garantire l'autenticità delle risposte DNS.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="400">
                    <span class="tip-number">04</span>
                    <div class="tip-icon">📝</div>
                    <h3 class="tip-title">Record SPF</h3>
                    <p>Configura un record SPF per specificare quali server sono autorizzati a inviare email per conto del tuo dominio.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="500">
                    <span class="tip-number">05</span>
                    <div class="tip-icon">🔐</div>
                    <h3 class="tip-title">CAA Records</h3>
                    <p>Usa record CAA per specificare quali Certificate Authority possono emettere certificati SSL per il tuo dominio.</p>
                </div>
                
                <div class="tip-card" data-aos="fade-up" data-aos-delay="600">
                    <span class="tip-number">06</span>
                    <div class="tip-icon">🚀</div>
                    <h3 class="tip-title">CDN Integration</h3>
                    <p>Considera l'uso di un CDN con Anycast DNS per migliorare le prestazioni globali e la resilienza del tuo sito.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer id="about">
        <div class="footer-content">
            <div class="footer-section">
                <h3>G Tech Group</h3>
                <p>Leader nell'analisi e ottimizzazione DNS. Forniamo strumenti professionali per la gestione e il monitoraggio delle infrastrutture DNS.</p>
            </div>
            <div class="footer-section">
                <h3>Servizi</h3>
                <p>
                    <a href="#">Analisi DNS</a><br>
                    <a href="#">Monitoraggio Real-time</a><br>
                    <a href="#">Consulenza DNS</a><br>
                    <a href="#">Migrazione Domini</a>
                </p>
            </div>
            <div class="footer-section">
                <h3>Risorse</h3>
                <p>
                    <a href="#">Documentazione</a><br>
                    <a href="#">Blog Tecnico</a><br>
                    <a href="#">Case Studies</a><br>
                    <a href="#">FAQ</a>
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> G Tech Group - DNS Check Premium v3.0</p>
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
        
        // Theme toggle (placeholder)
        function toggleTheme() {
            // Implementazione tema dark/light
            alert('Tema dark in arrivo nella prossima versione!');
        }
        
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
