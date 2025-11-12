<?php
/**
 * Funzioni di utilità generale per Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Definisci costanti se non già definite
if (!defined('LOG_ENABLED')) {
    define('LOG_ENABLED', false);
}

if (!defined('RATE_LIMIT_ENABLED')) {
    define('RATE_LIMIT_ENABLED', true);
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.2.1');
}

if (!defined('SEO_TITLE')) {
    define('SEO_TITLE', 'Controllo Domini - Analisi DNS, WHOIS e Blacklist Gratuita');
}

if (!defined('SEO_DESCRIPTION')) {
    define('SEO_DESCRIPTION', 'Analizza gratuitamente qualsiasi dominio: verifica DNS, record MX, SPF, DKIM, DMARC, informazioni WHOIS, presenza in blacklist e servizi cloud come Microsoft 365 e Google Workspace.');
}

/**
 * Calcola il tempo di risposta DNS per un dominio
 * 
 * @param string $domain Il dominio da verificare
 * @return float Tempo di risposta in millisecondi
 */
function measureDnsResponseTime($domain) {
    $start = microtime(true);
    
    // Prova prima con A record
    $result = @dns_get_record($domain, DNS_A);
    
    // Se non trova A record, prova con ANY
    if (empty($result)) {
        @dns_get_record($domain, DNS_ANY);
    }
    
    $end = microtime(true);
    return round(($end - $start) * 1000, 2);
}

/**
 * Ottiene tutti gli indirizzi IP per un dominio
 *
 * @param string $domain Dominio
 * @param int $depth Profondità ricorsione (per prevenire infinite loop)
 * @param array $visited Domini visitati (per prevenire cicli circolari)
 * @return array Lista IP
 */
function getIpAddresses($domain, $depth = 0, $visited = array()) {
    // Limite massimo profondità CNAME (RFC suggerisce max 8)
    $max_depth = 10;

    // Prevenzione infinite loop
    if ($depth >= $max_depth) {
        error_log("CNAME depth limit reached for domain: $domain");
        return array();
    }

    // Prevenzione cicli circolari
    $domain_lower = strtolower(trim($domain));
    if (in_array($domain_lower, $visited)) {
        error_log("CNAME circular reference detected for domain: $domain");
        return array();
    }
    $visited[] = $domain_lower;

    $ips = array();

    // Ottieni record A (IPv4)
    $a_records = @dns_get_record($domain, DNS_A);
    if ($a_records) {
        foreach ($a_records as $record) {
            if (isset($record['ip']) && !in_array($record['ip'], $ips)) {
                $ips[] = $record['ip'];
            }
        }
    }

    // Ottieni record AAAA (IPv6)
    $aaaa_records = @dns_get_record($domain, DNS_AAAA);
    if ($aaaa_records) {
        foreach ($aaaa_records as $record) {
            if (isset($record['ipv6']) && !in_array($record['ipv6'], $ips)) {
                $ips[] = $record['ipv6'];
            }
        }
    }

    // Se non trova IP diretti, controlla CNAME
    if (empty($ips)) {
        $cname_records = @dns_get_record($domain, DNS_CNAME);
        if ($cname_records) {
            foreach ($cname_records as $record) {
                if (isset($record['target'])) {
                    // Ricorsione per seguire CNAME con depth tracking
                    $target_ips = getIpAddresses($record['target'], $depth + 1, $visited);
                    $ips = array_merge($ips, $target_ips);
                }
            }
        }
    }

    return array_unique($ips);
}

/**
 * Formatta il TTL in formato leggibile
 * 
 * @param int $seconds TTL in secondi
 * @return string TTL formattato (es: 1h, 30m, 45s)
 */
function formatTTL($seconds) {
    if (!is_numeric($seconds) || $seconds < 0) {
        return '0s';
    }
    
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return round($seconds / 60) . 'm';
    } elseif ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        $minutes = round(($seconds % 3600) / 60);
        return $minutes > 0 ? $hours . 'h ' . $minutes . 'm' : $hours . 'h';
    } else {
        $days = floor($seconds / 86400);
        $hours = round(($seconds % 86400) / 3600);
        return $hours > 0 ? $days . 'd ' . $hours . 'h' : $days . 'd';
    }
}

