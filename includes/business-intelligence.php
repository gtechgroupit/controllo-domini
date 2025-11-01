<?php
/**
 * Business Intelligence Extraction
 *
 * Extract valuable business information from websites
 * including contact details, company info, social profiles, etc.
 *
 * @package ControlloDomin
 * @version 4.3.0
 */

class BusinessIntelligence {
    private $url;
    private $html;
    private $dom;
    private $xpath;

    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Extract all business intelligence
     */
    public function extract() {
        $this->fetchPage();

        return [
            'contact_info' => $this->extractContactInfo(),
            'company_info' => $this->extractCompanyInfo(),
            'social_profiles' => $this->extractSocialProfiles(),
            'business_hours' => $this->extractBusinessHours(),
            'pricing' => $this->extractPricingInfo(),
            'team' => $this->extractTeamInfo(),
            'testimonials' => $this->extractTestimonials(),
            'certifications' => $this->extractCertifications(),
            'legal' => $this->extractLegalInfo(),
            'languages' => $this->detectLanguages(),
            'target_audience' => $this->analyzeTargetAudience(),
            'business_model' => $this->detectBusinessModel()
        ];
    }

    /**
     * Fetch page content
     */
    private function fetchPage() {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0\r\n",
                'timeout' => 30
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
        }
    }

    /**
     * Extract contact information
     */
    private function extractContactInfo() {
        $contact = [
            'emails' => $this->extractEmails(),
            'phones' => $this->extractPhones(),
            'addresses' => $this->extractAddresses(),
            'contact_form' => $this->hasContactForm(),
            'live_chat' => $this->hasLiveChat(),
            'whatsapp' => $this->hasWhatsApp(),
            'appointment_booking' => $this->hasAppointmentBooking()
        ];

        return $contact;
    }

    /**
     * Extract email addresses
     */
    private function extractEmails() {
        $emails = [];

        // Find emails in text
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $this->html, $matches);
        $emails = array_merge($emails, $matches[0]);

        // Find emails in mailto links
        $links = $this->xpath->query('//a[starts-with(@href, "mailto:")]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $email = str_replace('mailto:', '', $href);
            $email = explode('?', $email)[0]; // Remove parameters
            $emails[] = $email;
        }

        // Remove duplicates and common false positives
        $emails = array_unique($emails);
        $emails = array_filter($emails, function($email) {
            return !in_array(strtolower($email), [
                'example@example.com',
                'test@test.com',
                'email@example.com'
            ]);
        });

        return array_values($emails);
    }

    /**
     * Extract phone numbers
     */
    private function extractPhones() {
        $phones = [];

        // Various phone number patterns
        $patterns = [
            '/\+\d{1,3}[\s.-]?\(?\d{1,4}\)?[\s.-]?\d{1,4}[\s.-]?\d{1,9}/',  // International
            '/\d{3}[\s.-]?\d{3}[\s.-]?\d{4}/',  // US/CA format
            '/\(\d{3}\)[\s.-]?\d{3}[\s.-]?\d{4}/',  // (xxx) xxx-xxxx
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $this->html, $matches);
            $phones = array_merge($phones, $matches[0]);
        }

        // Find phones in tel: links
        $links = $this->xpath->query('//a[starts-with(@href, "tel:")]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $phone = str_replace('tel:', '', $href);
            $phones[] = $phone;
        }

        return array_unique($phones);
    }

    /**
     * Extract addresses
     */
    private function extractAddresses() {
        $addresses = [];

        // Look for address in schema.org
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['address'])) {
                $addresses[] = $this->formatAddress($json['address']);
            }
            if (isset($json['location']['address'])) {
                $addresses[] = $this->formatAddress($json['location']['address']);
            }
        }

        // Look for microdata
        $elements = $this->xpath->query('//*[@itemprop="address"]');
        foreach ($elements as $element) {
            $addresses[] = trim($element->textContent);
        }

        return array_unique($addresses);
    }

    /**
     * Format address from schema
     */
    private function formatAddress($address) {
        if (is_string($address)) {
            return $address;
        }

        $parts = [];
        if (isset($address['streetAddress'])) $parts[] = $address['streetAddress'];
        if (isset($address['addressLocality'])) $parts[] = $address['addressLocality'];
        if (isset($address['addressRegion'])) $parts[] = $address['addressRegion'];
        if (isset($address['postalCode'])) $parts[] = $address['postalCode'];
        if (isset($address['addressCountry'])) $parts[] = $address['addressCountry'];

        return implode(', ', $parts);
    }

    /**
     * Check if has contact form
     */
    private function hasContactForm() {
        $forms = $this->xpath->query('//form');
        foreach ($forms as $form) {
            $action = strtolower($form->getAttribute('action'));
            $inputs = $this->xpath->query('.//input|.//textarea', $form);

            $hasEmail = false;
            $hasMessage = false;

            foreach ($inputs as $input) {
                $type = strtolower($input->getAttribute('type'));
                $name = strtolower($input->getAttribute('name'));

                if ($type === 'email' || strpos($name, 'email') !== false) {
                    $hasEmail = true;
                }
                if ($input->nodeName === 'textarea' || strpos($name, 'message') !== false) {
                    $hasMessage = true;
                }
            }

            if ($hasEmail && $hasMessage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if has live chat
     */
    private function hasLiveChat() {
        $chatPatterns = [
            'intercom', 'drift', 'zendesk', 'livechat', 'tawk', 'crisp',
            'olark', 'freshchat', 'liveperson', 'chat-widget'
        ];

        foreach ($chatPatterns as $pattern) {
            if (stripos($this->html, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if has WhatsApp
     */
    private function hasWhatsApp() {
        return stripos($this->html, 'wa.me') !== false ||
               stripos($this->html, 'whatsapp://') !== false ||
               stripos($this->html, 'whatsapp') !== false;
    }

    /**
     * Check if has appointment booking
     */
    private function hasAppointmentBooking() {
        $bookingPatterns = [
            'calendly', 'acuity', 'booking', 'appointment',
            'schedule', 'book now', 'booksy', 'simplybook'
        ];

        foreach ($bookingPatterns as $pattern) {
            if (stripos($this->html, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract company information
     */
    private function extractCompanyInfo() {
        $company = [
            'name' => $this->extractCompanyName(),
            'description' => $this->extractDescription(),
            'founded' => $this->extractFoundedYear(),
            'size' => $this->extractCompanySize(),
            'industry' => $this->extractIndustry(),
            'vat_number' => $this->extractVAT(),
            'registration_number' => $this->extractRegistrationNumber()
        ];

        return $company;
    }

    /**
     * Extract company name
     */
    private function extractCompanyName() {
        // From schema.org
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['name']) && in_array($json['@type'], ['Organization', 'LocalBusiness', 'Corporation'])) {
                return $json['name'];
            }
        }

        // From Open Graph
        $og_name = $this->xpath->query('//meta[@property="og:site_name"]');
        if ($og_name->length > 0) {
            return $og_name->item(0)->getAttribute('content');
        }

        // From title
        $title = $this->xpath->query('//title');
        if ($title->length > 0) {
            return trim($title->item(0)->textContent);
        }

        return null;
    }

    /**
     * Extract description
     */
    private function extractDescription() {
        $meta = $this->xpath->query('//meta[@name="description"]');
        if ($meta->length > 0) {
            return $meta->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Extract founded year
     */
    private function extractFoundedYear() {
        // From schema.org
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['foundingDate'])) {
                return $json['foundingDate'];
            }
        }

        // Try to find in text
        if (preg_match('/founded.{0,20}(\d{4})/i', $this->html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/since.{0,20}(\d{4})/i', $this->html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract company size
     */
    private function extractCompanySize() {
        $text = strtolower($this->html);

        if (preg_match('/(\d+[\+\-]?)\s*employees?/i', $text, $matches)) {
            return $matches[1] . ' employees';
        }

        $sizeKeywords = [
            'enterprise' => 'Enterprise (1000+ employees)',
            'large company' => 'Large (200-1000 employees)',
            'medium-sized' => 'Medium (50-200 employees)',
            'small business' => 'Small (10-50 employees)',
            'startup' => 'Startup (<10 employees)'
        ];

        foreach ($sizeKeywords as $keyword => $size) {
            if (strpos($text, $keyword) !== false) {
                return $size;
            }
        }

        return null;
    }

    /**
     * Extract industry
     */
    private function extractIndustry() {
        // From schema.org
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['@type'])) {
                $type = $json['@type'];
                if (in_array($type, ['Restaurant', 'Hotel', 'Store', 'MedicalClinic', 'LegalService'])) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Extract VAT number
     */
    private function extractVAT() {
        // European VAT format
        if (preg_match('/\b[A-Z]{2}\d{8,12}\b/', $this->html, $matches)) {
            return $matches[0];
        }

        // Italian P.IVA
        if (preg_match('/P\.?IVA[\s:]*(\d{11})/', $this->html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract registration number
     */
    private function extractRegistrationNumber() {
        if (preg_match('/registration.{0,30}(\d{6,})/i', $this->html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract social profiles
     */
    private function extractSocialProfiles() {
        $profiles = [];

        $socialPatterns = [
            'facebook' => '/(?:https?:)?\/\/(?:www\.)?facebook\.com\/[a-zA-Z0-9\.\-_]+/i',
            'twitter' => '/(?:https?:)?\/\/(?:www\.)?(?:twitter\.com|x\.com)\/[a-zA-Z0-9_]+/i',
            'instagram' => '/(?:https?:)?\/\/(?:www\.)?instagram\.com\/[a-zA-Z0-9\.\-_]+/i',
            'linkedin' => '/(?:https?:)?\/\/(?:www\.)?linkedin\.com\/(company|in)\/[a-zA-Z0-9\-_]+/i',
            'youtube' => '/(?:https?:)?\/\/(?:www\.)?youtube\.com\/(channel|c|user)\/[a-zA-Z0-9\-_]+/i',
            'tiktok' => '/(?:https?:)?\/\/(?:www\.)?tiktok\.com\/@[a-zA-Z0-9\.\-_]+/i',
            'pinterest' => '/(?:https?:)?\/\/(?:www\.)?pinterest\.com\/[a-zA-Z0-9\.\-_]+/i',
            'github' => '/(?:https?:)?\/\/(?:www\.)?github\.com\/[a-zA-Z0-9\-_]+/i'
        ];

        foreach ($socialPatterns as $platform => $pattern) {
            if (preg_match($pattern, $this->html, $matches)) {
                $profiles[$platform] = $matches[0];
            }
        }

        return $profiles;
    }

    /**
     * Extract business hours
     */
    private function extractBusinessHours() {
        $hours = [];

        // From schema.org
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['openingHoursSpecification'])) {
                $hours = $json['openingHoursSpecification'];
                break;
            }
        }

        return $hours;
    }

    /**
     * Extract pricing information
     */
    private function extractPricingInfo() {
        $pricing = [
            'has_pricing' => false,
            'currency' => $this->detectCurrency(),
            'price_range' => $this->extractPriceRange(),
            'payment_methods' => $this->detectPaymentMethods()
        ];

        $pricing['has_pricing'] = !empty($pricing['price_range']);

        return $pricing;
    }

    /**
     * Detect currency
     */
    private function detectCurrency() {
        $currencies = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            '¥' => 'JPY',
            'CHF' => 'CHF'
        ];

        foreach ($currencies as $symbol => $code) {
            if (strpos($this->html, $symbol) !== false) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Extract price range
     */
    private function extractPriceRange() {
        preg_match_all('/[\$€£¥]\s*\d+(?:[.,]\d{2})?/', $this->html, $matches);

        if (!empty($matches[0])) {
            $prices = array_map(function($price) {
                return (float)preg_replace('/[^\d.]/', '', $price);
            }, $matches[0]);

            return [
                'min' => min($prices),
                'max' => max($prices),
                'count' => count($prices)
            ];
        }

        return null;
    }

    /**
     * Detect payment methods
     */
    private function detectPaymentMethods() {
        $methods = [];

        $paymentPatterns = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'apple pay' => 'Apple Pay',
            'google pay' => 'Google Pay',
            'klarna' => 'Klarna'
        ];

        foreach ($paymentPatterns as $pattern => $name) {
            if (stripos($this->html, $pattern) !== false) {
                $methods[] = $name;
            }
        }

        return $methods;
    }

    /**
     * Extract team information
     */
    private function extractTeamInfo() {
        $team = [
            'has_team_page' => $this->hasTeamPage(),
            'team_size_estimate' => $this->estimateTeamSize()
        ];

        return $team;
    }

    /**
     * Check if has team page
     */
    private function hasTeamPage() {
        $links = $this->xpath->query('//a/@href');
        foreach ($links as $link) {
            $href = strtolower($link->nodeValue);
            if (preg_match('/(team|about|chi-siamo|staff|people)/i', $href)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Estimate team size
     */
    private function estimateTeamSize() {
        // Count team member schema
        $count = 0;
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['employee']) || isset($json['member'])) {
                $count = count($json['employee'] ?? $json['member']);
            }
        }

        return $count > 0 ? $count : null;
    }

    /**
     * Extract testimonials
     */
    private function extractTestimonials() {
        $testimonials = [
            'has_testimonials' => false,
            'count' => 0
        ];

        // Check schema.org Review
        $schemas = $this->xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemas as $schema) {
            $json = json_decode($schema->textContent, true);
            if (isset($json['review']) || isset($json['@type']) === 'Review') {
                $testimonials['has_testimonials'] = true;
                $testimonials['count']++;
            }
        }

        // Check common class names
        $elements = $this->xpath->query('//*[contains(@class, "testimonial") or contains(@class, "review")]');
        if ($elements->length > 0) {
            $testimonials['has_testimonials'] = true;
            $testimonials['count'] = max($testimonials['count'], $elements->length);
        }

        return $testimonials;
    }

    /**
     * Extract certifications
     */
    private function extractCertifications() {
        $certifications = [];

        $certPatterns = [
            'ISO 9001', 'ISO 27001', 'PCI DSS', 'GDPR compliant',
            'SOC 2', 'HIPAA', 'FDA approved', 'CE marking',
            'Google Partner', 'Microsoft Partner', 'AWS Partner'
        ];

        foreach ($certPatterns as $cert) {
            if (stripos($this->html, $cert) !== false) {
                $certifications[] = $cert;
            }
        }

        return $certifications;
    }

    /**
     * Extract legal information
     */
    private function extractLegalInfo() {
        $legal = [
            'privacy_policy' => $this->hasPage('privacy'),
            'terms_of_service' => $this->hasPage('terms'),
            'cookie_policy' => $this->hasPage('cookie'),
            'gdpr_compliant' => $this->hasGDPR(),
            'age_restriction' => $this->hasAgeRestriction()
        ];

        return $legal;
    }

    /**
     * Check if has specific page
     */
    private function hasPage($keyword) {
        $links = $this->xpath->query('//a/@href');
        foreach ($links as $link) {
            if (stripos($link->nodeValue, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check GDPR compliance
     */
    private function hasGDPR() {
        return stripos($this->html, 'gdpr') !== false ||
               stripos($this->html, 'cookie consent') !== false ||
               stripos($this->html, 'cookie banner') !== false;
    }

    /**
     * Check age restriction
     */
    private function hasAgeRestriction() {
        return stripos($this->html, '18+') !== false ||
               stripos($this->html, 'age verification') !== false ||
               stripos($this->html, 'adult content') !== false;
    }

    /**
     * Detect languages
     */
    private function detectLanguages() {
        $languages = [];

        // From html lang attribute
        $html = $this->xpath->query('//html');
        if ($html->length > 0) {
            $lang = $html->item(0)->getAttribute('lang');
            if ($lang) {
                $languages[] = $lang;
            }
        }

        // From hreflang
        $links = $this->xpath->query('//link[@rel="alternate"][@hreflang]');
        foreach ($links as $link) {
            $languages[] = $link->getAttribute('hreflang');
        }

        return array_unique($languages);
    }

    /**
     * Analyze target audience
     */
    private function analyzeTargetAudience() {
        $audience = [
            'b2b' => false,
            'b2c' => false,
            'geo_target' => $this->detectGeoTarget()
        ];

        $b2b_keywords = ['enterprise', 'business', 'corporate', 'b2b', 'solution', 'roi'];
        $b2c_keywords = ['shop', 'buy', 'customer', 'personal', 'individual', 'b2c'];

        $text = strtolower($this->html);

        $b2b_score = 0;
        $b2c_score = 0;

        foreach ($b2b_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $b2b_score++;
            }
        }

        foreach ($b2c_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $b2c_score++;
            }
        }

        $audience['b2b'] = $b2b_score > 0;
        $audience['b2c'] = $b2c_score > 0;

        if ($b2b_score > $b2c_score) {
            $audience['primary'] = 'B2B';
        } elseif ($b2c_score > $b2b_score) {
            $audience['primary'] = 'B2C';
        } else {
            $audience['primary'] = 'Mixed';
        }

        return $audience;
    }

    /**
     * Detect geographic target
     */
    private function detectGeoTarget() {
        $countries = [];

        // From hreflang
        $links = $this->xpath->query('//link[@rel="alternate"][@hreflang]');
        foreach ($links as $link) {
            $hreflang = $link->getAttribute('hreflang');
            if (strlen($hreflang) === 5 && $hreflang[2] === '-') {
                $countries[] = strtoupper(substr($hreflang, 3));
            }
        }

        return array_unique($countries);
    }

    /**
     * Detect business model
     */
    private function detectBusinessModel() {
        $model = [
            'ecommerce' => false,
            'saas' => false,
            'marketplace' => false,
            'blog' => false,
            'portfolio' => false,
            'lead_generation' => false
        ];

        $text = strtolower($this->html);

        // E-commerce
        if (stripos($text, 'add to cart') !== false ||
            stripos($text, 'checkout') !== false ||
            stripos($text, 'woocommerce') !== false) {
            $model['ecommerce'] = true;
        }

        // SaaS
        if (stripos($text, 'subscription') !== false ||
            stripos($text, 'pricing') !== false ||
            stripos($text, 'sign up') !== false) {
            $model['saas'] = true;
        }

        // Marketplace
        if (stripos($text, 'marketplace') !== false ||
            stripos($text, 'seller') !== false ||
            stripos($text, 'vendor') !== false) {
            $model['marketplace'] = true;
        }

        // Blog
        if (stripos($text, 'blog') !== false ||
            stripos($text, 'article') !== false) {
            $model['blog'] = true;
        }

        // Portfolio
        if (stripos($text, 'portfolio') !== false ||
            stripos($text, 'projects') !== false) {
            $model['portfolio'] = true;
        }

        // Lead generation
        if (stripos($text, 'contact us') !== false ||
            stripos($text, 'get a quote') !== false ||
            stripos($text, 'request demo') !== false) {
            $model['lead_generation'] = true;
        }

        return $model;
    }
}

/**
 * Helper function
 */
function extractBusinessIntelligence($url) {
    $extractor = new BusinessIntelligence($url);
    return $extractor->extract();
}
