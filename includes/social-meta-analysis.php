<?php
/**
 * Funzioni per l'analisi dei meta tag social e Open Graph
 * 
 * @package ControlDomini
 * @subpackage SEO
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Analizza i meta tag social di una pagina
 * 
 * @param string $url URL da analizzare
 * @return array Risultati analisi
 */
function analyzeSocialMetaTags($url) {
    $results = array(
        'url' => $url,
        'success' => false,
        'meta_tags' => array(
            'basic' => array(),
            'open_graph' => array(),
            'twitter' => array(),
            'schema_org' => array(),
            'other_social' => array()
        ),
        'preview' => array(
            'facebook' => array(),
            'twitter' => array(),
            'linkedin' => array(),
            'whatsapp' => array()
        ),
        'images' => array(),
        'issues' => array(),
        'score' => 0,
        'recommendations' => array()
    );
    
    // Recupera HTML della pagina
    $html_data = fetchPageHtml($url);
    
    if (!$html_data['success']) {
        $results['error'] = 'Impossibile recuperare la pagina';
        return $results;
    }
    
    $results['success'] = true;
    $html = $html_data['html'];
    
    // Estrai tutti i meta tag
    $results['meta_tags'] = extractAllMetaTags($html);
    
    // Analizza Open Graph
    $results['open_graph_analysis'] = analyzeOpenGraph($results['meta_tags']['open_graph']);
    
    // Analizza Twitter Cards
    $results['twitter_analysis'] = analyzeTwitterCards($results['meta_tags']['twitter']);
    
    // Analizza Schema.org
    $results['schema_analysis'] = analyzeSchemaOrg($html);
    
    // Estrai e analizza immagini social
    $results['images'] = analyzeSocialImages($results['meta_tags'], $url);
    
    // Genera preview per ogni piattaforma
    $results['preview'] = generateSocialPreviews($results['meta_tags'], $url);
    
    // Identifica problemi
    $results['issues'] = identifySocialMetaIssues($results);
    
    // Calcola score
    $results['score'] = calculateSocialMetaScore($results);
    
    // Genera raccomandazioni
    $results['recommendations'] = generateSocialMetaRecommendations($results);
    
    // Test di validazione
    $results['validation'] = validateSocialMeta($results);
    
    return $results;
}

/**
 * Recupera HTML della pagina
 * 
 * @param string $url URL
 * @return array Dati HTML
 */
function fetchPageHtml($url) {
    $result = array(
        'success' => false,
        'html' => '',
        'final_url' => $url
    );
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SocialMetaBot/1.0; +https://controllodomini.it)'
    ));
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 200 && $response) {
        $result['success'] = true;
        $result['html'] = $response;
        $result['final_url'] = $info['url'];
    }
    
    return $result;
}

/**
 * Estrae tutti i meta tag dalla pagina
 * 
 * @param string $html HTML
 * @return array Meta tags estratti
 */
function extractAllMetaTags($html) {
    $meta_tags = array(
        'basic' => array(),
        'open_graph' => array(),
        'twitter' => array(),
        'schema_org' => array(),
        'other_social' => array()
    );
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR);
    
    // Estrai meta tag base
    $metas = $dom->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        $name = $meta->getAttribute('name');
        $property = $meta->getAttribute('property');
        $content = $meta->getAttribute('content');
        
        if (empty($content)) continue;
        
        // Meta tag base
        if ($name) {
            $name_lower = strtolower($name);
            
            if (in_array($name_lower, array('description', 'keywords', 'author', 'viewport', 'robots'))) {
                $meta_tags['basic'][$name_lower] = $content;
            }
            
            // Twitter Cards
            elseif (strpos($name_lower, 'twitter:') === 0) {
                $key = str_replace('twitter:', '', $name_lower);
                $meta_tags['twitter'][$key] = $content;
            }
            
            // Altri social
            elseif (in_array($name_lower, array('pinterest', 'linkedin', 'medium'))) {
                $meta_tags['other_social'][$name_lower] = $content;
            }
        }
        
        // Open Graph (property)
        if ($property) {
            if (strpos($property, 'og:') === 0) {
                $key = str_replace('og:', '', $property);
                
                // Gestisci array per proprietà multiple (es. og:image)
                if (in_array($key, array('image', 'video', 'audio'))) {
                    if (!isset($meta_tags['open_graph'][$key])) {
                        $meta_tags['open_graph'][$key] = array();
                    }
                    $meta_tags['open_graph'][$key][] = $content;
                } else {
                    $meta_tags['open_graph'][$key] = $content;
                }
            }
            
            // Facebook specific
            elseif (strpos($property, 'fb:') === 0) {
                $key = str_replace('fb:', '', $property);
                $meta_tags['open_graph']['fb_' . $key] = $content;
            }
            
            // Article metadata
            elseif (strpos($property, 'article:') === 0) {
                $key = str_replace('article:', '', $property);
                $meta_tags['open_graph']['article_' . $key] = $content;
            }
        }
    }
    
    // Estrai title
    $titles = $dom->getElementsByTagName('title');
    if ($titles->length > 0) {
        $meta_tags['basic']['title'] = trim($titles->item(0)->textContent);
    }
    
    // Estrai canonical URL
    $links = $dom->getElementsByTagName('link');
    foreach ($links as $link) {
        if ($link->getAttribute('rel') === 'canonical') {
            $meta_tags['basic']['canonical'] = $link->getAttribute('href');
        }
    }
    
    return $meta_tags;
}

