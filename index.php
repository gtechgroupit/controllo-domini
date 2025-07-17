<?php
/**
 * Homepage - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Carica configurazione
require_once __DIR__ . '/config/config.php';

// Include funzioni necessarie
require_once ABSPATH . 'includes/functions.php';
require_once ABSPATH . 'includes/dns-functions.php';
require_once ABSPATH . 'includes/whois-functions.php';
require_once ABSPATH . 'includes/blacklist-functions.php';
require_once ABSPATH . 'includes/cloud-detection.php';
require_once ABSPATH . 'includes/security-analysis.php';

// Inizializza variabili
$domain = '';
$analysis_completed = false;
$error_message = '';
$success_message = '';
$analysis_time = 0;

// Array per risultati
$dns_results = array();
$whois_info = array();
$blacklist_results = array();
$cloud_services = array();
$domain_health = array();
$suggestions = array();
$email_config = array();
$security_analysis = array();
$performance_analysis = array();

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['domain'])) {
    // Ottieni dominio dal POST o GET
    $domain = isset($_POST['domain']) ? $_POST['domain'] : (isset($_GET['domain']) ? $_GET['domain'] : '');
    
    // Pulizia e validazione dominio
    $domain = trim(strtolower($domain));
    $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
    $domain = explode('/', $domain)[0]; // Rimuovi path
    
    if (!empty($domain)) {
        // Validazione formato dominio
        if (!isValidDomain($domain)) {
            $error_message = 'Formato dominio non valido. Inserisci un dominio nel formato: esempio.com';
        } else {
            // Avvia analisi
            $analysis_start = microtime(true);
            
            try {
                // Controlla rate limiting
                if (isRateLimited($_SERVER['REMOTE_ADDR'])) {
                    throw new Exception('Troppe richieste. Riprova tra qualche minuto.');
                }
                
                // 1. Ottieni tutti i record DNS
                $dns_results = getAllDnsRecords($domain);
                
                if (empty($dns_results)) {
                    throw new Exception('Impossibile risolvere il dominio. Verifica che sia corretto.');
                } else {
                    // 2. Identifica servizi cloud
                    $cloud_services = identifyCloudServices($dns_results, $domain);
                    
                    // Assicurati che detected_services esista e sia un array
                    if (!isset($cloud_services['detected_services']) || !is_array($cloud_services['detected_services'])) {
                        $cloud_services['detected_services'] = array();
                    }
                    
                    // 3. Analizza sottodomini comuni
                    $subdomain_analysis = analyzeCommonSubdomains($domain);
                    
                    // 4. Ottieni informazioni WHOIS
                    $whois_info = getWhoisInfo($domain, isset($_GET['debug']));
                    
                    // 5. Controlla blacklist
                    $blacklist_results = checkBlacklists($domain);
                    
                    // 6. Analizza configurazione email
                    $email_config = analyzeEmailConfiguration($dns_results);
                    
                    // 7. Analizza sicurezza DNS
                    $security_analysis = analyzeSecurityRecords($dns_results);
                    
                    // 8. Analizza performance DNS
                    $performance_analysis = analyzeDnsPerformance($dns_results);
                    
                    // 9. Calcola salute del dominio
                    $domain_health = array(
                        'dns_health' => analyzeDomainHealth($dns_results, $cloud_services, $blacklist_results),
                        'email_config' => $email_config,
                        'security' => $security_analysis,
                        'performance' => $performance_analysis
                    );
                    
                    // 10. Genera suggerimenti
                    $suggestions = getDnsSuggestions($dns_results, $domain);
                    
                    // Log analisi per statistiche - CORREZIONE QUI
                    $cloud_services_list = array();
                    if (isset($cloud_services['detected_services']) && is_array($cloud_services['detected_services'])) {
                        $cloud_services_list = array_keys($cloud_services['detected_services']);
                    }
                    
                    logAnalysis($domain, array(
                        'dns_count' => count($dns_results),
                        'has_mx' => isset($dns_results['MX']) && !empty($dns_results['MX']),
                        'has_spf' => isset($email_config['has_spf']) ? $email_config['has_spf'] : false,
                        'cloud_services' => $cloud_services_list,
                        'blacklisted' => isset($blacklist_results['listed']) ? $blacklist_results['listed'] > 0 : false
                    ));
                    
                    $analysis_completed = true;
                    $analysis_time = round((microtime(true) - $analysis_start) * 1000, 2);
                    
                    // Messaggio di successo
                    $success_message = "Analisi completata in {$analysis_time}ms";
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
                        <span class="input-icon">🌐</span>
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
                               pattern="[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.?[a-zA-Z]{2,}"
                               title="Inserisci un dominio valido (es: esempio.com)">
                    </div>
                    <div class="form-help">
                        <small>Inserisci il dominio senza http:// o www</small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-block submit-btn" id="submitBtn">
                    <span>Avvia Analisi Completa</span>
                </button>
            </form>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert" data-aos="fade-in">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-content"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message && $analysis_completed): ?>
                <div class="alert alert-success" role="alert" data-aos="fade-in">
                    <span class="alert-icon">✅</span>
                    <span class="alert-content"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div class="hero-stats" data-aos="fade-up" data-aos-delay="200">
            <div class="hero-stat">
                <span class="hero-stat-value" data-value="50000">0</span>
                <span class="hero-stat-label">Domini Analizzati</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value">99.9%</span>
                <span class="hero-stat-label">Uptime</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value">&lt; 2s</span>
                <span class="hero-stat-label">Tempo Medio</span>
            </div>
        </div>
    </div>
</section>

<?php if ($analysis_completed && !empty($dns_results)): ?>
    <!-- Domain Overview Section -->
    <section id="domain-overview" class="section">
        <div class="container">
            <div class="overview-header" data-aos="fade-up">
                <h2 class="section-title">Analisi completa per <span class="highlight-domain"><?php echo htmlspecialchars($domain); ?></span></h2>
                <p class="section-subtitle">Risultati dell'analisi DNS, WHOIS e servizi cloud</p>
            </div>
            
            <!-- Domain Health Score -->
            <?php if (!empty($domain_health['dns_health'])): ?>
            <div class="health-score-container" data-aos="fade-up">
                <div class="health-score-card">
                    <h3>Salute del Dominio</h3>
                    <div class="health-score-visual">
                        <div class="health-score-circle" data-score="<?php echo $domain_health['dns_health']['score']; ?>">
                            <svg viewBox="0 0 200 200">
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#e0e0e0" stroke-width="12"/>
                                <circle cx="100" cy="100" r="90" fill="none" stroke="url(#scoreGradient)" 
                                        stroke-width="12" stroke-linecap="round"
                                        stroke-dasharray="<?php echo 565.5 * ($domain_health['dns_health']['score'] / 100); ?> 565.5"
                                        transform="rotate(-90 100 100)"/>
                                <defs>
                                    <linearGradient id="scoreGradient">
                                        <stop offset="0%" stop-color="#ff6b6b"/>
                                        <stop offset="50%" stop-color="#ffd93d"/>
                                        <stop offset="100%" stop-color="#6bcf7f"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="health-score-text">
                                <span class="score-value"><?php echo $domain_health['dns_health']['score']; ?></span>
                                <span class="score-label">/ 100</span>
                            </div>
                        </div>
                    </div>
                    <p class="health-score-status <?php echo $domain_health['dns_health']['status_class']; ?>">
                        <?php echo $domain_health['dns_health']['status_text']; ?>
                    </p>
                </div>
                
                <!-- Quick Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-icon">📊</span>
                        <span class="stat-value"><?php echo count($dns_results); ?></span>
                        <span class="stat-label">Record DNS</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">☁️</span>
                        <span class="stat-value"><?php echo count($cloud_services['detected_services'] ?? []); ?></span>
                        <span class="stat-label">Servizi Cloud</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🔒</span>
                        <span class="stat-value"><?php echo $email_config['has_spf'] && $email_config['has_dkim'] && $email_config['has_dmarc'] ? 'Ottima' : 'Da Migliorare'; ?></span>
                        <span class="stat-label">Sicurezza Email</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🚫</span>
                        <span class="stat-value"><?php echo $blacklist_results['listed']; ?>/<?php echo $blacklist_results['checked']; ?></span>
                        <span class="stat-label">Blacklist</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- DNS Records Section -->
    <section id="dns-records" class="section section-alt">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Record DNS</h2>
                <p class="section-subtitle">Configurazione DNS completa del dominio</p>
            </div>
            
            <?php foreach ($dns_results as $type => $records): ?>
            <?php if (!empty($records)): ?>
            <div class="dns-type-section" data-aos="fade-up">
                <h3 class="dns-type-title">
                    <span class="dns-type-badge"><?php echo $type; ?></span>
                    <?php echo getDnsTypeDescription($type); ?>
                </h3>
                
                <div class="table-wrapper">
                    <table class="dns-table">
                        <thead>
                            <tr>
                                <?php echo getDnsTableHeaders($type); ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr class="dns-row <?php echo getDnsRowClass($record, $type); ?>">
                                <?php echo formatDnsRecord($record, $type); ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- Subdomain Analysis -->
            <?php if (!empty($subdomain_analysis) && count($subdomain_analysis) > 0): ?>
            <div class="subdomain-section" data-aos="fade-up">
                <h3 class="section-subtitle">Sottodomini Rilevati</h3>
                <div class="subdomain-grid">
                    <?php foreach ($subdomain_analysis as $subdomain => $exists): ?>
                    <?php if ($exists): ?>
                    <div class="subdomain-card">
                        <span class="subdomain-name"><?php echo htmlspecialchars($subdomain); ?></span>
                        <span class="subdomain-status active">Attivo</span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- WHOIS Information Section -->
    <?php if (!empty($whois_info) && !isset($whois_info['error'])): ?>
    <section id="whois-info" class="section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Informazioni WHOIS</h2>
                <p class="section-subtitle">Dati di registrazione del dominio</p>
            </div>
            
            <div class="whois-content" data-aos="fade-up">
                <div class="whois-grid">
                    <?php if (isset($whois_info['registrar'])): ?>
                    <div class="whois-item">
                        <span class="whois-label">Registrar</span>
                        <span class="whois-value"><?php echo sanitizeOutput($whois_info['registrar']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($whois_info['creation_date'])): ?>
                    <div class="whois-item">
                        <span class="whois-label">Data Registrazione</span>
                        <span class="whois-value"><?php echo formatDate($whois_info['creation_date']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($whois_info['expiry_date'])): ?>
                    <div class="whois-item">
                        <span class="whois-label">Data Scadenza</span>
                        <span class="whois-value <?php echo isExpiringSoon($whois_info['expiry_date']) ? 'text-warning' : ''; ?>">
                            <?php echo formatDate($whois_info['expiry_date']); ?>
                            <?php if (isExpiringSoon($whois_info['expiry_date'])): ?>
                                <span class="badge badge-warning">In scadenza</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($whois_info['status'])): ?>
                    <div class="whois-item">
                        <span class="whois-label">Stato</span>
                        <span class="whois-value">
                            <?php foreach ((array)$whois_info['status'] as $status): ?>
                                <span class="status-badge"><?php echo formatDomainStatus($status); ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($whois_info['nameservers'])): ?>
                    <div class="whois-item whois-item-full">
                        <span class="whois-label">Nameserver</span>
                        <span class="whois-value">
                            <?php foreach ($whois_info['nameservers'] as $ns): ?>
                                <span class="nameserver-item"><?php echo sanitizeOutput($ns); ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($whois_info['raw']) && isset($_GET['debug'])): ?>
                <div class="whois-raw">
                    <h4>WHOIS Raw Data</h4>
                    <pre><?php echo htmlspecialchars($whois_info['raw']); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Blacklist Check Section -->
    <?php if (!empty($blacklist_results['results'])): ?>
    <section id="blacklist-check" class="section section-alt">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Controllo Blacklist</h2>
                <p class="section-subtitle">Verifica presenza in <?php echo $blacklist_results['checked']; ?> blacklist principali</p>
            </div>
            
            <div class="blacklist-summary" data-aos="fade-up">
                <div class="blacklist-score <?php echo $blacklist_results['listed'] > 0 ? 'warning' : 'success'; ?>">
                    <span class="score-icon"><?php echo $blacklist_results['listed'] > 0 ? '⚠️' : '✅'; ?></span>
                    <span class="score-text">
                        <?php if ($blacklist_results['listed'] > 0): ?>
                            Trovato in <?php echo $blacklist_results['listed']; ?> blacklist
                        <?php else: ?>
                            Nessuna blacklist rilevata
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <div class="blacklist-grid" data-aos="fade-up">
                <?php foreach ($blacklist_results['results'] as $bl): ?>
                <div class="blacklist-item <?php echo $bl['listed'] ? 'listed' : 'clean'; ?>">
                    <span class="bl-status"><?php echo $bl['listed'] ? '❌' : '✅'; ?></span>
                    <span class="bl-name"><?php echo htmlspecialchars($bl['name']); ?></span>
                    <?php if ($bl['listed'] && isset($bl['reason'])): ?>
                        <span class="bl-reason"><?php echo htmlspecialchars($bl['reason']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($blacklist_results['listed'] > 0): ?>
            <div class="blacklist-help" data-aos="fade-up">
                <h4>Come rimuovere il dominio dalle blacklist?</h4>
                <ol>
                    <li>Identifica e risolvi il problema che ha causato il listing</li>
                    <li>Contatta ogni blacklist per richiedere la rimozione</li>
                    <li>Implementa misure di sicurezza per prevenire futuri listing</li>
                    <li>Monitora regolarmente lo stato delle blacklist</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Email Configuration Section -->
    <?php if (!empty($email_config)): ?>
    <section id="email-config" class="section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Configurazione Email</h2>
                <p class="section-subtitle">Analisi SPF, DKIM e DMARC</p>
            </div>
            
            <div class="email-config-grid" data-aos="fade-up">
                <!-- SPF -->
                <div class="config-card <?php echo $email_config['has_spf'] ? 'success' : 'warning'; ?>">
                    <div class="config-header">
                        <span class="config-icon"><?php echo $email_config['has_spf'] ? '✅' : '⚠️'; ?></span>
                        <h3>SPF Record</h3>
                    </div>
                    <div class="config-content">
                        <?php if ($email_config['has_spf']): ?>
                            <p class="config-status">SPF configurato correttamente</p>
                            <code class="spf-record"><?php echo htmlspecialchars($email_config['spf_record']); ?></code>
                            <?php if (!empty($email_config['spf_includes'])): ?>
                                <div class="spf-includes">
                                    <h4>Include:</h4>
                                    <ul>
                                        <?php foreach ($email_config['spf_includes'] as $include): ?>
                                            <li><?php echo htmlspecialchars($include); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="config-status">SPF non configurato</p>
                            <p class="config-help">Aggiungi un record TXT SPF per autenticare le email inviate dal tuo dominio.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- DKIM -->
                <div class="config-card <?php echo $email_config['has_dkim'] ? 'success' : 'info'; ?>">
                    <div class="config-header">
                        <span class="config-icon"><?php echo $email_config['has_dkim'] ? '✅' : 'ℹ️'; ?></span>
                        <h3>DKIM</h3>
                    </div>
                    <div class="config-content">
                        <?php if ($email_config['has_dkim']): ?>
                            <p class="config-status">DKIM rilevato</p>
                            <p class="config-help">Selettori DKIM trovati nel dominio.</p>
                        <?php else: ?>
                            <p class="config-status">DKIM non rilevato</p>
                            <p class="config-help">Configura DKIM per firmare digitalmente le tue email.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- DMARC -->
                <div class="config-card <?php echo $email_config['has_dmarc'] ? 'success' : 'warning'; ?>">
                    <div class="config-header">
                        <span class="config-icon"><?php echo $email_config['has_dmarc'] ? '✅' : '⚠️'; ?></span>
                        <h3>DMARC Policy</h3>
                    </div>
                    <div class="config-content">
                        <?php if ($email_config['has_dmarc']): ?>
                            <p class="config-status">DMARC configurato</p>
                            <code class="dmarc-record"><?php echo htmlspecialchars($email_config['dmarc_record']); ?></code>
                            <div class="dmarc-policy">
                                <span class="policy-label">Policy:</span>
                                <span class="policy-value <?php echo getDmarcPolicyClass($email_config['dmarc_policy']); ?>">
                                    <?php echo strtoupper($email_config['dmarc_policy']); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <p class="config-status">DMARC non configurato</p>
                            <p class="config-help">Implementa DMARC per proteggere il tuo dominio dal phishing.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Email Security Score -->
            <div class="email-security-score" data-aos="fade-up">
                <h3>Punteggio Sicurezza Email</h3>
                <div class="security-score-bar">
                    <div class="score-fill" style="width: <?php echo calculateEmailScore($email_config); ?>%"></div>
                </div>
                <p class="score-text"><?php echo calculateEmailScore($email_config); ?>% - <?php echo getEmailScoreText(calculateEmailScore($email_config)); ?></p>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Recommendations Section -->
    <?php if (!empty($suggestions)): ?>
    <section id="recommendations" class="section section-alt">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Raccomandazioni</h2>
                <p class="section-subtitle">Suggerimenti per migliorare la configurazione del dominio</p>
            </div>
            
            <div class="recommendations-grid" data-aos="fade-up">
                <?php foreach ($suggestions as $rec): ?>
                <div class="recommendation-card <?php echo $rec['priority']; ?>">
                    <span class="rec-icon"><?php echo $rec['priority'] == 'high' ? '⚠️' : '💡'; ?></span>
                    <div>
                        <strong><?php echo sanitizeOutput($rec['message']); ?></strong>
                        <p><?php echo sanitizeOutput($rec['action']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Cloud Services -->
        <?php if (!empty($cloud_services['detected_services']) && is_array($cloud_services['detected_services'])): ?>
        <section class="cloud-services" data-aos="fade-up">
            <div class="cloud-header">
                <span class="cloud-icon">☁️</span>
                <div class="cloud-content">
                    <h2 class="cloud-title">Servizi Cloud Rilevati</h2>
                    <p>Abbiamo identificato <?php echo count($cloud_services['detected_services']); ?> servizi cloud configurati per questo dominio</p>
                </div>
            </div>
            
            <div class="cloud-grid">
                <?php foreach ($cloud_services['detected_services'] as $service_id => $service): ?>
                <div class="cloud-card">
                    <div class="cloud-service-name">
                        <span><?php echo isset($service['category']) && $service['category'] == 'email' ? '📧' : '☁️'; ?></span>
                        <?php echo htmlspecialchars($service['name'] ?? 'Servizio Cloud'); ?>
                    </div>
                    <div class="cloud-service-details">
                        <span class="service-category"><?php echo htmlspecialchars($service['category'] ?? 'cloud'); ?></span>
                        <span class="service-confidence confidence-<?php echo htmlspecialchars($service['confidence'] ?? 'medium'); ?>">
                            <?php echo ucfirst($service['confidence'] ?? 'medium'); ?> confidence
                        </span>
                    </div>
                    <?php if (!empty($service['indicators'])): ?>
                    <div class="service-indicators">
                        <?php foreach (array_slice($service['indicators'], 0, 3) as $indicator): ?>
                            <small><?php echo htmlspecialchars($indicator); ?></small>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section id="features" class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Funzionalità Complete</h2>
            <p class="section-subtitle">Tutto quello che serve per analizzare e monitorare i tuoi domini</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">
                    <span>🔍</span>
                </div>
                <h3>Analisi DNS Completa</h3>
                <p>Verifica tutti i record DNS inclusi A, AAAA, MX, TXT, CNAME, NS e molto altro.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">
                    <span>📊</span>
                </div>
                <h3>Informazioni WHOIS</h3>
                <p>Ottieni dettagli su proprietà, registrar, date di scadenza e stato del dominio.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">
                    <span>🚫</span>
                </div>
                <h3>Controllo Blacklist</h3>
                <p>Verifica la presenza in oltre 30 blacklist per proteggere la tua reputazione.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">
                    <span>☁️</span>
                </div>
                <h3>Rilevamento Cloud</h3>
                <p>Identifica automaticamente servizi cloud come Office 365, Google Workspace, AWS.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-icon">
                    <span>🔒</span>
                </div>
                <h3>Analisi Sicurezza</h3>
                <p>Controlla SPF, DKIM, DMARC, DNSSEC e altre configurazioni di sicurezza.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-icon">
                    <span>⚡</span>
                </div>
                <h3>Performance DNS</h3>
                <p>Analizza tempi di risposta, TTL e ottimizzazione delle query DNS.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="section section-alt">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Come Funziona</h2>
            <p class="section-subtitle">Analizza qualsiasi dominio in 3 semplici passaggi</p>
        </div>
        
        <div class="steps-container">
            <div class="step" data-aos="fade-right" data-aos-delay="100">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Inserisci il Dominio</h3>
                    <p>Digita il nome del dominio che vuoi analizzare nel campo di ricerca.</p>
                </div>
            </div>
            
            <div class="step" data-aos="fade-right" data-aos-delay="200">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Analisi Automatica</h3>
                    <p>Il sistema esegue controlli approfonditi su DNS, WHOIS, blacklist e servizi cloud.</p>
                </div>
            </div>
            
            <div class="step" data-aos="fade-right" data-aos-delay="300">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Risultati Dettagliati</h3>
                    <p>Ricevi un report completo con suggerimenti per ottimizzare la configurazione.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Domande Frequenti</h2>
            <p class="section-subtitle">Risposte alle domande più comuni sul nostro servizio</p>
        </div>
        
        <div class="faq-container" data-aos="fade-up">
            <div class="faq-item">
                <h3 class="faq-question">
                    <span class="faq-icon">+</span>
                    Quanto costa utilizzare Controllo Domini?
                </h3>
                <div class="faq-answer">
                    <p>Controllo Domini è completamente gratuito per uso personale e professionale. Non richiediamo registrazione né carta di credito.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">
                    <span class="faq-icon">+</span>
                    Quali tipi di domini posso analizzare?
                </h3>
                <div class="faq-answer">
                    <p>Puoi analizzare qualsiasi dominio valido con estensioni come .com, .it, .org, .net e molte altre. Supportiamo anche domini internazionalizzati (IDN).</p>
                </div>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">
                    <span class="faq-icon">+</span>
                    I miei dati sono al sicuro?
                </h3>
                <div class="faq-answer">
                    <p>Assolutamente sì. Non salviamo dati personali e tutte le analisi vengono eseguite in tempo reale. I risultati sono visibili solo a te.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">
                    <span class="faq-icon">+</span>
                    Quanto spesso vengono aggiornati i dati?
                </h3>
                <div class="faq-answer">
                    <p>Tutti i dati vengono recuperati in tempo reale dai server DNS autoritativi e dai database WHOIS ufficiali, garantendo informazioni sempre aggiornate.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">
                    <span class="faq-icon">+</span>
                    Posso utilizzare l'API per integrazioni?
                </h3>
                <div class="faq-answer">
                    <p>Sì, offriamo un'API RESTful per sviluppatori e aziende che necessitano di integrare le nostre funzionalità nelle loro applicazioni. Contattaci per maggiori informazioni.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section id="cta" class="section cta-section">
    <div class="container">
        <div class="cta-content" data-aos="zoom-in">
            <h2>Inizia Subito l'Analisi del Tuo Dominio</h2>
            <p>Scopri tutto sulla configurazione e la sicurezza del tuo dominio in pochi secondi</p>
            <a href="#domain-check" class="btn btn-white btn-lg smooth-scroll">
                Analizza Ora
            </a>
        </div>
    </div>
</section>

<?php require_once ABSPATH . 'templates/footer.php'; ?>
