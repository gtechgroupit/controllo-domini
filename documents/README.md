# Controllo Domini - Documentazione Progetto

## Panoramica

**Controllo Domini** è uno strumento professionale di analisi domini sviluppato da G Tech Group. L'applicazione fornisce un'analisi completa e dettagliata di domini web attraverso una suite di strumenti integrati che coprono DNS, WHOIS, sicurezza, performance e SEO.

### Informazioni Progetto

- **Nome**: Controllo Domini
- **Versione**: 4.0
- **Autore**: G Tech Group
- **URL**: https://controllodomini.it
- **Licenza**: Proprietaria
- **Linguaggio**: PHP 7.4+
- **Linee di Codice**: ~14,757 linee di PHP
- **Funzioni Totali**: 206+ funzioni distribuite in 13 moduli

## Caratteristiche Principali

### 🔍 Analisi Complete

1. **DNS Record Analysis**
   - Query di tutti i tipi di record DNS (A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA)
   - Analisi TTL e metriche di performance
   - Rilevamento automatico sottodomini
   - Misurazione tempi di risposta
   - Rilevamento record duplicati

2. **WHOIS Lookup**
   - Informazioni registrar e registrante
   - Date di registrazione e scadenza
   - Dettagli nameserver
   - Validazione DNSSEC
   - Metodi multipli di recupero dati (socket, shell_exec, fallback web)

3. **Blacklist Checking**
   - Controllo su 30+ server DNSBL
   - Scoring reputazione IP
   - Modalità controllo parallela e sequenziale
   - Calcolo statistiche dettagliate

4. **Cloud Service Detection**
   - Microsoft 365/Office 365
   - Google Workspace
   - AWS, Azure, Google Cloud
   - Provider CDN (Cloudflare, CloudFront, ecc.)
   - Provider hosting
   - Scoring di confidenza

5. **SSL/TLS Certificate Analysis**
   - Validità certificati
   - Analisi catena certificati
   - Supporto protocolli SSL/TLS
   - Analisi cipher suite
   - Controllo vulnerabilità
   - Assegnazione grade (A-F)

6. **HTTP Security Headers**
   - Analisi HSTS, CSP, X-Frame-Options
   - Sistema di scoring pesato
   - Raccomandazioni conformità

7. **Technology Stack Detection**
   - Framework web
   - Librerie JavaScript/CSS
   - CMS detection
   - Analisi sicurezza stack tecnologico

8. **Social Media Meta Tags**
   - Open Graph tags
   - Twitter Cards
   - Schema.org structured data
   - Generazione preview per piattaforme social

9. **Performance Analysis**
   - Core Web Vitals
   - Analisi risorse pagina
   - Ottimizzazione immagini
   - Strategia caching
   - Analisi compressione
   - Scoring performance

10. **SEO Analysis**
    - Analisi robots.txt
    - Parsing sitemap.xml
    - Crawlability analysis
    - Scoring SEO

11. **Redirect Chain Analysis**
    - Tracciamento catene redirect
    - Validazione tag canonical
    - Rilevamento problemi

12. **Port Scanning**
    - Scansione porte comuni
    - Identificazione servizi
    - Rilevamento vulnerabilità note
    - Valutazione rischi

## Architettura

### Stack Tecnologico

**Backend:**
- PHP 7.4+ (Procedural, no OOP)
- Apache con mod_rewrite
- Estensioni richieste: json, curl, mbstring, openssl

**Frontend:**
- HTML5 semantico
- CSS3 con variabili CSS
- JavaScript Vanilla (ES6+)
- AOS (Animate On Scroll)
- No jQuery

**Pattern Architetturale:**
- Applicazione stateless (no database)
- Struttura simil-MVC con templates
- Organizzazione modulare basata su funzioni
- Tutte le query sono verso servizi esterni

### Struttura Directory

```
controllo-domini/
├── assets/              # CSS, JS, immagini
├── config/              # Configurazione globale
├── includes/            # 13 moduli funzionali (206+ funzioni)
├── templates/           # Header e footer
├── documents/           # Documentazione progetto (questa cartella)
├── *.php                # Pagine principali
├── .htaccess            # Configurazione Apache
└── robots.txt           # Configurazione SEO
```

## Quick Start

### Requisiti Sistema

- PHP 7.4 o superiore
- Apache con mod_rewrite abilitato
- Estensioni PHP: json, curl, mbstring, openssl
- Accesso DNS per query esterne
- Accesso socket per connessioni WHOIS

### Installazione Base

```bash
# Clone repository
git clone [repository-url]

# Configura permessi
chmod 755 controllo-domini
chmod 644 controllo-domini/config/config.php

# Configura Apache virtual host
# Abilita mod_rewrite

# Visita l'applicazione
http://localhost/controllo-domini
```

Vedi [INSTALLATION.md](INSTALLATION.md) per istruzioni dettagliate.

## Documentazione

Questa cartella `documents/` contiene la documentazione completa del progetto:

- **[README.md](README.md)** - Questo file, panoramica generale
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architettura sistema e design pattern
- **[API.md](API.md)** - Documentazione API REST v2.0
- **[INSTALLATION.md](INSTALLATION.md)** - Guida installazione e setup
- **[CONFIGURATION.md](CONFIGURATION.md)** - Configurazione e personalizzazione
- **[FEATURES.md](FEATURES.md)** - Documentazione dettagliata funzionalità
- **[SECURITY.md](SECURITY.md)** - Sicurezza e best practices
- **[DEVELOPMENT.md](DEVELOPMENT.md)** - Guida per sviluppatori
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment e produzione

## Funzionalità Chiave

