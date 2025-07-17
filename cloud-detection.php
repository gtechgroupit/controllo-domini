<?php
/**
 * Rilevamento Cloud - Identifica servizi cloud e CDN
 * 
 * @author G Tech Group
 * @version 4.0
 */

// Definisci ABSPATH se non esiste
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Includi file di configurazione e funzioni
if (file_exists(ABSPATH . 'config/config.php')) {
    require_once ABSPATH . 'config/config.php';
}

if (file_exists(ABSPATH . 'includes/functions.php')) {
    require_once ABSPATH . 'includes/functions.php';
}

if (file_exists(ABSPATH . 'includes/dns-functions.php')) {
    require_once ABSPATH . 'includes/dns-functions.php';
}

if (file_exists(ABSPATH . 'includes/cloud-functions.php')) {
    require_once ABSPATH . 'includes/cloud-functions.php';
}

// Imposta pagina corrente per il menu
$current_page = 'cloud-detection';

// Meta tags specifici per questa pagina
$page_title = "Rilevamento Servizi Cloud - Identifica Provider | " . (defined('APP_NAME') ? APP_NAME : 'Controllo Domini');
$page_description = "Identifica quali servizi cloud utilizza un dominio: Microsoft 365, Google Workspace, AWS, Cloudflare e oltre 50 altri provider cloud e CDN.";
$canonical_url = (defined('APP_URL') ? APP_URL : 'https://controllodomini.it') . "/cloud-detection";

// Resto del codice...

// Variabili per il form
$domain = '';
$cloud_results = null;
$dns_results = null;
$error_message = '';
$analysis_completed = false;

// Gestione del form
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
                // Recupera record DNS
                $dns_data = getAllDnsRecords($domain);
                $dns_results = $dns_data['records'];
                
                if (empty($dns_results)) {
                    $error_message = 'Nessun record DNS trovato per questo dominio.';
                } else {
                    // Identifica servizi cloud
                    $cloud_results = identifyCloudServices($dns_results, $domain);
                    $analysis_completed = true;
                }
            } catch (Exception $e) {
                $error_message = 'Errore durante l\'analisi: ' . $e->getMessage();
            }
        }
    }
}

// Includi header
include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-secondary">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-aos="fade-up">
                Rilevamento Cloud
                <span class="hero-gradient">Identifica Servizi Cloud</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Scopri quali servizi cloud e CDN utilizza un dominio
            </p>
        </div>
    </div>
</section>

