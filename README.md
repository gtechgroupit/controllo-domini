# Controllo Domini - DNS & WHOIS Analysis Tool

[![Version](https://img.shields.io/badge/version-4.0-blue.svg)](https://github.com/gtechgroupit/controllo-domini)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![G Tech Group](https://img.shields.io/badge/by-G%20Tech%20Group-orange.svg)](https://gtechgroup.it)

Sistema professionale per l'analisi completa di domini, DNS, WHOIS e blacklist. Sviluppato da G Tech Group per fornire uno strumento gratuito e completo per l'analisi dei domini.

🌐 **Live Demo**: [https://controllodomini.it](https://controllodomini.it)

## 📋 Caratteristiche

### Analisi DNS Completa
- ✅ Tutti i record DNS (A, AAAA, MX, TXT, CNAME, NS, SOA, SRV, CAA)
- ✅ Rilevamento automatico sottodomini comuni
- ✅ Analisi TTL e performance
- ✅ Validazione configurazione email (SPF, DKIM, DMARC)

### Informazioni WHOIS
- ✅ Dati intestatario dominio
- ✅ Date registrazione e scadenza
- ✅ Informazioni registrar
- ✅ Nameserver registrati
- ✅ Supporto GDPR/Privacy

### Controllo Blacklist
- ✅ Verifica su 30+ blacklist principali
- ✅ Controllo reputazione IP
- ✅ Score reputazione complessivo
- ✅ Raccomandazioni per delisting

### Rilevamento Servizi Cloud
- ✅ Microsoft 365 / Office 365
- ✅ Google Workspace
- ✅ AWS, Azure, Google Cloud
- ✅ CDN (Cloudflare, CloudFront, etc.)
- ✅ Hosting providers

### Sicurezza e Performance
- ✅ Analisi DNSSEC
- ✅ Controllo CAA records
- ✅ Health score dominio
- ✅ Suggerimenti ottimizzazione

## 🚀 Installazione

### Requisiti di Sistema

- PHP >= 7.4
- Apache con mod_rewrite abilitato
- Estensioni PHP richieste:
  - `json`
  - `curl` (opzionale, per WHOIS avanzato)
  - `mbstring`
  - `openssl`

### Installazione Rapida

1. **Clona il repository**
```bash
git clone https://github.com/gtechgroupit/controllo-domini.git
cd controllo-domini
