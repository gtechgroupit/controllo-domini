<?php
/**
 * Advanced SEO Analysis for Web Agencies
 *
 * Comprehensive SEO analysis including meta tags, structured data,
 * social media optimization, and technical SEO
 *
 * @package ControlloDomin
 * @version 4.3.0
 */

require_once __DIR__ . '/utilities.php';

class AdvancedSEO {
    private $url;
    private $html;
    private $dom;
    private $xpath;
    private $headers;

    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Perform complete SEO analysis
     */
    public function analyze() {
        $this->fetchPage();

        return [
            'meta_tags' => $this->getMetaTags(),
            'structured_data' => $this->getStructuredData(),
            'social_media' => $this->getSocialMediaTags(),
            'headings' => $this->getHeadings(),
            'links' => $this->getLinkAnalysis(),
            'images' => $this->getImageAnalysis(),
            'content' => $this->getContentAnalysis(),
            'technical_seo' => $this->getTechnicalSEO(),
            'mobile_seo' => $this->getMobileSEO(),
            'international_seo' => $this->getInternationalSEO(),
            'indexability' => $this->getIndexability(),
            'page_speed_indicators' => $this->getPageSpeedIndicators(),
            'seo_score' => $this->calculateSEOScore()
        ];
    }

    /**
     * Fetch page content
     */
    private function fetchPage() {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (compatible; SEOBot/1.0; +https://controllodomini.it)\r\n",
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 3
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $this->html = @file_get_contents($this->url, false, $context);

        if ($this->html) {
            $this->dom = new DOMDocument();
            @$this->dom->loadHTML($this->html);
            $this->xpath = new DOMXPath($this->dom);

            // Get headers
            $this->headers = $http_response_header ?? [];
        }
    }

    /**
     * Get all meta tags
     */
    private function getMetaTags() {
        $meta_tags = [
            'title' => $this->getTitle(),
            'description' => $this->getMetaContent('description'),
            'keywords' => $this->getMetaContent('keywords'),
            'robots' => $this->getMetaContent('robots'),
            'author' => $this->getMetaContent('author'),
            'viewport' => $this->getMetaContent('viewport'),
            'canonical' => $this->getCanonical(),
            'alternate' => $this->getAlternateLinks(),
            'prev_next' => $this->getPrevNextLinks(),
            'language' => $this->getLanguage(),
            'charset' => $this->getCharset(),
            'theme_color' => $this->getMetaContent('theme-color'),
            'generator' => $this->getMetaContent('generator'),
            'all_meta' => $this->getAllMeta()
        ];

        // Add quality checks
        $meta_tags['quality'] = [
            'title_length' => strlen($meta_tags['title']),
            'title_optimal' => strlen($meta_tags['title']) >= 30 && strlen($meta_tags['title']) <= 60,
            'description_length' => strlen($meta_tags['description']),
            'description_optimal' => strlen($meta_tags['description']) >= 120 && strlen($meta_tags['description']) <= 160,
            'has_keywords' => !empty($meta_tags['keywords']),
            'has_canonical' => !empty($meta_tags['canonical']),
            'has_viewport' => !empty($meta_tags['viewport']),
            'mobile_friendly_meta' => !empty($meta_tags['viewport'])
        ];

        return $meta_tags;
    }

    /**
     * Get structured data (Schema.org, JSON-LD, Microdata)
     */
    private function getStructuredData() {
        $structured_data = [
            'json_ld' => $this->getJSONLD(),
            'microdata' => $this->getMicrodata(),
            'rdfa' => $this->getRDFa(),
            'open_graph' => $this->getOpenGraph(),
            'twitter_card' => $this->getTwitterCard(),
            'breadcrumbs' => $this->getBreadcrumbsSchema()
        ];

        $structured_data['summary'] = [
            'has_json_ld' => !empty($structured_data['json_ld']),
            'has_microdata' => !empty($structured_data['microdata']),
            'has_open_graph' => !empty($structured_data['open_graph']),
            'has_twitter_card' => !empty($structured_data['twitter_card']),
            'total_schemas' => count($structured_data['json_ld']) + count($structured_data['microdata'])
        ];

        return $structured_data;
    }

    /**
     * Get JSON-LD structured data
     */
    private function getJSONLD() {
        $json_ld = [];
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');

        foreach ($scripts as $script) {
            $json = json_decode($script->textContent, true);
            if ($json) {
                $json_ld[] = $json;
            }
        }

        return $json_ld;
    }

    /**
     * Get microdata
     */
    private function getMicrodata() {
        $microdata = [];
        $items = $this->xpath->query('//*[@itemscope]');

        foreach ($items as $item) {
            $itemtype = $item->getAttribute('itemtype');
            if ($itemtype) {
                $microdata[] = [
                    'type' => $itemtype,
                    'properties' => $this->extractMicrodataProperties($item)
                ];
            }
        }

        return $microdata;
    }

    /**
     * Extract microdata properties
     */
    private function extractMicrodataProperties($element) {
        $properties = [];
        $props = $this->xpath->query('.//*[@itemprop]', $element);

        foreach ($props as $prop) {
            $name = $prop->getAttribute('itemprop');
            $value = $prop->getAttribute('content') ?: $prop->textContent;
            $properties[$name] = trim($value);
        }

        return $properties;
    }

    /**
     * Get RDFa data
     */
    private function getRDFa() {
        $rdfa = [];
        $elements = $this->xpath->query('//*[@typeof]');

        foreach ($elements as $element) {
            $rdfa[] = [
                'type' => $element->getAttribute('typeof'),
                'properties' => $this->extractRDFaProperties($element)
            ];
        }

        return $rdfa;
    }

    /**
     * Extract RDFa properties
     */
    private function extractRDFaProperties($element) {
        $properties = [];
        $props = $this->xpath->query('.//*[@property]', $element);

        foreach ($props as $prop) {
            $name = $prop->getAttribute('property');
            $value = $prop->getAttribute('content') ?: $prop->textContent;
            $properties[$name] = trim($value);
        }

        return $properties;
    }

    /**
     * Get Open Graph data
     */
    private function getOpenGraph() {
        $og = [];
        $metas = $this->xpath->query('//meta[starts-with(@property, "og:")]');

        foreach ($metas as $meta) {
            $property = str_replace('og:', '', $meta->getAttribute('property'));
            $og[$property] = $meta->getAttribute('content');
        }

        return $og;
    }

    /**
     * Get Twitter Card data
     */
    private function getTwitterCard() {
        $twitter = [];
        $metas = $this->xpath->query('//meta[starts-with(@name, "twitter:")]');

        foreach ($metas as $meta) {
            $name = str_replace('twitter:', '', $meta->getAttribute('name'));
            $twitter[$name] = $meta->getAttribute('content');
        }

        return $twitter;
    }

    /**
     * Get breadcrumbs schema
     */
    private function getBreadcrumbsSchema() {
        $breadcrumbs = [];

        // Try JSON-LD first
        $scripts = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $script) {
            $json = json_decode($script->textContent, true);
            if ($json && isset($json['@type']) && $json['@type'] === 'BreadcrumbList') {
                $breadcrumbs = $json;
                break;
            }
        }

        return $breadcrumbs;
    }

