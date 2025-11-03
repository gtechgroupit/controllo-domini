<?php
/**
 * Complete Website Scan Page - For Web Agencies
 *
 * Advanced comprehensive scan with all data points
 *
 * @package ControlloDomin
 * @version 4.3.0
 */

require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/complete-scan.php';

$domain = $_GET['domain'] ?? '';
$scan_results = null;
$error = null;

if (!empty($domain)) {
    $domain = sanitizeDomain($domain);

    try {
        $scan_results = performCompleteScan($domain);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Complete Website Scan - Controllo Domini';
$page_description = 'Comprehensive website analysis for web agencies';
include __DIR__ . '/includes/header.php';
?>

<div class="scan-container">
    <!-- Scan Form -->
    <div class="scan-header">
        <h1>🚀 Complete Website Scan</h1>
        <p class="subtitle">Advanced analysis with 1000+ data points for web agencies</p>
    </div>

    <form method="GET" class="scan-form">
        <div class="input-group">
            <input type="text"
                   name="domain"
                   placeholder="example.com"
                   value="<?php echo htmlspecialchars($domain); ?>"
                   required
                   pattern="[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}"
                   class="domain-input">
            <button type="submit" class="btn-scan">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Scan Website
            </button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($scan_results): ?>
        <!-- Overall Score Card -->
        <div class="score-card">
            <div class="score-main">
                <div class="score-circle score-<?php echo strtolower($scan_results['overall_score']['grade']); ?>">
                    <span class="score-number"><?php echo round($scan_results['overall_score']['score']); ?></span>
                    <span class="score-max">/100</span>
                </div>
                <div class="score-details">
                    <h2>Overall Grade: <?php echo $scan_results['overall_score']['grade']; ?></h2>
                    <p><?php echo $scan_results['overall_score']['interpretation']; ?></p>
                    <p class="scan-meta">
                        Scanned: <?php echo $scan_results['scan_date']; ?> •
                        Time: <?php echo $scan_results['execution_time']; ?>ms
                    </p>
                </div>
            </div>

            <div class="score-breakdown">
                <h3>Score Breakdown</h3>
                <div class="breakdown-grid">
                    <?php foreach ($scan_results['overall_score']['breakdown'] as $category => $score): ?>
                        <div class="breakdown-item">
                            <span class="breakdown-label"><?php echo ucfirst($category); ?></span>
                            <div class="breakdown-bar">
                                <div class="breakdown-fill" style="width: <?php echo $score; ?>%"></div>
                            </div>
                            <span class="breakdown-value"><?php echo round($score); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <?php if ($scan_results['recommendations']['total_recommendations'] > 0): ?>
            <div class="recommendations-section">
                <h2>📋 Recommendations (<?php echo $scan_results['recommendations']['total_recommendations']; ?>)</h2>

                <?php if (!empty($scan_results['recommendations']['critical'])): ?>
                    <div class="recommendations-group critical">
                        <h3>🔴 Critical (<?php echo count($scan_results['recommendations']['critical']); ?>)</h3>
                        <?php foreach ($scan_results['recommendations']['critical'] as $rec): ?>
                            <div class="recommendation-card">
                                <div class="rec-header">
                                    <span class="rec-category"><?php echo $rec['category']; ?></span>
                                    <span class="rec-impact"><?php echo $rec['impact']; ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($rec['issue']); ?></h4>
                                <p><?php echo htmlspecialchars($rec['recommendation']); ?></p>
                                <span class="rec-effort">Effort: <?php echo $rec['effort']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($scan_results['recommendations']['important'])): ?>
                    <div class="recommendations-group important">
                        <h3>🟡 Important (<?php echo count($scan_results['recommendations']['important']); ?>)</h3>
                        <?php foreach ($scan_results['recommendations']['important'] as $rec): ?>
                            <div class="recommendation-card">
                                <div class="rec-header">
                                    <span class="rec-category"><?php echo $rec['category']; ?></span>
                                    <span class="rec-impact"><?php echo $rec['impact']; ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($rec['issue']); ?></h4>
                                <p><?php echo htmlspecialchars($rec['recommendation']); ?></p>
                                <span class="rec-effort">Effort: <?php echo $rec['effort']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($scan_results['recommendations']['suggested'])): ?>
                    <div class="recommendations-group suggested">
                        <h3>🟢 Suggested (<?php echo count($scan_results['recommendations']['suggested']); ?>)</h3>
                        <?php foreach ($scan_results['recommendations']['suggested'] as $rec): ?>
                            <div class="recommendation-card">
                                <div class="rec-header">
                                    <span class="rec-category"><?php echo $rec['category']; ?></span>
                                    <span class="rec-impact"><?php echo $rec['impact']; ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($rec['issue']); ?></h4>
                                <p><?php echo htmlspecialchars($rec['recommendation']); ?></p>
                                <span class="rec-effort">Effort: <?php echo $rec['effort']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab active" data-tab="seo">SEO Analysis</button>
            <button class="tab" data-tab="technologies">Technologies</button>
            <button class="tab" data-tab="business">Business Intelligence</button>
            <button class="tab" data-tab="security">Security</button>
            <button class="tab" data-tab="performance">Performance</button>
            <button class="tab" data-tab="technical">Technical</button>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content active" id="tab-seo">
            <h2>🔍 SEO Analysis</h2>

            <div class="info-grid">
                <div class="info-card">
                    <h3>SEO Score</h3>
                    <div class="score-badge score-<?php echo strtolower($scan_results['seo']['seo_score']['grade']); ?>">
                        <?php echo $scan_results['seo']['seo_score']['score']; ?>/100
                        (<?php echo $scan_results['seo']['seo_score']['grade']; ?>)
                    </div>
                    <?php if (!empty($scan_results['seo']['seo_score']['issues'])): ?>
                        <ul class="issues-list">
                            <?php foreach ($scan_results['seo']['seo_score']['issues'] as $issue): ?>
                                <li><?php echo htmlspecialchars($issue); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Meta Tags</h3>
                    <table class="data-table">
                        <tr>
                            <th>Title</th>
                            <td><?php echo htmlspecialchars($scan_results['seo']['meta_tags']['title']); ?></td>
                        </tr>
                        <tr>
                            <th>Length</th>
                            <td>
                                <?php echo $scan_results['seo']['meta_tags']['quality']['title_length']; ?> chars
                                <?php echo $scan_results['seo']['meta_tags']['quality']['title_optimal'] ? '✓' : '⚠️'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?php echo htmlspecialchars($scan_results['seo']['meta_tags']['description']); ?></td>
                        </tr>
                        <tr>
                            <th>Length</th>
                            <td>
                                <?php echo $scan_results['seo']['meta_tags']['quality']['description_length']; ?> chars
                                <?php echo $scan_results['seo']['meta_tags']['quality']['description_optimal'] ? '✓' : '⚠️'; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="info-card">
                    <h3>Headings Structure</h3>
                    <?php $headings = $scan_results['seo']['headings']; ?>
                    <table class="data-table">
                        <tr>
                            <th>H1</th>
                            <td>
                                <?php echo $headings['analysis']['h1_count']; ?>
                                <?php echo $headings['analysis']['h1_optimal'] ? '✓' : '⚠️'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>H2</th>
                            <td><?php echo $headings['analysis']['h2_count']; ?></td>
                        </tr>
                        <tr>
                            <th>Total</th>
                            <td><?php echo $headings['analysis']['total_headings']; ?></td>
                        </tr>
                    </table>
                </div>

                <div class="info-card">
                    <h3>Content Analysis</h3>
                    <table class="data-table">
                        <tr>
                            <th>Words</th>
                            <td><?php echo number_format($scan_results['seo']['content']['word_count']); ?></td>
                        </tr>
                        <tr>
                            <th>Reading Time</th>
                            <td><?php echo $scan_results['seo']['content']['reading_time']; ?> min</td>
                        </tr>
                        <tr>
                            <th>Text/HTML Ratio</th>
                            <td><?php echo $scan_results['seo']['content']['text_html_ratio']; ?>%</td>
                        </tr>
                    </table>
                </div>

                <?php if (!empty($scan_results['seo']['structured_data']['json_ld'])): ?>
                    <div class="info-card full-width">
                        <h3>Structured Data (JSON-LD)</h3>
                        <p><?php echo count($scan_results['seo']['structured_data']['json_ld']); ?> schemas found</p>
                        <pre class="code-block"><?php echo json_encode($scan_results['seo']['structured_data']['json_ld'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="tab-technologies">
            <h2>💻 Technologies Detected</h2>

            <div class="tech-summary">
                <h3>Summary: <?php echo $scan_results['technologies']['summary']['total_technologies']; ?> technologies found</h3>
            </div>

            <?php foreach (['cms', 'frameworks', 'analytics', 'marketing', 'ecommerce', 'cdn', 'hosting', 'security'] as $category): ?>
                <?php if (!empty($scan_results['technologies'][$category])): ?>
                    <div class="tech-category">
                        <h3><?php echo ucfirst($category); ?> (<?php echo count($scan_results['technologies'][$category]); ?>)</h3>
                        <div class="tech-grid">
                            <?php foreach ($scan_results['technologies'][$category] as $tech): ?>
                                <div class="tech-card">
                                    <div class="tech-icon"><?php echo $tech['icon']; ?></div>
                                    <div class="tech-info">
                                        <h4><?php echo $tech['name']; ?></h4>
                                        <?php if ($tech['version']): ?>
                                            <span class="tech-version">v<?php echo $tech['version']; ?></span>
                                        <?php endif; ?>
                                        <div class="tech-confidence">
                                            Confidence: <?php echo $tech['confidence']; ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="tab-content" id="tab-business">
            <h2>💼 Business Intelligence</h2>

            <div class="info-grid">
                <?php $bi = $scan_results['business_intelligence']; ?>

                <!-- Contact Info -->
                <div class="info-card">
                    <h3>📞 Contact Information</h3>
                    <?php if (!empty($bi['contact_info']['emails'])): ?>
                        <h4>Emails:</h4>
                        <ul>
                            <?php foreach ($bi['contact_info']['emails'] as $email): ?>
                                <li><?php echo htmlspecialchars($email); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($bi['contact_info']['phones'])): ?>
                        <h4>Phones:</h4>
                        <ul>
                            <?php foreach ($bi['contact_info']['phones'] as $phone): ?>
                                <li><?php echo htmlspecialchars($phone); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="features-list">
                        <?php if ($bi['contact_info']['contact_form']): ?>
                            <span class="badge-success">✓ Contact Form</span>
                        <?php endif; ?>
                        <?php if ($bi['contact_info']['live_chat']): ?>
                            <span class="badge-success">✓ Live Chat</span>
                        <?php endif; ?>
                        <?php if ($bi['contact_info']['whatsapp']): ?>
                            <span class="badge-success">✓ WhatsApp</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Company Info -->
                <div class="info-card">
                    <h3>🏢 Company Information</h3>
                    <table class="data-table">
                        <?php if ($bi['company_info']['name']): ?>
                            <tr>
                                <th>Name</th>
                                <td><?php echo htmlspecialchars($bi['company_info']['name']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($bi['company_info']['founded']): ?>
                            <tr>
                                <th>Founded</th>
                                <td><?php echo htmlspecialchars($bi['company_info']['founded']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($bi['company_info']['size']): ?>
                            <tr>
                                <th>Size</th>
                                <td><?php echo htmlspecialchars($bi['company_info']['size']); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($bi['company_info']['vat_number']): ?>
                            <tr>
                                <th>VAT</th>
                                <td><?php echo htmlspecialchars($bi['company_info']['vat_number']); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Social Profiles -->
                <?php if (!empty($bi['social_profiles'])): ?>
                    <div class="info-card">
                        <h3>📱 Social Media Profiles</h3>
                        <ul class="social-list">
                            <?php foreach ($bi['social_profiles'] as $platform => $url): ?>
                                <li>
                                    <strong><?php echo ucfirst($platform); ?>:</strong>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"><?php echo htmlspecialchars($url); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Business Model -->
                <div class="info-card">
                    <h3>🎯 Business Model</h3>
                    <div class="features-list">
                        <?php foreach ($bi['business_model'] as $model => $detected): ?>
                            <?php if ($detected): ?>
                                <span class="badge-info"><?php echo ucwords(str_replace('_', ' ', $model)); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Target Audience -->
                <div class="info-card">
                    <h3>👥 Target Audience</h3>
                    <p><strong>Primary:</strong> <?php echo $bi['target_audience']['primary']; ?></p>
                    <?php if ($bi['target_audience']['b2b']): ?>
                        <span class="badge-info">B2B</span>
                    <?php endif; ?>
                    <?php if ($bi['target_audience']['b2c']): ?>
                        <span class="badge-info">B2C</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="tab-security">
            <h2>🔒 Security Analysis</h2>

            <div class="info-grid">
                <!-- SSL Certificate -->
                <div class="info-card">
                    <h3>SSL Certificate</h3>
                    <?php if ($scan_results['ssl']['valid']): ?>
                        <div class="status-badge status-success">✓ Valid Certificate</div>
                        <?php if (isset($scan_results['ssl']['issuer'])): ?>
                            <table class="data-table">
                                <tr>
                                    <th>Issuer</th>
                                    <td><?php echo $scan_results['ssl']['issuer']; ?></td>
                                </tr>
                                <tr>
                                    <th>Valid Until</th>
                                    <td><?php echo $scan_results['ssl']['valid_to']; ?></td>
                                </tr>
                                <tr>
                                    <th>Days Remaining</th>
                                    <td><?php echo $scan_results['ssl']['days_until_expiry']; ?></td>
                                </tr>
                            </table>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="status-badge status-error">✗ No Valid SSL</div>
                    <?php endif; ?>
                </div>

                <!-- Security Headers -->
                <div class="info-card">
                    <h3>Security Headers</h3>
                    <?php if (isset($scan_results['security_headers']['score'])): ?>
                        <div class="score-badge">Score: <?php echo $scan_results['security_headers']['score']; ?>/100</div>

                        <?php if (!empty($scan_results['security_headers']['present'])): ?>
                            <h4>Present:</h4>
                            <ul class="check-list">
                                <?php foreach ($scan_results['security_headers']['present'] as $header): ?>
                                    <li class="check-success">✓ <?php echo $header; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($scan_results['security_headers']['missing'])): ?>
                            <h4>Missing:</h4>
                            <ul class="check-list">
                                <?php foreach ($scan_results['security_headers']['missing'] as $header): ?>
                                    <li class="check-error">✗ <?php echo $header; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Blacklist Status -->
                <?php if (isset($scan_results['blacklist'])): ?>
                    <div class="info-card">
                        <h3>Blacklist Status</h3>
                        <?php if ($scan_results['blacklist']['listed']): ?>
                            <div class="status-badge status-error">
                                ⚠️ Listed on <?php echo count($scan_results['blacklist']['blacklists']); ?> blacklists
                            </div>
                        <?php else: ?>
                            <div class="status-badge status-success">✓ Not Blacklisted</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="tab-performance">
            <h2>⚡ Performance Analysis</h2>

            <div class="info-grid">
                <?php if (isset($scan_results['performance'])): ?>
                    <div class="info-card">
                        <h3>Performance Score</h3>
                        <div class="score-badge">
                            <?php echo $scan_results['performance']['score'] ?? 'N/A'; ?>/100
                        </div>
                    </div>

                    <?php if (isset($scan_results['seo']['page_speed_indicators'])): ?>
                        <div class="info-card">
                            <h3>Page Speed Indicators</h3>
                            <table class="data-table">
                                <tr>
                                    <th>Defer Scripts</th>
                                    <td><?php echo $scan_results['seo']['page_speed_indicators']['defer_scripts']; ?></td>
                                </tr>
                                <tr>
                                    <th>Async Scripts</th>
                                    <td><?php echo $scan_results['seo']['page_speed_indicators']['async_scripts']; ?></td>
                                </tr>
                                <tr>
                                    <th>External Scripts</th>
                                    <td><?php echo $scan_results['seo']['page_speed_indicators']['external_scripts']; ?></td>
                                </tr>
                                <tr>
                                    <th>External Styles</th>
                                    <td><?php echo $scan_results['seo']['page_speed_indicators']['external_styles']; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Resources</th>
                                    <td><?php echo $scan_results['seo']['page_speed_indicators']['total_resources']; ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="tab-technical">
            <h2>⚙️ Technical Information</h2>

            <div class="info-grid">
                <!-- DNS Records -->
                <?php if (isset($scan_results['dns'])): ?>
                    <div class="info-card full-width">
                        <h3>DNS Records</h3>
                        <pre class="code-block"><?php echo json_encode($scan_results['dns'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                <?php endif; ?>

                <!-- WHOIS Info -->
                <?php if (isset($scan_results['whois'])): ?>
                    <div class="info-card full-width">
                        <h3>WHOIS Information</h3>
                        <pre class="code-block"><?php echo json_encode($scan_results['whois'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h3>Export Report</h3>
            <div class="export-buttons">
                <a href="/export?domain=<?php echo urlencode($domain); ?>&format=pdf" class="btn btn-export">
                    📄 Export PDF
                </a>
                <a href="/export?domain=<?php echo urlencode($domain); ?>&format=json" class="btn btn-export">
                    💾 Export JSON
                </a>
                <a href="/export?domain=<?php echo urlencode($domain); ?>&format=csv" class="btn btn-export">
                    📊 Export CSV
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.scan-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

.scan-header {
    text-align: center;
    margin-bottom: 40px;
}

.scan-header h1 {
    font-size: 42px;
    margin: 0 0 16px 0;
}

.subtitle {
    font-size: 18px;
    color: var(--text-secondary);
}

.scan-form {
    max-width: 600px;
    margin: 0 auto 40px;
}

.input-group {
    display: flex;
    gap: 12px;
}

.domain-input {
    flex: 1;
    padding: 16px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 16px;
}

.btn-scan {
    padding: 16px 32px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.score-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.score-main {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 40px;
    align-items: center;
    margin-bottom: 40px;
}

.score-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    border: 8px solid;
}

.score-circle.score-a, .score-circle.score-a\+ {
    border-color: #10b981;
    color: #10b981;
}

.score-circle.score-b, .score-circle.score-b\+ {
    border-color: #3b82f6;
    color: #3b82f6;
}

.score-circle.score-c, .score-circle.score-c\+ {
    border-color: #f59e0b;
    color: #f59e0b;
}

.score-circle.score-d, .score-circle.score-d\+, .score-circle.score-f {
    border-color: #ef4444;
    color: #ef4444;
}

.score-max {
    font-size: 20px;
    font-weight: 400;
}

.breakdown-grid {
    display: grid;
    gap: 16px;
}

.breakdown-item {
    display: grid;
    grid-template-columns: 120px 1fr 60px;
    gap: 12px;
    align-items: center;
}

.breakdown-bar {
    height: 24px;
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow: hidden;
}

.breakdown-fill {
    height: 100%;
    background: var(--primary-color);
    transition: width 0.3s;
}

.recommendations-section {
    margin-bottom: 40px;
}

.recommendations-group {
    margin-bottom: 32px;
}

.recommendation-card {
    background: var(--card-bg);
    border-left: 4px solid;
    padding: 20px;
    margin-bottom: 16px;
    border-radius: 8px;
}

.recommendations-group.critical .recommendation-card {
    border-color: #ef4444;
}

.recommendations-group.important .recommendation-card {
    border-color: #f59e0b;
}

.recommendations-group.suggested .recommendation-card {
    border-color: #10b981;
}

.rec-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
}

.rec-category {
    background: var(--primary-color);
    color: white;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 32px;
}

.tab {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: -2px;
}

.tab.active {
    border-bottom-color: var(--primary-color);
    color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.info-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-card.full-width {
    grid-column: 1 / -1;
}

.data-table {
    width: 100%;
    margin-top: 16px;
}

.data-table th {
    text-align: left;
    padding: 8px;
    font-weight: 600;
    color: var(--text-secondary);
}

.data-table td {
    padding: 8px;
}

.tech-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.tech-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: var(--bg-secondary);
    border-radius: 8px;
}

.tech-icon {
    font-size: 32px;
}

.code-block {
    background: var(--bg-secondary);
    padding: 16px;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 12px;
}

.export-section {
    margin-top: 40px;
    padding: 24px;
    background: var(--card-bg);
    border-radius: 12px;
    text-align: center;
}

.export-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 16px;
}

.btn-export {
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
}
</style>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.dataset.tab;

        // Update tabs
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Update content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById('tab-' + tabName).classList.add('active');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
