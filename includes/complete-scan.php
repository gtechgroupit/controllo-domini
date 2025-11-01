<?php
/**
 * Complete Advanced Website Scan for Web Agencies
 *
 * Comprehensive website analysis combining all detection systems
 * including SEO, technologies, business intelligence, and more
 *
 * @package ControlloDomin
 * @version 4.3.0
 */

require_once __DIR__ . '/advanced-seo.php';
require_once __DIR__ . '/web-technology-advanced.php';
require_once __DIR__ . '/business-intelligence.php';
require_once __DIR__ . '/dns-functions.php';
require_once __DIR__ . '/whois-functions.php';
require_once __DIR__ . '/blacklist-functions.php';
require_once __DIR__ . '/ssl-certificate.php';
require_once __DIR__ . '/security-headers.php';
require_once __DIR__ . '/performance-analysis.php';

class CompleteScan {
    private $domain;
    private $url;
    private $start_time;
    private $cache;

    public function __construct($domain) {
        $this->domain = $domain;
        $this->url = 'https://' . $domain;
        $this->start_time = microtime(true);
        $this->cache = getCache();
    }

    /**
     * Perform complete website scan
     */
    public function scan() {
        $results = [
            'domain' => $this->domain,
            'url' => $this->url,
            'scan_date' => date('Y-m-d H:i:s'),
            'scan_id' => uniqid('scan_', true),

            // Core analysis
            'dns' => $this->getDNS(),
            'whois' => $this->getWHOIS(),
            'ssl' => $this->getSSL(),
            'blacklist' => $this->getBlacklist(),
            'security_headers' => $this->getSecurityHeaders(),

            // Advanced analysis
            'seo' => $this->getSEO(),
            'technologies' => $this->getTechnologies(),
            'business_intelligence' => $this->getBusinessIntelligence(),
            'performance' => $this->getPerformance(),

            // Additional insights
            'competitors' => $this->getCompetitorInsights(),
            'recommendations' => $this->generateRecommendations(),
            'overall_score' => null,
            'execution_time' => null
        ];

        // Calculate overall score
        $results['overall_score'] = $this->calculateOverallScore($results);

        // Calculate execution time
        $results['execution_time'] = round((microtime(true) - $this->start_time) * 1000, 2);

        return $results;
    }

