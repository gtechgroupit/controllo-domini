# API Documentation - Controllo Domini

## Indice

1. [Panoramica API](#panoramica-api)
2. [Autenticazione](#autenticazione)
3. [Rate Limiting](#rate-limiting)
4. [Endpoints](#endpoints)
5. [Formato Risposte](#formato-risposte)
6. [Codici di Errore](#codici-di-errore)
7. [Esempi](#esempi)
8. [SDK e Librerie](#sdk-e-librerie)

## Panoramica API

### Base URL

```
https://api.controllodomini.it/v2
```

### Versione Corrente

**v2.0** - API RESTful

### Caratteristiche

- **Formato**: JSON (request e response)
- **Protocollo**: HTTPS only
- **Autenticazione**: API Key (header-based)
- **Rate Limiting**: 100 richieste/ora per IP
- **Timeout**: 30 secondi per request
- **Encoding**: UTF-8

### Endpoints Disponibili

| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/dns/lookup` | POST | Analisi completa record DNS |
| `/whois/lookup` | POST | Lookup informazioni WHOIS |
| `/blacklist/check` | POST | Controllo blacklist email |
| `/cloud/detect` | POST | Rilevamento servizi cloud |
| `/ssl/analyze` | POST | Analisi certificato SSL/TLS |
| `/security/headers` | POST | Analisi security headers HTTP |
| `/tech/detect` | POST | Rilevamento stack tecnologico |
| `/social/meta` | POST | Analisi meta tag social media |
| `/performance/analyze` | POST | Analisi performance sito |
| `/seo/analyze` | POST | Analisi SEO (robots.txt, sitemap) |
| `/redirect/analyze` | POST | Analisi catena redirect |
| `/ports/scan` | POST | Scansione porte comuni |

## Autenticazione

### API Key

Ogni richiesta deve includere un API key nell'header:

```http
X-API-Key: your_api_key_here
```

### Ottenere un API Key

1. Registrati su https://controllodomini.it/api/register
2. Verifica la tua email
3. Genera un API key dal dashboard
4. Copia e salva il key in modo sicuro

### Esempio cURL

```bash
curl -X POST https://api.controllodomini.it/v2/dns/lookup \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}'
```

### Sicurezza

- ⚠️ Non condividere mai il tuo API key
- 🔒 Usa sempre HTTPS
- 🔄 Ruota regolarmente le chiavi
- 📝 Usa chiavi diverse per ambienti diversi (dev/staging/prod)

## Rate Limiting

### Limiti

| Piano | Richieste/Ora | Richieste/Giorno |
|-------|---------------|------------------|
| Free | 100 | 1,000 |
| Basic | 500 | 10,000 |
| Pro | 2,000 | 50,000 |
| Enterprise | Unlimited | Unlimited |

### Headers di Rate Limit

Ogni risposta include headers informativi:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1699891200
```

- `X-RateLimit-Limit`: Limite totale per periodo
- `X-RateLimit-Remaining`: Richieste rimanenti
- `X-RateLimit-Reset`: Timestamp Unix reset contatore

### Rate Limit Exceeded

Quando il limite viene superato:

**Status Code**: `429 Too Many Requests`

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 1800 seconds.",
    "retry_after": 1800
  }
}
```

## Endpoints

### 1. DNS Lookup

Analizza tutti i record DNS di un dominio.

**Endpoint**: `POST /dns/lookup`

**Request Body**:
```json
{
  "domain": "example.com",
  "record_types": ["A", "AAAA", "MX", "TXT", "CNAME", "NS", "SOA"],
  "check_subdomains": true,
  "common_subdomains": ["www", "mail", "ftp", "smtp"]
}
```

**Parametri**:

| Campo | Tipo | Richiesto | Default | Descrizione |
|-------|------|-----------|---------|-------------|
| domain | string | Sì | - | Nome dominio da analizzare |
| record_types | array | No | All types | Tipi di record da query |
| check_subdomains | boolean | No | false | Controlla sottodomini comuni |
| common_subdomains | array | No | ["www","mail"] | Lista sottodomini custom |

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "response_time_ms": 145,
    "records": {
      "A": [
        {
          "ip": "93.184.216.34",
          "ttl": 3600,
          "type": "A"
        }
      ],
      "AAAA": [
        {
          "ip": "2606:2800:220:1:248:1893:25c8:1946",
          "ttl": 3600,
          "type": "AAAA"
        }
      ],
      "MX": [
        {
          "priority": 10,
          "target": "mail.example.com",
          "ttl": 3600,
          "type": "MX"
        }
      ],
      "TXT": [
        {
          "txt": "v=spf1 include:_spf.example.com ~all",
          "ttl": 3600,
          "type": "TXT"
        }
      ],
      "NS": [
        {
          "target": "ns1.example.com",
          "ttl": 86400,
          "type": "NS"
        }
      ]
    },
    "subdomains": {
      "www.example.com": {
        "exists": true,
        "records": [...]
      },
      "mail.example.com": {
        "exists": true,
        "records": [...]
      }
    },
    "dnssec": {
      "enabled": true,
      "valid": true
    },
    "statistics": {
      "total_records": 15,
      "unique_ips": 3,
      "duplicates_found": 0
    }
  }
}
```

---

### 2. WHOIS Lookup

Recupera informazioni WHOIS di un dominio.

**Endpoint**: `POST /whois/lookup`

**Request Body**:
```json
{
  "domain": "example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "whois_server": "whois.verisign-grs.com",
    "registrar": {
      "name": "Example Registrar, Inc.",
      "whois_server": "whois.example-registrar.com",
      "url": "https://www.example-registrar.com",
      "abuse_contact_email": "abuse@example-registrar.com",
      "abuse_contact_phone": "+1.1234567890"
    },
    "registrant": {
      "name": "Privacy Protected",
      "organization": "Privacy Service",
      "email": "contact@privacyservice.com",
      "privacy_protected": true
    },
    "dates": {
      "created": "1995-08-14T04:00:00Z",
      "updated": "2023-08-14T07:01:30Z",
      "expires": "2026-08-13T04:00:00Z",
      "days_until_expiry": 278
    },
    "nameservers": [
      "ns1.example.com",
      "ns2.example.com"
    ],
    "status": [
      "clientDeleteProhibited",
      "clientTransferProhibited",
      "clientUpdateProhibited"
    ],
    "dnssec": {
      "enabled": true,
      "ds_records": [...]
    },
    "raw_whois": "Domain Name: EXAMPLE.COM\nRegistry Domain ID: ...\n[Full WHOIS data]"
  }
}
```

---

### 3. Blacklist Check

Controlla se un dominio/IP è presente in blacklist email.

**Endpoint**: `POST /blacklist/check`

**Request Body**:
```json
{
  "domain": "example.com",
  "check_www": true,
  "mode": "parallel"
}
```

**Parametri**:

| Campo | Tipo | Richiesto | Default | Descrizione |
|-------|------|-----------|---------|-------------|
| domain | string | Sì | - | Dominio o IP da controllare |
| check_www | boolean | No | true | Controlla anche variante www |
| mode | string | No | "parallel" | "parallel" o "sequential" |

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "ips_checked": [
      "93.184.216.34"
    ],
    "timestamp": "2025-11-01T10:30:00Z",
    "total_blacklists": 30,
    "listed_on": 0,
    "clean": 30,
    "results": [
      {
        "blacklist": "zen.spamhaus.org",
        "listed": false,
        "response_time_ms": 45
      },
      {
        "blacklist": "bl.spamcop.net",
        "listed": false,
        "response_time_ms": 52
      }
    ],
    "reputation_score": 100,
    "reputation_rating": "Excellent",
    "summary": {
      "status": "clean",
      "risk_level": "low",
      "message": "This domain is not listed on any checked blacklists."
    }
  }
}
```

---

### 4. Cloud Service Detection

Rileva servizi cloud e hosting provider.

**Endpoint**: `POST /cloud/detect`

**Request Body**:
```json
{
  "domain": "example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "email_services": [
      {
        "provider": "Microsoft 365",
        "confidence": 95,
        "indicators": [
          "MX: example-com.mail.protection.outlook.com",
          "TXT: v=spf1 include:spf.protection.outlook.com"
        ]
      }
    ],
    "hosting_providers": [
      {
        "provider": "Cloudflare",
        "confidence": 85,
        "indicators": [
          "NS: ns1.cloudflare.com",
          "IP: Cloudflare range"
        ]
      }
    ],
    "cdn_providers": [
      {
        "provider": "Cloudflare CDN",
        "confidence": 90,
        "indicators": [
          "HTTP Header: CF-Ray",
          "IP Range: Cloudflare"
        ]
      }
    ],
    "cloud_platforms": [
      {
        "provider": "AWS",
        "services": ["Route53", "CloudFront"],
        "confidence": 70
      }
    ],
    "security_services": [
      {
        "service": "Cloudflare DDoS Protection",
        "confidence": 85
      }
    ],
    "summary": {
      "total_services": 5,
      "primary_email": "Microsoft 365",
      "primary_hosting": "Cloudflare",
      "primary_cdn": "Cloudflare"
    }
  }
}
```

---

### 5. SSL/TLS Certificate Analysis

Analizza certificato SSL/TLS e configurazione.

**Endpoint**: `POST /ssl/analyze`

**Request Body**:
```json
{
  "domain": "example.com",
  "port": 443
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "certificate": {
      "subject": {
        "CN": "example.com",
        "O": "Example Inc.",
        "C": "US"
      },
      "issuer": {
        "CN": "DigiCert SHA2 Secure Server CA",
        "O": "DigiCert Inc",
        "C": "US"
      },
      "valid_from": "2024-01-01T00:00:00Z",
      "valid_to": "2025-01-01T23:59:59Z",
      "days_remaining": 61,
      "signature_algorithm": "sha256WithRSAEncryption",
      "public_key_bits": 2048,
      "serial_number": "0F8A1B2C3D4E5F6G7H8I9J0K",
      "san": [
        "example.com",
        "www.example.com"
      ]
    },
    "chain": {
      "valid": true,
      "certificates": [
        {
          "subject": "example.com",
          "issuer": "DigiCert SHA2 Secure Server CA"
        },
        {
          "subject": "DigiCert SHA2 Secure Server CA",
          "issuer": "DigiCert Global Root CA"
        }
      ]
    },
    "protocols": {
      "SSLv2": false,
      "SSLv3": false,
      "TLSv1.0": false,
      "TLSv1.1": false,
      "TLSv1.2": true,
      "TLSv1.3": true
    },
    "cipher_suites": [
      "TLS_AES_256_GCM_SHA384",
      "TLS_CHACHA20_POLY1305_SHA256",
      "TLS_AES_128_GCM_SHA256"
    ],
    "vulnerabilities": {
      "heartbleed": false,
      "poodle": false,
      "beast": false,
      "crime": false,
      "freak": false,
      "logjam": false
    },
    "grade": "A+",
    "score": 100,
    "recommendations": []
  }
}
```

---

### 6. Security Headers Analysis

Analizza HTTP security headers.

**Endpoint**: `POST /security/headers`

**Request Body**:
```json
{
  "url": "https://example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "headers": {
      "Strict-Transport-Security": {
        "present": true,
        "value": "max-age=31536000; includeSubDomains; preload",
        "score": 10,
        "status": "good",
        "recommendation": null
      },
      "Content-Security-Policy": {
        "present": true,
        "value": "default-src 'self'; script-src 'self' 'unsafe-inline'",
        "score": 8,
        "status": "warning",
        "recommendation": "Remove 'unsafe-inline' from script-src"
      },
      "X-Frame-Options": {
        "present": true,
        "value": "SAMEORIGIN",
        "score": 10,
        "status": "good"
      },
      "X-Content-Type-Options": {
        "present": true,
        "value": "nosniff",
        "score": 10,
        "status": "good"
      },
      "Referrer-Policy": {
        "present": true,
        "value": "strict-origin-when-cross-origin",
        "score": 10,
        "status": "good"
      },
      "Permissions-Policy": {
        "present": false,
        "value": null,
        "score": 0,
        "status": "missing",
        "recommendation": "Add Permissions-Policy header"
      }
    },
    "total_score": 48,
    "max_score": 60,
    "percentage": 80,
    "grade": "B+",
    "summary": {
      "present": 5,
      "missing": 1,
      "warnings": 1
    }
  }
}
```

---

### 7. Technology Stack Detection

Rileva tecnologie utilizzate da un sito web.

**Endpoint**: `POST /tech/detect`

**Request Body**:
```json
{
  "url": "https://example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "web_server": {
      "name": "nginx",
      "version": "1.24.0"
    },
    "programming_languages": [
      {
        "language": "PHP",
        "version": "8.2",
        "confidence": 90
      }
    ],
    "frameworks": [
      {
        "name": "Laravel",
        "version": "10.x",
        "category": "Backend Framework",
        "confidence": 85
      },
      {
        "name": "Vue.js",
        "version": "3.3",
        "category": "Frontend Framework",
        "confidence": 95
      }
    ],
    "cms": {
      "name": "WordPress",
      "version": "6.4",
      "confidence": 100
    },
    "javascript_libraries": [
      {
        "name": "jQuery",
        "version": "3.7.1"
      },
      {
        "name": "Bootstrap",
        "version": "5.3.0"
      }
    ],
    "analytics": [
      {
        "name": "Google Analytics",
        "tracking_id": "UA-12345678-1"
      }
    ],
    "cdn": [
      {
        "name": "Cloudflare",
        "confidence": 95
      }
    ],
    "security": [
      {
        "service": "Cloudflare DDoS Protection"
      }
    ]
  }
}
```

---

### 8. Performance Analysis

Analizza performance e ottimizzazioni di un sito.

**Endpoint**: `POST /performance/analyze`

**Request Body**:
```json
{
  "url": "https://example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "url": "https://example.com",
    "timestamp": "2025-11-01T10:30:00Z",
    "metrics": {
      "total_load_time_ms": 1234,
      "time_to_first_byte_ms": 150,
      "dom_content_loaded_ms": 890,
      "page_size_bytes": 1254896,
      "page_size_mb": 1.2,
      "total_requests": 45
    },
    "core_web_vitals": {
      "LCP": {
        "value": 1.8,
        "unit": "seconds",
        "rating": "good"
      },
      "FID": {
        "value": 50,
        "unit": "milliseconds",
        "rating": "good"
      },
      "CLS": {
        "value": 0.05,
        "rating": "good"
      }
    },
    "resources": {
      "images": {
        "count": 15,
        "total_size_kb": 450,
        "optimized": 10,
        "unoptimized": 5
      },
      "javascript": {
        "count": 8,
        "total_size_kb": 256,
        "minified": 6,
        "unminified": 2
      },
      "css": {
        "count": 3,
        "total_size_kb": 85,
        "minified": 3
      }
    },
    "caching": {
      "enabled": true,
      "cached_resources": 35,
      "uncached_resources": 10
    },
    "compression": {
      "enabled": true,
      "gzip": true,
      "brotli": false
    },
    "score": 85,
    "grade": "B",
    "recommendations": [
      "Optimize 5 unoptimized images",
      "Minify 2 JavaScript files",
      "Enable Brotli compression"
    ]
  }
}
```

---

### 9-12. Altri Endpoints

Gli altri endpoint seguono pattern simili. Vedi la documentazione interattiva su:

https://controllodomini.it/api-docs

## Formato Risposte

### Success Response

Tutte le risposte di successo hanno questo formato:

```json
{
  "success": true,
  "data": {
    // ... dati specifici endpoint
  },
  "meta": {
    "request_id": "req_abc123xyz",
    "timestamp": "2025-11-01T10:30:00Z",
    "execution_time_ms": 234
  }
}
```

### Error Response

Tutte le risposte di errore hanno questo formato:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      // ... dettagli aggiuntivi se disponibili
    }
  },
  "meta": {
    "request_id": "req_abc123xyz",
    "timestamp": "2025-11-01T10:30:00Z"
  }
}
```

