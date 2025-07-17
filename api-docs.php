<?php
/**
 * Documentazione API - Guida completa alle API
 * 
 * @author G Tech Group
 * @version 4.0
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
$current_page = 'api-docs';

// Meta tags specifici per questa pagina
$page_title = "Documentazione API - REST API Reference | " . (defined('APP_NAME') ? APP_NAME : 'Controllo Domini');
$page_description = "Documentazione completa delle API di Controllo Domini. Endpoints, autenticazione, esempi di codice e rate limits per integrare i nostri servizi.";
$canonical_url = (defined('APP_URL') ? APP_URL : 'https://controllodomini.it') . "/api-docs";

// Resto del codice...

// Includi header
include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero hero-secondary">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" data-aos="fade-up">
                API Documentation
                <span class="hero-gradient">REST API v2.0</span>
            </h1>
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                Integra le nostre funzionalità di analisi domini nella tua applicazione
            </p>
            <div class="api-badges" data-aos="fade-up" data-aos-delay="200">
                <span class="api-badge">RESTful</span>
                <span class="api-badge">JSON</span>
                <span class="api-badge">HTTPS</span>
                <span class="api-badge">Rate Limited</span>
            </div>
        </div>
    </div>
</section>

<!-- API Documentation -->
<section class="api-docs-section">
    <div class="container">
        <div class="docs-layout">
            <!-- Sidebar Navigation -->
            <aside class="docs-sidebar" data-aos="fade-right">
                <nav class="docs-nav">
                    <h3>Contenuti</h3>
                    <ul>
                        <li><a href="#introduction" class="active">Introduzione</a></li>
                        <li><a href="#authentication">Autenticazione</a></li>
                        <li><a href="#rate-limits">Rate Limits</a></li>
                        <li><a href="#endpoints">Endpoints</a></li>
                        <li><a href="#dns-lookup">DNS Lookup</a></li>
                        <li><a href="#whois-lookup">WHOIS Lookup</a></li>
                        <li><a href="#blacklist-check">Blacklist Check</a></li>
                        <li><a href="#cloud-detection">Cloud Detection</a></li>
                        <li><a href="#errors">Gestione Errori</a></li>
                        <li><a href="#examples">Esempi di Codice</a></li>
                        <li><a href="#sdks">SDK & Librerie</a></li>
                        <li><a href="#changelog">Changelog API</a></li>
                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="docs-content" data-aos="fade-up">
                <!-- Introduction -->
                <section id="introduction" class="docs-section">
                    <h2>Introduzione</h2>
                    <p>L'API di Controllo Domini permette di accedere programmaticamente a tutte le funzionalità di analisi domini disponibili sul nostro sito. Puoi verificare DNS, WHOIS, blacklist e servizi cloud direttamente dalla tua applicazione.</p>
                    
                    <div class="info-box">
                        <h4>Base URL</h4>
                        <code class="code-block">https://api.controllodomini.it/v2</code>
                    </div>

                    <h3>Caratteristiche principali</h3>
                    <ul class="feature-list">
                        <li>✓ Risposte JSON strutturate</li>
                        <li>✓ Autenticazione tramite API Key</li>
                        <li>✓ Rate limiting flessibile</li>
                        <li>✓ Supporto CORS</li>
                        <li>✓ Endpoint RESTful</li>
                        <li>✓ HTTPS obbligatorio</li>
                    </ul>

                    <h3>Quick Start</h3>
                    <div class="code-example">
                        <div class="code-header">
                            <span>cURL</span>
                            <button onclick="copyCode(this)" class="copy-button">Copia</button>
                        </div>
                        <pre><code>curl -X GET "https://api.controllodomini.it/v2/dns/example.com" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json"</code></pre>
                    </div>
                </section>

                <!-- Authentication -->
                <section id="authentication" class="docs-section">
                    <h2>Autenticazione</h2>
                    <p>Tutte le richieste API richiedono autenticazione tramite API Key. Puoi ottenere la tua API Key registrandoti gratuitamente.</p>

                    <h3>Ottenere una API Key</h3>
                    <ol>
                        <li>Registrati su <a href="/register">controllodomini.it/register</a></li>
                        <li>Verifica il tuo indirizzo email</li>
                        <li>Accedi alla dashboard e genera una nuova API Key</li>
                        <li>Copia la chiave (verrà mostrata una sola volta)</li>
                    </ol>

                    <h3>Utilizzo della API Key</h3>
                    <p>Includi la tua API Key nell'header <code>Authorization</code> di ogni richiesta:</p>

                    <div class="code-example">
                        <div class="code-header">
                            <span>HTTP Header</span>
                            <button onclick="copyCode(this)" class="copy-button">Copia</button>
                        </div>
                        <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                    </div>

                    <div class="warning-box">
                        <h4>⚠️ Sicurezza API Key</h4>
                        <ul>
                            <li>Non condividere mai la tua API Key pubblicamente</li>
                            <li>Non includere la API Key nel codice client-side</li>
                            <li>Usa variabili d'ambiente per memorizzare le chiavi</li>
                            <li>Rigenera la chiave se compromessa</li>
                        </ul>
                    </div>
                </section>

                <!-- Rate Limits -->
                <section id="rate-limits" class="docs-section">
                    <h2>Rate Limits</h2>
                    <p>I rate limits proteggono l'API da un uso eccessivo e garantiscono un servizio equo per tutti gli utenti.</p>

                    <h3>Limiti per piano</h3>
                    <table class="rate-limits-table">
                        <thead>
                            <tr>
                                <th>Piano</th>
                                <th>Richieste/minuto</th>
                                <th>Richieste/giorno</th>
                                <th>Burst limit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Free</td>
                                <td>10</td>
                                <td>1,000</td>
                                <td>20</td>
                            </tr>
                            <tr>
                                <td>Basic</td>
                                <td>60</td>
                                <td>10,000</td>
                                <td>100</td>
                            </tr>
                            <tr>
                                <td>Pro</td>
                                <td>300</td>
                                <td>50,000</td>
                                <td>500</td>
                            </tr>
                            <tr>
                                <td>Enterprise</td>
                                <td>Custom</td>
                                <td>Custom</td>
                                <td>Custom</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>Headers di Rate Limit</h3>
                    <p>Ogni risposta include headers che indicano lo stato del rate limit:</p>
                    
                    <div class="code-example">
                        <div class="code-header">
                            <span>Response Headers</span>
                        </div>
                        <pre><code>X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
X-RateLimit-Reset-After: 3600</code></pre>
                    </div>
                </section>

                <!-- Endpoints -->
                <section id="endpoints" class="docs-section">
                    <h2>Endpoints Disponibili</h2>
                    <p>Lista completa degli endpoint API disponibili:</p>

                    <div class="endpoints-grid">
                        <div class="endpoint-card">
                            <h4>DNS Lookup</h4>
                            <code>GET /dns/{domain}</code>
                            <p>Recupera tutti i record DNS di un dominio</p>
                        </div>
                        <div class="endpoint-card">
                            <h4>WHOIS Lookup</h4>
                            <code>GET /whois/{domain}</code>
                            <p>Ottieni informazioni WHOIS del dominio</p>
                        </div>
                        <div class="endpoint-card">
                            <h4>Blacklist Check</h4>
                            <code>GET /blacklist/{domain_or_ip}</code>
                            <p>Verifica presenza in blacklist</p>
                        </div>
                        <div class="endpoint-card">
                            <h4>Cloud Detection</h4>
                            <code>GET /cloud/{domain}</code>
                            <p>Identifica servizi cloud utilizzati</p>
                        </div>
                        <div class="endpoint-card">
                            <h4>Full Analysis</h4>
                            <code>GET /analyze/{domain}</code>
                            <p>Analisi completa del dominio</p>
                        </div>
                        <div class="endpoint-card">
                            <h4>Bulk Analysis</h4>
                            <code>POST /bulk/analyze</code>
                            <p>Analizza multipli domini</p>
                        </div>
                    </div>
                </section>

                <!-- DNS Lookup -->
                <section id="dns-lookup" class="docs-section">
                    <h2>DNS Lookup</h2>
                    <p>Recupera tutti i record DNS per un dominio specifico.</p>

                    <div class="endpoint-details">
                        <h3>Request</h3>
                        <code class="endpoint-url">GET /dns/{domain}</code>
                        
                        <h4>Parametri</h4>
                        <table class="params-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Richiesto</th>
                                    <th>Descrizione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>domain</code></td>
                                    <td>string</td>
                                    <td>Sì</td>
                                    <td>Il dominio da analizzare</td>
                                </tr>
                                <tr>
                                    <td><code>types</code></td>
                                    <td>string</td>
                                    <td>No</td>
                                    <td>Tipi di record da recuperare (comma-separated)</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4>Esempio Request</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>cURL</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>curl -X GET "https://api.controllodomini.it/v2/dns/example.com?types=A,MX,TXT" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json"</code></pre>
                        </div>

                        <h3>Response</h3>
                        <div class="code-example">
                            <div class="code-header">
                                <span>200 OK</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>{
  "success": true,
  "domain": "example.com",
  "timestamp": "2024-01-20T10:30:00Z",
  "records": {
    "A": [
      {
        "ip": "93.184.216.34",
        "ttl": 3600,
        "type": "A"
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
    ]
  },
  "response_time_ms": 145
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- WHOIS Lookup -->
                <section id="whois-lookup" class="docs-section">
                    <h2>WHOIS Lookup</h2>
                    <p>Ottieni informazioni complete sulla registrazione del dominio.</p>

                    <div class="endpoint-details">
                        <h3>Request</h3>
                        <code class="endpoint-url">GET /whois/{domain}</code>
                        
                        <h4>Esempio Response</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>200 OK</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>{
  "success": true,
  "domain": "example.com",
  "status": "active",
  "registrar": "Example Registrar Inc.",
  "creation_date": "1995-08-14T00:00:00Z",
  "expiry_date": "2025-08-13T00:00:00Z",
  "updated_date": "2024-01-15T00:00:00Z",
  "nameservers": [
    "ns1.example.com",
    "ns2.example.com"
  ],
  "registrant": {
    "name": "REDACTED FOR PRIVACY",
    "organization": "Example Organization",
    "country": "US"
  },
  "domain_status": [
    "clientTransferProhibited",
    "clientUpdateProhibited"
  ]
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Blacklist Check -->
                <section id="blacklist-check" class="docs-section">
                    <h2>Blacklist Check</h2>
                    <p>Verifica se un dominio o IP è presente in blacklist di spam.</p>

                    <div class="endpoint-details">
                        <h3>Request</h3>
                        <code class="endpoint-url">GET /blacklist/{domain_or_ip}</code>
                        
                        <h4>Esempio Response</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>200 OK</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>{
  "success": true,
  "target": "example.com",
  "ips_checked": ["93.184.216.34"],
  "reputation": {
    "score": 98,
    "status": "Excellent",
    "color": "success"
  },
  "blacklists": {
    "checked": 45,
    "listed": 1,
    "issues": [
      {
        "ip": "93.184.216.34",
        "blacklist": "Example RBL",
        "dnsbl": "rbl.example.org",
        "status": "listed",
        "reason": "Spam reports"
      }
    ]
  },
  "checked_at": "2024-01-20T10:30:00Z"
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Cloud Detection -->
                <section id="cloud-detection" class="docs-section">
                    <h2>Cloud Detection</h2>
                    <p>Identifica quali servizi cloud e CDN utilizza un dominio.</p>

                    <div class="endpoint-details">
                        <h3>Request</h3>
                        <code class="endpoint-url">GET /cloud/{domain}</code>
                        
                        <h4>Esempio Response</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>200 OK</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>{
  "success": true,
  "domain": "example.com",
  "detected": [
    {
      "name": "Cloudflare",
      "type": "CDN/Security",
      "confidence": 100,
      "evidence": [
        "NS records point to cloudflare.com",
        "Cloudflare IP ranges detected"
      ]
    },
    {
      "name": "Google Workspace",
      "type": "Email Service",
      "confidence": 100,
      "evidence": [
        "MX records point to google.com",
        "SPF includes _spf.google.com"
      ]
    }
  ],
  "email_services": [
    {
      "provider": "Google Workspace",
      "mx_record": "aspmx.l.google.com",
      "priority": 1
    }
  ],
  "cdn_services": [
    {
      "name": "Cloudflare",
      "details": {
        "ray_id_support": true,
        "anycast": true
      }
    }
  ]
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Error Handling -->
                <section id="errors" class="docs-section">
                    <h2>Gestione Errori</h2>
                    <p>L'API utilizza codici di stato HTTP standard e restituisce errori in formato JSON.</p>

                    <h3>Formato Errore</h3>
                    <div class="code-example">
                        <div class="code-header">
                            <span>Error Response</span>
                        </div>
                        <pre><code>{
  "success": false,
  "error": {
    "code": "INVALID_DOMAIN",
    "message": "The provided domain is not valid",
    "details": "Domain contains invalid characters"
  },
  "timestamp": "2024-01-20T10:30:00Z"
}</code></pre>
                    </div>

                    <h3>Codici di Errore Comuni</h3>
                    <table class="error-codes-table">
                        <thead>
                            <tr>
                                <th>HTTP Status</th>
                                <th>Error Code</th>
                                <th>Descrizione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>400</td>
                                <td>INVALID_DOMAIN</td>
                                <td>Dominio non valido o malformato</td>
                            </tr>
                            <tr>
                                <td>401</td>
                                <td>UNAUTHORIZED</td>
                                <td>API Key mancante o non valida</td>
                            </tr>
                            <tr>
                                <td>403</td>
                                <td>FORBIDDEN</td>
                                <td>Accesso negato alla risorsa</td>
                            </tr>
                            <tr>
                                <td>404</td>
                                <td>NOT_FOUND</td>
                                <td>Endpoint o risorsa non trovata</td>
                            </tr>
                            <tr>
                                <td>429</td>
                                <td>RATE_LIMITED</td>
                                <td>Troppo richieste, rate limit superato</td>
                            </tr>
                            <tr>
                                <td>500</td>
                                <td>INTERNAL_ERROR</td>
                                <td>Errore interno del server</td>
                            </tr>
                            <tr>
                                <td>503</td>
                                <td>SERVICE_UNAVAILABLE</td>
                                <td>Servizio temporaneamente non disponibile</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Code Examples -->
                <section id="examples" class="docs-section">
                    <h2>Esempi di Codice</h2>
                    
                    <div class="example-tabs">
                        <button class="example-tab active" onclick="showExample('php')">PHP</button>
                        <button class="example-tab" onclick="showExample('python')">Python</button>
                        <button class="example-tab" onclick="showExample('javascript')">JavaScript</button>
                        <button class="example-tab" onclick="showExample('ruby')">Ruby</button>
                    </div>

                    <!-- PHP Example -->
                    <div id="example-php" class="example-content active">
                        <h4>PHP con cURL</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>PHP</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>&lt;?php
$domain = "example.com";
$apiKey = "YOUR_API_KEY";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.controllodomini.it/v2/dns/{$domain}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    print_r($data);
} else {
    echo "Error: HTTP {$httpCode}\n";
    echo $response;
}
?&gt;</code></pre>
                        </div>
                    </div>

                    <!-- Python Example -->
                    <div id="example-python" class="example-content">
                        <h4>Python con requests</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>Python</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>import requests

domain = "example.com"
api_key = "YOUR_API_KEY"

headers = {
    "Authorization": f"Bearer {api_key}",
    "Accept": "application/json"
}

response = requests.get(
    f"https://api.controllodomini.it/v2/dns/{domain}",
    headers=headers
)

if response.status_code == 200:
    data = response.json()
    print(data)
else:
    print(f"Error: HTTP {response.status_code}")
    print(response.text)</code></pre>
                        </div>
                    </div>

                    <!-- JavaScript Example -->
                    <div id="example-javascript" class="example-content">
                        <h4>JavaScript con Fetch API</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>JavaScript</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>const domain = 'example.com';
const apiKey = 'YOUR_API_KEY';

fetch(`https://api.controllodomini.it/v2/dns/${domain}`, {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Accept': 'application/json'
    }
})
.then(response => {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
})
.then(data => {
    console.log(data);
})
.catch(error => {
    console.error('Error:', error);
});</code></pre>
                        </div>
                    </div>

                    <!-- Ruby Example -->
                    <div id="example-ruby" class="example-content">
                        <h4>Ruby con Net::HTTP</h4>
                        <div class="code-example">
                            <div class="code-header">
                                <span>Ruby</span>
                                <button onclick="copyCode(this)" class="copy-button">Copia</button>
                            </div>
                            <pre><code>require 'net/http'
require 'json'

domain = 'example.com'
api_key = 'YOUR_API_KEY'

uri = URI("https://api.controllodomini.it/v2/dns/#{domain}")
http = Net::HTTP.new(uri.host, uri.port)
http.use_ssl = true

request = Net::HTTP::Get.new(uri)
request['Authorization'] = "Bearer #{api_key}"
request['Accept'] = 'application/json'

response = http.request(request)

if response.code == '200'
  data = JSON.parse(response.body)
  puts data
else
  puts "Error: HTTP #{response.code}"
  puts response.body
end</code></pre>
                        </div>
                    </div>
                </section>

                <!-- SDKs -->
                <section id="sdks" class="docs-section">
                    <h2>SDK & Librerie</h2>
                    <p>Librerie ufficiali e community-maintained per vari linguaggi di programmazione.</p>

                    <div class="sdk-grid">
                        <div class="sdk-card">
                            <div class="sdk-icon">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="#777BB4">
                                    <path d="M20 5C11.7 5 5 11.7 5 20s6.7 15 15 15 15-6.7 15-15S28.3 5 20 5zm-2.5 22.5v-3h5v3h-5zm7.5-5h-10v-3h10v3zm0-5h-10v-3h10v3z"/>
                                </svg>
                            </div>
                            <h4>PHP SDK</h4>
                            <p>Libreria PHP ufficiale con supporto Composer</p>
                            <code>composer require gtechgroup/controllodomini-php</code>
                            <a href="https://github.com/gtechgroupit/controllodomini-php" target="_blank" class="sdk-link">
                                Documentazione →
                            </a>
                        </div>

                        <div class="sdk-card">
                            <div class="sdk-icon">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="#3776AB">
                                    <path d="M20 5C11.7 5 5 11.7 5 20s6.7 15 15 15 15-6.7 15-15S28.3 5 20 5zm0 25c-5.5 0-10-4.5-10-10s4.5-10 10-10 10 4.5 10 10-4.5 10-10 10z"/>
                                </svg>
                            </div>
                            <h4>Python SDK</h4>
                            <p>Package Python con supporto async</p>
                            <code>pip install controllodomini</code>
                            <a href="https://github.com/gtechgroupit/controllodomini-python" target="_blank" class="sdk-link">
                                Documentazione →
                            </a>
                        </div>

                        <div class="sdk-card">
                            <div class="sdk-icon">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="#F7DF1E">
                                    <path d="M5 5v30h30V5H5zm16.4 23.1c0 2.9-1.7 4.2-4.2 4.2-2.2 0-3.5-1.2-4.2-2.6l2.3-1.4c.4.8 1 1.4 1.9 1.4.8 0 1.3-.4 1.3-1.9v-8.2h2.9v8.5z"/>
                                </svg>
                            </div>
                            <h4>Node.js SDK</h4>
                            <p>Client JavaScript/TypeScript per Node.js</p>
                            <code>npm install @gtechgroup/controllodomini</code>
                            <a href="https://github.com/gtechgroupit/controllodomini-node" target="_blank" class="sdk-link">
                                Documentazione →
                            </a>
                        </div>

                        <div class="sdk-card coming-soon">
                            <div class="sdk-icon">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="#CC342D">
                                    <path d="M20 5l-8.7 8.7v12.6L20 35l8.7-8.7V13.7L20 5zm0 4.3l5.8 5.8v9.8L20 30.7l-5.8-5.8v-9.8L20 9.3z"/>
                                </svg>
                            </div>
                            <h4>Ruby Gem</h4>
                            <p>Gem Ruby (Coming Soon)</p>
                            <code>gem install controllodomini</code>
                            <span class="coming-soon-badge">In sviluppo</span>
                        </div>
                    </div>
                </section>

                <!-- API Changelog -->
                <section id="changelog" class="docs-section">
                    <h2>Changelog API</h2>
                    
                    <div class="changelog-item">
                        <div class="changelog-header">
                            <h4>v2.0.0</h4>
                            <span class="changelog-date">20 Gennaio 2024</span>
                        </div>
                        <ul class="changelog-list">
                            <li>🚀 Nuova versione major dell'API</li>
                            <li>✨ Aggiunto endpoint /bulk/analyze per analisi multiple</li>
                            <li>⚡ Miglioramento performance del 40%</li>
                            <li>🔧 Standardizzazione delle risposte JSON</li>
                            <li>📝 Documentazione completamente riscritta</li>
                        </ul>
                    </div>

                    <div class="changelog-item">
                        <div class="changelog-header">
                            <h4>v1.5.0</h4>
                            <span class="changelog-date">15 Dicembre 2023</span>
                        </div>
                        <ul class="changelog-list">
                            <li>✨ Aggiunto supporto per record CAA</li>
                            <li>🐛 Fix timeout su domini con molti record</li>
                            <li>📊 Nuovi campi nella risposta cloud detection</li>
                        </ul>
                    </div>

                    <div class="changelog-item">
                        <div class="changelog-header">
                            <h4>v1.4.0</h4>
                            <span class="changelog-date">1 Novembre 2023</span>
                        </div>
                        <ul class="changelog-list">
                            <li>✨ Supporto IPv6 completo</li>
                            <li>🔒 Miglioramenti sicurezza autenticazione</li>
                            <li>📈 Aumento rate limits per piani Pro</li>
                        </ul>
                    </div>
                </section>

                <!-- Support -->
                <section class="docs-support">
                    <h2>Hai bisogno di aiuto?</h2>
                    <p>Il nostro team di supporto è qui per aiutarti con l'integrazione delle API.</p>
                    
                    <div class="support-options">
                        <a href="/contatti" class="support-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <h4>Supporto Email</h4>
                            <p>api@controllodomini.it</p>
                        </a>
                        
                        <a href="https://github.com/gtechgroupit/controllodomini-api/issues" target="_blank" class="support-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            <h4>GitHub Issues</h4>
                            <p>Segnala problemi</p>
                        </a>
                        
                        <a href="https://discord.gg/controllodomini" target="_blank" class="support-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                            </svg>
                            <h4>Discord Community</h4>
                            <p>Chat in tempo reale</p>
                        </a>
                    </div>
                </section>
            </main>
        </div>
    </div>
</section>

<script>
// Funzione per mostrare tab esempi
function showExample(language) {
    // Rimuovi active da tutti
    document.querySelectorAll('.example-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.example-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Aggiungi active al selezionato
    event.target.classList.add('active');
    document.getElementById(`example-${language}`).classList.add('active');
}

// Funzione per copiare codice
function copyCode(button) {
    const codeBlock = button.closest('.code-example').querySelector('code');
    const text = codeBlock.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.textContent;
        button.textContent = 'Copiato!';
        button.classList.add('copied');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);
    });
}

// Smooth scroll per navigation
document.querySelectorAll('.docs-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Rimuovi active da tutti
        document.querySelectorAll('.docs-nav a').forEach(a => a.classList.remove('active'));
        this.classList.add('active');
        
        // Scroll alla sezione
        const targetId = this.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        
        if (targetSection) {
            targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Highlight sezione attiva durante scroll
const sections = document.querySelectorAll('.docs-section');
const navLinks = document.querySelectorAll('.docs-nav a');

window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (pageYOffset >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').substring(1) === current) {
            link.classList.add('active');
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>
