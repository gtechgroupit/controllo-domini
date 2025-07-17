<?php
/**
 * Pagina SPF, DKIM e DMARC - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Carica il bootstrap
require_once 'bootstrap.php';

// SEO e metadata
$page_title = 'Guida SPF, DKIM e DMARC - Proteggi le tue Email | Controllo Domini';
$page_description = 'Guida completa alla configurazione di SPF, DKIM e DMARC per proteggere il tuo dominio dallo spoofing e migliorare la deliverability delle email.';
$canonical_url = APP_URL . '/spf-dkim-dmarc';
$page_name = 'SPF, DKIM e DMARC';

// Carica header
require_once ABSPATH . 'templates/header.php';
?>

<main id="main-content" class="guide-page">
    <div class="container">
        <!-- Hero Section -->
        <section class="guide-hero" data-aos="fade-up">
            <h1 class="guide-title">SPF, DKIM e DMARC</h1>
            <p class="guide-subtitle">
                Proteggi il tuo dominio dalle email fraudolente e migliora la deliverability
            </p>
            <div class="guide-meta">
                <span class="guide-reading-time">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Tempo di lettura: 10 minuti
                </span>
                <span class="guide-difficulty">
                    <span class="difficulty-badge difficulty-medium">Livello: Intermedio</span>
                </span>
            </div>
        </section>

        <!-- Introduzione -->
        <section class="guide-section" data-aos="fade-up">
            <div class="guide-content">
                <p class="lead">
                    SPF, DKIM e DMARC sono tre tecnologie fondamentali per proteggere il tuo dominio 
                    dall'email spoofing e garantire che le tue email legittime raggiungano i destinatari.
                </p>
                
                <div class="info-box">
                    <h3>Perché sono importanti?</h3>
                    <ul>
                        <li><strong>Prevenzione frodi</strong>: Impediscono ai malintenzionati di inviare email a nome tuo</li>
                        <li><strong>Migliore deliverability</strong>: Le email autenticate hanno maggiori probabilità di arrivare nella inbox</li>
                        <li><strong>Protezione del brand</strong>: Salvaguardano la reputazione del tuo dominio</li>
                        <li><strong>Conformità</strong>: Sempre più provider richiedono l'autenticazione email</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- SPF Section -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="spf">
                <span class="section-icon">📧</span>
                SPF (Sender Policy Framework)
            </h2>
            
            <div class="guide-content">
                <h3>Cos'è SPF?</h3>
                <p>
                    SPF è un record DNS TXT che specifica quali server sono autorizzati a inviare email 
                    per conto del tuo dominio. Funziona come una "lista bianca" di indirizzi IP autorizzati.
                </p>

                <h3>Come configurare SPF</h3>
                <div class="step-by-step">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>Identifica i tuoi mittenti</h4>
                            <p>Elenca tutti i servizi che inviano email per il tuo dominio:</p>
                            <ul>
                                <li>Server di posta aziendale</li>
                                <li>Provider email (Gmail, Office 365)</li>
                                <li>Servizi di marketing (MailChimp, SendGrid)</li>
                                <li>CRM e applicazioni web</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Costruisci il record SPF</h4>
                            <p>Crea un record TXT con la sintassi SPF:</p>
                            <div class="code-block">
                                <code>v=spf1 include:_spf.google.com include:spf.protection.outlook.com ip4:203.0.113.0 -all</code>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h4>Aggiungi il record al DNS</h4>
                            <p>Inserisci il record TXT nel tuo pannello DNS:</p>
                            <table class="guide-table">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nome</th>
                                    <th>Valore</th>
                                    <th>TTL</th>
                                </tr>
                                <tr>
                                    <td>TXT</td>
                                    <td>@</td>
                                    <td>v=spf1 include:... -all</td>
                                    <td>3600</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <h3>Meccanismi SPF comuni</h3>
                <div class="mechanism-grid">
                    <div class="mechanism-card">
                        <h4>include:</h4>
                        <p>Include il record SPF di un altro dominio</p>
                        <code>include:_spf.google.com</code>
                    </div>
                    <div class="mechanism-card">
                        <h4>ip4: / ip6:</h4>
                        <p>Autorizza specifici indirizzi IP</p>
                        <code>ip4:192.168.1.0/24</code>
                    </div>
                    <div class="mechanism-card">
                        <h4>a:</h4>
                        <p>Autorizza gli IP del record A del dominio</p>
                        <code>a:mail.example.com</code>
                    </div>
                    <div class="mechanism-card">
                        <h4>mx:</h4>
                        <p>Autorizza i server MX del dominio</p>
                        <code>mx</code>
                    </div>
                </div>

                <h3>Qualificatori SPF</h3>
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>Qualificatore</th>
                            <th>Risultato</th>
                            <th>Descrizione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>-all</code></td>
                            <td>Fail</td>
                            <td>Rifiuta email non autorizzate (consigliato)</td>
                        </tr>
                        <tr>
                            <td><code>~all</code></td>
                            <td>SoftFail</td>
                            <td>Marca come sospette ma non rifiuta</td>
                        </tr>
                        <tr>
                            <td><code>?all</code></td>
                            <td>Neutral</td>
                            <td>Nessuna policy definita</td>
                        </tr>
                        <tr>
                            <td><code>+all</code></td>
                            <td>Pass</td>
                            <td>Accetta tutto (sconsigliato)</td>
                        </tr>
                    </tbody>
                </table>

                <div class="warning-box">
                    <h4>⚠️ Limiti SPF</h4>
                    <ul>
                        <li>Massimo 10 lookup DNS (include, a, mx, ptr, exists)</li>
                        <li>Massimo 255 caratteri per stringa TXT</li>
                        <li>Un solo record SPF per dominio</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- DKIM Section -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="dkim">
                <span class="section-icon">🔐</span>
                DKIM (DomainKeys Identified Mail)
            </h2>
            
            <div class="guide-content">
                <h3>Cos'è DKIM?</h3>
                <p>
                    DKIM aggiunge una firma digitale crittografica alle email inviate dal tuo dominio. 
                    I server riceventi possono verificare questa firma per confermare che l'email 
                    non è stata modificata durante il transito e proviene effettivamente dal tuo dominio.
                </p>

                <h3>Come funziona DKIM</h3>
                <div class="dkim-flow">
                    <div class="flow-step">
                        <div class="flow-icon">🔑</div>
                        <h4>Generazione chiavi</h4>
                        <p>Viene creata una coppia di chiavi pubblica/privata</p>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-step">
                        <div class="flow-icon">✍️</div>
                        <h4>Firma email</h4>
                        <p>Il server firma l'email con la chiave privata</p>
                    </div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-step">
                        <div class="flow-icon">✅</div>
                        <h4>Verifica</h4>
                        <p>Il destinatario verifica con la chiave pubblica nel DNS</p>
                    </div>
                </div>

                <h3>Configurazione DKIM</h3>
                <div class="step-by-step">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>Genera le chiavi DKIM</h4>
                            <p>La maggior parte dei provider email genera automaticamente le chiavi:</p>
                            <ul>
                                <li><strong>Google Workspace</strong>: Admin console → Apps → Gmail → Authenticate email</li>
                                <li><strong>Office 365</strong>: Security center → Policies → DKIM</li>
                                <li><strong>Server proprio</strong>: Usa OpenDKIM o simili</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Pubblica la chiave pubblica</h4>
                            <p>Aggiungi un record TXT nel DNS con il selettore DKIM:</p>
                            <table class="guide-table">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nome</th>
                                    <th>Valore</th>
                                </tr>
                                <tr>
                                    <td>TXT</td>
                                    <td>selector._domainkey</td>
                                    <td>v=DKIM1; k=rsa; p=MIGfMA0GCSq...</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h4>Attiva DKIM</h4>
                            <p>Abilita la firma DKIM nel tuo provider email</p>
                        </div>
                    </div>
                </div>

                <h3>Esempio record DKIM</h3>
                <div class="code-block">
                    <pre>google._domainkey IN TXT "v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAq8Jxj3r..."</pre>
                </div>

                <div class="info-box">
                    <h4>Best practice DKIM</h4>
                    <ul>
                        <li>Usa chiavi di almeno 1024 bit (preferibilmente 2048)</li>
                        <li>Ruota le chiavi periodicamente (ogni 6-12 mesi)</li>
                        <li>Usa selettori diversi per servizi diversi</li>
                        <li>Mantieni backup sicuri delle chiavi private</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- DMARC Section -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="dmarc">
                <span class="section-icon">🛡️</span>
                DMARC (Domain-based Message Authentication)
            </h2>
            
            <div class="guide-content">
                <h3>Cos'è DMARC?</h3>
                <p>
                    DMARC costruisce su SPF e DKIM, permettendoti di specificare cosa fare 
                    con le email che falliscono l'autenticazione e ricevere report sulle 
                    email inviate a nome del tuo dominio.
                </p>

                <h3>Policy DMARC</h3>
                <div class="policy-grid">
                    <div class="policy-card">
                        <h4>none</h4>
                        <p>Solo monitoraggio, nessuna azione</p>
                        <span class="policy-use">Ideale per iniziare</span>
                    </div>
                    <div class="policy-card">
                        <h4>quarantine</h4>
                        <p>Sposta in spam le email sospette</p>
                        <span class="policy-use">Fase intermedia</span>
                    </div>
                    <div class="policy-card">
                        <h4>reject</h4>
                        <p>Rifiuta completamente le email non autenticate</p>
                        <span class="policy-use">Protezione massima</span>
                    </div>
                </div>

                <h3>Implementazione DMARC</h3>
                <div class="step-by-step">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>Verifica SPF e DKIM</h4>
                            <p>Assicurati che SPF e DKIM siano configurati correttamente</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Inizia con policy "none"</h4>
                            <p>Crea un record DMARC in modalità monitoraggio:</p>
                            <div class="code-block">
                                <code>v=DMARC1; p=none; rua=mailto:dmarc@tuodominio.it</code>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h4>Analizza i report</h4>
                            <p>Monitora i report DMARC per identificare mittenti legittimi</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">4</span>
                        <div class="step-content">
                            <h4>Aumenta gradualmente la policy</h4>
                            <p>Passa a "quarantine" e poi a "reject" quando sei sicuro</p>
                        </div>
                    </div>
                </div>

                <h3>Tag DMARC comuni</h3>
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Descrizione</th>
                            <th>Esempio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>v</code></td>
                            <td>Versione (sempre DMARC1)</td>
                            <td>v=DMARC1</td>
                        </tr>
                        <tr>
                            <td><code>p</code></td>
                            <td>Policy per il dominio</td>
                            <td>p=reject</td>
                        </tr>
                        <tr>
                            <td><code>sp</code></td>
                            <td>Policy per i sottodomini</td>
                            <td>sp=quarantine</td>
                        </tr>
                        <tr>
                            <td><code>rua</code></td>
                            <td>Email per report aggregati</td>
                            <td>rua=mailto:dmarc@example.com</td>
                        </tr>
                        <tr>
                            <td><code>ruf</code></td>
                            <td>Email per report forensi</td>
                            <td>ruf=mailto:forensics@example.com</td>
                        </tr>
                        <tr>
                            <td><code>pct</code></td>
                            <td>Percentuale email da filtrare</td>
                            <td>pct=50</td>
                        </tr>
                        <tr>
                            <td><code>adkim</code></td>
                            <td>Allineamento DKIM</td>
                            <td>adkim=s (strict) o r (relaxed)</td>
                        </tr>
                        <tr>
                            <td><code>aspf</code></td>
                            <td>Allineamento SPF</td>
                            <td>aspf=s (strict) o r (relaxed)</td>
                        </tr>
                    </tbody>
                </table>

                <h3>Esempio evoluzione DMARC</h3>
                <div class="dmarc-evolution">
                    <div class="evolution-step">
                        <h4>Fase 1: Monitoraggio</h4>
                        <code>v=DMARC1; p=none; rua=mailto:dmarc@tuodominio.it</code>
                    </div>
                    <div class="evolution-step">
                        <h4>Fase 2: Test quarantena</h4>
                        <code>v=DMARC1; p=none; sp=quarantine; pct=10; rua=mailto:dmarc@tuodominio.it</code>
                    </div>
                    <div class="evolution-step">
                        <h4>Fase 3: Quarantena parziale</h4>
                        <code>v=DMARC1; p=quarantine; pct=50; rua=mailto:dmarc@tuodominio.it</code>
                    </div>
                    <div class="evolution-step">
                        <h4>Fase 4: Protezione completa</h4>
                        <code>v=DMARC1; p=reject; rua=mailto:dmarc@tuodominio.it; ruf=mailto:forensics@tuodominio.it</code>
                    </div>
                </div>
            </div>
        </section>

        <!-- Verifica e Testing -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">🔍</span>
                Verifica e Testing
            </h2>
            
            <div class="guide-content">
                <h3>Strumenti di verifica</h3>
                <div class="tools-grid">
                    <div class="tool-card">
                        <h4>Controllo Domini</h4>
                        <p>Usa il nostro strumento per verificare SPF, DKIM e DMARC</p>
                        <a href="/" class="btn btn-primary">Verifica ora</a>
                    </div>
                    <div class="tool-card">
                        <h4>Mail Tester</h4>
                        <p>Invia un'email di test per verificare l'autenticazione</p>
                        <a href="https://www.mail-tester.com" target="_blank" rel="noopener" class="btn btn-secondary">Vai al sito</a>
                    </div>
                    <div class="tool-card">
                        <h4>MXToolbox</h4>
                        <p>Suite completa di strumenti per l'analisi email</p>
                        <a href="https://mxtoolbox.com" target="_blank" rel="noopener" class="btn btn-secondary">Vai al sito</a>
                    </div>
                </div>

                <h3>Checklist di verifica</h3>
                <div class="checklist">
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Record SPF pubblicato e sintassi corretta</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Tutti i mittenti autorizzati inclusi in SPF</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Chiave DKIM pubblicata nel DNS</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Email firmate correttamente con DKIM</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Record DMARC pubblicato</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Report DMARC configurati e ricevuti</span>
                    </label>
                    <label class="checklist-item">
                        <input type="checkbox">
                        <span>Test invio email superati</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Troubleshooting -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">🔧</span>
                Risoluzione Problemi Comuni
            </h2>
            
            <div class="guide-content">
                <div class="troubleshooting-accordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                SPF fallisce anche se configurato correttamente
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Troppi lookup DNS</strong>: Riduci gli include o usa servizi di flattening SPF</li>
                                <li><strong>IP non inclusi</strong>: Verifica tutti i server che inviano email</li>
                                <li><strong>Sintassi errata</strong>: Controlla spazi e caratteri speciali</li>
                                <li><strong>Record duplicati</strong>: Deve esserci un solo record SPF</li>
                            </ul>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                DKIM non viene verificato
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Chiave non trovata</strong>: Verifica il nome del selettore</li>
                                <li><strong>Chiave malformata</strong>: Controlla che non ci siano interruzioni nel record</li>
                                <li><strong>Firma non applicata</strong>: Assicurati che DKIM sia attivo nel mail server</li>
                                <li><strong>DNS non propagato</strong>: Attendi 24-48 ore dopo la pubblicazione</li>
                            </ul>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                Non ricevo report DMARC
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Email bloccata</strong>: Verifica filtri spam per l'indirizzo report</li>
                                <li><strong>Volume basso</strong>: I report aggregati arrivano ogni 24 ore</li>
                                <li><strong>Sintassi errata</strong>: Controlla il formato mailto: nel tag rua</li>
                                <li><strong>Provider non supporta</strong>: Non tutti inviano report forensi (ruf)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Best Practices -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">⭐</span>
                Best Practice e Consigli
            </h2>
            
            <div class="guide-content">
                <div class="best-practices-grid">
                    <div class="practice-card">
                        <h3>🚀 Implementazione graduale</h3>
                        <p>Non passare direttamente a policy restrittive. Inizia con il monitoraggio e aumenta gradualmente.</p>
                    </div>
                    
                    <div class="practice-card">
                        <h3>📊 Monitora sempre</h3>
                        <p>Analizza regolarmente i report DMARC per identificare problemi o nuovi mittenti legittimi.</p>
                    </div>
                    
                    <div class="practice-card">
                        <h3>🔄 Aggiorna regolarmente</h3>
                        <p>Rivedi SPF quando aggiungi nuovi servizi. Ruota le chiavi DKIM periodicamente.</p>
                    </div>
                    
                    <div class="practice-card">
                        <h3>📝 Documenta tutto</h3>
                        <p>Mantieni un registro di tutti i servizi autorizzati a inviare email per il tuo dominio.</p>
                    </div>
                    
                    <div class="practice-card">
                        <h3>🎯 Allineamento dominio</h3>
                        <p>Usa lo stesso dominio per From, Return-Path e firma DKIM quando possibile.</p>
                    </div>
                    
                    <div class="practice-card">
                        <h3>🛡️ Sottodomini</h3>
                        <p>Proteggi anche i sottodomini non utilizzati con record SPF/DMARC restrittivi.</p>
                    </div>
                </div>

                <div class="timeline-section">
                    <h3>Timeline di implementazione consigliata</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker">1</div>
                            <div class="timeline-content">
                                <h4>Settimana 1-2</h4>
                                <p>Implementa SPF e verifica tutti i mittenti</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker">2</div>
                            <div class="timeline-content">
                                <h4>Settimana 3-4</h4>
                                <p>Configura DKIM per tutti i servizi principali</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker">3</div>
                            <div class="timeline-content">
                                <h4>Mese 2</h4>
                                <p>Attiva DMARC con p=none e analizza i report</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker">4</div>
                            <div class="timeline-content">
                                <h4>Mese 3-4</h4>
                                <p>Passa gradualmente a p=quarantine</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker">5</div>
                            <div class="timeline-content">
                                <h4>Mese 5-6</h4>
                                <p>Implementa p=reject per protezione completa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="guide-cta" data-aos="fade-up">
            <div class="cta-content">
                <h2>Verifica subito la tua configurazione email</h2>
                <p>Usa il nostro strumento gratuito per controllare SPF, DKIM e DMARC del tuo dominio</p>
                <a href="/" class="btn btn-primary btn-lg">
                    Analizza il tuo dominio
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </section>
    </div>
</main>

<!-- JavaScript per accordion -->
<script>
function toggleAccordion(button) {
    const item = button.closest('.accordion-item');
    const content = item.querySelector('.accordion-content');
    const icon = button.querySelector('.accordion-icon');
    
    // Toggle active class
    item.classList.toggle('active');
    
    // Toggle icon
    icon.textContent = item.classList.contains('active') ? '▲' : '▼';
    
    // Animate content
    if (item.classList.contains('active')) {
        content.style.maxHeight = content.scrollHeight + 'px';
    } else {
        content.style.maxHeight = '0';
    }
}
</script>

<!-- CSS aggiuntivo per la pagina -->
<style>
/* Guide Page Styles */
.guide-page {
    padding: 80px 0 120px;
    min-height: 100vh;
}