/**
 * Valida e pulisce un nome di dominio
 * 
 * @param string $domain Il dominio da validare
 * @return string|false Dominio pulito o false se non valido
 */
function validateDomain($domain) {
    if (empty($domain)) {
        return false;
    }
    
    // Rimuove spazi e caratteri invisibili
    $domain = trim($domain);
    $domain = preg_replace('/\s+/', '', $domain);
    
    // Rimuove protocolli e path
    $domain = preg_replace('#^[a-zA-Z]+://#', '', $domain); // Rimuove qualsiasi protocollo
    $domain = preg_replace('#^www\.#i', '', $domain); // Rimuove www.
    $domain = preg_replace('#/.*$#', '', $domain); // Rimuove tutto dopo il primo /
    $domain = preg_replace('#:[\d]+$#', '', $domain); // Rimuove porta
    
    // Converte in minuscolo
    $domain = strtolower($domain);
    
    // Gestisci IDN (domini internazionalizzati)
    if (isIDN($domain)) {
        $domain = idnToAscii($domain);
    }
    
    // Verifica formato dominio base
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $domain)) {
        return false;
    }
    
    // Verifica che ci sia almeno un punto
    if (strpos($domain, '.') === false) {
        return false;
    }
    
    // Verifica lunghezza totale (max 253 caratteri)
    if (strlen($domain) > 253) {
        return false;
    }
    
    // Verifica ogni label del dominio
    $labels = explode('.', $domain);
    
    // Deve avere almeno 2 labels (nome + TLD)
    if (count($labels) < 2) {
        return false;
    }
    
    foreach ($labels as $label) {
        // Ogni label deve essere tra 1 e 63 caratteri
        if (strlen($label) < 1 || strlen($label) > 63) {
            return false;
        }
        
        // Non può iniziare o finire con un trattino
        if ($label[0] === '-' || $label[strlen($label)-1] === '-') {
            return false;
        }
        
        // Deve contenere solo caratteri validi
        if (!preg_match('/^[a-z0-9-]+$/i', $label)) {
            return false;
        }
    }
    
    // Verifica che il TLD sia valido (almeno 2 caratteri, solo lettere)
    $tld = end($labels);
    if (!preg_match('/^[a-z]{2,}$/i', $tld)) {
        return false;
    }
    
    return $domain;
}

/**
 * Estrae il TLD da un dominio
 * 
 * @param string $domain Il dominio
 * @return string Il TLD
 */
function extractTLD($domain) {
    if (empty($domain)) {
        return '';
    }
    
    // Lista completa dei TLD di secondo livello
    $secondLevelTLDs = array(
        // UK
        '.co.uk', '.org.uk', '.me.uk', '.ac.uk', '.gov.uk', '.net.uk', 
        '.sch.uk', '.nhs.uk', '.police.uk', '.mod.uk',
        // Australia
        '.com.au', '.net.au', '.org.au', '.edu.au', '.gov.au', '.asn.au', '.id.au',
        // Nuova Zelanda
        '.co.nz', '.net.nz', '.org.nz', '.school.nz', '.govt.nz', '.ac.nz',
        // Giappone
        '.co.jp', '.or.jp', '.ne.jp', '.ac.jp', '.go.jp',
        // Brasile
        '.com.br', '.net.br', '.org.br', '.gov.br', '.edu.br',
        // Sud Africa
        '.co.za', '.net.za', '.org.za', '.gov.za', '.edu.za', '.ac.za',
        // India
        '.co.in', '.net.in', '.org.in', '.gov.in', '.ac.in',
        // Corea
        '.co.kr', '.or.kr', '.ne.kr', '.go.kr', '.ac.kr',
        // Cina
        '.com.cn', '.net.cn', '.org.cn', '.gov.cn', '.edu.cn', '.ac.cn',
        // Argentina
        '.com.ar', '.net.ar', '.org.ar', '.gov.ar', '.edu.ar',
        // Messico
        '.com.mx', '.net.mx', '.org.mx', '.gob.mx', '.edu.mx',
        // Russia
        '.com.ru', '.net.ru', '.org.ru', '.gov.ru', '.edu.ru',
        // Altri
        '.co.id', '.co.il', '.co.th', '.co.ve'
    );
    
    $domain = strtolower($domain);
    
    // Controlla TLD di secondo livello
    foreach ($secondLevelTLDs as $sld) {
        if (substr($domain, -strlen($sld)) === $sld) {
            return substr($sld, 1); // Rimuove il punto iniziale
        }
    }
    
    // TLD di primo livello
    $lastDot = strrpos($domain, '.');
    if ($lastDot !== false) {
        return substr($domain, $lastDot + 1);
    }
    
    return '';
}

