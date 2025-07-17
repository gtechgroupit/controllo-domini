<?php
/**
 * Controllo Domini - Pagina Principale
 * Sistema professionale per l'analisi DNS e WHOIS
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @version 4.0
 * @website https://controllodomini.it
 */

// Definisce la costante ABSPATH se non definita
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Carica configurazione
require_once ABSPATH . 'config/config.php';

// Carica funzioni
require_once ABSPATH . 'includes/utilities.php';
require_once ABSPATH . 'includes/dns-functions.php';
require_once ABSPATH . 'includes/whois-functions.php';
require_once ABSPATH . 'includes/cloud-detection.php';
require_once ABSPATH . 'includes/blacklist-functions.php';

// Variabili per la gestione del form
$domain = '';
$dns_results = null;
$whois_info = null;
$cloud_services = null;
$blacklist_results = null;
$domain_health = null;
$error_message = '';
$success_message = '';
$response_time = 0;
$analysis_completed = false;

// Gestione del form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    
    // Validazione dominio
    $validated_domain = validateDomain($domain);
    
    if (!$validated_domain) {
        $error_message = 'Inserisci un nome di dominio valido (es: esempio.com)';
    } else {
        $domain = $validated_domain;
        
        // Rate limiting
        $user_ip = getVisitorIP();
        if (!checkRateLimit($user_ip)) {
            $error_message = 'Hai raggiunto il limite di richieste. Riprova tra qualche minuto.';
        } else {
            try {
                // Inizia l'analisi
                $analysis_start = microtime(true);
                
                // Esegui operazioni in parallelo dove possibile
                $promises = array();
                
                // 1. Misura tempo di risposta DNS
                $response_time = measureDnsResponseTime($domain);
                
                // 2. Recupera tutti i record DNS con timeout
                $dns_data = getAllDnsRecords($domain, 10); // Timeout di 10 secondi
                $dns_results = $dns_data['records'];
                
                if (empty($dns_results)) {
                    $error_message = 'Nessun record DNS trovato per questo dominio. Verifica che il dominio sia attivo.';
                } else {
                    // Esegui le seguenti operazioni in modo ottimizzato
                    
                    // 3. Analizza servizi cloud (veloce)
                    $cloud_services = identifyCloudServices($dns_results, $domain);
                    
                    // Assicurati che detected sia un array
                    if (!isset($cloud_services['detected']) || !is_array($cloud_services['detected'])) {
                        $cloud_services['detected'] = array();
                    }
                    
                    // 4. Ottieni informazioni WHOIS con cache
                    $whois_info = getWhoisInfoCached($domain, isset($_GET['debug']));
                    
                    // 5. Controlla blacklist (limita il numero di blacklist per velocità)
                    $blacklist_results = checkBlacklistsFast($domain);
                    
                    // 6. Analizza configurazione email
                    $email_config = analyzeEmailConfiguration($dns_results);
                    
                    // 7. Analizza sicurezza DNS
                    $security_analysis = analyzeSecurityRecords($dns_results);
                    
                    // 8. Calcola health score complessivo
                    $domain_health = calculateDomainHealth(array(
                        'dns' => $dns_results,
                        'whois' => $whois_info,
                        'blacklist' => $blacklist_results,
                        'email' => $email_config,
                        'security' => $security_analysis,
                        'response_time' => $response_time
                    ));
                    
                    // 9. Log analisi per statistiche (asincrono)
                    logAnalysisAsync($domain, array(
                        'dns_count' => count($dns_results),
                        'has_mx' => !empty($dns_results['MX']),
                        'has_spf' => isset($email_config['has_spf']) ? $email_config['has_spf'] : false,
                        'cloud_services' => array_keys($cloud_services['detected']),
                        'blacklisted' => isset($blacklist_results['statistics']['total_listings']) ? $blacklist_results['statistics']['total_listings'] > 0 : false
                    ));
                    
                    $analysis_completed = true;
                    $success_message = 'Analisi completata con successo!';
                }
                
            } catch (Exception $e) {
                $error_message = 'Si è verificato un errore durante l\'analisi. Riprova tra qualche istante.';
                error_log('Errore analisi dominio ' . $domain . ': ' . $e->getMessage());
            }
        }
    }
}

