<?php
/**
 * Pagina Setup Microsoft 365 - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 */

// Carica il bootstrap
require_once 'bootstrap.php';

// SEO e metadata
$page_title = 'Guida Setup Microsoft 365 - Configurazione Email e DNS | Controllo Domini';
$page_description = 'Guida completa alla configurazione di Microsoft 365 (Office 365) per il tuo dominio. Setup DNS, email, Teams e sicurezza passo dopo passo.';
$canonical_url = APP_URL . '/setup-microsoft-365';
$page_name = 'Setup Microsoft 365';

// Carica header
require_once ABSPATH . 'templates/header.php';
?>

<main id="main-content" class="guide-page">
    <div class="container">
        <!-- Hero Section -->
        <section class="guide-hero" data-aos="fade-up">
            <div class="microsoft-logo">
                <svg width="60" height="60" viewBox="0 0 23 23" fill="none">
                    <rect x="0" y="0" width="11" height="11" fill="#F25022"/>
                    <rect x="12" y="0" width="11" height="11" fill="#7FBA00"/>
                    <rect x="0" y="12" width="11" height="11" fill="#00A4EF"/>
                    <rect x="12" y="12" width="11" height="11" fill="#FFB900"/>
                </svg>
            </div>
            <h1 class="guide-title">Setup Microsoft 365</h1>
            <p class="guide-subtitle">
                Configura email professionali, Teams, OneDrive e tutte le app di Microsoft 365 per il tuo dominio
            </p>
            <div class="guide-meta">
                <span class="guide-reading-time">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Tempo di lettura: 15 minuti
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
                    Microsoft 365 (precedentemente Office 365) offre una suite completa di strumenti 
                    di produttività cloud per aziende di ogni dimensione. Questa guida ti accompagnerà 
                    nella configurazione completa per il tuo dominio personalizzato.
                </p>
                
                <div class="info-box">
                    <h3>Cosa include Microsoft 365?</h3>
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-icon">📧</span>
                            <strong>Exchange Online</strong>
                            <p>Email professionali con 50GB+</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">💬</span>
                            <strong>Microsoft Teams</strong>
                            <p>Chat, videochiamate e collaborazione</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📁</span>
                            <strong>OneDrive</strong>
                            <p>1TB di storage cloud per utente</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📝</span>
                            <strong>Office Apps</strong>
                            <p>Word, Excel, PowerPoint online e desktop</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📅</span>
                            <strong>SharePoint</strong>
                            <p>Siti team e gestione documenti</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🛡️</span>
                            <strong>Sicurezza avanzata</strong>
                            <p>Defender, DLP e compliance</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Prerequisiti -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">✅</span>
                Prerequisiti
            </h2>
            
            <div class="guide-content">
                <div class="prerequisites-list">
                    <div class="prerequisite-item">
                        <span class="check-icon">✓</span>
                        <div>
                            <h4>Dominio verificato</h4>
                            <p>Devi essere proprietario del dominio e avere accesso al pannello DNS</p>
                        </div>
                    </div>
                    <div class="prerequisite-item">
                        <span class="check-icon">✓</span>
                        <div>
                            <h4>Licenze Microsoft 365</h4>
                            <p>Un abbonamento Microsoft 365 Business o Enterprise attivo</p>
                        </div>
                    </div>
                    <div class="prerequisite-item">
                        <span class="check-icon">✓</span>
                        <div>
                            <h4>Account amministratore</h4>
                            <p>Credenziali di amministratore globale per Microsoft 365</p>
                        </div>
                    </div>
                    <div class="prerequisite-item">
                        <span class="check-icon">✓</span>
                        <div>
                            <h4>Backup email esistenti</h4>
                            <p>Se stai migrando, assicurati di avere un backup delle email attuali</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Step 1: Verifica Dominio -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="verifica-dominio">
                <span class="section-icon">1️⃣</span>
                Verifica del Dominio
            </h2>
            
            <div class="guide-content">
                <p>Il primo passo è dimostrare a Microsoft che sei il proprietario del dominio.</p>
                
                <div class="step-by-step">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>Accedi al centro di amministrazione</h4>
                            <p>Vai su <a href="https://admin.microsoft.com" target="_blank" rel="noopener">admin.microsoft.com</a> con le tue credenziali amministratore</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Aggiungi il dominio</h4>
                            <p>Naviga su <strong>Impostazioni → Domini → Aggiungi dominio</strong></p>
                            <p>Inserisci il tuo dominio (es: tuodominio.it)</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h4>Scegli il metodo di verifica</h4>
                            <p>Microsoft offre tre opzioni:</p>
                            <ul>
                                <li><strong>Record TXT</strong> (consigliato) - Aggiungi un record TXT al DNS</li>
                                <li><strong>Record MX</strong> - Aggiungi un record MX temporaneo</li>
                                <li><strong>File HTML</strong> - Carica un file sul tuo sito web</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">4</span>
                        <div class="step-content">
                            <h4>Aggiungi il record di verifica</h4>
                            <p>Per il metodo TXT, aggiungi questo record al tuo DNS:</p>
                            <table class="guide-table">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nome/Host</th>
                                    <th>Valore</th>
                                    <th>TTL</th>
                                </tr>
                                <tr>
                                    <td>TXT</td>
                                    <td>@ o vuoto</td>
                                    <td>MS=ms12345678</td>
                                    <td>3600</td>
                                </tr>
                            </table>
                            <small>* Il valore MS= sarà diverso per ogni dominio</small>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">5</span>
                        <div class="step-content">
                            <h4>Verifica il dominio</h4>
                            <p>Dopo aver aggiunto il record, clicca su "Verifica" in Microsoft 365</p>
                            <p>La propagazione DNS può richiedere fino a 48 ore</p>
                        </div>
                    </div>
                </div>

                <div class="tip-box">
                    <h4>💡 Suggerimento</h4>
                    <p>Non rimuovere il record TXT di verifica dopo la conferma. Microsoft lo utilizza periodicamente per verificare che tu sia ancora il proprietario del dominio.</p>
                </div>
            </div>
        </section>

        <!-- Step 2: Configurazione Email -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="configurazione-email">
                <span class="section-icon">2️⃣</span>
                Configurazione Email (Exchange Online)
            </h2>
            
            <div class="guide-content">
                <p>Dopo la verifica del dominio, configura i record DNS per indirizzare le email a Microsoft 365.</p>
                
                <h3>Record MX (Mail Exchange)</h3>
                <p>Il record MX indica dove devono essere consegnate le email per il tuo dominio.</p>
                
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nome/Host</th>
                            <th>Valore/Punta a</th>
                            <th>Priorità</th>
                            <th>TTL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>MX</td>
                            <td>@ o vuoto</td>
                            <td>tuodominio-it.mail.protection.outlook.com</td>
                            <td>0</td>
                            <td>3600</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="warning-box">
                    <h4>⚠️ Importante</h4>
                    <ul>
                        <li>Rimuovi tutti i record MX esistenti prima di aggiungere quello di Microsoft</li>
                        <li>Il valore esatto del record MX sarà fornito nel pannello di amministrazione</li>
                        <li>La priorità deve essere la più bassa (0 o 1)</li>
                    </ul>
                </div>

                <h3>Record SPF</h3>
                <p>Proteggi il tuo dominio dallo spoofing autorizzando Microsoft a inviare email.</p>
                
                <table class="guide-table">
                    <tr>
                        <th>Tipo</th>
                        <th>Nome/Host</th>
                        <th>Valore</th>
                        <th>TTL</th>
                    </tr>
                    <tr>
                        <td>TXT</td>
                        <td>@ o vuoto</td>
                        <td>v=spf1 include:spf.protection.outlook.com -all</td>
                        <td>3600</td>
                    </tr>
                </table>

                <div class="info-box">
                    <h4>SPF con mittenti multipli</h4>
                    <p>Se usi altri servizi per inviare email, combina gli include:</p>
                    <code>v=spf1 include:spf.protection.outlook.com include:_spf.google.com include:mail.zendesk.com -all</code>
                </div>

                <h3>Record Autodiscover</h3>
                <p>Permette ai client di configurarsi automaticamente.</p>
                
                <table class="guide-table">
                    <tr>
                        <th>Tipo</th>
                        <th>Nome/Host</th>
                        <th>Valore</th>
                        <th>TTL</th>
                    </tr>
                    <tr>
                        <td>CNAME</td>
                        <td>autodiscover</td>
                        <td>autodiscover.outlook.com</td>
                        <td>3600</td>
                    </tr>
                </table>

                <h3>Record DKIM</h3>
                <p>Abilita la firma digitale delle email per maggiore sicurezza.</p>
                
                <div class="step-by-step">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>Genera le chiavi DKIM</h4>
                            <p>Nel centro di amministrazione, vai su <strong>Sicurezza → Criteri → DKIM</strong></p>
                            <p>Seleziona il tuo dominio e clicca su "Abilita"</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4>Aggiungi i record CNAME</h4>
                            <p>Microsoft ti fornirà due record CNAME da aggiungere:</p>
                            <table class="guide-table">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Nome</th>
                                    <th>Valore</th>
                                </tr>
                                <tr>
                                    <td>CNAME</td>
                                    <td>selector1._domainkey</td>
                                    <td>selector1-tuodominio-it._domainkey.gtechgroup.onmicrosoft.com</td>
                                </tr>
                                <tr>
                                    <td>CNAME</td>
                                    <td>selector2._domainkey</td>
                                    <td>selector2-tuodominio-it._domainkey.gtechgroup.onmicrosoft.com</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Step 3: Configurazione Servizi -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="configurazione-servizi">
                <span class="section-icon">3️⃣</span>
                Configurazione Altri Servizi
            </h2>
            
            <div class="guide-content">
                <h3>Skype for Business / Teams</h3>
                <p>Per abilitare le funzionalità di comunicazione, aggiungi questi record:</p>
                
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nome</th>
                            <th>Valore</th>
                            <th>Priorità</th>
                            <th>Peso</th>
                            <th>Porta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SRV</td>
                            <td>_sip._tls</td>
                            <td>sipdir.online.lync.com</td>
                            <td>100</td>
                            <td>1</td>
                            <td>443</td>
                        </tr>
                        <tr>
                            <td>SRV</td>
                            <td>_sipfederationtls._tcp</td>
                            <td>sipfed.online.lync.com</td>
                            <td>100</td>
                            <td>1</td>
                            <td>5061</td>
                        </tr>
                    </tbody>
                </table>
                
                <table class="guide-table">
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>Valore</th>
                    </tr>
                    <tr>
                        <td>CNAME</td>
                        <td>sip</td>
                        <td>sipdir.online.lync.com</td>
                    </tr>
                    <tr>
                        <td>CNAME</td>
                        <td>lyncdiscover</td>
                        <td>webdir.online.lync.com</td>
                    </tr>
                </table>

                <h3>Mobile Device Management (MDM)</h3>
                <p>Per gestire dispositivi mobili con Intune:</p>
                
                <table class="guide-table">
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>Valore</th>
                    </tr>
                    <tr>
                        <td>CNAME</td>
                        <td>enterpriseregistration</td>
                        <td>enterpriseregistration.windows.net</td>
                    </tr>
                    <tr>
                        <td>CNAME</td>
                        <td>enterpriseenrollment</td>
                        <td>enterpriseenrollment.manage.microsoft.com</td>
                    </tr>
                </table>
            </div>
        </section>

        <!-- Step 4: Migrazione -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="migrazione">
                <span class="section-icon">4️⃣</span>
                Migrazione delle Email
            </h2>
            
            <div class="guide-content">
                <p>Se stai migrando da un altro provider, Microsoft offre diversi metodi di migrazione.</p>
                
                <div class="migration-options">
                    <div class="migration-card">
                        <h3>Migrazione IMAP</h3>
                        <p><strong>Ideale per:</strong> Gmail, Yahoo, altri provider IMAP</p>
                        <p><strong>Cosa migra:</strong> Solo email (non calendari o contatti)</p>
                        <p><strong>Tempo:</strong> 2-7 giorni</p>
                        <div class="migration-steps">
                            <ol>
                                <li>Crea gli utenti in Microsoft 365</li>
                                <li>Ottieni le password delle caselle di origine</li>
                                <li>Usa il tool di migrazione nel centro admin</li>
                                <li>Monitora il progresso</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="migration-card">
                        <h3>Migrazione Cutover</h3>
                        <p><strong>Ideale per:</strong> Exchange on-premises (< 2000 caselle)</p>
                        <p><strong>Cosa migra:</strong> Email, calendari, contatti</p>
                        <p><strong>Tempo:</strong> 2-3 giorni</p>
                        <div class="migration-steps">
                            <ol>
                                <li>Prepara Exchange on-premises</li>
                                <li>Crea endpoint di migrazione</li>
                                <li>Avvia batch di migrazione</li>
                                <li>Cambia record MX</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="migration-card">
                        <h3>Migrazione Staged</h3>
                        <p><strong>Ideale per:</strong> Exchange 2003/2007 con molti utenti</p>
                        <p><strong>Cosa migra:</strong> Email, calendari, contatti in fasi</p>
                        <p><strong>Tempo:</strong> Settimane/mesi</p>
                        <div class="migration-steps">
                            <ol>
                                <li>Sincronizza directory con Azure AD Connect</li>
                                <li>Migra gruppi di utenti</li>
                                <li>Configura coesistenza</li>
                                <li>Completa migrazione</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="timeline-section">
                    <h3>Timeline migrazione tipica</h3>
                    <div class="migration-timeline">
                        <div class="timeline-phase">
                            <div class="phase-header">
                                <span class="phase-number">1</span>
                                <h4>Preparazione (1-2 settimane)</h4>
                            </div>
                            <ul>
                                <li>Inventario caselle email</li>
                                <li>Pulizia dati non necessari</li>
                                <li>Comunicazione agli utenti</li>
                                <li>Setup Microsoft 365</li>
                            </ul>
                        </div>
                        
                        <div class="timeline-phase">
                            <div class="phase-header">
                                <span class="phase-number">2</span>
                                <h4>Pre-migrazione (2-3 giorni)</h4>
                            </div>
                            <ul>
                                <li>Creazione utenti</li>
                                <li>Assegnazione licenze</li>
                                <li>Test pilota</li>
                                <li>Backup finale</li>
                            </ul>
                        </div>
                        
                        <div class="timeline-phase">
                            <div class="phase-header">
                                <span class="phase-number">3</span>
                                <h4>Migrazione (1-7 giorni)</h4>
                            </div>
                            <ul>
                                <li>Avvio migrazione dati</li>
                                <li>Monitoraggio progresso</li>
                                <li>Risoluzione errori</li>
                                <li>Sincronizzazione delta</li>
                            </ul>
                        </div>
                        
                        <div class="timeline-phase">
                            <div class="phase-header">
                                <span class="phase-number">4</span>
                                <h4>Post-migrazione (1 settimana)</h4>
                            </div>
                            <ul>
                                <li>Cambio record MX</li>
                                <li>Configurazione client</li>
                                <li>Formazione utenti</li>
                                <li>Supporto e ottimizzazione</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Configurazione Client -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="configurazione-client">
                <span class="section-icon">💻</span>
                Configurazione Client Email
            </h2>
            
            <div class="guide-content">
                <h3>Configurazione automatica</h3>
                <p>Con Autodiscover configurato, la maggior parte dei client si configura automaticamente:</p>
                <ol>
                    <li>Apri Outlook o il client email</li>
                    <li>Inserisci l'indirizzo email</li>
                    <li>Inserisci la password</li>
                    <li>Il client configura tutto automaticamente</li>
                </ol>

                <h3>Configurazione manuale</h3>
                <p>Se necessario, usa questi parametri per la configurazione manuale:</p>
                
                <div class="config-cards">
                    <div class="config-card">
                        <h4>IMAP</h4>
                        <table class="config-table">
                            <tr>
                                <td>Server</td>
                                <td>outlook.office365.com</td>
                            </tr>
                            <tr>
                                <td>Porta</td>
                                <td>993</td>
                            </tr>
                            <tr>
                                <td>Sicurezza</td>
                                <td>SSL/TLS</td>
                            </tr>
                            <tr>
                                <td>Autenticazione</td>
                                <td>OAuth2 o password</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="config-card">
                        <h4>POP3</h4>
                        <table class="config-table">
                            <tr>
                                <td>Server</td>
                                <td>outlook.office365.com</td>
                            </tr>
                            <tr>
                                <td>Porta</td>
                                <td>995</td>
                            </tr>
                            <tr>
                                <td>Sicurezza</td>
                                <td>SSL/TLS</td>
                            </tr>
                            <tr>
                                <td>Autenticazione</td>
                                <td>OAuth2 o password</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="config-card">
                        <h4>SMTP</h4>
                        <table class="config-table">
                            <tr>
                                <td>Server</td>
                                <td>smtp.office365.com</td>
                            </tr>
                            <tr>
                                <td>Porta</td>
                                <td>587</td>
                            </tr>
                            <tr>
                                <td>Sicurezza</td>
                                <td>STARTTLS</td>
                            </tr>
                            <tr>
                                <td>Autenticazione</td>
                                <td>OAuth2 o password</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="warning-box">
                    <h4>⚠️ Autenticazione moderna</h4>
                    <p>Microsoft sta dismettendo l'autenticazione base (username/password) a favore di OAuth2.</p>
                    <p>Assicurati che i tuoi client supportino l'autenticazione moderna per evitare interruzioni.</p>
                </div>
            </div>
        </section>

        <!-- Sicurezza e Compliance -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title" id="sicurezza">
                <span class="section-icon">🛡️</span>
                Sicurezza e Compliance
            </h2>
            
            <div class="guide-content">
                <h3>Configurazioni di sicurezza essenziali</h3>
                
                <div class="security-checklist">
                    <div class="security-item">
                        <input type="checkbox" id="mfa">
                        <label for="mfa">
                            <h4>Abilita MFA (Multi-Factor Authentication)</h4>
                            <p>Richiedi un secondo fattore di autenticazione per tutti gli utenti</p>
                            <small>Admin center → Users → Multi-factor authentication</small>
                        </label>
                    </div>
                    
                    <div class="security-item">
                        <input type="checkbox" id="conditional">
                        <label for="conditional">
                            <h4>Configura accesso condizionale</h4>
                            <p>Limita l'accesso basandoti su posizione, dispositivo e rischio</p>
                            <small>Azure AD → Security → Conditional Access</small>
                        </label>
                    </div>
                    
                    <div class="security-item">
                        <input type="checkbox" id="atp">
                        <label for="atp">
                            <h4>Attiva Microsoft Defender</h4>
                            <p>Protezione avanzata da phishing, malware e minacce zero-day</p>
                            <small>Security center → Policies → Safe attachments/links</small>
                        </label>
                    </div>
                    
                    <div class="security-item">
                        <input type="checkbox" id="dlp">
                        <label for="dlp">
                            <h4>Implementa DLP (Data Loss Prevention)</h4>
                            <p>Previeni la condivisione accidentale di informazioni sensibili</p>
                            <small>Compliance center → Data loss prevention</small>
                        </label>
                    </div>
                    
                    <div class="security-item">
                        <input type="checkbox" id="retention">
                        <label for="retention">
                            <h4>Configura retention policy</h4>
                            <p>Definisci per quanto tempo conservare email e documenti</p>
                            <small>Compliance center → Data governance → Retention</small>
                        </label>
                    </div>
                    
                    <div class="security-item">
                        <input type="checkbox" id="audit">
                        <label for="audit">
                            <h4>Abilita audit log</h4>
                            <p>Traccia tutte le attività amministrative e degli utenti</p>
                            <small>Compliance center → Audit → Start recording</small>
                        </label>
                    </div>
                </div>

                <h3>Best practice password</h3>
                <div class="password-policies">
                    <div class="policy-item">
                        <span class="policy-icon">📏</span>
                        <div>
                            <h4>Lunghezza minima: 12 caratteri</h4>
                            <p>Password più lunghe sono esponenzialmente più sicure</p>
                        </div>
                    </div>
                    <div class="policy-item">
                        <span class="policy-icon">🔄</span>
                        <div>
                            <h4>No rotazione obbligatoria</h4>
                            <p>Microsoft ora sconsiglia il cambio password periodico forzato</p>
                        </div>
                    </div>
                    <div class="policy-item">
                        <span class="policy-icon">🚫</span>
                        <div>
                            <h4>Banna password comuni</h4>
                            <p>Utilizza la lista di password vietate di Azure AD</p>
                        </div>
                    </div>
                    <div class="policy-item">
                        <span class="policy-icon">🔐</span>
                        <div>
                            <h4>Richiedi MFA</h4>
                            <p>La seconda autenticazione è più importante della complessità password</p>
                        </div>
                    </div>
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
                                Le email non arrivano dopo il cambio MX
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Verifica propagazione DNS</strong>: Usa nslookup o dig per verificare il record MX</li>
                                <li><strong>Controlla spam/quarantena</strong>: Le email potrebbero essere in quarantena</li>
                                <li><strong>Verifica domini accettati</strong>: Il dominio deve essere configurato come "Authoritative"</li>
                                <li><strong>Controlla connettori</strong>: Se usi connettori personalizzati, verifica la configurazione</li>
                            </ul>
                            <div class="code-block">
                                <code>nslookup -type=mx tuodominio.it</code>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                Outlook non si connette automaticamente
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Verifica Autodiscover</strong>: Il record CNAME deve puntare a autodiscover.outlook.com</li>
                                <li><strong>Test connettività</strong>: Usa testconnectivity.microsoft.com</li>
                                <li><strong>Profilo Outlook</strong>: Ricrea il profilo di Outlook</li>
                                <li><strong>Versione Outlook</strong>: Assicurati di usare una versione supportata</li>
                            </ul>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                Errori di autenticazione SMTP
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>SMTP AUTH</strong>: Deve essere abilitato per l'utente</li>
                                <li><strong>App password</strong>: Se MFA è attivo, potrebbe servire una app password</li>
                                <li><strong>Client moderni</strong>: Usa OAuth2 invece di autenticazione base</li>
                                <li><strong>Licenza</strong>: Verifica che l'utente abbia una licenza Exchange Online</li>
                            </ul>
                            <p>Per abilitare SMTP AUTH per un utente:</p>
                            <div class="code-block">
                                <code>Set-CASMailbox -Identity user@domain.com -SmtpClientAuthenticationDisabled $false</code>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" onclick="toggleAccordion(this)">
                                Teams/Skype non funziona
                                <span class="accordion-icon">▼</span>
                            </button>
                        </h3>
                        <div class="accordion-content">
                            <ul>
                                <li><strong>Record SRV</strong>: Verifica che siano configurati correttamente</li>
                                <li><strong>Firewall</strong>: Le porte necessarie devono essere aperte</li>
                                <li><strong>Licenze</strong>: Gli utenti devono avere licenze Teams/Skype</li>
                                <li><strong>Client aggiornato</strong>: Usa l'ultima versione del client</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Costi e Licenze -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">💰</span>
                Piani e Licenze
            </h2>
            
            <div class="guide-content">
                <div class="plans-comparison">
                    <div class="plan-card">
                        <h3>Business Basic</h3>
                        <div class="plan-price">€5.60/utente/mese</div>
                        <ul class="plan-features">
                            <li>✅ Email con 50GB</li>
                            <li>✅ Teams, SharePoint, OneDrive</li>
                            <li>✅ Office Web Apps</li>
                            <li>❌ Office Desktop</li>
                            <li>✅ 1TB OneDrive</li>
                        </ul>
                    </div>
                    
                    <div class="plan-card featured">
                        <div class="plan-badge">Più popolare</div>
                        <h3>Business Standard</h3>
                        <div class="plan-price">€11.70/utente/mese</div>
                        <ul class="plan-features">
                            <li>✅ Tutto di Basic</li>
                            <li>✅ Office Desktop completo</li>
                            <li>✅ Outlook desktop</li>
                            <li>✅ Access e Publisher (PC)</li>
                            <li>✅ Webinar e registrazione</li>
                        </ul>
                    </div>
                    
                    <div class="plan-card">
                        <h3>Business Premium</h3>
                        <div class="plan-price">€20.60/utente/mese</div>
                        <ul class="plan-features">
                            <li>✅ Tutto di Standard</li>
                            <li>✅ Sicurezza avanzata</li>
                            <li>✅ Device management</li>
                            <li>✅ Information protection</li>
                            <li>✅ Defender for Business</li>
                        </ul>
                    </div>
                </div>

                <div class="info-box">
                    <h4>📌 Note sui prezzi</h4>
                    <ul>
                        <li>Prezzi indicativi soggetti a variazioni</li>
                        <li>Fatturazione annuale o mensile disponibile</li>
                        <li>Sconti volume per 300+ licenze (Enterprise)</li>
                        <li>Trial gratuito di 30 giorni disponibile</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Risorse Utili -->
        <section class="guide-section" data-aos="fade-up">
            <h2 class="section-title">
                <span class="section-icon">📚</span>
                Risorse Utili
            </h2>
            
            <div class="guide-content">
                <div class="resources-grid">
                    <a href="https://admin.microsoft.com" target="_blank" rel="noopener" class="resource-card">
                        <div class="resource-icon">⚙️</div>
                        <h4>Admin Center</h4>
                        <p>Pannello di amministrazione Microsoft 365</p>
                    </a>
                    
                    <a href="https://docs.microsoft.com/microsoft-365/" target="_blank" rel="noopener" class="resource-card">
                        <div class="resource-icon">📖</div>
                        <h4>Documentazione</h4>
                        <p>Guide ufficiali Microsoft</p>
                    </a>
                    
                    <a href="https://testconnectivity.microsoft.com" target="_blank" rel="noopener" class="resource-card">
                        <div class="resource-icon">🔍</div>
                        <h4>Test Connettività</h4>
                        <p>Diagnostica problemi email</p>
                    </a>
                    
                    <a href="https://status.office365.com" target="_blank" rel="noopener" class="resource-card">
                        <div class="resource-icon">📊</div>
                        <h4>Service Health</h4>
                        <p>Stato servizi Microsoft 365</p>
                    </a>
                    
                    <a href="https://portal.azure.com" target="_blank" rel="noopener" class="resource-card">
                        <div class="resource-icon">☁️</div>
                        <h4>Azure Portal</h4>
                        <p>Gestione avanzata Azure AD</p>
                    </a>
                    
                    <a href="/" class="resource-card">
                        <div class="resource-icon">🔧</div>
                        <h4>Verifica DNS</h4>
                        <p>Controlla la tua configurazione</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="guide-cta" data-aos="fade-up">
            <div class="cta-content">
                <h2>Verifica la configurazione del tuo dominio</h2>
                <p>Usa il nostro strumento gratuito per controllare che tutti i record DNS siano configurati correttamente</p>
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
/* Microsoft 365 Logo */
.microsoft-logo {
    margin-bottom: 2rem;
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.feature-item {
    text-align: center;
}

.feature-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}