    /**
     * Get social media tags and profiles
     */
    private function getSocialMediaTags() {
        return [
            'open_graph' => $this->getOpenGraph(),
            'twitter_card' => $this->getTwitterCard(),
            'social_profiles' => $this->extractSocialProfiles(),
            'social_share_buttons' => $this->detectSocialShareButtons(),
            'schema_social' => $this->getSchemaSocialProfiles()
        ];
    }

    /**
     * Extract social media profiles from links
     */
    private function extractSocialProfiles() {
        $profiles = [];
        $social_domains = [
            'facebook.com' => 'Facebook',
            'twitter.com' => 'Twitter',
            'x.com' => 'Twitter/X',
            'instagram.com' => 'Instagram',
            'linkedin.com' => 'LinkedIn',
            'youtube.com' => 'YouTube',
            'tiktok.com' => 'TikTok',
            'pinterest.com' => 'Pinterest',
            'github.com' => 'GitHub',
            'medium.com' => 'Medium',
            'whatsapp.com' => 'WhatsApp',
            'telegram.me' => 'Telegram',
            'snapchat.com' => 'Snapchat',
            'reddit.com' => 'Reddit',
            'vimeo.com' => 'Vimeo'
        ];

        $links = $this->xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            foreach ($social_domains as $domain => $platform) {
                if (strpos($href, $domain) !== false) {
                    $profiles[$platform] = $href;
                    break;
                }
            }
        }

