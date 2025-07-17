<?php
/**
 * Altri Strumenti - Raccolta completa di utility per domini
 * 
 * @author G Tech Group
 * @version 4.0
 */

require_once 'config/config.php';

// Meta tags specifici per questa pagina
$page_title = "Strumenti per Domini - Utility Complete | " . APP_NAME;
$page_description = "Raccolta completa di strumenti gratuiti per l'analisi e la gestione dei domini: DNS, WHOIS, blacklist, SSL, performance e molto altro.";
$canonical_url = APP_URL . "/tools";

// Includi header
include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-secondary">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-aos="fade-up">
                Strumenti per Domini
                <span class="hero-gradient">Utility Complete</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Tutti gli strumenti di cui hai bisogno per analizzare e gestire i tuoi domini
            </p>
        </div>
    </div>
</section>

<!-- Tools Section -->
<section class="tools-section">
    <div class="container">
        <!-- Strumenti Principali -->
        <div class="tools-category" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                Strumenti Principali
            </h2>
            <div class="tools-grid">
                <!-- DNS Check -->
                <a href="/dns-check" class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.95 4 4 12.95 4 24s8.95 20 20 20 20-8.95 20-20S35.05 4 24 4zm-2 29.5v-3h4v3h-4zm4.13-6.74l-.9.92C23.5 29.45 22 30.5 22 33h4c0-1.5.75-2.25 1.97-3.52l1.24-1.26c.73-.77 1.29-1.78 1.29-2.72 0-2.48-2.02-4.5-4.5-4.5s-4.5 2.02-4.5 4.5h3c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5c0 .41-.17.8-.45 1.06z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Controllo DNS</h3>
                    <p class="tool-description">Analizza tutti i record DNS di un dominio: A, AAAA, MX, TXT, CNAME, NS e altro.</p>
                    <span class="tool-cta">Analizza DNS →</span>
                </a>

                <!-- WHOIS Lookup -->
                <a href="/whois-lookup" class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.96 4 4 12.96 4 24s8.96 20 20 20 20-8.96 20-20S35.04 4 24 4zm0 6c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm4 21h-8v-1c0-2.66 5.34-4 8-4v5z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Verifica WHOIS</h3>
                    <p class="tool-description">Scopri proprietario, data di registrazione, scadenza e registrar di qualsiasi dominio.</p>
                    <span class="tool-cta">Verifica WHOIS →</span>
                </a>

                <!-- Blacklist Check -->
                <a href="/blacklist-check" class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M4 34V8c0-2.21 1.79-4 4-4h32c2.21 0 4 1.79 4 4v26c0 2.21-1.79 4-4 4H8c-2.21 0-4-1.79-4-4zm4-26v26h32V8H8zm16 22l10-10-2.83-2.83L24 24.34l-5.17-5.17L16 22l8 8z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Controllo Blacklist</h3>
                    <p class="tool-description">Verifica se il tuo dominio o IP è presente in blacklist di spam.</p>
                    <span class="tool-cta">Controlla Blacklist →</span>
                </a>

                <!-- Cloud Detection -->
                <a href="/cloud-detection" class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M38.71 20.07C37.35 13.19 31.28 8 24 8c-5.78 0-10.79 3.28-13.3 8.07C4.69 16.72 0 21.81 0 28c0 6.63 5.37 12 12 12h26c5.52 0 10-4.48 10-10 0-5.28-4.11-9.56-9.29-9.93z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Rilevamento Cloud</h3>
                    <p class="tool-description">Identifica servizi cloud come Microsoft 365, Google Workspace, CDN e altro.</p>
                    <span class="tool-cta">Rileva Cloud →</span>
                </a>
            </div>
        </div>

        <!-- Strumenti di Sicurezza -->
        <div class="tools-category" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                Strumenti di Sicurezza
            </h2>
            <div class="tools-grid">
                <!-- SSL Checker -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M36 16h-2v-4c0-5.52-4.48-10-10-10S14 6.48 14 12v4h-2c-2.21 0-4 1.79-4 4v20c0 2.21 1.79 4 4 4h24c2.21 0 4-1.79 4-4V20c0-2.21-1.79-4-4-4zM24 34c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.2-18H17.8v-4c0-3.42 2.78-6.2 6.2-6.2 3.42 0 6.2 2.78 6.2 6.2v4z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Verifica SSL/TLS</h3>
                    <p class="tool-description">Controlla validità, catena di certificazione e configurazione SSL/TLS.</p>
                    <span class="tool-cta">Controlla SSL →</span>
                </div>

                <!-- Security Headers -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 2L4 12v14c0 11.11 7.67 21.47 18 24 10.33-2.53 18-12.89 18-24V12L24 2zm0 22h14c-1.11 8.83-7.06 16.66-14 18.87V24zm0-18.6v16.6H10V13.9l14-6.5z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Security Headers</h3>
                    <p class="tool-description">Analizza headers di sicurezza HTTP come CSP, HSTS, X-Frame-Options.</p>
                    <span class="tool-cta">Analizza Headers →</span>
                </div>

                <!-- Port Scanner -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M20 16V8c0-2.21 1.79-4 4-4s4 1.79 4 4v8c2.21 0 4 1.79 4 4v20c0 2.21-1.79 4-4 4H20c-2.21 0-4-1.79-4-4V20c0-2.21 1.79-4 4-4zm4 0c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2s2 .9 2 2v6c0 1.1-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Port Scanner</h3>
                    <p class="tool-description">Scansiona porte aperte e servizi esposti su un server.</p>
                    <span class="tool-cta">Scansiona Porte →</span>
                </div>

                <!-- DNSSEC Validator -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.96 4 4 12.96 4 24s8.96 20 20 20 20-8.96 20-20S35.04 4 24 4zm-4 30l-8-8 2.83-2.83L20 28.34l13.17-13.17L36 18 20 34z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Validatore DNSSEC</h3>
                    <p class="tool-description">Verifica la corretta implementazione e validità di DNSSEC.</p>
                    <span class="tool-cta">Valida DNSSEC →</span>
                </div>
            </div>
        </div>

        <!-- Strumenti Email -->
        <div class="tools-category" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                Strumenti Email
            </h2>
            <div class="tools-grid">
                <!-- SPF Record Generator -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M40 8H8c-2.21 0-3.98 1.79-3.98 4L4 36c0 2.21 1.79 4 4 4h32c2.21 0 4-1.79 4-4V12c0-2.21-1.79-4-4-4zm0 8L24 26 8 16v-4l16 10 16-10v4z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Generatore SPF</h3>
                    <p class="tool-description">Crea record SPF corretti per proteggere il tuo dominio dallo spoofing.</p>
                    <span class="tool-cta">Genera SPF →</span>
                </div>

                <!-- DKIM Validator -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M36 8H12c-2.21 0-4 1.79-4 4v24c0 2.21 1.79 4 4 4h24c2.21 0 4-1.79 4-4V12c0-2.21-1.79-4-4-4zM21 34l-7-7 2.83-2.83L21 28.34l11.17-11.17L35 20 21 34z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Validatore DKIM</h3>
                    <p class="tool-description">Verifica la corretta configurazione delle firme DKIM.</p>
                    <span class="tool-cta">Valida DKIM →</span>
                </div>

                <!-- DMARC Analyzer -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.95 4 4 12.95 4 24s8.95 20 20 20 20-8.95 20-20S35.05 4 24 4zm10 16H14v-4h20v4z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Analizzatore DMARC</h3>
                    <p class="tool-description">Analizza e valida policy DMARC per la protezione email.</p>
                    <span class="tool-cta">Analizza DMARC →</span>
                </div>

                <!-- Email Deliverability -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M40 8H8c-2.21 0-3.98 1.79-3.98 4L4 36c0 2.21 1.79 4 4 4h32c2.21 0 4-1.79 4-4V12c0-2.21-1.79-4-4-4zm-16 4L8 22v-6l16 10 16-10v6L24 12z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Test Deliverability</h3>
                    <p class="tool-description">Testa la deliverability delle tue email e identifica problemi.</p>
                    <span class="tool-cta">Testa Email →</span>
                </div>
            </div>
        </div>

        <!-- Strumenti Performance -->
        <div class="tools-category" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>
                </svg>
                Strumenti Performance
            </h2>
            <div class="tools-grid">
                <!-- DNS Performance -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M6 18v12h8V18H6zm0-8v6h8v-6H6zm10 20h24v-2H16v2zm0-12h24v-2H16v2zm0-10v2h24v-2H16z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">DNS Performance</h3>
                    <p class="tool-description">Misura tempi di risposta DNS e confronta nameserver.</p>
                    <span class="tool-cta">Testa Performance →</span>
                </div>

                <!-- TTL Analyzer -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M31.5 28c1.93 0 3.5-1.57 3.5-3.5S33.43 21 31.5 21 28 22.57 28 24.5s1.57 3.5 3.5 3.5zM24 4C12.96 4 4 12.96 4 24s8.96 20 20 20 20-8.96 20-20S35.04 4 24 4zm0 36c-8.82 0-16-7.18-16-16S15.18 8 24 8s16 7.18 16 16-7.18 16-16 16z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Analizzatore TTL</h3>
                    <p class="tool-description">Analizza e ottimizza i valori TTL dei record DNS.</p>
                    <span class="tool-cta">Analizza TTL →</span>
                </div>

                <!-- Response Time -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.97 4 4 12.97 4 24s8.97 20 20 20 20-8.97 20-20S35.03 4 24 4zm10 22H22V14h4v8h8v4z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Response Time</h3>
                    <p class="tool-description">Misura tempi di risposta del sito da diverse località.</p>
                    <span class="tool-cta">Misura Tempi →</span>
                </div>

                <!-- CDN Detector -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 4C12.95 4 4 12.95 4 24s8.95 20 20 20 20-8.95 20-20S35.05 4 24 4zm-4 29.5v-15l12 7.5-12 7.5z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">CDN Detector</h3>
                    <p class="tool-description">Identifica e analizza l'utilizzo di CDN e edge server.</p>
                    <span class="tool-cta">Rileva CDN →</span>
                </div>
            </div>
        </div>

        <!-- Utility Aggiuntive -->
        <div class="tools-category" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                </svg>
                Utility Aggiuntive
            </h2>
            <div class="tools-grid">
                <!-- Subdomain Finder -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M12 40q-3.3 0-5.65-2.35Q4 35.3 4 32V16q0-3.3 2.35-5.65Q8.7 8 12 8h24q3.3 0 5.65 2.35Q44 12.7 44 16v16q0 3.3-2.35 5.65Q39.3 40 36 40Zm0-4h24q1.65 0 2.825-1.175Q40 33.65 40 32V16q0-1.65-1.175-2.825Q37.65 12 36 12H12q-1.65 0-2.825 1.175Q8 14.35 8 16v16q0 1.65 1.175 2.825Q10.35 36 12 36Zm2-4h20v-2H14Zm0-6h20v-2H14Zm0-6h20v-2H14Z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Subdomain Finder</h3>
                    <p class="tool-description">Trova sottodomini attivi e nascosti di un dominio.</p>
                    <span class="tool-cta">Trova Sottodomini →</span>
                </div>

                <!-- Domain Age -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M18 4v2h6V4h-6zm4 38q-3.3 0-6.2-1.25t-5.05-3.4q-2.15-2.15-3.4-5.05Q6.1 29.4 6.1 26.1t1.25-6.2q1.25-2.9 3.4-5.05 2.15-2.15 5.05-3.4Q18.7 10.2 22 10.2q3.3 0 6.2 1.25t5.05 3.4q2.15 2.15 3.4 5.05 1.25 2.9 1.25 6.2t-1.25 6.2q-1.25 2.9-3.4 5.05-2.15 2.15-5.05 3.4Q25.3 42 22 42Zm0-4q5.5 0 9.35-3.85T35.2 24.8q0-5.5-3.85-9.35T22 11.6q-5.5 0-9.35 3.85T8.8 24.8q0 5.5 3.85 9.35T22 38Zm2-6V16h-4v20h4Z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Domain Age</h3>
                    <p class="tool-description">Calcola l'età esatta di un dominio dalla prima registrazione.</p>
                    <span class="tool-cta">Calcola Età →</span>
                </div>

                <!-- Reverse IP -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M14 26h20v-4H14v4zm0 8h20v-4H14v4zm0-16h20v-4H14v4zM6 42V6h36v36H6zm4-4h28V10H10v28z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Reverse IP Lookup</h3>
                    <p class="tool-description">Trova tutti i domini ospitati sullo stesso indirizzo IP.</p>
                    <span class="tool-cta">Reverse IP →</span>
                </div>

                <!-- Domain Availability -->
                <div class="tool-card">
                    <div class="tool-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M24 44q-4.1 0-7.75-1.575-3.65-1.575-6.375-4.3-2.725-2.725-4.3-6.375Q4 28.1 4 24q0-4.15 1.575-7.8 1.575-3.65 4.3-6.35 2.725-2.7 6.375-4.275Q19.9 4 24 4q4.15 0 7.8 1.575 3.65 1.575 6.35 4.275 2.7 2.7 4.275 6.35Q44 19.85 44 24q0 4.1-1.575 7.75-1.575 3.65-4.275 6.375t-6.35 4.3Q28.15 44 24 44Zm0-4q6.75 0 11.375-4.625T40 24q0-6.75-4.625-11.375T24 8q-6.75 0-11.375 4.625T8 24q0 6.75 4.625 11.375T24 40Zm-2.15-6.5 10.6-10.6-2.7-2.7-7.9 7.9-3.9-3.9-2.7 2.7Z"/>
                        </svg>
                    </div>
                    <h3 class="tool-title">Domain Availability</h3>
                    <p class="tool-description">Verifica la disponibilità di domini con diverse estensioni.</p>
                    <span class="tool-cta">Verifica Disponibilità →</span>
                </div>
            </div>
        </div>

        <!-- Coming Soon -->
        <div class="tools-category coming-soon-section" data-aos="fade-up">
            <h2 class="category-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                Prossimamente
            </h2>
            <div class="coming-soon-grid">
                <div class="coming-soon-item">
                    <h4>Monitoring 24/7</h4>
                    <p>Sistema di monitoraggio continuo per domini e servizi</p>
                    <span class="coming-tag">In sviluppo</span>
                </div>
                <div class="coming-soon-item">
                    <h4>API Webhooks</h4>
                    <p>Notifiche real-time per cambiamenti DNS e scadenze</p>
                    <span class="coming-tag">Q2 2024</span>
                </div>
                <div class="coming-soon-item">
                    <h4>Bulk Operations</h4>
                    <p>Analisi e gestione massiva di multipli domini</p>
                    <span class="coming-tag">Q3 2024</span>
                </div>
                <div class="coming-soon-item">
                    <h4>Mobile App</h4>
                    <p>App iOS e Android per gestione domini in mobilità</p>
                    <span class="coming-tag">Q4 2024</span>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="tools-cta-section" data-aos="fade-up">
            <div class="cta-content">
                <h2>Non trovi lo strumento che cerchi?</h2>
                <p>Siamo sempre aperti a suggerimenti per nuovi strumenti. Facci sapere cosa ti serve!</p>
                <div class="cta-buttons">
                    <a href="/contatti" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Suggerisci uno strumento
                    </a>
                    <a href="/api-docs" class="btn btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Usa le nostre API
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Stili specifici per la pagina tools */
.tools-section {
    padding: 80px 0;
    background: var(--bg-primary);
}

