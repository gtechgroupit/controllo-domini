# Changelog - Controllo Domini

Tutte le modifiche significative a questo progetto sono documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [Non Rilasciato]

### Pianificato per v4.2
- Database integration per caching e analytics
- User authentication system
- API REST completa con rate limiting avanzato
- Dashboard analytics e reporting
- Export risultati in PDF/CSV
- Schedulazione analisi ricorrenti
- Notifiche email per scadenze domini

---

## [4.1.0] - 2025-01-15

### Aggiunto
- **Documentazione completa**: Creata cartella `/documents` con 9 file di documentazione professionale
  - README.md: Panoramica progetto e quick start
  - ARCHITECTURE.md: Architettura sistema completa (30KB)
  - API.md: Documentazione API REST v2.0 (25KB)
  - INSTALLATION.md: Guida installazione dettagliata (19KB)
  - CONFIGURATION.md: Guida configurazione (20KB)
  - FEATURES.md: Documentazione features complete (20KB)
  - SECURITY.md: Security best practices (18KB)
  - DEVELOPMENT.md: Guida sviluppatori (17KB)
  - DEPLOYMENT.md: Procedure deployment (17KB)
- **CHANGELOG.md**: Storico versioni dettagliato
- **ROADMAP.md**: Pianificazione sviluppo futuro
- **CONTRIBUTING.md**: Linee guida contribuzione

### Migliorato
- Ottimizzazione performance query DNS parallele
- Miglioramento gestione timeout per servizi esterni
- Refactoring funzioni utility per maggiore riusabilità
- Miglioramento error handling con logging strutturato

### Fixato
- Fix timeout occasionali su query WHOIS per TLD .it
- Fix parsing WHOIS per domini con privacy protection
- Fix visualizzazione caratteri speciali in output HTML
- Fix responsive mobile per tabelle risultati

### Sicurezza
- Aggiornamento escape di output per prevenire XSS
- Implementazione Content Security Policy più restrittiva
- Miglioramento validazione input domini
- Aggiunta protezione CSRF token (preparazione futura)

---

## [4.0.0] - 2024-12-01

### Aggiunto
- **🎯 Cloud Service Detection**: Nuovo modulo per rilevamento automatico servizi cloud
  - Rilevamento Microsoft 365 / Office 365
  - Rilevamento Google Workspace
  - Rilevamento provider hosting (Cloudflare, AWS, Azure, GCP)
  - Rilevamento CDN (Cloudflare, CloudFront, Akamai)
  - Confidence scoring per ogni rilevamento
  - Supporto 50+ provider cloud e hosting
- **📊 Confidence Scoring System**: Sistema di scoring affidabilità rilevamenti (0-100%)
- **🔄 Unified DNS Visualization**: Interfaccia unificata per visualizzazione record DNS
- **⚡ Advanced Timeout Handling**: Gestione timeout migliorata per query esterne
- **📱 Mobile Responsive Enhancement**: Miglioramento esperienza mobile
- **🎨 UI/UX Improvements**: Redesign interfaccia con focus su usabilità

### Modificato
- Refactoring completo modulo DNS analysis per migliore performance
- Aggiornamento library AOS a versione 2.3.1
- Migrazione Google Analytics da UA a GA4
- Ottimizzazione query DNSBL con cURL multi handler
- Miglioramento parsing WHOIS per nuovi formati

### Rimosso
- Deprecato supporto PHP 7.3 (ora richiede PHP 7.4+)
- Rimosso vecchio sistema di caching file-based (preparazione nuovo sistema)

### Sicurezza
- Aggiornamento security headers con policy più restrittive
- Implementazione rate limiting framework (infrastruttura, non ancora abilitato)
- Aggiornamento cipher suite SSL/TLS raccomandati

---

## [3.5.2] - 2024-10-15

### Fixato
- **Critical**: Fix vulnerabilità XSS in output domain name
- Fix memory leak in analisi performance con pagine molto grandi
- Fix timeout query DNS per alcuni nameserver lenti
- Correzione bug visualizzazione certificati SSL con SAN multipli

### Sicurezza
- Patch sicurezza per handling input non sanitizzato
- Aggiornamento validazione dominio per IDN (Internationalized Domain Names)

---

## [3.5.1] - 2024-09-20

### Fixato
- Fix compatibilità PHP 8.2 (deprecated warnings)
- Fix parsing sitemap.xml con namespace XML complessi
- Correzione calcolo TTL per record DNS con valori molto alti
- Fix visualizzazione performance metrics con valori negativi

### Migliorato
- Ottimizzazione memoria per analisi pagine > 10MB
- Miglioramento gestione errori HTTP timeout