/**
 * Analizza Open Graph tags
 * 
 * @param array $og_tags Open Graph tags
 * @return array Analisi
 */
function analyzeOpenGraph($og_tags) {
    $analysis = array(
        'complete' => false,
        'valid' => true,
        'required_present' => array(),
        'optional_present' => array(),
        'missing_required' => array(),
        'issues' => array()
    );
    
    // Tag richiesti
    $required_tags = array('title', 'type', 'image', 'url');
    
    // Tag opzionali comuni
    $optional_tags = array(
        'description', 'site_name', 'locale', 'locale_alternate',
        'determiner', 'audio', 'video', 'article_author',
        'article_published_time', 'article_modified_time', 'article_section'
    );
    
    // Controlla tag richiesti
    foreach ($required_tags as $tag) {
        if (isset($og_tags[$tag]) && !empty($og_tags[$tag])) {
            $analysis['required_present'][] = $tag;
        } else {
            $analysis['missing_required'][] = $tag;
            $analysis['valid'] = false;
        }
    }
    
    // Controlla tag opzionali
    foreach ($optional_tags as $tag) {
        if (isset($og_tags[$tag]) && !empty($og_tags[$tag])) {
            $analysis['optional_present'][] = $tag;
        }
    }
    
    $analysis['complete'] = empty($analysis['missing_required']);
    
    // Validazione specifica
    
    // Tipo valido
    if (isset($og_tags['type'])) {
        $valid_types = array(
            'website', 'article', 'book', 'profile',
            'music.song', 'music.album', 'music.playlist',
            'video.movie', 'video.episode', 'video.tv_show'
        );
        
        if (!in_array($og_tags['type'], $valid_types)) {
            $analysis['issues'][] = array(
                'field' => 'og:type',
                'issue' => 'Tipo non valido: ' . $og_tags['type']
            );
        }
    }
    
    // Immagine
    if (isset($og_tags['image'])) {
        $images = is_array($og_tags['image']) ? $og_tags['image'] : array($og_tags['image']);
        
        foreach ($images as $image) {
            if (!filter_var($image, FILTER_VALIDATE_URL)) {
                $analysis['issues'][] = array(
                    'field' => 'og:image',
                    'issue' => 'URL immagine non valido'
                );
            }
        }
    }
    
    // URL
    if (isset($og_tags['url']) && !filter_var($og_tags['url'], FILTER_VALIDATE_URL)) {
        $analysis['issues'][] = array(
            'field' => 'og:url',
            'issue' => 'URL non valido'
        );
    }
    
    // Lunghezza testi
    if (isset($og_tags['title']) && strlen($og_tags['title']) > 90) {
        $analysis['issues'][] = array(
            'field' => 'og:title',
            'issue' => 'Titolo troppo lungo (' . strlen($og_tags['title']) . ' caratteri, max 90)'
        );
    }
    
    if (isset($og_tags['description']) && strlen($og_tags['description']) > 200) {
        $analysis['issues'][] = array(
            'field' => 'og:description',
            'issue' => 'Descrizione troppo lunga (' . strlen($og_tags['description']) . ' caratteri, max 200)'
        );
    }
    
    return $analysis;
}

