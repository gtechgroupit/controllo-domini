# Guida Deployment - Controllo Domini

## Indice

1. [Panoramica Deployment](#panoramica-deployment)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Deployment Manual](#deployment-manual)
4. [Deployment Automated](#deployment-automated)
5. [Deployment Strategies](#deployment-strategies)
6. [Post-Deployment](#post-deployment)
7. [Rollback Procedures](#rollback-procedures)
8. [Monitoring](#monitoring)
9. [Maintenance](#maintenance)

## Panoramica Deployment

### Ambienti

```
Development → Staging → Production
     ↓            ↓          ↓
  Local PC    Test Server  Live Server
```

### Infrastructure Overview

```
┌─────────────────────────────────────┐
│         INTERNET                     │
└──────────────┬──────────────────────┘
               │
        ┌──────▼──────┐
        │ Cloudflare  │ (Optional)
        │ DDoS, CDN   │
        └──────┬──────┘
               │
        ┌──────▼──────────────┐
        │  Load Balancer      │ (Optional)
        │  NGINX / HAProxy    │
        └──────┬──────────────┘
               │
    ┌──────────┼──────────┐
    │          │          │
┌───▼───┐  ┌──▼───┐  ┌──▼───┐
│ Web   │  │ Web  │  │ Web  │
│ Server│  │Server│  │Server│
│ #1    │  │ #2   │  │ #3   │
└───────┘  └──────┘  └──────┘
```

**Current Setup**: Single Web Server
**Recommended**: Load Balancer + Multi-Server

## Pre-Deployment Checklist

### Code Review

- [ ] **Code quality**: Nessun warning PHP
- [ ] **Coding standards**: PSR-12 compliance
- [ ] **Security**: Input validation, output encoding
- [ ] **Performance**: Nessun bottleneck evidente
- [ ] **Error handling**: Gestione errori appropriata
- [ ] **Logging**: Log configurati correttamente

### Testing

- [ ] **Manual testing**: Tutte le funzionalità testate
- [ ] **Cross-browser**: Chrome, Firefox, Safari, Edge
- [ ] **Mobile responsive**: iOS Safari, Android Chrome
- [ ] **Performance**: PageSpeed score > 80
- [ ] **Security headers**: A grade su securityheaders.com
- [ ] **SSL**: A+ grade su SSL Labs

### Configuration

- [ ] **config.php**: APP_ENV = 'production'
- [ ] **Error logging**: display_errors = Off
- [ ] **Debug mode**: DEBUG_MODE = false
- [ ] **Rate limiting**: Abilitato
- [ ] **Analytics**: GA4 configurato
- [ ] **.env**: Credenziali aggiornate

### Infrastructure

- [ ] **Server**: PHP 8.2+, Apache 2.4+
- [ ] **SSL Certificate**: Valido e rinnovato
- [ ] **Firewall**: Configurato (UFW, fail2ban)
- [ ] **Backup**: Automatico configurato
- [ ] **Monitoring**: Uptime monitoring attivo
- [ ] **DNS**: Record propagati

### Documentation

- [ ] **README.md**: Aggiornato
- [ ] **CHANGELOG.md**: Versione documentata
- [ ] **API docs**: Aggiornati
- [ ] **Documentation**: Completa e accurata

## Deployment Manual

### Step 1: Backup

```bash
# Backup codice
cd /var/www
sudo tar -czf /backup/controllo-domini-$(date +%Y%m%d-%H%M%S).tar.gz controllo-domini/

# Backup configurazione
sudo cp /var/www/controllo-domini/config/config.php /backup/config-$(date +%Y%m%d).php

# Backup database (se applicabile)
# mysqldump -u user -p database > /backup/db-$(date +%Y%m%d).sql
```

### Step 2: Manutenzione Mode (Opzionale)

Crea `maintenance.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance - Controllo Domini</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>🔧 Manutenzione in Corso</h1>
    <p>Torneremo online a breve. Grazie per la pazienza!</p>
</body>
</html>
```

Abilita maintenance mode:

```apache
# .htaccess (aggiungi all'inizio)
RewriteEngine On
RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.0$ # Tuo IP
RewriteCond %{REQUEST_URI} !^/maintenance\.html$
RewriteRule ^(.*)$ /maintenance.html [R=503,L]
```

### Step 3: Pull Latest Code

```bash
cd /var/www/controllo-domini

# Verifica branch
git branch

# Pull latest
sudo -u www-data git fetch origin
sudo -u www-data git checkout main
sudo -u www-data git pull origin main

# O per specifica versione
sudo -u www-data git fetch --tags
sudo -u www-data git checkout tags/v4.1
```

### Step 4: Update Configuration

```bash
# Modifica config per production
sudo nano config/config.php

# Verifica:
# - APP_ENV = 'production'
# - DEBUG_MODE = false
# - display_errors = Off
# - RATE_LIMIT_ENABLED = true
```

### Step 5: Update Dependencies (se applicabile)

```bash
# Se usi Composer (future)
composer install --no-dev --optimize-autoloader

# Se usi npm (future)
npm ci --production
```

### Step 6: Permissions

```bash
# Set permissions
sudo chown -R www-data:www-data /var/www/controllo-domini
sudo find /var/www/controllo-domini -type d -exec chmod 755 {} \;
sudo find /var/www/controllo-domini -type f -exec chmod 644 {} \;

# Restrict sensitive files
sudo chmod 640 /var/www/controllo-domini/config/config.php
sudo chmod 640 /var/www/controllo-domini/.env
```

### Step 7: Clear Cache (se applicabile)

```bash
# Se cache implementata
sudo rm -rf /var/www/controllo-domini/cache/*

# OPcache reset
sudo systemctl restart php8.2-fpm
# o
sudo service apache2 restart
```

### Step 8: Test

```bash
# Test homepage
curl -I https://controllodomini.it/

# Test API
curl https://controllodomini.it/api/health

# Test DNS check
curl -X POST https://controllodomini.it/dns-check.php \
  -d "domain=google.com&analyze=1"
```

### Step 9: Disable Maintenance Mode

```bash
# Rimuovi regole maintenance da .htaccess
sudo nano .htaccess
# (commenta o rimuovi le righe maintenance)

# Riavvia Apache
sudo systemctl restart apache2
```

### Step 10: Monitoring

```bash
# Monitora logs per errori
sudo tail -f /var/log/apache2/controllodomini-error.log

# Monitora accessi
sudo tail -f /var/log/apache2/controllodomini-access.log

# Monitora performance
htop
```

## Deployment Automated

### Deploy Script

Crea `deploy.sh`:

```bash
#!/bin/bash

# ====================================
# Controllo Domini - Deploy Script
# ====================================

set -e  # Exit on error

# Configuration
APP_DIR="/var/www/controllo-domini"
BACKUP_DIR="/backup"
LOG_FILE="/var/log/deploy.log"
MAINTENANCE_FILE="$APP_DIR/maintenance.html"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Step 1: Backup
log "Creating backup..."
BACKUP_FILE="$BACKUP_DIR/controllo-domini-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" -C /var/www controllo-domini/ || error "Backup failed"
log "Backup created: $BACKUP_FILE"

# Step 2: Enable maintenance mode
log "Enabling maintenance mode..."
# (Implementation)

# Step 3: Pull latest code
log "Pulling latest code..."
cd "$APP_DIR"
git fetch origin || error "Git fetch failed"
git checkout main || error "Git checkout failed"
git pull origin main || error "Git pull failed"

# Step 4: Update dependencies (if applicable)
# log "Updating dependencies..."
# composer install --no-dev --optimize-autoloader

# Step 5: Set permissions
log "Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod 640 "$APP_DIR/config/config.php"

# Step 6: Clear cache
log "Clearing cache..."
rm -rf "$APP_DIR/cache/*"

# Step 7: Restart services
log "Restarting Apache..."
systemctl restart apache2 || error "Apache restart failed"

# Step 8: Disable maintenance mode
log "Disabling maintenance mode..."
# (Implementation)

# Step 9: Verify deployment
log "Verifying deployment..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://controllodomini.it)
if [ "$HTTP_CODE" -eq 200 ]; then
    log "Deployment successful! HTTP $HTTP_CODE"
else
    error "Deployment verification failed! HTTP $HTTP_CODE"
fi

log "Deployment completed successfully!"
```

Uso:

```bash
# Make executable
chmod +x deploy.sh

# Run
sudo ./deploy.sh
```

### GitHub Actions (CI/CD)

`.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/controllo-domini
            git pull origin main
            sudo ./deploy.sh
```

### Deployment via Deployer (PHP)

Installa Deployer:

```bash
composer require deployer/deployer --dev
```

`deploy.php`:

```php
<?php
namespace Deployer;

require 'recipe/common.php';

// Config
set('application', 'Controllo Domini');
set('repository', 'git@github.com:gtechgroup/controllo-domini.git');
set('keep_releases', 5);

// Hosts
host('production')
    ->set('hostname', 'controllodomini.it')
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/controllo-domini');

// Tasks
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
```

Deploy:

```bash
vendor/bin/dep deploy production
```

## Deployment Strategies

### 1. Rolling Deployment

Aggiorna server uno alla volta (con load balancer):

```bash
# Server 1
ssh server1.example.com "cd /var/www/controllo-domini && git pull && sudo systemctl restart apache2"

# Wait and verify
sleep 30

# Server 2
ssh server2.example.com "cd /var/www/controllo-domini && git pull && sudo systemctl restart apache2"

# Server 3
ssh server3.example.com "cd /var/www/controllo-domini && git pull && sudo systemctl restart apache2"
```

### 2. Blue-Green Deployment

Due ambienti identici, switch DNS:

```
Blue (v4.0) ← Current production
Green (v4.1) ← New version

Deploy to Green → Test → Switch DNS → Blue becomes standby
```

### 3. Canary Deployment

Gradual rollout a subset di utenti:

```nginx
# Nginx config
upstream backend {
    server backend1.example.com weight=9;  # 90% old version
    server backend2.example.com weight=1;  # 10% new version
}
```

## Post-Deployment

### Verification Checklist

- [ ] **Homepage loads**: https://controllodomini.it
- [ ] **DNS check**: Test domain analysis
- [ ] **WHOIS lookup**: Test WHOIS query
- [ ] **SSL certificate**: Verify HTTPS
- [ ] **Security headers**: Check headers
- [ ] **Performance**: PageSpeed test
- [ ] **Analytics**: GA4 tracking
- [ ] **Logs**: No error logs
- [ ] **Monitoring**: Uptime check active

### Smoke Tests

```bash
#!/bin/bash
# smoke-test.sh

BASE_URL="https://controllodomini.it"

echo "Running smoke tests..."

# Test 1: Homepage
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL")
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ Homepage: OK ($HTTP_CODE)"
else
    echo "❌ Homepage: FAIL ($HTTP_CODE)"
fi

# Test 2: DNS Check
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/dns-check.php" -d "domain=google.com&analyze=1")
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ DNS Check: OK"
else
    echo "❌ DNS Check: FAIL"
fi

# Test 3: WHOIS
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/whois-lookup.php" -d "domain=google.com&analyze=1")
if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ WHOIS Lookup: OK"
else
    echo "❌ WHOIS Lookup: FAIL"
fi

echo "Smoke tests completed"
```

### Announce Deployment

- **Team**: Notifica team via Slack/Email
- **Users**: Annuncio su homepage (se major release)
- **Changelog**: Aggiorna changelog pubblico
- **Social**: Tweet/post nuove feature (opzionale)

## Rollback Procedures

### Quick Rollback (Git)

```bash
# Rollback to previous version
cd /var/www/controllo-domini
sudo -u www-data git log --oneline -10  # View recent commits
sudo -u www-data git checkout <previous-commit-hash>
sudo systemctl restart apache2

# Or rollback to previous tag
sudo -u www-data git checkout tags/v4.0
sudo systemctl restart apache2
```

### Rollback from Backup

```bash
# Stop Apache
sudo systemctl stop apache2

# Remove current version
sudo mv /var/www/controllo-domini /var/www/controllo-domini.failed

# Restore from backup
sudo tar -xzf /backup/controllo-domini-20250101-120000.tar.gz -C /var/www/

# Restore permissions
sudo chown -R www-data:www-data /var/www/controllo-domini

# Start Apache
sudo systemctl start apache2

# Verify
curl -I https://controllodomini.it
```

### Rollback Database (se applicabile)

```bash
# Restore database from backup
mysql -u root -p database_name < /backup/db-20250101.sql
```

## Monitoring

### Uptime Monitoring

**Services**:
- [UptimeRobot](https://uptimerobot.com)
- [Pingdom](https://www.pingdom.com)
- [StatusCake](https://www.statuscake.com)

**Setup**:
```
Monitor URL: https://controllodomini.it
Check interval: 5 minutes
Alert on: Down, Slow (>5s)
Notifications: Email, Slack
```

### Performance Monitoring

**Google Analytics 4**:
- Page load times
- Core Web Vitals
- User engagement
- Traffic sources

**New Relic / DataDog** (optional):
- Application performance
- Error rates
- Transaction times
- Infrastructure metrics

### Log Monitoring

```bash
# Monitor errors in real-time
sudo tail -f /var/log/apache2/controllodomini-error.log

# Count errors per hour
grep "$(date '+%d/%b/%Y:%H')" /var/log/apache2/controllodomini-error.log | wc -l

# Find most common errors
grep "error" /var/log/apache2/controllodomini-error.log | \
  awk '{print $NF}' | sort | uniq -c | sort -rn | head -10
```

### Alert Setup

**Slack Webhook** per alerting:

```bash
# alert.sh
#!/bin/bash

SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

send_alert() {
    MESSAGE=$1
    curl -X POST -H 'Content-type: application/json' \
      --data "{\"text\":\"⚠️ Controllo Domini Alert: $MESSAGE\"}" \
      "$SLACK_WEBHOOK"
}

# Check site is up
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://controllodomini.it)
if [ "$HTTP_CODE" -ne 200 ]; then
    send_alert "Site is down! HTTP $HTTP_CODE"
fi
```

Cron job:

```bash
*/5 * * * * /path/to/alert.sh
```

## Maintenance

### Regular Tasks

**Daily**:
- [ ] Check error logs
- [ ] Monitor uptime
- [ ] Review analytics

**Weekly**:
- [ ] Review security logs
- [ ] Check SSL certificate expiry
- [ ] Update dependencies
- [ ] Backup verification

**Monthly**:
- [ ] Security audit
- [ ] Performance review
- [ ] Update system packages
- [ ] Review and clean old backups

### Update Schedule

```bash
# System updates (weekly)
sudo apt update && sudo apt upgrade -y

# PHP updates (as needed)
sudo apt install php8.3  # When new version available

# Apache updates (as needed)
sudo apt install apache2

# SSL certificate renewal (automatic via certbot)
# Verify:
sudo certbot renew --dry-run
```

### Backup Schedule

```bash
# Daily backup script
#!/bin/bash
# /etc/cron.daily/backup-controllo-domini

BACKUP_DIR="/backup/daily"
APP_DIR="/var/www/controllo-domini"

# Create backup
tar -czf "$BACKUP_DIR/backup-$(date +%Y%m%d).tar.gz" "$APP_DIR"

# Keep only last 7 days
find "$BACKUP_DIR" -name "backup-*.tar.gz" -mtime +7 -delete

# Weekly full backup to S3 (optional)
# aws s3 cp "$BACKUP_DIR/backup-$(date +%Y%m%d).tar.gz" s3://my-bucket/backups/
```

### Health Check Endpoint

Crea `health.php`:

```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'version' => '4.1',
    'checks' => []
];

// Check PHP version
$health['checks']['php'] = [
    'version' => phpversion(),
    'status' => version_compare(phpversion(), '7.4.0', '>=') ? 'ok' : 'error'
];

// Check required extensions
$required = ['json', 'curl', 'mbstring', 'openssl'];
foreach ($required as $ext) {
    $health['checks']['extension_' . $ext] = [
        'status' => extension_loaded($ext) ? 'ok' : 'error'
    ];
}

// Check DNS resolution
$dns = @dns_get_record('google.com', DNS_A);
$health['checks']['dns'] = [
    'status' => $dns ? 'ok' : 'error'
];

// Overall status
$allOk = true;
foreach ($health['checks'] as $check) {
    if ($check['status'] !== 'ok') {
        $allOk = false;
        break;
    }
}
$health['status'] = $allOk ? 'ok' : 'degraded';

http_response_code($allOk ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
```

Monitor:

```bash
curl https://controllodomini.it/health.php
```

---

**Ultimo aggiornamento**: Novembre 2025
**Versione guida**: 1.0