// Impostazioni per la pagina
$page_name = 'Home';
$body_class = 'home-page';
$load_charts = true;
$load_clipboard = true;

// Genera titolo e descrizione dinamici
$page_title = $domain ? generatePageTitle($domain) : SEO_TITLE;
$page_description = $domain ? generateMetaDescription($domain) : SEO_DESCRIPTION;

// Include header
require_once ABSPATH . 'templates/header.php';
?>

<!-- Domain Check Form Section -->
<section id="domain-check" class="form-section">
    <div class="container">
        <div class="form-card" data-aos="zoom-in">
            <form method="POST" action="" id="domainForm" class="domain-form">
                <div class="form-group">
                    <label class="form-label" for="domain">
                        Inserisci il dominio da analizzare
                    </label>
                    <div class="input-group">
                        <span class="input-icon">🌐</span>
                        <input type="text" 
                               id="domain" 
                               name="domain" 
                               class="form-input domain-input"
                               placeholder="esempio.com" 
                               value="<?php echo htmlspecialchars($domain); ?>" 
                               required
                               autocomplete="off"
                               autofocus>
                        <button type="submit" class="btn btn-primary submit-btn" id="analyzeBtn">
                            <span class="btn-text">Analizza</span>
                            <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                <div class="alert alert-error" data-aos="fade-in">
                    <span class="alert-icon">⚠️</span>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message && $analysis_completed): ?>
                <div class="alert alert-success" data-aos="fade-in">
                    <span class="alert-icon">✅</span>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
            </form>
            
            <!-- Quick Examples -->
            <div class="quick-examples">
                <span>Esempi:</span>
                <button type="button" class="example-link" data-domain="google.com">google.com</button>
                <button type="button" class="example-link" data-domain="microsoft.com">microsoft.com</button>
                <button type="button" class="example-link" data-domain="amazon.com">amazon.com</button>
            </div>
        </div>
    </div>
</section>