### Per Professionisti Web
- Analisi DNS completa con metriche di performance
- Validazione configurazioni email (SPF, DKIM, DMARC)
- Rilevamento provider cloud e servizi hosting
- Analisi sicurezza SSL/TLS

### Per SEO Specialist
- Analisi meta tag social media
- Controllo robots.txt e sitemap
- Analisi redirect e canonical
- Performance metrics e Core Web Vitals

### Per Security Analyst
- Controllo blacklist email
- Scansione porte e servizi
- Analisi security headers HTTP
- Rilevamento vulnerabilità SSL/TLS
- Technology stack fingerprinting

### Per Sviluppatori
- API REST v2.0 (documentata)
- Codice modulare e ben organizzato
- 206+ funzioni riutilizzabili
- Nessuna dipendenza esterna pesante

## Statistiche Codebase

| Categoria | Valore |
|-----------|--------|
| Linee di codice PHP totali | ~14,757 |
| Moduli funzionali | 13 |
| Funzioni totali | 206+ |
| Pagine principali | 12 |
| File include | 13 |
| Server WHOIS supportati | 20+ TLD |
| Server DNSBL | 30+ |
| Tipi record DNS | 9 (A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA) |

## File Principali

### Moduli Core (`/includes/`)

| File | Linee | Funzioni | Descrizione |
|------|-------|----------|-------------|
| utilities.php | 1,160 | 36+ | Utilità generali |
| dns-functions.php | 586 | ~15 | Analisi DNS |
| whois-functions.php | 1,098 | ~20 | Lookup WHOIS |
| blacklist-functions.php | 852 | ~15 | Controllo blacklist |
| cloud-detection.php | 999 | ~15 | Rilevamento servizi cloud |
| ssl-certificate.php | 731 | ~12 | Analisi SSL/TLS |
| security-headers.php | 578 | ~10 | Analisi security headers |
| technology-detection.php | 1,190 | ~18 | Rilevamento tecnologie |
| social-meta-analysis.php | 1,037 | ~15 | Analisi meta tag social |
| performance-analysis.php | 1,107 | ~20 | Analisi performance |
| robots-sitemap.php | 825 | ~12 | Analisi SEO |
| redirect-analysis.php | 822 | ~10 | Analisi redirect |
| port-scanner.php | 869 | ~12 | Scansione porte |

### Pagine Principali

| File | Descrizione |
|------|-------------|
| index.php | Dashboard principale (2,702 linee) |
| dns-check.php | Pagina controllo DNS |
| whois-lookup.php | Pagina lookup WHOIS |
| blacklist-check.php | Pagina controllo blacklist |
| cloud-detection.php | Pagina rilevamento cloud |
| spf-dkim-dmarc.php | Guida autenticazione email |
| setup-microsoft-365.php | Guida setup M365 |
| dns-guide.php | Documentazione DNS |
| tools.php | Directory strumenti |
| api-docs.php | Documentazione API |
| changelog.php | Cronologia versioni |

## Servizi Esterni Utilizzati

### Query DNS
- Funzioni PHP native `dns_get_record()`
- Query dirette a server DNS autoritativi
- Supporto tutti i tipi di record

### Server WHOIS
- Connessioni socket dirette
- Mapping TLD → WHOIS server
- Fallback a metodi HTTP

### Server DNSBL
- 30+ servizi blacklist (Spamhaus, SpamCop, Barracuda, SORBS, ecc.)
- Query parallele via cURL multi
- Timeout configurabili

### Librerie JavaScript
- AOS 2.3.1 (Animate On Scroll) - da CDN
- Google Fonts (Poppins, Lato) - da CDN

## Configurazioni Speciali

### Rate Limiting
- 100 richieste per IP all'ora
- Infrastruttura implementata (attualmente disabilitata)
- Configurabile in `config/config.php`

### Timezone
- Impostato su Europe/Rome
- Configurabile in `config/config.php`

### Caching
- Attualmente disabilitato
- Infrastruttura presente per implementazione futura
- Caching asset statici configurato in `.htaccess`

### Analytics
- Google Analytics integration
- ID tracciamento configurabile
- Abilitato di default

## Versioning

Il progetto segue il versioning semantico:

- **v4.0 (Corrente)**:
  - Nuova rilevamento servizi cloud con scoring confidenza
  - Visualizzazione unificata record DNS
  - Gestione timeout avanzata
  - Design responsive mobile
  - Integrazione social media ricca

Vedi [changelog.php](../changelog.php) per la cronologia completa.

## Contributi

### Team di Sviluppo
- **G Tech Group** - Sviluppo e manutenzione

### Crediti Esterni
- AOS (Animate On Scroll) - Libreria animazioni
- Google Fonts - Font web
- Server WHOIS pubblici - Dati WHOIS
- Server DNSBL - Dati blacklist

## Supporto e Contatti

- **Website**: https://controllodomini.it
- **Email**: [configurare in config.php]
- **Documentazione**: Cartella `/documents`
- **API Docs**: https://controllodomini.it/api-docs

## Licenza

Copyright © G Tech Group. Tutti i diritti riservati.

Questo software è proprietario. L'utilizzo, la copia, la modifica e la distribuzione sono soggetti a licenza.

## Prossimi Passi

1. Leggi [INSTALLATION.md](INSTALLATION.md) per setup dettagliato
2. Consulta [CONFIGURATION.md](CONFIGURATION.md) per personalizzazione
3. Esplora [FEATURES.md](FEATURES.md) per funzionalità complete
4. Riferimento [API.md](API.md) per integrazione API
5. Leggi [SECURITY.md](SECURITY.md) per best practices sicurezza
6. Consulta [DEVELOPMENT.md](DEVELOPMENT.md) per contribuire allo sviluppo

---

**Ultimo aggiornamento**: Novembre 2025
**Versione documentazione**: 1.0