## Codici di Errore

### HTTP Status Codes

| Status | Significato |
|--------|-------------|
| 200 | Success - Richiesta completata |
| 400 | Bad Request - Parametri invalidi |
| 401 | Unauthorized - API key mancante o invalida |
| 403 | Forbidden - API key non autorizzata per endpoint |
| 404 | Not Found - Endpoint non esistente |
| 429 | Too Many Requests - Rate limit superato |
| 500 | Internal Server Error - Errore server |
| 503 | Service Unavailable - Servizio temporaneamente non disponibile |

### Application Error Codes

| Code | Descrizione |
|------|-------------|
| `INVALID_DOMAIN` | Dominio non valido |
| `INVALID_IP` | Indirizzo IP non valido |
| `INVALID_URL` | URL non valida |
| `DOMAIN_NOT_FOUND` | Dominio non risolve |
| `WHOIS_TIMEOUT` | Timeout query WHOIS |
| `DNS_TIMEOUT` | Timeout query DNS |
| `RATE_LIMIT_EXCEEDED` | Rate limit superato |
| `INVALID_API_KEY` | API key non valida |
| `API_KEY_EXPIRED` | API key scaduta |
| `INSUFFICIENT_CREDITS` | Crediti insufficienti |
| `FEATURE_NOT_AVAILABLE` | Feature non disponibile per piano |
| `INTERNAL_ERROR` | Errore interno server |