/**
 * Genera un ID univoco per la sessione di analisi
 * 
 * @return string ID sessione
 */
function generateAnalysisId() {
    return 'analysis_' . uniqid() . '_' . time() . '_' . mt_rand(1000, 9999);
}

/**
 * Formatta una data in italiano
 * 
 * @param string $date Data da formattare
 * @param string $format Formato output (default: d/m/Y)
 * @return string Data formattata
 */
function formatDateIT($date, $format = 'd/m/Y') {
    if (empty($date) || in_array($date, array('Non disponibile', 'N/A', 'Unknown'))) {
        return 'Non disponibile';
    }
    
    // Array di formati da provare
    $formats = array(
        'Y-m-d H:i:s',
        'Y-m-d',
        'd/m/Y H:i:s',
        'd/m/Y',
        'd-m-Y',
        'd.m.Y',
        'm/d/Y',
        'Y.m.d',
        'Ymd'
    );
    
    $timestamp = false;
    
    // Prova con strtotime
    $timestamp = strtotime($date);
    
    // Se fallisce, prova con i formati specifici
    if (!$timestamp || $timestamp < 0) {
        foreach ($formats as $sourceFormat) {
            $dateObj = DateTime::createFromFormat($sourceFormat, $date);
            if ($dateObj) {
                $timestamp = $dateObj->getTimestamp();
                break;
            }
        }
    }
    
    // Se abbiamo un timestamp valido
    if ($timestamp && $timestamp > 0) {
        // Verifica che sia una data ragionevole
        $year = date('Y', $timestamp);
        if ($year >= 1990 && $year <= 2100) {
            return date($format, $timestamp);
        }
    }
    
    // Se tutto fallisce, ritorna la data originale
    return $date;
}

/**
 * Calcola i giorni rimanenti fino a una data
 * 
 * @param string $date Data target
 * @return int|false Giorni rimanenti o false se non calcolabile
 */
