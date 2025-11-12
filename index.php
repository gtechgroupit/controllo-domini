<?php
/**
 * Controllo Domini - Pagina Principale
 * Sistema professionale per l'analisi DNS, WHOIS e sicurezza domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @version 4.2.1
 * @website https://controllodomini.it
 */

// Definisce la costante ABSPATH se non definita
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Carica configurazione
require_once ABSPATH . 'config/config.php';

// Carica tutte le funzioni necessarie
require_once ABSPATH . 'includes/utilities.php';
require_once ABSPATH . 'includes/dns-functions.php';
require_once ABSPATH . 'includes/whois-functions.php';
require_once ABSPATH . 'includes/cloud-detection.php';
require_once ABSPATH . 'includes/blacklist-functions.php';
require_once ABSPATH . 'includes/ssl-certificate.php';
require_once ABSPATH . 'includes/security-headers.php';
require_once ABSPATH . 'includes/technology-detection.php';
require_once ABSPATH . 'includes/social-meta-analysis.php';
require_once ABSPATH . 'includes/performance-analysis.php';
require_once ABSPATH . 'includes/robots-sitemap.php';
require_once ABSPATH . 'includes/redirect-analysis.php';
require_once ABSPATH . 'includes/port-scanner.php';

/**
 * Safe htmlspecialchars wrapper che gestisce array e valori non-string
 * 
 * @param mixed $value Valore da escapare
 * @param string $default Valore di default se il valore non è una stringa
 * @return string Stringa escaped
 */
function safeHtmlspecialchars($value, $default = '') {
    if (is_string($value)) {
        return htmlspecialchars($value);
    } elseif (is_numeric($value)) {
        return htmlspecialchars((string)$value);
    } elseif (is_array($value)) {
        // Se è un array, prova a estrarre informazioni rilevanti
        if (isset($value['CN'])) {
            return htmlspecialchars($value['CN']);
        } elseif (isset($value['O'])) {
            return htmlspecialchars($value['O']);
        } else {
            // Converti array in stringa formattata
            $parts = array();
            foreach ($value as $k => $v) {
                if (is_string($v)) {
                    $parts[] = $k . '=' . $v;
                }
            }
            return htmlspecialchars(implode(', ', $parts) ?: $default);
        }
    } else {
        return htmlspecialchars($default);
    }
}

// Variabili per la gestione del form
$domain = '';
$dns_results = null;
$whois_info = null;
$cloud_services = null;
$blacklist_results = null;
$domain_health = null;
$ssl_info = null;
$security_headers = null;
$technologies = null;
$social_meta = null;
$performance_data = null;
$robots_analysis = null;
$sitemap_analysis = null;
$redirect_analysis = null;
$port_scan_results = null;
$error_message = '';
$success_message = '';
$response_time = 0;
$analysis_completed = false;
$analysis_duration = 0;

// Gestione del form POST e parametri GET per deep linking
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) ||
    (isset($_GET['domain']) && isset($_GET['analyze']))) {

    // Verifica CSRF token per richieste POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrf_token)) {
            $error_message = 'Richiesta non valida. Ricarica la pagina e riprova.';
            goto skip_analysis;
        }
    }

    $domain = trim($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST['domain'] : $_GET['domain']);
    $scan_ports = isset($_POST['scan_ports']) || isset($_GET['scan_ports']);
    
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
                    
                    // Assicurati che detected sia un array
                    if (!isset($cloud_services['detected']) || !is_array($cloud_services['detected'])) {
                        $cloud_services['detected'] = array();
                    }
                    
                    // 4. Ottieni informazioni WHOIS
                    $whois_info = getWhoisInfo($domain, isset($_GET['debug']));
                    
                    // 5. Controlla blacklist
                    $blacklist_results = checkBlacklists($domain);
                    
                    // 6. Analizza configurazione email
                    $email_config = analyzeEmailConfiguration($dns_results);
                    
                    // 7. Analizza sicurezza DNS
                    $security_analysis = analyzeSecurityRecords($dns_results);
                    
                    // 8. Analizza certificato SSL
                    $ssl_info = analyzeSSLCertificate($domain);
                    
                    // 9. Controlla security headers
                    $security_headers = analyzeSecurityHeaders($domain);
                    
                    // 10. Rileva tecnologie
                    $technologies = detectTechnologyStack($domain);
                    
                    // 11. Analizza meta tag social
                    $social_meta = analyzeSocialMetaTags('https://' . $domain);
                    
                    // 12. Analisi performance
                    $performance_data = analyzePerformance($domain);
                    
                    // 13. Controlla robots.txt e sitemap
                    $robots_analysis = analyzeRobotsTxt($domain);
                    $sitemap_analysis = analyzeSitemap($domain);
                    
                    // 14. Analizza redirect
                    $redirect_analysis = analyzeRedirectChain('https://' . $domain);
                    
                    // 15. Scansione porte (se richiesta)
                    if ($scan_ports) {
                        $port_scan_results = scanPorts($domain);
                    }
                    
                    // 16. Calcola health score complessivo
                    $domain_health = calculateDomainHealth(array(
                        'dns' => $dns_results,
                        'whois' => $whois_info,
                        'blacklist' => $blacklist_results,
                        'email' => $email_config,
                        'security' => $security_analysis,
                        'ssl' => $ssl_info,
                        'headers' => $security_headers,
                        'performance' => $performance_data,
                        'response_time' => $response_time
                    ));
                    
                    // Calcola durata analisi
                    $analysis_duration = round((microtime(true) - $analysis_start), 2);
                    
                    // 17. Log analisi per statistiche
                    logAnalysis($domain, array(
                        'dns_count' => count($dns_results),
                        'has_mx' => !empty($dns_results['MX']),
                        'has_spf' => isset($email_config['has_spf']) ? $email_config['has_spf'] : false,
                        'has_ssl' => isset($ssl_info['valid']) ? $ssl_info['valid'] : false,
                        'cloud_services' => array_keys($cloud_services['detected']),
                        'blacklisted' => isset($blacklist_results['statistics']['total_listings']) ? $blacklist_results['statistics']['total_listings'] > 0 : false,
                        'technologies' => isset($technologies['detected']) ? count($technologies['detected']) : 0,
                        'health_score' => $domain_health['score'],
                        'analysis_duration' => $analysis_duration
                    ));
                    
                    $analysis_completed = true;
                    $success_message = 'Analisi completata con successo in ' . $analysis_duration . ' secondi!';
                }
                
            } catch (Exception $e) {
                $error_message = 'Si è verificato un errore durante l\'analisi. Riprova tra qualche istante.';
                error_log('Errore analisi dominio ' . $domain . ': ' . $e->getMessage());
            }
        }
    }

    skip_analysis: // Label for CSRF failure skip
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
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

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
                    
                    <!-- Opzioni avanzate -->
                    <div class="advanced-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="scan_ports" value="1" <?php echo isset($_POST['scan_ports']) ? 'checked' : ''; ?>>
                            <span>Includi scansione porte (richiede più tempo)</span>
                        </label>
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
                <button type="button" class="example-link" data-domain="gtechgroup.it">gtechgroup.it</button>
            </div>
        </div>
    </div>
