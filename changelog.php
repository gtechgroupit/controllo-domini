<?php
/**
 * Changelog - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Definisce la costante ABSPATH se non definita
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Carica configurazione
require_once ABSPATH . 'config/config.php';

// Impostazioni per la pagina
$page_name = 'Changelog';
$page_title = 'Changelog - Controllo Domini | Storico Aggiornamenti';
$page_description = 'Scopri tutte le novità, miglioramenti e correzioni implementate in Controllo Domini. Changelog completo del tool di analisi DNS e WHOIS.';
$body_class = 'changelog-page';
$current_page = 'changelog';

// Include header
require_once ABSPATH . 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-small">
    <div class="container">
        <div class="hero-content">
            <h1 class="gradient-text">Changelog</h1>
            <p class="hero-subtitle">Tutte le novità e gli aggiornamenti di Controllo Domini</p>
        </div>
    </div>
</section>

<!-- Changelog Content -->
<section class="changelog-section">
    <div class="container">
        <div class="changelog-container">
            
            <!-- Version 4.0 - Latest -->
            <div class="changelog-item" data-aos="fade-up">
                <div class="changelog-date">
                    <span class="date-day">17</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 4.0</h2>
                        <span class="version-badge version-latest">Latest</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🚀 Nuove Funzionalità</h3>
                        <ul class="changes-list">
                            <li>Implementato sistema di rilevamento servizi cloud avanzato (Microsoft 365, Google Workspace, AWS, Azure)</li>
                            <li>Aggiunta visualizzazione unificata di tutti i record DNS senza tab</li>
                            <li>Nuovo design per le statistiche homepage con animazioni e contatori</li>
                            <li>Sistema di timeout intelligente per prevenire analisi bloccate</li>
                            <li>Aggiunto campo di ricerca esempi rapidi (google.com, microsoft.com, amazon.com)</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">💎 Miglioramenti</h3>
                        <ul class="changes-list">
                            <li>Ottimizzata la gestione degli errori con array_keys e validazione dati</li>
                            <li>Migliorata l'interfaccia di caricamento durante l'analisi</li>
                            <li>Aggiornato il sistema di badge colorati per i tipi di record DNS</li>
                            <li>Implementata animazione fluida per i contatori delle statistiche</li>
                            <li>Migliorata la responsività su dispositivi mobili</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🐛 Correzioni Bug</h3>
                        <ul class="changes-list">
                            <li>Risolto errore PHP "Undefined array key 'detected'" nella sezione cloud services</li>
                            <li>Corretto problema di parsing con endif non bilanciati</li>
                            <li>Sistemato timeout infinito durante l'analisi di alcuni domini</li>
                            <li>Risolto errore 500 su alcuni domini specifici</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Version 3.9 -->
            <div class="changelog-item" data-aos="fade-up" data-aos-delay="100">
                <div class="changelog-date">
                    <span class="date-day">16</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 3.9</h2>
                        <span class="version-badge">Stable</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🚀 Nuove Funzionalità</h3>
                        <ul class="changes-list">
                            <li>Aggiunto rilevamento automatico sottodomini comuni</li>
                            <li>Implementata analisi TTL e performance DNS</li>
                            <li>Nuovo sistema di health score per la salute del dominio</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">💎 Miglioramenti</h3>
                        <ul class="changes-list">
                            <li>Velocizzata l'analisi WHOIS con cache temporanea</li>
                            <li>Migliorata la visualizzazione dei record MX e priorità</li>
                            <li>Ottimizzato il controllo blacklist per maggiore velocità</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Version 3.8 -->
            <div class="changelog-item" data-aos="fade-up" data-aos-delay="200">
                <div class="changelog-date">
                    <span class="date-day">15</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 3.8</h2>
                        <span class="version-badge">Stable</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🚀 Nuove Funzionalità</h3>
                        <ul class="changes-list">
                            <li>Aggiunta analisi completa SPF, DKIM e DMARC</li>
                            <li>Implementato sistema di raccomandazioni per la sicurezza email</li>
                            <li>Nuovo indicatore visuale per servizi cloud rilevati</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🐛 Correzioni Bug</h3>
                        <ul class="changes-list">
                            <li>Risolto problema visualizzazione record TXT lunghi</li>
                            <li>Corretto errore timeout su domini con molti record</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Version 3.7 -->
            <div class="changelog-item" data-aos="fade-up" data-aos-delay="300">
                <div class="changelog-date">
                    <span class="date-day">14</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 3.7</h2>
                        <span class="version-badge">Stable</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">💎 Miglioramenti</h3>
                        <ul class="changes-list">
                            <li>Completo restyling dell'interfaccia utente</li>
                            <li>Aggiornate tutte le librerie JavaScript alle ultime versioni</li>
                            <li>Migliorata accessibilità con supporto screen reader</li>
                            <li>Ottimizzate le performance di caricamento pagina</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🛡️ Sicurezza</h3>
                        <ul class="changes-list">
                            <li>Implementati header di sicurezza avanzati</li>
                            <li>Aggiornate policy CSP per maggiore protezione</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Version 3.6 -->
            <div class="changelog-item" data-aos="fade-up" data-aos-delay="400">
                <div class="changelog-date">
                    <span class="date-day">13</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 3.6</h2>
                        <span class="version-badge">Stable</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🚀 Nuove Funzionalità</h3>
                        <ul class="changes-list">
                            <li>Aggiunto supporto per record CAA (Certificate Authority Authorization)</li>
                            <li>Implementata verifica DNSSEC con indicatore visuale</li>
                            <li>Nuovo sistema di export dati in formato JSON/CSV</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">💎 Miglioramenti</h3>
                        <ul class="changes-list">
                            <li>Migliorata gestione dei domini internazionali (IDN)</li>
                            <li>Ottimizzato algoritmo di rilevamento servizi email</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Version 3.5 - Initial -->
            <div class="changelog-item" data-aos="fade-up" data-aos-delay="500">
                <div class="changelog-date">
                    <span class="date-day">12</span>
                    <span class="date-month">LUG</span>
                    <span class="date-year">2025</span>
                </div>
                
                <div class="changelog-content">
                    <div class="version-header">
                        <h2 class="version-number">Versione 3.5</h2>
                        <span class="version-badge">Initial Release</span>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🎉 Prima Release Pubblica</h3>
                        <ul class="changes-list">
                            <li>Analisi completa record DNS (A, AAAA, MX, TXT, NS, CNAME, SOA)</li>
                            <li>Verifica WHOIS con informazioni proprietario e scadenza</li>
                            <li>Controllo presenza in oltre 30 blacklist principali</li>
                            <li>Rilevamento base servizi cloud (Microsoft 365, Google Workspace)</li>
                            <li>Interfaccia responsive per dispositivi mobili</li>
                            <li>Sistema di notifiche e alert in tempo reale</li>
                        </ul>
                    </div>
                    
                    <div class="changes-section">
                        <h3 class="changes-title">🔧 Caratteristiche Tecniche</h3>
                        <ul class="changes-list">
                            <li>Sviluppato con PHP 7.4+ per massime performance</li>
                            <li>Utilizzo di cache intelligente per velocizzare le analisi</li>
                            <li>API RESTful per integrazioni esterne</li>
                            <li>Supporto completo per IPv6</li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Subscribe to Updates -->
        <div class="changelog-subscribe" data-aos="fade-up">
            <div class="subscribe-card">
                <h3>🔔 Rimani Aggiornato</h3>
                <p>Iscriviti alla newsletter per ricevere notifiche sui nuovi aggiornamenti e funzionalità.</p>
                <form class="subscribe-form" action="/newsletter/subscribe" method="POST">
                    <input type="email" 
                           name="email" 
                           placeholder="La tua email" 
                           required 
                           class="subscribe-input">
                    <button type="submit" class="btn btn-primary">
                        Iscriviti agli Aggiornamenti
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Links -->
        <div class="changelog-links" data-aos="fade-up">
            <a href="https://github.com/gtechgroupit/controllo-domini/releases" 
               target="_blank" 
               rel="noopener" 
               class="changelog-link">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 0C4.477 0 0 4.477 0 10c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0110 4.844a9.59 9.59 0 012.504.337c1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C17.137 18.163 20 14.418 20 10c0-5.523-4.477-10-10-10z"/>
                </svg>
                Vedi su GitHub
            </a>
            <a href="/api-docs" class="changelog-link">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                </svg>
                Documentazione API
            </a>
            <a href="/status" class="changelog-link">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Stato del Servizio
            </a>
        </div>
    </div>
</section>

<!-- Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "@id": "https://controllodomini.it/changelog",
    "url": "https://controllodomini.it/changelog",
    "name": "Changelog - Controllo Domini",
    "description": "<?php echo $page_description; ?>",
    "breadcrumb": {
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://controllodomini.it"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Changelog",
                "item": "https://controllodomini.it/changelog"
            }
        ]
    }
}
</script>

<style>
/* Stili specifici per la pagina changelog */
.hero-small {
    padding: 120px 0 60px;
}