---

## [3.5.0] - 2024-08-10

### Aggiunto
- **Port Scanner**: Nuovo modulo per scansione porte comuni (869 linee)
  - Scansione porte: 21, 22, 25, 80, 443, 3306, 5432, 8080, 8443
  - Identificazione servizi
  - Rilevamento vulnerabilità note
  - Risk assessment
  - Service fingerprinting
- **Educational Content**: Guide dettagliate aggiunte
  - Guida SPF, DKIM, DMARC per autenticazione email
  - Guida setup Microsoft 365 completa
  - Guida DNS record types
- **robots.txt Editor**: Tool per generare robots.txt

### Migliorato
- Performance analysis ora include analisi third-party scripts
- Miglioramento rilevamento tecnologie: +30 nuove tecnologie supportate
- Ottimizzazione query parallele DNSBL (riduzione tempo 40%)

---

## [3.4.0] - 2024-06-15

### Aggiunto
- **Redirect Chain Analysis**: Analisi completa catene redirect (822 linee)
  - Seguimento catene redirect complete
  - Rilevamento HTTP→HTTPS migration
  - Validazione canonical tags
  - Issue detection e raccomandazioni
- **Social Media Preview**: Preview social per Facebook, Twitter, LinkedIn
- **Structured Data Validation**: Validazione Schema.org markup

### Migliorato
- Performance analysis ora supporta HTTP/2 push detection
- SSL analysis include test per TLS 1.3
- Miglioramento UI con animazioni AOS

---

## [3.3.0] - 2024-04-20

### Aggiunto
- **SEO Analysis**: robots.txt e sitemap.xml analysis (825 linee)
  - Parsing e validazione robots.txt
  - Parsing sitemap.xml (supporto sitemap index)
  - Crawlability scoring
  - SEO recommendations
- **Social Meta Analysis**: Analisi Open Graph e Twitter Cards (1,037 linee)
  - Validazione meta tag social
  - Generazione preview per diverse piattaforme
  - Issue detection

### Migliorato
- Blacklist check ora supporta 30+ DNSBL (precedentemente 20)
- Aggiunto reputation scoring per blacklist check
- Performance metrics più dettagliate

---

## [3.2.0] - 2024-02-10

### Aggiunto
- **Performance Analysis**: Analisi completa performance sito (1,107 linee)
  - Core Web Vitals (LCP, FID, CLS)
  - Resource waterfall analysis
  - Image optimization detection
  - JavaScript/CSS analysis
  - Caching strategy evaluation
  - Compression detection
  - Performance scoring
- **Resource Timeline**: Visualizzazione timeline caricamento risorse

### Migliorato
- Security headers analysis con scoring pesato
- Technology detection migliorate (+50 tecnologie)

---

## [3.1.0] - 2024-01-05

### Aggiunto
- **Technology Stack Detection**: Rilevamento completo stack tecnologico (1,190 linee)
  - Web server detection (Apache, Nginx, IIS)
  - Programming language detection (PHP, Python, Node.js, Ruby)
  - Framework detection (Laravel, Symfony, React, Vue, Angular)
  - CMS detection (WordPress, Drupal, Joomla)
  - JavaScript libraries (jQuery, Bootstrap, ecc.)
  - Analytics services (GA, Facebook Pixel)
- **HTTP/2 Detection**: Rilevamento supporto HTTP/2
- **CDN Detection**: Identificazione CDN providers

### Migliorato
- SSL analysis include check vulnerabilità (Heartbleed, POODLE, BEAST, ecc.)
- Miglioramento parsing certificati SSL con catene complesse

---

## [3.0.0] - 2023-11-15

### Aggiunto
- **Security Headers Analysis**: Nuovo modulo analisi security headers (578 linee)
  - Analisi HSTS, CSP, X-Frame-Options, X-Content-Type-Options
  - Referrer-Policy, Permissions-Policy
  - Scoring system con raccomandazioni
  - Conformità OWASP best practices
- **API Documentation Page**: Pagina documentazione API integrata
- **Changelog Page**: Pagina changelog integrata nell'app

### Modificato
- **Breaking**: Ristrutturazione architettura moduli
- Separazione completa business logic da presentation layer
- Migrazione da procedural a function-based organization
- Standardizzazione naming conventions

### Migliorato
- Template system con header/footer separati
- Miglioramento SEO con meta tag dinamici
- Performance: riduzione tempo caricamento 30%

---

## [2.5.0] - 2023-09-10

