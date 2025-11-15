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
    <meta name="theme-color" content="#5d8ecf" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Controllo Domini">
    <meta name="application-name" content="Controllo Domini">
    <meta name="msapplication-TileColor" content="#5d8ecf">
    <meta name="msapplication-config" content="/browserconfig.xml">

    <!-- Enhanced SEO Meta Tags -->
    <meta name="rating" content="General">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    <meta name="language" content="Italian">
    <meta name="geo.region" content="IT">
    <meta name="geo.placename" content="Italy">
    <meta name="format-detection" content="telephone=no">

    <!-- Accessibility -->
    <meta name="color-scheme" content="light dark">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#1a1a1a">
    <meta name="mobile-web-app-title" content="ControlDomini">
    <meta name="apple-mobile-web-app-title" content="ControlDomini">
    
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

    $modern_css_file = '/assets/css/modern-ui.css';
    $modern_css_path = ABSPATH . 'assets/css/modern-ui.css';
    $modern_css_version = file_exists($modern_css_path) ? filemtime($modern_css_path) : time();

    $minimal_css_file = '/assets/css/minimal-professional.css';
    $minimal_css_path = ABSPATH . 'assets/css/minimal-professional.css';
    $minimal_css_version = file_exists($minimal_css_path) ? filemtime($minimal_css_path) : time();
    ?>
    <!-- New Minimal Professional Design -->
    <link href="<?php echo $minimal_css_file; ?>?v=<?php echo $minimal_css_version; ?>" rel="stylesheet" type="text/css">
    <!-- Legacy CSS (for compatibility) -->
    <link href="<?php echo $css_file; ?>?v=<?php echo $css_version; ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo $modern_css_file; ?>?v=<?php echo $modern_css_version; ?>" rel="stylesheet" type="text/css">
    <link href="/assets/css/enhancements.css?v=<?php echo $css_version; ?>" rel="stylesheet" type="text/css">

    <!-- CSS Libraries -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- JSON-LD Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Controllo Domini",
        "url": "<?php echo APP_URL; ?>",
        "logo": "<?php echo OG_IMAGE; ?>",
        "description": "<?php echo htmlspecialchars($page_description); ?>",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "1247",
            "bestRating": "5",
            "worstRating": "1"
        },
        "provider": {
            "@type": "Organization",
            "name": "G Tech Group",
            "url": "<?php echo APP_URL; ?>",
            "logo": "<?php echo OG_IMAGE; ?>",
            "sameAs": [
                "https://www.facebook.com/controllodomini",
                "https://twitter.com/controllodomini",
                "https://www.linkedin.com/company/controllodomini"
            ]
        },
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?php echo APP_URL; ?>/?domain={search_term_string}&analyze=1"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <?php if (isset($domain) && $domain): ?>
    <!-- Breadcrumb Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [{
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "<?php echo APP_URL; ?>"
        },{
            "@type": "ListItem",
            "position": 2,
            "name": "Analisi Dominio",
            "item": "<?php echo APP_URL . '/?domain=' . urlencode($domain); ?>"
        }]
    }
    </script>

    <!-- Website Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "url": "<?php echo APP_URL; ?>",
        "name": "Controllo Domini",
        "description": "Strumento professionale per analisi DNS, WHOIS, SSL e sicurezza domini",
        "publisher": {
            "@type": "Organization",
            "name": "G Tech Group",
            "logo": {
                "@type": "ImageObject",
                "url": "<?php echo OG_IMAGE; ?>",
                "width": 1200,
                "height": 630
            }
        },
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?php echo APP_URL; ?>/?domain={search_term_string}&analyze=1"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <?php endif; ?>

    <!-- Critical CSS inline per performance -->
    <style>
        /* Critical CSS per above-the-fold content */
        :root{
            --primary:#5d8ecf;
            --primary-dark:#4a7ab8;
            --primary-light:#7aa6dd;
            --secondary:#264573;
            --secondary-dark:#1a2f4f;
            --secondary-light:#3a5987;
            --accent:#ffd700;
            --success:#26de81;
            --warning:#ffa502;
            --error:#ee5a6f;
            --text-dark:#000222;
            --text-light:#64748b;
            --bg-light:#f8fafc;
            --bg-white:#ffffff;
            --border-color:#e5e7eb;
            --shadow-sm:0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md:0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);
            --transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Lato',-apple-system,BlinkMacSystemFont,sans-serif;background:#f0f4f8;color:var(--text-dark)}
        .container{max-width:1400px;margin:0 auto;padding:0 20px}
        nav{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);position:fixed;top:50px;left:0;right:0;z-index:1000;box-shadow:var(--shadow-md);transition:var(--transition)}
        .hero{padding:200px 20px 80px;text-align:center}
        .loading{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        
        /* Improved Top bar styles */
        .top-bar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #1e3c72 100%);
            background-size: 200% 100%;
            animation: gradientShift 10s ease infinite;
            color: white;
            padding: 10px 0;
            font-size: 14px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .top-bar-contacts {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .top-bar-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 0;
            font-weight: 500;
        }
        
        .top-bar-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }
        
        .top-bar-item:hover {
            color: white;
            transform: translateY(-1px);
        }
        
        .top-bar-item:hover::after {
            width: 100%;
        }
        
        .top-bar-item svg {
            width: 18px;
            height: 18px;
            opacity: 0.9;
        }
        
        /* New animated CTA button */
        .top-bar-cta {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
            color: #1e3c72;
            padding: 8px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            border: 2px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
            transform: translateY(0);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        .top-bar-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, #ffed4e 100%);
            transition: left 0.4s cubic-bezier(0.4,0,0.2,1);
            z-index: -1;
        }
        
        .top-bar-cta:hover {
            color: #1e3c72;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(0,0,0,0.35);
            border-color: var(--accent);
        }
        
        .top-bar-cta:hover::before {
            left: 0;
        }
        
        .top-bar-cta svg {
            width: 18px;
            height: 18px;
            transition: transform 0.3s ease;
        }
        
        .top-bar-cta:hover svg {
            transform: translate(3px, -3px) rotate(45deg);
        }
        
        /* Improved navigation styles */
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--secondary);
            font-weight: 700;
            font-size: 22px;
            transition: var(--transition);
        }
        
        .logo img {
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            color: var(--primary);
        }
        
        .logo:hover img {
            transform: rotate(-10deg) scale(1.1);
        }
        
        .nav-links {
            display: flex;
            gap: 35px;
            align-items: center;
        }
        
        .nav-links a {
            position: relative;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 16px;
            transition: color 0.3s ease;
            padding: 8px 0;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--primary-gradient);
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .nav-links a:hover::after,
        .nav-links a[aria-current="page"]::after {
            width: 100%;
        }
        
        .nav-links a[aria-current="page"] {
            color: var(--primary);
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 10px;
            position: relative;
            width: 40px;
            height: 40px;
            transition: var(--transition);
        }
        
        .mobile-menu-btn span {
            display: block;
            width: 25px;
            height: 3px;
            background: var(--secondary);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            transition: var(--transition);
        }
        
        .mobile-menu-btn span:first-child {
            top: 10px;
        }
        
        .mobile-menu-btn span:nth-child(2) {
            top: 50%;
            transform: translate(-50%, -50%);
        }
        
        .mobile-menu-btn span:last-child {
            bottom: 10px;
        }
        
        .mobile-menu-btn.active span:first-child {
            transform: translateX(-50%) translateY(8px) rotate(45deg);
            top: 50%;
        }
        
        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-btn.active span:last-child {
            transform: translateX(-50%) translateY(-8px) rotate(-45deg);
            bottom: 50%;
        }
        
        /* Hero improvements */
        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--primary) 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 5s ease infinite;
        }
        
        @keyframes gradientText {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            color: var(--text-light);
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Hero stats */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 50px;
            flex-wrap: wrap;
        }
        
        .hero-stat {
            text-align: center;
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }
        
        .hero-stat:nth-child(1) { animation-delay: 0.2s; }
        .hero-stat:nth-child(2) { animation-delay: 0.4s; }
        .hero-stat:nth-child(3) { animation-delay: 0.6s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-stat-value {
            display: block;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .hero-stat-label {
            display: block;
            font-size: 0.95rem;
            color: var(--text-light);
            font-weight: 600;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .top-bar {
                font-size: 13px;
                padding: 12px 0;
            }
            
            .top-bar-content {
                justify-content: center;
                text-align: center;
            }
            
            .top-bar-contacts {
                gap: 15px;
                justify-content: center;
                width: 100%;
            }
            
            .top-bar-item span {
                display: none;
            }
            
            .top-bar-cta {
                margin-top: 10px;
                padding: 6px 20px;
                font-size: 12px;
            }
            
            nav {
                top: 60px;
            }
            
            .hero {
                padding-top: 180px;
                padding-bottom: 60px;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-links {
                position: fixed;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 30px 20px;
                box-shadow: var(--shadow-xl);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                border-top: 1px solid var(--border-color);
            }
            
            .nav-links.active {
                opacity: 1;
                visibility: visible;
                top: 100%;
            }
            
            .nav-links a {
                width: 100%;
                padding: 15px 0;
                text-align: center;
                border-bottom: 1px solid var(--border-color);
            }
            
            .nav-links a:last-child {
                border-bottom: none;
            }
            
            .hero-stats {
                gap: 30px;
            }
            
            .hero-stat-value {
                font-size: 2rem;
            }
        }
        
        /* Skip link for accessibility */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 0 0 8px 0;
            transition: top 0.3s ease;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Smooth scroll fix for sticky header */
        html {
            scroll-padding-top: 120px;
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
                    <a href="tel:+390465846245" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                        <span>+39 0465 846245</span>
                    </a>
                    <a href="mailto:info@gtechgroup.it" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span>info@gtechgroup.it</span>
                    </a>
                    <a href="https://wa.me/393921361200" target="_blank" rel="noopener" class="top-bar-item">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <span>WhatsApp</span>
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
                    <?php else: ?>
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="20" r="20" fill="url(#logo-gradient)"/>
                        <path d="M20 10C14.477 10 10 14.477 10 20s4.477 10 10 10 10-4.477 10-10-4.477-10-10-10zm0 18c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z" fill="white"/>
                        <circle cx="20" cy="20" r="4" fill="white"/>
                        <defs>
                            <linearGradient id="logo-gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#5d8ecf"/>
                                <stop offset="1" stop-color="#264573"/>
                            </linearGradient>
                        </defs>
                    </svg>
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
                    <span></span>
                    <span></span>
                    <span></span>
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
    <div class="breadcrumb-wrapper" style="margin-top: 50px; padding-top: 80px;">
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
    <main id="main-content" role="main" <?php echo ($current_page !== 'index' || isset($hide_hero)) ? 'style="padding-top: 130px;"' : ''; ?>>
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
        
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const navLinks = document.getElementById('navLinks');
            
            if (mobileMenuBtn && navLinks) {
                mobileMenuBtn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    navLinks.classList.toggle('active');
                    
                    const isOpen = navLinks.classList.contains('active');
                    this.setAttribute('aria-expanded', isOpen);
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.nav-container')) {
                        mobileMenuBtn.classList.remove('active');
                        navLinks.classList.remove('active');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