/**
 * Analizza Twitter Cards
 * 
 * @param array $twitter_tags Twitter tags
 * @return array Analisi
 */
function analyzeTwitterCards($twitter_tags) {
    $analysis = array(
        'card_type' => null,
        'complete' => false,
        'valid' => true,
        'required_present' => array(),
        'missing_required' => array(),
        'issues' => array()
    );
    
    // Determina tipo di card
    $analysis['card_type'] = isset($twitter_tags['card']) ? $twitter_tags['card'] : null;
    
    if (!$analysis['card_type']) {
        $analysis['valid'] = false;
        $analysis['issues'][] = array(
            'field' => 'twitter:card',
            'issue' => 'Tag twitter:card mancante'
        );
        return $analysis;
    }
    
    // Requisiti per tipo di card
    $card_requirements = array(
        'summary' => array('title', 'description'),
        'summary_large_image' => array('title', 'description', 'image'),
        'app' => array('title', 'description', 'app:id:iphone', 'app:id:googleplay'),
        'player' => array('title', 'player', 'player:width', 'player:height')
    );
    
    // Controlla requisiti
    if (isset($card_requirements[$analysis['card_type']])) {
        $required = $card_requirements[$analysis['card_type']];
        
        foreach ($required as $tag) {
            if (isset($twitter_tags[$tag]) && !empty($twitter_tags[$tag])) {
                $analysis['required_present'][] = $tag;
            } else {
                $analysis['missing_required'][] = $tag;
                $analysis['valid'] = false;
            }
        }
    }
    
    $analysis['complete'] = empty($analysis['missing_required']);
    
    // Validazioni specifiche
    
    // Lunghezza titolo
    if (isset($twitter_tags['title']) && strlen($twitter_tags['title']) > 70) {
        $analysis['issues'][] = array(
            'field' => 'twitter:title',
            'issue' => 'Titolo troppo lungo per Twitter'
        );
    }
    
    // Lunghezza descrizione
    if (isset($twitter_tags['description']) && strlen($twitter_tags['description']) > 200) {
        $analysis['issues'][] = array(
            'field' => 'twitter:description',
            'issue' => 'Descrizione troppo lunga per Twitter'
        );
    }
    
    // Immagine
    if (isset($twitter_tags['image'])) {
        if (!filter_var($twitter_tags['image'], FILTER_VALIDATE_URL)) {
            $analysis['issues'][] = array(
                'field' => 'twitter:image',
                'issue' => 'URL immagine non valido'
            );
        }
    }
    
    // Site (deve iniziare con @)
    if (isset($twitter_tags['site']) && substr($twitter_tags['site'], 0, 1) !== '@') {
        $analysis['issues'][] = array(
            'field' => 'twitter:site',
            'issue' => 'Username Twitter deve iniziare con @'
        );
    }
    
    return $analysis;
}

/**
 * Analizza Schema.org
 * 
 * @param string $html HTML
 * @return array Analisi
 */
function analyzeSchemaOrg($html) {
    $analysis = array(
        'found' => false,
        'types' => array(),
        'json_ld' => array(),
        'microdata' => array(),
        'rdfa' => array(),
        'issues' => array()
    );
    
    // JSON-LD
    if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        $analysis['found'] = true;
        
        foreach ($matches[1] as $json_string) {
            $json = json_decode($json_string, true);
            if ($json) {
                $analysis['json_ld'][] = $json;
                
                // Estrai tipi
                if (isset($json['@type'])) {
                    $types = is_array($json['@type']) ? $json['@type'] : array($json['@type']);
                    $analysis['types'] = array_merge($analysis['types'], $types);
                }
                
                // Valida struttura base
                if (!isset($json['@context'])) {
                    $analysis['issues'][] = '@context mancante in JSON-LD';
                }
            } else {
                $analysis['issues'][] = 'JSON-LD non valido';
            }
        }
    }
    
    // Microdata
    if (strpos($html, 'itemscope') !== false) {
        $analysis['found'] = true;
        
        // Estrai tipi
        if (preg_match_all('/itemtype=["\']https?:\/\/schema\.org\/([^"\']+)["\']/', $html, $matches)) {
            $analysis['microdata'] = array_unique($matches[1]);
            $analysis['types'] = array_merge($analysis['types'], $analysis['microdata']);
        }
    }
    
    // RDFa
    if (strpos($html, 'typeof=') !== false || strpos($html, 'vocab=') !== false) {
        $analysis['found'] = true;
        $analysis['rdfa'] = true;
    }
    
    $analysis['types'] = array_unique($analysis['types']);
    
    return $analysis;
}

