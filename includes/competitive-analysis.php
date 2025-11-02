<?php
/**
 * Competitive Analysis System
 *
 * Compare multiple websites side-by-side for competitive intelligence:
 * - Technology stack comparison
 * - SEO performance benchmarking
 * - Security posture comparison
 * - Performance metrics comparison
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/complete-scan.php';

class CompetitiveAnalysis {
    private $domains = [];
    private $results = [];

    /**
     * Add domain to comparison
     */
    public function addDomain($domain) {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#/.*$#', '', $domain);

        if (!in_array($domain, $this->domains)) {
            $this->domains[] = $domain;
        }

        return $this;
    }

    /**
     * Perform competitive analysis
     */
    public function analyze() {
        if (count($this->domains) < 2) {
            throw new Exception('At least 2 domains required for competitive analysis');
        }

        // Scan each domain
        foreach ($this->domains as $domain) {
            try {
                $this->results[$domain] = performCompleteScan($domain);
            } catch (Exception $e) {
                $this->results[$domain] = ['error' => $e->getMessage()];
            }
        }

        // Generate comparison
        return $this->generateComparison();
    }

    /**
     * Generate comparison report
     */
    private function generateComparison() {
        $comparison = [
            'domains' => $this->domains,
            'analysis_date' => date('Y-m-d H:i:s'),
            'overall_scores' => $this->compareOverallScores(),
            'seo' => $this->compareSEO(),
            'technologies' => $this->compareTechnologies(),
            'security' => $this->compareSecurity(),
            'performance' => $this->comparePerformance(),
            'business' => $this->compareBusinessIntelligence(),
            'winner' => $this->determineWinner(),
            'insights' => $this->generateInsights()
        ];

        return $comparison;
    }

    /**
     * Compare overall scores
     */
    private function compareOverallScores() {
        $scores = [];

        foreach ($this->results as $domain => $result) {
            if (isset($result['overall_score'])) {
                $scores[$domain] = [
                    'score' => $result['overall_score']['score'] ?? 0,
                    'grade' => $result['overall_score']['grade'] ?? 'N/A',
                    'breakdown' => $result['overall_score']['breakdown'] ?? []
                ];
            }
        }

        // Sort by score
        uasort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scores;
    }

    /**
     * Compare SEO metrics
     */
    private function compareSEO() {
        $seo_comparison = [];

        foreach ($this->results as $domain => $result) {
            if (isset($result['seo'])) {
                $seo = $result['seo'];

                $seo_comparison[$domain] = [
                    'score' => $seo['seo_score']['score'] ?? 0,
                    'has_title' => !empty($seo['meta_tags']['title']),
                    'has_description' => !empty($seo['meta_tags']['description']),
                    'has_h1' => !empty($seo['headings']['h1']),
                    'has_structured_data' => !empty($seo['structured_data']),
                    'has_open_graph' => !empty($seo['open_graph']),
                    'internal_links' => $seo['link_analysis']['internal_count'] ?? 0,
                    'external_links' => $seo['link_analysis']['external_count'] ?? 0,
                    'images_with_alt' => $seo['image_analysis']['with_alt'] ?? 0,
                    'content_word_count' => $seo['content_analysis']['word_count'] ?? 0
                ];
            }
        }

        return $seo_comparison;
    }

    /**
     * Compare technologies
     */
    private function compareTechnologies() {
        $tech_comparison = [];
        $all_technologies = [];

        // Collect all technologies
        foreach ($this->results as $domain => $result) {
            if (isset($result['technologies'])) {
                foreach ($result['technologies'] as $category => $techs) {
                    if (is_array($techs)) {
                        foreach ($techs as $tech) {
                            $tech_name = $tech['name'] ?? '';
                            if ($tech_name) {
                                $all_technologies[$tech_name][$domain] = [
                                    'confidence' => $tech['confidence'] ?? 0,
                                    'version' => $tech['version'] ?? null,
                                    'category' => $category
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Create comparison matrix
        foreach ($all_technologies as $tech_name => $usage) {
            $tech_comparison[$tech_name] = [
                'category' => $usage[array_key_first($usage)]['category'] ?? 'unknown',
                'usage' => []
            ];

            foreach ($this->domains as $domain) {
                $tech_comparison[$tech_name]['usage'][$domain] = [
                    'detected' => isset($usage[$domain]),
                    'confidence' => $usage[$domain]['confidence'] ?? 0,
                    'version' => $usage[$domain]['version'] ?? null
                ];
            }
        }

        return $tech_comparison;
    }

    /**
     * Compare security
     */
    private function compareSecurity() {
        $security_comparison = [];

        foreach ($this->results as $domain => $result) {
            $security_comparison[$domain] = [
                'ssl_valid' => $result['ssl']['valid'] ?? false,
                'ssl_issuer' => $result['ssl']['issuer'] ?? null,
                'ssl_expires' => $result['ssl']['expires'] ?? null,
                'is_blacklisted' => $result['blacklist']['is_blacklisted'] ?? false,
                'security_headers' => []
            ];

            if (isset($result['security_headers'])) {
                foreach ($result['security_headers'] as $header => $value) {
                    $security_comparison[$domain]['security_headers'][$header] = !empty($value);
                }
            }
        }

        return $security_comparison;
    }

    /**
     * Compare performance
     */
    private function comparePerformance() {
        $performance_comparison = [];

        foreach ($this->results as $domain => $result) {
            $performance_comparison[$domain] = [
                'execution_time' => $result['execution_time_ms'] ?? 0,
                'has_https' => isset($result['ssl']) && ($result['ssl']['valid'] ?? false),
                'dns_records_count' => isset($result['dns']) ? count($result['dns']) : 0
            ];
        }

        return $performance_comparison;
    }

    /**
     * Compare business intelligence
     */
    private function compareBusinessIntelligence() {
        $business_comparison = [];

        foreach ($this->results as $domain => $result) {
            if (isset($result['business_intelligence'])) {
                $bi = $result['business_intelligence'];

                $business_comparison[$domain] = [
                    'has_email' => !empty($bi['contact_info']['emails']),
                    'has_phone' => !empty($bi['contact_info']['phones']),
                    'has_address' => !empty($bi['contact_info']['addresses']),
                    'social_profiles_count' => count($bi['social_profiles'] ?? []),
                    'has_live_chat' => $bi['has_live_chat'] ?? false,
                    'has_whatsapp' => $bi['has_whatsapp'] ?? false,
                    'business_models' => []
                ];

                if (isset($bi['business_model'])) {
                    foreach ($bi['business_model'] as $model => $detected) {
                        if ($detected) {
                            $business_comparison[$domain]['business_models'][] = $model;
                        }
                    }
                }
            }
        }

        return $business_comparison;
    }

    /**
     * Determine winner by category
     */
    private function determineWinner() {
        $winners = [];

        // Overall score winner
        $scores = $this->compareOverallScores();
        if (!empty($scores)) {
            $winners['overall'] = array_key_first($scores);
        }

        // SEO winner
        $seo = $this->compareSEO();
        if (!empty($seo)) {
            $seo_scores = array_column($seo, 'score');
            arsort($seo_scores);
            $winners['seo'] = array_key_first($seo_scores);
        }

        // Security winner (most security features)
        $security = $this->compareSecurity();
        $security_scores = [];
        foreach ($security as $domain => $sec) {
            $score = 0;
            if ($sec['ssl_valid']) $score += 30;
            if (!$sec['is_blacklisted']) $score += 20;
            $score += count(array_filter($sec['security_headers'])) * 5;
            $security_scores[$domain] = $score;
        }
        if (!empty($security_scores)) {
            arsort($security_scores);
            $winners['security'] = array_key_first($security_scores);
        }

        // Technology leader (most technologies)
        $tech = $this->compareTechnologies();
        $tech_counts = [];
        foreach ($this->domains as $domain) {
            $tech_counts[$domain] = 0;
            foreach ($tech as $tech_name => $usage) {
                if ($usage['usage'][$domain]['detected']) {
                    $tech_counts[$domain]++;
                }
            }
        }
        if (!empty($tech_counts)) {
            arsort($tech_counts);
            $winners['technology'] = array_key_first($tech_counts);
        }

        return $winners;
    }

    /**
     * Generate insights and recommendations
     */
    private function generateInsights() {
        $insights = [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'recommendations' => []
        ];

        $scores = $this->compareOverallScores();
        $seo = $this->compareSEO();
        $security = $this->compareSecurity();
        $tech = $this->compareTechnologies();

        // Identify strengths and weaknesses
        foreach ($this->domains as $domain) {
            $domain_insights = [
                'strengths' => [],
                'weaknesses' => []
            ];

            // SEO insights
            if (isset($seo[$domain])) {
                if ($seo[$domain]['score'] >= 80) {
                    $domain_insights['strengths'][] = 'Strong SEO optimization';
                } elseif ($seo[$domain]['score'] < 50) {
                    $domain_insights['weaknesses'][] = 'Poor SEO performance';
                }

                if (!$seo[$domain]['has_structured_data']) {
                    $domain_insights['weaknesses'][] = 'Missing structured data';
                }
            }

            // Security insights
            if (isset($security[$domain])) {
                if ($security[$domain]['ssl_valid']) {
                    $domain_insights['strengths'][] = 'Valid SSL certificate';
                } else {
                    $domain_insights['weaknesses'][] = 'Invalid or missing SSL';
                }

                if ($security[$domain]['is_blacklisted']) {
                    $domain_insights['weaknesses'][] = 'Listed on spam blacklists';
                }
            }

            $insights['strengths'][$domain] = $domain_insights['strengths'];
            $insights['weaknesses'][$domain] = $domain_insights['weaknesses'];
        }

        // Identify opportunities (features competitors have that you don't)
        foreach ($this->domains as $domain) {
            $opportunities = [];

            // Check if competitors have technologies you don't
            foreach ($tech as $tech_name => $usage) {
                $has_it = $usage['usage'][$domain]['detected'] ?? false;

                if (!$has_it) {
                    // Check if any competitor has it
                    foreach ($this->domains as $competitor) {
                        if ($competitor !== $domain && ($usage['usage'][$competitor]['detected'] ?? false)) {
                            $opportunities[] = "Consider adopting {$tech_name} (used by {$competitor})";
                            break;
                        }
                    }
                }
            }

            $insights['opportunities'][$domain] = array_slice($opportunities, 0, 5);
        }

        // Generate recommendations
        foreach ($this->domains as $domain) {
            $recommendations = [];

            if (isset($scores[$domain]) && $scores[$domain]['score'] < 70) {
                $recommendations[] = "Focus on improving overall website quality";
            }

            if (isset($seo[$domain]) && $seo[$domain]['score'] < 60) {
                $recommendations[] = "Invest in SEO optimization";
            }

            if (isset($security[$domain]) && !$security[$domain]['ssl_valid']) {
                $recommendations[] = "URGENT: Install valid SSL certificate";
            }

            $insights['recommendations'][$domain] = $recommendations;
        }

        return $insights;
    }

    /**
     * Export comparison to CSV
     */
    public function exportCSV($filepath) {
        $comparison = $this->generateComparison();

        $fp = fopen($filepath, 'w');

        // Header
        fputcsv($fp, array_merge(['Metric'], $this->domains));

        // Overall scores
        $scores = $comparison['overall_scores'];
        $score_row = ['Overall Score'];
        foreach ($this->domains as $domain) {
            $score_row[] = $scores[$domain]['score'] ?? 'N/A';
        }
        fputcsv($fp, $score_row);

        // SEO scores
        $seo = $comparison['seo'];
        $seo_row = ['SEO Score'];
        foreach ($this->domains as $domain) {
            $seo_row[] = $seo[$domain]['score'] ?? 'N/A';
        }
        fputcsv($fp, $seo_row);

        // More rows...

        fclose($fp);
        return $filepath;
    }
}

/**
 * Helper function
 */
function createCompetitiveAnalysis($domains) {
    $analysis = new CompetitiveAnalysis();
    foreach ($domains as $domain) {
        $analysis->addDomain($domain);
    }
    return $analysis->analyze();
}