<?php if ($analysis_completed && $dns_results): ?>
<!-- Results Section -->
<section id="results" class="results-section">
    <div class="container">
        <!-- Health Score Overview -->
        <div class="health-overview" data-aos="fade-up">
            <div class="health-header">
                <h2>Salute del Dominio: <?php echo htmlspecialchars($domain); ?></h2>
                <div class="health-score-circle" data-score="<?php echo $domain_health['score']; ?>">
                    <svg viewBox="0 0 200 200">
                        <circle cx="100" cy="100" r="90" fill="none" stroke="#e0e0e0" stroke-width="20"/>
                        <circle cx="100" cy="100" r="90" fill="none" stroke="url(#scoreGradient)" stroke-width="20" 
                                stroke-dasharray="<?php echo 565.48 * ($domain_health['score'] / 100); ?> 565.48"
                                stroke-linecap="round"
                                transform="rotate(-90 100 100)"/>
                        <defs>
                            <linearGradient id="scoreGradient">
                                <stop offset="0%" style="stop-color:#ff4757"/>
                                <stop offset="50%" style="stop-color:#ffa502"/>
                                <stop offset="100%" style="stop-color:#26de81"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="score-text">
                        <span class="score-value"><?php echo $domain_health['score']; ?></span>
                        <span class="score-label">/ 100</span>
                    </div>
                </div>
                <p class="health-status <?php echo $domain_health['status_class']; ?>">
                    <?php echo $domain_health['status_text']; ?>
                </p>
            </div>
        </div>
        
        <!-- Key Stats -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value" data-value="<?php echo $response_time; ?>"><?php echo $response_time; ?><span style="font-size: 0.5em;">ms</span></div>
                <div class="stat-label">Tempo di risposta</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value" data-value="<?php echo isset($dns_data['stats']['total_records']) ? $dns_data['stats']['total_records'] : 0; ?>">
                    <?php echo isset($dns_data['stats']['total_records']) ? $dns_data['stats']['total_records'] : 0; ?>
                </div>
                <div class="stat-label">Record DNS</div>
            </div>
            <?php if ($whois_info && isset($whois_info['expires']) && $whois_info['expires'] != 'Non disponibile'): ?>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value <?php echo getExpirationClass(daysUntil($whois_info['expires'])); ?>">
                <?php 
                    $days = daysUntil($whois_info['expires']);
                    if ($days !== false) {
                        echo $days;
                    } else {
                        echo 'N/A';
                    }
                ?></div>
                <div class="stat-label">Giorni alla scadenza</div>
            </div>
            <?php endif; ?>
            <?php if ($blacklist_results && isset($blacklist_results['reputation'])): ?>
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value" data-value="<?php echo $blacklist_results['reputation']['score']; ?>"><?php echo $blacklist_results['reputation']['score']; ?><span style="font-size: 0.5em; font-weight: 400;">%</span></div>
                <div class="stat-label">Reputazione</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- WHOIS Information -->
        <?php if ($whois_info): ?>
        <section class="whois-section" data-aos="fade-up">
            <div class="whois-header">
                <span class="whois-icon">👤</span>
                <h2 class="whois-title">Informazioni Intestatario Dominio</h2>
            </div>
            
            <div class="whois-grid">
                <?php if (isset($whois_info['owner'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Intestatario</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['owner']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['registrar'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Registrar</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['registrar']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['created'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Data Registrazione</div>
                    <div class="whois-value"><?php 
                        echo sanitizeOutput($whois_info['created']);
                        if ($whois_info['created'] != 'Non disponibile') {
                            $age = calculateDomainAge($whois_info['created']);
                            if ($age !== false) {
                                echo ' <small>(' . floor($age / 365) . ' anni)</small>';
                            }
                        }
                    ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['expires'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Data Scadenza</div>
                    <div class="whois-value <?php echo getExpirationClass(daysUntil($whois_info['expires'])); ?>">
                        <?php 
                        echo sanitizeOutput($whois_info['expires']);
                        $days = daysUntil($whois_info['expires']);
                        if ($days !== false) {
                            echo ' <small>(' . $days . ' giorni)</small>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['updated'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Ultimo Aggiornamento</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['updated']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['status'])): ?>
                <div class="whois-item">
                    <div class="whois-label">Stato</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['status']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($whois_info['nameservers'])): ?>
                <div class="whois-item whois-item-full">
                    <div class="whois-label">Nameserver</div>
                    <div class="whois-value">
                        <?php foreach ($whois_info['nameservers'] as $ns): ?>
                            <span class="nameserver-badge"><?php echo sanitizeOutput($ns); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($whois_info['dnssec']) && $whois_info['dnssec']): ?>
                <div class="whois-item">
                    <div class="whois-label">DNSSEC</div>
                    <div class="whois-value"><span class="badge badge-success">Attivo</span></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Cloud Services Detection -->
        <?php if ($cloud_services && !empty($cloud_services['detected'])): ?>
        <section class="cloud-section" data-aos="fade-up">
            <div class="cloud-header">
                <span class="cloud-icon">☁️</span>
                <h2 class="cloud-title">Servizi Cloud Rilevati</h2>
            </div>
            
            <div class="cloud-grid">
                <?php foreach ($cloud_services['detected'] as $service => $details): ?>
                <div class="cloud-card">
                    <div class="cloud-card-header">
                        <span class="cloud-service-icon"><?php echo getServiceIcon($service); ?></span>
                        <h3><?php echo htmlspecialchars($service); ?></h3>
                    </div>
                    <div class="cloud-card-body">
                        <p class="confidence-level">
                            Confidenza: <strong><?php echo $details['confidence']; ?>%</strong>
                        </p>
                        <?php if (!empty($details['indicators'])): ?>
                        <div class="indicators">
                            <p class="indicators-label">Indicatori trovati:</p>
                            <ul class="indicators-list">
                                <?php foreach ($details['indicators'] as $indicator): ?>
                                <li><?php echo htmlspecialchars($indicator); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- DNS Records -->
        <?php if ($dns_results): ?>
        <section class="dns-section" data-aos="fade-up">
            <div class="dns-header">
                <span class="dns-icon">🔍</span>
                <h2 class="dns-title">Record DNS Completi</h2>
                <div class="dns-actions">
                    <button class="btn btn-secondary" onclick="exportDNS()">
                        <span>📥</span> Esporta
                    </button>
                    <button class="btn btn-secondary" onclick="copyAllDNS()">
                        <span>📋</span> Copia Tutto
                    </button>
                </div>
            </div>
            
            <div class="dns-records-container">
                <?php foreach ($dns_results as $type => $records): ?>
                <?php if (!empty($records)): ?>
                <div class="dns-type-section" data-aos="fade-up" data-aos-delay="100">
                    <div class="dns-type-header">
                        <h3 class="dns-type-title">
                            <span class="dns-type-badge"><?php echo $type; ?></span>
                            <span class="dns-type-description"><?php echo getDnsTypeDescription($type); ?></span>
                            <span class="dns-type-count"><?php echo count($records); ?> record<?php echo count($records) > 1 ? 's' : ''; ?></span>
                        </h3>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="dns-table">
                            <thead>
                                <tr>
                                    <?php echo getDNSTableHeaders($type); ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                <tr class="dns-row">
                                    <?php echo formatDNSRecord($type, $record); ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Email Configuration Analysis -->
        <?php if (isset($email_config) && $email_config['has_mx']): ?>
        <section class="email-section" data-aos="fade-up">
            <div class="email-header">
                <span class="email-icon">✉️</span>
                <h2 class="email-title">Configurazione Email</h2>
            </div>
            
            <div class="email-grid">
                <div class="email-stat">
                    <div class="email-stat-icon">📧</div>
                    <div class="email-stat-value"><?php echo $email_config['mx_count']; ?></div>
                    <div class="email-stat-label">Server MX</div>
                </div>
                
                <div class="email-stat">
                    <div class="email-stat-icon <?php echo $email_config['has_spf'] ? 'success' : 'warning'; ?>">
                        <?php echo $email_config['has_spf'] ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="email-stat-value">SPF</div>
                    <div class="email-stat-label"><?php echo $email_config['has_spf'] ? 'Configurato' : 'Non trovato'; ?></div>
                </div>
                
                <div class="email-stat">
                    <div class="email-stat-icon <?php echo $email_config['has_dkim'] ? 'success' : 'warning'; ?>">
                        <?php echo $email_config['has_dkim'] ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="email-stat-value">DKIM</div>
                    <div class="email-stat-label"><?php echo $email_config['has_dkim'] ? 'Attivo' : 'Non configurato'; ?></div>
                </div>
                
                <div class="email-stat">
                    <div class="email-stat-icon <?php echo $email_config['has_dmarc'] ? 'success' : 'warning'; ?>">
                        <?php echo $email_config['has_dmarc'] ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="email-stat-value">DMARC</div>
                    <div class="email-stat-label"><?php echo $email_config['has_dmarc'] ? 'Attivo' : 'Non configurato'; ?></div>
                </div>
            </div>
            
            <?php if (isset($email_config['email_provider']) && $email_config['email_provider']): ?>
            <div class="email-provider">
                <p>Provider Email Rilevato: <strong><?php echo htmlspecialchars($email_config['email_provider']); ?></strong></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($email_config['recommendations'])): ?>
            <div class="email-recommendations">
                <h3>Raccomandazioni</h3>
                <ul>
                    <?php foreach ($email_config['recommendations'] as $rec): ?>
                    <li><?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Blacklist Check -->
        <?php if ($blacklist_results): ?>
        <section class="blacklist-section" data-aos="fade-up">
            <div class="blacklist-header">
                <span class="blacklist-icon">🚫</span>
                <h2 class="blacklist-title">Controllo Blacklist</h2>
            </div>
            
            <div class="blacklist-overview">
                <div class="reputation-gauge">
                    <div class="gauge-circle" style="--score: <?php echo $blacklist_results['reputation']['score']; ?>">
                        <svg viewBox="0 0 200 200">
                            <circle cx="100" cy="100" r="90" fill="none" stroke="#e0e0e0" stroke-width="15"/>
                            <circle cx="100" cy="100" r="90" fill="none" 
                                    stroke="<?php echo getReputationColor($blacklist_results['reputation']['score']); ?>" 
                                    stroke-width="15" 
                                    stroke-dasharray="<?php echo 565.48 * ($blacklist_results['reputation']['score'] / 100); ?> 565.48"
                                    stroke-linecap="round"
                                    transform="rotate(-90 100 100)"/>
                        </svg>
                        <div class="gauge-text">
                            <span class="gauge-value"><?php echo $blacklist_results['reputation']['score']; ?>%</span>
                            <span class="gauge-label">Reputazione</span>
                        </div>
                    </div>
                    <p class="reputation-status" style="color: <?php echo getReputationColor($blacklist_results['reputation']['score']); ?>">
                        <?php echo $blacklist_results['reputation']['rating']; ?>
                    </p>
                </div>
                
                <div class="blacklist-stats">
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value" style="color: <?php echo $blacklist_results['statistics']['total_listings'] > 0 ? 'var(--error)' : 'var(--success)'; ?>">
                            <?php echo $blacklist_results['statistics']['total_listings']; ?>
                        </div>
                        <div class="blacklist-stat-label">Presenze in Blacklist</div>
                    </div>
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value"><?php echo $blacklist_results['statistics']['total_checks']; ?></div>
                        <div class="blacklist-stat-label">Controlli Totali</div>
                    </div>
                    <div class="blacklist-stat">
                        <div class="blacklist-stat-value"><?php echo count($blacklist_results['ips_checked']); ?></div>
                        <div class="blacklist-stat-label">IP Verificati</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($blacklist_results['listings'])): ?>
            <div class="blacklist-issues">
                <h3><span>⚠️</span> IP Presenti in Blacklist</h3>
                <div class="issues-grid">
                    <?php 
                    $grouped_listings = array();
                    foreach ($blacklist_results['listings'] as $listing) {
                        $key = $listing['ip'];
                        if (!isset($grouped_listings[$key])) {
                            $grouped_listings[$key] = array(
                                'ip' => $listing['ip'],
                                'source' => $listing['source'],
                                'blacklists' => array()
                            );
                        }
                        $grouped_listings[$key]['blacklists'][] = $listing['blacklist'];
                    }
                    ?>
                    <?php foreach ($grouped_listings as $ip_data): ?>
                    <div class="issue-card">
                        <div class="issue-header">
                            <span class="issue-ip"><?php echo htmlspecialchars($ip_data['ip']); ?></span>
                            <span class="issue-source"><?php echo htmlspecialchars($ip_data['source']); ?></span>
                        </div>
                        <div class="issue-blacklists">
                            <?php foreach ($ip_data['blacklists'] as $bl): ?>
                            <span class="blacklist-badge"><?php echo htmlspecialchars($bl); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="blacklist-clean">
                <h3><span>✅</span> Nessuna presenza in blacklist rilevata!</h3>
                <p>Tutti gli IP del dominio hanno una reputazione pulita.</p>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Security Analysis -->
        <?php if (isset($security_analysis)): ?>
        <section class="security-section" data-aos="fade-up">
            <div class="security-header">
                <span class="security-icon">🔒</span>
                <h2 class="security-title">Analisi Sicurezza</h2>
            </div>
            
            <div class="security-grid">
                <div class="security-item <?php echo $security_analysis['has_caa'] ? 'enabled' : 'disabled'; ?>">
                    <div class="security-item-icon"><?php echo $security_analysis['has_caa'] ? '✅' : '❌'; ?></div>
                    <div class="security-item-content">
                        <h4>CAA Records</h4>
                        <p><?php echo $security_analysis['has_caa'] ? 'Configurati' : 'Non configurati'; ?></p>
                    </div>
                </div>
                
                <div class="security-item <?php echo (isset($whois_info['dnssec']) && $whois_info['dnssec']) ? 'enabled' : 'disabled'; ?>">
                    <div class="security-item-icon"><?php echo (isset($whois_info['dnssec']) && $whois_info['dnssec']) ? '✅' : '❌'; ?></div>
                    <div class="security-item-content">
                        <h4>DNSSEC</h4>
                        <p><?php echo (isset($whois_info['dnssec']) && $whois_info['dnssec']) ? 'Attivo' : 'Non attivo'; ?></p>
                    </div>
                </div>
                
                <div class="security-item <?php echo $security_analysis['has_tlsa'] ? 'enabled' : 'disabled'; ?>">
                    <div class="security-item-icon"><?php echo $security_analysis['has_tlsa'] ? '✅' : '❌'; ?></div>
                    <div class="security-item-content">
                        <h4>DANE/TLSA</h4>
                        <p><?php echo $security_analysis['has_tlsa'] ? 'Configurato' : 'Non configurato'; ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($security_analysis['recommendations'])): ?>
            <div class="security-recommendations">
                <h3>Raccomandazioni di Sicurezza</h3>
                <ul>
                    <?php foreach ($security_analysis['recommendations'] as $rec): ?>
                    <li><?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
    </div>
</section>
<?php endif; ?>

<!-- Progress indicator durante l'analisi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('domainForm');
    const analyzeBtn = document.getElementById('analyzeBtn');
    
    form.addEventListener('submit', function(e) {
        // Mostra indicatore di caricamento
        analyzeBtn.disabled = true;
        analyzeBtn.innerHTML = '<span class="btn-text">Analisi in corso...</span><span class="spinner"></span>';
        
        // Timeout di sicurezza
        setTimeout(function() {
            if (analyzeBtn.disabled) {
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<span class="btn-text">Analizza</span><span class="btn-icon">→</span>';
            }
        }, 30000); // 30 secondi di timeout
    });
});
</script>