/* Prerequisites */
.prerequisites-list {
    margin: 2rem 0;
}

.prerequisite-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: 12px;
}

.check-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    background: var(--success-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Migration Options */
.migration-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.migration-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
}

.migration-card h3 {
    color: var(--primary-color);
    margin-top: 0;
}

.migration-steps {
    margin-top: 1.5rem;
}

.migration-steps ol {
    margin: 0;
    padding-left: 1.5rem;
}

/* Migration Timeline */
.migration-timeline {
    margin: 2rem 0;
}

.timeline-phase {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
}

.phase-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.phase-number {
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

/* Config Cards */
.config-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.config-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.config-card h4 {
    margin-top: 0;
    color: var(--primary-color);
}

.config-table {
    width: 100%;
    margin-top: 1rem;
}

.config-table td:first-child {
    font-weight: 600;
    padding-right: 1rem;
}

/* Security Checklist */
.security-checklist {
    margin: 2rem 0;
}

.security-item {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.security-item input[type="checkbox"] {
    width: 24px;
    height: 24px;
    margin-right: 1rem;
    cursor: pointer;
}

.security-item label {
    display: flex;
    align-items: start;
    cursor: pointer;
}

.security-item h4 {
    margin: 0 0 0.5rem 0;
}

.security-item small {
    display: block;
    margin-top: 0.5rem;
    color: var(--text-muted);
    font-family: monospace;
}

/* Password Policies */
.password-policies {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.policy-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--card-bg);
    border-radius: 12px;
}

.policy-icon {
    font-size: 2rem;
}

/* Plans Comparison */
.plans-comparison {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.plan-card {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
}

.plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.plan-card.featured {
    border-color: var(--primary-color);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--primary-gradient);
    color: white;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.plan-card h3 {
    margin-top: 0;
    text-align: center;
}

.plan-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    text-align: center;
    margin: 1rem 0;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-light);
}

.plan-features li:last-child {
    border-bottom: none;
}

/* Resources Grid */
.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.resource-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.resource-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.resource-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.resource-card h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
}

.resource-card p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .migration-options {
        grid-template-columns: 1fr;
    }
    
    .config-cards {
        grid-template-columns: 1fr;
    }
    
    .plans-comparison {
        grid-template-columns: 1fr;
    }
    
    .resources-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}
</style>

<?php require_once ABSPATH . 'templates/footer.php'; ?>