function daysUntil($date) {
    if (empty($date) || in_array($date, array('Non disponibile', 'N/A', 'Unknown'))) {
        return false;
    }
    
    // Prova diversi formati
    $timestamp = strtotime(str_replace('/', '-', $date));
    
    if (!$timestamp || $timestamp < 0) {
        // Prova con DateTime
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
    
    // Calcola differenza in giorni
    $days = floor(($timestamp - time()) / 86400);
    
    // Può essere negativo se già scaduto
    return $days;
}

/**
 * Calcola l'età del dominio in giorni
 * 
 * @param string $created_date Data creazione
 * @return int|false Età in giorni o false
 */
function calculateDomainAge($created_date) {
    if (empty($created_date) || in_array($created_date, array('Non disponibile', 'N/A', 'Unknown'))) {
        return false;
    }
    
    // Usa la stessa logica di daysUntil ma al contrario
    $timestamp = strtotime(str_replace('/', '-', $created_date));
    
    if (!$timestamp || $timestamp < 0) {
        $formats = array('d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y');
        foreach ($formats as $format) {
            $dateObj = DateTime::createFromFormat($format, $created_date);
            if ($dateObj) {
                $timestamp = $dateObj->getTimestamp();
                break;
            }
        }
    }
    
    if (!$timestamp || $timestamp < 0) {
        return false;
    }
    
    // Calcola età in giorni
    $age = floor((time() - $timestamp) / 86400);
    
    // Verifica che sia un valore ragionevole
    if ($age < 0 || $age > 15000) { // Max ~41 anni
        return false;
    }
    
    return $age;
}

/**
 * Genera classe CSS per stato scadenza
 * 
 * @param int $days Giorni alla scadenza
 * @return string Classe CSS
 */
function getExpirationClass($days) {
    if ($days === false || $days === null) {
        return '';
    }
    
    if ($days < 0) {
        return 'expired'; // Già scaduto
    } elseif ($days < 30) {
        return 'text-danger';
    } elseif ($days < 90) {
        return 'text-warning';
    } elseif ($days < 180) {
        return 'text-info';
    }
    
    return 'text-success';
}

/**
 * Converte un IP in formato reverse per query DNS
 * 
 * @param string $ip Indirizzo IP
 * @return string IP reverse
 */
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

/**
 * Ottiene l'IP reale del visitatore
 * 
 * @return string Indirizzo IP
 */
function getVisitorIP() {
    // Headers in ordine di priorità
    $ipKeys = array(
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_REAL_IP',            // Nginx proxy
        'HTTP_X_FORWARDED_FOR',      // Proxy standard
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Default
    );
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            
            foreach ($ips as $ip) {
                $ip = trim($ip);
                
                // Rimuovi porta se presente
                if (strpos($ip, ':') !== false && strpos($ip, '[') === false) {
                    list($ip, ) = explode(':', $ip);
                }
                
                // Valida IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    // Fallback
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Log delle analisi per statistiche
 * 
 * @param string $domain Dominio analizzato
 * @param array $results Risultati analisi
 * @return bool Success
 */
function logAnalysis($domain, $results = array()) {
    if (!defined('LOG_ENABLED') || !LOG_ENABLED) {
        return false;
    }
    
    $logData = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'domain' => $domain,
        'ip' => getVisitorIP(),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
        'dns_records' => isset($results['dns_count']) ? $results['dns_count'] : 0,
        'has_mx' => isset($results['has_mx']) ? $results['has_mx'] : false,
        'has_spf' => isset($results['has_spf']) ? $results['has_spf'] : false,
        'cloud_services' => isset($results['cloud_services']) ? $results['cloud_services'] : array(),
        'blacklisted' => isset($results['blacklisted']) ? $results['blacklisted'] : false
    );
    
    // Directory dei log
    $logDir = dirname(dirname(__FILE__)) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // File di log mensile
    $logFile = $logDir . '/analysis_' . date('Y_m') . '.log';
    
    // Scrivi il log
    $logLine = json_encode($logData) . PHP_EOL;
    
    return @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Genera meta description dinamica per SEO
 * 
 * @param string $domain Dominio analizzato
 * @return string Meta description
 */
function generateMetaDescription($domain = null) {
    if ($domain) {
        $domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        return "Analisi completa del dominio {$domain}: verifica DNS, record MX, SPF, DKIM, DMARC, informazioni WHOIS, presenza in blacklist. Strumento gratuito by Controllo Domini.";
    }
    
    return defined('SEO_DESCRIPTION') ? SEO_DESCRIPTION : 
           'Analizza gratuitamente qualsiasi dominio: verifica DNS, WHOIS, blacklist e servizi cloud.';
}

/**
 * Genera title tag dinamico per SEO
 * 
 * @param string $domain Dominio analizzato
 * @return string Title tag
 */
function generatePageTitle($domain = null) {
    if ($domain) {
        $domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        return "Analisi dominio {$domain} - DNS, WHOIS, Blacklist | Controllo Domini";
    }
    
    return defined('SEO_TITLE') ? SEO_TITLE : 
           'Controllo Domini - Analisi DNS, WHOIS e Blacklist Gratuita';
}

/**
 * Sanitizza output HTML
 * 
 * @param string $string Stringa da sanitizzare
 * @return string Stringa sanitizzata
 */
function sanitizeOutput($string) {
    if (is_array($string)) {
        return array_map('sanitizeOutput', $string);
    }
    
    if (!is_string($string)) {
        return $string;
    }
    
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Formatta dimensione file
 * 
 * @param int $bytes Dimensione in bytes
 * @param int $precision Precisione decimale
 * @return string Dimensione formattata
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Genera URL SEO-friendly (slug)
 * 
 * @param string $string Stringa da convertire
 * @return string URL SEO-friendly
 */
function generateSlug($string) {
    // Converti in minuscolo
    $string = mb_strtolower($string, 'UTF-8');
    
    // Sostituisci caratteri accentati
    $charMap = array(
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'ñ' => 'n', 'ç' => 'c',
        'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss'
    );
    
    $string = strtr($string, $charMap);
    
    // Rimuovi caratteri non alfanumerici
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    
    // Sostituisci spazi e underscore con trattini
    $string = preg_replace('/[\s_]+/', '-', $string);
    
    // Rimuovi trattini multipli
    $string = preg_replace('/-+/', '-', $string);
    
    // Rimuovi trattini all'inizio e alla fine
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Controlla se il dominio è un IDN (Internationalized Domain Name)
 * 
 * @param string $domain Dominio da verificare
 * @return bool True se IDN
 */
function isIDN($domain) {
    // Controlla presenza di caratteri non-ASCII
    return preg_match('/[^\x00-\x7F]/', $domain) || 
           strpos($domain, 'xn--') !== false;
}

/**
 * Converte IDN in formato ASCII (Punycode)
 * 
 * @param string $domain Dominio IDN
 * @return string Dominio in Punycode
 */
function idnToAscii($domain) {
    // Se la funzione PHP è disponibile
    if (function_exists('idn_to_ascii')) {
        // Prova prima con le costanti più recenti
        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        } else {
            // Fallback per versioni PHP più vecchie
            $ascii = @idn_to_ascii($domain);
        }
        
        return ($ascii !== false) ? $ascii : $domain;
    }
    
    // Se già in formato Punycode
    if (strpos($domain, 'xn--') !== false) {
        return $domain;
    }
    
    // Semplice implementazione di fallback
    // In produzione, usare una libreria completa
    return $domain;
}

/**
 * Converte Punycode in IDN
 * 
 * @param string $domain Dominio in Punycode
 * @return string Dominio IDN
 */
function idnToUtf8($domain) {
    if (function_exists('idn_to_utf8')) {
        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            $utf8 = idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        } else {
            $utf8 = @idn_to_utf8($domain);
        }
        
        return ($utf8 !== false) ? $utf8 : $domain;
    }
    
    return $domain;
}

/**
 * Ottiene informazioni sul browser del visitatore
 * 
 * @return array Informazioni browser
 */
function getBrowserInfo() {
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    if (empty($userAgent)) {
        return array(
            'browser' => 'Unknown',
            'version' => '',
            'platform' => 'Unknown',
            'is_mobile' => false,
            'is_bot' => false,
            'user_agent' => ''
        );
    }
    
    $browser = 'Unknown';
    $version = '';
    $platform = 'Unknown';
    $is_mobile = false;
    $is_bot = false;
    
    // Rileva bot
    $bot_patterns = array(
        'bot', 'crawler', 'spider', 'crawl', 'slurp', 'googlebot', 'bingbot',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp',
        'telegram', 'discord', 'slack'
    );
    
    foreach ($bot_patterns as $pattern) {
        if (stripos($userAgent, $pattern) !== false) {
            $is_bot = true;
            $browser = 'Bot';
            break;
        }
    }
    
    if (!$is_bot) {
        // Rileva piattaforma
        if (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            if (preg_match('/windows nt 10/i', $userAgent)) {
                $platform = 'Windows 10';
            } elseif (preg_match('/windows nt 11/i', $userAgent)) {
                $platform = 'Windows 11';
            } else {
                $platform = 'Windows';
            }
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
            $is_mobile = true;
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            if (preg_match('/iphone/i', $userAgent)) {
                $platform = 'iPhone';
            } elseif (preg_match('/ipad/i', $userAgent)) {
                $platform = 'iPad';
            } else {
                $platform = 'iOS';
            }
            $is_mobile = true;
        }
        
        // Rileva browser
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
            preg_match('/edg\/([0-9.]+)/i', $userAgent, $matches);
            $version = isset($matches[1]) ? $matches[1] : '';
        } elseif (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
            $browser = 'Chrome';
            preg_match('/chrome\/([0-9.]+)/i', $userAgent, $matches);
            $version = isset($matches[1]) ? $matches[1] : '';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
            preg_match('/version\/([0-9.]+)/i', $userAgent, $matches);
            $version = isset($matches[1]) ? $matches[1] : '';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
            preg_match('/firefox\/([0-9.]+)/i', $userAgent, $matches);
            $version = isset($matches[1]) ? $matches[1] : '';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
            if (preg_match('/opr\/([0-9.]+)/i', $userAgent, $matches)) {
                $version = isset($matches[1]) ? $matches[1] : '';
            } elseif (preg_match('/opera\/([0-9.]+)/i', $userAgent, $matches)) {
                $version = isset($matches[1]) ? $matches[1] : '';
            }
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
            if (preg_match('/msie ([0-9.]+)/i', $userAgent, $matches)) {
                $version = isset($matches[1]) ? $matches[1] : '';
            } elseif (preg_match('/rv:([0-9.]+)/i', $userAgent, $matches)) {
                $version = isset($matches[1]) ? $matches[1] : '';
            }
        }
        
        // Rileva mobile aggiuntivo
        if (!$is_mobile) {
            $mobile_patterns = array('mobile', 'android', 'iphone', 'ipod', 'blackberry', 'windows phone');
            foreach ($mobile_patterns as $pattern) {
                if (stripos($userAgent, $pattern) !== false) {
                    $is_mobile = true;
                    break;
                }
            }
        }
    }
    
    return array(
        'browser' => $browser,
        'version' => $version,
        'platform' => $platform,
        'is_mobile' => $is_mobile,
        'is_bot' => $is_bot,
        'user_agent' => $userAgent
    );
}

/**
 * Genera hash per cache
 * 
 * @param string $input Input da hashare
 * @return string Hash
 */
function generateCacheKey($input) {
    $version = defined('APP_VERSION') ? APP_VERSION : '1.0';
    return 'cd_' . md5($input . '_v' . $version . '_' . date('Ymd'));
}

/**
 * Controlla se la richiesta è AJAX
 * 
 * @return bool True se richiesta AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Invia headers per JSON response
 * 
 * @param bool $allowOrigin Se permettere CORS
 */
function setJsonHeaders($allowOrigin = false) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    if ($allowOrigin) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');
    }
}

