<?php
/**
 * Funzioni per l'analisi di robots.txt e sitemap
 * 
 * @package ControlDomini
 * @subpackage SEO
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza robots.txt e sitemap di un dominio
 * 
 * @param string $domain Dominio da analizzare
 * @return array Risultati dell'analisi
 */
function analyzeRobotsSitemap($domain) {
    $results = array(
        'robots' => analyzeRobotsTxt($domain),
        'sitemap' => analyzeSitemap($domain),
        'crawl_analysis' => array(),
        'seo_score' => 0,
        'recommendations' => array()
    );
    
    // Analisi crawlability
    $results['crawl_analysis'] = analyzeCrawlability($results['robots'], $results['sitemap']);
    
    // Calcola SEO score
    $results['seo_score'] = calculateSeoScore($results);
    
    // Genera raccomandazioni
    $results['recommendations'] = generateRobotsSitemapRecommendations($results);
    
    return $results;
}

/**
 * Analizza il file robots.txt
 * 
 * @param string $domain Dominio
 * @return array Analisi robots.txt
 */
function analyzeRobotsTxt($domain) {
    $result = array(
        'exists' => false,
        'url' => "https://{$domain}/robots.txt",
        'size' => 0,
        'content' => '',
        'rules' => array(),
        'sitemaps' => array(),
        'crawl_delay' => array(),
        'issues' => array(),
        'user_agents' => array(),
        'disallowed_paths' => array(),
        'allowed_paths' => array(),
        'response_code' => null,
        'response_time' => null
    );
    
    $start_time = microtime(true);
    
    // Recupera robots.txt
    $ch = curl_init($result['url']);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ControlloDomin/1.0; +https://controllodomini.it/bot)'
    ));
    
    $content = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $result['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
    $result['response_code'] = $info['http_code'];
    
    if ($info['http_code'] === 200 && $content) {
        $result['exists'] = true;
        $result['size'] = strlen($content);
        $result['content'] = $content;
        
        // Analizza contenuto
        $parsed = parseRobotsTxt($content);
        $result = array_merge($result, $parsed);
        
        // Controlla problemi comuni
        $result['issues'] = checkRobotsIssues($result);
        
        // Analisi sicurezza
        $result['security_analysis'] = analyzeRobotsSecurity($result);
    } else {
        $result['issues'][] = array(
            'type' => 'missing',
            'severity' => 'medium',
            'message' => 'File robots.txt non trovato',
            'impact' => 'I crawler potrebbero indicizzare contenuti non desiderati'
        );
    }
    
    return $result;
}

/**
 * Parse del contenuto robots.txt
 * 
 * @param string $content Contenuto del file
 * @return array Dati parsati
 */
function parseRobotsTxt($content) {
    $result = array(
        'rules' => array(),
        'sitemaps' => array(),
        'crawl_delay' => array(),
        'user_agents' => array(),
        'disallowed_paths' => array(),
        'allowed_paths' => array()
    );
    
    $lines = explode("\n", $content);
    $current_agent = '*';
    $result['rules'][$current_agent] = array(
        'disallow' => array(),
        'allow' => array(),
        'crawl_delay' => null
    );
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignora commenti e linee vuote
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Rimuovi commenti inline
        if (($pos = strpos($line, '#')) !== false) {
            $line = trim(substr($line, 0, $pos));
        }
        
        // Parse direttive
        if (preg_match('/^([\w-]+):\s*(.*)$/i', $line, $matches)) {
            $directive = strtolower($matches[1]);
            $value = trim($matches[2]);
            
            switch ($directive) {
                case 'user-agent':
                    $current_agent = $value;
                    if (!isset($result['rules'][$current_agent])) {
                        $result['rules'][$current_agent] = array(
                            'disallow' => array(),
                            'allow' => array(),
                            'crawl_delay' => null
                        );
                    }
                    if (!in_array($current_agent, $result['user_agents'])) {
                        $result['user_agents'][] = $current_agent;
                    }
                    break;
                    
                case 'disallow':
                    if (!empty($value)) {
                        $result['rules'][$current_agent]['disallow'][] = $value;
                        if (!in_array($value, $result['disallowed_paths'])) {
                            $result['disallowed_paths'][] = $value;
                        }
                    }
                    break;
                    
                case 'allow':
                    if (!empty($value)) {
                        $result['rules'][$current_agent]['allow'][] = $value;
                        if (!in_array($value, $result['allowed_paths'])) {
                            $result['allowed_paths'][] = $value;
                        }
                    }
                    break;
                    
                case 'crawl-delay':
                    $delay = intval($value);
                    $result['rules'][$current_agent]['crawl_delay'] = $delay;
                    $result['crawl_delay'][$current_agent] = $delay;
                    break;
                    
                case 'sitemap':
                    if (!in_array($value, $result['sitemaps'])) {
                        $result['sitemaps'][] = $value;
                    }
                    break;
            }
        }
    }
    
    return $result;
}