/**
 * Analizza immagini social
 * 
 * @param array $meta_tags Meta tags
 * @param string $base_url URL base
 * @return array Analisi immagini
 */
function analyzeSocialImages($meta_tags, $base_url) {
    $images = array();
    
    // Open Graph images
    if (isset($meta_tags['open_graph']['image'])) {
        $og_images = is_array($meta_tags['open_graph']['image']) ? 
                     $meta_tags['open_graph']['image'] : 
                     array($meta_tags['open_graph']['image']);
        
        foreach ($og_images as $image_url) {
            $image_data = analyzeSocialImage($image_url, $base_url, 'open_graph');
            if ($image_data) {
                $images[] = $image_data;
            }
        }
    }
    
    // Twitter image
    if (isset($meta_tags['twitter']['image'])) {
        $image_data = analyzeSocialImage($meta_tags['twitter']['image'], $base_url, 'twitter');
        if ($image_data) {
            $images[] = $image_data;
        }
    }
    
    return $images;
}

/**
 * Analizza singola immagine social
 * 
 * @param string $image_url URL immagine
 * @param string $base_url URL base
 * @param string $platform Piattaforma
 * @return array|null Dati immagine
 */
function analyzeSocialImage($image_url, $base_url, $platform) {
    // Risolvi URL relativo
    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
        $parsed_base = parse_url($base_url);
        if (strpos($image_url, '/') === 0) {
            $image_url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $image_url;
        } else {
            return null;
        }
    }
    
    $image_data = array(
        'url' => $image_url,
        'platform' => $platform,
        'accessible' => false,
        'size' => 0,
        'dimensions' => array('width' => 0, 'height' => 0),
        'format' => null,
        'issues' => array()
    );
    
    // Verifica accessibilità e dimensioni
    $ch = curl_init($image_url);
    curl_setopt_array($ch, array(
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ));
    
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] == 200) {
        $image_data['accessible'] = true;
        $image_data['size'] = $info['download_content_length'];
        
        // Ottieni dimensioni (richiede download parziale)
        $image_info = @getimagesize($image_url);
        if ($image_info) {
            $image_data['dimensions'] = array(
                'width' => $image_info[0],
                'height' => $image_info[1]
            );
            $image_data['format'] = image_type_to_mime_type($image_info[2]);
        }
        
        // Valida dimensioni per piattaforma
        validateImageDimensions($image_data, $platform);
    } else {
        $image_data['issues'][] = 'Immagine non accessibile (HTTP ' . $info['http_code'] . ')';
    }
    
    return $image_data;
}

/**
 * Valida dimensioni immagine per piattaforma
 * 
 * @param array &$image_data Dati immagine
 * @param string $platform Piattaforma
 */
function validateImageDimensions(&$image_data, $platform) {
    $requirements = array(
        'open_graph' => array(
            'min_width' => 1200,
            'min_height' => 630,
            'recommended_ratio' => 1.91,
            'max_size' => 8388608 // 8MB
        ),
        'twitter' => array(
            'min_width' => 800,
            'min_height' => 418,
            'recommended_ratio' => 1.91,
            'max_size' => 5242880 // 5MB
        )
    );
    
    if (!isset($requirements[$platform])) return;
    
    $req = $requirements[$platform];
    $width = $image_data['dimensions']['width'];
    $height = $image_data['dimensions']['height'];
    
    // Dimensioni minime
    if ($width < $req['min_width'] || $height < $req['min_height']) {
        $image_data['issues'][] = sprintf(
            'Dimensioni troppo piccole (%dx%d). Minimo richiesto: %dx%d',
            $width, $height, $req['min_width'], $req['min_height']
        );
    }
    
    // Aspect ratio
    if ($height > 0) {
        $ratio = $width / $height;
        if (abs($ratio - $req['recommended_ratio']) > 0.1) {
            $image_data['issues'][] = sprintf(
                'Aspect ratio non ottimale (%.2f:1). Consigliato: %.2f:1',
                $ratio, $req['recommended_ratio']
            );
        }
    }
    
    // Dimensione file
    if ($image_data['size'] > $req['max_size']) {
        $image_data['issues'][] = sprintf(
            'File troppo grande (%s). Massimo: %s',
            formatBytes($image_data['size']),
            formatBytes($req['max_size'])
        );
    }
}

