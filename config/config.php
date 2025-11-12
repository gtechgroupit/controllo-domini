<?php
/**
 * Configurazione Controllo Domini
 * Sistema professionale per l'analisi DNS e WHOIS
 *
 * @author G Tech Group
 * @version 4.2.1
 * @website https://controllodomini.it
 */

// Configurazione base
define('APP_NAME', 'Controllo Domini');
define('APP_VERSION', '4.2.1');
define('APP_AUTHOR', 'G Tech Group');
define('APP_URL', 'https://controllodomini.it');
define('APP_DESCRIPTION', 'Strumento professionale gratuito per l\'analisi completa di domini, DNS, WHOIS e blacklist. Verifica la configurazione DNS, identifica servizi cloud e controlla la reputazione del dominio.');

// Meta tags SEO
define('SEO_TITLE', 'Controllo Domini - Analisi DNS, WHOIS e Blacklist Gratuita');
define('SEO_DESCRIPTION', 'Analizza gratuitamente qualsiasi dominio: verifica DNS, record MX, SPF, DKIM, DMARC, informazioni WHOIS, presenza in blacklist e servizi cloud come Microsoft 365 e Google Workspace.');
define('SEO_KEYWORDS', 'controllo domini, verifica dns, whois lookup, controllo blacklist, analisi dominio, dns checker, mx record, spf record, dkim, dmarc, microsoft 365, google workspace, reputazione dominio, dns italia');
define('SEO_AUTHOR', 'G Tech Group');
define('SEO_ROBOTS', 'index, follow');
define('SEO_CANONICAL', 'https://controllodomini.it');

// Open Graph tags
define('OG_TITLE', 'Controllo Domini - Analisi Professionale DNS e WHOIS');
define('OG_DESCRIPTION', 'Strumento gratuito per analizzare domini, verificare DNS, controllare WHOIS e blacklist. Identifica servizi cloud e verifica la salute del tuo dominio.');
define('OG_TYPE', 'website');
define('OG_URL', 'https://controllodomini.it');
define('OG_IMAGE', 'https://controllodomini.it/assets/images/og-image.jpg');
define('OG_SITE_NAME', 'Controllo Domini');
define('OG_LOCALE', 'it_IT');

// Twitter Card
define('TWITTER_CARD', 'summary_large_image');
define('TWITTER_TITLE', 'Controllo Domini - Analisi DNS e WHOIS Professionale');
define('TWITTER_DESCRIPTION', 'Analizza gratuitamente domini, DNS, WHOIS e blacklist. Verifica la configurazione e la reputazione del tuo dominio.');
define('TWITTER_IMAGE', 'https://controllodomini.it/assets/images/twitter-card.jpg');
define('TWITTER_SITE', '@gtechgroup');