/**
 * Controlla problemi comuni in robots.txt
 * 
 * @param array $robots_data Dati robots.txt
 * @return array Issues trovate
 */
function checkRobotsIssues($robots_data) {
    $issues = array();
    
    // File troppo grande
    if ($robots_data['size'] > 500000) { // 500KB
        $issues[] = array(
            'type' => 'size',
            'severity' => 'high',
            'message' => 'File robots.txt troppo grande (' . formatBytes($robots_data['size']) . ')',
            'impact' => 'Alcuni crawler potrebbero ignorarlo'
        );
    }
    
    // Blocco completo del sito
    foreach ($robots_data['rules'] as $agent => $rules) {
        if (in_array('/', $rules['disallow']) && empty($rules['allow'])) {
            $issues[] = array(
                'type' => 'complete_block',
                'severity' => 'critical',
                'message' => "Tutto il sito è bloccato per: $agent",
                'impact' => 'Il sito non sarà indicizzato'
            );
        }
    }
    
    // Crawl delay troppo alto
    foreach ($robots_data['crawl_delay'] as $agent => $delay) {
        if ($delay > 10) {
            $issues[] = array(
                'type' => 'crawl_delay',
                'severity' => 'medium',
                'message' => "Crawl-delay troppo alto per $agent: {$delay}s",
                'impact' => 'Rallenta significativamente la scansione'
            );
        }
    }
    
    // Path sensibili esposti
    $sensitive_paths = array(
        '/admin', '/wp-admin', '/administrator', 
        '/phpmyadmin', '/backup', '/.git',
        '/config', '/private', '/api/private'
    );
    
    $blocked_sensitive = array_intersect($sensitive_paths, $robots_data['disallowed_paths']);
    $exposed_sensitive = array_diff($sensitive_paths, $blocked_sensitive);
    
    if (!empty($exposed_sensitive)) {
        $issues[] = array(
            'type' => 'security',
            'severity' => 'medium',
            'message' => 'Path sensibili non bloccati: ' . implode(', ', array_slice($exposed_sensitive, 0, 3)),
            'impact' => 'Potenziale esposizione di aree sensibili'
        );
    }
    
    // Nessuna sitemap
    if (empty($robots_data['sitemaps'])) {
        $issues[] = array(
            'type' => 'sitemap',
            'severity' => 'low',
            'message' => 'Nessuna sitemap specificata in robots.txt',
            'impact' => 'I crawler devono cercare manualmente la sitemap'
        );
    }
    
    // Regole duplicate o conflittuali
    foreach ($robots_data['rules'] as $agent => $rules) {
        $conflicts = array_intersect($rules['disallow'], $rules['allow']);
        if (!empty($conflicts)) {
            $issues[] = array(
                'type' => 'conflict',
                'severity' => 'medium',
                'message' => "Regole conflittuali per $agent",
                'impact' => 'Comportamento imprevedibile dei crawler'
            );
        }
    }
    
    return $issues;
}

/**
 * Analizza aspetti di sicurezza in robots.txt
 * 
 * @param array $robots_data Dati robots.txt
 * @return array Analisi sicurezza
 */
function analyzeRobotsSecurity($robots_data) {
    $security = array(
        'exposes_structure' => false,
        'blocks_security_scanners' => false,
        'sensitive_paths' => array(),
        'admin_paths' => array(),
        'api_paths' => array(),
        'backup_paths' => array()
    );
    
    // Analizza path disallowed per informazioni sulla struttura
    foreach ($robots_data['disallowed_paths'] as $path) {
        // Admin paths
        if (preg_match('/(admin|administrator|backend|cpanel|panel)/i', $path)) {
            $security['admin_paths'][] = $path;
            $security['exposes_structure'] = true;
        }
        
        // API paths
        if (preg_match('/(api|webservice|service|endpoint)/i', $path)) {
            $security['api_paths'][] = $path;
        }
        
        // Backup/temp paths
        if (preg_match('/(backup|temp|tmp|old|test|dev)/i', $path)) {
            $security['backup_paths'][] = $path;
            $security['exposes_structure'] = true;
        }
        
        // Version control
        if (preg_match('/(\\.git|\\.svn|\\.hg)/i', $path)) {
            $security['sensitive_paths'][] = $path;
        }
    }
    
    // Controlla se blocca security scanner
    $security_scanners = array('*security*', '*scanner*', '*audit*');
    foreach ($robots_data['user_agents'] as $agent) {
        foreach ($security_scanners as $scanner) {
            if (fnmatch($scanner, strtolower($agent))) {
                $security['blocks_security_scanners'] = true;
                break 2;
            }
        }
    }
    
    return $security;
}