.changelog-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.changelog-container {
    max-width: 900px;
    margin: 0 auto;
}

.changelog-item {
    display: flex;
    gap: 40px;
    margin-bottom: 60px;
    position: relative;
}

.changelog-item::before {
    content: '';
    position: absolute;
    left: 60px;
    top: 80px;
    bottom: -60px;
    width: 2px;
    background: #e2e8f0;
}

.changelog-item:last-child::before {
    display: none;
}

.changelog-date {
    flex-shrink: 0;
    width: 120px;
    text-align: center;
    position: relative;
}

.changelog-date::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 20px;
    width: 12px;
    height: 12px;
    background: var(--primary);
    border: 3px solid #f8f9fa;
    border-radius: 50%;
    z-index: 1;
}

.date-day {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--secondary);
    line-height: 1;
}

.date-month {
    display: block;
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
    text-transform: uppercase;
    margin: 5px 0;
}

.date-year {
    display: block;
    font-size: 0.875rem;
    color: var(--gray-dark);
}

.changelog-content {
    flex: 1;
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.version-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.version-number {
    font-size: 1.75rem;
    color: var(--secondary);
    margin: 0;
}

.version-badge {
    background: var(--gray-light);
    color: var(--gray-dark);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.version-badge.version-latest {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
}

.changes-section {
    margin-bottom: 25px;
}

.changes-section:last-child {
    margin-bottom: 0;
}

.changes-title {
    font-size: 1.1rem;
    color: var(--secondary);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.changes-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.changes-list li {
    position: relative;
    padding-left: 25px;
    margin-bottom: 8px;
    color: var(--text-dark);
    line-height: 1.6;
}

.changes-list li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--primary);
    font-weight: bold;
    font-size: 1.2rem;
}

.changelog-subscribe {
    margin: 80px 0 40px;
}

.subscribe-card {
    background: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.subscribe-card h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--secondary);
}

.subscribe-card p {
    color: var(--gray-dark);
    margin-bottom: 25px;
}

.subscribe-form {
    display: flex;
    gap: 10px;
    max-width: 500px;
    margin: 0 auto;
}

.subscribe-input {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid var(--gray-medium);
    border-radius: 8px;
    font-size: 1rem;
}

.subscribe-input:focus {
    outline: none;
    border-color: var(--primary);
}

.changelog-links {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 40px;
}

.changelog-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--primary);
    font-weight: 500;
    transition: all 0.3s ease;
}

.changelog-link:hover {
    color: var(--primary-dark);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .changelog-item {
        flex-direction: column;
        gap: 20px;
    }
    
    .changelog-item::before {
        left: 60px;
        top: 120px;
    }
    
    .changelog-date {
        display: flex;
        align-items: center;
        gap: 15px;
        width: auto;
    }
    
    .changelog-date::after {
        right: auto;
        left: 60px;
        top: 50%;
        transform: translateY(-50%);
    }
    
    .subscribe-form {
        flex-direction: column;
    }
    
    .changelog-links {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
}
</style>

<?php
// Include footer
require_once ABSPATH . 'templates/footer.php';
?>