/**
 * Genera preview per piattaforme social
 * 
 * @param array $meta_tags Meta tags
 * @param string $url URL
 * @return array Preview
 */
function generateSocialPreviews($meta_tags, $url) {
    $previews = array();
    
    // Facebook/Open Graph Preview
    $previews['facebook'] = array(
        'title' => $meta_tags['open_graph']['title'] ?? 
                   $meta_tags['basic']['title'] ?? 
                   'Senza titolo',
        'description' => $meta_tags['open_graph']['description'] ?? 
                         $meta_tags['basic']['description'] ?? 
                         '',
        'image' => isset($meta_tags['open_graph']['image']) ? 
                   (is_array($meta_tags['open_graph']['image']) ? 
                    $meta_tags['open_graph']['image'][0] : 
                    $meta_tags['open_graph']['image']) : 
                   null,
        'url' => $meta_tags['open_graph']['url'] ?? $url,
        'site_name' => $meta_tags['open_graph']['site_name'] ?? parse_url($url, PHP_URL_HOST)
    );
    
    // Twitter Preview
    $previews['twitter'] = array(
        'card_type' => $meta_tags['twitter']['card'] ?? 'summary',
        'title' => $meta_tags['twitter']['title'] ?? 
                   $previews['facebook']['title'],
        'description' => $meta_tags['twitter']['description'] ?? 
                         $previews['facebook']['description'],
        'image' => $meta_tags['twitter']['image'] ?? 
                   $previews['facebook']['image'],
        'site' => $meta_tags['twitter']['site'] ?? null,
        'creator' => $meta_tags['twitter']['creator'] ?? null
    );
    
    // LinkedIn Preview
    $previews['linkedin'] = array(
        'title' => $previews['facebook']['title'],
        'description' => substr($previews['facebook']['description'], 0, 150),
        'image' => $previews['facebook']['image'],
        'source' => $previews['facebook']['site_name']
    );
    
    // WhatsApp Preview
    $previews['whatsapp'] = array(
        'title' => $previews['facebook']['title'],
        'description' => substr($previews['facebook']['description'], 0, 120),
        'image' => $previews['facebook']['image'],
        'url' => $url
    );
    
    return $previews;
}

/**
 * Identifica problemi nei meta tag social
 * 
 * @param array $results Risultati analisi
 * @return array Issues
 */
function identifySocialMetaIssues($results) {
    $issues = array();
    
    // Open Graph issues
    if (!$results['open_graph_analysis']['complete']) {
        $issues[] = array(
            'type' => 'open_graph_incomplete',
            'severity' => 'high',
            'message' => 'Open Graph tags incompleti',
            'details' => 'Mancano: ' . implode(', ', $results['open_graph_analysis']['missing_required'])
        );
    }
    
    // Twitter Cards issues
    if ($results['twitter_analysis']['card_type'] && !$results['twitter_analysis']['complete']) {
        $issues[] = array(
            'type' => 'twitter_incomplete',
            'severity' => 'medium',
            'message' => 'Twitter Card incompleta',
            'details' => 'Mancano: ' . implode(', ', $results['twitter_analysis']['missing_required'])
        );
    }
    
    // Nessun Twitter Card
    if (!$results['twitter_analysis']['card_type']) {
        $issues[] = array(
            'type' => 'twitter_missing',
            'severity' => 'medium',
            'message' => 'Twitter Card non configurata',
            'details' => 'Aggiungi meta tag Twitter per migliorare la condivisione'
        );
    }
    
    // Schema.org mancante
    if (!$results['schema_analysis']['found']) {
        $issues[] = array(
            'type' => 'schema_missing',
            'severity' => 'low',
            'message' => 'Dati strutturati Schema.org non trovati',
            'details' => 'Implementa Schema.org per migliorare la comprensione dai motori di ricerca'
        );
    }
    
    // Problemi immagini
    foreach ($results['images'] as $image) {
        if (!empty($image['issues'])) {
            foreach ($image['issues'] as $image_issue) {
                $issues[] = array(
                    'type' => 'image_issue',
                    'severity' => 'medium',
                    'message' => 'Problema immagine ' . $image['platform'],
                    'details' => $image_issue
                );
            }
        }
    }
    
    // Lunghezza contenuti
    if (isset($results['meta_tags']['basic']['description'])) {
        $desc_length = strlen($results['meta_tags']['basic']['description']);
        if ($desc_length < 120) {
            $issues[] = array(
                'type' => 'description_short',
                'severity' => 'low',
                'message' => 'Descrizione troppo breve',
                'details' => "Solo $desc_length caratteri, consigliati almeno 120"
            );
        } elseif ($desc_length > 160) {
            $issues[] = array(
                'type' => 'description_long',
                'severity' => 'low',
                'message' => 'Descrizione troppo lunga',
                'details' => "$desc_length caratteri, massimo consigliato 160"
            );
        }
    }
    
    // Canonical mancante
    if (!isset($results['meta_tags']['basic']['canonical'])) {
        $issues[] = array(
            'type' => 'canonical_missing',
            'severity' => 'medium',
            'message' => 'URL canonical non specificato',
            'details' => 'Aggiungi link canonical per evitare contenuti duplicati'
        );
    }
    
    return $issues;
}

