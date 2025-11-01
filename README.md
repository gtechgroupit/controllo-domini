<div align="center">

# 🌐 Controllo Domini

### Piattaforma Professionale per Analisi Completa Domini Web

[![Version](https://img.shields.io/badge/version-4.1.0-blue.svg)](https://github.com/gtechgroupit/controllo-domini)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-Proprietary-orange.svg)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-complete-green.svg)](documents/)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/gtechgroupit/controllo-domini/graphs/commit-activity)
[![G Tech Group](https://img.shields.io/badge/by-G%20Tech%20Group-orange.svg)](https://gtechgroup.it)

[🚀 Demo](https://controllodomini.it) • [📚 Documentazione](documents/) • [🗺️ Roadmap](documents/ROADMAP.md) • [🤝 Contribuire](documents/CONTRIBUTING.md)

</div>

---

## 📋 Panoramica

**Controllo Domini** è una piattaforma completa e professionale per l'analisi approfondita di domini web, sviluppata da G Tech Group. Offre **12 strumenti di analisi integrati** che coprono DNS, WHOIS, sicurezza, performance, SEO e molto altro.

### 🎯 Per Chi è Questo Tool?

- **👨‍💻 Sviluppatori Web**: Analisi tecnica completa di domini e infrastruttura
- **🔒 Security Analyst**: Audit sicurezza, SSL/TLS, blacklist, port scanning
- **📊 SEO Specialist**: Analisi meta tag, robots.txt, sitemap, performance
- **🏢 System Administrator**: Monitoring DNS, WHOIS, certificati SSL
- **💼 Web Agency**: Report professionali per clienti

---

## ✨ Caratteristiche Principali

<table>
<tr>
<td width="50%">

### 🔍 Analisi Network

- **DNS Lookup Completo**: 9 tipi record (A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA)
- **WHOIS Lookup**: Informazioni registrazione, scadenza, registrar
- **Blacklist Check**: Controllo su 30+ DNSBL servers
- **Cloud Detection**: Rilevamento Microsoft 365, Google Workspace, AWS, Azure
- **Port Scanning**: Scansione porte comuni e identificazione servizi

</td>
<td width="50%">

### 🔒 Analisi Sicurezza

- **SSL/TLS Analysis**: Certificati, protocolli, cipher suites, vulnerabilità
- **Security Headers**: HSTS, CSP, X-Frame-Options, scoring
- **Technology Detection**: Framework, CMS, librerie, fingerprinting
- **Vulnerability Check**: Rilevamento software vulnerabile
- **DNSSEC Validation**: Validazione firma DNSSEC

</td>
</tr>
<tr>
<td width="50%">

### 📊 Analisi Performance

- **Core Web Vitals**: LCP, FID, CLS
- **Resource Analysis**: Waterfall, ottimizzazione immagini
- **Caching Strategy**: Valutazione politica cache
- **Compression Check**: Gzip, Brotli detection
- **Performance Score**: Grade A-F con raccomandazioni

</td>
<td width="50%">

### 🎨 Analisi SEO & Social

- **Social Meta Analysis**: Open Graph, Twitter Cards
- **SEO Audit**: robots.txt, sitemap.xml
- **Redirect Analysis**: Catene redirect, canonical
- **Structured Data**: Schema.org validation
- **Social Preview**: Anteprima per Facebook, Twitter, LinkedIn

</td>
</tr>
</table>

---

## 🚀 Quick Start

### Requisiti

| Componente | Versione Minima | Consigliata |
|------------|-----------------|-------------|
| PHP | 7.4 | 8.2+ |
| Apache | 2.4 | 2.4.57+ |
| RAM | 512 MB | 2 GB |
| Disk Space | 100 MB | 500 MB |

**Estensioni PHP Richieste**: `json`, `curl`, `mbstring`, `openssl`

### Installazione

```bash
# 1. Clone repository
git clone https://github.com/gtechgroupit/controllo-domini.git
cd controllo-domini

# 2. Configura Apache virtual host
sudo nano /etc/apache2/sites-available/controllodomini.conf

# 3. Abilita mod_rewrite
sudo a2enmod rewrite

# 4. Imposta permessi
sudo chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# 5. Riavvia Apache
sudo systemctl restart apache2
```

Vedi [📖 Guida Installazione Completa](documents/INSTALLATION.md) per istruzioni dettagliate.

---

## 📚 Documentazione

Documentazione completa disponibile nella cartella [`documents/`](documents/):

| Documento | Descrizione |
|-----------|-------------|
| [📖 README](documents/README.md) | Panoramica completa del progetto |
| [🏗️ ARCHITECTURE](documents/ARCHITECTURE.md) | Architettura sistema e design patterns |
| [🔌 API](documents/API.md) | Documentazione API REST v2.0 |
| [⚙️ INSTALLATION](documents/INSTALLATION.md) | Guida installazione dettagliata |
| [🔧 CONFIGURATION](documents/CONFIGURATION.md) | Configurazione e personalizzazione |
| [✨ FEATURES](documents/FEATURES.md) | Documentazione funzionalità complete |
| [🔒 SECURITY](documents/SECURITY.md) | Best practices sicurezza |
| [👨‍💻 DEVELOPMENT](documents/DEVELOPMENT.md) | Guida per sviluppatori |
| [🚀 DEPLOYMENT](documents/DEPLOYMENT.md) | Procedure deployment |
| [📝 CHANGELOG](documents/CHANGELOG.md) | Cronologia versioni |
| [🗺️ ROADMAP](documents/ROADMAP.md) | Roadmap sviluppo futuro |
| [🤝 CONTRIBUTING](documents/CONTRIBUTING.md) | Linee guida contribuzione |

---

## 💻 Utilizzo

### Via Web Interface

Visita l'applicazione web e inserisci il dominio da analizzare:

```
https://controllodomini.it
```

### Via API (Future v4.2)

```bash
# DNS Lookup
curl -X POST https://api.controllodomini.it/v2/dns/lookup \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}'

# WHOIS Lookup
curl -X POST https://api.controllodomini.it/v2/whois/lookup \
  -H "X-API-Key: your_api_key" \
  -d '{"domain": "example.com"}'
```

Vedi [API Documentation](documents/API.md) per reference completa.

---

## 🛠️ Stack Tecnologico

### Backend
- **PHP 7.4+**: Linguaggio principale (procedural architecture)
- **Apache 2.4**: Web server con mod_rewrite
- **No Database**: Stateless application (query real-time a servizi esterni)

### Frontend
- **HTML5**: Markup semantico
- **CSS3**: Styling con CSS variables
- **Vanilla JavaScript**: ES6+, no framework
- **AOS 2.3.1**: Animate On Scroll library

### External Services
- **DNS Servers**: Query tramite `dns_get_record()`
- **WHOIS Servers**: Socket diretti TCP:43 + fallback
- **DNSBL Servers**: 30+ blacklist servers
- **Target Websites**: cURL per analisi HTTP/SSL

### Architettura
```
┌─────────────────────────────────────────┐
│      Presentation Layer                 │
│  Templates + Assets (HTML/CSS/JS)      │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Application Layer                  │
│  13 Modules + 206+ Functions           │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      External Services                  │
│  DNS | WHOIS | DNSBL | HTTP | SSL      │
└─────────────────────────────────────────┘
```

Vedi [ARCHITECTURE.md](documents/ARCHITECTURE.md) per dettagli completi.

---

## 📊 Statistiche Progetto

| Metrica | Valore |
|---------|--------|
| **Linee di Codice** | ~14,757 PHP |
| **Moduli Funzionali** | 13 |
| **Funzioni Totali** | 206+ |
| **File Documentazione** | 12 docs |
| **Dimensione Docs** | ~200 KB |
| **TLD WHOIS Supportati** | 20+ |
| **Server DNSBL** | 30+ |
| **Tipi Record DNS** | 9 |
| **Analisi Integrate** | 12 tools |

---

## 🗺️ Roadmap

### Q1 2025 - v4.2 (In Sviluppo)
- 🗄️ Database integration (PostgreSQL)
- 👤 User authentication system
- 🔑 API v2.1 con API keys
- 💾 Redis caching layer
- 📄 Export PDF/CSV

### Q2 2025 - v4.3
- 👥 Team & organization management
- 🔐 SSO (SAML, OAuth)
- 📝 Audit logging
- 🎨 White-label solution

### Q3 2025 - v5.0
- 🔌 GraphQL API
- 🪝 Webhooks system
- 📦 Official SDKs (PHP, JS, Python, Go)
- 🛠️ CLI tool

### Q4 2025 - v5.1
- 📊 Continuous monitoring
- 🚨 Alert system multi-channel
- 📈 Status pages
- 🎯 SLA tracking

Vedi [ROADMAP completa](documents/ROADMAP.md) per dettagli.

---

## 🤝 Contribuire

Contributi, issue e feature request sono benvenuti! Vedi [CONTRIBUTING.md](documents/CONTRIBUTING.md) per iniziare.

### Come Contribuire

1. 🍴 **Fork** il repository
2. 🌿 **Crea** feature branch (`git checkout -b feature/AmazingFeature`)
3. 💾 **Commit** modifiche (`git commit -m 'feat: add amazing feature'`)
4. 📤 **Push** al branch (`git push origin feature/AmazingFeature`)
5. 🎉 **Apri** Pull Request

### Development Setup

```bash
# Clone & setup
git clone https://github.com/YOUR_USERNAME/controllo-domini.git
cd controllo-domini

# Configure development
cp config/config.php config/config.local.php
# Edit config.local.php per development settings

# Setup virtual host (vedi INSTALLATION.md)

# Verifica setup
curl http://controllodomini.local/
```

Vedi [DEVELOPMENT.md](documents/DEVELOPMENT.md) per guida completa.

---

## 📝 Changelog

Tutte le modifiche significative sono documentate nel [CHANGELOG](documents/CHANGELOG.md).

### [4.1.0] - 2025-01-15

#### Aggiunto
- ✨ Documentazione completa (9 file, ~200KB)
- 📝 CHANGELOG dettagliato
- 🗺️ ROADMAP sviluppo futuro
- 🤝 CONTRIBUTING guidelines

#### Migliorato
- ⚡ Performance query DNS parallele
- 🛡️ Security: CSP più restrittiva
- 🐛 Bug fixes: timeout WHOIS .it

---

## 🏢 Chi Siamo

**G Tech Group** è un'azienda italiana specializzata in soluzioni web e tecnologiche innovative.

- 🌐 **Website**: [gtechgroup.it](https://gtechgroup.it)
- 📧 **Email**: info@gtechgroup.it
- 🐙 **GitHub**: [github.com/gtechgroupit](https://github.com/gtechgroupit)

### Team

- **Project Lead**: G Tech Group Development Team
- **Maintainers**: [Contributors](https://github.com/gtechgroupit/controllo-domini/graphs/contributors)
- **Contributors**: Community contributors (grazie! 🙏)

---

## 📄 Licenza

Copyright © 2022-2025 G Tech Group. Tutti i diritti riservati.

Questo software è proprietario. Per informazioni su licenze commerciali:
- 📧 Email: licensing@controllodomini.it
- 🌐 Website: [controllodomini.it/licensing](https://controllodomini.it/licensing)

---

## 🙏 Riconoscimenti

### Libraries & Services

- **AOS**: [Animate On Scroll](https://michalsnik.github.io/aos/) by Michał Sajnóg
- **Google Fonts**: [Poppins](https://fonts.google.com/specimen/Poppins) & [Lato](https://fonts.google.com/specimen/Lato)
- **DNS Servers**: Public DNS resolvers (Google, Cloudflare, OpenDNS)
- **WHOIS Servers**: Public WHOIS servers per TLD
- **DNSBL Servers**: Spamhaus, SpamCop, Barracuda, SORBS, e altri

### Inspiration

Grazie alla community open-source per l'ispirazione continua.

---

## 📞 Support & Contatti

### Per Utenti

- 💬 **Community**: [GitHub Discussions](https://github.com/gtechgroupit/controllo-domini/discussions)
- 🐛 **Bug Report**: [GitHub Issues](https://github.com/gtechgroupit/controllo-domini/issues)
- 📧 **Email Support**: support@controllodomini.it

### Per Sviluppatori

- 👨‍💻 **Development**: [DEVELOPMENT.md](documents/DEVELOPMENT.md)
- 🤝 **Contributing**: [CONTRIBUTING.md](documents/CONTRIBUTING.md)
- 📝 **API Docs**: [API.md](documents/API.md)
- 📧 **Email Dev**: dev@controllodomini.it

### Per Business

- 💼 **Enterprise**: enterprise@controllodomini.it
- 🤝 **Partnership**: partnership@controllodomini.it
- 📊 **Marketing**: marketing@controllodomini.it

---

## ⭐ Supportaci

Se trovi utile questo progetto:

- ⭐ **Star** questo repository
- 🍴 **Fork** per contribuire
- 📣 **Condividi** con altri
- 💬 **Feedback** via Discussions
- 🐛 **Segnala bug** via Issues

---

<div align="center">

**Made with ❤️ by G Tech Group**

[Website](https://gtechgroup.it) • [Demo](https://controllodomini.it) • [Docs](documents/) • [Roadmap](documents/ROADMAP.md)

</div>