/**
 * Analizza la sitemap
 * 
 * @param string $domain Dominio
 * @return array Analisi sitemap
 */
function analyzeSitemap($domain) {
    $result = array(
        'found' => false,
        'urls' => array(),
        'url_count' => 0,
        'size' => 0,
        'format' => null,
        'last_modified' => null,
        'issues' => array(),
        'index_files' => array(),
        'response_code' => null,
        'response_time' => null
    );
    
    // Lista di possibili URL sitemap
    $sitemap_urls = array(
        "https://{$domain}/sitemap.xml",
        "https://{$domain}/sitemap_index.xml",
        "https://{$domain}/sitemap.xml.gz",
        "https://{$domain}/wp-sitemap.xml"
    );
    
    foreach ($sitemap_urls as $url) {
        $sitemap_data = fetchSitemap($url);
        
        if ($sitemap_data['success']) {
            $result['found'] = true;
            $result['primary_url'] = $url;
            $result['size'] = $sitemap_data['size'];
            $result['response_code'] = $sitemap_data['response_code'];
            $result['response_time'] = $sitemap_data['response_time'];
            
            // Parse sitemap
            $parsed = parseSitemap($sitemap_data['content'], $url);
            $result = array_merge($result, $parsed);
            
            // Controlla problemi
            $result['issues'] = checkSitemapIssues($result);
            
            break;
        }
    }
    
    if (!$result['found']) {
        $result['issues'][] = array(
            'type' => 'missing',
            'severity' => 'high',
            'message' => 'Nessuna sitemap trovata',
            'impact' => 'I motori di ricerca non possono scoprire facilmente tutte le pagine'
        );
    }
    
    return $result;
}

/**
 * Recupera il contenuto della sitemap
 * 
 * @param string $url URL della sitemap
 * @return array Dati sitemap
 */
function fetchSitemap($url) {
    $result = array(
        'success' => false,
        'content' => '',
        'size' => 0,
        'response_code' => null,
        'response_time' => null
    );
    
    $start_time = microtime(true);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ControlloDomin/1.0)'
    ));
    
    $content = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $result['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
    $result['response_code'] = $info['http_code'];
    
    if ($info['http_code'] === 200 && $content) {
        $result['success'] = true;
        $result['content'] = $content;
        $result['size'] = strlen($content);
    }
    
    return $result;
}

/**
 * Parse del contenuto della sitemap
 * 
 * @param string $content Contenuto XML
 * @param string $url URL della sitemap
 * @return array Dati parsati
 */
function parseSitemap($content, $url) {
    $result = array(
        'format' => 'unknown',
        'urls' => array(),
        'url_count' => 0,
        'index_files' => array(),
        'images' => 0,
        'videos' => 0,
        'news' => 0
    );
    
    // Disabilita errori XML
    $old_setting = libxml_use_internal_errors(true);
    
    $xml = simplexml_load_string($content);
    
    if ($xml === false) {
        $result['format'] = 'invalid';
        return $result;
    }
    
    // Rileva tipo di sitemap
    $namespaces = $xml->getNamespaces();
    
    if ($xml->getName() === 'sitemapindex') {
        // Sitemap index
        $result['format'] = 'index';
        
        foreach ($xml->sitemap as $sitemap) {
            $index_entry = array(
                'loc' => (string)$sitemap->loc,
                'lastmod' => isset($sitemap->lastmod) ? (string)$sitemap->lastmod : null
            );
            $result['index_files'][] = $index_entry;
        }
    } else {
        // URL sitemap
        $result['format'] = 'urlset';
        
        foreach ($xml->url as $url_entry) {
            $url_data = array(
                'loc' => (string)$url_entry->loc,
                'lastmod' => isset($url_entry->lastmod) ? (string)$url_entry->lastmod : null,
                'changefreq' => isset($url_entry->changefreq) ? (string)$url_entry->changefreq : null,
                'priority' => isset($url_entry->priority) ? (float)$url_entry->priority : null
            );
            
            // Controlla estensioni
            if (isset($namespaces['image'])) {
                $images = $url_entry->children($namespaces['image']);
                if ($images && $images->image) {
                    $result['images'] += count($images->image);
                }
            }
            
            if (isset($namespaces['video'])) {
                $videos = $url_entry->children($namespaces['video']);
                if ($videos && $videos->video) {
                    $result['videos'] += count($videos->video);
                }
            }
            
            if (isset($namespaces['news'])) {
                $news = $url_entry->children($namespaces['news']);
                if ($news && $news->news) {
                    $result['news']++;
                }
            }
            
            $result['urls'][] = $url_data;
        }
        
        $result['url_count'] = count($result['urls']);
    }
    
    // Ripristina impostazione errori
    libxml_use_internal_errors($old_setting);
    
    return $result;
}