/**
 * Calcola score meta tag social
 * 
 * @param array $results Risultati
 * @return int Score (0-100)
 */
function calculateSocialMetaScore($results) {
    $score = 100;
    
    // Open Graph (35 punti)
    if (!$results['open_graph_analysis']['complete']) {
        $missing = count($results['open_graph_analysis']['missing_required']);
        $score -= $missing * 8;
    }
    
    // Twitter Cards (25 punti)
    if (!$results['twitter_analysis']['card_type']) {
        $score -= 25;
    } elseif (!$results['twitter_analysis']['complete']) {
        $score -= 15;
    }
    
    // Schema.org (15 punti)
    if (!$results['schema_analysis']['found']) {
        $score -= 15;
    }
    
    // Immagini (15 punti)
    $image_issues = 0;
    foreach ($results['images'] as $image) {
        $image_issues += count($image['issues']);
    }
    if ($image_issues > 0) {
        $score -= min(15, $image_issues * 5);
    }
    
    // Meta base (10 punti)
    if (!isset($results['meta_tags']['basic']['description'])) {
        $score -= 5;
    }
    if (!isset($results['meta_tags']['basic']['canonical'])) {
        $score -= 5;
    }
    
    return max(0, $score);
}

/**
 * Genera raccomandazioni per meta tag social
 * 
 * @param array $results Risultati
 * @return array Raccomandazioni
 */
function generateSocialMetaRecommendations($results) {
    $recommendations = array();
    
    // Open Graph
    if (!$results['open_graph_analysis']['complete']) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'open_graph',
            'title' => 'Completa i tag Open Graph',
            'description' => 'Aggiungi i tag Open Graph mancanti per migliorare la condivisione su Facebook e altre piattaforme',
            'actions' => array_map(function($tag) {
                return "Aggiungi <meta property=\"og:$tag\" content=\"...\">";
            }, $results['open_graph_analysis']['missing_required'])
        );
    }
    
    // Twitter Cards
    if (!$results['twitter_analysis']['card_type']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'twitter',
            'title' => 'Implementa Twitter Cards',
            'description' => 'Configura Twitter Cards per migliorare l\'aspetto dei tuoi link su Twitter',
            'actions' => array(
                'Aggiungi <meta name="twitter:card" content="summary_large_image">',
                'Aggiungi <meta name="twitter:title" content="...">',
                'Aggiungi <meta name="twitter:description" content="...">',
                'Aggiungi <meta name="twitter:image" content="...">'
            )
        );
    }
    
    // Schema.org
    if (!$results['schema_analysis']['found']) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'schema',
            'title' => 'Implementa dati strutturati Schema.org',
            'description' => 'Aggiungi markup Schema.org per aiutare i motori di ricerca a comprendere meglio il tuo contenuto',
            'actions' => array(
                'Usa JSON-LD per implementare Schema.org',
                'Scegli il tipo appropriato (Article, Product, Organization, etc.)',
                'Valida con Google Structured Data Testing Tool'
            )
        );
    }
    
    // Immagini
    $has_image_issues = false;
    foreach ($results['images'] as $image) {
        if (!empty($image['issues'])) {
            $has_image_issues = true;
            break;
        }
    }
    
    if ($has_image_issues) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'images',
            'title' => 'Ottimizza le immagini social',
            'description' => 'Le immagini social non rispettano le specifiche consigliate',
            'actions' => array(
                'Usa immagini di almeno 1200x630px per Open Graph',
                'Mantieni un aspect ratio di 1.91:1',
                'Comprimi le immagini sotto i 5MB',
                'Usa formati JPEG o PNG'
            )
        );
    }
    
    // Descrizione
    if (!isset($results['meta_tags']['basic']['description'])) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'basic',
            'title' => 'Aggiungi meta description',
            'description' => 'La meta description è fondamentale per SEO e condivisione social',
            'actions' => array(
                'Aggiungi <meta name="description" content="...">',
                'Mantieni tra 120-160 caratteri',
                'Includi parole chiave rilevanti'
            )
        );
    }
    
    return $recommendations;
}