<?php
// Include footer
require_once ABSPATH . 'templates/footer.php';

// Helper functions per la visualizzazione
function getDNSTableHeaders($type) {
    $headers = array(
        'A' => '<th>Host</th><th>IP</th><th>TTL</th>',
        'AAAA' => '<th>Host</th><th>IPv6</th><th>TTL</th>',
        'MX' => '<th>Host</th><th>Priority</th><th>Mail Server</th><th>TTL</th>',
        'TXT' => '<th>Host</th><th>Value</th><th>TTL</th>',
        'NS' => '<th>Host</th><th>Nameserver</th><th>TTL</th>',
        'CNAME' => '<th>Host</th><th>Target</th><th>TTL</th>',
        'SOA' => '<th>Host</th><th>Primary NS</th><th>Email</th><th>Serial</th><th>TTL</th>',
        'SRV' => '<th>Service</th><th>Priority</th><th>Weight</th><th>Port</th><th>Target</th><th>TTL</th>',
        'CAA' => '<th>Host</th><th>Flags</th><th>Tag</th><th>Value</th><th>TTL</th>'
    );
    
    return isset($headers[$type]) ? $headers[$type] : '<th>Type</th><th>Value</th><th>TTL</th>';
}

function getDnsTypeDescription($type) {
    $descriptions = array(
        'A' => 'Indirizzi IPv4',
        'AAAA' => 'Indirizzi IPv6',
        'MX' => 'Server di posta',
        'TXT' => 'Record di testo',
        'NS' => 'Name Server',
        'CNAME' => 'Alias canonici',
        'SOA' => 'Start of Authority',
        'SRV' => 'Record di servizio',
        'CAA' => 'Certificate Authority Authorization'
    );
    
    return isset($descriptions[$type]) ? $descriptions[$type] : 'Record ' . $type;
}

