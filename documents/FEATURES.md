# Documentazione Funzionalità - Controllo Domini

## Indice

1. [Panoramica Funzionalità](#panoramica-funzionalità)
2. [DNS Record Analysis](#dns-record-analysis)
3. [WHOIS Lookup](#whois-lookup)
4. [Blacklist Checking](#blacklist-checking)
5. [Cloud Service Detection](#cloud-service-detection)
6. [SSL/TLS Certificate Analysis](#ssltls-certificate-analysis)
7. [Security Headers Analysis](#security-headers-analysis)
8. [Technology Stack Detection](#technology-stack-detection)
9. [Social Media Meta Analysis](#social-media-meta-analysis)
10. [Performance Analysis](#performance-analysis)
11. [SEO Analysis](#seo-analysis)
12. [Redirect Chain Analysis](#redirect-chain-analysis)
13. [Port Scanning](#port-scanning)
14. [Guide Educational](#guide-educational)

## Panoramica Funzionalità

Controllo Domini offre **12 strumenti di analisi** principali più **3 guide educative** per professionisti web, SEO specialist, e security analyst.

### Matrice Funzionalità

| Funzionalità | Categoria | Livello | File Principale |
|--------------|-----------|---------|-----------------|
| DNS Analysis | Network | Core | dns-functions.php |
| WHOIS Lookup | Domain | Core | whois-functions.php |
| Blacklist Check | Email Security | Core | blacklist-functions.php |
| Cloud Detection | Infrastructure | Advanced | cloud-detection.php |
| SSL/TLS Analysis | Security | Advanced | ssl-certificate.php |
| Security Headers | Security | Advanced | security-headers.php |
| Tech Detection | Analysis | Advanced | technology-detection.php |
| Social Meta | SEO | Advanced | social-meta-analysis.php |
| Performance | Optimization | Advanced | performance-analysis.php |
| SEO Analysis | SEO | Advanced | robots-sitemap.php |
| Redirect Analysis | SEO | Advanced | redirect-analysis.php |
| Port Scanning | Security | Advanced | port-scanner.php |

## DNS Record Analysis

### Descrizione

Analisi completa dei record DNS di un dominio, con supporto per tutti i principali tipi di record, rilevamento sottodomini comuni, e metriche di performance.

### File: `includes/dns-functions.php` (586 linee, ~15 funzioni)

### Caratteristiche

#### 1. Tipi di Record Supportati

- **A** - IPv4 address
- **AAAA** - IPv6 address
- **MX** - Mail Exchange
- **TXT** - Text records (SPF, DKIM, DMARC, verification)
- **CNAME** - Canonical Name
- **NS** - Name Server
- **SOA** - Start of Authority
- **SRV** - Service records
- **CAA** - Certification Authority Authorization

#### 2. Analisi Sottodomini

Controlla automaticamente sottodomini comuni:
- www
- mail
- ftp
- smtp
- pop
- imap
- webmail
- remote
- blog
- shop
- admin

#### 3. Metriche Performance

- Tempo di risposta DNS (ms)
- TTL (Time To Live) per ogni record
- Confronto performance tra query
- Rilevamento record duplicati

#### 4. Analisi DNSSEC

- Verifica presenza DNSSEC
- Controllo validità firma
- DS records

### Funzioni Principali

```php
// Recupera tutti i record DNS
getAllDnsRecords($domain)
  → Ritorna array con tutti i tipi di record

// Misura tempo risposta DNS
measureDnsResponseTime($domain)
  → Ritorna tempo in millisecondi

// Analisi DNSSEC
analyzeDNSSEC($domain)
  → Ritorna stato DNSSEC

// Controlla sottodomini comuni
getCommonSubdomains($domain)
  → Ritorna array sottodomini trovati
```

### Output

```
✅ 15 record DNS trovati
⏱️ Tempo risposta: 145ms
📊 TTL medio: 3600s (1 ora)
🔒 DNSSEC: Abilitato e valido
🌐 2 sottodomini rilevati (www, mail)
```

### Uso via Web

```
POST /dns-check.php
Parameters:
  - domain: example.com
  - analyze: 1
```

### Use Cases

- Verifica propagazione DNS dopo migrazione
- Controllo configurazione email (MX, SPF)
- Audit record DNS per sicurezza
- Risoluzione problemi connettività

---

## WHOIS Lookup

### Descrizione

Recupera informazioni WHOIS complete di un dominio attraverso query dirette ai server WHOIS autoritativi, con sistema di fallback multi-livello.

### File: `includes/whois-functions.php` (1,098 linee, ~20 funzioni)

### Caratteristiche

#### 1. Metodi di Recupero

**Priorità Fallback**:
1. **Socket diretto** (primario) - Connessione TCP:43
2. **shell_exec** (fallback) - Comando whois di sistema
3. **HTTP API** (ultimo fallback) - Servizi web WHOIS

#### 2. TLD Supportati

20+ TLD configurati:
- Generic: com, net, org, info, biz
- Europe: it, eu, de, fr, uk, nl, es, ch, at, be
- Americas: us, ca, mx, br
- Asia: jp, cn, au, in
- New gTLD: io, me, tv, cc, ws, mobi, pro
- Special: edu, gov, mil

#### 3. Dati Estratti

- **Registrar**: Nome, WHOIS server, URL, contatti abuse
- **Registrante**: Nome, organizzazione, email (se non protetto GDPR)
- **Date**: Creazione, ultimo aggiornamento, scadenza
- **Nameserver**: Lista nameserver autoritativi
- **Status**: Domain status codes
- **DNSSEC**: Stato e DS records
- **Privacy**: Rilevamento protezione privacy

### Funzioni Principali

```php
// Lookup WHOIS principale
getWhoisInfo($domain)
  → Ritorna array completo dati WHOIS

// Query via socket
getWhoisViaSocket($domain, $server)
  → Connessione diretta porta 43

// Parsing dati WHOIS
parseWhoisData($whoisText)
  → Estrae campi strutturati

// Estrae data scadenza
extractExpiryDate($whoisText)
  → Ritorna data scadenza formattata

// Calcola giorni a scadenza
getDaysUntilExpiry($expiryDate)
  → Ritorna numero giorni rimanenti
```

### Output

```
📋 Dominio: example.com
🏢 Registrar: Example Registrar, Inc.
📅 Creato: 14/08/1995
🔄 Aggiornato: 14/08/2023
⏰ Scade: 13/08/2026 (278 giorni)
🌐 Nameserver: ns1.example.com, ns2.example.com
🔒 DNSSEC: Abilitato
🛡️ Privacy: Attiva
```

### Gestione GDPR

Con GDPR molti dati registrante sono nascosti:

```
Registrant: Privacy Protected
Organization: Privacy Service
Email: contact@privacyservice.com
```

L'applicazione rileva e segnala quando privacy è attiva.

### Use Cases

- Controllo scadenza domini
- Verifica proprietà domini
- Ricerca registrar di un dominio
- Audit configurazione DNSSEC

---

## Blacklist Checking

### Descrizione

Controlla se un dominio o IP è presente in 30+ blacklist email (DNSBL - DNS-based Blacklist), con calcolo reputation score e raccomandazioni.

### File: `includes/blacklist-functions.php` (852 linee, ~15 funzioni)

### Caratteristiche

#### 1. Server DNSBL

30+ server controllati:
- **Spamhaus** (ZEN, SBL, XBL, PBL)
- **SpamCop**
- **Barracuda**
- **SORBS**
- **UCEPROTECT** (Level 1, 2, 3)
- **SpamCannibal**
- **PSBL**
- **DroneBL**
- **abuse.ch**
- Altri 15+ server

#### 2. Modalità Checking

**Parallel Mode** (default):
- Query simultanee via cURL multi
- Timeout indipendenti per server
- Completamento in ~2-5 secondi

**Sequential Mode**:
- Query seriali
- Più lento ma più affidabile
- Utile se parallel ha problemi

#### 3. Scoring System

**Reputation Score** (0-100):
- 100: Nessuna listing (Excellent)
- 90-99: 1 listing minore (Good)
- 70-89: Multiple listing minori (Warning)
- 50-69: Listing maggiori (Poor)
- 0-49: Multiple listing maggiori (Critical)

#### 4. Controlli Multipli

- IP principale dominio
- Variante www.dominio
- Tutti gli IP A e AAAA rilevati

### Funzioni Principali

```php
// Check blacklist principale
checkBlacklists($ips, $domain)
  → Ritorna risultati completi

// Check parallelo (veloce)
checkBlacklistsParallel($ips)
  → cURL multi per performance

// Check sequenziale (affidabile)
checkBlacklistsSequential($ips)
  → Query seriali

// Calcola reputation
calculateReputation($results)
  → Ritorna score 0-100

// Statistiche
calculateBlacklistStatistics($results)
  → Ritorna statistiche dettagliate
```

### Output

```
🎯 IP Controllati: 93.184.216.34
📊 Server DNSBL: 30
✅ Clean: 30
❌ Listed: 0
⭐ Reputation Score: 100/100 (Excellent)
🛡️ Status: Clean - Not listed on any blacklist
```

### Output con Listing

```
⚠️ Listed on 2 blacklists:
  • zen.spamhaus.org - Listed (127.0.0.2)
  • bl.spamcop.net - Listed (127.0.0.2)

⭐ Reputation Score: 75/100 (Warning)
💡 Recommendation: Contact blacklist operators to delist
```

### Use Cases

- Diagnostica problemi email delivery
- Monitoraggio reputazione IP
- Pre-check prima migrazione email
- Verifica IP server prima acquisto

---

## Cloud Service Detection

### Descrizione

Rileva automaticamente servizi cloud, provider email, hosting, CDN e piattaforme utilizzate da un dominio, con confidence scoring.

### File: `includes/cloud-detection.php` (999 linee, ~15 funzioni)

### Caratteristiche

#### 1. Servizi Rilevati

**Email Services**:
- Microsoft 365 / Office 365
- Google Workspace
- Zoho Mail
- ProtonMail
- Altri provider email

**Hosting Providers**:
- Cloudflare
- AWS (Route53, EC2, CloudFront)
- Google Cloud
- Microsoft Azure
- DigitalOcean
- Linode
- Aruba
- OVH

**CDN Providers**:
- Cloudflare
- AWS CloudFront
- Akamai
- Fastly
- KeyCDN

**Cloud Platforms**:
- AWS
- Google Cloud Platform
- Microsoft Azure
- Alibaba Cloud

#### 2. Metodi di Rilevamento

**DNS Analysis**:
- MX records (email)
- TXT records (SPF, verification)
- NS records (nameserver)
- CNAME patterns

**HTTP Analysis**:
- Server headers
- X-Powered-By headers
- Custom headers (CF-RAY, X-Azure, ecc.)

**IP Range Analysis**:
- IP ownership lookup
- ASN (Autonomous System Number)
- Range matching

#### 3. Confidence Scoring

```
95-100%: Certezza molto alta
85-94%: Certezza alta
70-84%: Probabile
50-69%: Possibile
<50%: Incerto
```

### Funzioni Principali

```php
// Rilevamento principale
identifyCloudServices($domain)
  → Ritorna array servizi rilevati

// Email services
detectEmailServices($domain, $dnsRecords)
  → Rileva provider email

// Hosting providers
detectHostingProviders($domain, $dnsRecords)
  → Rileva hosting

// CDN providers
detectCDNProviders($domain, $httpHeaders)
  → Rileva CDN

// Confidence scoring
calculateConfidenceScores($indicators)
  → Calcola scoring confidenza
```

### Output

```
📧 Email Service:
  • Microsoft 365 (95% confidence)
    - MX: example-com.mail.protection.outlook.com
    - SPF: include:spf.protection.outlook.com

🌐 Hosting Provider:
  • Cloudflare (90% confidence)
    - NS: ns1.cloudflare.com, ns2.cloudflare.com
    - IP Range: Cloudflare (104.16.x.x)

🚀 CDN Provider:
  • Cloudflare CDN (95% confidence)
    - Header: CF-RAY present
    - IP: Cloudflare network

☁️ Cloud Platform:
  • AWS (70% confidence)
    - Services: Route53, CloudFront
```

### Use Cases

- Audit infrastruttura dominio
- Migrazione planning
- Competitor analysis
- Security assessment

---

## SSL/TLS Certificate Analysis

### Descrizione

Analisi completa certificato SSL/TLS, catena certificati, protocolli supportati, cipher suite, vulnerabilità note, e assegnazione grade A-F.

### File: `includes/ssl-certificate.php` (731 linee, ~12 funzioni)

### Caratteristiche

#### 1. Analisi Certificato

**Dati Estratti**:
- Subject (CN, O, C)
- Issuer (CA)
- Validità (from/to)
- Giorni rimanenti
- Serial number
- Signature algorithm
- Public key bits (2048, 4096)
- SAN (Subject Alternative Names)

#### 2. Catena Certificati

- Root CA
- Intermediate CA
- End-entity certificate
- Validazione catena completa

#### 3. Protocolli Supportati

Test protocolli:
- ❌ SSLv2 (deprecated)
- ❌ SSLv3 (deprecated)
- ❌ TLS 1.0 (deprecated)
- ⚠️ TLS 1.1 (weak)
- ✅ TLS 1.2 (good)
- ✅ TLS 1.3 (excellent)

#### 4. Cipher Suites

Analizza cipher supportati:
- ECDHE (Perfect Forward Secrecy)
- AES-256-GCM
- ChaCha20-Poly1305
- Weak ciphers (RC4, 3DES, MD5)

#### 5. Vulnerability Checks

Controlla vulnerabilità note:
- **Heartbleed** (CVE-2014-0160)
- **POODLE** (CVE-2014-3566)
- **BEAST** (CVE-2011-3389)
- **CRIME** (CVE-2012-4929)
- **FREAK** (CVE-2015-0204)
- **Logjam** (CVE-2015-4000)

#### 6. Grade System

```
A+ : Perfect configuration
A  : Strong security
B  : Good security, minor issues
C  : Adequate security, multiple issues
D  : Weak security
F  : Critically weak, major vulnerabilities
```

### Funzioni Principali

```php
// Analisi completa SSL
analyzeSSLCertificate($domain, $port = 443)
  → Ritorna analisi completa

// Info certificato
getSSLCertificateInfo($domain)
  → Estrae dati certificato

// Catena certificati
getSSLChain($domain)
  → Valida catena

// Test protocolli
checkSSLProtocols($domain)
  → Testa tutti i protocolli

// Test cipher
checkCipherSuites($domain)
  → Analizza cipher

// Vulnerabilità
checkSSLVulnerabilities($domain)
  → Controlla CVE note

// Calcola grade
calculateSSLScore($cert, $protocols, $ciphers, $vulns)
  → Assegna grade A-F
```

### Output

```
🔒 Certificato SSL/TLS

📜 Subject: example.com
🏢 Issuer: DigiCert SHA2 Secure Server CA
✅ Valido da: 01/01/2024
⏰ Valido fino: 01/01/2025 (61 giorni)
🔑 Public Key: RSA 2048 bit
📋 SAN: example.com, www.example.com

⛓️ Catena Certificati: ✅ Valida

🔐 Protocolli:
  ❌ SSLv2, SSLv3, TLS 1.0, TLS 1.1
  ✅ TLS 1.2, TLS 1.3

🔑 Cipher Suites: (Top 3)
  • TLS_AES_256_GCM_SHA384
  • TLS_CHACHA20_POLY1305_SHA256
  • TLS_AES_128_GCM_SHA256

🛡️ Vulnerabilità:
  ✅ Nessuna vulnerabilità nota rilevata

⭐ Grade: A+
💯 Score: 100/100
```

### Use Cases

- Pre-check prima scadenza certificato
- Security audit SSL/TLS
- Conformità PCI DSS
- Troubleshooting connessioni HTTPS

---

## Security Headers Analysis

### Descrizione

Analizza HTTP security headers di un sito web, assegna score pesato, e fornisce raccomandazioni per migliorare sicurezza.

### File: `includes/security-headers.php` (578 linee, ~10 funzioni)

### Caratteristiche

#### 1. Headers Analizzati

**Strict-Transport-Security (HSTS)**:
- Peso: 10 punti
- Controlla max-age, includeSubDomains, preload

**Content-Security-Policy (CSP)**:
- Peso: 10 punti
- Analizza direttive
- Rileva unsafe-inline, unsafe-eval

**X-Frame-Options**:
- Peso: 10 punti
- Valori: DENY, SAMEORIGIN

**X-Content-Type-Options**:
- Peso: 10 punti
- Valore: nosniff

**Referrer-Policy**:
- Peso: 10 punti
- Policy privacy-friendly

**Permissions-Policy**:
- Peso: 10 punti
- Controllo feature browser

#### 2. Scoring System

```
Score Totale: 60 punti massimi
Percentuale: (score / 60) * 100

90-100%: A (Excellent)
80-89%:  B (Good)
70-79%:  C (Adequate)
60-69%:  D (Weak)
<60%:    F (Poor)
```

### Funzioni Principali

```php
// Analisi completa headers
analyzeSecurityHeaders($url)
  → Ritorna analisi + score

// Fetch headers HTTP
getHttpHeaders($url)
  → Recupera headers via cURL

// Valida HSTS
validateHSTS($value)
  → Controlla configurazione HSTS

// Valida CSP
validateCSP($value)
  → Analizza Content-Security-Policy

// Calcola score
calculateSecurityScore($headers)
  → Ritorna score + grade
```

### Output

```
🔐 Security Headers Analysis

URL: https://example.com

✅ Strict-Transport-Security: 10/10
   max-age=31536000; includeSubDomains; preload

⚠️ Content-Security-Policy: 8/10
   Warning: 'unsafe-inline' in script-src
   Recommendation: Remove unsafe-inline

✅ X-Frame-Options: 10/10
   SAMEORIGIN

✅ X-Content-Type-Options: 10/10
   nosniff

✅ Referrer-Policy: 10/10
   strict-origin-when-cross-origin

❌ Permissions-Policy: 0/10
   Missing
   Recommendation: Add Permissions-Policy header

📊 Total Score: 48/60 (80%)
⭐ Grade: B (Good)

💡 Recommendations:
  1. Improve CSP - remove unsafe-inline
  2. Add Permissions-Policy header
```

### Use Cases

- Security audit siti web
- Conformità OWASP
- Pre-deploy security check
- Penetration testing

---

## Technology Stack Detection

### Descrizione

Rileva framework, CMS, librerie JavaScript, server web, linguaggi e servizi utilizzati da un sito attraverso analisi HTTP headers, HTML, JavaScript, CSS.

### File: `includes/technology-detection.php` (1,190 linee, ~18 funzioni)

### Caratteristiche

#### 1. Categorie Rilevate

**Web Server**:
- Apache, Nginx, IIS, LiteSpeed
- Versioni quando disponibili

**Programming Languages**:
- PHP, Python, Node.js, Ruby, ASP.NET
- Framework: Laravel, Symfony, Django, Rails, Express

**Frontend Frameworks**:
- React, Vue.js, Angular, Svelte
- jQuery, Lodash, Axios

**CMS**:
- WordPress, Drupal, Joomla, Magento
- Versioni e plugin rilevabili

**CSS Frameworks**:
- Bootstrap, Tailwind CSS, Foundation
- Material UI, Bulma

**Analytics & Marketing**:
- Google Analytics (UA / GA4)
- Facebook Pixel
- Hotjar, Matomo

**CDN & Services**:
- Cloudflare, AWS CloudFront
- Google Tag Manager
- reCAPTCHA

#### 2. Metodi di Rilevamento

**HTTP Headers**:
```
Server: nginx/1.24.0
X-Powered-By: PHP/8.2.0
X-Generator: WordPress 6.4
```

**HTML Meta Tags**:
```html
<meta name="generator" content="WordPress 6.4">
<meta name="framework" content="Laravel">
```

**JavaScript Analysis**:
```javascript
// React detection
window.React !== undefined
document.querySelector('[data-reactroot]')

// Vue.js detection
window.Vue !== undefined
document.querySelector('[data-v-]')
```

**CSS Analysis**:
```css
/* Bootstrap detection */
.container, .row, .col-*

/* Tailwind detection */
utility classes pattern
```

**External Resources**:
```html
<script src="https://cdn.jsdelivr.net/npm/vue@3"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css">
```

### Funzioni Principali

```php
// Rilevamento principale
detectTechnologyStack($url)
  → Ritorna stack completo

// Analizza headers HTTP
analyzeHttpHeaders($headers)
  → Rileva da headers

// Analizza HTML
analyzeHtml($html)
  → Parsing HTML per framework

// Analizza JavaScript
analyzeJavaScript($html)
  → Rileva librerie JS

// Analizza CSS
analyzeCss($html)
  → Rileva framework CSS

// Analizza risorse esterne
analyzeExternalResources($html)
  → Rileva CDN e librerie
```

### Output

```
🔧 Technology Stack Detection

URL: https://example.com

🖥️ Web Server:
  • nginx 1.24.0

💻 Programming Language:
  • PHP 8.2 (90% confidence)

🎨 Backend Framework:
  • Laravel 10.x (85% confidence)

⚛️ Frontend Framework:
  • Vue.js 3.3 (95% confidence)

📦 JavaScript Libraries:
  • jQuery 3.7.1
  • Axios 1.5.0
  • Bootstrap 5.3.0

🎨 CSS Framework:
  • Bootstrap 5.3.0

📊 Analytics:
  • Google Analytics 4 (G-XXXXXXXXX)

🌐 CDN:
  • Cloudflare (95% confidence)

🔒 Security Services:
  • Cloudflare DDoS Protection
  • Google reCAPTCHA v3
```

### Use Cases

- Competitor analysis
- Technology audit
- Migration planning
- Security assessment (outdated software)

---

*[Continua con le altre funzionalità... Per motivi di spazio, fornisco una versione condensata delle restanti]*

## Social Media Meta Analysis

Analizza meta tag Open Graph, Twitter Cards, Schema.org. Genera preview per Facebook, Twitter, LinkedIn, WhatsApp.

**File**: `social-meta-analysis.php` (1,037 linee)

**Output**: Preview social, validation, issue detection

---

## Performance Analysis

Analizza Core Web Vitals, risorse pagina, waterfall timing, ottimizzazione immagini/JS/CSS, caching, compression.

**File**: `performance-analysis.php` (1,107 linee)

**Metriche**: LCP, FID, CLS, Total Load Time, Page Size, # Requests

---

## SEO Analysis

Analizza robots.txt, sitemap.xml, crawlability, directives, SEO scoring.

**File**: `robots-sitemap.php` (825 linee)

**Output**: Validation robots.txt, sitemap parsing, SEO score

---

## Redirect Chain Analysis

Segue catene redirect HTTP, rileva HTTP→HTTPS, www redirects, canonical tags, problemi.

**File**: `redirect-analysis.php` (822 linee)

**Output**: Redirect chain completa, issues, raccomandazioni

---

## Port Scanning

Scansiona porte comuni (21, 22, 25, 80, 443, 3306, ecc.), identifica servizi, vulnerabilità note, risk assessment.

**File**: `port-scanner.php` (869 linee)

**Output**: Porte aperte/chiuse, servizi rilevati, vulnerability warnings

---

## Guide Educational

### 1. SPF, DKIM, DMARC Guide

**Pagina**: `spf-dkim-dmarc.php`

Guida completa autenticazione email:
- Cos'è SPF e come configurarlo
- DKIM signatures
- DMARC policy
- Esempi record DNS
- Best practices

### 2. Microsoft 365 Setup Guide

**Pagina**: `setup-microsoft-365.php`

Guida setup M365:
- Configurazione DNS per M365
- MX records
- TXT records per verifica
- SPF, DKIM, DMARC per M365
- Step-by-step wizard

### 3. DNS Guide

**Pagina**: `dns-guide.php`

Documentazione completa DNS:
- Tipi di record DNS
- Come funziona DNS
- TTL e propagazione
- Best practices sicurezza DNS
- DNSSEC

---

**Ultimo aggiornamento**: Novembre 2025
**Versione documentazione**: 1.0