</section>

<?php if ($analysis_completed && $dns_results): ?>
<!-- Results Section - SPOSTATA SUBITO DOPO IL FORM -->
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
            <?php if ($ssl_info && isset($ssl_info['valid'])): ?>
            <div class="stat-card">
                <div class="stat-icon">🔒</div>
                <div class="stat-value <?php echo $ssl_info['valid'] ? 'text-success' : 'text-danger'; ?>"><?php echo $ssl_info['valid'] ? 'Valido' : 'Non Valido'; ?></div>
                <div class="stat-label">SSL</div>
            </div>
            <?php endif; ?>
            <?php if ($blacklist_results && isset($blacklist_results['reputation'])): ?>
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value" data-value="<?php echo $blacklist_results['reputation']['score']; ?>"><?php echo $blacklist_results['reputation']['score']; ?><span style="font-size: 0.5em; font-weight: 400;">%</span></div>
                <div class="stat-label">Reputazione</div>
            </div>
            <?php endif; ?>
            <?php if ($technologies && isset($technologies['detected'])): ?>
            <div class="stat-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-value"><?php echo count($technologies['detected']); ?></div>
                <div class="stat-label">Tecnologie</div>
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
        
        <!-- SSL Certificate Information -->
        <?php if ($ssl_info && isset($ssl_info['valid'])): ?>
        <section class="ssl-section" data-aos="fade-up">
            <div class="ssl-header">
                <span class="ssl-icon">🔒</span>
                <h2 class="ssl-title">Certificato SSL/TLS</h2>
            </div>
            
            <div class="ssl-content">
                <div class="ssl-status <?php echo $ssl_info['valid'] ? 'ssl-valid' : 'ssl-invalid'; ?>">
                    <div class="ssl-status-icon"><?php echo $ssl_info['valid'] ? '✅' : '❌'; ?></div>
                    <div class="ssl-status-text">
                        <h3><?php echo $ssl_info['valid'] ? 'Certificato Valido' : 'Certificato Non Valido'; ?></h3>
                        <?php if (isset($ssl_info['error'])): ?>
                            <p><?php echo htmlspecialchars($ssl_info['error']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($ssl_info['valid'] && isset($ssl_info['details'])): ?>
                <div class="ssl-details">
                    <div class="ssl-detail-item">
                        <span class="detail-label">Emesso per:</span>
                        <span class="detail-value"><?php 
                            if (isset($ssl_info['details']['subject'])) {
                                echo safeHtmlspecialchars($ssl_info['details']['subject'], 'N/A');
                            } else {
                                echo 'N/A';
                            }
                        ?></span>
                    </div>
                    <div class="ssl-detail-item">
                        <span class="detail-label">Emesso da:</span>
                        <span class="detail-value"><?php 
                            if (isset($ssl_info['details']['issuer'])) {
                                echo safeHtmlspecialchars($ssl_info['details']['issuer'], 'N/A');
                            } else {
                                echo 'N/A';
                            }
                        ?></span>
                    </div>
                    <div class="ssl-detail-item">
                        <span class="detail-label">Valido dal:</span>
                        <span class="detail-value"><?php 
                            if (isset($ssl_info['details']['valid_from'])) {
                                echo safeHtmlspecialchars($ssl_info['details']['valid_from'], 'N/A');
                            } else {
                                echo 'N/A';
                            }
                        ?></span>
                    </div>
                    <div class="ssl-detail-item">
                        <span class="detail-label">Valido fino al:</span>
                        <span class="detail-value <?php echo (isset($ssl_info['days_until_expiry']) && $ssl_info['days_until_expiry'] < 30) ? 'text-warning' : ''; ?>">
                            <?php 
                            if (isset($ssl_info['details']['valid_to'])) {
                                echo safeHtmlspecialchars($ssl_info['details']['valid_to'], 'N/A');
                            } else {
                                echo 'N/A';
                            }
                            ?>
                            <?php if (isset($ssl_info['days_until_expiry']) && $ssl_info['days_until_expiry'] >= 0): ?>
                                <small>(<?php echo $ssl_info['days_until_expiry']; ?> giorni)</small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (isset($ssl_info['details']['san']) && !empty($ssl_info['details']['san'])): ?>
                    <div class="ssl-detail-item">
                        <span class="detail-label">Domini alternativi:</span>
                        <span class="detail-value"><?php 
                            if (is_array($ssl_info['details']['san'])) {
                                // Filtra solo le stringhe dall'array
                                $san_strings = array_filter($ssl_info['details']['san'], 'is_string');
                                echo htmlspecialchars(implode(', ', $san_strings));
                            } else {
                                echo safeHtmlspecialchars($ssl_info['details']['san']);
                            }
                        ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Security Headers -->
        <?php if ($security_headers && isset($security_headers['headers'])): ?>
        <section class="security-headers-section" data-aos="fade-up">
            <div class="security-headers-header">
                <span class="security-icon">🛡️</span>
                <h2 class="security-title">Security Headers</h2>
                <div class="security-score">
                    Score: <span class="score-badge <?php echo getScoreClass($security_headers['score']); ?>">
                        <?php echo $security_headers['score']; ?>/100
                    </span>
                </div>
            </div>
            
            <div class="headers-grid">
                <?php foreach ($security_headers['headers'] as $header => $info): ?>
                <div class="header-item <?php echo $info['present'] ? 'header-present' : 'header-missing'; ?>">
                    <div class="header-status">
                        <?php echo $info['present'] ? '✅' : '❌'; ?>
                    </div>
                    <div class="header-info">
                        <h4><?php echo safeHtmlspecialchars($info['name']); ?></h4>
                        <p><?php echo safeHtmlspecialchars($info['description']); ?></p>
                        <?php if ($info['present'] && isset($info['value'])): ?>
                            <code><?php echo safeHtmlspecialchars($info['value']); ?></code>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($security_headers['recommendations'])): ?>
            <div class="security-recommendations">
                <h3>Raccomandazioni</h3>
                <ul>
                    <?php foreach ($security_headers['recommendations'] as $rec): ?>
                    <li><?php echo safeHtmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Technologies Detection -->
        <?php if ($technologies && !empty($technologies['detected'])): ?>
        <section class="technologies-section" data-aos="fade-up">
            <div class="technologies-header">
                <span class="tech-icon">🔧</span>
                <h2 class="tech-title">Tecnologie Rilevate</h2>
            </div>
            
            <div class="technologies-grid">
                <?php if (isset($technologies['categories']) && is_array($technologies['categories'])): ?>
                    <?php foreach ($technologies['categories'] as $category => $techs): ?>
                    <?php if (!empty($techs) && is_array($techs)): ?>
                    <div class="tech-category">
                        <h3><?php echo safeHtmlspecialchars($category); ?></h3>
                        <div class="tech-items">
                            <?php foreach ($techs as $tech): ?>
                            <?php if (is_array($tech)): ?>
                            <div class="tech-item">
                                <?php if (isset($tech['icon'])): ?>
                                <img src="<?php echo safeHtmlspecialchars($tech['icon']); ?>" alt="<?php echo safeHtmlspecialchars($tech['name']); ?>" class="tech-icon">
                                <?php endif; ?>
                                <span class="tech-name"><?php echo safeHtmlspecialchars($tech['name']); ?></span>
                                <?php if (isset($tech['version'])): ?>
                                <span class="tech-version"><?php echo safeHtmlspecialchars($tech['version']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
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
                        <h3><?php echo safeHtmlspecialchars($service); ?></h3>
                    </div>
                    <div class="cloud-card-body">
                        <p class="confidence-level">
                            Confidenza: <strong><?php echo isset($details['confidence']) ? $details['confidence'] : 0; ?>%</strong>
                        </p>
                        <?php if (isset($details['indicators']) && !empty($details['indicators'])): ?>
                        <div class="indicators">
                            <p class="indicators-label">Indicatori trovati:</p>
                            <ul class="indicators-list">
                                <?php foreach ($details['indicators'] as $indicator): ?>
                                <li><?php echo safeHtmlspecialchars($indicator); ?></li>
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
                <p>Provider Email Rilevato: <strong><?php echo safeHtmlspecialchars($email_config['email_provider']); ?></strong></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($email_config['recommendations'])): ?>
            <div class="email-recommendations">
                <h3>Raccomandazioni</h3>
                <ul>
                    <?php foreach ($email_config['recommendations'] as $rec): ?>
                    <li><?php echo safeHtmlspecialchars($rec); ?></li>
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
                            <span class="issue-ip"><?php echo safeHtmlspecialchars($ip_data['ip']); ?></span>
                            <span class="issue-source"><?php echo safeHtmlspecialchars($ip_data['source']); ?></span>
                        </div>
                        <div class="issue-blacklists">
                            <?php foreach ($ip_data['blacklists'] as $bl): ?>
                            <span class="blacklist-badge"><?php echo safeHtmlspecialchars($bl); ?></span>
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
                <h2 class="security-title">Analisi Sicurezza DNS</h2>
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
                    <li><?php echo safeHtmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Performance Analysis -->
        <?php if ($performance_data): ?>
        <section class="performance-section" data-aos="fade-up">
            <div class="performance-header">
                <span class="perf-icon">⚡</span>
                <h2 class="perf-title">Analisi Performance</h2>
            </div>
            
            <div class="performance-metrics">
                <?php if (isset($performance_data['dns_lookup'])): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $performance_data['dns_lookup']; ?>ms</div>
                    <div class="metric-label">DNS Lookup</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($performance_data['ttfb'])): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo $performance_data['ttfb']; ?>ms</div>
                    <div class="metric-label">Time to First Byte</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($performance_data['page_load'])): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo number_format($performance_data['page_load'] / 1000, 2); ?>s</div>
                    <div class="metric-label">Page Load Time</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($performance_data['page_size'])): ?>
                <div class="metric-card">
                    <div class="metric-value"><?php echo formatBytes($performance_data['page_size']); ?></div>
                    <div class="metric-label">Page Size</div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($performance_data['optimization'])): ?>
            <div class="performance-optimization">
                <h3>Ottimizzazioni Rilevate</h3>
                <div class="optimization-grid">
                    <div class="opt-item <?php echo $performance_data['optimization']['compression'] ? 'enabled' : 'disabled'; ?>">
                        <span><?php echo $performance_data['optimization']['compression'] ? '✅' : '❌'; ?></span>
                        Compressione <?php echo isset($performance_data['optimization']['compression_type']) ? $performance_data['optimization']['compression_type'] : 'Non attiva'; ?>
                    </div>
                    <div class="opt-item <?php echo $performance_data['optimization']['caching'] ? 'enabled' : 'disabled'; ?>">
                        <span><?php echo $performance_data['optimization']['caching'] ? '✅' : '❌'; ?></span>
                        Cache Headers
                    </div>
                    <div class="opt-item <?php echo $performance_data['optimization']['http2'] ? 'enabled' : 'disabled'; ?>">
                        <span><?php echo $performance_data['optimization']['http2'] ? '✅' : '❌'; ?></span>
                        HTTP/2 Support
                    </div>
                    <div class="opt-item <?php echo $performance_data['optimization']['cdn'] ? 'enabled' : 'disabled'; ?>">
                        <span><?php echo $performance_data['optimization']['cdn'] ? '✅' : '❌'; ?></span>
                        CDN Rilevato
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- SEO & Social Meta -->
        <?php if ($social_meta): ?>
        <section class="social-meta-section" data-aos="fade-up">
            <div class="social-header">
                <span class="social-icon">📱</span>
                <h2 class="social-title">Meta Tag Social & SEO</h2>
            </div>
            
            <?php if (isset($social_meta['basic']) && is_array($social_meta['basic'])): ?>
            <div class="meta-group">
                <h3>Meta Tag Base</h3>
                <div class="meta-items">
                    <?php foreach ($social_meta['basic'] as $name => $content): ?>
                    <?php if (is_string($content)): ?>
                    <div class="meta-item">
                        <span class="meta-name"><?php echo safeHtmlspecialchars($name); ?>:</span>
                        <span class="meta-content"><?php echo safeHtmlspecialchars($content); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($social_meta['opengraph']) && !empty($social_meta['opengraph']) && is_array($social_meta['opengraph'])): ?>
            <div class="meta-group">
                <h3>Open Graph</h3>
                <div class="meta-items">
                    <?php foreach ($social_meta['opengraph'] as $property => $content): ?>
                    <?php if (is_string($content)): ?>
                    <div class="meta-item">
                        <span class="meta-name"><?php echo safeHtmlspecialchars($property); ?>:</span>
                        <span class="meta-content"><?php echo safeHtmlspecialchars($content); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($social_meta['twitter']) && !empty($social_meta['twitter']) && is_array($social_meta['twitter'])): ?>
            <div class="meta-group">
                <h3>Twitter Card</h3>
                <div class="meta-items">
                    <?php foreach ($social_meta['twitter'] as $name => $content): ?>
                    <?php if (is_string($content)): ?>
                    <div class="meta-item">
                        <span class="meta-name"><?php echo safeHtmlspecialchars($name); ?>:</span>
                        <span class="meta-content"><?php echo safeHtmlspecialchars($content); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Robots.txt & Sitemap -->
        <?php if ($robots_analysis || $sitemap_analysis): ?>
        <section class="robots-sitemap-section" data-aos="fade-up">
            <div class="robots-sitemap-header">
                <span class="robots-icon">🤖</span>
                <h2 class="robots-title">Robots.txt & Sitemap</h2>
            </div>
            
            <div class="robots-sitemap-grid">
                <?php if ($robots_analysis): ?>
                <div class="robots-card">
                    <h3>Robots.txt</h3>
                    <div class="robots-status <?php echo $robots_analysis['found'] ? 'found' : 'not-found'; ?>">
                        <?php echo $robots_analysis['found'] ? '✅ Trovato' : '❌ Non trovato'; ?>
                    </div>
                    <?php if ($robots_analysis['found']): ?>
                    <div class="robots-details">
                        <p>User-agents: <?php echo count($robots_analysis['user_agents']); ?></p>
                        <p>Regole disallow: <?php echo count($robots_analysis['disallow']); ?></p>
                        <p>Regole allow: <?php echo count($robots_analysis['allow']); ?></p>
                        <?php if (!empty($robots_analysis['sitemap'])): ?>
                        <p>Sitemap dichiarate: <?php echo count($robots_analysis['sitemap']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($sitemap_analysis): ?>
                <div class="sitemap-card">
                    <h3>Sitemap XML</h3>
                    <div class="sitemap-status <?php echo $sitemap_analysis['found'] ? 'found' : 'not-found'; ?>">
                        <?php echo $sitemap_analysis['found'] ? '✅ Trovata' : '❌ Non trovata'; ?>
                    </div>
                    <?php if ($sitemap_analysis['found']): ?>
                    <div class="sitemap-details">
                        <p>URL totali: <?php echo $sitemap_analysis['url_count']; ?></p>
                        <p>Formato: <?php echo strtoupper($sitemap_analysis['format']); ?></p>
                        <?php if ($sitemap_analysis['last_modified']): ?>
                        <p>Ultimo aggiornamento: <?php echo $sitemap_analysis['last_modified']; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($sitemap_analysis['index_files'])): ?>
                        <p>Sitemap index: <?php echo count($sitemap_analysis['index_files']); ?> file</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Redirect Chain Analysis -->
        <?php if ($redirect_analysis && isset($redirect_analysis['chain'])): ?>
        <section class="redirect-section" data-aos="fade-up">
            <div class="redirect-header">
                <span class="redirect-icon">🔄</span>
                <h2 class="redirect-title">Analisi Catena di Redirect</h2>
            </div>
            
            <div class="redirect-overview">
                <div class="redirect-stats">
                    <div class="redirect-stat">
                        <span class="stat-value"><?php echo $redirect_analysis['redirect_count']; ?></span>
                        <span class="stat-label">Redirect totali</span>
                    </div>
                    <div class="redirect-stat">
                        <span class="stat-value"><?php echo round($redirect_analysis['total_time'] / 1000, 2); ?>s</span>
                        <span class="stat-label">Tempo totale</span>
                    </div>
                    <div class="redirect-stat">
                        <span class="stat-value <?php echo $redirect_analysis['redirect_score'] >= 70 ? 'text-success' : 'text-warning'; ?>">
                            <?php echo $redirect_analysis['redirect_score']; ?>/100
                        </span>
                        <span class="stat-label">Score redirect</span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($redirect_analysis['chain'])): ?>
            <div class="redirect-chain">
                <h3>Catena di Redirect</h3>
                <div class="chain-steps">
                    <?php foreach ($redirect_analysis['chain'] as $index => $step): ?>
                    <div class="chain-step">
                        <div class="step-number"><?php echo $index + 1; ?></div>
                        <div class="step-details">
                            <div class="step-url"><?php echo safeHtmlspecialchars($step['url']); ?></div>
                            <div class="step-info">
                                <span class="status-code status-<?php echo substr($step['status_code'], 0, 1); ?>xx">
                                    <?php echo $step['status_code']; ?> <?php echo safeHtmlspecialchars($step['status_text']); ?>
                                </span>
                                <span class="response-time"><?php echo $step['response_time']; ?>ms</span>
                                <?php if ($step['redirect_type'] != 'Unknown'): ?>
                                <span class="redirect-type"><?php echo safeHtmlspecialchars($step['redirect_type']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($redirect_analysis['issues'])): ?>
            <div class="redirect-issues">
                <h3>Problemi Rilevati</h3>
                <div class="issues-list">
                    <?php foreach ($redirect_analysis['issues'] as $issue): ?>
                    <div class="issue-item issue-<?php echo $issue['severity']; ?>">
                        <div class="issue-icon">
                            <?php 
                            switch($issue['severity']) {
                                case 'critical': echo '🚨'; break;
                                case 'high': echo '⚠️'; break;
                                case 'medium': echo '⚡'; break;
                                default: echo 'ℹ️';
                            }
                            ?>
                        </div>
                        <div class="issue-content">
                            <h4><?php echo safeHtmlspecialchars($issue['message']); ?></h4>
                            <?php if (isset($issue['impact'])): ?>
                            <p><?php echo safeHtmlspecialchars($issue['impact']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($redirect_analysis['recommendations'])): ?>
            <div class="redirect-recommendations">
                <h3>Raccomandazioni</h3>
                <div class="recommendations-grid">
                    <?php foreach ($redirect_analysis['recommendations'] as $rec): ?>
                    <div class="recommendation-card">
                        <div class="rec-priority priority-<?php echo $rec['priority']; ?>">
                            <?php echo ucfirst($rec['priority']); ?>
                        </div>
                        <h4><?php echo safeHtmlspecialchars($rec['title']); ?></h4>
                        <p><?php echo safeHtmlspecialchars($rec['description']); ?></p>
                        <?php if (isset($rec['solution'])): ?>
                        <div class="rec-solution">
                            <strong>Soluzione:</strong> <?php echo safeHtmlspecialchars($rec['solution']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        
        <!-- Port Scan Results -->
        <?php if ($port_scan_results && !empty($port_scan_results['ports'])): ?>
        <section class="ports-section" data-aos="fade-up">
            <div class="ports-header">
                <span class="ports-icon">🔌</span>
                <h2 class="ports-title">Scansione Porte</h2>
            </div>
            
            <div class="ports-summary">
                <p>IP scansionato: <strong><?php echo safeHtmlspecialchars($port_scan_results['ip']); ?></strong></p>
                <p>Porte aperte: <strong><?php echo $port_scan_results['open_count']; ?></strong> su <?php echo $port_scan_results['total_scanned']; ?> scansionate</p>
            </div>
            
            <div class="ports-grid">
                <?php foreach ($port_scan_results['ports'] as $port => $info): ?>
                <?php if ($info['status'] === 'open'): ?>
                <div class="port-item open">
                    <div class="port-number"><?php echo $port; ?></div>
                    <div class="port-service"><?php echo safeHtmlspecialchars($info['service']); ?></div>
                    <div class="port-status">Aperta</div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section id="features" class="features-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Funzionalità Complete</h2>
            <p class="section-subtitle">Analisi professionale con oltre 30 controlli automatici</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">🔍</div>
                <h3>Analisi DNS Completa</h3>
                <p>Recupera tutti i record DNS inclusi A, AAAA, MX, TXT, NS, CNAME, SOA, SRV e CAA con informazioni dettagliate.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">👤</div>
                <h3>Informazioni WHOIS</h3>
                <p>Scopri il proprietario del dominio, data di registrazione, scadenza, registrar e stato del dominio.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">☁️</div>
                <h3>Rilevamento Cloud</h3>
                <p>Identifica automaticamente i servizi cloud utilizzati come Microsoft 365, Google Workspace, Cloudflare e altri.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">🚫</div>
                <h3>Controllo Blacklist</h3>
                <p>Verifica se il dominio o i suoi IP sono presenti in blacklist spam con controllo su oltre 50 database.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="feature-icon">📧</div>
                <h3>Analisi Email</h3>
                <p>Controlla la configurazione email con verifica SPF, DKIM, DMARC e identificazione del provider email.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="feature-icon">🔒</div>
                <h3>SSL & Sicurezza</h3>
                <p>Analizza certificato SSL, security headers, DNSSEC, CAA records e DANE/TLSA per una protezione ottimale.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="700">
                <div class="feature-icon">🔧</div>
                <h3>Tecnologie & CMS</h3>
                <p>Rileva automaticamente CMS, framework, linguaggi di programmazione e tecnologie utilizzate.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="800">
                <div class="feature-icon">⚡</div>
                <h3>Performance & SEO</h3>
                <p>Analisi performance, controllo robots.txt, sitemap, meta tag social e ottimizzazioni SEO.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="900">
                <div class="feature-icon">🔄</div>
                <h3>Redirect & Porte</h3>
                <p>Traccia catene di redirect, analizza HTTP headers e scansione porte opzionale.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="how-it-works-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Come Funziona</h2>
            <p class="section-subtitle">Un processo semplice e veloce in 3 passaggi</p>
        </div>
        
        <div class="steps-container">
            <div class="step-card" data-aos="fade-right" data-aos-delay="100">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Inserisci il Dominio</h3>
                    <p>Digita il nome del dominio che vuoi analizzare nel campo di ricerca. Supportiamo tutti i TLD e sottodomini.</p>
                </div>
            </div>
            
            <div class="step-connector" data-aos="fade-up" data-aos-delay="200">
                <div class="connector-line"></div>
            </div>
            
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Analisi Automatica</h3>
                    <p>Il sistema esegue automaticamente tutti i controlli: DNS, WHOIS, blacklist, SSL, performance e sicurezza.</p>
                </div>
            </div>
            
            <div class="step-connector" data-aos="fade-up" data-aos-delay="400">
                <div class="connector-line"></div>
            </div>
            
            <div class="step-card" data-aos="fade-left" data-aos-delay="500">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Report Completo</h3>
                    <p>Ricevi un report dettagliato con tutte le informazioni, raccomandazioni e un punteggio di salute del dominio.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="faq-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Domande Frequenti</h2>
            <p class="section-subtitle">Risposte alle domande più comuni sul nostro servizio</p>
        </div>
        
        <div class="faq-container">
            <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Cos'è l'analisi DNS e perché è importante?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>L'analisi DNS (Domain Name System) permette di verificare tutti i record associati a un dominio. È importante perché ti consente di controllare la configurazione del tuo sito web, email, sicurezza e identificare eventuali problemi di configurazione che potrebbero impattare la raggiungibilità o la sicurezza del tuo dominio.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Il servizio è gratuito?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sì, il servizio di analisi base è completamente gratuito. Puoi analizzare qualsiasi dominio senza registrazione o costi nascosti. Per utenti con esigenze avanzate, offriamo API professionali con limiti di utilizzo più elevati.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Quali informazioni posso ottenere dal WHOIS?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Il WHOIS fornisce informazioni sul proprietario del dominio (se non protette da privacy), date di registrazione e scadenza, registrar utilizzato, nameserver configurati e stato del dominio. Queste informazioni sono utili per verificare la proprietà e monitorare le scadenze.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Come funziona il controllo blacklist?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Il controllo blacklist verifica se gli IP associati al dominio sono presenti in liste di spam o blacklist pubbliche. Controlliamo oltre 50 database diversi per assicurarci che il tuo dominio non sia stato segnalato, cosa che potrebbe impattare la deliverability delle email o la reputazione online.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="500">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Posso esportare i risultati dell'analisi?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sì, puoi esportare tutti i dati DNS in formato JSON o copiarli negli appunti per utilizzarli in altri strumenti. I risultati completi possono essere salvati per riferimento futuro o condivisi con il tuo team tecnico.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="600">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Quanto spesso dovrei controllare il mio dominio?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Consigliamo di controllare il tuo dominio almeno una volta al mese per monitorare eventuali cambiamenti non autorizzati, verificare le scadenze e assicurarsi che non ci siano problemi di blacklist. Per domini critici, un controllo settimanale può essere più appropriato.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="700">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Quali tecnologie riuscite a rilevare?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Il nostro sistema rileva CMS (WordPress, Joomla, Drupal, etc.), e-commerce (WooCommerce, Magento, Shopify), framework (Laravel, React, Angular), server web (Apache, Nginx, IIS), linguaggi di programmazione, CDN, analytics e molte altre tecnologie.</p>
                </div>
            </div>
            
            <div class="faq-item" data-aos="fade-up" data-aos-delay="800">
                <button class="faq-question" onclick="toggleFaq(this)">
                    <span>Come posso migliorare il punteggio di salute del mio dominio?</span>
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-answer">
                    <p>Per migliorare il punteggio: configura correttamente SPF, DKIM e DMARC per le email, attiva DNSSEC, usa un certificato SSL valido, implementa security headers, mantieni il dominio fuori dalle blacklist, rinnova il dominio con anticipo e ottimizza le performance DNS.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content" data-aos="zoom-in">
            <h2>Pronto per analizzare il tuo dominio?</h2>
            <p>Inizia subito con un'analisi completa e gratuita</p>
            <a href="#domain-check" class="btn btn-cta">Analizza Ora</a>
        </div>
    </div>
</section>

<!-- Progress indicator durante l'analisi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('domainForm');
    const analyzeBtn = document.getElementById('analyzeBtn');
    
    // Example domain buttons
    document.querySelectorAll('.example-link').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('domain').value = this.dataset.domain;
        });
    });
    
    // Form submission
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
        }, 60000); // 60 secondi di timeout
    });
    
    // FAQ toggle
    window.toggleFaq = function(button) {
        const faqItem = button.parentElement;
        const answer = faqItem.querySelector('.faq-answer');
        const icon = button.querySelector('.faq-icon');
        
        if (faqItem.classList.contains('active')) {
            faqItem.classList.remove('active');
            answer.style.maxHeight = null;
            icon.textContent = '+';
        } else {
            // Close other FAQs
            document.querySelectorAll('.faq-item.active').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.faq-answer').style.maxHeight = null;
                item.querySelector('.faq-icon').textContent = '+';
            });
            
            faqItem.classList.add('active');
            answer.style.maxHeight = answer.scrollHeight + 'px';
            icon.textContent = '−';
        }
    };
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // AGGIUNTO: Scroll automatico ai risultati quando l'analisi è completata
    <?php if ($analysis_completed && $dns_results): ?>
    // Aspetta che la pagina sia completamente caricata
    window.addEventListener('load', function() {
        // Piccolo delay per assicurarsi che tutti gli elementi siano renderizzati
        setTimeout(function() {
            const resultsSection = document.getElementById('results');
            if (resultsSection) {
                resultsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 300);
    });
    <?php endif; ?>
    
    // Export DNS data
    window.exportDNS = function() {
        const dnsData = <?php echo json_encode($dns_results); ?>;
        const dataStr = JSON.stringify(dnsData, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = 'dns-records-<?php echo $domain; ?>.json';
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    };
    
    // Copy all DNS records
    window.copyAllDNS = function() {
        const dnsData = <?php echo json_encode($dns_results); ?>;
        const dataStr = JSON.stringify(dnsData, null, 2);
        
        navigator.clipboard.writeText(dataStr).then(function() {
            showNotification('Record DNS copiati negli appunti!', 'success');
        }, function(err) {
            showNotification('Errore durante la copia', 'error');
        });
    };
    
    // Notification helper
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});
</script>

<style>
/* Additional styles for new sections */
.features-section,
.how-it-works-section,
.faq-section,
.cta-section {
    padding: 80px 0;
}

.features-section {
    background: var(--gray-50);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.feature-card {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    text-align: center;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--secondary);
}

.feature-card p {
    color: var(--gray-600);
    line-height: 1.6;
    margin-bottom: 20px;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.feature-list li {
    padding: 5px 0;
    color: var(--gray-600);
    position: relative;
    padding-left: 20px;
}

.feature-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--success);
}

