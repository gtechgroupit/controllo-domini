<?php
/**
 * Controllo Blacklist - Verifica reputazione IP e dominio
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

if (file_exists(ABSPATH . 'includes/blacklist-functions.php')) {
    require_once ABSPATH . 'includes/blacklist-functions.php';
}

// Imposta pagina corrente per il menu
$current_page = 'blacklist-check';

// Meta tags specifici per questa pagina
$page_title = "Controllo Blacklist - Verifica Reputazione | " . (defined('APP_NAME') ? APP_NAME : 'Controllo Domini');
$page_description = "Controlla se il tuo dominio o IP è in blacklist. Verifica gratuita su oltre 50 database di spam e blacklist per proteggere la tua reputazione online.";
$canonical_url = (defined('APP_URL') ? APP_URL : 'https://controllodomini.it') . "/blacklist-check";

// Resto del codice...

// Variabili per il form
$domain = '';
$blacklist_results = null;
$error_message = '';
$analysis_completed = false;

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    
    // Validazione dominio o IP
    $is_ip = filter_var($domain, FILTER_VALIDATE_IP);
    $validated_domain = $is_ip ? $domain : validateDomain($domain);
    
    if (!$validated_domain) {
        $error_message = 'Inserisci un nome di dominio o indirizzo IP valido';
    } else {
        $domain = $validated_domain;
        
        // Rate limiting
        $user_ip = getVisitorIP();
        if (!checkRateLimit($user_ip)) {
            $error_message = 'Hai raggiunto il limite di richieste. Riprova tra qualche minuto.';
        } else {
            try {
                // Controlla blacklist
                $blacklist_results = checkBlacklists($domain);
                
                if (!$blacklist_results) {
                    $error_message = 'Errore durante il controllo delle blacklist.';
                } else {
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
                Controllo Blacklist
                <span class="hero-gradient">Verifica Reputazione</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Controlla se il tuo dominio o IP è presente in blacklist di spam
            </p>
        </div>
    </div>
</section>

<!-- Blacklist Check Tool -->
<section class="tool-section">
    <div class="container">
        <!-- Form di ricerca -->
        <div class="search-box" data-aos="fade-up">
            <form method="POST" action="/blacklist-check" class="domain-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" 
                               name="domain" 
                               class="domain-input" 
                               placeholder="Inserisci dominio o indirizzo IP (es: esempio.com o 192.168.1.1)"
                               value="<?php echo htmlspecialchars($domain); ?>"
                               required>
                        <button type="submit" class="analyze-button">
                            <span class="button-text">Controlla Blacklist</span>
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

        <?php if ($analysis_completed && $blacklist_results): ?>
        <!-- Risultati Blacklist -->
        <div class="results-container" data-aos="fade-up">
            <div class="results-header">
                <h2>Risultati controllo blacklist per <?php echo htmlspecialchars($domain); ?></h2>
                <div class="results-actions">
                    <button onclick="copyBlacklistReport()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4 2a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H4zm0 1h8a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                        </svg>
                        Copia Report
                    </button>
                    <button onclick="downloadBlacklistCsv()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 12a.5.5 0 00.5-.5v-7a.5.5 0 00-1 0v7a.5.5 0 00.5.5z"/>
                            <path d="M8 13a.5.5 0 00.354-.146l3-3a.5.5 0 00-.708-.708L8 11.793 5.354 9.146a.5.5 0 00-.708.708l3 3A.5.5 0 008 13z"/>
                        </svg>
                        Scarica CSV
                    </button>
                </div>
            </div>

            <!-- Riepilogo reputazione -->
            <div class="reputation-summary">
                <?php 
                $reputation = $blacklist_results['reputation'];
                $status_class = '';
                $status_icon = '';
                
                if ($reputation['score'] >= 95) {
                    $status_class = 'excellent';
                    $status_icon = '✓';
                } elseif ($reputation['score'] >= 80) {
                    $status_class = 'good';
                    $status_icon = '✓';
                } elseif ($reputation['score'] >= 60) {
                    $status_class = 'warning';
                    $status_icon = '!';
                } else {
                    $status_class = 'critical';
                    $status_icon = '✗';
                }
                ?>
                <div class="reputation-card <?php echo $status_class; ?>">
                    <div class="reputation-score">
                        <div class="score-circle">
                            <svg class="progress-ring" width="120" height="120">
                                <circle class="progress-ring-circle" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"
                                        style="stroke-dasharray: <?php echo 326.73 * ($reputation['score'] / 100); ?> 326.73;"/>
                            </svg>
                            <div class="score-value">
                                <span class="score-number"><?php echo $reputation['score']; ?></span>
                                <span class="score-label">Score</span>
                            </div>
                        </div>
                    </div>
                    <div class="reputation-details">
                        <h3 class="reputation-status"><?php echo $reputation['status']; ?></h3>
                        <p class="reputation-message">
                            <?php
                            if ($blacklist_results['listed'] == 0) {
                                echo "Il tuo dominio/IP non è presente in nessuna blacklist!";
                            } elseif ($blacklist_results['listed'] == 1) {
                                echo "Il tuo dominio/IP è presente in 1 blacklist.";
                            } else {
                                echo "Il tuo dominio/IP è presente in " . $blacklist_results['listed'] . " blacklist.";
                            }
                            ?>
                        </p>
                        <div class="reputation-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo $blacklist_results['checked']; ?></span>
                                <span class="stat-label">Blacklist controllate</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value <?php echo $blacklist_results['listed'] > 0 ? 'text-error' : 'text-success'; ?>">
                                    <?php echo $blacklist_results['listed']; ?>
                                </span>
                                <span class="stat-label">Presenze rilevate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IP controllati -->
            <?php if (!empty($blacklist_results['ips_checked'])): ?>
            <div class="checked-ips">
                <h3>IP Controllati</h3>
                <div class="ip-list">
                    <?php foreach ($blacklist_results['ips_checked'] as $ip): ?>
                    <div class="ip-item">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 0a8 8 0 100 16A8 8 0 008 0zM4.5 7.5a.5.5 0 000 1h5.793l-2.147 2.146a.5.5 0 00.708.708l3-3a.5.5 0 000-.708l-3-3a.5.5 0 10-.708.708L10.293 7.5H4.5z"/>
                        </svg>
                        <?php echo htmlspecialchars($ip); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Blacklist dove è presente -->
            <?php if (!empty($blacklist_results['issues'])): ?>
            <div class="blacklist-issues">
                <h3 class="issues-title">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Presente nelle seguenti blacklist
                </h3>
                <div class="issues-list">
                    <?php foreach ($blacklist_results['issues'] as $issue): ?>
                    <div class="issue-item">
                        <div class="issue-header">
                            <span class="issue-name"><?php echo htmlspecialchars($issue['blacklist']); ?></span>
                            <span class="issue-ip"><?php echo htmlspecialchars($issue['ip']); ?></span>
                        </div>
                        <div class="issue-details">
                            <span class="issue-dnsbl">Database: <?php echo htmlspecialchars($issue['dnsbl']); ?></span>
                            <a href="https://mxtoolbox.com/SuperTool.aspx?action=blacklist%3a<?php echo urlencode($issue['ip']); ?>&run=toolpage" 
                               target="_blank" 
                               rel="noopener"
                               class="issue-action">
                                Verifica dettagli
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                    <path d="M10.5 1.5v3h-1v-2.293L4.854 6.854l-.708-.708L8.793 2.5H6.5v-1h4z"/>
                                    <path d="M10 10.5a.5.5 0 01-.5.5h-7a.5.5 0 01-.5-.5v-7a.5.5 0 01.5-.5H6v1H3v6h6V7h1v3.5z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Blacklist pulite -->
            <?php if (!empty($blacklist_results['clean'])): ?>
            <div class="blacklist-clean">
                <h3 class="clean-title">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Non presente in queste blacklist
                </h3>
                <div class="clean-grid">
                    <?php foreach ($blacklist_results['clean'] as $clean): ?>
                    <div class="clean-item">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                            <path d="M7 0a7 7 0 100 14A7 7 0 007 0zm3.5 5.5L6 10 3.5 7.5 4.914 6.086 6 7.172l3.086-3.086L10.5 5.5z"/>
                        </svg>
                        <?php echo htmlspecialchars($clean['blacklist']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Consigli per il delisting -->
            <?php if ($blacklist_results['listed'] > 0): ?>
            <div class="delisting-advice">
                <h3>Come rimuovere il tuo IP dalle blacklist</h3>
                <div class="advice-steps">
                    <div class="advice-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Identifica la causa</h4>
                            <p>Verifica se ci sono state attività sospette o invii massivi di email dal tuo server.</p>
                        </div>
                    </div>
                    <div class="advice-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Risolvi il problema</h4>
                            <p>Correggi eventuali configurazioni errate, rimuovi malware o blocca account compromessi.</p>
                        </div>
                    </div>
                    <div class="advice-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Richiedi la rimozione</h4>
                            <p>Contatta ogni blacklist provider e segui la loro procedura di delisting.</p>
                        </div>
                    </div>
                    <div class="advice-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Monitora regolarmente</h4>
                            <p>Controlla periodicamente lo stato delle blacklist per prevenire futuri problemi.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Informazioni aggiuntive -->
        <div class="info-section" data-aos="fade-up">
            <h2>Cosa sono le Blacklist?</h2>
            <p>Le blacklist (o blocklist) sono database che contengono indirizzi IP o domini segnalati per attività sospette, spam o comportamenti malevoli. Essere presenti in una blacklist può causare problemi nella consegna delle email e nella reputazione online.</p>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>Tipi di Blacklist</h3>
                    <p>Esistono blacklist per IP (DNSBL), domini (URIBL) e blacklist specifiche per email spam, malware o phishing.</p>
                </div>
                <div class="info-card">
                    <h3>Impatto sulle Email</h3>
                    <p>Gli IP in blacklist vedono le loro email bloccate o finite in spam. È cruciale mantenere una buona reputazione.</p>
                </div>
                <div class="info-card">
                    <h3>Cause Comuni</h3>
                    <p>Invio massivo di email, server compromessi, configurazioni errate o segnalazioni degli utenti.</p>
                </div>
                <div class="info-card">
                    <h3>Prevenzione</h3>
                    <p>Usa autenticazione email (SPF, DKIM, DMARC), monitora i volumi di invio e mantieni liste pulite.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Dati blacklist per JavaScript
const blacklistData = <?php echo json_encode($blacklist_results ?? []); ?>;
const domain = <?php echo json_encode($domain); ?>;

// Funzione per copiare il report
function copyBlacklistReport() {
    let text = `Blacklist Report for ${domain}\n`;
    text += `${'='.repeat(50)}\n\n`;
    text += `Reputation Score: ${blacklistData.reputation.score}/100 (${blacklistData.reputation.status})\n`;
    text += `Total Blacklists Checked: ${blacklistData.checked}\n`;
    text += `Listed in: ${blacklistData.listed} blacklists\n\n`;
    
    if (blacklistData.ips_checked && blacklistData.ips_checked.length > 0) {
        text += `IPs Checked:\n`;
        blacklistData.ips_checked.forEach(ip => {
            text += `  - ${ip}\n`;
        });
        text += '\n';
    }
    
    if (blacklistData.issues && blacklistData.issues.length > 0) {
        text += `BLACKLIST ISSUES:\n`;
        blacklistData.issues.forEach(issue => {
            text += `  • ${issue.blacklist} (${issue.dnsbl})\n`;
            text += `    IP: ${issue.ip}\n`;
        });
    } else {
        text += `No blacklist issues found!\n`;
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Report copiato negli appunti!', 'success');
    }).catch(() => {
        showNotification('Errore durante la copia', 'error');
    });
}

// Funzione per scaricare CSV
function downloadBlacklistCsv() {
    let csv = 'Blacklist,Status,IP,Database\n';
    
    // Aggiungi blacklist con problemi
    if (blacklistData.issues && blacklistData.issues.length > 0) {
        blacklistData.issues.forEach(issue => {
            csv += `"${issue.blacklist}","Listed","${issue.ip}","${issue.dnsbl}"\n`;
        });
    }
    
    // Aggiungi blacklist pulite
    if (blacklistData.clean && blacklistData.clean.length > 0) {
        blacklistData.clean.forEach(clean => {
            csv += `"${clean.blacklist}","Clean","${clean.ip || domain}",""\n`;
        });
    }
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `blacklist-report-${domain}-${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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

<?php include 'templates/footer.php'; ?>
