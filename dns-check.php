<?php
/**
 * Controllo DNS - Analisi dettagliata record DNS
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

// Imposta pagina corrente per il menu
$current_page = 'dns-check';

// Meta tags specifici per questa pagina
$page_title = "Controllo DNS - Analisi Record DNS | " . APP_NAME;
$page_description = "Verifica tutti i record DNS di un dominio: A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA. Strumento gratuito per analisi DNS completa.";
$canonical_url = APP_URL . "/dns-check";

// Variabili per il form
$domain = '';
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
                // Recupera tutti i record DNS
                $dns_data = getAllDnsRecords($domain);
                $dns_results = $dns_data['records'];
                
                if (empty($dns_results)) {
                    $error_message = 'Nessun record DNS trovato per questo dominio.';
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
include ABSPATH . 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-secondary">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-aos="fade-up">
                Controllo DNS
                <span class="hero-gradient">Analisi Record DNS</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Verifica tutti i record DNS del tuo dominio in tempo reale
            </p>
        </div>
    </div>
</section>

<!-- DNS Check Tool -->
<section class="tool-section">
    <div class="container">
        <!-- Form di ricerca -->
        <div class="search-box" data-aos="fade-up">
            <form method="POST" action="/dns-check" class="domain-form">
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
                            <span class="button-text">Analizza DNS</span>
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

        <?php if ($analysis_completed && $dns_results): ?>
        <!-- Risultati DNS -->
        <div class="results-container" data-aos="fade-up">
            <div class="results-header">
                <h2>Risultati DNS per <?php echo htmlspecialchars($domain); ?></h2>
                <div class="results-actions">
                    <button onclick="copyAllDnsRecords()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4 2a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H4zm0 1h8a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                        </svg>
                        Copia Tutto
                    </button>
                    <button onclick="exportDnsJson()" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 12a.5.5 0 00.5-.5v-7a.5.5 0 00-1 0v7a.5.5 0 00.5.5z"/>
                            <path d="M8 13a.5.5 0 00.354-.146l3-3a.5.5 0 00-.708-.708L8 11.793 5.354 9.146a.5.5 0 00-.708.708l3 3A.5.5 0 008 13z"/>
                        </svg>
                        Esporta JSON
                    </button>
                </div>
            </div>

            <!-- Record A -->
            <?php if (isset($dns_results['A']) && !empty($dns_results['A'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">🌐</span>
                    Record A (IPv4)
                </h3>
                <div class="record-grid">
                    <?php foreach ($dns_results['A'] as $record): ?>
                    <div class="record-card">
                        <div class="record-value"><?php echo htmlspecialchars($record['ip']); ?></div>
                        <div class="record-meta">
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                            <?php if (isset($record['location'])): ?>
                            <span class="location"><?php echo htmlspecialchars($record['location']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record AAAA -->
            <?php if (isset($dns_results['AAAA']) && !empty($dns_results['AAAA'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">🌍</span>
                    Record AAAA (IPv6)
                </h3>
                <div class="record-grid">
                    <?php foreach ($dns_results['AAAA'] as $record): ?>
                    <div class="record-card">
                        <div class="record-value"><?php echo htmlspecialchars($record['ipv6']); ?></div>
                        <div class="record-meta">
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record MX -->
            <?php if (isset($dns_results['MX']) && !empty($dns_results['MX'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">📧</span>
                    Record MX (Mail Exchange)
                </h3>
                <div class="record-grid">
                    <?php 
                    // Ordina per priorità
                    usort($dns_results['MX'], function($a, $b) {
                        return $a['pri'] - $b['pri'];
                    });
                    foreach ($dns_results['MX'] as $record): 
                    ?>
                    <div class="record-card">
                        <div class="record-value"><?php echo htmlspecialchars($record['target']); ?></div>
                        <div class="record-meta">
                            <span class="priority">Priorità: <?php echo htmlspecialchars($record['pri']); ?></span>
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record TXT -->
            <?php if (isset($dns_results['TXT']) && !empty($dns_results['TXT'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">📝</span>
                    Record TXT
                </h3>
                <div class="txt-records">
                    <?php foreach ($dns_results['TXT'] as $record): ?>
                    <div class="txt-record">
                        <code><?php echo htmlspecialchars($record['txt']); ?></code>
                        <div class="record-meta">
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                            <?php 
                            // Identifica il tipo di record TXT
                            $txt = strtolower($record['txt']);
                            if (strpos($txt, 'v=spf1') !== false) {
                                echo '<span class="record-tag spf">SPF</span>';
                            } elseif (strpos($txt, 'v=dkim1') !== false) {
                                echo '<span class="record-tag dkim">DKIM</span>';
                            } elseif (strpos($txt, 'v=dmarc1') !== false) {
                                echo '<span class="record-tag dmarc">DMARC</span>';
                            } elseif (strpos($txt, 'google-site-verification') !== false) {
                                echo '<span class="record-tag google">Google</span>';
                            } elseif (strpos($txt, 'facebook-domain-verification') !== false) {
                                echo '<span class="record-tag facebook">Facebook</span>';
                            } elseif (strpos($txt, 'ms=') !== false) {
                                echo '<span class="record-tag microsoft">Microsoft</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record NS -->
            <?php if (isset($dns_results['NS']) && !empty($dns_results['NS'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">🖥️</span>
                    Record NS (Name Server)
                </h3>
                <div class="record-grid">
                    <?php foreach ($dns_results['NS'] as $record): ?>
                    <div class="record-card">
                        <div class="record-value"><?php echo htmlspecialchars($record['target']); ?></div>
                        <div class="record-meta">
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record CNAME -->
            <?php if (isset($dns_results['CNAME']) && !empty($dns_results['CNAME'])): ?>
            <div class="dns-record-section">
                <h3 class="record-type-title">
                    <span class="record-icon">🔗</span>
                    Record CNAME
                </h3>
                <div class="record-grid">
                    <?php foreach ($dns_results['CNAME'] as $record): ?>
                    <div class="record-card">
                        <div class="record-value"><?php echo htmlspecialchars($record['target']); ?></div>
                        <div class="record-meta">
                            <span class="ttl">TTL: <?php echo htmlspecialchars($record['ttl']); ?>s</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Informazioni aggiuntive -->
        <div class="info-section" data-aos="fade-up">
            <h2>Cosa sono i record DNS?</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3>Record A</h3>
                    <p>Mappa un dominio a un indirizzo IPv4. È il record più comune e fondamentale per far funzionare un sito web.</p>
                </div>
                <div class="info-card">
                    <h3>Record MX</h3>
                    <p>Specifica i server di posta per il dominio. Essenziale per ricevere email sul dominio.</p>
                </div>
                <div class="info-card">
                    <h3>Record TXT</h3>
                    <p>Contiene informazioni testuali, spesso usato per SPF, DKIM, DMARC e verifiche di proprietà.</p>
                </div>
                <div class="info-card">
                    <h3>Record CNAME</h3>
                    <p>Crea un alias che punta a un altro dominio. Utile per sottodomini e servizi esterni.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Funzioni JavaScript per l'export e la copia
function copyAllDnsRecords() {
    const dnsData = <?php echo json_encode($dns_results ?? []); ?>;
    const text = JSON.stringify(dnsData, null, 2);
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Record DNS copiati negli appunti!', 'success');
    }).catch(() => {
        showNotification('Errore durante la copia', 'error');
    });
}

function exportDnsJson() {
    const dnsData = <?php echo json_encode($dns_results ?? []); ?>;
    const domain = <?php echo json_encode($domain); ?>;
    const dataStr = JSON.stringify(dnsData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = `dns-records-${domain}-${Date.now()}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}

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

<?php include ABSPATH . 'templates/footer.php'; ?>