### Esempi di Errori

**Invalid Domain**:
```json
{
  "success": false,
  "error": {
    "code": "INVALID_DOMAIN",
    "message": "The provided domain name is not valid",
    "details": {
      "domain": "invalid..domain",
      "reason": "Double dot not allowed"
    }
  }
}
```

**Rate Limit**:
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit of 100 requests per hour exceeded",
    "details": {
      "limit": 100,
      "reset_at": "2025-11-01T11:00:00Z",
      "retry_after": 1800
    }
  }
}
```

## Esempi

### JavaScript (Fetch)

```javascript
const apiKey = 'your_api_key_here';

async function checkDNS(domain) {
  const response = await fetch('https://api.controllodomini.it/v2/dns/lookup', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': apiKey
    },
    body: JSON.stringify({
      domain: domain,
      check_subdomains: true
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log('DNS Records:', data.data.records);
  } else {
    console.error('Error:', data.error.message);
  }
}

checkDNS('example.com');
```

### Python (requests)

```python
import requests

API_KEY = 'your_api_key_here'
BASE_URL = 'https://api.controllodomini.it/v2'

def check_whois(domain):
    headers = {
        'X-API-Key': API_KEY,
        'Content-Type': 'application/json'
    }

    payload = {
        'domain': domain
    }

    response = requests.post(
        f'{BASE_URL}/whois/lookup',
        headers=headers,
        json=payload
    )

    data = response.json()

    if data['success']:
        print(f"Domain: {data['data']['domain']}")
        print(f"Registrar: {data['data']['registrar']['name']}")
        print(f"Expires: {data['data']['dates']['expires']}")
    else:
        print(f"Error: {data['error']['message']}")

check_whois('example.com')
```

### PHP (cURL)

```php
<?php

$apiKey = 'your_api_key_here';
$baseUrl = 'https://api.controllodomini.it/v2';

function checkBlacklist($domain) {
    global $apiKey, $baseUrl;

    $ch = curl_init($baseUrl . '/blacklist/check');

    $payload = json_encode([
        'domain' => $domain,
        'check_www' => true
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data['success']) {
        echo "Reputation Score: " . $data['data']['reputation_score'] . "\n";
        echo "Status: " . $data['data']['summary']['status'] . "\n";
    } else {
        echo "Error: " . $data['error']['message'] . "\n";
    }
}

checkBlacklist('example.com');
```

### Node.js (axios)

```javascript
const axios = require('axios');

const API_KEY = 'your_api_key_here';
const BASE_URL = 'https://api.controllodomini.it/v2';

async function analyzeSSL(domain) {
  try {
    const response = await axios.post(
      `${BASE_URL}/ssl/analyze`,
      {
        domain: domain,
        port: 443
      },
      {
        headers: {
          'X-API-Key': API_KEY,
          'Content-Type': 'application/json'
        }
      }
    );

    const { data } = response.data;
    console.log(`SSL Grade: ${data.grade}`);
    console.log(`Certificate Valid Until: ${data.certificate.valid_to}`);
    console.log(`TLS 1.3 Supported: ${data.protocols['TLSv1.3']}`);

  } catch (error) {
    if (error.response) {
      console.error('Error:', error.response.data.error.message);
    } else {
      console.error('Error:', error.message);
    }
  }
}

analyzeSSL('example.com');
```

### cURL (Command Line)

```bash
# DNS Lookup
curl -X POST https://api.controllodomini.it/v2/dns/lookup \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "domain": "example.com",
    "check_subdomains": true
  }' | jq

# WHOIS Lookup
curl -X POST https://api.controllodomini.it/v2/whois/lookup \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}' | jq

# Blacklist Check
curl -X POST https://api.controllodomini.it/v2/blacklist/check \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "domain": "example.com",
    "check_www": true,
    "mode": "parallel"
  }' | jq