// Schema.org structured data
define('SCHEMA_ORG', json_encode([
    "@context" => "https://schema.org",
    "@type" => "WebApplication",
    "name" => "Controllo Domini",
    "alternateName" => "DNS Check Italia",
    "url" => "https://controllodomini.it",
    "description" => "Strumento professionale per l'analisi completa di domini, DNS, WHOIS e blacklist",
    "applicationCategory" => "UtilityApplication",
    "operatingSystem" => "All",
    "offers" => [
        "@type" => "Offer",
        "price" => "0",
        "priceCurrency" => "EUR"
    ],
    "author" => [
        "@type" => "Organization",
        "name" => "G Tech Group",
        "url" => "https://gtechgroup.it"
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => "4.8",
        "ratingCount" => "1250"
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// Configurazione PHP
@ini_set('display_errors', 0);
@ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Headers di sicurezza
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com; " .
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
       "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self' https://www.google-analytics.com; " .
       "frame-ancestors 'self'; " .
       "base-uri 'self'; " .
       "form-action 'self'";
header("Content-Security-Policy: " . $csp);

// HTTP Strict Transport Security (HSTS)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Timezone
date_default_timezone_set('Europe/Rome');

// Configurazione WHOIS servers
$GLOBALS['whois_servers'] = array(
    'com' => 'whois.verisign-grs.com',
    'net' => 'whois.verisign-grs.com',
    'org' => 'whois.pir.org',
    'info' => 'whois.afilias.net',
    'biz' => 'whois.biz',
    'it' => 'whois.nic.it',
    'eu' => 'whois.eu',
    'de' => 'whois.denic.de',
    'fr' => 'whois.afnic.fr',
    'uk' => 'whois.nic.uk',
    'co.uk' => 'whois.nic.uk',
    'org.uk' => 'whois.nic.uk',
    'nl' => 'whois.domain-registry.nl',
    'es' => 'whois.nic.es',
    'ch' => 'whois.nic.ch',
    'at' => 'whois.nic.at',
    'be' => 'whois.dns.be',
    'jp' => 'whois.jprs.jp',
    'cn' => 'whois.cnnic.cn',
    'au' => 'whois.auda.org.au',
    'com.au' => 'whois.auda.org.au',
    'ca' => 'whois.cira.ca',
    'us' => 'whois.nic.us',
    'mx' => 'whois.mx',
    'br' => 'whois.registro.br',
    'io' => 'whois.nic.io',
    'me' => 'whois.nic.me',
    'tv' => 'whois.tv',
    'cc' => 'whois.nic.cc',
    'ws' => 'whois.website.ws',
    'mobi' => 'whois.dotmobiregistry.net',
    'pro' => 'whois.registry.pro',
    'edu' => 'whois.educause.edu',
    'gov' => 'whois.nic.gov'
);

// Configurazione blacklist DNS
$GLOBALS['dnsbl_servers'] = array(
    'zen.spamhaus.org' => 'Spamhaus ZEN',
    'bl.spamcop.net' => 'SpamCop',
    'b.barracudacentral.org' => 'Barracuda',
    'dnsbl.sorbs.net' => 'SORBS',
    'spam.dnsbl.sorbs.net' => 'SORBS Spam',
    'cbl.abuseat.org' => 'CBL Abuseat',
    'dnsbl-1.uceprotect.net' => 'UCEPROTECT Level 1',
    'psbl.surriel.com' => 'PSBL',
    'db.wpbl.info' => 'WPBL',
    'ix.dnsbl.manitu.net' => 'Manitu',
    'combined.rbl.msrbl.net' => 'MSRBL',
    'multi.spamhaus.org' => 'Spamhaus Multi',
    'bogons.cymru.com' => 'Team Cymru Bogons',
    'tor.dan.me.uk' => 'TOR Exit Nodes',
    'rbl.spamlab.com' => 'SpamLab',
    'noptr.spamrats.com' => 'SpamRats NoPtr',
    'spam.spamrats.com' => 'SpamRats Spam',
    'virbl.dnsbl.bit.nl' => 'VirBL',
    'wormrbl.imp.ch' => 'Worm RBL',
    'spamguard.leadmon.net' => 'SpamGuard'
);

// Indicatori servizi cloud
$GLOBALS['cloud_indicators'] = array(
    'microsoft365' => array(
        'mx' => array('outlook.com', 'mail.protection.outlook.com'),
        'txt' => array('MS=', 'v=spf1 include:spf.protection.outlook.com'),
        'cname' => array('autodiscover.outlook.com', 'enterpriseregistration.windows.net', 'enterpriseenrollment.manage.microsoft.com')
    ),
    'google_workspace' => array(
        'mx' => array('aspmx.l.google.com', 'alt1.aspmx.l.google.com', 'alt2.aspmx.l.google.com'),
        'txt' => array('google-site-verification=', 'v=spf1 include:_spf.google.com'),
        'cname' => array('ghs.google.com', 'googlehosted.com')
    )
);

// Configurazione cache (per future implementazioni)
define('CACHE_ENABLED', false);
define('CACHE_TTL', 3600); // 1 ora

// Limiti di rate
define('RATE_LIMIT_ENABLED', false);
define('RATE_LIMIT_REQUESTS', 100); // richieste per IP
define('RATE_LIMIT_WINDOW', 3600); // finestra temporale in secondi

// Debug mode - solo per ambiente di sviluppo
define('DEBUG_MODE', false); // Cambiare manualmente a true solo in sviluppo

// Analytics (Google Analytics, Matomo, etc.)
define('ANALYTICS_ENABLED', true);
define('GA_TRACKING_ID', 'G-XXXXXXXXXX'); // Sostituire con ID reale

// Breadcrumb per SEO
function getBreadcrumb($current = 'Home') {
    $breadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => 'https://controllodomini.it'
            ]
        ]
    ];
    
    if ($current !== 'Home') {
        $breadcrumb['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $current,
            'item' => 'https://controllodomini.it#' . strtolower(str_replace(' ', '-', $current))
        ];
    }
    
    return json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>
