<?php
/**
 * Template Header - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage Templates
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(__FILE__)) . '/');
}

// Carica configurazione se non già caricata
if (!defined('APP_NAME')) {
    require_once ABSPATH . 'config/config.php';
}

// Determina la pagina corrente per SEO dinamico
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = generatePageTitle(isset($domain) ? $domain : null);
$page_description = generateMetaDescription(isset($domain) ? $domain : null);
$canonical_url = APP_URL . $_SERVER['REQUEST_URI'];

// Genera breadcrumb per pagina corrente
$breadcrumb_data = getBreadcrumb(isset($page_name) ? $page_name : 'Home');
?>
<!DOCTYPE html>
<html lang="it" itemscope itemtype="https://schema.org/WebPage">
<head>
    <!-- Meta tags essenziali -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo SEO_KEYWORDS; ?>">
    <meta name="author" content="<?php echo SEO_AUTHOR; ?>">
    <meta name="robots" content="<?php echo SEO_ROBOTS; ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:type" content="<?php echo OG_TYPE; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:image" content="<?php echo OG_IMAGE; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?php echo OG_SITE_NAME; ?>">
    <meta property="og:locale" content="<?php echo OG_LOCALE; ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="<?php echo TWITTER_CARD; ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="twitter:image" content="<?php echo TWITTER_IMAGE; ?>">
    <meta name="twitter:site" content="<?php echo TWITTER_SITE; ?>">
    
    <!-- Additional Meta Tags -->
    <meta name="theme-color" content="#5d8ecf">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Controllo Domini">
    <meta name="application-name" content="Controllo Domini">
    <meta name="msapplication-TileColor" content="#5d8ecf">
    <meta name="msapplication-config" content="/browserconfig.xml">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    
    <!-- Preconnect per performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <?php if (defined('CDN_URL')): ?>
    <link rel="preconnect" href="<?php echo CDN_URL; ?>">
    <?php endif; ?>
    
    <!-- DNS Prefetch -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS - PERCORSO SEMPLIFICATO -->
    <?php 
    $css_file = '/assets/css/style.css';
    $css_path = ABSPATH . 'assets/css/style.css';
    $css_version = file_exists($css_path) ? filemtime($css_path) : time();
    ?>
    <link href="<?php echo $css_file; ?>?v=<?php echo $css_version; ?>" rel="stylesheet" type="text/css">
    
    <!-- CSS Libraries -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Critical CSS inline per performance -->
    <style>
        /* Critical CSS per above-the-fold content */
        :root{--primary:#5d8ecf;--secondary:#264573;--text-dark:#000222}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Lato',-apple-system,BlinkMacSystemFont,sans-serif;background:#f0f4f8;color:var(--text-dark)}
        .container{max-width:1400px;margin:0 auto;padding:0 20px}
        nav{background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);position:fixed;top:0;left:0;right:0;z-index:1000}
        .hero{padding:140px 20px 80px;text-align:center}
        .loading{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
    
    <!-- Schema.org structured data -->
    <script type="application/ld+json">
    <?php echo SCHEMA_ORG; ?>
    </script>
    
    <!-- Breadcrumb structured data -->
    <script type="application/ld+json">
    <?php echo $breadcrumb_data; ?>
    </script>
    
    <!-- FAQPage Schema (se applicabile) -->
    <?php if ($current_page === 'index'): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "Cos'è il controllo domini?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Il controllo domini è un'analisi completa che verifica i record DNS, le informazioni WHOIS, la presenza in blacklist e i servizi cloud utilizzati da un dominio."
                }
            },
            {
                "@type": "Question",
                "name": "Quali informazioni posso ottenere?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Puoi ottenere record DNS (A, MX, TXT, etc.), informazioni sull'intestatario, data di scadenza, nameserver, presenza in blacklist e servizi cloud come Microsoft 365 o Google Workspace."
                }
            },
            {
                "@type": "Question",
                "name": "Il servizio è gratuito?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Sì, Controllo Domini è un servizio completamente gratuito offerto da G Tech Group per l'analisi professionale dei domini."
                }
            }
        ]
    }
    </script>
    <?php endif; ?>
    
    <!-- Google Analytics -->
    <?php if (ANALYTICS_ENABLED && GA_TRACKING_ID): ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_TRACKING_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GA_TRACKING_ID; ?>', {
            'anonymize_ip': true,
            'cookie_flags': 'max-age=7200;secure;samesite=none'
        });
    </script>
    <?php endif; ?>
    
    <!-- Custom CSS hook -->
    <?php if (isset($custom_css)): ?>
    <style><?php echo $custom_css; ?></style>
    <?php endif; ?>
    
    <!-- Head scripts hook -->
    <?php if (isset($head_scripts)): ?>
    <?php echo $head_scripts; ?>
    <?php endif; ?>
</head>

