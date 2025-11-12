<?php
/**
 * Guida DNS - Documentazione completa sui record DNS
 *
 * @author G Tech Group
 * @version 4.2.1
 */

// Definisci ABSPATH se non esiste
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Includi file di configurazione
if (file_exists(ABSPATH . 'config/config.php')) {
    require_once ABSPATH . 'config/config.php';
}

// Imposta pagina corrente per il menu
$current_page = 'dns-guide';

// Meta tags specifici per questa pagina
$page_title = "Guida DNS - Come funzionano i record DNS | " . (defined('APP_NAME') ? APP_NAME : 'Controllo Domini');
$page_description = "Guida completa ai record DNS: A, AAAA, MX, TXT, CNAME, NS. Scopri come configurare correttamente i DNS del tuo dominio.";
$canonical_url = (defined('APP_URL') ? APP_URL : 'https://controllodomini.it') . "/dns-guide";

// Breadcrumb
$breadcrumb_items = array(
    array('name' => 'Guida DNS', 'url' => null)
);

// Includi header
include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-secondary">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-aos="fade-up">
                Guida Completa ai Record DNS
                <span class="hero-gradient">Tutto quello che devi sapere</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Impara a configurare e gestire i record DNS del tuo dominio come un professionista
            </p>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="guide-content-section">
    <div class="container">
        <div class="guide-layout">
            <!-- Sidebar Navigation -->
            <aside class="guide-sidebar" data-aos="fade-right">
                <nav class="guide-nav">
                    <h3>Contenuti</h3>
                    <ul>
                        <li><a href="#introduction" class="active">Introduzione ai DNS</a></li>
                        <li><a href="#how-dns-works">Come funzionano i DNS</a></li>
                        <li><a href="#record-types">Tipi di Record DNS</a></li>
                        <li><a href="#record-a">Record A</a></li>
                        <li><a href="#record-aaaa">Record AAAA</a></li>
                        <li><a href="#record-mx">Record MX</a></li>
                        <li><a href="#record-txt">Record TXT</a></li>
                        <li><a href="#record-cname">Record CNAME</a></li>
                        <li><a href="#record-ns">Record NS</a></li>
                        <li><a href="#record-soa">Record SOA</a></li>
                        <li><a href="#record-srv">Record SRV</a></li>
                        <li><a href="#record-caa">Record CAA</a></li>
                        <li><a href="#ttl">TTL (Time To Live)</a></li>
                        <li><a href="#propagation">Propagazione DNS</a></li>
                        <li><a href="#troubleshooting">Risoluzione Problemi</a></li>
                        <li><a href="#best-practices">Best Practices</a></li>
                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="guide-content" data-aos="fade-up">
                <!-- Introduction -->
                <section id="introduction" class="guide-section">
                    <h2>Introduzione ai DNS</h2>
                    <p>Il DNS (Domain Name System) è il sistema che traduce i nomi di dominio leggibili dagli umani (come esempio.it) in indirizzi IP che i computer utilizzano per comunicare tra loro.</p>
                    <p>Pensalo come la rubrica telefonica di Internet: quando digiti un nome di dominio nel browser, il DNS trova l'indirizzo IP corrispondente per raggiungere il server giusto.</p>
                    
                    <div class="info-box">
                        <strong>💡 Lo sapevi?</strong>
                        <p>Il sistema DNS è stato inventato nel 1983 da Paul Mockapetris per sostituire il file HOSTS.TXT che conteneva tutti i mapping nome-indirizzo di Internet.</p>
                    </div>
                </section>

                <!-- How DNS Works -->
                <section id="how-dns-works" class="guide-section">
                    <h2>Come funzionano i DNS</h2>
                    <p>Il processo di risoluzione DNS avviene in diversi passaggi:</p>
                    
                    <ol class="process-list">
                        <li>
                            <strong>Query DNS</strong>
                            <p>Il browser invia una richiesta al resolver DNS (solitamente fornito dal tuo ISP)</p>
                        </li>
                        <li>
                            <strong>Root Server</strong>
                            <p>Il resolver contatta un root server DNS che conosce i server TLD</p>
                        </li>
                        <li>
                            <strong>TLD Server</strong>
                            <p>Il root server indirizza al server TLD appropriato (.com, .it, ecc.)</p>
                        </li>
                        <li>
                            <strong>Authoritative Server</strong>
                            <p>Il TLD server fornisce l'indirizzo del server autoritativo per il dominio</p>
                        </li>
                        <li>
                            <strong>Risposta</strong>
                            <p>Il server autoritativo restituisce l'indirizzo IP richiesto</p>
                        </li>
                    </ol>

                    <div class="visual-diagram">
                        <img src="/assets/images/dns-process.svg" alt="Processo di risoluzione DNS" loading="lazy">
                    </div>
                </section>

                <!-- Record Types -->
                <section id="record-types" class="guide-section">
                    <h2>Tipi di Record DNS</h2>
                    <p>Esistono diversi tipi di record DNS, ognuno con una funzione specifica:</p>
                    
                    <div class="record-types-grid">
                        <div class="record-type-card">
                            <h4>A</h4>
                            <p>Mappa un dominio a un indirizzo IPv4</p>
                        </div>
                        <div class="record-type-card">
                            <h4>AAAA</h4>
                            <p>Mappa un dominio a un indirizzo IPv6</p>
                        </div>
                        <div class="record-type-card">
                            <h4>MX</h4>
                            <p>Specifica i server di posta elettronica</p>
                        </div>
                        <div class="record-type-card">
                            <h4>TXT</h4>
                            <p>Contiene informazioni testuali arbitrarie</p>
                        </div>
                        <div class="record-type-card">
                            <h4>CNAME</h4>
                            <p>Crea un alias per un altro dominio</p>
                        </div>
                        <div class="record-type-card">
                            <h4>NS</h4>
                            <p>Specifica i name server autoritativi</p>
                        </div>
                    </div>
                </section>

                <!-- Record A -->
                <section id="record-a" class="guide-section">
                    <h2>Record A</h2>
                    <p>Il record A (Address) è il tipo più comune di record DNS. Mappa un nome di dominio a un indirizzo IPv4.</p>
                    
                    <h3>Sintassi</h3>
                    <code class="code-block">
                        esempio.it.    IN    A    93.184.216.34
                    </code>
                    
                    <h3>Esempi comuni</h3>
                    <code class="code-block">
                        esempio.it.         IN    A    93.184.216.34
                        www.esempio.it.     IN    A    93.184.216.34
                        mail.esempio.it.    IN    A    93.184.216.35
                    </code>
                    
                    <h3>Quando usarlo</h3>
                    <ul>
                        <li>Per puntare il dominio principale al server web</li>
                        <li>Per creare sottodomini che puntano a server specifici</li>
                        <li>Per configurare server di posta, FTP, o altri servizi</li>
                    </ul>
                    
                    <div class="warning-box">
                        <strong>⚠️ Attenzione</strong>
                        <p>Non puoi avere un record CNAME e un record A per lo stesso sottodominio.</p>
                    </div>
                </section>

                <!-- Record AAAA -->
                <section id="record-aaaa" class="guide-section">
                    <h2>Record AAAA</h2>
                    <p>Il record AAAA (Quad-A) è l'equivalente IPv6 del record A. Mappa un nome di dominio a un indirizzo IPv6.</p>
                    
                    <h3>Sintassi</h3>
                    <code class="code-block">
                        esempio.it.    IN    AAAA    2606:2800:220:1:248:1893:25c8:1946
                    </code>
                    
                    <h3>Perché è importante</h3>
                    <p>Con l'esaurimento degli indirizzi IPv4, IPv6 sta diventando sempre più importante. Configurare record AAAA garantisce che il tuo sito sia accessibile anche da reti IPv6.</p>
                    
                    <div class="info-box">
                        <strong>💡 Suggerimento</strong>
                        <p>È buona pratica avere sia record A che AAAA per garantire la massima accessibilità.</p>
                    </div>
                </section>

                <!-- Record MX -->
                <section id="record-mx" class="guide-section">
                    <h2>Record MX</h2>
                    <p>I record MX (Mail eXchange) specificano i server di posta responsabili per ricevere email per il dominio.</p>
                    
                    <h3>Sintassi</h3>
                    <code class="code-block">
                        esempio.it.    IN    MX    10    mail1.esempio.it.
                        esempio.it.    IN    MX    20    mail2.esempio.it.
                    </code>
                    
                    <h3>Priorità</h3>
                    <p>Il numero prima del server indica la priorità (più basso = priorità maggiore). I server con priorità più bassa vengono provati per primi.</p>
                    
                    <h3>Configurazioni comuni</h3>
                    <div class="config-examples">
                        <h4>Google Workspace</h4>
                        <code class="code-block">
                            esempio.it.    IN    MX    1     aspmx.l.google.com.
                            esempio.it.    IN    MX    5     alt1.aspmx.l.google.com.
                            esempio.it.    IN    MX    5     alt2.aspmx.l.google.com.
                            esempio.it.    IN    MX    10    alt3.aspmx.l.google.com.
                            esempio.it.    IN    MX    10    alt4.aspmx.l.google.com.
                        </code>
                        
                        <h4>Microsoft 365</h4>
                        <code class="code-block">
                            esempio.it.    IN    MX    0     esempio-it.mail.protection.outlook.com.
                        </code>
                    </div>
                </section>

                <!-- Record TXT -->
                <section id="record-txt" class="guide-section">
                    <h2>Record TXT</h2>
                    <p>I record TXT contengono informazioni testuali arbitrarie. Sono spesso usati per verifiche di proprietà e configurazioni di sicurezza email.</p>
                    
                    <h3>Usi comuni</h3>
                    <ul>
                        <li><strong>SPF</strong>: Specifica quali server possono inviare email per il dominio</li>
                        <li><strong>DKIM</strong>: Firma digitale per autenticare le email</li>
                        <li><strong>DMARC</strong>: Policy per la gestione delle email non autenticate</li>
                        <li><strong>Verifiche di proprietà</strong>: Google, Microsoft, Facebook, ecc.</li>
                    </ul>
                    
                    <h3>Esempi</h3>
                    <code class="code-block">
                        # SPF
                        esempio.it.    IN    TXT    "v=spf1 include:_spf.google.com ~all"
                        
                        # DMARC
                        _dmarc.esempio.it.    IN    TXT    "v=DMARC1; p=reject; rua=mailto:dmarc@esempio.it"
                        
                        # Verifica Google
                        esempio.it.    IN    TXT    "google-site-verification=abc123..."
                    </code>
                    
                    <div class="info-box">
                        <strong>💡 Limite caratteri</strong>
                        <p>I record TXT hanno un limite di 255 caratteri per stringa, ma puoi concatenare più stringhe usando le virgolette.</p>
                    </div>
                </section>

                <!-- Record CNAME -->
                <section id="record-cname" class="guide-section">
                    <h2>Record CNAME</h2>
                    <p>Il record CNAME (Canonical Name) crea un alias che punta a un altro nome di dominio.</p>
                    
                    <h3>Sintassi</h3>
                    <code class="code-block">
                        www.esempio.it.    IN    CNAME    esempio.it.
                        blog.esempio.it.   IN    CNAME    esempio.wordpress.com.
                    </code>
                    
                    <h3>Regole importanti</h3>
                    <ul>
                        <li>Un CNAME non può coesistere con altri record per lo stesso nome</li>
                        <li>Non puoi creare un CNAME per il dominio root (@)</li>
                        <li>Il target di un CNAME deve essere un nome di dominio, non un IP</li>
                    </ul>
                    
                    <div class="warning-box">
                        <strong>⚠️ Errore comune</strong>
                        <p>Non creare catene di CNAME (CNAME che punta a un altro CNAME). Questo rallenta la risoluzione DNS.</p>
                    </div>
                </section>

                <!-- Record NS -->
                <section id="record-ns" class="guide-section">
                    <h2>Record NS</h2>
                    <p>I record NS (Name Server) specificano quali server DNS sono autoritativi per il dominio.</p>
                    
                    <h3>Esempio</h3>
                    <code class="code-block">
                        esempio.it.    IN    NS    ns1.esempio.it.
                        esempio.it.    IN    NS    ns2.esempio.it.
                    </code>
                    
                    <h3>Best practices</h3>
                    <ul>
                        <li>Usa almeno 2 name server per ridondanza</li>
                        <li>Distribuisci i name server geograficamente</li>
                        <li>Usa name server su reti diverse</li>
                    </ul>
                </section>

                <!-- Record SOA -->
                <section id="record-soa" class="guide-section">
                    <h2>Record SOA</h2>
                    <p>Il record SOA (Start of Authority) contiene informazioni amministrative sulla zona DNS.</p>
                    
                    <h3>Struttura</h3>
                    <code class="code-block">
                        esempio.it. IN SOA ns1.esempio.it. admin.esempio.it. (
                            2024010101 ; Serial
                            10800      ; Refresh (3 ore)
                            3600       ; Retry (1 ora)
                            604800     ; Expire (1 settimana)
                            86400      ; Minimum TTL (1 giorno)
                        )
                    </code>
                    
                    <h3>Campi del record SOA</h3>
                    <ul>
                        <li><strong>Serial</strong>: Numero di versione della zona (formato YYYYMMDDNN)</li>
                        <li><strong>Refresh</strong>: Frequenza di controllo aggiornamenti</li>
                        <li><strong>Retry</strong>: Tempo di attesa dopo un fallimento</li>
                        <li><strong>Expire</strong>: Tempo massimo prima di considerare la zona non valida</li>
                        <li><strong>Minimum TTL</strong>: TTL predefinito per i record negativi</li>
                    </ul>
                </section>

                <!-- Record SRV -->
                <section id="record-srv" class="guide-section">
                    <h2>Record SRV</h2>
                    <p>I record SRV (Service) specificano l'ubicazione di servizi specifici.</p>
                    
                    <h3>Formato</h3>
                    <code class="code-block">
                        _service._protocol.name. TTL IN SRV priority weight port target.
                    </code>
                    
                    <h3>Esempio per Microsoft 365</h3>
                    <code class="code-block">
                        _sip._tls.esempio.it.     IN SRV 100 1 443 sipdir.online.lync.com.
                        _sipfederationtls._tcp.esempio.it. IN SRV 100 1 5061 sipfed.online.lync.com.
                    </code>
                    
                    <h3>Usi comuni</h3>
                    <ul>
                        <li>Autodiscover per client email</li>
                        <li>SIP/VoIP</li>
                        <li>Servizi di messaggistica istantanea</li>
                        <li>Gaming multiplayer</li>
                    </ul>
                </section>

                <!-- Record CAA -->
                <section id="record-caa" class="guide-section">
                    <h2>Record CAA</h2>
                    <p>I record CAA (Certification Authority Authorization) specificano quali CA possono emettere certificati SSL per il dominio.</p>
                    
                    <h3>Sintassi</h3>
                    <code class="code-block">
                        esempio.it.    IN    CAA    0 issue "letsencrypt.org"
                        esempio.it.    IN    CAA    0 issue "digicert.com"
                        esempio.it.    IN    CAA    0 iodef "mailto:security@esempio.it"
                    </code>
                    
                    <h3>Tag supportati</h3>
                    <ul>
                        <li><strong>issue</strong>: Autorizza CA a emettere certificati</li>
                        <li><strong>issuewild</strong>: Autorizza CA a emettere certificati wildcard</li>
                        <li><strong>iodef</strong>: Email per notifiche di violazioni</li>
                    </ul>
                    
                    <div class="info-box">
                        <strong>🔒 Sicurezza</strong>
                        <p>I record CAA sono un'importante misura di sicurezza per prevenire l'emissione non autorizzata di certificati SSL.</p>
                    </div>
                </section>

                <!-- TTL -->
                <section id="ttl" class="guide-section">
                    <h2>TTL (Time To Live)</h2>
                    <p>Il TTL determina per quanto tempo un record DNS viene memorizzato nella cache prima di dover essere richiesto nuovamente.</p>
                    
                    <h3>Valori comuni</h3>
                    <table class="ttl-table">
                        <thead>
                            <tr>
                                <th>TTL</th>
                                <th>Durata</th>
                                <th>Uso consigliato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>300</td>
                                <td>5 minuti</td>
                                <td>Durante migrazioni o test</td>
                            </tr>
                            <tr>
                                <td>3600</td>
                                <td>1 ora</td>
                                <td>Siti in sviluppo attivo</td>
                            </tr>
                            <tr>
                                <td>86400</td>
                                <td>24 ore</td>
                                <td>Siti stabili (default)</td>
                            </tr>
                            <tr>
                                <td>604800</td>
                                <td>1 settimana</td>
                                <td>Record che cambiano raramente</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="tip-box">
                        <strong>💡 Suggerimento</strong>
                        <p>Prima di una migrazione, abbassa il TTL a 300 secondi almeno 24 ore prima per velocizzare la propagazione.</p>
                    </div>
                </section>

                <!-- DNS Propagation -->
                <section id="propagation" class="guide-section">
                    <h2>Propagazione DNS</h2>
                    <p>La propagazione DNS è il tempo necessario affinché le modifiche ai record DNS si diffondano attraverso tutti i server DNS di Internet.</p>
                    
                    <h3>Fattori che influenzano la propagazione</h3>
                    <ul>
                        <li><strong>TTL dei record</strong>: Determina la durata della cache</li>
                        <li><strong>Cache DNS locale</strong>: ISP e resolver locali</li>
                        <li><strong>Geographic distribution</strong>: Server DNS globali</li>
                    </ul>
                    
                    <h3>Timeline tipica</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <span class="time">0-15 min</span>
                            <p>Aggiornamento sui name server autoritativi</p>
                        </div>
                        <div class="timeline-item">
                            <span class="time">15 min - 4 ore</span>
                            <p>Propagazione ai resolver principali</p>
                        </div>
                        <div class="timeline-item">
                            <span class="time">4-48 ore</span>
                            <p>Propagazione completa globale</p>
                        </div>
                    </div>
                    
                    <h3>Come verificare la propagazione</h3>
                    <ul>
                        <li>Usa strumenti online come whatsmydns.net</li>
                        <li>Testa da diverse località geografiche</li>
                        <li>Svuota la cache DNS locale</li>
                        <li>Usa DNS pubblici per testing (8.8.8.8, 1.1.1.1)</li>
                    </ul>
                </section>

                <!-- Troubleshooting -->
                <section id="troubleshooting" class="guide-section">
                    <h2>Risoluzione Problemi DNS</h2>
                    
                    <h3>Problemi comuni e soluzioni</h3>
                    
                    <div class="troubleshooting-item">
                        <h4>Il sito non è raggiungibile dopo il cambio DNS</h4>
                        <ul>
                            <li>Verifica che i record A/AAAA siano corretti</li>
                            <li>Controlla il TTL e aspetta la propagazione</li>
                            <li>Svuota la cache DNS locale</li>
                            <li>Verifica con: <code>nslookup esempio.it</code></li>
                        </ul>
                    </div>
                    
                    <div class="troubleshooting-item">
                        <h4>Le email non arrivano</h4>
                        <ul>
                            <li>Controlla i record MX e la loro priorità</li>
                            <li>Verifica i record SPF, DKIM e DMARC</li>
                            <li>Assicurati che i server mail siano raggiungibili</li>
                            <li>Controlla i log del server di posta</li>
                        </ul>
                    </div>
                    
                    <div class="troubleshooting-item">
                        <h4>Certificato SSL non valido</h4>
                        <ul>
                            <li>Verifica i record CAA</li>
                            <li>Controlla che il dominio punti al server corretto</li>
                            <li>Verifica la validazione DNS per Let's Encrypt</li>
                        </ul>
                    </div>
                    
                    <h3>Comandi utili per diagnostica</h3>
                    <code class="code-block">
                        # Windows
                        nslookup esempio.it
                        nslookup esempio.it 8.8.8.8
                        ipconfig /flushdns
                        
                        # Linux/Mac
                        dig esempio.it
                        dig @8.8.8.8 esempio.it
                        host esempio.it
                        sudo dscacheutil -flushcache  # Mac
                        sudo systemd-resolve --flush-caches  # Linux
                    </code>
                </section>

                <!-- Best Practices -->
                <section id="best-practices" class="guide-section">
                    <h2>Best Practices DNS</h2>
                    
                    <h3>Sicurezza</h3>
                    <ul>
                        <li>Implementa DNSSEC per prevenire DNS spoofing</li>
                        <li>Usa record CAA per controllare l'emissione di certificati</li>
                        <li>Configura SPF, DKIM e DMARC per la sicurezza email</li>
                        <li>Limita i trasferimenti di zona solo ai server autorizzati</li>
                    </ul>
                    
                    <h3>Performance</h3>
                    <ul>
                        <li>Usa TTL appropriati (non troppo bassi né troppo alti)</li>
                        <li>Minimizza le catene di CNAME</li>
                        <li>Usa CDN con GeoDNS per contenuti statici</li>
                        <li>Monitora le performance DNS con strumenti dedicati</li>
                    </ul>
                    
                    <h3>Affidabilità</h3>
                    <ul>
                        <li>Usa almeno 2-3 name server su reti diverse</li>
                        <li>Configura monitoring per i tuoi DNS</li>
                        <li>Mantieni backup delle configurazioni DNS</li>
                        <li>Documenta tutte le modifiche DNS</li>
                    </ul>
                    
                    <h3>Manutenzione</h3>
                    <ul>
                        <li>Rimuovi record DNS obsoleti</li>
                        <li>Aggiorna regolarmente il numero seriale SOA</li>
                        <li>Verifica periodicamente tutti i record</li>
                        <li>Testa le modifiche in ambiente di staging</li>
                    </ul>
                    
                    <div class="conclusion-box">
                        <h3>🎯 Conclusione</h3>
                        <p>La corretta gestione dei DNS è fondamentale per la disponibilità e sicurezza del tuo dominio. Seguendo queste linee guida e comprendendo come funzionano i diversi record DNS, potrai configurare e mantenere i tuoi domini in modo professionale.</p>
                        <p>Ricorda sempre di testare le modifiche e di monitorare la propagazione DNS dopo ogni cambiamento.</p>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="guide-cta">
                    <h3>Vuoi analizzare i DNS del tuo dominio?</h3>
                    <p>Usa il nostro strumento gratuito per verificare la configurazione DNS del tuo dominio.</p>
                    <a href="/" class="btn btn-primary">Analizza DNS</a>
                </section>
            </main>
        </div>
    </div>