/**
 * Rate limiting con supporto per file e memcached
 * 
 * @param string $identifier Identificatore (IP, session, etc)
 * @param int $limit Limite richieste
 * @param int $window Finestra temporale in secondi
 * @return bool True se dentro i limiti
 */
function checkRateLimit($identifier, $limit = null, $window = null) {
    // Verifica se il rate limiting è abilitato
    if (!defined('RATE_LIMIT_ENABLED') || !RATE_LIMIT_ENABLED) {
        return true;
    }
    
    // Usa valori di default se non specificati
    if ($limit === null) {
        $limit = defined('RATE_LIMIT_REQUESTS') ? RATE_LIMIT_REQUESTS : 100;
    }
    
    if ($window === null) {
        $window = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 3600;
    }
    
    // Sanitizza l'identificatore
    $identifier = preg_replace('/[^a-zA-Z0-9_.-]/', '', $identifier);
    
    // Prova prima con Memcached se disponibile
    if (class_exists('Memcached')) {
        return checkRateLimitMemcached($identifier, $limit, $window);
    }
    
    // Fallback su file system
    return checkRateLimitFile($identifier, $limit, $window);
}

/**
 * Rate limiting con Memcached
 * 
 * @param string $identifier Identificatore
 * @param int $limit Limite
 * @param int $window Finestra temporale
 * @return bool
 */
