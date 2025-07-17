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

// Carica utilities se non già caricate
if (!function_exists('generatePageTitle')) {
    require_once ABSPATH . 'includes/utilities.php';
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
    
    <!-- CSS -->
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
        nav{background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);position:fixed;top:40px;left:0;right:0;z-index:1000}
        .hero{padding:180px 20px 80px;text-align:center}
        .loading{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        
        /* Top bar styles */
        .top-bar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 8px 0;
            font-size: 14px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .top-bar-contacts {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .top-bar-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        
        .top-bar-item:hover {
            opacity: 0.8;
            color: white;
        }
        
        .top-bar-item svg {
            width: 16px;
            height: 16px;
        }
        
        .top-bar-cta {
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            color: #1e3c72;
            padding: 6px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .top-bar-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .top-bar-cta:hover {
            color: #1e3c72;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border-color: #ffd700;
        }
        
        .top-bar-cta:hover::before {
            left: 0;
        }
        
        .top-bar-cta svg {
            width: 16px;
            height: 16px;
            transition: transform 0.3s ease;
        }
        
        .top-bar-cta:hover svg {
            transform: translate(2px, -2px);
        }
        
        @media (max-width: 768px) {
            .top-bar {
                font-size: 12px;
                padding: 10px 0;
            }
            
            .top-bar-content {
                flex-direction: column;
                text-align: center;
            }
            
            .top-bar-contacts {
                flex-direction: column;
                gap: 10px;
            }
            
            nav {
                top: 80px;
            }
            
            .hero {
                padding-top: 220px;
            }
        }
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
    <?php if (defined('ANALYTICS_ENABLED') && ANALYTICS_ENABLED && defined('GA_TRACKING_ID') && GA_TRACKING_ID): ?>
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
    <?php if (defined('ANALYTICS_ENABLED') && ANALYTICS_ENABLED && defined('GTM_ID') && GTM_ID): ?>
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
    
    <!-- Top Bar con contatti G Tech Group -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="top-bar-contacts">
                    <a href="tel:+390687502002" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                        +39 06 87502002
                    </a>
                    <a href="mailto:info@gtechgroup.it" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        info@gtechgroup.it
                    </a>
                    <a href="https://wa.me/393921361200" target="_blank" rel="noopener" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        WhatsApp
                    </a>
                </div>
                <a href="https://gtechgroup.it" target="_blank" rel="noopener" class="top-bar-cta">
                    <strong>Scopri G Tech Group</strong>
                    <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
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
    <div class="breadcrumb-wrapper" style="margin-top: 40px;">
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
    <main id="main-content" role="main" <?php echo ($current_page !== 'index' || isset($hide_hero)) ? 'style="padding-top: 100px;"' : ''; ?>>
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
