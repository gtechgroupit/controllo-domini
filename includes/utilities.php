<?php
/**
 * Funzioni di utilità generale per Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Calcola il tempo di risposta DNS per un dominio
 * 
 * @param string $domain Il dominio da verificare
 * @return float Tempo di risposta in millisecondi
 */
function measureDnsResponseTime($domain) {
    $start = microtime(true);
    @dns_get_record($domain, DNS_A);
    $end = microtime(true);
    return round(($end - $start) * 1000, 2);
}

/**
 * Formatta il TTL in formato leggibile
 * 
 * @param int $seconds TTL in secondi
 * @return string TTL formattato (es: 1h, 30m, 45s)
 */
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

/**
 * Valida e pulisce un nome di dominio
 * 
 * @param string $domain Il dominio da validare
 * @return string|false Dominio pulito o false se non valido
 */
function validateDomain($domain) {
    // Rimuove spazi
    $domain = trim($domain);
    
    // Rimuove http://, https://, www.
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = trim($domain, '/');
    
    // Converte in minuscolo
    $domain = strtolower($domain);
    
    // Verifica formato dominio base
    if (!preg_match('/^[a-z0-9][a-z0-9-]{0,61}[a-z0-9]?\.[a-z]{2,}$/i', $domain)) {
        return false;
    }
    
    // Verifica lunghezza totale
    if (strlen($domain) > 253) {
        return false;
    }
    
    // Verifica ogni label del dominio
    $labels = explode('.', $domain);
    foreach ($labels as $label) {
        if (strlen($label) > 63 || strlen($label) < 1) {
            return false;
        }
        if (substr($label, 0, 1) === '-' || substr($label, -1) === '-') {
            return false;
        }
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
    $tld = strtolower(substr(strrchr($domain, '.'), 1));
    
    // Gestisce TLD di secondo livello
    $secondLevelTLDs = array('.co.uk', '.org.uk', '.com.au', '.com.br', '.co.jp', '.co.kr', '.co.nz', '.co.za');
    foreach ($secondLevelTLDs as $sld) {
        if (substr($domain, -strlen($sld)) === $sld) {
            return substr($sld, 1);
        }
    }
    
    return $tld;
}

/**
 * Genera un ID univoco per la sessione di analisi
 * 
 * @return string ID sessione
 */
function generateAnalysisId() {
    return 'analysis_' . uniqid() . '_' . time();
}

/**
 * Formatta una data in italiano
 * 
 * @param string $date Data da formattare
 * @param string $format Formato output (default: d/m/Y)
 * @return string Data formattata
 */
function formatDateIT($date, $format = 'd/m/Y') {
    if (empty($date) || $date == 'Non disponibile') {
        return $date;
    }
    
    $timestamp = strtotime($date);
    if (!$timestamp) {
        // Prova con formato italiano
        $date = str_replace('/', '-', $date);
        $timestamp = strtotime($date);
    }
    
    if ($timestamp && $timestamp > 0) {
        return date($format, $timestamp);
    }
    
    return $date;
}

/**
 * Calcola i giorni rimanenti fino a una data
 * 
 * @param string $date Data target
 * @return int|false Giorni rimanenti o false se non calcolabile
 */
function daysUntil($date) {
    if (empty($date) || $date == 'Non disponibile') {
        return false;
    }
    
    $timestamp = strtotime(str_replace('/', '-', $date));
    if (!$timestamp) {
        return false;
    }
    
    $days = floor(($timestamp - time()) / 86400);
    return $days >= 0 ? $days : false;
}

/**
 * Genera classe CSS per stato scadenza
 * 
 * @param int $days Giorni alla scadenza
 * @return string Classe CSS
 */
function getExpirationClass($days) {
    if ($days === false) {
        return '';
    }
    
    if ($days < 30) {
        return 'text-danger';
    } elseif ($days < 90) {
        return 'text-warning';
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
    return implode('.', array_reverse(explode('.', $ip)));
}

/**
 * Ottiene l'IP del visitatore
 * 
 * @return string Indirizzo IP
 */
function getVisitorIP() {
    $ipKeys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Log delle analisi per statistiche
 * 
 * @param string $domain Dominio analizzato
 * @param array $results Risultati analisi
 * @return void
 */
function logAnalysis($domain, $results = array()) {
    if (!defined('LOG_ENABLED') || !LOG_ENABLED) {
        return;
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
        'cloud_services' => isset($results['cloud_services']) ? implode(',', array_keys($results['cloud_services'])) : '',
        'blacklisted' => isset($results['blacklisted']) ? $results['blacklisted'] : false
    );
    
    // Qui puoi implementare il salvataggio su file o database
    // Per ora, solo preparazione dei dati
}

/**
 * Genera meta description dinamica per SEO
 * 
 * @param string $domain Dominio analizzato
 * @return string Meta description
 */
function generateMetaDescription($domain = null) {
    if ($domain) {
        return "Analisi completa del dominio {$domain}: verifica DNS, record MX, SPF, DKIM, DMARC, informazioni WHOIS, presenza in blacklist. Strumento gratuito by Controllo Domini.";
    }
    
    return SEO_DESCRIPTION;
}

/**
 * Genera title tag dinamico per SEO
 * 
 * @param string $domain Dominio analizzato
 * @return string Title tag
 */
function generatePageTitle($domain = null) {
    if ($domain) {
        return "Analisi dominio {$domain} - DNS, WHOIS, Blacklist | Controllo Domini";
    }
    
    return SEO_TITLE;
}

/**
 * Sanitizza output HTML
 * 
 * @param string $string Stringa da sanitizzare
 * @return string Stringa sanitizzata
 */
function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatta dimensione file
 * 
 * @param int $bytes Dimensione in bytes
 * @return string Dimensione formattata
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Genera URL SEO-friendly
 * 
 * @param string $string Stringa da convertire
 * @return string URL SEO-friendly
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[àáâãäå]/', 'a', $string);
    $string = preg_replace('/[èéêë]/', 'e', $string);
    $string = preg_replace('/[ìíîï]/', 'i', $string);
    $string = preg_replace('/[òóôõö]/', 'o', $string);
    $string = preg_replace('/[ùúûü]/', 'u', $string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
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
    return preg_match('/[^\x00-\x7F]/', $domain);
}

/**
 * Converte IDN in formato ASCII (Punycode)
 * 
 * @param string $domain Dominio IDN
 * @return string Dominio in Punycode
 */
function idnToAscii($domain) {
    if (function_exists('idn_to_ascii')) {
        $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        return $ascii !== false ? $ascii : $domain;
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
    
    $browser = 'Unknown';
    $version = '';
    $platform = 'Unknown';
    
    // Rileva piattaforma
    if (preg_match('/linux/i', $userAgent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $userAgent)) {
        $platform = 'Windows';
    } elseif (preg_match('/android/i', $userAgent)) {
        $platform = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $platform = 'iOS';
    }
    
    // Rileva browser
    if (preg_match('/firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/chrome/i', $userAgent) && !preg_match('/edge/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/edge/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/opera|opr/i', $userAgent)) {
        $browser = 'Opera';
    } elseif (preg_match('/msie|trident/i', $userAgent)) {
        $browser = 'Internet Explorer';
    }
    
    return array(
        'browser' => $browser,
        'platform' => $platform,
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
    return 'cd_' . md5($input . '_v' . APP_VERSION);
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
 */
function setJsonHeaders() {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');
}

/**
 * Rate limiting semplice
 * 
 * @param string $identifier Identificatore (IP, session, etc)
 * @param int $limit Limite richieste
 * @param int $window Finestra temporale in secondi
 * @return bool True se dentro i limiti
 */
function checkRateLimit($identifier, $limit = 100, $window = 3600) {
    if (!defined('RATE_LIMIT_ENABLED') || !RATE_LIMIT_ENABLED) {
        return true;
    }
    
    // Implementazione semplificata - in produzione usare Redis/Memcached
    $cacheFile = sys_get_temp_dir() . '/controllodomini_rl_' . md5($identifier);
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data['timestamp'] > time() - $window) {
            if ($data['count'] >= $limit) {
                return false;
            }
            $data['count']++;
        } else {
            $data = array('timestamp' => time(), 'count' => 1);
        }
    } else {
        $data = array('timestamp' => time(), 'count' => 1);
    }
    
    file_put_contents($cacheFile, json_encode($data));
    return true;
}
?>
