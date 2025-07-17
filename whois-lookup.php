<?php
/**
 * Verifica WHOIS - Informazioni registrazione dominio
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

if (file_exists(ABSPATH . 'includes/whois-functions.php')) {
    require_once ABSPATH . 'includes/whois-functions.php';
}

// Imposta pagina corrente per il menu
$current_page = 'whois-lookup';

// Meta tags specifici per questa pagina
$page_title = "Verifica WHOIS - Informazioni Dominio | " . (defined('APP_NAME') ? APP_NAME : 'Controllo Domini');
$page_description = "Verifica WHOIS gratuita: scopri proprietario, data registrazione, scadenza e registrar di qualsiasi dominio. Informazioni complete sulla registrazione domini.";
$canonical_url = (defined('APP_URL') ? APP_URL : 'https://controllodomini.it') . "/whois-lookup";

// Resto del codice...

// Variabili per il form
$domain = '';
$whois_info = null;
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
                // Ottieni informazioni WHOIS
                $whois_info = getWhoisInfo($domain);
                
                if (!$whois_info || !$whois_info['success']) {
                    $error_message = 'Impossibile recuperare le informazioni WHOIS per questo dominio.';
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
                Verifica WHOIS
                <span class="hero-gradient">Informazioni Registrazione</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Scopri chi ha registrato un dominio e quando scade
            </p>
        </div>
    </div>
</section>

<!-- WHOIS Lookup Tool -->
<section class="tool-section">
    <div class="container">
        <!-- Form di ricerca -->
        <div class="search-box" data-aos="fade-up">
            <form method="POST" action="/whois-lookup" class="domain-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" 
                               name="domain" 
                               class="domain-input" 
                               placeholder="Inserisci il dominio da verificare (es: esempio.com)"
                               value="<?php echo htmlspecialchars($domain); ?>"
                               required
                               pattern="^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$"
                               title="Inserisci un nome di dominio valido">
                        <button type="submit" class="analyze-button">
                            <span class="button-text">Verifica WHOIS</span>
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

        <?php if ($analysis_completed && $whois_info && $whois_info['success']): ?>
        <!-- Risultati WHOIS -->
        <div class="results-container" data-aos="fade-up">
            <div class="results-header">
                <h2>Informazioni WHOIS per <?php echo htmlspecialchars($domain); ?></h2>
                <div class="results-actions">
                    <button onclick="copyWhoisInfo()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4 2a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H4zm0 1h8a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                        </svg>
                        Copia Tutto
                    </button>
                    <button onclick="exportWhoisPdf()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 12a.5.5 0 00.5-.5v-7a.5.5 0 00-1 0v7a.5.5 0 00.5.5z"/>
                            <path d="M8 13a.5.5 0 00.354-.146l3-3a.5.5 0 00-.708-.708L8 11.793 5.354 9.146a.5.5 0 00-.708.708l3 3A.5.5 0 008 13z"/>
                        </svg>
                        Scarica Report
                    </button>
                </div>
            </div>

            <!-- Informazioni principali -->
            <div class="whois-main-info">
                <div class="info-grid">
                    <!-- Stato dominio -->
                    <div class="info-box">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3>Stato Dominio</h3>
                            <p class="info-value <?php echo $whois_info['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $whois_info['status'] === 'active' ? 'Attivo' : 'Non Attivo'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Data registrazione -->
                    <div class="info-box">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3>Data Registrazione</h3>
                            <p class="info-value">
                                <?php 
                                if (!empty($whois_info['creation_date'])) {
                                    echo formatWhoisDate($whois_info['creation_date']);
                                } else {
                                    echo 'Non disponibile';
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Data scadenza -->
                    <div class="info-box">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3>Data Scadenza</h3>
                            <p class="info-value">
                                <?php 
                                if (!empty($whois_info['expiry_date'])) {
                                    $expiry_date = formatWhoisDate($whois_info['expiry_date']);
                                    $days_until_expiry = calculateDaysUntilExpiry($whois_info['expiry_date']);
                                    
                                    echo $expiry_date;
                                    if ($days_until_expiry !== false) {
                                        $expiry_class = 'expiry-ok';
                                        if ($days_until_expiry <= 30) {
                                            $expiry_class = 'expiry-urgent';
                                        } elseif ($days_until_expiry <= 90) {
                                            $expiry_class = 'expiry-warning';
                                        }
                                        echo '<span class="expiry-days ' . $expiry_class . '">(' . $days_until_expiry . ' giorni)</span>';
                                    }
                                } else {
                                    echo 'Non disponibile';
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Registrar -->
                    <div class="info-box">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10h5v-2h-5c-4.34 0-8-3.66-8-8s3.66-8 8-8 8 3.66 8 8v1.43c0 .79-.71 1.57-1.5 1.57s-1.5-.78-1.5-1.57V12c0-2.76-2.24-5-5-5s-5 2.24-5 5 2.24 5 5 5c1.38 0 2.64-.56 3.54-1.47.65.89 1.77 1.47 2.96 1.47 1.97 0 3.5-1.6 3.5-3.57V12c0-5.52-4.48-10-10-10zm0 13c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h3>Registrar</h3>
                            <p class="info-value">
                                <?php echo !empty($whois_info['registrar']) ? htmlspecialchars($whois_info['registrar']) : 'Non disponibile'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dettagli aggiuntivi -->
            <div class="whois-details">
                <h3>Dettagli Completi</h3>
                
                <!-- Intestatario -->
                <?php if (!empty($whois_info['registrant_name']) || !empty($whois_info['registrant_organization'])): ?>
                <div class="detail-section">
                    <h4>Intestatario</h4>
                    <div class="detail-content">
                        <?php if (!empty($whois_info['registrant_name'])): ?>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($whois_info['registrant_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($whois_info['registrant_organization'])): ?>
                        <p><strong>Organizzazione:</strong> <?php echo htmlspecialchars($whois_info['registrant_organization']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($whois_info['registrant_country'])): ?>
                        <p><strong>Paese:</strong> <?php echo htmlspecialchars($whois_info['registrant_country']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Nameserver -->
                <?php if (!empty($whois_info['nameservers'])): ?>
                <div class="detail-section">
                    <h4>Nameserver</h4>
                    <div class="nameserver-list">
                        <?php foreach ($whois_info['nameservers'] as $ns): ?>
                        <div class="nameserver-item">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M2 2a2 2 0 00-2 2v1a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2H2zm.5 3a.5.5 0 110-1 .5.5 0 010 1zm2 0a.5.5 0 110-1 .5.5 0 010 1zM2 9a2 2 0 00-2 2v1a2 2 0 002 2h12a2 2 0 002-2v-1a2 2 0 00-2-2H2zm.5 3a.5.5 0 110-1 .5.5 0 010 1zm2 0a.5.5 0 110-1 .5.5 0 010 1z"/>
                            </svg>
                            <?php echo htmlspecialchars($ns); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stati del dominio -->
                <?php if (!empty($whois_info['domain_status'])): ?>
                <div class="detail-section">
                    <h4>Stati del Dominio</h4>
                    <div class="status-list">
                        <?php 
                        $statuses = is_array($whois_info['domain_status']) ? $whois_info['domain_status'] : array($whois_info['domain_status']);
                        foreach ($statuses as $status): 
                        ?>
                        <div class="status-item">
                            <?php 
                            $status_text = htmlspecialchars($status);
                            $status_class = 'status-default';
                            
                            if (strpos($status, 'clientTransferProhibited') !== false) {
                                $status_class = 'status-locked';
                                $status_desc = 'Trasferimento bloccato';
                            } elseif (strpos($status, 'clientDeleteProhibited') !== false) {
                                $status_class = 'status-locked';
                                $status_desc = 'Cancellazione bloccata';
                            } elseif (strpos($status, 'ok') !== false || strpos($status, 'active') !== false) {
                                $status_class = 'status-ok';
                                $status_desc = 'Stato normale';
                            } else {
                                $status_desc = 'Stato dominio';
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                            <span class="status-desc"><?php echo $status_desc; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- WHOIS grezzo -->
                <?php if (!empty($whois_info['raw_data'])): ?>
                <div class="detail-section">
                    <h4>Dati WHOIS Completi</h4>
                    <div class="raw-whois">
                        <pre><?php echo htmlspecialchars($whois_info['raw_data']); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informazioni aggiuntive -->
        <div class="info-section" data-aos="fade-up">
            <h2>Cos'è il WHOIS?</h2>
            <p>Il WHOIS è un protocollo di query e risposta ampiamente utilizzato per interrogare database che memorizzano gli assegnatari registrati di una risorsa Internet, come un nome di dominio.</p>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>Informazioni Disponibili</h3>
                    <p>I dati WHOIS includono tipicamente: intestatario del dominio, contatti amministrativi e tecnici, date di registrazione e scadenza, nameserver.</p>
                </div>
                <div class="info-card">
                    <h3>Privacy WHOIS</h3>
                    <p>Molti registrar offrono servizi di privacy WHOIS che nascondono le informazioni personali del registrante per proteggere la privacy.</p>
                </div>
                <div class="info-card">
                    <h3>GDPR e WHOIS</h3>
                    <p>Dal 2018, il GDPR ha limitato le informazioni disponibili pubblicamente nel WHOIS per i domini europei.</p>
                </div>
                <div class="info-card">
                    <h3>Utilizzi del WHOIS</h3>
                    <p>Verificare la proprietà di un dominio, controllare le date di scadenza, investigare problemi tecnici o legali.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Dati WHOIS per JavaScript
const whoisData = <?php echo json_encode($whois_info ?? []); ?>;
const domain = <?php echo json_encode($domain); ?>;

// Funzione per copiare le informazioni WHOIS
function copyWhoisInfo() {
    let text = `WHOIS Information for ${domain}\n`;
    text += `${'='.repeat(40)}\n\n`;
    
    if (whoisData.registrar) {
        text += `Registrar: ${whoisData.registrar}\n`;
    }
    if (whoisData.creation_date) {
        text += `Creation Date: ${whoisData.creation_date}\n`;
    }
    if (whoisData.expiry_date) {
        text += `Expiry Date: ${whoisData.expiry_date}\n`;
    }
    if (whoisData.registrant_name) {
        text += `Registrant: ${whoisData.registrant_name}\n`;
    }
    if (whoisData.nameservers && whoisData.nameservers.length > 0) {
        text += `\nNameservers:\n`;
        whoisData.nameservers.forEach(ns => {
            text += `  - ${ns}\n`;
        });
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Informazioni WHOIS copiate negli appunti!', 'success');
    }).catch(() => {
        showNotification('Errore durante la copia', 'error');
    });
}

// Funzione per esportare report PDF (simulata)
function exportWhoisPdf() {
    // In un'implementazione reale, genereresti un PDF lato server
    showNotification('Funzionalità di export PDF in arrivo!', 'info');
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