```

## SDK e Librerie

### SDK Ufficiali

| Linguaggio | Repository | Installazione |
|------------|-----------|---------------|
| PHP | `gtechgroup/controllodomini-php` | `composer require gtechgroup/controllodomini-php` |
| JavaScript/Node.js | `gtechgroup/controllodomini-js` | `npm install @gtechgroup/controllodomini` |
| Python | `gtechgroup/controllodomini-python` | `pip install controllodomini` |

### Esempio con SDK PHP

```php
<?php
require 'vendor/autoload.php';

use ControlloDomin\Client;

$client = new Client('your_api_key_here');

// DNS Lookup
$dns = $client->dns()->lookup('example.com', [
    'check_subdomains' => true
]);

// WHOIS Lookup
$whois = $client->whois()->lookup('example.com');

// Blacklist Check
$blacklist = $client->blacklist()->check('example.com');

// SSL Analysis
$ssl = $client->ssl()->analyze('example.com');
```

### Esempio con SDK JavaScript

```javascript
const ControlloDomin = require('@gtechgroup/controllodomini');

const client = new ControlloDomin('your_api_key_here');

// DNS Lookup
const dns = await client.dns.lookup('example.com', {
  checkSubdomains: true
});

// WHOIS Lookup
const whois = await client.whois.lookup('example.com');

