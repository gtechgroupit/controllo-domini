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
                
                // 1. Misura tempo di risposta DNS
                $response_time = measureDnsResponseTime($domain);
                
                // 2. Recupera tutti i record DNS
                $dns_data = getAllDnsRecords($domain);
                $dns_results = $dns_data['records'];
                
                if (empty($dns_results)) {
                    $error_message = 'Nessun record DNS trovato per questo dominio. Verifica che il dominio sia attivo.';
                } else {
                    // 3. Analizza servizi cloud
                    $cloud_services = identifyCloudServices($dns_results, $domain);
                    
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
                    
                    // Log analisi per statistiche
                    logAnalysis($domain, array(
                        'dns_count' => count($dns_results),
                        'has_mx' => isset($dns_results['MX']) && !empty($dns_results['MX']),
                        'has_spf' => $email_config['has_spf'],
                        'cloud_services' => array_keys($cloud_services['detected_services']),
                        'blacklisted' => $blacklist_results['listed'] > 0
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
                               pattern="[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}"
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

<?php if ($analysis_completed && $dns_results): ?>

<!-- Results Section -->
<section id="results" class="results-section">
    <div class="container">
        
        <!-- Stats Overview -->
        <div class="stats-grid" data-aos="fade-up">
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value" data-value="<?php echo $response_time; ?>"><?php echo $response_time; ?><span style="font-size: 0.5em; font-weight: 400;">ms</span></div>
                <div class="stat-label">Tempo di risposta DNS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value" data-value="<?php echo array_sum(array_map('count', $dns_results)); ?>"><?php echo array_sum(array_map('count', $dns_results)); ?></div>
                <div class="stat-label">Record DNS totali</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔍</div>
                <div class="stat-value" data-value="<?php echo count($dns_results); ?>"><?php echo count($dns_results); ?></div>
                <div class="stat-label">Tipi di record trovati</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $whois_info['status']; ?></div>
                <div class="stat-label">Stato dominio</div>
            </div>
            <?php if ($whois_info['expires'] != 'Non disponibile'): ?>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php 
                    $days_until = daysUntil($whois_info['expires']);
                    if ($days_until !== false) {
                        echo $days_until;
                        echo '<span style="font-size: 0.5em; font-weight: 400;"> giorni</span>';
                    } else {
                        echo 'N/D';
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
                <div class="whois-item">
                    <div class="whois-label">Intestatario</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['owner']); ?></div>
                </div>
                <div class="whois-item">
                    <div class="whois-label">Registrar</div>
                    <div class="whois-value"><?php echo sanitizeOutput($whois_info['registrar']); ?></div>
                </div>
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
                <?php if (!empty($whois_info['nameservers'])): ?>
                <div class="whois-item" style="grid-column: 1 / -1;">
                    <div class="whois-label">Nameservers</div>
                    <div class="whois-value">
                        <?php foreach ($whois_info['nameservers'] as $ns): ?>
                            <span class="badge badge-primary"><?php echo sanitizeOutput($ns); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($whois_info['dnssec']): ?>
                <div class="whois-item">
                    <div class="whois-label">DNSSEC</div>
                    <div class="whois-value"><span class="badge badge-success">Attivo</span></div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php
            $whois_report = generateWhoisReport($whois_info);
            if (!empty($whois_report['risk_factors']) || !empty($whois_report['recommendations'])):
            ?>
            <div class="whois-recommendations">
                <?php foreach ($whois_report['risk_factors'] as $risk): ?>
                <div class="alert alert-warning">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-content"><?php echo sanitizeOutput($risk); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($whois_report['recommendations'] as $rec): ?>
                <div class="alert alert-info">
                    <span class="alert-icon">💡</span>
                    <span class="alert-content"><?php echo sanitizeOutput($rec); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Blacklist Check -->
        <?php if ($blacklist_results && !empty($blacklist_results['ips_checked'])): ?>
        <section class="blacklist-section" data-aos="fade-up">
            <div class="blacklist-header">
                <h2 class="blacklist-title">
                    <span>🛡️</span> Controllo Blacklist e Reputazione
                </h2>
                <div class="reputation-badge <?php echo $blacklist_results['reputation']['color']; ?>">
                    <span style="font-size: 1.5rem;"><?php echo $blacklist_results['reputation']['score']; ?>%</span>
                    <span><?php echo $blacklist_results['reputation']['status']; ?></span>
                </div>
            </div>
            
            <div class="blacklist-stats">
                <div class="blacklist-stat">
                    <div class="blacklist-stat-value"><?php echo count($blacklist_results['ips_checked']); ?></div>
                    <div class="blacklist-stat-label">IP Controllati</div>
                </div>
                <div class="blacklist-stat">
                    <div class="blacklist-stat-value"><?php echo count($GLOBALS['dnsbl_servers']); ?></div>
                    <div class="blacklist-stat-label">Blacklist Verificate</div>
                </div>
                <div class="blacklist-stat">
                    <div class="blacklist-stat-value" style="color: <?php echo $blacklist_results['listed'] > 0 ? 'var(--error)' : 'var(--success)'; ?>">
                        <?php echo $blacklist_results['listed']; ?>
                    </div>
                    <div class="blacklist-stat-label">Presenze in Blacklist</div>
                </div>
                <div class="blacklist-stat">
                    <div class="blacklist-stat-value"><?php echo $blacklist_results['statistics']['total_checks']; ?></div>
                    <div class="blacklist-stat-label">Controlli Totali</div>
                </div>
            </div>
            
            <?php if (!empty($blacklist_results['recommendations'])): ?>
            <div class="blacklist-recommendations">
                <?php foreach ($blacklist_results['recommendations'] as $rec): ?>
                <div class="alert alert-<?php echo $rec['type']; ?>">
                    <span class="alert-icon"><?php echo $rec['type'] == 'error' ? '⚠️' : '💡'; ?></span>
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
        <?php if (!empty($cloud_services['detected_services'])): ?>
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
                        <span><?php echo $service['category'] == 'email' ? '📧' : '☁️'; ?></span>
                        <?php echo sanitizeOutput($service['name']); ?>
                        <span class="badge badge-info"><?php echo ucfirst($service['confidence']); ?> confidence</span>
                    </div>
                    <?php if (!empty($service['details']['description'])): ?>
                    <p><?php echo sanitizeOutput($service['details']['description']); ?></p>
                    <?php endif; ?>
                    <div class="cloud-indicators">
                        <strong>Indicatori rilevati:</strong>
                        <ul>
                            <?php foreach (array_slice($service['indicators'], 0, 3) as $indicator): ?>
                            <li><?php echo sanitizeOutput($indicator); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($service['indicators']) > 3): ?>
                            <li>... e altri <?php echo count($service['indicators']) - 3; ?> indicatori</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Domain Health -->
        <?php if ($domain_health): ?>
        <section class="health-section" data-aos="fade-up">
            <div class="health-header">
                <h2 class="health-title">🏥 Salute del Dominio</h2>
                <div class="health-score">
                    <svg class="health-score-circle" viewBox="0 0 120 120">
                        <circle class="health-score-bg" cx="60" cy="60" r="54" />
                        <circle class="health-score-progress" 
                                cx="60" cy="60" r="54"
                                stroke-dasharray="339.292"
                                stroke-dashoffset="<?php echo 339.292 - (339.292 * $domain_health['dns_health']['score'] / 100); ?>" />
                    </svg>
                    <div class="health-score-text"><?php echo $domain_health['dns_health']['score']; ?></div>
                </div>
            </div>
            
            <div class="health-grid">
                <!-- Email Configuration -->
                <div class="health-card">
                    <h3>📧 Configurazione Email</h3>
                    <div class="health-items">
                        <div class="health-check <?php echo $domain_health['email_config']['has_mx'] ? 'success' : 'error'; ?>">
                            <span><?php echo $domain_health['email_config']['has_mx'] ? '✅' : '❌'; ?></span>
                            Record MX <?php echo $domain_health['email_config']['has_mx'] ? 'configurati' : 'mancanti'; ?>
                        </div>
                        <div class="health-check <?php echo $domain_health['email_config']['has_spf'] ? 'success' : 'warning'; ?>">
                            <span><?php echo $domain_health['email_config']['has_spf'] ? '✅' : '⚠️'; ?></span>
                            SPF <?php echo $domain_health['email_config']['has_spf'] ? 'configurato' : 'non configurato'; ?>
                        </div>
                        <div class="health-check <?php echo $domain_health['email_config']['has_dkim'] ? 'success' : 'info'; ?>">
                            <span><?php echo $domain_health['email_config']['has_dkim'] ? '✅' : 'ℹ️'; ?></span>
                            DKIM <?php echo $domain_health['email_config']['has_dkim'] ? 'attivo' : 'non rilevato'; ?>
                        </div>
                        <div class="health-check <?php echo $domain_health['email_config']['has_dmarc'] ? 'success' : 'info'; ?>">
                            <span><?php echo $domain_health['email_config']['has_dmarc'] ? '✅' : 'ℹ️'; ?></span>
                            DMARC <?php echo $domain_health['email_config']['has_dmarc'] ? 'configurato' : 'non configurato'; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Security -->
                <div class="health-card">
                    <h3>🔒 Sicurezza</h3>
                    <div class="health-items">
                        <div class="health-check <?php echo $domain_health['security']['has_caa'] ? 'success' : 'info'; ?>">
                            <span><?php echo $domain_health['security']['has_caa'] ? '✅' : 'ℹ️'; ?></span>
                            CAA <?php echo $domain_health['security']['has_caa'] ? 'configurato' : 'non configurato'; ?>
                        </div>
                        <div class="health-check <?php echo $domain_health['security']['has_dnssec'] ? 'success' : 'info'; ?>">
                            <span><?php echo $domain_health['security']['has_dnssec'] ? '✅' : 'ℹ️'; ?></span>
                            DNSSEC <?php echo $domain_health['security']['has_dnssec'] ? 'attivo' : 'non attivo'; ?>
                        </div>
                        <div class="health-score-mini">
                            <strong>Security Score:</strong> <?php echo $domain_health['security']['security_score']; ?>/100
                        </div>
                    </div>
                </div>
                
                <!-- Performance -->
                <div class="health-card">
                    <h3>⚡ Performance</h3>
                    <div class="health-items">
                        <div class="health-check">
                            <span>📊</span>
                            <?php echo $domain_health['performance']['ns_count']; ?> Nameserver configurati
                        </div>
                        <div class="health-check">
                            <span>⏱️</span>
                            Tempo risposta: <?php echo $response_time; ?>ms
                        </div>
                        <?php if (!empty($domain_health['performance']['recommendations'])): ?>
                        <div class="health-recommendations">
                            <?php foreach (array_slice($domain_health['performance']['recommendations'], 0, 2) as $rec): ?>
                            <small>• <?php echo sanitizeOutput($rec); ?></small><br>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- DNS Records Details -->
        <section class="dns-records-section" data-aos="fade-up">
            <div class="results-header">
                <h2>Record DNS Completi per <?php echo sanitizeOutput($domain); ?></h2>
                <p>Analisi completata il <?php echo date('d/m/Y \a\l\l\e H:i:s'); ?></p>
                <div class="export-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="exportResults('json')">
                        📥 Esporta JSON
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="exportResults('csv')">
                        📥 Esporta CSV
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="window.print()">
                        🖨️ Stampa
                    </button>
                </div>
            </div>
            
            <div class="results-body">
                <?php
                // Ordine preferito per la visualizzazione
                $preferred_order = array('A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SOA', 'SRV', 'CAA');
                
                // Mostra prima i record nell'ordine preferito
                foreach ($preferred_order as $type) {
                    if (isset($dns_results[$type])) {
                        echo formatDnsRecord($type, $dns_results[$type], $cloud_services);
                    }
                }
                
                // Mostra eventuali altri tipi di record
                foreach ($dns_results as $type => $records) {
                    if (!in_array($type, $preferred_order)) {
                        echo formatDnsRecord($type, $records, $cloud_services);
                    }
                }
                ?>
            </div>
        </section>
        
    </div>
</section>

<?php elseif ($analysis_completed): ?>
    <!-- No Results Found -->
    <section class="no-results-section">
        <div class="container">
            <div class="no-results" data-aos="fade-up">
                <div class="no-results-icon">🔍</div>
                <h3>Nessun record DNS trovato</h3>
                <p>Verifica che il dominio sia scritto correttamente e sia attivo.</p>
                <a href="/" class="btn btn-primary">Prova con un altro dominio</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Features Section -->
<section id="features" class="features-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Funzionalità Complete di Analisi</h2>
            <p>Tutto quello che ti serve per analizzare e monitorare i tuoi domini</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">
                    <span>🔍</span>
                </div>
                <h3>Analisi DNS Completa</h3>
                <p>Recuperiamo tutti i record DNS del dominio inclusi A, AAAA, MX, TXT, CNAME, NS, SOA, SRV e CAA.</p>
                <ul class="feature-list">
                    <li>Record IPv4 e IPv6</li>
                    <li>Configurazione email</li>
                    <li>Record di sicurezza</li>
                    <li>Analisi TTL</li>
                </ul>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">
                    <span>👤</span>
                </div>
                <h3>Dati WHOIS</h3>
                <p>Informazioni complete sull'intestatario del dominio, date di registrazione e scadenza.</p>
                <ul class="feature-list">
                    <li>Proprietario dominio</li>
                    <li>Registrar</li>
                    <li>Date importanti</li>
                    <li>Nameserver registrati</li>
                </ul>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">
                    <span>🛡️</span>
                </div>
                <h3>Controllo Blacklist</h3>
                <p>Verifichiamo la presenza del dominio in oltre 30 blacklist principali.</p>
                <ul class="feature-list">
                    <li>Spamhaus, SpamCop</li>
                    <li>Barracuda, SORBS</li>
                    <li>Reputazione email</li>
                    <li>Score reputazione</li>
                </ul>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">
                    <span>☁️</span>
                </div>
                <h3>Rilevamento Cloud</h3>
                <p>Identifichiamo automaticamente i servizi cloud utilizzati dal dominio.</p>
                <ul class="feature-list">
                    <li>Microsoft 365</li>
                    <li>Google Workspace</li>
                    <li>AWS, Azure, GCP</li>
                    <li>CDN e hosting</li>
                </ul>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-icon">
                    <span>📧</span>
                </div>
                <h3>Analisi Email</h3>
                <p>Verifica completa della configurazione email e autenticazione.</p>
                <ul class="feature-list">
                    <li>Record SPF</li>
                    <li>DKIM configuration</li>
                    <li>Policy DMARC</li>
                    <li>Server MX</li>
                </ul>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-icon">
                    <span>🔒</span>
                </div>
                <h3>Sicurezza DNS</h3>
                <p>Analisi approfondita delle configurazioni di sicurezza DNS.</p>
                <ul class="feature-list">
                    <li>DNSSEC status</li>
                    <li>CAA records</li>
                    <li>Security headers</li>
                    <li>Best practices</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="how-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Come Funziona</h2>
            <p>Analisi professionale del dominio in 3 semplici passaggi</p>
        </div>
        
        <div class="steps-grid">
            <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                <div class="step-number">1</div>
                <div class="step-icon">✍️</div>
                <h3>Inserisci il Dominio</h3>
                <p>Digita il nome del dominio che vuoi analizzare nel campo di ricerca. Non serve includere http:// o www.</p>
            </div>
            
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">2</div>
                <div class="step-icon">🔍</div>
                <h3>Analisi Automatica</h3>
                <p>Il nostro sistema esegue controlli approfonditi su DNS, WHOIS, blacklist e servizi cloud in tempo reale.</p>
            </div>
            
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">3</div>
                <div class="step-icon">📊</div>
                <h3>Report Dettagliato</h3>
                <p>Ricevi un report completo con tutti i dati, suggerimenti per miglioramenti e opzioni di export.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Domande Frequenti</h2>
            <p>Risposte alle domande più comuni sul nostro servizio</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
                <div class="faq-question">
                    <h3>Cos'è il controllo domini?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>Il controllo domini è un'analisi completa che verifica tutti gli aspetti tecnici di un dominio internet: record DNS, informazioni WHOIS, presenza in blacklist, servizi cloud utilizzati e configurazioni di sicurezza. È essenziale per amministratori di sistema, professionisti IT e proprietari di siti web.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
                <div class="faq-question">
                    <h3>Quali informazioni posso ottenere?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>Puoi ottenere: tutti i record DNS (A, MX, TXT, etc.), informazioni sull'intestatario del dominio, date di registrazione e scadenza, nameserver configurati, presenza in blacklist spam, servizi cloud rilevati (Microsoft 365, Google Workspace), configurazione email (SPF, DKIM, DMARC) e molto altro.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
                <div class="faq-question">
                    <h3>Il servizio è gratuito?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>Sì, Controllo Domini è un servizio completamente gratuito. Non richiediamo registrazione né carte di credito. Puoi analizzare tutti i domini che desideri senza limitazioni.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
                <div class="faq-question">
                    <h3>Quanto tempo richiede l'analisi?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>L'analisi completa richiede generalmente meno di 2 secondi. Questo include il recupero di tutti i record DNS, le informazioni WHOIS, il controllo su oltre 30 blacklist e l'identificazione dei servizi cloud. I tempi possono variare leggermente in base alla complessità del dominio.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
                <div class="faq-question">
                    <h3>Cosa sono i record SPF, DKIM e DMARC?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>Sono protocolli di autenticazione email: SPF specifica quali server possono inviare email per il tuo dominio, DKIM aggiunge una firma digitale alle email per verificarne l'autenticità, DMARC definisce come gestire le email che non passano i controlli SPF/DKIM. Sono fondamentali per la deliverability delle email.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="600">
                <div class="faq-question">
                    <h3>Perché controllare le blacklist?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>Se il tuo dominio o IP è presente in blacklist spam, le tue email potrebbero non essere consegnate e il tuo sito potrebbe essere bloccato. Il nostro controllo verifica oltre 30 blacklist principali per assicurarti che la tua reputazione online sia pulita.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content" data-aos="zoom-in">
            <h2>Inizia Subito l'Analisi del Tuo Dominio</h2>
            <p>Unisciti a migliaia di professionisti che utilizzano Controllo Domini ogni giorno</p>
            <a href="#domain-check" class="btn btn-primary btn-lg">
                <span>Analizza Ora</span>
                <span>→</span>
            </a>
            <div class="cta-features">
                <span><i>✓</i> 100% Gratuito</span>
                <span><i>✓</i> Nessuna Registrazione</span>
                <span><i>✓</i> Risultati Immediati</span>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once ABSPATH . 'templates/footer.php';
?>
