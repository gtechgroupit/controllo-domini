<?php
/**
 * Template Footer - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage Templates
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}
?>

        </div><!-- .container -->
    </main><!-- #main-content -->
    
    <!-- Call to Action Section -->
    <?php if (!isset($hide_cta) && $current_page !== 'contatti'): ?>
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Pronto per analizzare il tuo dominio?</h2>
                <p>Scopri tutto sul tuo dominio in pochi secondi. Gratuito e senza registrazione.</p>
                <a href="/#domain-check" class="btn btn-primary btn-lg">
                    <span>Inizia Analisi</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer id="footer" class="site-footer" role="contentinfo">
        <div class="footer-main">
            <div class="container">
                <div class="footer-content">
                    <!-- Company Info -->
                    <div class="footer-section footer-about">
                        <h3 class="footer-title">Controllo Domini</h3>
                        <p class="footer-description">
                            Strumento professionale gratuito per l'analisi completa di domini. 
                            Verifica DNS, WHOIS, blacklist e servizi cloud in tempo reale.
                        </p>
                        <div class="footer-social">
                            <a href="https://github.com/gtechgroupit" target="_blank" rel="noopener" aria-label="GitHub">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                            </a>
                            <a href="https://twitter.com/gtechgroup" target="_blank" rel="noopener" aria-label="Twitter">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a href="https://www.linkedin.com/company/gtechgroup" target="_blank" rel="noopener" aria-label="LinkedIn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="footer-section">
                        <h4 class="footer-subtitle">Strumenti</h4>
                        <ul class="footer-links">
                            <li><a href="/">Controllo DNS</a></li>
                            <li><a href="/#whois">Verifica WHOIS</a></li>
                            <li><a href="/#blacklist">Controllo Blacklist</a></li>
                            <li><a href="/#cloud">Rilevamento Cloud</a></li>
                            <li><a href="/api-docs">Documentazione API</a></li>
                            <li><a href="/tools">Altri Strumenti</a></li>
                        </ul>
                    </div>
                    
                    <!-- Resources -->
                    <div class="footer-section">
                        <h4 class="footer-subtitle">Risorse</h4>
                        <ul class="footer-links">
                            <li><a href="/guide/dns">Guida DNS</a></li>
                            <li><a href="/guide/spf-dkim-dmarc">SPF, DKIM e DMARC</a></li>
                            <li><a href="/guide/microsoft-365">Setup Microsoft 365</a></li>
                            <li><a href="/guide/google-workspace">Setup Google Workspace</a></li>
                            <li><a href="/blog">Blog</a></li>
                            <li><a href="/changelog">Changelog</a></li>
                        </ul>
                    </div>
                    
                    <!-- Company -->
                    <div class="footer-section">
                        <h4 class="footer-subtitle">G Tech Group</h4>
                        <ul class="footer-links">
                            <li><a href="https://gtechgroup.it" target="_blank" rel="noopener">Chi Siamo</a></li>
                            <li><a href="/contatti">Contatti</a></li>
                            <li><a href="/privacy">Privacy Policy</a></li>
                            <li><a href="/termini">Termini di Servizio</a></li>
                            <li><a href="/cookie-policy">Cookie Policy</a></li>
                            <li><a href="/sitemap.xml">Sitemap</a></li>
                        </ul>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="footer-section footer-newsletter">
                        <h4 class="footer-subtitle">Newsletter</h4>
                        <p>Ricevi aggiornamenti su nuove funzionalità e guide.</p>
                        <form id="newsletterForm" class="newsletter-form" action="/newsletter/subscribe" method="POST">
                            <div class="newsletter-input-group">
                                <input type="email" 
                                       name="email" 
                                       class="newsletter-input" 
                                       placeholder="La tua email" 
                                       required
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                       aria-label="Email per newsletter">
                                <button type="submit" class="newsletter-btn" aria-label="Iscriviti alla newsletter">
                                    <span class="btn-text">Iscriviti</span>
                                    <span class="btn-icon">→</span>
                                </button>
                            </div>
                            <p class="newsletter-disclaimer">
                                <small>Nessuno spam. Puoi cancellarti in qualsiasi momento.</small>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <div class="footer-copyright">
                        <p>&copy; <?php echo date('Y'); ?> G Tech Group. Tutti i diritti riservati.</p>
                        <p class="footer-version">
                            <small>
                                Controllo Domini v<?php echo APP_VERSION; ?> | 
                                <a href="/status" rel="nofollow">Status</a> | 
                                <a href="/changelog">Changelog</a>
                            </small>
                        </p>
                    </div>
                    
                    <div class="footer-badges">
                        <!-- SSL Badge -->
                        <div class="footer-badge" title="Connessione sicura SSL">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 1l3 1v5c0 5.55-3.84 10.74-9 12-5.16-1.26-9-6.45-9-12V2l5-1 5 1zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l5-1.03v7.72z"/>
                            </svg>
                            <span>SSL</span>
                        </div>
                        
                        <!-- GDPR Badge -->
                        <div class="footer-badge" title="GDPR Compliant">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z"/>
                            </svg>
                            <span>GDPR</span>
                        </div>
                        
                        <!-- Made in Italy -->
                        <div class="footer-badge" title="Made in Italy">
                            <span class="flag-it">🇮🇹</span>
                            <span>Made in Italy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="scrollTopBtn" class="scroll-top-btn" aria-label="Torna su" title="Torna su">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" aria-hidden="true">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Analisi in corso...</p>
        </div>
    </div>
    
    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    
    <!-- Modal Container -->
    <div id="modalContainer"></div>
    
    <!-- Scripts -->
    <script src="/assets/js/main.js?v=<?php echo filemtime(ABSPATH . 'assets/js/main.js'); ?>"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Chart.js (solo se necessario) -->
    <?php if (isset($load_charts) && $load_charts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <?php endif; ?>
    
    <!-- Clipboard.js (solo se necessario) -->
    <?php if (isset($load_clipboard) && $load_clipboard): ?>
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
    <script>
        if (typeof ClipboardJS !== 'undefined') {
            const clipboard = new ClipboardJS('.copy-btn');
            clipboard.on('success', function(e) {
                showNotification('Copiato negli appunti!', 'success');
                e.clearSelection();
            });
        }
    </script>
    <?php endif; ?>
    
    <!-- Newsletter Form Handler -->
    <script>
        document.getElementById('newsletterForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const email = form.email.value;
            const btn = form.querySelector('.newsletter-btn');
            
            // Disabilita form
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span>';
            
            try {
                const response = await fetch('/api/newsletter/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    form.innerHTML = '<div class="alert alert-success">✅ Iscrizione completata! Controlla la tua email.</div>';
                } else {
                    throw new Error(data.message || 'Errore durante l\'iscrizione');
                }
            } catch (error) {
                showNotification(error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<span class="btn-text">Iscriviti</span><span class="btn-icon">→</span>';
            }
        });
    </script>
    
    <!-- Structured Data for WebSite -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Controllo Domini",
        "alternateName": "DNS Check Italia",
        "url": "https://controllodomini.it",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://controllodomini.it/?domain={search_term_string}&analyze=true"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <!-- Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "G Tech Group",
        "url": "https://gtechgroup.it",
        "logo": "https://gtechgroup.it/logo.png",
        "sameAs": [
            "https://github.com/gtechgroupit",
            "https://twitter.com/gtechgroup",
            "https://www.linkedin.com/company/gtechgroup"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer support",
            "email": "support@gtechgroup.it",
            "url": "https://controllodomini.it/contatti"
        }
    }
    </script>
    
    <!-- Service Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "serviceType": "Domain Analysis Tool",
        "provider": {
            "@type": "Organization",
            "name": "G Tech Group"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Italy"
        },
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Domain Analysis Services",
            "itemListElement": [
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "DNS Analysis",
                        "description": "Complete DNS record analysis"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "WHOIS Lookup",
                        "description": "Domain ownership information"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Blacklist Check",
                        "description": "Reputation monitoring"
                    }
                }
            ]
        }
    }
    </script>
    
    <!-- Performance Monitoring -->
    <script>
        // Web Vitals reporting
        if ('PerformanceObserver' in window) {
            try {
                // LCP
                new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
                    // Send to analytics
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'web_vitals', {
                            event_category: 'Web Vitals',
                            event_label: 'LCP',
                            value: Math.round(lastEntry.renderTime || lastEntry.loadTime)
                        });
                    }
                }).observe({entryTypes: ['largest-contentful-paint']});
                
                // FID
                new PerformanceObserver((entryList) => {
                    const firstInput = entryList.getEntries()[0];
                    const fid = firstInput.processingStart - firstInput.startTime;
                    console.log('FID:', fid);
                    // Send to analytics
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'web_vitals', {
                            event_category: 'Web Vitals',
                            event_label: 'FID',
                            value: Math.round(fid)
                        });
                    }
                }).observe({entryTypes: ['first-input']});
                
                // CLS
                let cls = 0;
                new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        if (!entry.hadRecentInput) {
                            cls += entry.value;
                        }
                    }
                    console.log('CLS:', cls);
                }).observe({entryTypes: ['layout-shift']});
                
            } catch (e) {
                console.error('Web Vitals error:', e);
            }
        }
    </script>
    
    <!-- Custom footer scripts hook -->
    <?php if (isset($footer_scripts)): ?>
    <?php echo $footer_scripts; ?>
    <?php endif; ?>
    
    <!-- Development/Debug Mode -->
    <?php if (DEBUG_MODE): ?>
    <script>
        console.log('🚀 Controllo Domini - Debug Mode Active');
        console.log('Version:', '<?php echo APP_VERSION; ?>');
        console.log('Page Load Time:', performance.now() + 'ms');
    </script>
    <?php endif; ?>
</body>
</html>