    /**
     * Get DNS information
     */
    private function getDNS() {
        try {
            $cache_key = "complete_scan:dns:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return getAllDnsRecords($this->domain);
            }, 3600);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get WHOIS information
     */
    private function getWHOIS() {
        try {
            $cache_key = "complete_scan:whois:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return getWhoisInfo($this->domain);
            }, 86400);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get SSL information
     */
    private function getSSL() {
        try {
            $cache_key = "complete_scan:ssl:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return analyzeSSLCertificate($this->domain);
            }, 86400);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get blacklist status
     */
    private function getBlacklist() {
        try {
            $cache_key = "complete_scan:blacklist:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return checkBlacklists($this->domain);
            }, 7200);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get security headers
     */
    private function getSecurityHeaders() {
        try {
            $cache_key = "complete_scan:security:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return analyzeSecurityHeaders($this->url);
            }, 3600);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get advanced SEO analysis
     */
    private function getSEO() {
        try {
            $cache_key = "complete_scan:seo:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return analyzeAdvancedSEO($this->url);
            }, 3600);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get web technologies
     */
    private function getTechnologies() {
        try {
            $cache_key = "complete_scan:tech:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return detectWebTechnologyAdvanced($this->url);
            }, 86400);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get business intelligence
     */
    private function getBusinessIntelligence() {
        try {
            $cache_key = "complete_scan:business:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return extractBusinessIntelligence($this->url);
            }, 86400);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get performance analysis
     */
    private function getPerformance() {
        try {
            $cache_key = "complete_scan:performance:{$this->domain}";
            return $this->cache->remember($cache_key, function() {
                return analyzePerformance($this->url);
            }, 3600);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get competitor insights
     */
    private function getCompetitorInsights() {
        return [
            'similar_technologies' => $this->findSimilarTechnologies(),
            'market_position' => $this->estimateMarketPosition(),
            'differentiation' => $this->findDifferentiators()
        ];
    }

    /**
     * Find similar technologies used by competitors
     */
    private function findSimilarTechnologies() {
        // This would require a database of competitor sites
        // For now, return placeholder
        return [
            'note' => 'Competitor analysis requires additional data sources'
        ];
    }

    /**
     * Estimate market position
     */
    private function estimateMarketPosition() {
        $score = 0;
        $factors = [];

        // Check SSL
        if (isset($this->ssl['valid']) && $this->ssl['valid']) {
            $score += 20;
            $factors[] = 'Has valid SSL certificate';
        }

        // Check modern technologies
        if (isset($this->technologies['frameworks']) && count($this->technologies['frameworks']) > 0) {
            $score += 15;
            $factors[] = 'Uses modern frameworks';
        }

        // Check analytics
        if (isset($this->technologies['analytics']) && count($this->technologies['analytics']) > 0) {
            $score += 15;
            $factors[] = 'Has analytics tracking';
        }

        // Check CDN
        if (isset($this->technologies['cdn']) && count($this->technologies['cdn']) > 0) {
            $score += 20;
            $factors[] = 'Uses CDN for performance';
        }

        // Check SEO score
        if (isset($this->seo['seo_score']['score'])) {
            $seo_score = $this->seo['seo_score']['score'];
            $score += ($seo_score / 100) * 30;
            $factors[] = "SEO score: {$seo_score}/100";
        }

        return [
            'score' => round($score, 2),
            'max_score' => 100,
            'level' => $this->getMarketLevel($score),
            'factors' => $factors
        ];
    }

    /**
     * Get market level based on score
     */
    private function getMarketLevel($score) {
        if ($score >= 80) return 'Leader';
        if ($score >= 60) return 'Strong';
        if ($score >= 40) return 'Average';
        if ($score >= 20) return 'Developing';
        return 'Emerging';
    }

    /**
     * Find differentiators
     */
    private function findDifferentiators() {
        $differentiators = [];

        // Unique technologies
        if (isset($this->technologies['summary']['total_technologies'])) {
            $tech_count = $this->technologies['summary']['total_technologies'];
            if ($tech_count > 15) {
                $differentiators[] = "Rich technology stack ($tech_count technologies)";
            }
        }

        // Business model
        if (isset($this->business_intelligence['business_model'])) {
            $models = array_filter($this->business_intelligence['business_model']);
            if (count($models) > 2) {
                $differentiators[] = 'Multi-channel business model';
            }
        }

        // International presence
        if (isset($this->seo['international_seo']['hreflang']) &&
            count($this->seo['international_seo']['hreflang']) > 1) {
            $lang_count = count($this->seo['international_seo']['hreflang']);
            $differentiators[] = "International presence ($lang_count languages)";
        }

        // Security focus
        if (isset($this->security_headers['score']) && $this->security_headers['score'] >= 80) {
            $differentiators[] = 'Strong security implementation';
        }

        return $differentiators;
    }

    /**
     * Generate actionable recommendations
     */
    private function generateRecommendations() {
        $recommendations = [
            'critical' => [],
            'important' => [],
            'suggested' => []
        ];

        // SSL recommendations
        if (!isset($this->ssl['valid']) || !$this->ssl['valid']) {
            $recommendations['critical'][] = [
                'category' => 'Security',
                'issue' => 'No valid SSL certificate',
                'recommendation' => 'Install a valid SSL certificate (free with Let\'s Encrypt)',
                'impact' => 'High - Affects trust, SEO, and security',
                'effort' => 'Low'
            ];
        }

        // SEO recommendations
        if (isset($this->seo['seo_score']['issues'])) {
            foreach ($this->seo['seo_score']['issues'] as $issue) {
                $priority = 'important';
                if (strpos($issue, 'Missing title') !== false ||
                    strpos($issue, 'Missing H1') !== false) {
                    $priority = 'critical';
                }

                $recommendations[$priority][] = [
                    'category' => 'SEO',
                    'issue' => $issue,
                    'recommendation' => $this->getRecommendationForIssue($issue),
                    'impact' => 'Medium-High',
                    'effort' => 'Low'
                ];
            }
        }

        // Security headers
        if (isset($this->security_headers['missing']) &&
            count($this->security_headers['missing']) > 0) {
            $recommendations['important'][] = [
                'category' => 'Security',
                'issue' => 'Missing security headers: ' . implode(', ', $this->security_headers['missing']),
                'recommendation' => 'Add security headers to server configuration',
                'impact' => 'Medium',
                'effort' => 'Low'
            ];
        }

        // Performance recommendations
        if (isset($this->performance['score']) && $this->performance['score'] < 70) {
            $recommendations['important'][] = [
                'category' => 'Performance',
                'issue' => 'Low performance score',
                'recommendation' => 'Optimize images, minify CSS/JS, enable compression, use CDN',
                'impact' => 'High - Affects user experience and SEO',
                'effort' => 'Medium'
            ];
        }

        // Technology stack recommendations
        if (!isset($this->technologies['cdn']) || count($this->technologies['cdn']) === 0) {
            $recommendations['suggested'][] = [
                'category' => 'Performance',
                'issue' => 'No CDN detected',
                'recommendation' => 'Implement a CDN (Cloudflare, AWS CloudFront, etc.) to improve global performance',
                'impact' => 'Medium',
                'effort' => 'Low-Medium'
            ];
        }

        // Analytics recommendations
        if (!isset($this->technologies['analytics']) || count($this->technologies['analytics']) === 0) {
            $recommendations['suggested'][] = [
                'category' => 'Analytics',
                'issue' => 'No analytics detected',
                'recommendation' => 'Install web analytics (Google Analytics, Plausible, Matomo) to track visitors',
                'impact' => 'Medium',
                'effort' => 'Low'
            ];
        }

        // Mobile optimization
        if (isset($this->seo['mobile_seo']['viewport_meta']) &&
            !$this->seo['mobile_seo']['viewport_meta']) {
            $recommendations['important'][] = [
                'category' => 'Mobile',
                'issue' => 'Missing viewport meta tag',
                'recommendation' => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to HTML',
                'impact' => 'High - Affects mobile experience',
                'effort' => 'Very Low'
            ];
        }

        // Structured data
        if (!isset($this->seo['structured_data']['has_json_ld']) ||
            !$this->seo['structured_data']['has_json_ld']) {
            $recommendations['suggested'][] = [
                'category' => 'SEO',
                'issue' => 'No structured data (Schema.org)',
                'recommendation' => 'Add JSON-LD structured data for better search results',
                'impact' => 'Medium',
                'effort' => 'Medium'
            ];
        }

        return [
            'critical' => $recommendations['critical'],
            'important' => $recommendations['important'],
            'suggested' => $recommendations['suggested'],
            'total_recommendations' => count($recommendations['critical']) +
                                      count($recommendations['important']) +
                                      count($recommendations['suggested'])
        ];
    }

    /**
     * Get recommendation for specific issue
     */
    private function getRecommendationForIssue($issue) {
        $recommendations = [
            'Missing title tag' => 'Add a unique, descriptive <title> tag (30-60 characters)',
            'Missing meta description' => 'Add a compelling meta description (120-160 characters)',
            'Missing H1 tag' => 'Add a single H1 heading that describes the page content',
            'Multiple H1 tags' => 'Use only one H1 tag per page',
            'Title length not optimal' => 'Adjust title length to 30-60 characters',
            'Description length not optimal' => 'Adjust description length to 120-160 characters',
            'Missing canonical URL' => 'Add canonical URL to prevent duplicate content',
            'Not using HTTPS' => 'Migrate to HTTPS with SSL certificate',
            'No structured data found' => 'Implement Schema.org structured data (JSON-LD)',
        ];

        foreach ($recommendations as $key => $rec) {
            if (strpos($issue, $key) !== false) {
                return $rec;
            }
        }

        return 'Review and fix this issue';
    }

    /**
     * Calculate overall website score
     */
    private function calculateOverallScore($results) {
        $scores = [];
        $weights = [
            'seo' => 25,
            'security' => 20,
            'performance' => 20,
            'technologies' => 15,
            'ssl' => 10,
            'business' => 10
        ];

        // SEO score
        if (isset($results['seo']['seo_score']['score'])) {
            $scores['seo'] = $results['seo']['seo_score']['score'];
        }

        // Security score
        if (isset($results['security_headers']['score'])) {
            $scores['security'] = $results['security_headers']['score'];
        }

        // Performance score
        if (isset($results['performance']['score'])) {
            $scores['performance'] = $results['performance']['score'];
        }

        // Technology score (based on modern tech usage)
        if (isset($results['technologies']['summary']['total_technologies'])) {
            $tech_count = $results['technologies']['summary']['total_technologies'];
            $scores['technologies'] = min(100, ($tech_count / 15) * 100);
        }

        // SSL score
        if (isset($results['ssl']['valid']) && $results['ssl']['valid']) {
            $scores['ssl'] = 100;
        } else {
            $scores['ssl'] = 0;
        }

        // Business score (based on completeness of information)
        $business_score = 0;
        if (isset($results['business_intelligence']['contact_info']['emails']) &&
            count($results['business_intelligence']['contact_info']['emails']) > 0) {
            $business_score += 30;
        }
        if (isset($results['business_intelligence']['social_profiles']) &&
            count($results['business_intelligence']['social_profiles']) > 0) {
            $business_score += 30;
        }
        if (isset($results['business_intelligence']['company_info']['name'])) {
            $business_score += 40;
        }
        $scores['business'] = $business_score;

        // Calculate weighted average
        $total_score = 0;
        $total_weight = 0;

        foreach ($scores as $category => $score) {
            if (isset($weights[$category])) {
                $total_score += $score * ($weights[$category] / 100);
                $total_weight += $weights[$category] / 100;
            }
        }

        $overall = $total_weight > 0 ? $total_score / $total_weight : 0;

        return [
            'score' => round($overall, 2),
            'grade' => $this->getGrade($overall),
            'breakdown' => $scores,
            'interpretation' => $this->interpretScore($overall)
        ];
    }

    /**
     * Get letter grade from score
     */
    private function getGrade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'C+';
        if ($score >= 65) return 'C';
        if ($score >= 60) return 'D+';
        if ($score >= 55) return 'D';
        return 'F';
    }

    /**
     * Interpret overall score
     */
    private function interpretScore($score) {
        if ($score >= 90) {
            return 'Excellent - This website follows best practices in almost all areas';
        }
        if ($score >= 80) {
            return 'Very Good - Strong performance with minor areas for improvement';
        }
        if ($score >= 70) {
            return 'Good - Solid foundation with some optimization opportunities';
        }
        if ($score >= 60) {
            return 'Fair - Several areas need attention for better performance';
        }
        if ($score >= 50) {
            return 'Poor - Significant improvements needed across multiple areas';
        }
        return 'Critical - Major issues require immediate attention';
    }
}

/**
 * Helper function to perform complete scan
 */
function performCompleteScan($domain) {
    $scanner = new CompleteScan($domain);
    return $scanner->scan();
}
