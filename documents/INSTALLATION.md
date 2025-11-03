# Guida Installazione - Controllo Domini

## Indice

1. [Requisiti Sistema](#requisiti-sistema)
2. [Installazione Development](#installazione-development)
3. [Installazione Production](#installazione-production)
4. [Configurazione Web Server](#configurazione-web-server)
5. [Verifica Installazione](#verifica-installazione)
6. [Troubleshooting](#troubleshooting)
7. [Aggiornamento](#aggiornamento)

## Requisiti Sistema

### Requisiti Minimi

| Componente | Versione Minima | Versione Raccomandata |
|------------|-----------------|----------------------|
| PHP | 7.4 | 8.2+ |
| Apache | 2.4 | 2.4.57+ |
| RAM | 512 MB | 2 GB |
| Disk Space | 100 MB | 500 MB |
| OS | Linux/Unix | Ubuntu 22.04 LTS |

### Estensioni PHP Richieste

```bash
# Verifica estensioni installate
php -m

# Estensioni richieste:
- json       (Parsing JSON - REQUIRED)
- curl       (HTTP requests - REQUIRED)
- mbstring   (Multibyte string handling - REQUIRED)
- openssl    (SSL/TLS analysis - REQUIRED)
- xml        (XML parsing per sitemap - RECOMMENDED)
- dom        (DOM manipulation - RECOMMENDED)
- sockets    (WHOIS connections - OPTIONAL ma raccomandato)
```

### Moduli Apache Richiesti

```bash
# Verifica moduli abilitati
apache2ctl -M

# Moduli richiesti:
- mod_rewrite    (URL rewriting - REQUIRED)
- mod_headers    (Custom headers - REQUIRED)
- mod_deflate    (Gzip compression - RECOMMENDED)
- mod_expires    (Cache headers - RECOMMENDED)
- mod_ssl        (HTTPS - RECOMMENDED per produzione)
```

### Accesso Network Richiesto

L'applicazione deve poter accedere a:

- **DNS Servers** (porta 53 UDP/TCP)
  - 8.8.8.8 (Google DNS)
  - 1.1.1.1 (Cloudflare DNS)
  - DNS server configurati nel sistema

- **WHOIS Servers** (porta 43 TCP)
  - whois.verisign-grs.com
  - whois.nic.it
  - Altri server WHOIS per vari TLD

- **DNSBL Servers** (porta 53)
  - zen.spamhaus.org
  - bl.spamcop.net
  - Altri server blacklist

- **Target Websites** (porte 80, 443)
  - Per analisi SSL, headers, performance, ecc.

## Installazione Development

### 1. Clone Repository

```bash
# Via HTTPS
git clone https://github.com/gtechgroup/controllo-domini.git
cd controllo-domini

# Oppure via SSH
git clone git@github.com:gtechgroup/controllo-domini.git
cd controllo-domini
```

### 2. Installazione su Ubuntu/Debian

```bash
# Update package list
sudo apt update

# Installa Apache
sudo apt install apache2 -y

# Installa PHP e estensioni
sudo apt install php php-cli php-curl php-mbstring php-xml php-json -y

# Abilita moduli Apache
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod ssl

# Riavvia Apache
sudo systemctl restart apache2
```

### 3. Installazione su CentOS/RHEL

```bash
# Installa Apache
sudo yum install httpd -y

# Installa PHP e estensioni
sudo yum install php php-cli php-curl php-mbstring php-xml php-json -y

# Abilita moduli Apache (già inclusi generalmente)
# Modifica /etc/httpd/conf/httpd.conf se necessario

# Avvia e abilita Apache
sudo systemctl start httpd
sudo systemctl enable httpd
```

### 4. Installazione su macOS (MAMP/XAMPP)

**Opzione A: MAMP**

```bash
# Scarica MAMP da https://www.mamp.info/
# Installa MAMP
# Copia controllo-domini in /Applications/MAMP/htdocs/

# Configura PHP 7.4+ in MAMP preferences
# Abilita mod_rewrite in Apache config
```

**Opzione B: Homebrew**

```bash
# Installa Apache
brew install httpd

# Installa PHP
brew install php@8.2

# Modifica /usr/local/etc/httpd/httpd.conf per abilitare mod_rewrite
# Avvia Apache
brew services start httpd
```

### 5. Installazione su Windows (XAMPP)

```bash
# 1. Scarica XAMPP da https://www.apachefriends.org/
# 2. Installa XAMPP (default C:\xampp)
# 3. Copia cartella controllo-domini in C:\xampp\htdocs\
# 4. Modifica C:\xampp\apache\conf\httpd.conf:
#    - Trova "LoadModule rewrite_module" e decommenta
#    - Trova "AllowOverride None" e cambia in "AllowOverride All"
# 5. Riavvia Apache dal XAMPP Control Panel
```

### 6. Configura Permessi

```bash
# Linux/macOS
cd /path/to/controllo-domini

# Imposta ownership (Apache user)
sudo chown -R www-data:www-data .

# Imposta permessi directory
find . -type d -exec chmod 755 {} \;

# Imposta permessi file
find . -type f -exec chmod 644 {} \;

# Permessi speciali se necessario
chmod 755 bootstrap.php
```

### 7. Configura Apache Virtual Host (Development)

**Crea file virtual host**:

```bash
# Ubuntu/Debian
sudo nano /etc/apache2/sites-available/controllodomini.local.conf

# CentOS/RHEL
sudo nano /etc/httpd/conf.d/controllodomini.local.conf
```

**Contenuto**:

```apache
<VirtualHost *:80>
    ServerName controllodomini.local
    ServerAlias www.controllodomini.local

    DocumentRoot /var/www/controllo-domini

    <Directory /var/www/controllo-domini>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/controllodomini-error.log
    CustomLog ${APACHE_LOG_DIR}/controllodomini-access.log combined
</VirtualHost>
```

**Abilita virtual host**:

```bash
# Ubuntu/Debian
sudo a2ensite controllodomini.local.conf
sudo systemctl reload apache2

# CentOS/RHEL
sudo systemctl reload httpd
```

**Aggiungi a /etc/hosts**:

```bash
sudo nano /etc/hosts

# Aggiungi:
127.0.0.1   controllodomini.local
127.0.0.1   www.controllodomini.local
```

### 8. Verifica Installazione Development

Visita: http://controllodomini.local

Dovresti vedere la homepage dell'applicazione.

## Installazione Production

### 1. Preparazione Server

```bash
# Aggiorna sistema
sudo apt update && sudo apt upgrade -y

# Installa firewall
sudo apt install ufw -y
sudo ufw allow 'Apache Full'
sudo ufw enable

# Installa Certbot per SSL (Let's Encrypt)
sudo apt install certbot python3-certbot-apache -y
```

### 2. Clone Repository

```bash
# Clone in directory web root
cd /var/www
sudo git clone https://github.com/gtechgroup/controllo-domini.git
cd controllo-domini

# Checkout versione stabile
git checkout tags/v4.0
```

### 3. Configura Permessi Production

```bash
# Ownership Apache
sudo chown -R www-data:www-data /var/www/controllo-domini

# Permessi restrittivi
find /var/www/controllo-domini -type d -exec chmod 755 {} \;
find /var/www/controllo-domini -type f -exec chmod 644 {} \;

# Proteggi file sensibili
chmod 640 /var/www/controllo-domini/config/config.php
chmod 640 /var/www/controllo-domini/.htaccess
```

### 4. Configura Apache Virtual Host (Production)

**HTTP Virtual Host**:

```bash
sudo nano /etc/apache2/sites-available/controllodomini.it.conf
```

```apache
<VirtualHost *:80>
    ServerName controllodomini.it
    ServerAlias www.controllodomini.it

    DocumentRoot /var/www/controllo-domini

    <Directory /var/www/controllo-domini>
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    ErrorLog ${APACHE_LOG_DIR}/controllodomini-error.log
    CustomLog ${APACHE_LOG_DIR}/controllodomini-access.log combined
</VirtualHost>
```

**Abilita sito**:

```bash
sudo a2ensite controllodomini.it.conf
sudo systemctl reload apache2
```

### 5. Configura SSL (Let's Encrypt)

```bash
# Ottieni certificato SSL automaticamente
sudo certbot --apache -d controllodomini.it -d www.controllodomini.it

# Certbot configurerà automaticamente HTTPS e redirect
# Verrà creato un file controllodomini.it-le-ssl.conf
```

**HTTPS Virtual Host (creato da Certbot)**:

```apache
<VirtualHost *:443>
    ServerName controllodomini.it
    ServerAlias www.controllodomini.it

    DocumentRoot /var/www/controllo-domini

    <Directory /var/www/controllo-domini>
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/controllodomini.it/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/controllodomini.it/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    ErrorLog ${APACHE_LOG_DIR}/controllodomini-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/controllodomini-ssl-access.log combined
</VirtualHost>
```

### 6. Configura Auto-Renewal SSL

```bash
# Test auto-renewal
sudo certbot renew --dry-run

# Auto-renewal è configurato automaticamente via cron
# Verifica:
sudo systemctl status certbot.timer
```

### 7. Ottimizzazioni Production

**PHP Configuration** (`/etc/php/8.2/apache2/php.ini`):

```ini
# Performance
max_execution_time = 60
max_input_time = 60
memory_limit = 256M

# Security
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Uploads (se necessario)
upload_max_filesize = 10M
post_max_size = 10M

# Timezone
date.timezone = Europe/Rome
```

**Apache Performance** (`/etc/apache2/apache2.conf`):

```apache
# Timeout
Timeout 60

# KeepAlive
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# MPM Prefork (se usando mod_php)
<IfModule mpm_prefork_module>
    StartServers             5
    MinSpareServers          5
    MaxSpareServers         10
    MaxRequestWorkers      150
    MaxConnectionsPerChild   0
</IfModule>
```

### 8. Configura Monitoring e Logging

```bash
# Crea directory log se non esiste
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php

# Configura log rotation
sudo nano /etc/logrotate.d/controllodomini
```

**Contenuto logrotate**:

```
/var/www/controllo-domini/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/sbin/apachectl graceful > /dev/null 2>&1 || true
    endscript
}
```

## Configurazione Web Server

### Nginx (Alternativa ad Apache)

Se preferisci Nginx:

```bash
# Installa Nginx
sudo apt install nginx php-fpm -y
```

**Configurazione Nginx**:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name controllodomini.it www.controllodomini.it;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name controllodomini.it www.controllodomini.it;

    root /var/www/controllo-domini;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/controllodomini.it/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/controllodomini.it/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Clean URLs (simula mod_rewrite)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~* ^/(config|includes|templates)/.*$ {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }

    access_log /var/log/nginx/controllodomini-access.log;
    error_log /var/log/nginx/controllodomini-error.log;
}
```

**Abilita configurazione**:

```bash
sudo ln -s /etc/nginx/sites-available/controllodomini.it /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Verifica Installazione

### 1. Verifica Requisiti PHP

Crea file `check-requirements.php`:

```php
<?php
echo "<h1>Controllo Domini - System Check</h1>";

// PHP Version
echo "<h2>PHP Version</h2>";
echo "Current: " . phpversion() . "<br>";
echo "Required: 7.4+<br>";
echo (version_compare(phpversion(), '7.4.0', '>=') ? '✅ OK' : '❌ FAIL') . "<br><br>";

// Required Extensions
echo "<h2>Required Extensions</h2>";
$required = ['json', 'curl', 'mbstring', 'openssl'];
foreach ($required as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
}

// Optional Extensions
echo "<h2>Optional Extensions</h2>";
$optional = ['xml', 'dom', 'sockets'];
foreach ($optional as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '✅ Loaded' : '⚠️ Not Loaded (optional)') . "<br>";
}

// File Permissions
echo "<h2>File Permissions</h2>";
$files = [
    'config/config.php' => is_readable('config/config.php'),
    'includes/utilities.php' => is_readable('includes/utilities.php'),
    '.htaccess' => is_readable('.htaccess')
];
foreach ($files as $file => $readable) {
    echo "$file: " . ($readable ? '✅ Readable' : '❌ Not Readable') . "<br>";
}

// DNS Test
echo "<h2>DNS Resolution Test</h2>";
$testDomain = 'google.com';
$records = @dns_get_record($testDomain, DNS_A);
echo ($records ? '✅ DNS queries working' : '❌ DNS queries not working') . "<br>";

// WHOIS Test (socket)
echo "<h2>WHOIS Connection Test</h2>";
$whoisServer = 'whois.verisign-grs.com';
$socket = @fsockopen($whoisServer, 43, $errno, $errstr, 5);
echo ($socket ? '✅ WHOIS socket connection working' : '❌ WHOIS socket connection failed') . "<br>";
if ($socket) fclose($socket);

// cURL Test
echo "<h2>cURL Test</h2>";
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$result = @curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo ($httpCode == 200 ? '✅ cURL working' : '❌ cURL not working') . "<br>";

echo "<h2>Overall Status</h2>";
echo "✅ Installation check complete. Review any ❌ or ⚠️ items above.";
?>
```

Visita: `http://your-domain/check-requirements.php`

### 2. Verifica Funzionamento

Testa funzionalità principali:

```bash
# Test homepage
curl -I http://controllodomini.local/

# Test DNS check
curl -X POST http://controllodomini.local/dns-check.php \
  -d "domain=google.com&analyze=1"

# Test WHOIS
curl -X POST http://controllodomini.local/whois-lookup.php \
  -d "domain=google.com&analyze=1"
```

### 3. Verifica .htaccess

```bash
# Test URL rewriting
curl -I http://controllodomini.local/dns-check
# Dovrebbe rispondere 200, non 404

# Test security headers
curl -I http://controllodomini.local/
# Dovrebbe mostrare X-Content-Type-Options, X-Frame-Options, ecc.
```

## Troubleshooting

### Problema: Pagina bianca

**Causa**: Errori PHP non mostrati

**Soluzione**:
```bash
# Abilita display errors temporaneamente
sudo nano /etc/php/8.2/apache2/php.ini

# Modifica:
display_errors = On
error_reporting = E_ALL

# Riavvia Apache
sudo systemctl restart apache2

# Controlla log
tail -f /var/log/apache2/error.log
```

### Problema: 404 su URL pulite

**Causa**: mod_rewrite non abilitato o .htaccess ignorato

**Soluzione**:
```bash
# Abilita mod_rewrite
sudo a2enmod rewrite

# Verifica AllowOverride in virtual host
sudo nano /etc/apache2/sites-available/controllodomini.conf

# Deve essere:
<Directory /var/www/controllo-domini>
    AllowOverride All
</Directory>

# Riavvia
sudo systemctl restart apache2
```

### Problema: DNS lookup non funziona

**Causa**: Firewall blocca porte 53

**Soluzione**:
```bash
# Verifica connessione DNS
dig @8.8.8.8 google.com

# Se fallisce, controlla firewall
sudo ufw status
sudo ufw allow out 53/udp
sudo ufw allow out 53/tcp
```

### Problema: WHOIS non funziona

**Causa**: Firewall blocca porta 43 o socket non supportati

**Soluzione**:
```bash
# Verifica connessione WHOIS
telnet whois.verisign-grs.com 43

# Se fallisce:
sudo ufw allow out 43/tcp

# Verifica estensione socket PHP
php -m | grep socket
```

### Problema: Permessi negati

**Causa**: File ownership o permessi incorretti

**Soluzione**:
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/controllo-domini

# Fix permessi
find /var/www/controllo-domini -type d -exec chmod 755 {} \;
find /var/www/controllo-domini -type f -exec chmod 644 {} \;

# Verifica Apache user
ps aux | grep apache2
```

### Problema: SSL certificate errors

**Causa**: OpenSSL non configurato correttamente

**Soluzione**:
```bash
# Verifica estensione
php -m | grep openssl

# Test SSL
php -r "var_dump(openssl_get_cert_locations());"

# Se necessario, installa ca-certificates
sudo apt install ca-certificates
sudo update-ca-certificates
```

## Aggiornamento

### Aggiornamento Minor Version

```bash
cd /var/www/controllo-domini

# Backup
sudo cp -r /var/www/controllo-domini /var/www/controllo-domini.backup

# Pull latest
sudo -u www-data git pull origin main

# Clear cache (se implementato)
# rm -rf cache/*

# Riavvia Apache
sudo systemctl restart apache2
```

### Aggiornamento Major Version

```bash
# Backup completo
sudo tar -czf /backup/controllo-domini-$(date +%Y%m%d).tar.gz /var/www/controllo-domini

# Backup database (se applicabile)
# mysqldump -u user -p database > backup.sql

# Pull nuova versione
cd /var/www/controllo-domini
sudo -u www-data git fetch --tags
sudo -u www-data git checkout tags/v5.0

# Leggi CHANGELOG
cat changelog.php

# Verifica breaking changes
# Aggiorna config se necessario

# Test
# Visita sito e testa funzionalità

# Rollback se necessario
sudo -u www-data git checkout tags/v4.0
```

### Rollback

```bash
# Se hai backup
sudo rm -rf /var/www/controllo-domini
sudo cp -r /var/www/controllo-domini.backup /var/www/controllo-domini
sudo systemctl restart apache2

# Oppure via git
cd /var/www/controllo-domini
sudo -u www-data git checkout tags/v4.0
sudo systemctl restart apache2
```

## Installazione Docker (Opzionale)

### Dockerfile

```dockerfile
FROM php:8.2-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libxml2-dev \
    libonig-dev \
    && docker-php-ext-install curl mbstring xml

# Enable Apache modules
RUN a2enmod rewrite headers deflate expires ssl

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    environment:
      - APACHE_RUN_USER=www-data
      - APACHE_RUN_GROUP=www-data
```

### Build e Run

```bash
# Build
docker build -t controllo-domini .

# Run
docker run -d -p 8080:80 --name controllo-domini controllo-domini

# Oppure con docker-compose
docker-compose up -d
```

---

**Ultimo aggiornamento**: Novembre 2025
**Versione guida**: 1.0