<!-- Cloud Detection Tool -->
<section class="tool-section">
    <div class="container">
        <!-- Form di ricerca -->
        <div class="search-box" data-aos="fade-up">
            <form method="POST" action="/cloud-detection" class="domain-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" 
                               name="domain" 
                               class="domain-input" 
                               placeholder="Inserisci il dominio da analizzare (es: esempio.com)"
                               value="<?php echo htmlspecialchars($domain); ?>"
                               required
                               pattern="^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$"
                               title="Inserisci un nome di dominio valido">
                        <button type="submit" class="analyze-button">
                            <span class="button-text">Rileva Servizi Cloud</span>
                            <svg class="button-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-error" data-aos="fade-up">
            <svg class="alert-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($analysis_completed && $cloud_results): ?>
        <!-- Risultati Cloud -->
        <div class="results-container" data-aos="fade-up">
            <div class="results-header">
                <h2>Servizi Cloud rilevati per <?php echo htmlspecialchars($domain); ?></h2>
                <div class="results-actions">
                    <button onclick="copyCloudReport()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4 2a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H4zm0 1h8a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                        </svg>
                        Copia Report
                    </button>
                    <button onclick="exportCloudJson()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 12a.5.5 0 00.5-.5v-7a.5.5 0 00-1 0v7a.5.5 0 00.5.5z"/>
                            <path d="M8 13a.5.5 0 00.354-.146l3-3a.5.5 0 00-.708-.708L8 11.793 5.354 9.146a.5.5 0 00-.708.708l3 3A.5.5 0 008 13z"/>
                        </svg>
                        Esporta JSON
                    </button>
                </div>
            </div>

            <!-- Riepilogo servizi cloud -->
            <?php 
            $detected_count = is_array($cloud_results['detected']) ? count($cloud_results['detected']) : 0;
            ?>
            <div class="cloud-summary">
                <div class="summary-card">
                    <div class="summary-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor">
                            <path d="M38.71 20.07C37.35 13.19 31.28 8 24 8c-5.78 0-10.79 3.28-13.3 8.07C4.69 16.72 0 21.81 0 28c0 6.63 5.37 12 12 12h26c5.52 0 10-4.48 10-10 0-5.28-4.11-9.56-9.29-9.93z"/>
                        </svg>
                    </div>
                    <div class="summary-content">
                        <h3 class="summary-title">
                            <?php echo $detected_count; ?> Servizi Cloud Rilevati
                        </h3>
                        <p class="summary-description">
                            <?php 
                            if ($detected_count == 0) {
                                echo "Nessun servizio cloud principale rilevato per questo dominio.";
                            } elseif ($detected_count == 1) {
                                echo "Abbiamo identificato 1 servizio cloud utilizzato da questo dominio.";
                            } else {
                                echo "Abbiamo identificato $detected_count servizi cloud utilizzati da questo dominio.";
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Servizi cloud rilevati -->
            <?php if ($detected_count > 0): ?>
            <div class="cloud-services">
                <h3>Servizi Cloud Identificati</h3>
                <div class="services-grid">
                    <?php foreach ($cloud_results['detected'] as $service): ?>
                    <div class="service-card <?php echo strtolower(str_replace(' ', '-', $service['name'])); ?>">
                        <div class="service-header">
                            <div class="service-icon">
                                <?php echo getCloudServiceIcon($service['name']); ?>
                            </div>
                            <h4 class="service-name"><?php echo htmlspecialchars($service['name']); ?></h4>
                        </div>
                        <div class="service-details">
                            <p class="service-type"><?php echo htmlspecialchars($service['type']); ?></p>
                            <?php if (!empty($service['confidence'])): ?>
                            <div class="confidence-meter">
                                <span class="confidence-label">Confidenza:</span>
                                <div class="confidence-bar">
                                    <div class="confidence-fill" style="width: <?php echo $service['confidence']; ?>%"></div>
                                </div>
                                <span class="confidence-value"><?php echo $service['confidence']; ?>%</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($service['evidence'])): ?>
                            <div class="service-evidence">
                                <h5>Evidenze trovate:</h5>
                                <ul>
                                    <?php foreach ($service['evidence'] as $evidence): ?>
                                    <li><?php echo htmlspecialchars($evidence); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Email Services -->
            <?php if (!empty($cloud_results['email_services'])): ?>
            <div class="email-services">
                <h3>Servizi Email</h3>
                <div class="email-grid">
                    <?php foreach ($cloud_results['email_services'] as $email_service): ?>
                    <div class="email-card">
                        <div class="email-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <div class="email-content">
                            <h4><?php echo htmlspecialchars($email_service['provider']); ?></h4>
                            <p>MX Priority: <?php echo htmlspecialchars($email_service['priority']); ?></p>
                            <p class="email-server"><?php echo htmlspecialchars($email_service['mx_record']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CDN Detection -->
            <?php if (!empty($cloud_results['cdn_services'])): ?>
            <div class="cdn-services">
                <h3>Content Delivery Network (CDN)</h3>
                <div class="cdn-grid">
                    <?php foreach ($cloud_results['cdn_services'] as $cdn): ?>
                    <div class="cdn-card">
                        <div class="cdn-header">
                            <span class="cdn-icon"><?php echo getCdnIcon($cdn['name']); ?></span>
                            <h4><?php echo htmlspecialchars($cdn['name']); ?></h4>
                        </div>
                        <?php if (!empty($cdn['details'])): ?>
                        <div class="cdn-details">
                            <?php foreach ($cdn['details'] as $key => $value): ?>
                            <p><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hosting Provider -->
            <?php if (!empty($cloud_results['hosting'])): ?>
            <div class="hosting-info">
                <h3>Hosting Provider</h3>
                <div class="hosting-card">
                    <div class="hosting-icon">
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor">
                            <path d="M26 4H6a2 2 0 00-2 2v20a2 2 0 002 2h20a2 2 0 002-2V6a2 2 0 00-2-2zm-16 7a1 1 0 110-2 1 1 0 010 2zm4 0a1 1 0 110-2 1 1 0 010 2zm12 13H6v-8h20z"/>
                        </svg>
                    </div>
                    <div class="hosting-details">
                        <h4><?php echo htmlspecialchars($cloud_results['hosting']['provider']); ?></h4>
                        <?php if (!empty($cloud_results['hosting']['ip'])): ?>
                        <p><strong>IP:</strong> <?php echo htmlspecialchars($cloud_results['hosting']['ip']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($cloud_results['hosting']['location'])): ?>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($cloud_results['hosting']['location']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($cloud_results['hosting']['asn'])): ?>
                        <p><strong>ASN:</strong> <?php echo htmlspecialchars($cloud_results['hosting']['asn']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Security Services -->
            <?php if (!empty($cloud_results['security_services'])): ?>
            <div class="security-services">
                <h3>Servizi di Sicurezza</h3>
                <div class="security-grid">
                    <?php foreach ($cloud_results['security_services'] as $security): ?>
                    <div class="security-card">
                        <div class="security-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                            </svg>
                        </div>
                        <div class="security-content">
                            <h4><?php echo htmlspecialchars($security['name']); ?></h4>
                            <p><?php echo htmlspecialchars($security['type']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Configurazione rilevata -->
            <div class="configuration-details">
                <h3>Configurazione Rilevata</h3>
                <div class="config-tabs">
                    <button class="tab-button active" onclick="showTab('dns-config')">Record DNS</button>
                    <button class="tab-button" onclick="showTab('mail-config')">Configurazione Email</button>
                    <button class="tab-button" onclick="showTab('security-config')">Sicurezza</button>
                </div>
                
                <div id="dns-config" class="tab-content active">
                    <div class="config-list">
                        <?php if (!empty($cloud_results['dns_indicators'])): ?>
                        <?php foreach ($cloud_results['dns_indicators'] as $indicator): ?>
                        <div class="config-item">
                            <span class="config-type"><?php echo htmlspecialchars($indicator['type']); ?></span>
                            <span class="config-value"><?php echo htmlspecialchars($indicator['value']); ?></span>
                            <span class="config-service"><?php echo htmlspecialchars($indicator['indicates']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="mail-config" class="tab-content">
                    <div class="config-list">
                        <?php if (!empty($cloud_results['mail_configuration'])): ?>
                        <?php foreach ($cloud_results['mail_configuration'] as $mail_config): ?>
                        <div class="config-item">
                            <span class="config-type"><?php echo htmlspecialchars($mail_config['type']); ?></span>
                            <span class="config-value"><?php echo htmlspecialchars($mail_config['value']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="security-config" class="tab-content">
                    <div class="config-list">
                        <?php if (!empty($cloud_results['security_configuration'])): ?>
                        <?php foreach ($cloud_results['security_configuration'] as $sec_config): ?>
                        <div class="config-item">
                            <span class="config-type"><?php echo htmlspecialchars($sec_config['type']); ?></span>
                            <span class="config-value"><?php echo htmlspecialchars($sec_config['status']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informazioni aggiuntive -->
        <div class="info-section" data-aos="fade-up">
            <h2>Perché identificare i servizi cloud?</h2>
            <p>Conoscere quali servizi cloud utilizza un dominio può essere utile per vari scopi: analisi della concorrenza, due diligence aziendale, troubleshooting tecnico o semplicemente curiosità tecnologica.</p>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>Email Services</h3>
                    <p>Identifichiamo provider email come Microsoft 365, Google Workspace, Zoho Mail e altri servizi di posta aziendale.</p>
                </div>
                <div class="info-card">
                    <h3>CDN & Performance</h3>
                    <p>Rileviamo CDN come Cloudflare, Akamai, Fastly che migliorano le prestazioni e la sicurezza dei siti web.</p>
                </div>
                <div class="info-card">
                    <h3>Cloud Infrastructure</h3>
                    <p>Identifichiamo se un sito è ospitato su AWS, Azure, Google Cloud o altri provider di infrastruttura cloud.</p>
                </div>
                <div class="info-card">
                    <h3>Security Services</h3>
                    <p>Rileviamo servizi di sicurezza come WAF, DDoS protection e altri sistemi di protezione web.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Dati cloud per JavaScript
const cloudData = <?php echo json_encode($cloud_results ?? []); ?>;
const domain = <?php echo json_encode($domain); ?>;

// Funzione per cambiare tab
function showTab(tabId) {
    // Rimuovi active da tutti i tab
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Aggiungi active al tab selezionato
    event.target.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

// Funzione per copiare il report
function copyCloudReport() {
    let text = `Cloud Services Report for ${domain}\n`;
    text += `${'='.repeat(50)}\n\n`;
    
    if (cloudData.detected && cloudData.detected.length > 0) {
        text += `DETECTED SERVICES:\n`;
        cloudData.detected.forEach(service => {
            text += `\n• ${service.name} (${service.type})\n`;
            if (service.confidence) {
                text += `  Confidence: ${service.confidence}%\n`;
            }
            if (service.evidence && service.evidence.length > 0) {
                text += `  Evidence:\n`;
                service.evidence.forEach(ev => {
                    text += `    - ${ev}\n`;
                });
            }
        });
    }
    
    if (cloudData.email_services && cloudData.email_services.length > 0) {
        text += `\nEMAIL SERVICES:\n`;
        cloudData.email_services.forEach(email => {
            text += `  • ${email.provider}: ${email.mx_record} (Priority: ${email.priority})\n`;
        });
    }
    
    if (cloudData.cdn_services && cloudData.cdn_services.length > 0) {
        text += `\nCDN SERVICES:\n`;
        cloudData.cdn_services.forEach(cdn => {
            text += `  • ${cdn.name}\n`;
        });
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Report copiato negli appunti!', 'success');
    }).catch(() => {
        showNotification('Errore durante la copia', 'error');
    });
}

// Funzione per esportare JSON
function exportCloudJson() {
    const dataStr = JSON.stringify(cloudData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = `cloud-services-${domain}-${Date.now()}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}

// Funzione per mostrare notifiche
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<?php 
// Funzione helper per icone servizi cloud
function getCloudServiceIcon($serviceName) {
    $icons = [
        'Microsoft 365' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor"><path d="M17 6v14l10-2V8l-10-2zm-2 0L5 8v10l10 2V6z"/></svg>',
        'Google Workspace' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor"><path d="M16 8l-8 8h6v8h4v-8h6l-8-8z"/></svg>',
        'Cloudflare' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor"><path d="M22 12c0-1.1-.9-2-2-2H8c-.6 0-1 .4-1 1s.4 1 1 1h12v2c0 .6.4 1 1 1s1-.4 1-1v-2z"/></svg>',
        'AWS' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor"><path d="M13 10l3-4 3 4v12l-3 4-3-4V10z"/></svg>',
        'Default' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor"><circle cx="16" cy="16" r="8"/></svg>'
    ];
    
    return isset($icons[$serviceName]) ? $icons[$serviceName] : $icons['Default'];
}

// Funzione helper per icone CDN
function getCdnIcon($cdnName) {
    return '🌐'; // Può essere espanso con icone SVG specifiche per ogni CDN
}

include 'templates/footer.php'; 
?>