/**
 * Controlla problemi nella sitemap
 * 
 * @param array $sitemap_data Dati sitemap
 * @return array Issues trovate
 */
function checkSitemapIssues($sitemap_data) {
    $issues = array();
    
    // Sitemap troppo grande
    if ($sitemap_data['size'] > 52428800) { // 50MB
        $issues[] = array(
            'type' => 'size',
            'severity' => 'high',
            'message' => 'Sitemap troppo grande (' . formatBytes($sitemap_data['size']) . ')',
            'impact' => 'Supera il limite di 50MB'
        );
    }
    
    // Troppi URL
    if ($sitemap_data['url_count'] > 50000) {
        $issues[] = array(
            'type' => 'url_count',
            'severity' => 'high',
            'message' => 'Troppi URL nella sitemap (' . $sitemap_data['url_count'] . ')',
            'impact' => 'Supera il limite di 50.000 URL per file'
        );
    }
    
    // Analizza URL
    if (!empty($sitemap_data['urls'])) {
        $domains = array();
        $protocols = array();
        $outdated = 0;
        $no_lastmod = 0;
        
        foreach ($sitemap_data['urls'] as $url_entry) {
            // Estrai dominio e protocollo
            $parsed = parse_url($url_entry['loc']);
            if ($parsed) {
                $domains[$parsed['host']] = true;
                $protocols[$parsed['scheme']] = true;
            }
            
            // Controlla lastmod
            if (!$url_entry['lastmod']) {
                $no_lastmod++;
            } else {
                // Controlla se outdated (più di 1 anno)
                $lastmod_time = strtotime($url_entry['lastmod']);
                if ($lastmod_time && (time() - $lastmod_time) > 31536000) {
                    $outdated++;
                }
            }
        }
        
        // URL di domini diversi
        if (count($domains) > 1) {
            $issues[] = array(
                'type' => 'multiple_domains',
                'severity' => 'high',
                'message' => 'La sitemap contiene URL di domini diversi',
                'impact' => 'Non valida per i motori di ricerca'
            );
        }
        
        // Protocolli misti
        if (isset($protocols['http']) && isset($protocols['https'])) {
            $issues[] = array(
                'type' => 'mixed_protocols',
                'severity' => 'medium',
                'message' => 'Protocolli misti (HTTP e HTTPS)',
                'impact' => 'Possibili problemi di contenuto duplicato'
            );
        }
        
        // Lastmod mancanti
        if ($no_lastmod > ($sitemap_data['url_count'] * 0.5)) {
            $issues[] = array(
                'type' => 'missing_lastmod',
                'severity' => 'low',
                'message' => 'Molti URL senza data di modifica',
                'impact' => 'I crawler non sanno quando le pagine sono state aggiornate'
            );
        }
        
        // URL outdated
        if ($outdated > ($sitemap_data['url_count'] * 0.3)) {
            $issues[] = array(
                'type' => 'outdated',
                'severity' => 'medium',
                'message' => 'Molti URL non aggiornati da oltre un anno',
                'impact' => 'Potrebbe contenere contenuti obsoleti'
            );
        }
    }
    
    return $issues;
}

/**
 * Analizza la crawlability del sito
 * 
 * @param array $robots_data Dati robots.txt
 * @param array $sitemap_data Dati sitemap
 * @return array Analisi crawlability
 */