// Blacklist Check
const blacklist = await client.blacklist.check('example.com');

// SSL Analysis
const ssl = await client.ssl.analyze('example.com');
```

## Best Practices

### 1. Error Handling

Gestisci sempre gli errori:

```javascript
try {
  const result = await client.dns.lookup(domain);
  // Handle success
} catch (error) {
  if (error.code === 'RATE_LIMIT_EXCEEDED') {
    // Wait and retry
    await sleep(error.retryAfter * 1000);
  } else if (error.code === 'INVALID_DOMAIN') {
    // Notify user
  } else {
    // Log error
  }
}
```

### 2. Rate Limit Management

Monitora i rate limits:

```javascript
const response = await fetch(url, options);
const remaining = response.headers.get('X-RateLimit-Remaining');

if (remaining < 10) {
  console.warn('Rate limit quasi esaurito!');
}
```

### 3. Caching Lato Client

Implementa caching per ridurre chiamate:

```javascript
const cache = new Map();
const CACHE_TTL = 3600000; // 1 ora

async function getCachedDNS(domain) {
  const cached = cache.get(domain);

  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.data;
  }

  const data = await client.dns.lookup(domain);
  cache.set(domain, { data, timestamp: Date.now() });

  return data;
}
```

### 4. Timeout Management

Imposta timeout appropriati:

```javascript
const controller = new AbortController();
const timeout = setTimeout(() => controller.abort(), 30000);

try {
  const response = await fetch(url, {
    signal: controller.signal,
    ...options
  });
} finally {
  clearTimeout(timeout);
}
```

## Webhook (Future Feature)

**Coming Soon**: Webhook per notifiche asincrone di task long-running.

```json
{
  "webhook_url": "https://yoursite.com/webhook",
  "events": ["scan.completed", "certificate.expiring"],
  "secret": "your_webhook_secret"
}
```

## Supporto

- **Documentazione**: https://controllodomini.it/api-docs
- **Email**: api-support@controllodomini.it
- **Status Page**: https://status.controllodomini.it
- **GitHub Issues**: https://github.com/gtechgroup/controllodomini/issues

---

**Ultimo aggiornamento**: Novembre 2025
**Versione API**: v2.0
