# Guida Contribuzione - Controllo Domini

Grazie per il tuo interesse nel contribuire a Controllo Domini! 🎉

Questo documento fornisce linee guida per contribuire al progetto. Seguendo queste linee guida, aiuti i maintainer e la community a gestire e rispondere più efficacemente alle tue issue, valutare le tue modifiche, e aiutarti a finalizzare le tue pull request.

## Indice

1. [Codice di Condotta](#codice-di-condotta)
2. [Come Contribuire](#come-contribuire)
3. [Segnalare Bug](#segnalare-bug)
4. [Proporre Funzionalità](#proporre-funzionalità)
5. [Setup Ambiente Development](#setup-ambiente-development)
6. [Processo Pull Request](#processo-pull-request)
7. [Coding Standards](#coding-standards)
8. [Commit Guidelines](#commit-guidelines)
9. [Testing](#testing)
10. [Documentazione](#documentazione)

---

## Codice di Condotta

### Il Nostro Impegno

Noi, come membri, contributori e leader, ci impegniamo a rendere la partecipazione al nostro progetto e alla nostra community un'esperienza libera da molestie per tutti, indipendentemente da età, dimensioni corporee, disabilità visibili o invisibili, etnia, caratteristiche sessuali, identità ed espressione di genere, livello di esperienza, educazione, stato socio-economico, nazionalità, aspetto personale, razza, religione, o identità e orientamento sessuale.

### Standard

Esempi di comportamento che contribuisce a creare un ambiente positivo:

✅ Usare un linguaggio accogliente e inclusivo
✅ Rispettare punti di vista ed esperienze diverse
✅ Accettare con grazia critiche costruttive
✅ Focalizzarsi su ciò che è meglio per la community
✅ Mostrare empatia verso altri membri della community

Esempi di comportamento inaccettabile:

❌ L'uso di linguaggio o immagini sessualizzate
❌ Trolling, commenti insultanti/dispregiativi, e attacchi personali o politici
❌ Molestie pubbliche o private
❌ Pubblicare informazioni private altrui senza permesso esplicito
❌ Altra condotta che potrebbe essere considerata inappropriata in un contesto professionale

### Enforcement

I maintainer del progetto sono responsabili del chiarimento degli standard di comportamento accettabile e prenderanno azioni correttive appropriate e giuste in risposta a qualsiasi istanza di comportamento inaccettabile.

**Contact**: conduct@controllodomini.it

---

## Come Contribuire

Ci sono molti modi per contribuire a Controllo Domini:

### 🐛 Segnalare Bug
Aiutaci a migliorare segnalando bug che trovi.

### 💡 Proporre Funzionalità
Suggerisci nuove funzionalità o miglioramenti.

### 📝 Migliorare Documentazione
La documentazione può sempre essere migliorata.

### 💻 Contribuire Codice
Implementa nuove feature o risolvi bug esistenti.

### 🎨 Design e UI/UX
Migliora l'interfaccia utente e l'esperienza.

### 🌍 Traduzioni
Aiuta a tradurre l'app in altre lingue.

### ✅ Testing
Testa nuove feature e fornisci feedback.

### 💬 Community Support
Aiuta altri utenti rispondendo a domande.

---

## Segnalare Bug

Un buon bug report è fondamentale per risolvere problemi velocemente.

### Prima di Segnalare

1. ✅ **Verifica la documentazione**: Il problema potrebbe essere già documentato
2. ✅ **Cerca issue esistenti**: Qualcun altro potrebbe aver già segnalato lo stesso bug
3. ✅ **Prova l'ultima versione**: Il bug potrebbe essere già stato fixato

### Come Segnalare un Bug

**Usa il template**: [Bug Report Template](.github/ISSUE_TEMPLATE/bug_report.md)

**Includi**:

1. **Titolo chiaro e descrittivo**
   ```
   ❌ BAD: "DNS non funziona"
   ✅ GOOD: "DNS lookup fallisce per domini .it con timeout dopo 5 secondi"
   ```

2. **Passi per riprodurre**
   ```markdown
   1. Vai su /dns-check
   2. Inserisci dominio 'example.it'
   3. Clicca 'Analizza'
   4. Osserva timeout dopo 5 secondi
   ```

3. **Comportamento atteso**
   ```
   DNS lookup dovrebbe completarsi entro 2 secondi e mostrare record.
   ```

4. **Comportamento attuale**
   ```
   Timeout dopo 5 secondi con errore "DNS query failed".
   ```

5. **Screenshots** (se applicabile)
   Allega screenshot che mostrano il problema.

6. **Ambiente**
   ```markdown
   - OS: Ubuntu 22.04
   - Browser: Chrome 120.0
   - PHP Version: 8.2.0
   - App Version: 4.1.0
   ```

7. **Log e Error Messages**
   ```
   [2025-01-15 10:30:45] ERROR: DNS query timeout for example.it
   ```

8. **Informazioni Aggiuntive**
   Qualsiasi altro contesto utile.

### Esempio Bug Report Completo

```markdown
## Bug Description
DNS lookup fallisce per domini .it con timeout dopo 5 secondi

## Steps to Reproduce
1. Navigate to https://controllodomini.it/dns-check
2. Enter domain name: `esempio.it`
3. Click "Analizza" button
4. Wait 5 seconds
5. Observe timeout error

## Expected Behavior
- DNS lookup should complete within 2 seconds
- Should display A, AAAA, MX, TXT, NS, SOA records
- Should show response time metric

## Actual Behavior
- Query times out after exactly 5 seconds
- Error message: "DNS query failed: timeout"
- No records displayed
- No response time shown

## Screenshots
![Timeout Error](https://example.com/screenshot.png)

## Environment
- **OS**: Ubuntu 22.04 LTS
- **Browser**: Chrome 120.0.6099.129
- **PHP Version**: 8.2.0
- **Apache Version**: 2.4.52
- **App Version**: 4.1.0

## Error Logs
```
[2025-01-15 10:30:45] PHP Warning: dns_get_record(): A temporary server error occurred.
[2025-01-15 10:30:50] ERROR: DNS query timeout for esempio.it after 5000ms
```

## Additional Context
- Works fine for .com domains
- Only affects .it TLD
- Started happening after updating to v4.1.0
- Tested on multiple .it domains, all fail
```

---

## Proporre Funzionalità

### Prima di Proporre

1. ✅ **Controlla la roadmap**: [ROADMAP.md](ROADMAP.md) - Potrebbe essere già pianificata
2. ✅ **Cerca feature request esistenti**: Potrebbe essere già stata proposta
3. ✅ **Discuti su GitHub Discussions**: Ottieni feedback dalla community prima

### Come Proporre una Feature

**Usa il template**: [Feature Request Template](.github/ISSUE_TEMPLATE/feature_request.md)

**Includi**:

1. **Problema da Risolvere**
   ```markdown
   ## Problem Statement
   Attualmente non è possibile esportare risultati analisi in formato PDF.
   Questo rende difficile condividere report con clienti che preferiscono
   documenti formattati invece di JSON/CSV.
   ```

2. **Soluzione Proposta**
   ```markdown
   ## Proposed Solution
   Aggiungere bottone "Export PDF" nella pagina risultati che genera un
   PDF professionale con:
   - Logo e branding
   - Summary esecutivo
   - Risultati dettagliati per ogni analisi
   - Grafici e visualizzazioni
   - Timestamp e metadata
   ```

3. **Alternative Considerate**
   ```markdown
   ## Alternatives Considered
   1. **Export HTML + Print to PDF**: Troppo manuale
   2. **Third-party PDF service**: Privacy concerns
   3. **Email PDF on request**: Richiede setup SMTP
   ```

4. **Benefici**
   ```markdown
   ## Benefits
   - Professionali report condivisibili
   - Migliore customer experience
   - Competitive advantage
   - Potenziale premium feature
   ```

5. **Mockup/Design** (se applicabile)
   Allega screenshot, wireframe, o prototipi.

---

## Setup Ambiente Development

### Prerequisiti

```bash
- PHP 8.2+
- Apache 2.4+ / Nginx
- Git
- Composer (future)
- Node.js 18+ (future)
```

### Clone Repository

```bash
git clone https://github.com/gtechgroup/controllo-domini.git
cd controllo-domini
```

### Configurazione Locale

```bash
# Copia config
cp config/config.php config/config.local.php

# Modifica per development
nano config/config.local.php
```

**Impostazioni development**:
```php
define('APP_ENV', 'development');
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Apache Virtual Host

Vedi [INSTALLATION.md](INSTALLATION.md) per setup dettagliato.

### Verifica Setup

```bash
# Test homepage
curl http://controllodomini.local/

# Test DNS check
curl -X POST http://controllodomini.local/dns-check.php \
  -d "domain=google.com&analyze=1"
```

---

## Processo Pull Request

### 1. Fork & Clone

```bash
# Fork su GitHub, poi:
git clone https://github.com/YOUR_USERNAME/controllo-domini.git
cd controllo-domini
git remote add upstream https://github.com/gtechgroup/controllo-domini.git
```

### 2. Crea Branch

```bash
git checkout develop
git pull upstream develop
git checkout -b feature/your-feature-name
```

**Branch naming**:
- `feature/feature-name` - Nuove funzionalità
- `fix/bug-description` - Bug fix
- `docs/what-changed` - Documentazione
- `refactor/what-refactored` - Refactoring
- `perf/what-optimized` - Performance
- `test/what-tested` - Test

### 3. Implementa Modifiche

```bash
# Fai le tue modifiche
# Segui coding standards

# Test le tue modifiche
# Assicurati che tutto funzioni
```

### 4. Commit

```bash
git add .
git commit -m "feat(dns): add IPv6 support for DNS lookup"
```

Vedi [Commit Guidelines](#commit-guidelines) per dettagli.

### 5. Push & Create PR

```bash
git push origin feature/your-feature-name
```

Poi apri Pull Request su GitHub.

### 6. PR Checklist

Prima di aprire PR, verifica:

- [ ] ✅ Code segue [Coding Standards](#coding-standards)
- [ ] ✅ Commit messages seguono [Commit Guidelines](#commit-guidelines)
- [ ] ✅ Tutti i test passano (quando implementati)
- [ ] ✅ Nessun warning PHP
- [ ] ✅ Documentazione aggiornata (se necessario)
- [ ] ✅ CHANGELOG.md aggiornato (per feature/fix)
- [ ] ✅ No conflitti con `develop`
- [ ] ✅ PR description completa

### 7. PR Template

```markdown
## Description
Breve descrizione delle modifiche.

## Type of Change
- [ ] Bug fix (non-breaking change che risolve un issue)
- [ ] New feature (non-breaking change che aggiunge funzionalità)
- [ ] Breaking change (fix o feature che causa breaking change)
- [ ] Documentation update

## Motivation and Context
Perché questa modifica è necessaria? Quale problema risolve?

Fixes #123

## How Has This Been Tested?
Descrivi i test effettuati.

- [ ] Test A
- [ ] Test B

## Screenshots (se applicabile)
![Before](before.png)
![After](after.png)

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code where necessary
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing tests pass locally
```

### 8. Code Review

- Rispondi ai commenti reviewer
- Implementa modifiche richieste
- Push aggiornamenti al branch
- PR verrà automaticamente aggiornata

### 9. Merge

Quando approvata, un maintainer farà merge della PR.

---

## Coding Standards

### PHP Standards

Seguiamo **PSR-12** con adattamenti per codice procedural.

#### File Structure

```php
<?php
/**
 * File description
 *
 * @package    ControlloDomin
 * @subpackage Includes
 * @author     Your Name
 */

// Requires
require_once __DIR__ . '/utilities.php';

// Constants
define('MODULE_CONSTANT', 'value');

// Functions (alphabetically ordered)
function functionA() { }
function functionB() { }

// No closing PHP tag
```

#### Naming

```php
// Functions: camelCase
function getAllDnsRecords($domain) { }

// Constants: UPPER_SNAKE_CASE
define('DNS_TIMEOUT', 5);

// Variables: camelCase
$domainName = 'example.com';
```

#### Indentation

- **4 spaces** (NO tabs)
- Opening brace same line
- Spaces around operators

```php
function example($param) {
    if ($condition) {
        doSomething();
    }
}
```

#### Comments

```php
/**
 * Function description
 *
 * @param string $domain Domain name
 * @return array|false Results or false on failure
 */
function analyze($domain) {
    // Implementation comment
    $result = process($domain);

    return $result;
}
```

### HTML/CSS Standards

```html
<!-- HTML: lowercase, double quotes -->
<div class="container">
    <h1>Title</h1>
</div>

<!-- PHP short tags OK for echo -->
<p><?= safeHtmlspecialchars($content); ?></p>
```

```css
/* CSS: kebab-case, organized */
.main-container {
    display: flex;
    justify-content: center;
}
```

### JavaScript Standards

```javascript
// ES6+ syntax
const domain = 'example.com';

// Arrow functions
const process = (data) => data.filter(x => x.valid);

// Async/await
async function fetchData(url) {
    const response = await fetch(url);
    return await response.json();
}
```

### Static Analysis

Prima di commit, esegui:

```bash
# PHP syntax check
find . -name "*.php" -exec php -l {} \;

# Future: PHPStan
vendor/bin/phpstan analyse

# Future: PHP CodeSniffer
vendor/bin/phpcs
```

---

## Commit Guidelines

Seguiamo [Conventional Commits](https://www.conventionalcommits.org/).

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type

- `feat`: Nuova funzionalità
- `fix`: Bug fix
- `docs`: Documentazione
- `style`: Formattazione (no code change)
- `refactor`: Refactoring
- `perf`: Performance improvement
- `test`: Test
- `chore`: Build, config, ecc.

### Scope

Modulo o area modificata:
- `dns`: DNS analysis
- `whois`: WHOIS lookup
- `blacklist`: Blacklist check
- `ssl`: SSL certificate analysis
- `api`: API
- `ui`: User interface
- `config`: Configuration
- `docs`: Documentation

### Subject

- Imperativo: "add" non "added"
- Lowercase
- No punto finale
- Max 50 caratteri

### Body (opzionale)

- Spiega cosa e perché, non come
- Wrapping a 72 caratteri

### Footer (opzionale)

- Breaking changes: `BREAKING CHANGE: <description>`
- Issues: `Fixes #123`, `Closes #456`

### Esempi

```bash
# Simple
git commit -m "feat(dns): add IPv6 support"

# With body
git commit -m "fix(whois): handle timeout errors

WHOIS queries for .it domains were timing out after 5s.
Increased timeout to 10s and added retry logic.

Fixes #123"

# Breaking change
git commit -m "refactor(api)!: rename endpoint /dns to /dns/lookup

BREAKING CHANGE: API endpoint changed from /dns to /dns/lookup
for consistency with other endpoints."
```

---

## Testing

### Manual Testing

Prima di ogni PR, testa manualmente:

```bash
# Test DNS lookup
curl -X POST http://localhost/dns-check.php \
  -d "domain=google.com&analyze=1"

# Test WHOIS
curl -X POST http://localhost/whois-lookup.php \
  -d "domain=google.com&analyze=1"

# Test con domain problematici
# - Domini inesistenti
# - Domini con caratteri speciali
# - IDN (Internationalized Domain Names)
```

### Automated Testing (Future)

```bash
# Unit tests
./vendor/bin/phpunit tests/Unit

# Integration tests
./vendor/bin/phpunit tests/Integration

# E2E tests
npm run test:e2e
```

### Test Checklist

- [ ] ✅ Feature funziona come atteso
- [ ] ✅ No errori PHP
- [ ] ✅ No warning PHP
- [ ] ✅ Edge cases gestiti
- [ ] ✅ Error handling appropriato
- [ ] ✅ Performance accettabile
- [ ] ✅ Mobile responsive (se UI)
- [ ] ✅ Cross-browser (se UI)

---

## Documentazione

### Quando Aggiornare Documentazione

Aggiorna documentazione quando:

- ✅ Aggiungi nuova feature
- ✅ Modifichi comportamento esistente
- ✅ Aggiungi/modifichi API endpoint
- ✅ Modifichi configurazione
- ✅ Aggiungi dipendenze

### File da Aggiornare

1. **README.md**: Se cambia quick start o overview
2. **CHANGELOG.md**: Per ogni feature/fix
3. **ROADMAP.md**: Se feature implementata era in roadmap
4. **documents/FEATURES.md**: Per nuove feature
5. **documents/API.md**: Per modifiche API
6. **documents/CONFIGURATION.md**: Per nuove config
7. **Code comments**: Per funzioni complesse

### Documentation Standards

```markdown
# Title (H1 - solo uno per file)

## Section (H2)

### Subsection (H3)

Paragrafo normale con **bold** e *italic*.

- Lista bullet
- Item 2

1. Lista numerata
2. Item 2

`Inline code`

```php
// Code block con syntax highlighting
function example() {
    return true;
}
```

> Blockquote per note importanti

| Header 1 | Header 2 |
|----------|----------|
| Cell 1   | Cell 2   |
```

---

## Review Process

### Per Contributors

1. **Pazienza**: Review può richiedere qualche giorno
2. **Rispondi ai commenti**: Sii reattivo al feedback
3. **Non prendere personalmente**: Feedback è sul codice, non su di te
4. **Chiedi chiarimenti**: Se qualcosa non è chiaro

### Per Reviewers

1. **Sii gentile e costruttivo**
2. **Spiega il perché**: Non solo cosa cambiare, ma perché
3. **Suggerisci alternative**: Se possibile
4. **Approva quando pronto**: Non perfetto, ma buono abbastanza

### Review Checklist

#### Functionality
- [ ] Feature funziona come descritto?
- [ ] Edge cases gestiti?
- [ ] Error handling appropriato?

#### Code Quality
- [ ] Segue coding standards?
- [ ] Naming chiaro e descrittivo?
- [ ] No codice duplicato?
- [ ] No complessità non necessaria?

#### Performance
- [ ] No problemi performance evidenti?
- [ ] Query ottimizzate?
- [ ] Nessun N+1 query?

#### Security
- [ ] Input validato?
- [ ] Output escaped?
- [ ] No SQL injection risk?
- [ ] No XSS risk?

#### Testing
- [ ] Testato manualmente?
- [ ] Test automatici (se applicabili)?
- [ ] Edge cases testati?

#### Documentation
- [ ] Code commentato dove necessario?
- [ ] Documentazione aggiornata?
- [ ] CHANGELOG aggiornato?

---

## Getting Help

### Risorse

- 📖 **Documentazione**: [documents/](documents/)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/gtechgroup/controllo-domini/discussions)
- 🐛 **Issues**: [GitHub Issues](https://github.com/gtechgroup/controllo-domini/issues)
- 💌 **Email**: dev@controllodomini.it

### Dove Chiedere Aiuto

- **General questions**: GitHub Discussions
- **Bug reports**: GitHub Issues
- **Feature requests**: GitHub Discussions → Feature Requests
- **Security issues**: security@controllodomini.it (privato)
- **Code review help**: Commenta sulla tua PR

---

## Recognition

### Contributors

Tutti i contributor sono riconosciuti in:
- README.md Contributors section
- Pagina About sul sito
- Release notes per contributi significativi

### Types of Contributions

Non solo codice! Valutiamo anche:
- 📝 Documentazione
- 🐛 Bug reports
- 💡 Feature ideas
- 🌍 Traduzioni
- 🎨 Design
- 💬 Community support
- 📢 Evangelism

---

## License

Contribuendo a Controllo Domini, accetti che i tuoi contributi saranno licenziati sotto la stessa licenza del progetto.

---

## Domande?

Se hai domande su come contribuire, non esitare a:
- Aprire una discussion su GitHub
- Contattarci via email: contribute@controllodomini.it

**Grazie per contribuire a Controllo Domini! 🚀**

---

**Ultimo aggiornamento**: Novembre 2025
**Version**: 1.0