function analyzeCrawlability($robots_data, $sitemap_data) {
    $analysis = array(
        'is_crawlable' => true,
        'crawl_score' => 100,
        'blocked_important' => array(),
        'sitemap_coverage' => 0,
        'recommendations' => array()
    );
    
    // Controlla se il sito è crawlable
    if ($robots_data['exists']) {
        foreach ($robots_data['rules'] as $agent => $rules) {
            if (($agent === '*' || stripos($agent, 'googlebot') !== false) && 
                in_array('/', $rules['disallow'])) {
                $analysis['is_crawlable'] = false;
                $analysis['crawl_score'] = 0;
                break;
            }
        }
    }
    
    // Controlla path importanti bloccati
    $important_paths = array(
        '/products', '/services', '/blog', '/news',
        '/about', '/contact', '/shop', '/store'
    );
    
    if ($robots_data['exists']) {
        $analysis['blocked_important'] = array_intersect(
            $important_paths, 
            $robots_data['disallowed_paths']
        );
        
        if (!empty($analysis['blocked_important'])) {
            $analysis['crawl_score'] -= count($analysis['blocked_important']) * 5;
        }
    }
    
    // Calcola coverage sitemap
    if ($sitemap_data['found'] && $sitemap_data['url_count'] > 0) {
        // Stima basata su dimensione tipica del sito
        if ($sitemap_data['url_count'] < 100) {
            $analysis['sitemap_coverage'] = 80; // Piccolo sito
        } elseif ($sitemap_data['url_count'] < 1000) {
            $analysis['sitemap_coverage'] = 85; // Medio
        } else {
            $analysis['sitemap_coverage'] = 90; // Grande
        }
    } else {
        $analysis['crawl_score'] -= 20;
    }
    
    // Normalizza score
    $analysis['crawl_score'] = max(0, min(100, $analysis['crawl_score']));
    
    return $analysis;
}

/**
 * Calcola SEO score
 * 
 * @param array $results Risultati completi
 * @return int Score SEO
 */
function calculateSeoScore($results) {
    $score = 100;
    
    // Robots.txt
    if (!$results['robots']['exists']) {
        $score -= 10;
    } else {
        // Penalità per issues
        foreach ($results['robots']['issues'] as $issue) {
            switch ($issue['severity']) {
                case 'critical':
                    $score -= 20;
                    break;
                case 'high':
                    $score -= 10;
                    break;
                case 'medium':
                    $score -= 5;
                    break;
            }
        }
    }
    
    // Sitemap
    if (!$results['sitemap']['found']) {
        $score -= 15;
    } else {
        // Penalità per issues
        foreach ($results['sitemap']['issues'] as $issue) {
            switch ($issue['severity']) {
                case 'high':
                    $score -= 10;
                    break;
                case 'medium':
                    $score -= 5;
                    break;
            }
        }
    }
    
    // Crawlability
    if (!$results['crawl_analysis']['is_crawlable']) {
        $score = 0; // Critico se non crawlable
    }
    
    return max(0, min(100, $score));
}

/**
 * Genera raccomandazioni
 * 
 * @param array $results Risultati analisi
 * @return array Raccomandazioni
 */
function generateRobotsSitemapRecommendations($results) {
    $recommendations = array();
    
    // Robots.txt mancante
    if (!$results['robots']['exists']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'robots',
            'title' => 'Crea file robots.txt',
            'description' => 'Il file robots.txt aiuta a controllare quali parti del sito possono essere scansionate',
            'solution' => 'Crea un file robots.txt nella root del sito'
        );
    }
    
    // Sitemap mancante
    if (!$results['sitemap']['found']) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'sitemap',
            'title' => 'Crea una sitemap XML',
            'description' => 'La sitemap aiuta i motori di ricerca a trovare tutte le tue pagine',
            'solution' => 'Genera una sitemap XML e aggiungila a robots.txt'
        );
    }
    
    // Sitemap non in robots.txt
    if ($results['sitemap']['found'] && $results['robots']['exists'] && 
        empty($results['robots']['sitemaps'])) {
        $recommendations[] = array(
            'priority' => 'low',
            'category' => 'integration',
            'title' => 'Aggiungi sitemap a robots.txt',
            'description' => 'Dichiara la sitemap in robots.txt per facilitarne la scoperta',
            'solution' => 'Aggiungi: Sitemap: ' . $results['sitemap']['primary_url']
        );
    }
    
    // Path sensibili esposti
    if ($results['robots']['security_analysis']['exposes_structure']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'security',
            'title' => 'Rivedi i path bloccati',
            'description' => 'Alcuni path bloccati rivelano la struttura del sito',
            'solution' => 'Usa pattern generici invece di path specifici'
        );
    }
    
    // Crawl delay alto
    if (!empty($results['robots']['crawl_delay'])) {
        foreach ($results['robots']['crawl_delay'] as $agent => $delay) {
            if ($delay > 5) {
                $recommendations[] = array(
                    'priority' => 'low',
                    'category' => 'performance',
                    'title' => 'Riduci crawl-delay',
                    'description' => "Crawl-delay di {$delay}s per {$agent} è troppo alto",
                    'solution' => 'Riduci a 1-2 secondi o rimuovi se non necessario'
                );
            }
        }
    }
    
    return $recommendations;
}

/**
 * Formatta dimensione in bytes
 * 
 * @param int $bytes Dimensione in bytes
 * @return string Dimensione formattata
 */
function formatBytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}