function checkRateLimitMemcached($identifier, $limit, $window) {
    static $memcached = null;
    
    if ($memcached === null) {
        $memcached = new Memcached();
        $memcached->addServer('localhost', 11211);
    }
    
    $key = 'rate_limit_' . $identifier;
    $current = $memcached->get($key);
    
    if ($current === false) {
        // Prima richiesta
        $memcached->set($key, 1, $window);
        return true;
    }
    
    if ($current >= $limit) {
        return false;
    }
    
    $memcached->increment($key);
    return true;
}

/**
 * Rate limiting con file system
 * 
 * @param string $identifier Identificatore
 * @param int $limit Limite
 * @param int $window Finestra temporale
 * @return bool
 */
function checkRateLimitFile($identifier, $limit, $window) {
    // Directory per i file di rate limit
    $rateDir = sys_get_temp_dir() . '/controllodomini_rate';
    
    if (!is_dir($rateDir)) {
        @mkdir($rateDir, 0755, true);
    }
    
    // Pulizia periodica dei file vecchi (1% di probabilità)
    if (mt_rand(1, 100) == 1) {
        cleanupRateLimitFiles($rateDir, $window * 2);
    }
    
    $cacheFile = $rateDir . '/rl_' . md5($identifier) . '.json';
    
    // Lock file per evitare race conditions
    $lockFile = $cacheFile . '.lock';
    $fp = @fopen($lockFile, 'c');
    
    if (!$fp) {
        return true; // In caso di errore, permetti l'accesso
    }
    
    // Acquisisci lock esclusivo
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return true; // Se non possiamo ottenere il lock, permetti
    }
    
    $now = time();
    $data = array();
    
    // Leggi dati esistenti
    if (file_exists($cacheFile)) {
        $content = @file_get_contents($cacheFile);
        if ($content) {
            $data = json_decode($content, true);
            if (!is_array($data)) {
                $data = array();
            }
        }
    }
    
    // Pulisci timestamp vecchi
    $data = array_filter($data, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Controlla limite
    if (count($data) >= $limit) {
        flock($fp, LOCK_UN);
        fclose($fp);
        @unlink($lockFile);
        return false;
    }
    
    // Aggiungi nuovo timestamp
    $data[] = $now;
    
    // Salva dati aggiornati
    @file_put_contents($cacheFile, json_encode($data), LOCK_EX);
    
    // Rilascia lock
    flock($fp, LOCK_UN);
    fclose($fp);
    @unlink($lockFile);
    
    return true;
}