function formatDNSRecord($type, $record) {
    $html = '';
    
    switch ($type) {
        case 'A':
            $html = sprintf(
                '<td>%s</td><td><code>%s</code></td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['ip']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'AAAA':
            $html = sprintf(
                '<td>%s</td><td><code>%s</code></td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['ipv6']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'MX':
            $html = sprintf(
                '<td>%s</td><td>%d</td><td>%s</td><td>%s</td>',
                htmlspecialchars($record['host']),
                $record['pri'],
                htmlspecialchars($record['target']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'TXT':
            $html = sprintf(
                '<td>%s</td><td><code class="txt-value">%s</code></td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['txt']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'NS':
            $html = sprintf(
                '<td>%s</td><td>%s</td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['target']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'CNAME':
            $html = sprintf(
                '<td>%s</td><td>%s</td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['target']),
                formatTTL($record['ttl'])
            );
            break;
            
        case 'SOA':
            $html = sprintf(
                '<td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td>',
                htmlspecialchars($record['host']),
                htmlspecialchars($record['mname']),
                htmlspecialchars($record['rname']),
                $record['serial'],
                formatTTL($record['ttl'])
            );
            break;
            
        case 'CAA':
            $html = sprintf(
                '<td>%s</td><td>%d</td><td>%s</td><td>%s</td><td>%s</td>',
                htmlspecialchars($record['host']),
                $record['flags'],
                htmlspecialchars($record['tag']),
                htmlspecialchars($record['value']),
                formatTTL($record['ttl'])
            );
            break;
            
        default:
            $html = sprintf(
                '<td>%s</td><td>%s</td><td>%s</td>',
                $type,
                htmlspecialchars(print_r($record, true)),
                isset($record['ttl']) ? formatTTL($record['ttl']) : 'N/A'
            );
    }
    
    return $html;
}

function getServiceIcon($service) {
    $icons = array(
        'Microsoft 365' => '🏢',
        'Google Workspace' => '🔷',
        'Cloudflare' => '☁️',
        'Amazon Web Services' => '🚀',
        'GoDaddy' => '🌐',
        'Aruba' => '🇮🇹',
        'OVH' => '🇫🇷',
        'DigitalOcean' => '🌊',
        'Namecheap' => '💰'
    );
    
    return isset($icons[$service]) ? $icons[$service] : '☁️';
}

function getReputationColor($score) {
    if ($score >= 95) return '#26de81';
    if ($score >= 85) return '#20bf6b';
    if ($score >= 70) return '#ffa502';
    if ($score >= 50) return '#ff6348';
    return '#ee5a6f';
}

function calculateDomainHealth($data) {
    $score = 100;
    $issues = array();
    
    // Penalità per tempo di risposta
    if ($data['response_time'] > 1000) {
        $score -= 10;
        $issues[] = 'Tempo di risposta elevato';
    } elseif ($data['response_time'] > 500) {
        $score -= 5;
    }
    
    // Penalità per blacklist
    if (isset($data['blacklist']['statistics']['total_listings']) && 
        $data['blacklist']['statistics']['total_listings'] > 0) {
        $score -= min(30, $data['blacklist']['statistics']['total_listings'] * 5);
        $issues[] = 'Presenza in blacklist';
    }
    
    // Penalità per mancanza SPF/DKIM/DMARC
    if (isset($data['email'])) {
        if ($data['email']['has_mx'] && !$data['email']['has_spf']) {
            $score -= 10;
            $issues[] = 'SPF non configurato';
        }
        if ($data['email']['has_mx'] && !$data['email']['has_dmarc']) {
            $score -= 5;
            $issues[] = 'DMARC non configurato';
        }
    }
    
    // Penalità per mancanza DNSSEC
    if (isset($data['whois']['dnssec']) && !$data['whois']['dnssec']) {
        $score -= 5;
    }
    
    // Penalità per scadenza imminente
    if (isset($data['whois']['expires'])) {
        $days_to_expiry = daysUntil($data['whois']['expires']);
        if ($days_to_expiry !== false) {
            if ($days_to_expiry < 0) {
                $score -= 50;
                $issues[] = 'Dominio scaduto!';
            } elseif ($days_to_expiry < 30) {
                $score -= 20;
                $issues[] = 'Scadenza imminente';
            } elseif ($days_to_expiry < 90) {
                $score -= 10;
                $issues[] = 'Scadenza prossima';
            }
        }
    }
    
    // Normalizza score
    $score = max(0, min(100, $score));
    
    // Determina stato
    if ($score >= 90) {
        $status_text = 'Eccellente';
        $status_class = 'health-excellent';
    } elseif ($score >= 75) {
        $status_text = 'Buono';
        $status_class = 'health-good';
    } elseif ($score >= 60) {
        $status_text = 'Sufficiente';
        $status_class = 'health-fair';
    } elseif ($score >= 40) {
        $status_text = 'Problematico';
        $status_class = 'health-poor';
    } else {
        $status_text = 'Critico';
        $status_class = 'health-critical';
    }
    
    return array(
        'score' => $score,
        'status_text' => $status_text,
        'status_class' => $status_class,
        'issues' => $issues
    );
}

// Definizione funzione getBreadcrumb se non esiste
if (!function_exists('getBreadcrumb')) {
    function getBreadcrumb($page_name) {
        return array(
            array('name' => 'Home', 'url' => '/'),
            array('name' => $page_name, 'url' => '')
        );
    }
}

// Wrapper functions per le versioni ottimizzate
function getWhoisInfoCached($domain, $debug = false) {
    // Controlla cache prima
    $cache_key = 'whois_' . md5($domain);
    $cached = getCacheData($cache_key, 3600); // Cache per 1 ora
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Se non in cache, recupera
    $whois_info = getWhoisInfo($domain, $debug);
    
    // Salva in cache
    setCacheData($cache_key, $whois_info);
    
    return $whois_info;
}

function checkBlacklistsFast($domain) {
    // Usa solo le blacklist più importanti per velocità
    $priority_blacklists = array(
        'zen.spamhaus.org',
        'bl.spamcop.net',
        'b.barracudacentral.org',
        'dnsbl.sorbs.net',
        'dul.dnsbl.sorbs.net'
    );
    
    return checkBlacklistsSubset($domain, $priority_blacklists);
}

function getAllDnsRecords($domain, $timeout = 10) {
    // Aggiungi timeout per evitare blocchi
    $context = stream_context_create(array(
        'dns' => array(
            'timeout' => $timeout
        )
    ));
    
    // Chiama la funzione originale con timeout
    return getAllDnsRecordsWithContext($domain, $context);
}

function logAnalysisAsync($domain, $data) {
    // Log in modo asincrono per non rallentare la risposta
    // In produzione, potresti usare una coda di job
    try {
        logAnalysis($domain, $data);
    } catch (Exception $e) {
        // Ignora errori di log per non bloccare l'utente
        error_log('Errore log analisi: ' . $e->getMessage());
    }
}
?>