/**
 * Valida meta tag social
 * 
 * @param array $results Risultati
 * @return array Risultati validazione
 */
function validateSocialMeta($results) {
    $validation = array(
        'facebook_debugger' => generateDebuggerUrl('facebook', $results['url']),
        'twitter_validator' => generateDebuggerUrl('twitter', $results['url']),
        'linkedin_inspector' => generateDebuggerUrl('linkedin', $results['url']),
        'structured_data_test' => generateDebuggerUrl('google', $results['url']),
        'passed_tests' => array(),
        'failed_tests' => array()
    );
    
    // Test Open Graph
    if ($results['open_graph_analysis']['complete']) {
        $validation['passed_tests'][] = 'Open Graph completo';
    } else {
        $validation['failed_tests'][] = 'Open Graph incompleto';
    }
    
    // Test Twitter
    if ($results['twitter_analysis']['complete']) {
        $validation['passed_tests'][] = 'Twitter Card valida';
    } else {
        $validation['failed_tests'][] = 'Twitter Card non valida';
    }
    
    // Test immagini
    $valid_images = 0;
    foreach ($results['images'] as $image) {
        if ($image['accessible'] && empty($image['issues'])) {
            $valid_images++;
        }
    }
    
    if ($valid_images > 0) {
        $validation['passed_tests'][] = "$valid_images immagini social valide";
    }
    
    // Test Schema.org
    if ($results['schema_analysis']['found'] && empty($results['schema_analysis']['issues'])) {
        $validation['passed_tests'][] = 'Schema.org presente';
    }
    
    return $validation;
}

/**
 * Genera URL per debugger esterni
 * 
 * @param string $platform Piattaforma
 * @param string $url URL da testare
 * @return string URL debugger
 */
function generateDebuggerUrl($platform, $url) {
    $debuggers = array(
        'facebook' => 'https://developers.facebook.com/tools/debug/?q=' . urlencode($url),
        'twitter' => 'https://cards-dev.twitter.com/validator',
        'linkedin' => 'https://www.linkedin.com/post-inspector/inspect/' . urlencode($url),
        'google' => 'https://search.google.com/test/rich-results?url=' . urlencode($url)
    );
    
    return $debuggers[$platform] ?? '#';
}

/**
 * Estrae e analizza tutti i social link
 * 
 * @param string $html HTML
 * @return array Social links trovati
 */
function extractSocialLinks($html) {
    $social_links = array();
    
    $social_patterns = array(
        'facebook' => array('facebook.com/', 'fb.com/'),
        'twitter' => array('twitter.com/', 'x.com/'),
        'instagram' => array('instagram.com/'),
        'linkedin' => array('linkedin.com/'),
        'youtube' => array('youtube.com/', 'youtu.be/'),
        'pinterest' => array('pinterest.com/'),
        'tiktok' => array('tiktok.com/'),
        'github' => array('github.com/'),
        'telegram' => array('t.me/', 'telegram.me/')
    );
    
    // Estrai tutti i link
    if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
        foreach ($matches[1] as $url) {
            foreach ($social_patterns as $platform => $patterns) {
                foreach ($patterns as $pattern) {
                    if (strpos($url, $pattern) !== false) {
                        $social_links[$platform][] = $url;
                        break 2;
                    }
                }
            }
        }
    }
    
    // Rimuovi duplicati
    foreach ($social_links as $platform => $links) {
        $social_links[$platform] = array_unique($links);
    }
    
    return $social_links;
}