.guide-hero {
    text-align: center;
    margin-bottom: 80px;
}

.guide-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.guide-subtitle {
    font-size: 1.25rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.guide-meta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.guide-reading-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.difficulty-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
}

.difficulty-medium {
    background: var(--warning-bg);
    color: var(--warning-color);
}

/* Sections */
.guide-section {
    margin-bottom: 80px;
}

.section-title {
    font-size: 2rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.section-icon {
    font-size: 1.5em;
}

.guide-content {
    max-width: 800px;
    margin: 0 auto;
}

.guide-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 2rem 0 1rem;
}

.guide-content p {
    line-height: 1.8;
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

/* Info Boxes */
.info-box, .warning-box {
    padding: 1.5rem;
    border-radius: 12px;
    margin: 2rem 0;
}

.info-box {
    background: var(--info-bg);
    border: 1px solid var(--info-border);
}

.warning-box {
    background: var(--warning-bg);
    border: 1px solid var(--warning-border);
}

.info-box h3, .warning-box h4 {
    margin-top: 0;
    margin-bottom: 1rem;
}

/* Steps */
.step-by-step {
    margin: 2rem 0;
}

.step {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.step:last-child {
    border-bottom: none;
}

.step-number {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: var(--primary-gradient);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.step-content h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

/* Code Blocks */
.code-block {
    background: var(--code-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    overflow-x: auto;
}

.code-block code, .code-block pre {
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.9rem;
    color: var(--code-color);
}

/* Tables */
.guide-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    background: var(--card-bg);
    border-radius: 8px;
    overflow: hidden;
}

.guide-table th {
    background: var(--bg-secondary);
    font-weight: 600;
    text-align: left;
    padding: 1rem;
}

.guide-table td {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
}

.guide-table code {
    background: var(--code-bg);
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Mechanism Grid */
.mechanism-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.mechanism-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.mechanism-card h4 {
    color: var(--primary-color);
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.mechanism-card code {
    display: block;
    margin-top: 0.5rem;
    background: var(--code-bg);
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Flow Diagram */
.dkim-flow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 2rem 0;
    padding: 2rem;
    background: var(--bg-secondary);
    border-radius: 12px;
}

.flow-step {
    text-align: center;
    flex: 1;
}

.flow-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.flow-arrow {
    font-size: 2rem;
    color: var(--primary-color);
    margin: 0 1rem;
}

/* Policy Grid */
.policy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.policy-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.policy-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.policy-card h4 {
    color: var(--primary-color);
    margin-top: 0;
    font-size: 1.25rem;
}

.policy-use {
    display: inline-block;
    margin-top: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: var(--primary-bg-light);
    color: var(--primary-color);
    border-radius: 20px;
    font-size: 0.85rem;
}

/* Evolution Steps */
.dmarc-evolution {
    margin: 2rem 0;
}

.evolution-step {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0 8px 8px 0;
}

.evolution-step h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.evolution-step code {
    background: var(--code-bg);
    padding: 0.75rem;
    border-radius: 4px;
    display: block;
    overflow-x: auto;
}

/* Tools Grid */
.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.tool-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
}

.tool-card h4 {
    margin-top: 0;
    color: var(--text-primary);
}

.tool-card .btn {
    margin-top: 1rem;
}

/* Checklist */
.checklist {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
}

.checklist-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checklist-item span {
    flex: 1;
}

/* Accordion */
.troubleshooting-accordion {
    margin: 2rem 0;
}

.accordion-item {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.accordion-header {
    margin: 0;
}

.accordion-button {
    width: 100%;
    padding: 1.5rem;
    background: none;
    border: none;
    text-align: left;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.3s ease;
}

.accordion-button:hover {
    background: var(--bg-secondary);
}

.accordion-icon {
    transition: transform 0.3s ease;
}

.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.accordion-item.active .accordion-content {
    padding: 0 1.5rem 1.5rem;
}

/* Best Practices Grid */
.best-practices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.practice-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.practice-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.practice-card h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

/* Timeline */
.timeline-section {
    margin-top: 4rem;
}

.timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}

.timeline-item {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    position: relative;
}

.timeline-marker {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: var(--primary-gradient);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.timeline-content {
    flex: 1;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.timeline-content h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

/* CTA Section */
.guide-cta {
    background: var(--primary-gradient);
    border-radius: 24px;
    padding: 4rem;
    text-align: center;
    color: white;
    margin-top: 80px;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-content .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Dark mode adjustments */
[data-theme="dark"] .guide-content {
    color: var(--text-secondary);
}

[data-theme="dark"] .code-block {
    background: #1a1a1a;
}

[data-theme="dark"] .info-box {
    background: rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
}

[data-theme="dark"] .warning-box {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .guide-title {
        font-size: 2rem;
    }
    
    .guide-meta {
        flex-direction: column;
        gap: 1rem;
    }
    
    .dkim-flow {
        flex-direction: column;
        gap: 2rem;
    }
    
    .flow-arrow {
        transform: rotate(90deg);
    }
    
    .timeline::before {
        left: 20px;
    }
    
    .guide-cta {
        padding: 3rem 2rem;
    }
    
    .cta-content h2 {
        font-size: 1.75rem;
    }
}
</style>

<?php require_once ABSPATH . 'templates/footer.php'; ?>