        return $profiles;
    }

    /**
     * Detect social share buttons
     */
    private function detectSocialShareButtons() {
        $buttons = [
            'facebook_share' => false,
            'twitter_share' => false,
            'linkedin_share' => false,
            'pinterest_share' => false,
            'whatsapp_share' => false,
            'email_share' => false
        ];

        $html_lower = strtolower($this->html);

        if (strpos($html_lower, 'facebook.com/sharer') !== false ||
            strpos($html_lower, 'fb-share') !== false) {
            $buttons['facebook_share'] = true;
        }

        if (strpos($html_lower, 'twitter.com/intent/tweet') !== false ||
            strpos($html_lower, 'twitter-share') !== false) {
            $buttons['twitter_share'] = true;
        }

        if (strpos($html_lower, 'linkedin.com/shareArticle') !== false) {
            $buttons['linkedin_share'] = true;
        }

        if (strpos($html_lower, 'pinterest.com/pin/create') !== false) {
            $buttons['pinterest_share'] = true;
        }

        if (strpos($html_lower, 'wa.me') !== false || strpos($html_lower, 'whatsapp://') !== false) {
            $buttons['whatsapp_share'] = true;
        }

        return $buttons;
    }

    /**
     * Get social profiles from schema
     */
    private function getSchemaSocialProfiles() {
        $profiles = [];
        $json_ld = $this->getJSONLD();

        foreach ($json_ld as $schema) {
            if (isset($schema['sameAs'])) {
                $profiles = array_merge($profiles, (array)$schema['sameAs']);
            }
            if (isset($schema['social'])) {
                $profiles = array_merge($profiles, (array)$schema['social']);
            }
        }

        return array_unique($profiles);
    }

    /**
     * Analyze headings structure
     */
    private function getHeadings() {
        $headings = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => []
        ];

        for ($i = 1; $i <= 6; $i++) {
            $tags = $this->xpath->query("//h{$i}");
            foreach ($tags as $tag) {
                $headings["h{$i}"][] = trim($tag->textContent);
            }
        }

        return [
            'structure' => $headings,
            'analysis' => [
                'h1_count' => count($headings['h1']),
                'h1_optimal' => count($headings['h1']) === 1,
                'h2_count' => count($headings['h2']),
                'total_headings' => array_sum(array_map('count', $headings)),
                'hierarchy_proper' => $this->checkHeadingHierarchy($headings)
            ]
        ];
    }

    /**
     * Check if heading hierarchy is proper
     */
    private function checkHeadingHierarchy($headings) {
        // Check if h1 exists
        if (empty($headings['h1'])) {
            return false;
        }

        // Check if only one h1
        if (count($headings['h1']) > 1) {
            return false;
        }

        return true;
    }

    /**
     * Analyze links
     */
    private function getLinkAnalysis() {
        $links = $this->xpath->query('//a[@href]');

        $internal = 0;
        $external = 0;
        $nofollow = 0;
        $broken_hints = 0;
        $all_links = [];

        $domain = parse_url($this->url, PHP_URL_HOST);

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $rel = $link->getAttribute('rel');
            $text = trim($link->textContent);

            // Skip anchors and javascript
            if (empty($href) || $href[0] === '#' || strpos($href, 'javascript:') === 0) {
                continue;
            }

            $link_data = [
                'url' => $href,
                'text' => $text,
                'rel' => $rel
            ];

            // Check if internal or external
            $link_domain = parse_url($href, PHP_URL_HOST);
            if ($link_domain === null || $link_domain === $domain) {
                $internal++;
                $link_data['type'] = 'internal';
            } else {
                $external++;
                $link_data['type'] = 'external';
            }

            // Check nofollow
            if (strpos($rel, 'nofollow') !== false) {
                $nofollow++;
                $link_data['nofollow'] = true;
            }

            $all_links[] = $link_data;
        }

        return [
            'total' => count($all_links),
            'internal' => $internal,
            'external' => $external,
            'nofollow' => $nofollow,
            'ratio' => $internal > 0 ? round($external / $internal, 2) : 0,
            'links' => $all_links
        ];
    }

    /**
     * Analyze images
     */
    private function getImageAnalysis() {
        $images = $this->xpath->query('//img');

        $total = 0;
        $with_alt = 0;
        $without_alt = 0;
        $lazy_load = 0;
        $responsive = 0;
        $all_images = [];

        foreach ($images as $img) {
            $total++;
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $loading = $img->getAttribute('loading');
            $srcset = $img->getAttribute('srcset');

            $image_data = [
                'src' => $src,
                'alt' => $alt,
                'has_alt' => !empty($alt)
            ];

            if (!empty($alt)) {
                $with_alt++;
            } else {
                $without_alt++;
            }

            if ($loading === 'lazy') {
                $lazy_load++;
                $image_data['lazy'] = true;
            }

            if (!empty($srcset)) {
                $responsive++;
                $image_data['responsive'] = true;
            }

            $all_images[] = $image_data;
        }

        return [
            'total' => $total,
            'with_alt' => $with_alt,
            'without_alt' => $without_alt,
            'alt_percentage' => $total > 0 ? round(($with_alt / $total) * 100, 2) : 0,
            'lazy_load' => $lazy_load,
            'responsive' => $responsive,
            'images' => array_slice($all_images, 0, 20) // Limit to first 20
        ];
    }

    /**
     * Analyze content
     */
    private function getContentAnalysis() {
        $body = $this->xpath->query('//body');
        $text = '';

        if ($body->length > 0) {
            $text = $body->item(0)->textContent;
        }

        $text = trim(preg_replace('/\s+/', ' ', $text));
        $words = str_word_count($text);
        $chars = strlen($text);

        return [
            'word_count' => $words,
            'character_count' => $chars,
            'reading_time' => ceil($words / 200), // minutes
            'text_html_ratio' => strlen($this->html) > 0 ? round(($chars / strlen($this->html)) * 100, 2) : 0,
            'has_sufficient_content' => $words >= 300
        ];
    }

    /**
     * Technical SEO checks
     */
    private function getTechnicalSEO() {
        return [
            'https' => strpos($this->url, 'https://') === 0,
            'www_redirect' => $this->checkWWWRedirect(),
            'sitemap_xml' => $this->checkSitemapXML(),
            'robots_txt' => $this->checkRobotsTxt(),
            'favicon' => $this->checkFavicon(),
            'canonical_proper' => $this->checkCanonicalProper(),
            'pagination' => $this->hasPagination(),
            'amp' => $this->hasAMP(),
            'structured_data_valid' => !empty($this->getJSONLD())
        ];
    }

    /**
     * Check WWW redirect
     */
    private function checkWWWRedirect() {
        // This would need actual HTTP request to test
        return null; // Placeholder
    }

    /**
     * Check sitemap.xml existence
     */
    private function checkSitemapXML() {
        $domain = parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST);
        $sitemap_url = $domain . '/sitemap.xml';

        $headers = @get_headers($sitemap_url);
        return $headers && strpos($headers[0], '200') !== false;
    }

    /**
     * Check robots.txt existence
     */
    private function checkRobotsTxt() {
        $domain = parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST);
        $robots_url = $domain . '/robots.txt';

        $headers = @get_headers($robots_url);
        return $headers && strpos($headers[0], '200') !== false;
    }

    /**
     * Check favicon
     */
    private function checkFavicon() {
        $icons = $this->xpath->query('//link[@rel="icon" or @rel="shortcut icon" or @rel="apple-touch-icon"]');
        return $icons->length > 0;
    }

    /**
     * Check if canonical is proper
     */
    private function checkCanonicalProper() {
        $canonical = $this->getCanonical();
        if (empty($canonical)) {
            return false;
        }

        // Check if canonical points to same domain
        $canonical_domain = parse_url($canonical, PHP_URL_HOST);
        $page_domain = parse_url($this->url, PHP_URL_HOST);

        return $canonical_domain === $page_domain;
    }

    /**
     * Check pagination
     */
    private function hasPagination() {
        $prev = $this->xpath->query('//link[@rel="prev"]');
        $next = $this->xpath->query('//link[@rel="next"]');

        return $prev->length > 0 || $next->length > 0;
    }

    /**
     * Check AMP
     */
    private function hasAMP() {
        $amp = $this->xpath->query('//link[@rel="amphtml"]');
        return $amp->length > 0;
    }

    /**
     * Mobile SEO checks
     */
    private function getMobileSEO() {
        return [
            'viewport_meta' => !empty($this->getMetaContent('viewport')),
            'mobile_optimized' => $this->isMobileOptimized(),
            'tap_targets' => $this->checkTapTargets(),
            'font_size' => $this->checkFontSize()
        ];
    }

    /**
     * Check if mobile optimized
     */
    private function isMobileOptimized() {
        $viewport = $this->getMetaContent('viewport');
        return strpos($viewport, 'width=device-width') !== false;
    }

    /**
     * Check tap targets (basic)
     */
    private function checkTapTargets() {
        // Would need actual rendering to properly check
        return null; // Placeholder
    }

    /**
     * Check font size (basic)
     */
    private function checkFontSize() {
        // Would need actual rendering to properly check
        return null; // Placeholder
    }

    /**
     * International SEO
     */
    private function getInternationalSEO() {
        return [
            'hreflang' => $this->getHreflang(),
            'language' => $this->getLanguage(),
            'alternate_languages' => $this->getAlternateLinks()
        ];
    }

    /**
     * Get hreflang tags
     */
    private function getHreflang() {
        $hreflang = [];
        $links = $this->xpath->query('//link[@rel="alternate"][@hreflang]');

        foreach ($links as $link) {
            $hreflang[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }

        return $hreflang;
    }

    /**
     * Get indexability status
     */
    private function getIndexability() {
        $robots = $this->getMetaContent('robots');

        return [
            'robots_meta' => $robots,
            'indexable' => strpos($robots, 'noindex') === false,
            'followable' => strpos($robots, 'nofollow') === false,
            'x_robots_tag' => $this->getXRobotsTag()
        ];
    }

    /**
     * Get X-Robots-Tag header
     */
    private function getXRobotsTag() {
        foreach ($this->headers as $header) {
            if (stripos($header, 'x-robots-tag:') === 0) {
                return trim(substr($header, 14));
            }
        }
        return null;
    }

    /**
     * Get page speed indicators from HTML
     */
    private function getPageSpeedIndicators() {
        return [
            'defer_scripts' => $this->countDeferScripts(),
            'async_scripts' => $this->countAsyncScripts(),
            'inline_styles' => $this->countInlineStyles(),
            'external_styles' => $this->countExternalStyles(),
            'external_scripts' => $this->countExternalScripts(),
            'total_resources' => $this->countTotalResources()
        ];
    }

    /**
     * Count defer scripts
     */
    private function countDeferScripts() {
        return $this->xpath->query('//script[@defer]')->length;
    }

    /**
     * Count async scripts
     */
    private function countAsyncScripts() {
        return $this->xpath->query('//script[@async]')->length;
    }

    /**
     * Count inline styles
     */
    private function countInlineStyles() {
        return $this->xpath->query('//style')->length;
    }

    /**
     * Count external styles
     */
    private function countExternalStyles() {
        return $this->xpath->query('//link[@rel="stylesheet"]')->length;
    }

    /**
     * Count external scripts
     */
    private function countExternalScripts() {
        return $this->xpath->query('//script[@src]')->length;
    }

    /**
     * Count total resources
     */
    private function countTotalResources() {
        $images = $this->xpath->query('//img')->length;
        $scripts = $this->xpath->query('//script[@src]')->length;
        $styles = $this->xpath->query('//link[@rel="stylesheet"]')->length;

        return $images + $scripts + $styles;
    }

    /**
     * Calculate overall SEO score
     */
    private function calculateSEOScore() {
        $score = 100;
        $issues = [];

        // Title checks
        $title = $this->getTitle();
        if (empty($title)) {
            $score -= 10;
            $issues[] = 'Missing title tag';
        } elseif (strlen($title) < 30 || strlen($title) > 60) {
            $score -= 5;
            $issues[] = 'Title length not optimal (30-60 chars)';
        }

        // Description checks
        $description = $this->getMetaContent('description');
        if (empty($description)) {
            $score -= 10;
            $issues[] = 'Missing meta description';
        } elseif (strlen($description) < 120 || strlen($description) > 160) {
            $score -= 5;
            $issues[] = 'Description length not optimal (120-160 chars)';
        }

        // H1 checks
        $h1 = $this->xpath->query('//h1');
        if ($h1->length === 0) {
            $score -= 10;
            $issues[] = 'Missing H1 tag';
        } elseif ($h1->length > 1) {
            $score -= 5;
            $issues[] = 'Multiple H1 tags found';
        }

        // Canonical check
        if (empty($this->getCanonical())) {
            $score -= 5;
            $issues[] = 'Missing canonical URL';
        }

        // HTTPS check
        if (strpos($this->url, 'https://') !== 0) {
            $score -= 10;
            $issues[] = 'Not using HTTPS';
        }

        // Image alt checks
        $images = $this->xpath->query('//img');
        $images_without_alt = 0;
        foreach ($images as $img) {
            if (empty($img->getAttribute('alt'))) {
                $images_without_alt++;
            }
        }
        if ($images_without_alt > 0) {
            $score -= min(10, $images_without_alt);
            $issues[] = "$images_without_alt images without alt text";
        }

        // Structured data check
        if (empty($this->getJSONLD())) {
            $score -= 5;
            $issues[] = 'No structured data found';
        }

        return [
            'score' => max(0, $score),
            'grade' => $this->getScoreGrade($score),
            'issues' => $issues,
            'total_checks' => 8
        ];
    }

    /**
     * Get score grade
     */
    private function getScoreGrade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    // Helper methods

    private function getTitle() {
        $title = $this->xpath->query('//title');
        return $title->length > 0 ? trim($title->item(0)->textContent) : '';
    }

    private function getMetaContent($name) {
        $meta = $this->xpath->query("//meta[@name='$name']");
        if ($meta->length > 0) {
            return $meta->item(0)->getAttribute('content');
        }
        return '';
    }

    private function getCanonical() {
        $canonical = $this->xpath->query('//link[@rel="canonical"]');
        return $canonical->length > 0 ? $canonical->item(0)->getAttribute('href') : '';
    }

    private function getAlternateLinks() {
        $alternates = [];
        $links = $this->xpath->query('//link[@rel="alternate"]');

        foreach ($links as $link) {
            $hreflang = $link->getAttribute('hreflang');
            if ($hreflang) {
                $alternates[$hreflang] = $link->getAttribute('href');
            }
        }

        return $alternates;
    }

    private function getPrevNextLinks() {
        return [
            'prev' => $this->getLinkHref('prev'),
            'next' => $this->getLinkHref('next')
        ];
    }

    private function getLinkHref($rel) {
        $link = $this->xpath->query("//link[@rel='$rel']");
        return $link->length > 0 ? $link->item(0)->getAttribute('href') : null;
    }

    private function getLanguage() {
        $html = $this->xpath->query('//html');
        if ($html->length > 0) {
            return $html->item(0)->getAttribute('lang');
        }
        return '';
    }

    private function getCharset() {
        $meta = $this->xpath->query('//meta[@charset]');
        if ($meta->length > 0) {
            return $meta->item(0)->getAttribute('charset');
        }

        // Try http-equiv
        $meta = $this->xpath->query('//meta[@http-equiv="Content-Type"]');
        if ($meta->length > 0) {
            $content = $meta->item(0)->getAttribute('content');
            if (preg_match('/charset=([^;]+)/', $content, $matches)) {
                return $matches[1];
            }
        }

        return 'UTF-8'; // Default
    }

    private function getAllMeta() {
        $all_meta = [];
        $metas = $this->xpath->query('//meta');

        foreach ($metas as $meta) {
            $name = $meta->getAttribute('name') ?: $meta->getAttribute('property');
            $content = $meta->getAttribute('content');

            if ($name && $content) {
                $all_meta[$name] = $content;
            }
        }

        return $all_meta;
    }
}

/**
 * Helper function to perform advanced SEO analysis
 */
function analyzeAdvancedSEO($url) {
    $analyzer = new AdvancedSEO($url);
    return $analyzer->analyze();
}