### Aggiunto
- **SSL/TLS Certificate Analysis**: Analisi completa certificati SSL (731 linee)
  - Validazione certificato e catena
  - Test protocolli supportati (SSLv2, SSLv3, TLS 1.0-1.3)
  - Analisi cipher suites
  - Grade assignment (A-F)
  - Vulnerability scanning
- **Certificate Expiry Alerts**: Alert per certificati in scadenza

### Migliorato
- WHOIS lookup con supporto fallback HTTP
- Miglioramento gestione timeout per query lente

---

## [2.4.0] - 2023-07-20

### Aggiunto
- **Blacklist Check**: Controllo 20+ DNSBL per email reputation (852 linee)
  - Query parallele per performance
  - Modalità sequential come fallback
  - Statistiche dettagliate listing
  - Support per IPv4 e IPv6
- **Subdomain Scanner**: Scansione automatica sottodomini comuni
- **DNSSEC Validation**: Validazione DNSSEC per domini

### Migliorato
- DNS analysis supporta ora CAA records
- Visualizzazione risultati DNS con tabelle responsive
- Miglioramento mobile experience

---

## [2.3.0] - 2023-05-15

### Aggiunto
- **WHOIS Lookup**: Implementazione completa WHOIS (1,098 linee)
  - Socket connection diretta a server WHOIS
  - Supporto 20+ TLD
  - Fallback multipli (socket → shell_exec → HTTP)
  - Parsing automatico dati WHOIS
  - Calcolo giorni a scadenza dominio
- **WHOIS Server Management**: Gestione dinamica server WHOIS per TLD
- **Privacy Detection**: Rilevamento WHOIS privacy protection

### Migliorato
- DNS query con miglior gestione errori
- Logging strutturato per debugging
- Performance optimization query parallele

---

## [2.2.0] - 2023-03-10

### Aggiunto
- **SRV Records Support**: Supporto query record SRV
- **Response Time Metrics**: Misurazione tempo risposta DNS
- **Duplicate Detection**: Rilevamento record DNS duplicati
- **TTL Analysis**: Analisi TTL per tutti i record

### Modificato
- Refactoring modulo DNS con separazione concerns
- Miglioramento architettura funzioni helper

---

## [2.1.0] - 2023-01-20

### Aggiunto
- **Multiple DNS Record Types**: Supporto completo tipi record DNS
  - A, AAAA, MX, TXT, CNAME, NS, SOA
- **DNS Statistics**: Statistiche aggregate risultati DNS
- **Export Results**: Export risultati in formato JSON

### Migliorato
- UI/UX con design più moderno
- Responsive design per mobile
- Performance query DNS ottimizzate

---

## [2.0.0] - 2022-11-15

### Aggiunto
- **Complete Rewrite**: Riscrittura completa applicazione
- **Modular Architecture**: Architettura modulare con includes separati
- **Template System**: Sistema template per header/footer
- **Configuration Management**: Gestione configurazione centralizzata
- **Utilities Library**: Libreria funzioni utility riusabili
- **Clean URL Routing**: URL rewriting con .htaccess

### Modificato
- Migrazione da singolo file monolitico a architettura modulare
- Separazione logica/presentazione

---

## [1.5.0] - 2022-09-10

### Aggiunto
- **DNS Lookup Basico**: Prima implementazione lookup DNS
- **Basic UI**: Interfaccia utente semplice
- **Form Validation**: Validazione base input

---

## [1.0.0] - 2022-07-01

### Aggiunto
- **Initial Release**: Prima versione pubblica
- Lookup DNS basico per record A
- Interfaccia web minimale
- Validazione dominio base

---

## Legenda Tipi di Modifiche

- **Aggiunto**: Nuove funzionalità
- **Modificato**: Modifiche a funzionalità esistenti
- **Deprecato**: Funzionalità deprecate (saranno rimosse)
- **Rimosso**: Funzionalità rimosse
- **Fixato**: Bug fix
- **Sicurezza**: Patch di sicurezza

## Versioning

Il progetto segue [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0): Breaking changes, incompatibilità con versioni precedenti
- **MINOR** (x.X.0): Nuove funzionalità, backward compatible
- **PATCH** (x.x.X): Bug fix, backward compatible

---

## Link Utili

- [Repository GitHub](https://github.com/gtechgroup/controllo-domini)
- [Roadmap Sviluppo](ROADMAP.md)
- [Guida Contribuzione](CONTRIBUTING.md)
- [Documentazione Completa](documents/README.md)

---

**Ultimo aggiornamento**: Novembre 2025
**Versione corrente**: 4.1.0
**Prossima release pianificata**: v4.2.0 (Q1 2025)
