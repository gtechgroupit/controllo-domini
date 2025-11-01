# Roadmap - Controllo Domini

Questa roadmap definisce la visione e la pianificazione dello sviluppo di Controllo Domini per i prossimi 12-24 mesi.

## Indice

1. [Visione](#visione)
2. [Roadmap Overview](#roadmap-overview)
3. [Q1 2025 - v4.2](#q1-2025---v42)
4. [Q2 2025 - v4.3](#q2-2025---v43)
5. [Q3 2025 - v5.0](#q3-2025---v50)
6. [Q4 2025 - v5.1](#q4-2025---v51)
7. [2026 e Oltre](#2026-e-oltre)
8. [Feature Backlog](#feature-backlog)
9. [Technical Debt](#technical-debt)
10. [Community Requests](#community-requests)

---

## Visione

**Mission**: Diventare la piattaforma leader per l'analisi completa di domini web in Italia ed Europa, fornendo strumenti professionali accessibili a tutti.

**Obiettivi 2025**:
- 🎯 **100.000+ analisi mensili** (da ~10.000 attuali)
- 🌍 **Espansione internazionale** con supporto multi-lingua
- 🔐 **Certificazione SOC 2** per enterprise customers
- 💰 **Modello freemium** con piani premium
- 🤖 **AI-powered insights** e raccomandazioni intelligenti

---

## Roadmap Overview

```
2025 Q1          Q2           Q3           Q4          2026+
  │             │            │            │             │
  v4.2          v4.3         v5.0         v5.1         v6.0
  │             │            │            │             │
  │             │            │            │             │
Database    Enterprise   Public API   Monitoring   AI/ML
& Auth      Features     v3.0         Dashboard    Features
  │             │            │            │             │
  ├─Users      ├─Teams     ├─GraphQL    ├─Alerts     ├─Predictions
  ├─API        ├─SSO       ├─Webhooks   ├─Reports    ├─Insights
  ├─Cache      ├─Audit     ├─Rate Lmt   ├─Metrics    ├─AutoFix
  └─Export     └─RBAC      └─Docs       └─SLA        └─ML Models
```

---

## Q1 2025 - v4.2

**Tema**: Infrastructure & Foundation
**Target Release**: Marzo 2025
**Focus**: Database integration, autenticazione, caching, API migliorata

### 🎯 Obiettivi Principali

#### 1. Database Integration

**Priority**: 🔴 Critical
**Effort**: 3-4 settimane
**Status**: Not Started

**Features**:
- Implementazione PostgreSQL come database principale
- Schema design per:
  - Users e authentication
  - Analysis history e results caching
  - API keys e rate limiting
  - Audit logs
  - Scheduled tasks
- Migration tools da sistema stateless
- Backup e recovery automatico

**Technical Tasks**:
```sql
-- Schema principale
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    plan VARCHAR(50) DEFAULT 'free',
    api_key VARCHAR(64) UNIQUE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE analysis_history (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    domain VARCHAR(253) NOT NULL,
    analysis_type VARCHAR(50) NOT NULL,
    results JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE rate_limits (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    ip_address INET,
    request_count INTEGER DEFAULT 0,
    window_start TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_analysis_user ON analysis_history(user_id);
CREATE INDEX idx_analysis_domain ON analysis_history(domain);
CREATE INDEX idx_analysis_created ON analysis_history(created_at);
CREATE INDEX idx_rate_limits_user ON rate_limits(user_id);
CREATE INDEX idx_rate_limits_ip ON rate_limits(ip_address);
```

#### 2. User Authentication System

**Priority**: 🔴 Critical
**Effort**: 2-3 settimane
**Status**: Not Started

**Features**:
- Registrazione utenti con email verification
- Login/Logout con session management
- Password reset via email
- OAuth integration (Google, GitHub)
- Two-Factor Authentication (2FA)
- Remember me functionality
- User profile management

**Technical Stack**:
- PHP Sessions per authentication
- bcrypt per password hashing
- JWT per API authentication
- PHPMailer per email
- Google OAuth 2.0

**Pages da Creare**:
- `/register` - Registrazione
- `/login` - Login
- `/logout` - Logout
- `/profile` - Profilo utente
- `/forgot-password` - Reset password
- `/dashboard` - Dashboard utente personale

#### 3. Caching System

**Priority**: 🟡 High
**Effort**: 1-2 settimane
**Status**: Not Started

**Features**:
- Redis per caching applicativo
- Cache WHOIS results (TTL: 24 ore)
- Cache DNS results (TTL: 1 ora)
- Cache Blacklist results (TTL: 2 ore)
- Cache SSL certificates (TTL: 24 ore)
- Cache invalidation API
- Cache statistics e monitoring

**Implementation**:
```php
// includes/cache.php
class CacheManager {
    private $redis;

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect(REDIS_HOST, REDIS_PORT);
    }

    public function get($key, $callback, $ttl = 3600) {
        $cached = $this->redis->get($key);
        if ($cached !== false) {
            return json_decode($cached, true);
        }

        $data = $callback();
        $this->redis->setex($key, $ttl, json_encode($data));
        return $data;
    }

    public function invalidate($pattern) {
        $keys = $this->redis->keys($pattern);
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
    }
}

// Usage
$cache = new CacheManager();
$whois = $cache->get(
    "whois:$domain",
    fn() => getWhoisInfo($domain),
    86400  // 24 hours
);
```

#### 4. API v2.1 Enhancement

**Priority**: 🟡 High
**Effort**: 2 settimane
**Status**: Not Started

**Features**:
- API Key authentication
- Rate limiting per API key (100/hour free, 1000/hour pro)
- Pagination per risultati
- Filtering e sorting
- Bulk operations (multiple domains)
- Webhooks per long-running tasks
- API usage analytics
- OpenAPI 3.0 specification

**New Endpoints**:
```
POST   /api/v2/bulk/dns              Bulk DNS analysis
POST   /api/v2/bulk/whois            Bulk WHOIS lookup
GET    /api/v2/history               User analysis history
GET    /api/v2/usage                 API usage stats
POST   /api/v2/webhooks              Register webhook
DELETE /api/v2/webhooks/:id          Delete webhook
```

#### 5. Export & Reporting

**Priority**: 🟢 Medium
**Effort**: 1-2 settimane
**Status**: Not Started

**Features**:
- Export risultati in PDF
- Export risultati in CSV
- Export risultati in JSON
- Scheduled reports via email
- Report templates personalizzabili
- Branding personalizzato per report (premium)

**Libraries**:
- TCPDF per PDF generation
- PhpSpreadsheet per CSV/Excel
- Template engine per report customization

### 📊 Success Metrics

- ✅ 95% uptime con database
- ✅ < 100ms overhead per caching layer
- ✅ 1000+ utenti registrati nel primo mese
- ✅ 80% riduzione query esterne grazie a caching
- ✅ 50+ API integrations attive

---

## Q2 2025 - v4.3

**Tema**: Enterprise Features
**Target Release**: Giugno 2025
**Focus**: Team collaboration, SSO, audit logging, RBAC

### 🎯 Obiettivi Principali

#### 1. Team & Organization Management

**Priority**: 🟡 High
**Effort**: 3-4 settimane

**Features**:
- Creazione organizzazioni
- Gestione team membri
- Inviti team via email
- Role-Based Access Control (RBAC)
  - Admin: full access
  - Member: limited access
  - Viewer: read-only
- Shared analysis history
- Team billing e subscription
- Usage quota per team

**Schema**:
```sql
CREATE TABLE organizations (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    plan VARCHAR(50) DEFAULT 'team',
    billing_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE team_members (
    id UUID PRIMARY KEY,
    organization_id UUID REFERENCES organizations(id),
    user_id UUID REFERENCES users(id),
    role VARCHAR(50) NOT NULL,
    joined_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(organization_id, user_id)
);

CREATE TABLE permissions (
    id UUID PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    UNIQUE(role, resource, action)
);
```

#### 2. Single Sign-On (SSO)

**Priority**: 🟡 High
**Effort**: 2-3 settimane

**Features**:
- SAML 2.0 support
- OpenID Connect support
- Azure AD integration
- Google Workspace integration
- Okta integration
- Auto-provisioning utenti
- Just-In-Time (JIT) provisioning

#### 3. Audit Logging

**Priority**: 🟡 High
**Effort**: 1-2 settimane

**Features**:
- Log tutte le azioni utente
- Log accessi API
- Log modifiche configurazione
- Log export eventi
- Retention policy configurabile
- Search e filtering avanzato
- Export audit logs (CSV, JSON)

**Eventi Logged**:
- User login/logout
- API key creation/deletion
- Analysis execution
- Team member add/remove
- Permission changes
- Configuration changes
- Export operations

#### 4. White-Label Solution

**Priority**: 🟢 Medium
**Effort**: 2-3 settimane

**Features**:
- Custom branding (logo, colori)
- Custom domain (your-domain.com)
- Custom email templates
- Remove "Powered by Controllo Domini"
- Custom CSS injection
- Custom terms of service
- Custom privacy policy

#### 5. Advanced Analytics

**Priority**: 🟢 Medium
**Effort**: 2 settimane

**Features**:
- Usage analytics dashboard
- Domain analysis trends
- Most analyzed domains
- Performance metrics
- Cost analysis (API usage)
- Team analytics
- Custom reports builder

### 📊 Success Metrics

- ✅ 50+ enterprise customers
- ✅ 20+ SSO integrations attive
- ✅ 100% audit coverage per azioni critiche
- ✅ 10+ white-label deployments

---

## Q3 2025 - v5.0

**Tema**: Public API v3.0 & Developer Experience
**Target Release**: Settembre 2025
**Focus**: GraphQL API, webhooks, SDKs, developer tools

### 🎯 Obiettivi Principali

#### 1. GraphQL API

**Priority**: 🟡 High
**Effort**: 4-5 settimane

**Features**:
- GraphQL endpoint: `/graphql`
- Schema completo per tutte le entità
- Subscriptions per real-time updates
- DataLoader per batching e caching
- Relay-compliant pagination
- GraphQL Playground integrato
- Schema introspection

**Example Query**:
```graphql
query AnalyzeDomain($domain: String!) {
  domain(name: $domain) {
    name
    dns {
      a { ip ttl }
      mx { priority target }
      txt { value }
    }
    whois {
      registrar { name }
      dates { expires }
    }
    ssl {
      valid
      grade
      expiresAt
    }
  }
}
```

#### 2. Webhooks System

**Priority**: 🟡 High
**Effort**: 2-3 settimane

**Features**:
- Webhook registration API
- Event types:
  - `analysis.completed`
  - `domain.expiring`
  - `ssl.expiring`
  - `blacklist.listed`
  - `security.issue_detected`
- Retry mechanism con exponential backoff
- Webhook signature verification (HMAC)
- Webhook logs e debugging
- Test webhook tool

#### 3. Official SDKs

**Priority**: 🟡 High
**Effort**: 3-4 settimane

**Languages**:
- **PHP SDK**
  ```php
  $client = new ControlloDomin\Client($apiKey);
  $dns = $client->dns()->analyze('example.com');
  ```
- **JavaScript/TypeScript SDK**
  ```javascript
  const client = new ControlloDomin({ apiKey });
  const dns = await client.dns.analyze('example.com');
  ```
- **Python SDK**
  ```python
  client = ControlloDomin(api_key=key)
  dns = client.dns.analyze('example.com')
  ```
- **Go SDK**
  ```go
  client := controllodomini.NewClient(apiKey)
  dns, err := client.DNS.Analyze("example.com")
  ```

**Features per SDK**:
- Type-safe interfaces
- Auto-retry con backoff
- Rate limit handling
- Response caching
- Error handling
- Full documentation
- Unit tests (>90% coverage)

#### 4. Developer Portal

**Priority**: 🟡 High
**Effort**: 3 settimane

**Features**:
- Interactive API documentation
- API key management
- Usage dashboard
- Billing management
- Webhook management
- Code examples in 5+ languages
- Postman collection download
- OpenAPI/Swagger spec download
- GraphQL playground
- API status page

**URL**: `https://developers.controllodomini.it`

#### 5. CLI Tool

**Priority**: 🟢 Medium
**Effort**: 2 settimane

**Features**:
- Command-line tool per analisi domini
- Supporto tutte le analisi
- Output in JSON, table, CSV
- Batch processing da file
- Watch mode per monitoring
- Configuration file support

**Installation**:
```bash
npm install -g @controllodomini/cli
# or
brew install controllodomini
```

**Usage**:
```bash
# Analyze domain
cdomini dns example.com

# Multiple analysis
cdomini analyze example.com --all

# Batch from file
cdomini batch domains.txt --output results.json

# Watch mode
cdomini watch example.com --alert-on ssl_expiring
```

### 📊 Success Metrics

- ✅ 1000+ developer registrations
- ✅ 500+ API integrations attive
- ✅ 10,000+ SDK downloads
- ✅ 100+ webhook consumers
- ✅ 95% API uptime SLA

---

## Q4 2025 - v5.1

**Tema**: Monitoring & Alerting
**Target Release**: Dicembre 2025
**Focus**: Continuous monitoring, alerting, SLA tracking

### 🎯 Obiettivi Principali

#### 1. Continuous Monitoring

**Priority**: 🔴 Critical
**Effort**: 4-5 settimane

**Features**:
- Scheduled domain monitoring
  - Frequency: ogni ora, giorno, settimana, mese
- Monitor types:
  - DNS changes monitoring
  - WHOIS expiry monitoring
  - SSL certificate expiry monitoring
  - Blacklist monitoring
  - Security headers monitoring
  - Performance monitoring
- Alert rules configuration
- Multi-channel notifications
- Monitoring dashboard
- Historical trends

**Example Configuration**:
```json
{
  "domain": "example.com",
  "monitors": [
    {
      "type": "ssl_expiry",
      "frequency": "daily",
      "alert_threshold": "30_days",
      "channels": ["email", "slack"]
    },
    {
      "type": "dns_changes",
      "frequency": "hourly",
      "records": ["A", "MX"],
      "channels": ["webhook"]
    }
  ]
}
```

#### 2. Alert System

**Priority**: 🔴 Critical
**Effort**: 2-3 settimane

**Features**:
- Alert rules builder
- Severity levels: info, warning, critical
- Alert channels:
  - Email
  - SMS (Twilio)
  - Slack
  - Discord
  - Microsoft Teams
  - Webhook
  - PagerDuty
- Alert aggregation (prevent spam)
- Escalation policies
- On-call scheduling
- Alert acknowledgment
- Alert resolution tracking

#### 3. Status Pages

**Priority**: 🟡 High
**Effort**: 2 settimane

**Features**:
- Public status page per domain
- Custom subdomain: `status.your-domain.com`
- Real-time status indicators
- Incident history
- Planned maintenance
- Subscribe to updates (email, RSS, webhook)
- Custom branding
- Embed widget per website

**Example**: `https://status.controllodomini.it`

#### 4. Incident Management

**Priority**: 🟡 High
**Effort**: 2-3 settimane

**Features**:
- Incident creation automatica da alerts
- Incident timeline
- Root cause analysis
- Post-mortem reports
- Incident communication templates
- Incident metrics (MTTR, MTTD, MTTF)
- Integration con ticketing systems (Jira, Linear)

#### 5. SLA Tracking & Reporting

**Priority**: 🟢 Medium
**Effort**: 1-2 settimane

**Features**:
- SLA definition per servizio
- Uptime tracking (99.9%, 99.99%)
- Performance SLA (response time)
- Availability SLA
- SLA breach notifications
- SLA reports (monthly)
- SLA credit calculation

### 📊 Success Metrics

- ✅ 5,000+ domains monitored
- ✅ 99.9% uptime per monitoring system
- ✅ < 1 min alert delivery time
- ✅ 10,000+ alerts gestiti/mese
- ✅ 95% customer satisfaction su monitoring

---

## 2026 e Oltre

### Q1 2026 - v6.0: AI & Machine Learning

**Features Pianificate**:
- **AI-Powered Insights**
  - Analisi predittiva per scadenze
  - Raccomandazioni ottimizzazione automatiche
  - Anomaly detection intelligente
  - Smart alerts con ML
- **Natural Language Queries**
  - "Mostrami tutti i domini che scadono nel prossimo mese"
  - "Quali domini hanno problemi SSL?"
- **Auto-Remediation**
  - Suggerimenti fix automatici
  - One-click fix per problemi comuni
- **Competitive Analysis**
  - Confronto con competitor
  - Benchmark industry
- **ML Models**
  - Predizione downtime
  - Security threat detection
  - Performance optimization suggestions

### Q2 2026 - v6.1: Mobile Apps

**Features Pianificate**:
- **iOS App Native**
  - SwiftUI interface
  - Push notifications
  - Widget per dashboard
- **Android App Native**
  - Kotlin/Compose
  - Material Design 3
  - Widget home screen
- **Features Mobile**
  - Scan QR code per analisi
  - Offline mode
  - Camera scan URL
  - Voice commands
  - Dark mode

### Q3 2026 - v6.2: Marketplace & Integrations

**Features Pianificate**:
- **Plugin Marketplace**
  - Community plugins
  - Custom analyzers
  - Third-party integrations
- **Integration Store**
  - 100+ pre-built integrations
  - Zapier integration
  - IFTTT integration
  - Make.com integration
- **Workflow Builder**
  - Visual workflow editor
  - Trigger → Action → Condition
  - Multi-step workflows

### Q4 2026 - v7.0: Enterprise Platform

**Features Pianificate**:
- **On-Premise Deployment**
  - Docker compose
  - Kubernetes helm charts
  - Air-gapped installation
- **Multi-Region Support**
  - EU region
  - US region
  - Asia region
  - Data residency compliance
- **Advanced Security**
  - SOC 2 Type II certified
  - ISO 27001 certified
  - GDPR compliant
  - HIPAA compliant (optional)
- **High Availability**
  - 99.99% uptime SLA
  - Active-active replication
  - Auto-failover
  - Disaster recovery

---

## Feature Backlog

### High Priority (Next 6 Months)

1. **Bulk Import/Export** (v4.2)
   - Import domini da CSV
   - Export risultati batch
   - Schedule import ricorrenti

2. **API Rate Limit Increase** (v4.2)
   - Free: 100/hour → 200/hour
   - Pro: 1000/hour → 5000/hour
   - Enterprise: unlimited

3. **Custom Notifications** (v4.3)
   - Email templates personalizzabili
   - Multi-language support
   - Rich HTML emails

4. **Domain Portfolio Management** (v4.3)
   - Raggruppa domini per progetto
   - Tags e categorizzazione
   - Bulk operations

5. **Historical Data Comparison** (v5.0)
   - Confronta risultati nel tempo
   - Change detection
   - Trend analysis

### Medium Priority (6-12 Months)

6. **Browser Extensions** (v5.1)
   - Chrome extension
   - Firefox extension
   - Quick analysis da toolbar

7. **Scheduled Reports** (v5.1)
   - Weekly/monthly reports
   - Custom report builder
   - Auto-delivery via email

8. **Cost Tracking** (v5.1)
   - Track API usage costs
   - Budget alerts
   - Cost optimization suggestions

9. **Multi-Language Support** (v5.1)
   - Italiano (completo)
   - English
   - Español
   - Deutsch
   - Français

10. **Competitor Monitoring** (v6.0)
    - Track competitor domains
    - Technology changes
    - Performance comparison

### Low Priority (12+ Months)

11. **WordPress Plugin** (v6.1)
    - Analisi domini da WP admin
    - Auto-monitoring sito WP
    - One-click fixes

12. **Browser Testing** (v6.2)
    - Screenshot capture
    - Browser compatibility
    - Visual regression testing

13. **Performance Budget** (v6.2)
    - Define performance targets
    - Alert on budget exceed
    - CI/CD integration

14. **Accessibility Audit** (v6.2)
    - WCAG compliance check
    - Screen reader testing
    - Color contrast analysis

15. **Security Scanning** (v7.0)
    - Vulnerability scanning
    - Malware detection
    - XSS/SQL injection testing

---

## Technical Debt

### Infrastructure

1. **Migration PHP 8.3** (Q1 2025)
   - Aggiorna sintassi PHP 8.3
   - Sfrutta nuove features
   - Performance improvements

2. **Refactoring a OOP** (Q2 2025)
   - Migrazione da procedural a OOP
   - PSR-4 autoloading
   - Dependency Injection

3. **Test Coverage** (Q2 2025)
   - Unit tests (target: 80%)
   - Integration tests
   - E2E tests con Playwright

4. **CI/CD Pipeline** (Q1 2025)
   - GitHub Actions setup
   - Automated testing
   - Automated deployment
   - Docker builds

### Code Quality

5. **Static Analysis** (Q1 2025)
   - PHPStan level 8
   - Psalm
   - PHP CodeSniffer

6. **Security Scanning** (Ongoing)
   - SAST con SonarQube
   - Dependency scanning
   - Secret scanning

7. **Performance Optimization** (Q2 2025)
   - Query optimization
   - N+1 query elimination
   - Lazy loading
   - Async processing

### Documentation

8. **API Documentation** (Q1 2025)
   - OpenAPI 3.1 spec completa
   - Interactive examples
   - Versioning

9. **Architecture Diagrams** (Q1 2025)
   - C4 model diagrams
   - Sequence diagrams
   - Data flow diagrams

---

## Community Requests

### Top Voted Features

1. **Dark Mode** (1,234 votes)
   - Status: ✅ Planned for v4.2
   - ETA: Q1 2025

2. **Mobile App** (987 votes)
   - Status: 📋 Planned for v6.1
   - ETA: Q2 2026

3. **WordPress Plugin** (756 votes)
   - Status: 📋 Planned for v6.1
   - ETA: Q2 2026

4. **Multi-Language** (654 votes)
   - Status: ✅ Planned for v5.1
   - ETA: Q4 2025

5. **Bulk Analysis** (543 votes)
   - Status: ✅ In Development for v4.2
   - ETA: Q1 2025

### How to Request Features

1. **GitHub Discussions**: [github.com/gtechgroup/controllo-domini/discussions](https://github.com/gtechgroup/controllo-domini/discussions)
2. **Email**: features@controllodomini.it
3. **Voting**: Upvote existing requests

---

## Contributing to Roadmap

Vogliamo sentire la tua voce! Ecco come puoi influenzare la roadmap:

1. **Submit Feature Requests**: Apri una discussion su GitHub
2. **Vote on Features**: Upvota le feature che ti interessano
3. **Contribute Code**: Submit PR per feature della roadmap
4. **Sponsor Development**: Sponsorizza feature specifiche

---

## Roadmap Process

### Come Pianifichiamo

1. **Quarterly Planning**: Review roadmap ogni trimestre
2. **Community Input**: Consideriamo feedback community
3. **Business Needs**: Bilanciamo con esigenze business
4. **Technical Feasibility**: Valutiamo fattibilità tecnica
5. **Resource Allocation**: Assegniamo risorse disponibili

### Release Cycle

```
Development → Testing → Staging → Production
    2-3w        1w        1w         Deploy
```

### Flexibility

⚠️ **Nota**: Questa roadmap è living document e può cambiare basato su:
- Feedback community
- Priorità business
- Risorse disponibili
- Nuove opportunità tecnologiche
- Market conditions

---

## Success Metrics 2025

### User Metrics
- 📈 **100,000+** analisi mensili
- 👥 **10,000+** utenti registrati
- 🏢 **500+** enterprise customers
- 🌍 **50+** paesi raggiunti

### Technical Metrics
- ⚡ **< 100ms** median API response time
- 🎯 **99.9%** uptime SLA
- 🔐 **Zero** security breaches
- 📊 **80%+** test coverage

### Business Metrics
- 💰 **€1M+** ARR (Annual Recurring Revenue)
- 📈 **50%** YoY growth
- 😊 **90%+** customer satisfaction
- 🔄 **< 5%** churn rate

---

## Get Involved

- 💬 **Discuss**: [GitHub Discussions](https://github.com/gtechgroup/controllo-domini/discussions)
- 🐛 **Report Bugs**: [GitHub Issues](https://github.com/gtechgroup/controllo-domini/issues)
- 💡 **Request Features**: [Feature Requests](https://github.com/gtechgroup/controllo-domini/discussions/categories/feature-requests)
- 📧 **Contact**: roadmap@controllodomini.it

---

**Ultimo aggiornamento**: Novembre 2025
**Prossimo review**: Gennaio 2025
**Version**: 1.0