<body class="<?php echo isset($body_class) ? htmlspecialchars($body_class) : ''; ?>">
    <!-- Skip to content per accessibilità -->
    <a href="#main-content" class="skip-link">Vai al contenuto principale</a>
    
    <!-- Google Tag Manager (noscript) -->
    <?php if (ANALYTICS_ENABLED && defined('GTM_ID')): ?>
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo GTM_ID; ?>"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <?php endif; ?>
    
    <!-- Background decorativo -->
    <div class="background-gradient"></div>
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>
    
    <!-- Navigation -->
    <nav id="navbar" role="navigation" aria-label="Menu principale">
        <div class="container">
            <div class="nav-container">
                <a href="/" class="logo" aria-label="Controllo Domini Home">
                    <?php if (file_exists(ABSPATH . 'assets/images/logo.svg')): ?>
                    <img src="/assets/images/logo.svg" alt="Controllo Domini Logo" width="40" height="40">
                    <?php endif; ?>
                    <span>Controllo Domini</span>
                </a>
                
                <div class="nav-links" id="navLinks">
                    <a href="/" <?php echo $current_page === 'index' ? 'aria-current="page"' : ''; ?>>Home</a>
                    <a href="/#features">Funzionalità</a>
                    <a href="/#how-it-works">Come Funziona</a>
                    <a href="/#faq">FAQ</a>
                    <a href="/api-docs" <?php echo $current_page === 'api-docs' ? 'aria-current="page"' : ''; ?>>API</a>
                    <a href="/contatti" <?php echo $current_page === 'contatti' ? 'aria-current="page"' : ''; ?>>Contatti</a>
                </div>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu mobile" aria-expanded="false" aria-controls="navLinks">
                    <span>☰</span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section (solo per homepage) -->
    <?php if ($current_page === 'index' && !isset($hide_hero)): ?>
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1 class="gradient-text">Controllo Domini Professionale</h1>
                <p class="hero-subtitle">
                    Analisi completa DNS, WHOIS e blacklist per qualsiasi dominio.<br>
                    Identifica servizi cloud e verifica la salute del tuo dominio gratuitamente.
                </p>
                
                <!-- Quick stats -->
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-value">50K+</span>
                        <span class="hero-stat-label">Domini Analizzati</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value">99.9%</span>
                        <span class="hero-stat-label">Uptime</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value">< 2s</span>
                        <span class="hero-stat-label">Tempo Medio</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Breadcrumb (per pagine interne) -->
    <?php if ($current_page !== 'index' && !isset($hide_breadcrumb)): ?>
    <div class="breadcrumb-wrapper">
        <div class="container">
            <nav aria-label="Breadcrumb" class="breadcrumb">
                <ol itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="/">
                            <span itemprop="name">Home</span>
                        </a>
                        <meta itemprop="position" content="1">
                    </li>
                    <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
                        <?php foreach ($breadcrumb_items as $index => $item): ?>
                        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <?php if (isset($item['url'])): ?>
                            <a itemprop="item" href="<?php echo htmlspecialchars($item['url']); ?>">
                                <span itemprop="name"><?php echo htmlspecialchars($item['name']); ?></span>
                            </a>
                            <?php else: ?>
                            <span itemprop="name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <?php endif; ?>
                            <meta itemprop="position" content="<?php echo $index + 2; ?>">
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main content wrapper -->
    <main id="main-content" role="main">
        <div class="container">
            
    <!-- Notice banner (opzionale) -->
    <?php if (isset($_SESSION['notice'])): ?>
    <div class="alert alert-<?php echo $_SESSION['notice']['type'] ?? 'info'; ?>" role="alert">
        <span class="alert-icon"><?php echo $_SESSION['notice']['icon'] ?? 'ℹ️'; ?></span>
        <span class="alert-content"><?php echo htmlspecialchars($_SESSION['notice']['message']); ?></span>
    </div>
    <?php unset($_SESSION['notice']); ?>
    <?php endif; ?>
    
    <!-- Cookie consent banner -->
    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
    <div id="cookieConsent" class="cookie-consent" role="region" aria-label="Cookie consent">
        <div class="cookie-consent-content">
            <p>Utilizziamo cookie tecnici e analitici per migliorare la tua esperienza. 
            <a href="/privacy" target="_blank" rel="noopener">Maggiori informazioni</a></p>
            <div class="cookie-consent-actions">
                <button class="btn btn-sm btn-secondary" onclick="acceptCookies('necessary')">Solo necessari</button>
                <button class="btn btn-sm btn-primary" onclick="acceptCookies('all')">Accetta tutti</button>
            </div>
        </div>
    </div>
    <script>
        function acceptCookies(level) {
            document.cookie = "cookie_consent=" + level + "; max-age=31536000; path=/; secure; samesite=lax";
            document.getElementById('cookieConsent').style.display = 'none';
            if (level === 'all' && typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }
        }
    </script>
    <?php endif; ?>