/* How it works */
.steps-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 60px;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}

.step-card {
    flex: 1;
    text-align: center;
    position: relative;
}

.step-number {
    width: 80px;
    height: 80px;
    background: var(--primary-gradient);
    color: white;
    font-size: 2rem;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    box-shadow: var(--shadow-md);
}

.step-content h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--secondary);
}

.step-content p {
    color: var(--gray-600);
    max-width: 250px;
    margin: 0 auto;
}

.step-connector {
    flex: 0 0 100px;
    position: relative;
}

.connector-line {
    position: absolute;
    top: 40px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary);
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 10px,
        var(--primary) 10px,
        var(--primary) 20px
    );
}

/* FAQ */
.faq-container {
    max-width: 800px;
    margin: 50px auto 0;
}

.faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item.active {
    box-shadow: var(--shadow-md);
}

.faq-question {
    width: 100%;
    padding: 25px 30px;
    background: transparent;
    border: none;
    text-align: left;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--secondary);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: var(--gray-50);
}

.faq-icon {
    font-size: 1.5rem;
    transition: transform 0.3s ease;
}

.faq-item.active .faq-icon {
    transform: rotate(45deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-answer p {
    padding: 0 30px 25px;
    color: var(--gray-600);
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    background: var(--primary-gradient);
    color: white;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.btn-cta {
    background: white;
    color: var(--primary);
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    display: inline-block;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-md);
}

.btn-cta:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Advanced options */
.advanced-options {
    margin-top: 15px;
}

.checkbox-label {
    display: inline-flex;
    align-items: center;
    font-size: 0.9rem;
    color: var(--gray-600);
    cursor: pointer;
}

.checkbox-label input {
    margin-right: 8px;
}

/* New sections styles */
.ssl-section,
.security-headers-section,
.technologies-section,
.performance-section,
.social-meta-section,
.robots-sitemap-section,
.ports-section,
.redirect-section,
.security-section {
    margin-top: 60px;
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

/* SSL styles */
.ssl-status {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.ssl-valid {
    background: var(--success-light);
    color: var(--success-dark);
}

.ssl-invalid {
    background: var(--error-light);
    color: var(--error-dark);
}

.ssl-status-icon {
    font-size: 48px;
    margin-right: 20px;
}

.ssl-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.ssl-detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 5px;
}

.detail-value {
    color: var(--gray-800);
}

/* Security headers styles */
.security-score {
    display: inline-block;
    margin-left: auto;
}

.score-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.score-excellent { background: var(--success-light); color: var(--success-dark); }
.score-good { background: var(--info-light); color: var(--info-dark); }
.score-fair { background: var(--warning-light); color: var(--warning-dark); }
.score-poor { background: var(--error-light); color: var(--error-dark); }

.headers-grid {
    display: grid;
    gap: 20px;
    margin-top: 30px;
}

.header-item {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    border-radius: 12px;
    background: var(--gray-50);
}

.header-present {
    background: var(--success-light);
}

.header-missing {
    background: var(--error-light);
}

.header-status {
    font-size: 24px;
    margin-right: 15px;
}

.header-info h4 {
    margin: 0 0 10px 0;
    color: var(--gray-800);
}

.header-info p {
    margin: 0 0 10px 0;
    color: var(--gray-600);
}

.header-info code {
    display: block;
    padding: 10px;
    background: rgba(0,0,0,0.05);
    border-radius: 6px;
    font-size: 0.85rem;
    overflow-x: auto;
}

/* Technologies styles */
.technologies-grid {
    display: grid;
    gap: 30px;
    margin-top: 30px;
}

.tech-category h3 {
    margin-bottom: 15px;
    color: var(--gray-700);
}

.tech-items {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.tech-item {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    background: var(--gray-100);
    border-radius: 25px;
    font-size: 0.95rem;
}

.tech-item img {
    width: 24px;
    height: 24px;
    margin-right: 10px;
}

.tech-version {
    margin-left: 5px;
    opacity: 0.7;
}

/* Performance styles */
.performance-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.metric-card {
    text-align: center;
    padding: 30px;
    background: var(--gray-50);
    border-radius: 12px;
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 10px;
}

.metric-label {
    color: var(--gray-600);
    font-size: 0.95rem;
}

.optimization-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.opt-item {
    padding: 15px;
    border-radius: 8px;
    background: var(--gray-50);
}

.opt-item.enabled {
    background: var(--success-light);
}

.opt-item.disabled {
    background: var(--error-light);
}

/* Social meta styles */
.meta-group {
    margin-bottom: 30px;
}

.meta-group h3 {
    margin-bottom: 15px;
    color: var(--gray-700);
}

.meta-items {
    display: grid;
    gap: 15px;
}

.meta-item {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 15px;
    padding: 15px;
    background: var(--gray-50);
    border-radius: 8px;
}

.meta-name {
    font-weight: 600;
    color: var(--gray-700);
}

.meta-content {
    color: var(--gray-600);
    word-break: break-word;
}

/* Robots/Sitemap styles */
.robots-sitemap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.robots-card,
.sitemap-card {
    padding: 30px;
    background: var(--gray-50);
    border-radius: 12px;
}

.robots-status,
.sitemap-status {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 20px 0;
}

.robots-status.found,
.sitemap-status.found {
    color: var(--success);
}

.robots-status.not-found,
.sitemap-status.not-found {
    color: var(--error);
}

.robots-details,
.sitemap-details {
    margin-top: 20px;
}

.robots-details p,
.sitemap-details p {
    margin: 10px 0;
    color: var(--gray-700);
}

/* Redirect styles */
.redirect-section {
    background: white;
}

.redirect-overview {
    margin: 30px 0;
}

.redirect-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.redirect-stat {
    text-align: center;
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
}

.redirect-stat .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    display: block;
    margin-bottom: 5px;
}

.redirect-stat .stat-label {
    color: var(--gray-600);
    font-size: 0.9rem;
}

.redirect-chain {
    margin-top: 40px;
}

.chain-steps {
    margin-top: 20px;
}

.chain-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
}

.chain-step .step-number {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 20px;
    flex-shrink: 0;
}

.step-details {
    flex: 1;
}

.step-url {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 10px;
    word-break: break-all;
}

.step-info {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.status-code {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-2xx { background: var(--success-light); color: var(--success-dark); }
.status-3xx { background: var(--info-light); color: var(--info-dark); }
.status-4xx { background: var(--warning-light); color: var(--warning-dark); }
.status-5xx { background: var(--error-light); color: var(--error-dark); }

.response-time,
.redirect-type {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.redirect-issues,
.redirect-recommendations {
    margin-top: 40px;
}

.issues-list {
    margin-top: 20px;
}

.issue-item {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
}

.issue-critical { background: var(--error-light); }
.issue-high { background: var(--warning-light); }
.issue-medium { background: var(--info-light); }
.issue-low { background: var(--gray-100); }

.issue-icon {
    font-size: 24px;
    margin-right: 15px;
}

.issue-content h4 {
    margin: 0 0 5px 0;
    color: var(--gray-800);
}

.issue-content p {
    margin: 0;
    color: var(--gray-600);
}

.recommendations-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.recommendation-card {
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
}

.rec-priority {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.priority-high { background: var(--error-light); color: var(--error-dark); }
.priority-medium { background: var(--warning-light); color: var(--warning-dark); }
.priority-low { background: var(--info-light); color: var(--info-dark); }

.recommendation-card h4 {
    margin: 10px 0;
    color: var(--gray-800);
}

.recommendation-card p {
    color: var(--gray-600);
    margin-bottom: 10px;
}

.rec-solution {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--gray-200);
    color: var(--gray-700);
}

/* Port scan styles */
.ports-summary {
    margin: 20px 0;
    padding: 20px;
    background: var(--gray-50);
    border-radius: 12px;
}

.ports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 30px;
}

.port-item {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    background: var(--gray-100);
}

.port-item.open {
    background: var(--warning-light);
}

.port-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 5px;
}

.port-service {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 5px;
}

.port-status {
    font-size: 0.9rem;
    color: var(--gray-600);
}

/* Notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    background: white;
    box-shadow: var(--shadow-lg);
    transform: translateX(400px);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    background: var(--success);
    color: white;
}

.notification-error {
    background: var(--error);
    color: white;
}

.notification-info {
    background: var(--info);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .steps-container {
        flex-direction: column;
        gap: 40px;
    }
    
    .step-connector {
        display: none;
    }
    
    .step-card {
        max-width: 300px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .whois-grid {
        grid-template-columns: 1fr;
    }
    
    .cloud-grid {
        grid-template-columns: 1fr;
    }
    
    .headers-grid {
        grid-template-columns: 1fr;
    }
    
    .performance-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .meta-item {
        grid-template-columns: 1fr;
    }
}

/* Print styles */
@media print {
    .dns-actions,
    .domain-actions,
    .form-section,
    .features-section,
    .how-it-works-section,
    .faq-section,
    .cta-section,
    header,
    footer {
        display: none !important;
    }
    
    .results-section {
        padding: 0;
    }
    
    .health-score-circle svg {
        width: 150px;
        height: 150px;
    }
}
</style>

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

function getScoreClass($score) {
    if ($score >= 90) return 'score-excellent';
    if ($score >= 70) return 'score-good';
    if ($score >= 50) return 'score-fair';
    return 'score-poor';
}

function calculateDomainHealth($data) {
    $score = 100;
    $issues = array();
    
    // Penalità per tempo di risposta
    if ($data['response_time'] > 1000) {
        $score -= 10;
        $issues[] = 'Tempo di risposta DNS elevato';
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
    
    // Penalità per SSL
    if (isset($data['ssl']) && isset($data['ssl']['valid'])) {
        if (!$data['ssl']['valid']) {
            $score -= 20;
            $issues[] = 'Certificato SSL non valido';
        } elseif (isset($data['ssl']['days_until_expiry']) && $data['ssl']['days_until_expiry'] < 30) {
            $score -= 10;
            $issues[] = 'Certificato SSL in scadenza';
        }
    }
    
    // Penalità per security headers
    if (isset($data['headers']) && isset($data['headers']['score'])) {
        if ($data['headers']['score'] < 50) {
            $score -= 15;
            $issues[] = 'Security headers insufficienti';
        } elseif ($data['headers']['score'] < 80) {
            $score -= 5;
        }
    }
    
    // Penalità per performance
    if (isset($data['performance'])) {
        if (isset($data['performance']['ttfb']) && $data['performance']['ttfb'] > 1000) {
            $score -= 5;
            $issues[] = 'Performance lenta';
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