.tools-category {
    margin-bottom: 80px;
}

.category-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 2rem;
    margin-bottom: 40px;
    color: var(--text-primary);
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.tool-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 32px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
}

.tool-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-gradient-start), var(--primary-gradient-end));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tool-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.tool-card:hover::before {
    opacity: 1;
}

.tool-icon {
    width: 80px;
    height: 80px;
    background: var(--bg-secondary);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: var(--primary);
}

.tool-title {
    font-size: 1.25rem;
    margin-bottom: 12px;
    color: var(--text-primary);
}

.tool-description {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 16px;
}

.tool-cta {
    color: var(--primary);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Coming Soon Section */
.coming-soon-section {
    background: var(--bg-secondary);
    padding: 60px;
    border-radius: 24px;
    margin-top: 80px;
}

.coming-soon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.coming-soon-item {
    background: var(--surface);
    padding: 24px;
    border-radius: 12px;
}

.coming-soon-item h4 {
    margin-bottom: 8px;
}

.coming-tag {
    display: inline-block;
    background: var(--primary);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    margin-top: 12px;
}

/* CTA Section */
.tools-cta-section {
    text-align: center;
    margin-top: 80px;
    padding: 60px;
    background: linear-gradient(135deg, var(--primary-gradient-start), var(--primary-gradient-end));
    border-radius: 24px;
    color: white;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 16px;
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 32px;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-buttons .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 768px) {
    .tools-grid {
        grid-template-columns: 1fr;
    }
    
    .coming-soon-section {
        padding: 40px 20px;
    }
    
    .tools-cta-section {
        padding: 40px 20px;
    }
    
    .cta-content h2 {
        font-size: 1.875rem;
    }
}
</style>

<?php include 'templates/footer.php'; ?>