</section>

<style>
/* Guide specific styles */
.hero-secondary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 120px 0 80px;
    color: white;
}

.hero-gradient {
    display: block;
    font-size: 1.5rem;
    font-weight: 300;
    margin-top: 10px;
    opacity: 0.9;
}

.guide-content-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.guide-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 60px;
    max-width: 1200px;
    margin: 0 auto;
}

.guide-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.guide-nav {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.guide-nav h3 {
    margin-bottom: 20px;
    color: var(--secondary);
}

.guide-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.guide-nav li {
    margin-bottom: 12px;
}

.guide-nav a {
    color: var(--gray-dark);
    text-decoration: none;
    display: block;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s;
}

.guide-nav a:hover,
.guide-nav a.active {
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 500;
}

.guide-content {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.guide-section {
    margin-bottom: 60px;
    scroll-margin-top: 100px;
}

.guide-section h2 {
    color: var(--secondary);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.guide-section h3 {
    color: var(--primary);
    margin: 30px 0 15px;
}

.guide-section p {
    line-height: 1.8;
    margin-bottom: 15px;
}

.info-box, .warning-box, .tip-box {
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.warning-box {
    background: #fff3e0;
    border-left: 4px solid #ff9800;
}

.tip-box {
    background: #e8f5e9;
    border-left: 4px solid #4caf50;
}

.code-block {
    display: block;
    background: #f5f5f5;
    padding: 15px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    overflow-x: auto;
    margin: 15px 0;
}

.process-list {
    counter-reset: process-counter;
    list-style: none;
    padding-left: 0;
}

.process-list li {
    counter-increment: process-counter;
    margin-bottom: 20px;
    padding-left: 50px;
    position: relative;
}

.process-list li::before {
    content: counter(process-counter);
    position: absolute;
    left: 0;
    top: 0;
    width: 35px;
    height: 35px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.record-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.record-type-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.record-type-card h4 {
    color: var(--primary);
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.ttl-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.ttl-table th,
.ttl-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.ttl-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--secondary);
}

.timeline {
    margin: 30px 0;
}

.timeline-item {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-left: 30px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background: var(--primary);
    border-radius: 50%;
}

.timeline-item .time {
    font-weight: 600;
    color: var(--primary);
    min-width: 100px;
    margin-right: 20px;
}

.troubleshooting-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.troubleshooting-item h4 {
    color: var(--secondary);
    margin-bottom: 15px;
}

.config-examples h4 {
    color: var(--primary);
    margin: 20px 0 10px;
}

.guide-cta {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    margin-top: 60px;
}

.guide-cta h3 {
    color: white;
    margin-bottom: 10px;
}

.guide-cta p {
    margin-bottom: 20px;
    opacity: 0.9;
}

.guide-cta .btn {
    background: white;
    color: var(--primary);
}

.guide-cta .btn:hover {
    background: #f8f9fa;
}

.conclusion-box {
    background: #e8f5e9;
    padding: 30px;
    border-radius: 12px;
    margin-top: 40px;
}

.conclusion-box h3 {
    color: var(--secondary);
    margin-bottom: 15px;
}

/* Mobile responsive */
@media (max-width: 968px) {
    .guide-layout {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .guide-sidebar {
        position: static;
    }
    
    .guide-content {
        padding: 30px 20px;
    }
    
    .record-types-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}
</style>

<?php include 'templates/footer.php'; ?>
