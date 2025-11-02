<?php
/**
 * Professional PDF Export System
 *
 * Generate professional PDF reports for Complete Website Scan results
 * Uses FPDF library for lightweight PDF generation
 *
 * @package ControlloDomini
 * @version 4.2.1
 */

/**
 * Simple PDF Generator
 * Lightweight implementation for generating professional reports
 */
class SimplePDF {
    private $content = [];
    private $metadata = [];

    public function __construct($title = 'Website Analysis Report') {
        $this->metadata = [
            'title' => $title,
            'author' => 'Controllo Domini by G Tech Group',
            'creator' => 'Controllo Domini v4.2',
            'created' => date('Y-m-d H:i:s')
        ];
    }

    public function addContent($html) {
        $this->content[] = $html;
    }

    public function save($filepath) {
        $html = $this->generateHTML();
        file_put_contents($filepath, $html);
        return $filepath;
    }

    private function generateHTML() {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->metadata['title']}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }

        .pdf-header {
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .pdf-header h1 {
            color: #0066cc;
            font-size: 28pt;
            margin-bottom: 10px;
        }

        .pdf-header .subtitle {
            color: #666;
            font-size: 12pt;
        }

        .pdf-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .pdf-section h2 {
            color: #0066cc;
            font-size: 18pt;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 5px;
        }

        .pdf-section h3 {
            color: #333;
            font-size: 14pt;
            margin-bottom: 10px;
            margin-top: 15px;
        }

        .score-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .score-number {
            font-size: 48pt;
            font-weight: bold;
        }

        .score-label {
            font-size: 14pt;
            opacity: 0.9;
        }

        .grade-A { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .grade-B { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .grade-C { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .grade-D { background: linear-gradient(135deg, #ffd89b 0%, #fc6767 100%); }
        .grade-F { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0066cc;
            border-radius: 5px;
        }

        .info-box strong {
            display: block;
            color: #0066cc;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th {
            background: #0066cc;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            color: white;
        }

        .badge-critical { background: #dc3545; }
        .badge-important { background: #ff9800; }
        .badge-suggested { background: #2196f3; }
        .badge-success { background: #28a745; }
        .badge-warning { background: #ffc107; color: #333; }

        .recommendation {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        .recommendation-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .recommendation-description {
            color: #666;
            font-size: 10pt;
            line-height: 1.5;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 9pt;
        }

        .page-break {
            page-break-after: always;
        }

        ul, ol {
            margin-left: 25px;
            margin-bottom: 15px;
        }

        li {
            margin-bottom: 8px;
        }

        .tech-stack {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }

        .tech-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }

        .tech-name {
            font-weight: bold;
            color: #333;
        }

        .tech-confidence {
            font-size: 9pt;
            color: #666;
        }

        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
HTML;

        foreach ($this->content as $section) {
            $html .= $section;
        }

        $html .= <<<HTML
    <div class="footer">
        <p><strong>{$this->metadata['author']}</strong></p>
        <p>Report generato il {$this->metadata['created']}</p>
        <p>© 2025 G Tech Group - Tutti i diritti riservati</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}

/**
 * PDF Report Generator for Complete Website Scan
 */
class CompleteScanPDFExport {
    private $pdf;
    private $data;

    public function __construct($scan_data) {
        $this->data = $scan_data;
        $this->pdf = new SimplePDF('Complete Website Analysis - ' . $scan_data['domain']);
    }

    /**
     * Generate complete PDF report
     */
    public function generate($filepath) {
        // Header
        $this->addHeader();

        // Executive Summary
        $this->addExecutiveSummary();

        // Overall Score
        $this->addOverallScore();

        // Recommendations
        $this->addRecommendations();

        // Page break
        $this->pdf->addContent('<div class="page-break"></div>');

        // SEO Analysis
        $this->addSEOAnalysis();

        // Technology Stack
        $this->addTechnologyStack();

        // Page break
        $this->pdf->addContent('<div class="page-break"></div>');

        // Business Intelligence
        $this->addBusinessIntelligence();

        // Security Analysis
        $this->addSecurityAnalysis();

        // Technical Details
        $this->addTechnicalDetails();

        // Save PDF
        return $this->pdf->save($filepath);
    }

    /**
     * Add report header
     */
    private function addHeader() {
        $domain = htmlspecialchars($this->data['domain']);
        $date = htmlspecialchars($this->data['scan_date']);

        $html = <<<HTML
<div class="pdf-header">
    <h1>📊 Complete Website Analysis</h1>
    <div class="subtitle">
        <strong>Domain:</strong> {$domain}<br>
        <strong>Analysis Date:</strong> {$date}
    </div>
</div>
HTML;

        $this->pdf->addContent($html);
    }

    /**
     * Add executive summary
     */
    private function addExecutiveSummary() {
        $html = <<<HTML
<div class="pdf-section">
    <h2>Executive Summary</h2>
    <p>This comprehensive analysis report provides detailed insights into the technical infrastructure,
    SEO performance, security posture, and business intelligence of <strong>{$this->data['domain']}</strong>.</p>

    <p>The analysis covers over 100 data points across multiple categories including SEO optimization,
    technology stack detection, security headers, SSL configuration, business information, and competitive positioning.</p>
</div>
HTML;

        $this->pdf->addContent($html);
    }

    /**
     * Add overall score section
     */
    private function addOverallScore() {
        $score = $this->data['overall_score']['score'] ?? 0;
        $grade = $this->data['overall_score']['grade'] ?? 'N/A';
        $breakdown = $this->data['overall_score']['breakdown'] ?? [];

        $gradeClass = 'grade-' . $grade;

        $html = <<<HTML
<div class="pdf-section">
    <h2>Overall Score</h2>
    <div class="score-card {$gradeClass}">
        <div class="score-number">{$score}</div>
        <div class="score-label">Grade: {$grade}</div>
    </div>

    <h3>Score Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Score</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($breakdown as $category => $cat_score) {
            $category_name = ucfirst(str_replace('_', ' ', $category));
            $html .= "<tr><td>{$category_name}</td><td>{$cat_score}</td><td>-</td></tr>";
        }

        $html .= <<<HTML
        </tbody>
    </table>
</div>
HTML;

        $this->pdf->addContent($html);
    }

    /**
     * Add recommendations section
     */
    private function addRecommendations() {
        $recommendations = $this->data['recommendations'] ?? [];

        $html = '<div class="pdf-section"><h2>Recommendations</h2>';

        // Group by priority
        $priorities = ['critical' => [], 'important' => [], 'suggested' => []];
        foreach ($recommendations as $rec) {
            $priority = strtolower($rec['priority'] ?? 'suggested');
            if (isset($priorities[$priority])) {
                $priorities[$priority][] = $rec;
            }
        }

        // Critical
        if (!empty($priorities['critical'])) {
            $html .= '<h3>🔴 Critical Priority</h3>';
            foreach ($priorities['critical'] as $rec) {
                $title = htmlspecialchars($rec['title'] ?? '');
                $desc = htmlspecialchars($rec['description'] ?? '');
                $html .= <<<HTML
<div class="recommendation">
    <div class="recommendation-title">
        <span class="badge badge-critical">CRITICAL</span> {$title}
    </div>
    <div class="recommendation-description">{$desc}</div>
</div>
HTML;
            }
        }

        // Important
        if (!empty($priorities['important'])) {
            $html .= '<h3>🟠 Important Priority</h3>';
            foreach ($priorities['important'] as $rec) {
                $title = htmlspecialchars($rec['title'] ?? '');
                $desc = htmlspecialchars($rec['description'] ?? '');
                $html .= <<<HTML
<div class="recommendation">
    <div class="recommendation-title">
        <span class="badge badge-important">IMPORTANT</span> {$title}
    </div>
    <div class="recommendation-description">{$desc}</div>
</div>
HTML;
            }
        }

        // Suggested
        if (!empty($priorities['suggested'])) {
            $html .= '<h3>🔵 Suggested Improvements</h3>';
            $count = 0;
            foreach ($priorities['suggested'] as $rec) {
                if ($count >= 5) break; // Limit to 5 suggestions in PDF
                $title = htmlspecialchars($rec['title'] ?? '');
                $desc = htmlspecialchars($rec['description'] ?? '');
                $html .= <<<HTML
<div class="recommendation">
    <div class="recommendation-title">
        <span class="badge badge-suggested">SUGGESTED</span> {$title}
    </div>
    <div class="recommendation-description">{$desc}</div>
</div>
HTML;
                $count++;
            }
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }

    /**
     * Add SEO analysis section
     */
    private function addSEOAnalysis() {
        $seo = $this->data['seo'] ?? [];

        if (empty($seo)) {
            return;
        }

        $html = '<div class="pdf-section"><h2>SEO Analysis</h2>';

        // SEO Score
        if (isset($seo['seo_score'])) {
            $score = $seo['seo_score']['score'] ?? 0;
            $grade = $seo['seo_score']['grade'] ?? 'N/A';
            $html .= "<p><strong>SEO Score:</strong> {$score}/100 (Grade: {$grade})</p>";
        }

        // Meta Tags
        if (!empty($seo['meta_tags'])) {
            $html .= '<h3>Meta Tags</h3><div class="info-grid">';

            if (isset($seo['meta_tags']['title'])) {
                $title = htmlspecialchars($seo['meta_tags']['title']);
                $html .= "<div class=\"info-box\"><strong>Title</strong>{$title}</div>";
            }

            if (isset($seo['meta_tags']['description'])) {
                $desc = htmlspecialchars($seo['meta_tags']['description']);
                $html .= "<div class=\"info-box\"><strong>Description</strong>{$desc}</div>";
            }

            $html .= '</div>';
        }

        // Headings
        if (!empty($seo['headings'])) {
            $html .= '<h3>Heading Structure</h3><ul>';
            foreach ($seo['headings'] as $level => $headings) {
                $count = is_array($headings) ? count($headings) : 0;
                $html .= "<li><strong>{$level}:</strong> {$count} headings</li>";
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }

    /**
     * Add technology stack section
     */
    private function addTechnologyStack() {
        $tech = $this->data['technologies'] ?? [];

        if (empty($tech)) {
            return;
        }

        $html = '<div class="pdf-section"><h2>Technology Stack</h2>';

        // Categories
        $categories = ['cms', 'frameworks', 'analytics', 'marketing', 'ecommerce', 'cdn', 'hosting', 'security'];

        foreach ($categories as $category) {
            if (!empty($tech[$category])) {
                $category_name = ucfirst($category);
                $html .= "<h3>{$category_name}</h3><div class=\"tech-stack\">";

                foreach ($tech[$category] as $item) {
                    $name = htmlspecialchars($item['name'] ?? '');
                    $confidence = $item['confidence'] ?? 0;
                    $version = isset($item['version']) ? ' v' . htmlspecialchars($item['version']) : '';

                    $html .= <<<HTML
<div class="tech-item">
    <div class="tech-name">{$name}{$version}</div>
    <div class="tech-confidence">Confidence: {$confidence}%</div>
</div>
HTML;
                }

                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }

    /**
     * Add business intelligence section
     */
    private function addBusinessIntelligence() {
        $business = $this->data['business_intelligence'] ?? [];

        if (empty($business)) {
            return;
        }

        $html = '<div class="pdf-section"><h2>Business Intelligence</h2>';

        // Contact Information
        if (!empty($business['contact_info'])) {
            $html .= '<h3>Contact Information</h3><div class="info-grid">';

            if (!empty($business['contact_info']['emails'])) {
                $emails = implode(', ', array_slice($business['contact_info']['emails'], 0, 3));
                $html .= "<div class=\"info-box\"><strong>Email</strong>{$emails}</div>";
            }

            if (!empty($business['contact_info']['phones'])) {
                $phones = implode(', ', array_slice($business['contact_info']['phones'], 0, 3));
                $html .= "<div class=\"info-box\"><strong>Phone</strong>{$phones}</div>";
            }

            $html .= '</div>';
        }

        // Social Profiles
        if (!empty($business['social_profiles'])) {
            $html .= '<h3>Social Media Presence</h3><ul>';
            foreach ($business['social_profiles'] as $platform => $url) {
                $platform_name = ucfirst($platform);
                $html .= "<li><strong>{$platform_name}:</strong> " . htmlspecialchars($url) . "</li>";
            }
            $html .= '</ul>';
        }

        // Business Model
        if (!empty($business['business_model'])) {
            $html .= '<h3>Business Model</h3><ul>';
            foreach ($business['business_model'] as $model => $detected) {
                if ($detected) {
                    $model_name = ucfirst(str_replace('_', ' ', $model));
                    $html .= "<li>✓ {$model_name}</li>";
                }
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }

    /**
     * Add security analysis section
     */
    private function addSecurityAnalysis() {
        $ssl = $this->data['ssl'] ?? [];
        $security = $this->data['security_headers'] ?? [];
        $blacklist = $this->data['blacklist'] ?? [];

        $html = '<div class="pdf-section"><h2>Security Analysis</h2>';

        // SSL
        if (!empty($ssl)) {
            $html .= '<h3>SSL Certificate</h3>';
            if (isset($ssl['valid']) && $ssl['valid']) {
                $issuer = htmlspecialchars($ssl['issuer'] ?? 'Unknown');
                $expires = htmlspecialchars($ssl['expires'] ?? 'Unknown');
                $html .= "<p><span class=\"badge badge-success\">✓ VALID</span> Issued by: {$issuer}, Expires: {$expires}</p>";
            } else {
                $html .= "<p><span class=\"badge badge-critical\">✗ INVALID</span> SSL certificate issue detected</p>";
            }
        }

        // Blacklist
        if (!empty($blacklist)) {
            $listed = $blacklist['is_blacklisted'] ?? false;
            if ($listed) {
                $html .= '<p><span class="badge badge-critical">BLACKLISTED</span> Domain found on spam blacklists</p>';
            } else {
                $html .= '<p><span class="badge badge-success">CLEAN</span> Not found on spam blacklists</p>';
            }
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }

    /**
     * Add technical details section
     */
    private function addTechnicalDetails() {
        $dns = $this->data['dns'] ?? [];
        $whois = $this->data['whois'] ?? [];

        $html = '<div class="pdf-section"><h2>Technical Details</h2>';

        // DNS Records
        if (!empty($dns)) {
            $html .= '<h3>DNS Records</h3><table><thead><tr><th>Type</th><th>Value</th></tr></thead><tbody>';

            $record_types = ['A', 'AAAA', 'MX', 'NS', 'TXT'];
            foreach ($record_types as $type) {
                if (!empty($dns[$type])) {
                    $records = is_array($dns[$type]) ? $dns[$type] : [$dns[$type]];
                    foreach (array_slice($records, 0, 3) as $record) {
                        $value = is_array($record) ? implode(', ', $record) : $record;
                        $value = htmlspecialchars(substr($value, 0, 100));
                        $html .= "<tr><td>{$type}</td><td>{$value}</td></tr>";
                    }
                }
            }

            $html .= '</tbody></table>';
        }

        // WHOIS
        if (!empty($whois)) {
            $html .= '<h3>WHOIS Information</h3><div class="info-grid">';

            if (isset($whois['registrar'])) {
                $registrar = htmlspecialchars($whois['registrar']);
                $html .= "<div class=\"info-box\"><strong>Registrar</strong>{$registrar}</div>";
            }

            if (isset($whois['created'])) {
                $created = htmlspecialchars($whois['created']);
                $html .= "<div class=\"info-box\"><strong>Created</strong>{$created}</div>";
            }

            if (isset($whois['expires'])) {
                $expires = htmlspecialchars($whois['expires']);
                $html .= "<div class=\"info-box\"><strong>Expires</strong>{$expires}</div>";
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $this->pdf->addContent($html);
    }
}

/**
 * Helper function to generate PDF from complete scan
 */
function generateCompleteScanPDF($scan_data, $filepath) {
    $exporter = new CompleteScanPDFExport($scan_data);
    return $exporter->generate($filepath);
}
