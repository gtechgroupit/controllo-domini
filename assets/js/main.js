/**
 * JavaScript principale - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
 * @version 4.2.1
 */

(function() {
    'use strict';

    // ===================================
    // 1. CONFIGURAZIONE E VARIABILI GLOBALI
    // ===================================
    
    const config = {
        apiEndpoint: '/api',
        animationDuration: 300,
        debounceDelay: 500,
        scrollOffset: 100,
        notificationDuration: 3000,
        chartColors: {
            primary: '#5d8ecf',
            secondary: '#264573',
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6',
            purple: '#8b5cf6'
        }
    };

    // Cache DOM elements
    const elements = {
        navbar: null,
        domainForm: null,
        domainInput: null,
        submitBtn: null,
        resultsSection: null,
        mobileMenuBtn: null,
        navLinks: null,
        scrollTopBtn: null,
        loadingOverlay: null,
        toastContainer: null
    };

    // State management
    const state = {
        isLoading: false,
        currentDomain: '',
        analysisResults: null,
        charts: {},
        timers: {},
        observers: {}
    };

    // ===================================
    // 2. INIZIALIZZAZIONE
    // ===================================
    
    /**
     * Inizializza l'applicazione
     */
    function init() {
        console.log('🚀 Controllo Domini v4.0 - Initializing...');
        
        // Cache elementi DOM
        cacheElements();
        
        // Setup event listeners
        setupEventListeners();
        
        // Inizializza componenti UI
        initializeComponents();
        
        // Setup link esempi
        setupExampleLinks();
        
        // Inizializza AOS (Animate On Scroll)
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 1000,
                easing: 'ease-out-cubic',
                once: true,
                offset: 100
            });
        }
        
        // Setup analytics
        setupAnalytics();
        
        // Check URL parameters
        checkUrlParameters();
        
        // Setup observers
        setupIntersectionObservers();
        
        console.log('✅ Initialization complete');
    }

    /**
     * Cache elementi DOM per performance
     */
    function cacheElements() {
        elements.navbar = document.getElementById('navbar');
        elements.domainForm = document.getElementById('domainForm');
        elements.domainInput = document.getElementById('domain');
        elements.submitBtn = document.getElementById('analyzeBtn'); // Corretto ID
        elements.resultsSection = document.getElementById('results');
        elements.mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        elements.navLinks = document.querySelector('.nav-links');
        elements.scrollTopBtn = document.getElementById('scrollTopBtn');
        elements.loadingOverlay = document.getElementById('loadingOverlay');
        elements.toastContainer = document.getElementById('toastContainer');
        
        // Crea container toast se non esiste
        if (!elements.toastContainer) {
            elements.toastContainer = document.createElement('div');
            elements.toastContainer.id = 'toastContainer';
            elements.toastContainer.className = 'toast-container';
            elements.toastContainer.setAttribute('aria-live', 'polite');
            elements.toastContainer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(elements.toastContainer);
        }
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Navbar scroll effect
        window.addEventListener('scroll', throttle(handleScroll, 10));
        
        // Form submission
        if (elements.domainForm) {
            elements.domainForm.addEventListener('submit', handleFormSubmit);
        }
        
        // Domain input validation
        if (elements.domainInput) {
            elements.domainInput.addEventListener('input', debounce(validateDomainInput, config.debounceDelay));
            elements.domainInput.addEventListener('paste', handlePaste);
            elements.domainInput.addEventListener('keypress', handleEnterKey);
        }
        
        // Mobile menu
        if (elements.mobileMenuBtn) {
            elements.mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', handleSmoothScroll);
        });
        
        // Copy buttons
        document.addEventListener('click', handleGlobalClick);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
        
        // Window resize
        window.addEventListener('resize', debounce(handleResize, 250));
        
        // Scroll to top button
        if (elements.scrollTopBtn) {
            elements.scrollTopBtn.addEventListener('click', scrollToTop);
        }
        
        // Tab navigation
        setupTabNavigation();
    }

    /**
     * Setup link esempi dominio
     */
    function setupExampleLinks() {
        const exampleLinks = document.querySelectorAll('.example-link');
        
        exampleLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const domain = this.dataset.domain;
                
                if (domain && elements.domainInput) {
                    elements.domainInput.value = domain;
                    elements.domainInput.focus();
                    validateDomainInput();
                    
                    // Animazione feedback
                    elements.domainInput.classList.add('pulse');
                    setTimeout(() => {
                        elements.domainInput.classList.remove('pulse');
                    }, 600);
                }
            });
        });
    }

    // ===================================
    // 3. GESTIONE FORM E VALIDAZIONE
    // ===================================
    
    /**
     * Gestisce il submit del form
     * @param {Event} e - Evento submit
     */
    async function handleFormSubmit(e) {
        // Non preveniamo il default perché il form fa POST normale
        // e.preventDefault();
        
        if (state.isLoading) {
            e.preventDefault();
            return;
        }
        
        const domain = elements.domainInput.value.trim();
        
        // Validazione
        const validation = validateDomain(domain);
        if (!validation.isValid) {
            e.preventDefault();
            showError(validation.error);
            shakeElement(elements.domainInput);
            return;
        }
        
        // Imposta stato loading
        state.currentDomain = validation.cleanDomain;
        setLoadingState(true);
        updateSubmitButton('loading');
        
        // Il form verrà inviato normalmente via POST
        // La pagina si ricaricherà con i risultati
    }

    /**
     * Valida un dominio
     * @param {string} domain - Dominio da validare
     * @returns {Object} Risultato validazione
     */
    function validateDomain(domain) {
        if (!domain) {
            return { isValid: false, error: 'Inserisci un nome di dominio' };
        }
        
        // Rimuovi protocollo e path
        let cleanDomain = domain
            .replace(/^https?:\/\//, '')
            .replace(/^www\./, '')
            .replace(/\/.*$/, '')
            .replace(/:\d+$/, '') // Rimuovi porta
            .trim()
            .toLowerCase();
        
        // Controllo domini riservati
        const reservedDomains = ['localhost', '127.0.0.1', '0.0.0.0'];
        if (reservedDomains.includes(cleanDomain)) {
            return { isValid: false, error: 'Questo dominio non può essere analizzato' };
        }
        
        // Validazione formato base
        const domainRegex = /^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i;
        if (!domainRegex.test(cleanDomain)) {
            return { isValid: false, error: 'Formato dominio non valido' };
        }
        
        // Controllo presenza TLD
        if (cleanDomain.indexOf('.') === -1) {
            return { isValid: false, error: 'Il dominio deve includere un\'estensione (es: .com, .it)' };
        }
        
        // Controllo lunghezza
        if (cleanDomain.length > 253) {
            return { isValid: false, error: 'Dominio troppo lungo (max 253 caratteri)' };
        }
        
        // Controllo lunghezza labels
        const labels = cleanDomain.split('.');
        for (let label of labels) {
            if (label.length > 63) {
                return { isValid: false, error: 'Parte del dominio troppo lunga (max 63 caratteri)' };
            }
        }
        
        // Controllo IDN (Internationalized Domain Names)
        if (/[^\x00-\x7F]/.test(cleanDomain)) {
            // Per ora accettiamo IDN ma avvisiamo
            console.log('IDN domain detected:', cleanDomain);
        }
        
        return { isValid: true, cleanDomain: cleanDomain };
    }

    /**
     * Validazione in tempo reale dell'input
     */
    function validateDomainInput() {
        const value = elements.domainInput.value.trim();
        
        if (!value) {
            removeInputFeedback();
            return;
        }
        
        const validation = validateDomain(value);
        
        if (validation.isValid) {
            showInputSuccess();
            hideInputError();
        } else {
            showInputError(validation.error);
        }
    }

    /**
     * Gestisce l'incolla di testo
     * @param {Event} e - Evento paste
     */
    function handlePaste(e) {
        setTimeout(() => {
            // Pulisci automaticamente l'URL incollato
            const value = elements.domainInput.value;
            const cleaned = value
                .replace(/^https?:\/\//, '')
                .replace(/^www\./, '')
                .replace(/\/.*$/, '')
                .trim();
            
            if (cleaned !== value) {
                elements.domainInput.value = cleaned;
                validateDomainInput();
                
                // Notifica pulizia
                showNotification('URL pulito automaticamente', 'info');
            }
        }, 10);
    }

    /**
     * Gestisce tasto Enter nell'input
     * @param {KeyboardEvent} e - Evento tastiera
     */
    function handleEnterKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (elements.domainForm) {
                elements.domainForm.dispatchEvent(new Event('submit', { bubbles: true }));
            }
        }
    }

    // ===================================
    // 4. UI UPDATES E FEEDBACK
    // ===================================
    
    /**
     * Mostra messaggio di errore
     * @param {string} message - Messaggio errore
     */
    function showError(message) {
        // Rimuovi alert esistenti
        const existingAlerts = elements.domainForm.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const alert = createAlert('error', message);
        elements.domainForm.appendChild(alert);
        
        // Auto-rimuovi dopo 5 secondi
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    /**
     * Crea elemento alert
     * @param {string} type - Tipo alert (success, error, warning, info)
     * @param {string} message - Messaggio
     * @returns {HTMLElement} Elemento alert
     */
    function createAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <span class="alert-icon">${getAlertIcon(type)}</span>
            <span class="alert-content">${escapeHtml(message)}</span>
            <button class="alert-close" aria-label="Chiudi">&times;</button>
        `;
        
        // Chiudi al click
        alert.querySelector('.alert-close').addEventListener('click', () => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        });
        
        return alert;
    }

    /**
     * Ottiene icona per tipo alert
     * @param {string} type - Tipo alert
     * @returns {string} Icona
     */
    function getAlertIcon(type) {
        const icons = {
            success: '✅',
            error: '⚠️',
            warning: '⚡',
            info: 'ℹ️'
        };
        return icons[type] || '📌';
    }

    /**
     * Mostra feedback successo su input
     */
    function showInputSuccess() {
        elements.domainInput.classList.remove('error');
        elements.domainInput.classList.add('success');
        
        // Rimuovi messaggio errore se presente
        hideInputError();
    }

    /**
     * Mostra feedback errore su input
     * @param {string} message - Messaggio errore
     */
    function showInputError(message) {
        elements.domainInput.classList.remove('success');
        elements.domainInput.classList.add('error');
        
        // Mostra messaggio errore sotto l'input
        let errorEl = elements.domainInput.parentElement.querySelector('.input-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'input-error';
            elements.domainInput.parentElement.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    /**
     * Nasconde errore input
     */
    function hideInputError() {
        const errorEl = elements.domainInput.parentElement.querySelector('.input-error');
        if (errorEl) {
            errorEl.remove();
        }
    }

    /**
     * Rimuove feedback da input
     */
    function removeInputFeedback() {
        elements.domainInput.classList.remove('success', 'error');
        hideInputError();
    }

    /**
     * Aggiorna stato pulsante submit
     * @param {string} state - Stato (loading, ready, disabled)
     */
    function updateSubmitButton(state) {
        if (!elements.submitBtn) return;
        
        const btnText = elements.submitBtn.querySelector('.btn-text');
        const btnIcon = elements.submitBtn.querySelector('.btn-icon');
        
        switch (state) {
            case 'loading':
                elements.submitBtn.disabled = true;
                elements.submitBtn.classList.add('loading');
                if (btnText) btnText.textContent = 'Analisi in corso...';
                if (btnIcon) btnIcon.innerHTML = '<span class="spinner"></span>';
                break;
                
            case 'ready':
                elements.submitBtn.disabled = false;
                elements.submitBtn.classList.remove('loading');
                if (btnText) btnText.textContent = 'Analizza';
                if (btnIcon) btnIcon.textContent = '→';
                break;
                
            case 'disabled':
                elements.submitBtn.disabled = true;
                elements.submitBtn.classList.remove('loading');
                break;
        }
    }

    /**
     * Imposta stato loading
     * @param {boolean} isLoading - Stato loading
     */
    function setLoadingState(isLoading) {
        state.isLoading = isLoading;
        document.body.classList.toggle('is-loading', isLoading);
        
        if (elements.loadingOverlay) {
            elements.loadingOverlay.classList.toggle('active', isLoading);
        }
    }

    /**
     * Mostra notifica temporanea
     * @param {string} message - Messaggio
     * @param {string} type - Tipo (success, error, warning, info)
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'status');
        notification.setAttribute('aria-live', 'polite');
        
        const icon = getAlertIcon(type);
        notification.innerHTML = `
            <span class="notification-icon">${icon}</span>
            <span class="notification-text">${escapeHtml(message)}</span>
        `;
        
        elements.toastContainer.appendChild(notification);
        
        // Trigger animazione entrata
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // Auto-rimuovi dopo il tempo configurato
        setTimeout(() => {
            notification.classList.remove('show');
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 300);
        }, config.notificationDuration);
    }

    /**
     * Shake animation per elementi
     * @param {HTMLElement} element - Elemento da animare
     */
    function shakeElement(element) {
        element.classList.add('shake');
        setTimeout(() => {
            element.classList.remove('shake');
        }, 600);
    }

    // ===================================
    // 5. GESTIONE RISULTATI
    // ===================================
    
    /**
     * Inizializza visualizzazione risultati
     */
    function initializeResults() {
        // Animazione numeri statistiche
        animateStatistics();
        
        // Inizializza grafici
        initializeCharts();
        
        // Setup tooltips
        setupTooltips();
        
        // Setup copia risultati
        setupCopyFeatures();
        
        // Inizializza tabs DNS
        initializeDnsTabs();
        
        // Setup export buttons
        setupExportButtons();
    }

    /**
     * Anima numeri statistiche
     */
    function animateStatistics() {
        const statValues = document.querySelectorAll('.stat-value[data-value]');
        
        statValues.forEach(element => {
            const finalValue = parseFloat(element.dataset.value);
            if (isNaN(finalValue)) return;
            
            // Determina se è un numero intero o decimale
            const isInteger = finalValue % 1 === 0;
            const decimals = isInteger ? 0 : 1;
            
            animateValue(element, 0, finalValue, 1500, decimals);
        });
    }

    /**
     * Anima valore numerico
     * @param {HTMLElement} element - Elemento
     * @param {number} start - Valore iniziale
     * @param {number} end - Valore finale
     * @param {number} duration - Durata animazione (ms)
     * @param {number} decimals - Numero decimali
     */
    function animateValue(element, start, end, duration, decimals = 0) {
        const startTimestamp = performance.now();
        
        const step = (timestamp) => {
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            
            // Easing function (ease-out-cubic)
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            const value = start + (end - start) * easeProgress;
            
            element.textContent = value.toFixed(decimals);
            
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = end.toFixed(decimals);
            }
        };
        
        requestAnimationFrame(step);
    }

    /**
     * Inizializza tabs DNS
     */
    function initializeDnsTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.dataset.tab;
                
                // Rimuovi active da tutti
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Attiva corrente
                button.classList.add('active');
                const targetPane = document.querySelector(`[data-tab-content="${targetTab}"]`);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
                
                // Track evento
                trackEvent('dns_tabs', 'switch', targetTab);
            });
        });
    }

    // ===================================
    // 6. GRAFICI E VISUALIZZAZIONI
    // ===================================
    
    /**
     * Inizializza grafici
     */
    function initializeCharts() {
        // Health score circles
        const healthCircles = document.querySelectorAll('.health-score-circle[data-score]');
        healthCircles.forEach(initializeHealthCircle);
        
        // Gauge reputazione
        const reputationGauges = document.querySelectorAll('.gauge-circle[style*="--score"]');
        reputationGauges.forEach(animateGauge);
        
        // Progress bars
        initializeProgressBars();
    }

    /**
     * Inizializza cerchio health score
     * @param {HTMLElement} container - Container SVG
     */
    function initializeHealthCircle(container) {
        const score = parseInt(container.dataset.score) || 0;
        const circle = container.querySelector('circle:last-child');
        
        if (circle) {
            // Anima il cerchio dopo un breve delay
            setTimeout(() => {
                circle.style.strokeDashoffset = 'var(--offset)';
            }, 100);
        }
    }

    /**
     * Anima gauge circolare
     * @param {HTMLElement} gauge - Elemento gauge
     */
    function animateGauge(gauge) {
        const progressCircle = gauge.querySelector('circle:nth-child(2)');
        if (progressCircle) {
            setTimeout(() => {
                progressCircle.style.transition = 'stroke-dashoffset 1.5s ease-out';
            }, 100);
        }
    }

    // ===================================
    // 7. NAVIGAZIONE E SCROLL
    // ===================================
    
    /**
     * Gestisce scroll della pagina
     */
    function handleScroll() {
        const scrollY = window.scrollY;
        
        // Navbar scroll effect
        if (elements.navbar) {
            if (scrollY > 50) {
                elements.navbar.classList.add('scrolled');
            } else {
                elements.navbar.classList.remove('scrolled');
            }
        }
        
        // Show/hide scroll to top button
        if (elements.scrollTopBtn) {
            if (scrollY > 500) {
                elements.scrollTopBtn.classList.add('visible');
            } else {
                elements.scrollTopBtn.classList.remove('visible');
            }
        }
    }

    /**
     * Gestisce smooth scroll
     * @param {Event} e - Evento click
     */
    function handleSmoothScroll(e) {
        const href = this.getAttribute('href');
        
        // Ignora link esterni
        if (!href || !href.startsWith('#')) return;
        
        e.preventDefault();
        
        if (href === '#') {
            scrollToTop();
            return;
        }
        
        const target = document.querySelector(href);
        if (!target) return;
        
        smoothScrollTo(target);
        
        // Chiudi menu mobile se aperto
        if (elements.navLinks && elements.navLinks.classList.contains('active')) {
            toggleMobileMenu();
        }
    }

    /**
     * Scroll smooth a elemento
     * @param {HTMLElement} target - Elemento target
     */
    function smoothScrollTo(target) {
        const offset = config.scrollOffset;
        const targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }

    /**
     * Scroll to top
     */
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // ===================================
    // 8. MOBILE MENU
    // ===================================
    
    /**
     * Toggle mobile menu
     */
    function toggleMobileMenu() {
        if (!elements.navLinks || !elements.mobileMenuBtn) return;
        
        const isOpen = elements.navLinks.classList.contains('active');
        
        if (isOpen) {
            // Chiudi menu
            elements.navLinks.classList.remove('active');
            elements.mobileMenuBtn.classList.remove('active');
            elements.mobileMenuBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
        } else {
            // Apri menu
            elements.navLinks.classList.add('active');
            elements.mobileMenuBtn.classList.add('active');
            elements.mobileMenuBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('menu-open');
        }
        
        // Anima icona hamburger
        const icon = elements.mobileMenuBtn.querySelector('span') || elements.mobileMenuBtn;
        icon.textContent = isOpen ? '☰' : '✕';
    }

    // ===================================
    // 9. FEATURES AVANZATE
    // ===================================
    
    /**
     * Setup tooltips
     */
    function setupTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        
        tooltips.forEach(element => {
            const text = element.dataset.tooltip;
            if (!text) return;
            
            // Verifica se tooltip già esiste
            if (element.querySelector('.tooltip-text')) return;
            
            const tooltip = document.createElement('span');
            tooltip.className = 'tooltip-text';
            tooltip.textContent = text;
            tooltip.setAttribute('role', 'tooltip');
            
            element.classList.add('tooltip');
            element.appendChild(tooltip);
            
            // Accessibilità
            element.setAttribute('aria-describedby', `tooltip-${Math.random().toString(36).substr(2, 9)}`);
        });
    }

    /**
     * Setup funzionalità copia
     */
    function setupCopyFeatures() {
        // Aggiungi pulsanti copia a elementi copiabili
        const copyables = document.querySelectorAll('.copyable:not(.has-copy-btn)');
        
        copyables.forEach(element => {
            element.classList.add('has-copy-btn');
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'copy-btn';
            copyBtn.innerHTML = '📋';
            copyBtn.title = 'Copia';
            copyBtn.setAttribute('aria-label', 'Copia negli appunti');
            
            element.appendChild(copyBtn);
        });
    }

    /**
     * Gestisce click globali
     * @param {Event} e - Evento click
     */
    function handleGlobalClick(e) {
        // Gestione pulsanti copia
        if (e.target.classList.contains('copy-btn')) {
            handleCopyButton(e);
        }
        
        // Chiudi mobile menu se click fuori
        if (elements.navLinks && elements.navLinks.classList.contains('active')) {
            if (!e.target.closest('.nav-links') && !e.target.closest('.mobile-menu-btn')) {
                toggleMobileMenu();
            }
        }
    }

    /**
     * Gestisce click su pulsanti copia
     * @param {Event} e - Evento click
     */
    function handleCopyButton(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.target;
        const parent = button.parentElement;
        
        // Estrai testo da copiare
        let textToCopy = '';
        
        if (parent.classList.contains('copyable')) {
            // Copia tutto il testo dell'elemento
            textToCopy = parent.textContent.replace('📋', '').trim();
        } else {
            // Cerca un elemento specifico da copiare
            const copyTarget = parent.querySelector('.copy-target');
            textToCopy = copyTarget ? copyTarget.textContent.trim() : parent.textContent.trim();
        }
        
        copyToClipboard(textToCopy, button);
    }

    /**
     * Copia testo negli appunti
     * @param {string} text - Testo da copiare
     * @param {HTMLElement} button - Pulsante copia (per feedback)
     */
    async function copyToClipboard(text, button) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback per browser older o contesti non sicuri
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                textarea.style.pointerEvents = 'none';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            
            // Feedback visivo
            if (button) {
                const originalContent = button.innerHTML;
                button.innerHTML = '✅';
                button.classList.add('success');
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.classList.remove('success');
                }, 2000);
            }
            
            showNotification('Copiato negli appunti!', 'success');
        } catch (err) {
            console.error('Errore copia:', err);
            showNotification('Errore durante la copia', 'error');
        }
    }

    /**
     * Setup pulsanti export
     */
    function setupExportButtons() {
        // Export DNS
        const exportDnsBtn = document.querySelector('[onclick="exportDNS()"]');
        if (exportDnsBtn) {
            exportDnsBtn.removeAttribute('onclick');
            exportDnsBtn.addEventListener('click', () => exportResults('dns'));
        }
        
        // Copy all DNS
        const copyAllDnsBtn = document.querySelector('[onclick="copyAllDNS()"]');
        if (copyAllDnsBtn) {
            copyAllDnsBtn.removeAttribute('onclick');
            copyAllDnsBtn.addEventListener('click', copyAllDnsRecords);
        }
    }

    // ===================================
    // 10. KEYBOARD SHORTCUTS
    // ===================================
    
    /**
     * Gestisce keyboard shortcuts
     * @param {KeyboardEvent} e - Evento tastiera
     */
    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K: Focus su input dominio
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (elements.domainInput) {
                elements.domainInput.focus();
                elements.domainInput.select();
                
                // Scroll to input se non visibile
                const inputRect = elements.domainInput.getBoundingClientRect();
                if (inputRect.top < 0 || inputRect.bottom > window.innerHeight) {
                    smoothScrollTo(elements.domainInput.closest('.form-section'));
                }
            }
        }
        
        // Escape: Chiudi mobile menu o modal
        if (e.key === 'Escape') {
            // Chiudi mobile menu
            if (elements.navLinks && elements.navLinks.classList.contains('active')) {
                toggleMobileMenu();
            }
            
            // Chiudi modal attivo
            const activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) {
                closeModal(activeModal);
            }
        }
        
        // Ctrl/Cmd + S: Salva/Export risultati
        if ((e.ctrlKey || e.metaKey) && e.key === 's' && state.currentDomain) {
            e.preventDefault();
            exportResults('json');
        }
    }

    // ===================================
    // 11. TAB NAVIGATION
    // ===================================
    
    /**
     * Setup navigazione tabs con tastiera
     */
    function setupTabNavigation() {
        const tabLists = document.querySelectorAll('[role="tablist"]');
        
        tabLists.forEach(tabList => {
            const tabs = tabList.querySelectorAll('[role="tab"]');
            
            tabs.forEach((tab, index) => {
                tab.addEventListener('keydown', (e) => {
                    let newIndex;
                    
                    switch (e.key) {
                        case 'ArrowLeft':
                        case 'ArrowUp':
                            e.preventDefault();
                            newIndex = index - 1;
                            if (newIndex < 0) newIndex = tabs.length - 1;
                            tabs[newIndex].focus();
                            tabs[newIndex].click();
                            break;
                            
                        case 'ArrowRight':
                        case 'ArrowDown':
                            e.preventDefault();
                            newIndex = index + 1;
                            if (newIndex >= tabs.length) newIndex = 0;
                            tabs[newIndex].focus();
                            tabs[newIndex].click();
                            break;
                            
                        case 'Home':
                            e.preventDefault();
                            tabs[0].focus();
                            tabs[0].click();
                            break;
                            
                        case 'End':
                            e.preventDefault();
                            tabs[tabs.length - 1].focus();
                            tabs[tabs.length - 1].click();
                            break;
                    }
                });
            });
        });
    }

    // ===================================
    // 12. INTERSECTION OBSERVERS
    // ===================================
    
    /**
     * Setup Intersection Observers per animazioni
     */
    function setupIntersectionObservers() {
        // Observer per animazioni fade-in
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-visible');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        // Osserva elementi con classe fade-in
        document.querySelectorAll('.fade-in').forEach(el => {
            fadeObserver.observe(el);
        });
        
        // Observer per progress bars
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progressBar = entry.target.querySelector('.progress-bar-fill');
                    if (progressBar && progressBar.dataset.percent) {
                        progressBar.style.width = progressBar.dataset.percent + '%';
                    }
                    progressObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5
        });
        
        // Osserva progress bars
        document.querySelectorAll('.progress-bar').forEach(bar => {
            progressObserver.observe(bar);
        });
        
        // Salva observers per cleanup
        state.observers.fade = fadeObserver;
        state.observers.progress = progressObserver;
    }

    /**
     * Inizializza progress bars
     */
    function initializeProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar');
        
        progressBars.forEach(bar => {
            const fill = bar.querySelector('.progress-bar-fill');
            if (fill && fill.dataset.percent) {
                // Reset iniziale
                fill.style.width = '0%';
                
                // Animazione sarà triggerata dall'Intersection Observer
            }
        });
    }

    // ===================================
    // 13. URL E HISTORY MANAGEMENT
    // ===================================
    
    /**
     * Aggiorna URL con dominio analizzato
     * @param {string} domain - Dominio
     */
    function updateUrl(domain) {
        const url = new URL(window.location);
        url.searchParams.set('domain', domain);
        
        // Non aggiorniamo l'URL durante il POST del form
        // window.history.pushState({ domain }, '', url);
    }

    /**
     * Controlla parametri URL all'avvio
     */
    function checkUrlParameters() {
        const params = new URLSearchParams(window.location.search);
        const domain = params.get('domain');
        
        if (domain && elements.domainInput) {
            elements.domainInput.value = domain;
            
            // Se ci sono risultati nella pagina, inizializzali
            if (elements.resultsSection) {
                initializeResults();
            }
        }
    }

    // ===================================
    // 14. ANALYTICS E TRACKING
    // ===================================
    
    /**
     * Setup analytics
     */
    function setupAnalytics() {
        // Google Analytics 4
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID', {
                page_title: 'Controllo Domini',
                page_path: window.location.pathname
            });
        }
        
        // Track performance metrics
        if ('performance' in window) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const perfData = window.performance.timing;
                    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                    const domReadyTime = perfData.domContentLoadedEventEnd - perfData.navigationStart;
                    const renderTime = perfData.domComplete - perfData.domLoading;
                    
                    console.log('Performance metrics:', {
                        pageLoad: pageLoadTime + 'ms',
                        domReady: domReadyTime + 'ms',
                        render: renderTime + 'ms'
                    });
                    
                    trackEvent('performance', 'page_load', 'time', pageLoadTime);
                }, 0);
            });
        }
    }

    /**
     * Track evento
     * @param {string} category - Categoria
     * @param {string} action - Azione
     * @param {string} label - Label
     * @param {number} value - Valore opzionale
     */
    function trackEvent(category, action, label, value) {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: category,
                event_label: label,
                value: value
            });
        }
        
        // Debug in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('📊 Track Event:', { category, action, label, value });
        }
    }

    // ===================================
    // 15. EXPORT E CONDIVISIONE
    // ===================================
    
    /**
     * Esporta risultati analisi
     * @param {string} type - Tipo export (dns, json, csv, pdf)
     */
    function exportResults(type) {
        if (!state.currentDomain && elements.domainInput) {
            state.currentDomain = elements.domainInput.value;
        }
        
        if (!state.currentDomain) {
            showNotification('Nessun risultato da esportare', 'error');
            return;
        }
        
        switch (type) {
            case 'dns':
                exportDnsRecords();
                break;
            case 'json':
                exportAsJSON();
                break;
            case 'csv':
                exportAsCSV();
                break;
            case 'pdf':
                exportAsPDF();
                break;
            default:
                console.error('Formato export non supportato:', type);
        }
        
        trackEvent('export', type, state.currentDomain);
    }

    /**
     * Esporta record DNS
     */
    function exportDnsRecords() {
        const dnsData = collectDnsData();
        const content = formatDnsExport(dnsData);
        
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        downloadFile(url, `dns-records-${state.currentDomain}-${formatDate()}.txt`);
    }

    /**
     * Copia tutti i record DNS
     */
    function copyAllDnsRecords() {
        const dnsData = collectDnsData();
        const content = formatDnsExport(dnsData);
        
        copyToClipboard(content);
    }

    /**
     * Raccoglie dati DNS dalle tabelle
     * @returns {Object} Dati DNS
     */
    function collectDnsData() {
        const dnsData = {};
        const tables = document.querySelectorAll('.dns-table');
        
        tables.forEach(table => {
            const tabPane = table.closest('.tab-pane');
            if (!tabPane) return;
            
            const recordType = tabPane.dataset.tabContent;
            const rows = table.querySelectorAll('tbody tr');
            
            dnsData[recordType] = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const record = {};
                
                cells.forEach((cell, index) => {
                    const header = table.querySelectorAll('th')[index];
                    if (header) {
                        record[header.textContent.trim()] = cell.textContent.trim();
                    }
                });
                
                dnsData[recordType].push(record);
            });
        });
        
        return dnsData;
    }

    /**
     * Formatta export DNS
     * @param {Object} dnsData - Dati DNS
     * @returns {string} Testo formattato
     */
    function formatDnsExport(dnsData) {
        let content = `DNS Records for ${state.currentDomain}\n`;
        content += `Generated: ${new Date().toLocaleString()}\n`;
        content += '='.repeat(50) + '\n\n';
        
        Object.entries(dnsData).forEach(([type, records]) => {
            if (records.length > 0) {
                content += `${type} Records (${records.length})\n`;
                content += '-'.repeat(30) + '\n';
                
                records.forEach(record => {
                    Object.entries(record).forEach(([key, value]) => {
                        content += `${key}: ${value}\n`;
                    });
                    content += '\n';
                });
                
                content += '\n';
            }
        });
        
        return content;
    }

    /**
     * Esporta come JSON
     */
    function exportAsJSON() {
        const data = {
            domain: state.currentDomain,
            timestamp: new Date().toISOString(),
            dns: collectDnsData(),
            whois: collectWhoisData(),
            blacklist: collectBlacklistData(),
            health: collectHealthData()
        };
        
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        downloadFile(url, `controllo-domini-${state.currentDomain}-${formatDate()}.json`);
    }

    /**
     * Scarica file
     * @param {string} url - URL file
     * @param {string} filename - Nome file
     */
    function downloadFile(url, filename) {
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Cleanup
        setTimeout(() => URL.revokeObjectURL(url), 100);
    }

    /**
     * Condividi risultati
     */
    window.shareResults = async function() {
        const url = window.location.href;
        const text = `Analisi dominio ${state.currentDomain} - Controllo Domini`;
        
        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'Controllo Domini',
                    text: text,
                    url: url
                });
                trackEvent('share', 'native', state.currentDomain);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Errore condivisione:', err);
                }
            }
        } else {
            // Fallback: copia link
            copyToClipboard(url);
            showNotification('Link copiato negli appunti!', 'success');
            trackEvent('share', 'copy_link', state.currentDomain);
        }
    };

    // ===================================
    // 16. UTILITY FUNCTIONS
    // ===================================
    
    /**
     * Debounce function
     * @param {Function} func - Funzione da eseguire
     * @param {number} wait - Delay in ms
     * @returns {Function} Funzione debounced
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function
     * @param {Function} func - Funzione da eseguire
     * @param {number} limit - Limite in ms
     * @returns {Function} Funzione throttled
     */
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Escape HTML
     * @param {string} text - Testo da escapare
     * @returns {string} Testo escapato
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Formatta data per filename
     * @returns {string} Data formattata
     */
    function formatDate() {
        const now = new Date();
        return now.toISOString().split('T')[0];
    }

    /**
     * Gestisce window resize
     */
    function handleResize() {
        // Chiudi mobile menu su resize a desktop
        if (window.innerWidth > 768 && elements.navLinks && elements.navLinks.classList.contains('active')) {
            toggleMobileMenu();
        }
        
        // Ridisegna grafici se presenti
        Object.values(state.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    /**
     * Inizializza componenti UI
     */
    function initializeComponents() {
        // Accordion
        initAccordions();
        
        // Modals
        initModals();
        
        // Lazy loading images
        if ('IntersectionObserver' in window) {
            initLazyLoading();
        }
    }

    /**
     * Inizializza accordions
     */
    function initAccordions() {
        const accordions = document.querySelectorAll('.accordion-header');
        
        accordions.forEach(header => {
            header.addEventListener('click', () => {
                const content = header.nextElementSibling;
                const isOpen = header.classList.contains('active');
                
                // Chiudi altri accordion nello stesso gruppo
                const group = header.closest('.accordion-group');
                if (group) {
                    group.querySelectorAll('.accordion-header.active').forEach(h => {
                        if (h !== header) {
                            h.classList.remove('active');
                            h.nextElementSibling.style.maxHeight = null;
                        }
                    });
                }
                
                // Toggle corrente
                header.classList.toggle('active');
                
                if (!isOpen) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = null;
                }
            });
        });
    }

    /**
     * Inizializza modals
     */
    function initModals() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                const modal = document.getElementById(modalId);
                if (modal) {
                    openModal(modal);
                }
            });
        });
        
        // Chiudi modal su click overlay o pulsante chiudi
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay') || 
                e.target.classList.contains('modal-close')) {
                const modal = e.target.closest('.modal-overlay');
                if (modal) {
                    closeModal(modal);
                }
            }
        });
    }

    /**
     * Apre modal
     * @param {HTMLElement} modal - Elemento modal
     */
    function openModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus trap
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if (focusableElements.length) {
            focusableElements[0].focus();
        }
    }

    /**
     * Chiude modal
     * @param {HTMLElement} modal - Elemento modal
     */
    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    /**
     * Inizializza lazy loading
     */
    function initLazyLoading() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }

    /**
     * Funzioni helper per raccolta dati
     */
    function collectWhoisData() {
        // Raccoglie dati WHOIS dalla pagina
        const whoisSection = document.querySelector('.whois-section');
        if (!whoisSection) return {};
        
        const data = {};
        whoisSection.querySelectorAll('.whois-item').forEach(item => {
            const label = item.querySelector('.whois-label')?.textContent;
            const value = item.querySelector('.whois-value')?.textContent;
            if (label && value) {
                data[label] = value;
            }
        });
        
        return data;
    }

    function collectBlacklistData() {
        // Raccoglie dati blacklist dalla pagina
        const blacklistSection = document.querySelector('.blacklist-section');
        if (!blacklistSection) return {};
        
        return {
            score: blacklistSection.querySelector('.gauge-value')?.textContent,
            status: blacklistSection.querySelector('.reputation-status')?.textContent,
            listings: blacklistSection.querySelector('.blacklist-stat-value')?.textContent
        };
    }

    function collectHealthData() {
        // Raccoglie dati health dalla pagina
        const healthSection = document.querySelector('.health-overview');
        if (!healthSection) return {};
        
        return {
            score: healthSection.querySelector('.score-value')?.textContent,
            status: healthSection.querySelector('.health-status')?.textContent
        };
    }

    /**
     * Cleanup function
     */
    function cleanup() {
        // Rimuovi event listeners
        window.removeEventListener('scroll', handleScroll);
        window.removeEventListener('resize', handleResize);
        
        // Disconnetti observers
        Object.values(state.observers).forEach(observer => {
            if (observer && observer.disconnect) {
                observer.disconnect();
            }
        });
        
        // Clear timers
        Object.values(state.timers).forEach(timer => {
            clearTimeout(timer);
        });
    }

    // ===================================
    // INIZIALIZZAZIONE
    // ===================================
    
    // Avvia quando DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Cleanup on page unload
    window.addEventListener('unload', cleanup);
    
    // Esponi API pubblica
    window.ControlDomini = {
        version: '4.0',
        state,
        config,
        trackEvent,
        exportResults,
        shareResults,
        showNotification,
        copyToClipboard
    };

})();