/**
 * Pulizia file di rate limit vecchi
 * 
 * @param string $dir Directory
 * @param int $maxAge Età massima in secondi
 */
function cleanupRateLimitFiles($dir, $maxAge) {
    $now = time();
    $files = @glob($dir . '/rl_*.json');
    
    if (!$files) return;
    
    foreach ($files as $file) {
        if (($now - @filemtime($file)) > $maxAge) {
            @unlink($file);
            @unlink($file . '.lock');
        }
    }
}

/**
 * Genera un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION)) {
        @session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    
    return $token;
}

/**
 * Verifica un token CSRF
 * 
 * @param string $token Token da verificare
 * @return bool True se valido
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION)) {
        @session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Ottiene il protocollo corrente (http/https)
 * 
 * @return string Protocollo
 */
function getCurrentProtocol() {
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
    ) {
        return 'https';
    }
    
    return 'http';
}

/**
 * Genera URL completo per la pagina corrente
 * 
 * @param bool $includeQuery Include query string
 * @return string URL completo
 */
function getCurrentUrl($includeQuery = true) {
    $protocol = getCurrentProtocol();
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    
    if (!$includeQuery) {
        $uri = strtok($uri, '?');
    }
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Tempo di esecuzione dello script
 * 
 * @param float $start Timestamp di inizio
 * @return string Tempo formattato
 */
function getExecutionTime($start = null) {
    if ($start === null && defined('SCRIPT_START_TIME')) {
        $start = SCRIPT_START_TIME;
    }
    
    if ($start === null) {
        return '0ms';
    }
    
    $time = (microtime(true) - $start) * 1000;
    
    if ($time < 1000) {
        return round($time, 2) . 'ms';
    } else {
        return round($time / 1000, 2) . 's';
    }
}

/**
 * Memoria utilizzata dallo script
 * 
 * @return string Memoria formattata
 */
function getMemoryUsage() {
    $memory = memory_get_peak_usage(true);
    return formatBytes($memory);
}

/**
 * Debug helper - stampa variabile in modo leggibile
 * 
 * @param mixed $var Variabile da stampare
 * @param bool $return Se ritornare invece di stampare
 * @return string|null
 */
function debug($var, $return = false) {
    $output = '<pre style="background:#f4f4f4;padding:10px;border:1px solid #ddd;margin:10px 0;border-radius:4px;overflow:auto;">';
    $output .= htmlspecialchars(print_r($var, true));
    $output .= '</pre>';
    
    if ($return) {
        return $output;
    }
    
    echo $output;
    return null;
}
?>
