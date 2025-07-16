/**
 * JavaScript principale - Controllo Domini
 * 
 * @package ControlDomini
 * @author G Tech Group
 * @website https://controllodomini.it
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
        chartColors: {
            primary: '#5d8ecf',
            secondary: '#264573',
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
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
        navLinks: null
    };

    // State management
    const state = {
        isLoading: false,
        currentDomain: '',
        analysisResults: null,
        charts: {},
        timers: {}
    };

    // ===================================
    // 2. INIZIALIZZAZIONE
    // ===================================
    
    /**
     * Inizializza l'applicazione
     */
    function init() {
        // Cache elementi DOM
        cacheElements();
        
        // Setup event listeners
        setupEventListeners();
        
        // Inizializza componenti UI
        initializeComponents();
        
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
    }

    /**
     * Cache elementi DOM per performance
     */
    function cacheElements() {
        elements.navbar = document.getElementById('navbar');
        elements.domainForm = document.getElementById('domainForm');
        elements.domainInput = document.getElementById('domain');
        elements.submitBtn = document.getElementById('submitBtn');
        elements.resultsSection = document.getElementById('results');
        elements.mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        elements.navLinks = document.querySelector('.nav-links');
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Navbar scroll effect
        window.addEventListener('scroll', debounce(handleScroll, 10));
        
        // Form submission
        if (elements.domainForm) {
            elements.domainForm.addEventListener('submit', handleFormSubmit);
        }
        
        // Domain input validation
        if (elements.domainInput) {
            elements.domainInput.addEventListener('input', debounce(validateDomainInput, config.debounceDelay));
            elements.domainInput.addEventListener('paste', handlePaste);
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
        document.addEventListener('click', handleCopyButtons);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
        
        // Window resize
        window.addEventListener('resize', debounce(handleResize, 250));
    }

    // ===================================
    // 3. GESTIONE FORM E VALIDAZIONE
    // ===================================
    
    /**
     * Gestisce il submit del form
     * @param {Event} e - Evento submit
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        if (state.isLoading) return;
        
        const domain = elements.domainInput.value.trim();
        
        // Validazione
        const validation = validateDomain(domain);
        if (!validation.isValid) {
            showError(validation.error);
            return;
        }
        
        // Avvia analisi
        state.currentDomain = validation.cleanDomain;
        await startAnalysis(validation.cleanDomain);
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
            .trim()
            .toLowerCase();
        
        // Validazione formato base
        const domainRegex = /^[a-z0-9][a-z0-9-]{0,61}[a-z0-9]?\.[a-z]{2,}$/i;
        if (!domainRegex.test(cleanDomain)) {
            return { isValid: false, error: 'Formato dominio non valido' };
        }
        
        // Controllo lunghezza
        if (cleanDomain.length > 253) {
            return { isValid: false, error: 'Dominio troppo lungo' };
        }
        
        // Controllo IDN (Internationalized Domain Names)
        if (/[^\x00-\x7F]/.test(cleanDomain)) {
            // Converti in punycode se necessario
            if (window.punycode) {
                cleanDomain = punycode.toASCII(cleanDomain);
            }
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
        } else {
            showInputError();
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
            }
        }, 10);
    }

    // ===================================
    // 4. ANALISI DOMINIO
    // ===================================
    
    /**
     * Avvia l'analisi del dominio
     * @param {string} domain - Dominio da analizzare
     */
    async function startAnalysis(domain) {
        setLoadingState(true);
        updateSubmitButton('loading');
        
        // Scorri ai risultati
        if (elements.resultsSection) {
            smoothScrollTo(elements.resultsSection);
        }
        
        // Aggiorna URL
        updateUrl(domain);
        
        try {
            // Simula chiamata API (in produzione sostituire con vera API)
            await simulateAnalysis(domain);
            
            // Mostra risultati
            showResults();
            
            // Track analytics
            trackEvent('analysis', 'complete', domain);
            
        } catch (error) {
            console.error('Errore analisi:', error);
            showError('Si è verificato un errore durante l\'analisi. Riprova.');
            trackEvent('analysis', 'error', domain);
        } finally {
            setLoadingState(false);
            updateSubmitButton('ready');
        }
    }

    /**
     * Simula analisi (placeholder per integrazione API)
     * @param {string} domain - Dominio
     */
    async function simulateAnalysis(domain) {
        // Simula delay API
        await delay(2000);
        
        // In produzione, sostituire con:
        // const response = await fetch(`${config.apiEndpoint}/analyze`, {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ domain })
        // });
        // state.analysisResults = await response.json();
        
        state.analysisResults = {
            domain: domain,
            timestamp: new Date().toISOString(),
            dns: { /* risultati DNS */ },
            whois: { /* risultati WHOIS */ },
            blacklist: { /* risultati blacklist */ },
            cloud: { /* servizi cloud rilevati */ }
        };
    }

    // ===================================
    // 5. UI UPDATES E FEEDBACK
    // ===================================
    
    /**
     * Mostra messaggio di errore
     * @param {string} message - Messaggio errore
     */
    function showError(message) {
        const alert = createAlert('error', message);
        elements.domainForm.appendChild(alert);
        
        // Rimuovi dopo 5 secondi
        setTimeout(() => {
            alert.style.opacity = '0';
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
        alert.innerHTML = `
            <span class="alert-icon">${getAlertIcon(type)}</span>
            <span class="alert-content">${message}</span>
        `;
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
    }

    /**
     * Mostra feedback errore su input
     */
    function showInputError() {
        elements.domainInput.classList.remove('success');
        elements.domainInput.classList.add('error');
    }

    /**
     * Rimuove feedback da input
     */
    function removeInputFeedback() {
        elements.domainInput.classList.remove('success', 'error');
    }

    /**
     * Aggiorna stato pulsante submit
     * @param {string} state - Stato (loading, ready, disabled)
     */
    function updateSubmitButton(state) {
        if (!elements.submitBtn) return;
        
        switch (state) {
            case 'loading':
                elements.submitBtn.disabled = true;
                elements.submitBtn.innerHTML = '<span>Analisi in corso</span><span class="loading"></span>';
                break;
            case 'ready':
                elements.submitBtn.disabled = false;
                elements.submitBtn.innerHTML = '<span>Avvia Analisi Completa</span>';
                break;
            case 'disabled':
                elements.submitBtn.disabled = true;
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
    }

    // ===================================
    // 6. GESTIONE RISULTATI
    // ===================================
    
    /**
     * Mostra risultati analisi
     */
    function showResults() {
        if (!state.analysisResults || !elements.resultsSection) return;
        
        // Animazione numeri statistiche
        animateStatistics();
        
        // Inizializza grafici
        initializeCharts();
        
        // Setup tooltips
        setupTooltips();
        
        // Setup copia risultati
        setupCopyFeatures();
    }

    /**
     * Anima numeri statistiche
     */
    function animateStatistics() {
        const statValues = document.querySelectorAll('.stat-value[data-value]');
        
        statValues.forEach(element => {
            const finalValue = parseInt(element.dataset.value);
            if (isNaN(finalValue)) return;
            
            animateValue(element, 0, finalValue, 1000);
        });
    }

    /**
     * Anima valore numerico
     * @param {HTMLElement} element - Elemento
     * @param {number} start - Valore iniziale
     * @param {number} end - Valore finale
     * @param {number} duration - Durata animazione (ms)
     */
    function animateValue(element, start, end, duration) {
        const startTimestamp = performance.now();
        
        const step = (timestamp) => {
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            
            element.textContent = value;
            
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        
        requestAnimationFrame(step);
    }

    // ===================================
    // 7. GRAFICI E VISUALIZZAZIONI
    // ===================================
    
    /**
     * Inizializza grafici
     */
    function initializeCharts() {
        // Grafico reputazione
        const reputationChart = document.getElementById('reputationChart');
        if (reputationChart) {
            createReputationChart(reputationChart);
        }
        
        // Grafico DNS records
        const dnsChart = document.getElementById('dnsChart');
        if (dnsChart) {
            createDnsChart(dnsChart);
        }
        
        // Health score gauge
        const healthGauge = document.getElementById('healthGauge');
        if (healthGauge) {
            createHealthGauge(healthGauge);
        }
    }

    /**
     * Crea grafico reputazione (usando Chart.js se disponibile)
     * @param {HTMLElement} canvas - Elemento canvas
     */
    function createReputationChart(canvas) {
        if (typeof Chart === 'undefined') return;
        
        const ctx = canvas.getContext('2d');
        
        state.charts.reputation = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pulito', 'Blacklist'],
                datasets: [{
                    data: [95, 5], // Dati esempio
                    backgroundColor: [
                        config.chartColors.success,
                        config.chartColors.error
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Crea health gauge SVG
     * @param {HTMLElement} container - Container elemento
     */
    function createHealthGauge(container) {
        const score = parseInt(container.dataset.score) || 0;
        const radius = 54;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - (score / 100) * circumference;
        
        const svg = `
            <svg class="health-score-circle" viewBox="0 0 120 120">
                <circle class="health-score-bg" cx="60" cy="60" r="${radius}" />
                <circle class="health-score-progress" 
                        cx="60" cy="60" r="${radius}"
                        stroke-dasharray="${circumference}"
                        stroke-dashoffset="${offset}" />
            </svg>
            <div class="health-score-text">${score}</div>
        `;
        
        container.innerHTML = svg;
        
        // Anima il cerchio
        setTimeout(() => {
            const progressCircle = container.querySelector('.health-score-progress');
            if (progressCircle) {
                progressCircle.style.strokeDashoffset = offset;
            }
        }, 100);
    }

    // ===================================
    // 8. NAVIGAZIONE E SCROLL
    // ===================================
    
    /**
     * Gestisce scroll della pagina
     */
    function handleScroll() {
        const scrollY = window.scrollY;
        
        // Navbar scroll effect
        if (elements.navbar) {
            elements.navbar.classList.toggle('scrolled', scrollY > 50);
        }
        
        // Show/hide scroll to top button
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        if (scrollTopBtn) {
            scrollTopBtn.classList.toggle('visible', scrollY > 500);
        }
    }

    /**
     * Gestisce smooth scroll
     * @param {Event} e - Evento click
     */
    function handleSmoothScroll(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (!targetId || targetId === '#') return;
        
        const target = document.querySelector(targetId);
        if (!target) return;
        
        smoothScrollTo(target);
    }

    /**
     * Scroll smooth a elemento
     * @param {HTMLElement} target - Elemento target
     */
    function smoothScrollTo(target) {
        const offset = config.scrollOffset;
        const targetPosition = target.offsetTop - offset;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }

    // ===================================
    // 9. MOBILE MENU
    // ===================================
    
    /**
     * Toggle mobile menu
     */
    function toggleMobileMenu() {
        if (!elements.navLinks) return;
        
        elements.navLinks.classList.toggle('active');
        elements.mobileMenuBtn.classList.toggle('active');
        
        // Anima icona hamburger
        const icon = elements.mobileMenuBtn.querySelector('span') || elements.mobileMenuBtn;
        icon.textContent = elements.navLinks.classList.contains('active') ? '✕' : '☰';
    }

    // ===================================
    // 10. FEATURES AVANZATE
    // ===================================
    
    /**
     * Setup tooltips
     */
    function setupTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        
        tooltips.forEach(element => {
            const text = element.dataset.tooltip;
            if (!text) return;
            
            const tooltip = document.createElement('span');
            tooltip.className = 'tooltip-text';
            tooltip.textContent = text;
            
            element.classList.add('tooltip');
            element.appendChild(tooltip);
        });
    }

    /**
     * Setup funzionalità copia
     */
    function setupCopyFeatures() {
        // Aggiungi pulsanti copia a elementi copiabili
        const copyables = document.querySelectorAll('.copyable');
        
        copyables.forEach(element => {
            const copyBtn = document.createElement('button');
            copyBtn.className = 'copy-btn';
            copyBtn.innerHTML = '📋';
            copyBtn.title = 'Copia';
            
            element.appendChild(copyBtn);
        });
    }

    /**
     * Gestisce click su pulsanti copia
     * @param {Event} e - Evento click
     */
    function handleCopyButtons(e) {
        if (!e.target.classList.contains('copy-btn')) return;
        
        const parent = e.target.parentElement;
        const text = parent.textContent.replace('📋', '').trim();
        
        copyToClipboard(text);
        
        // Feedback visivo
        e.target.innerHTML = '✅';
        setTimeout(() => {
            e.target.innerHTML = '📋';
        }, 2000);
    }

    /**
     * Copia testo negli appunti
     * @param {string} text - Testo da copiare
     */
    async function copyToClipboard(text) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback per browser older
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            
            showNotification('Copiato negli appunti!', 'success');
        } catch (err) {
            console.error('Errore copia:', err);
            showNotification('Errore durante la copia', 'error');
        }
    }

    /**
     * Mostra notifica temporanea
     * @param {string} message - Messaggio
     * @param {string} type - Tipo (success, error)
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Trigger animazione
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Rimuovi dopo 3 secondi
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // ===================================
    // 11. KEYBOARD SHORTCUTS
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
            }
        }
        
        // Escape: Chiudi mobile menu
        if (e.key === 'Escape') {
            if (elements.navLinks && elements.navLinks.classList.contains('active')) {
                toggleMobileMenu();
            }
        }
        
        // Ctrl/Cmd + Enter: Submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (elements.domainForm && document.activeElement === elements.domainInput) {
                elements.domainForm.dispatchEvent(new Event('submit'));
            }
        }
    }

    // ===================================
    // 12. URL E HISTORY MANAGEMENT
    // ===================================
    
    /**
     * Aggiorna URL con dominio analizzato
     * @param {string} domain - Dominio
     */
    function updateUrl(domain) {
        const url = new URL(window.location);
        url.searchParams.set('domain', domain);
        
        window.history.pushState({ domain }, '', url);
    }

    /**
     * Controlla parametri URL all'avvio
     */
    function checkUrlParameters() {
        const params = new URLSearchParams(window.location.search);
        const domain = params.get('domain');
        
        if (domain && elements.domainInput) {
            elements.domainInput.value = domain;
            // Auto-avvia analisi se richiesto
            if (params.get('analyze') === 'true') {
                setTimeout(() => {
                    elements.domainForm.dispatchEvent(new Event('submit'));
                }, 500);
            }
        }
    }

    /**
     * Gestisce browser back/forward
     */
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.domain && elements.domainInput) {
            elements.domainInput.value = e.state.domain;
        }
    });

    // ===================================
    // 13. ANALYTICS E TRACKING
    // ===================================
    
    /**
     * Setup analytics
     */
    function setupAnalytics() {
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID', {
                page_title: 'Controllo Domini',
                page_path: window.location.pathname
            });
        }
        
        // Track page timing
        if ('performance' in window) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const perfData = window.performance.timing;
                    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                    
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
        
        // Console log in dev
        if (window.location.hostname === 'localhost') {
            console.log('Track Event:', { category, action, label, value });
        }
    }

    // ===================================
    // 14. UTILITIES
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
     * Delay promise
     * @param {number} ms - Millisecondi
     * @returns {Promise} Promise che si risolve dopo il delay
     */
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Gestisce window resize
     */
    function handleResize() {
        // Chiudi mobile menu su resize a desktop
        if (window.innerWidth > 768 && elements.navLinks && elements.navLinks.classList.contains('active')) {
            toggleMobileMenu();
        }
        
        // Ridisegna grafici
        Object.values(state.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    /**
     * Inizializza componenti UI avanzati
     */
    function initializeComponents() {
        // Progress bars
        initProgressBars();
        
        // Accordion
        initAccordions();
        
        // Tabs
        initTabs();
        
        // Modals
        initModals();
    }

    /**
     * Inizializza progress bars animate
     */
    function initProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const fill = entry.target.querySelector('.progress-bar-fill');
                    if (fill && fill.dataset.percent) {
                        fill.style.width = fill.dataset.percent + '%';
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        progressBars.forEach(bar => observer.observe(bar));
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
                    group.querySelectorAll('.accordion-header').forEach(h => {
                        h.classList.remove('active');
                        h.nextElementSibling.style.maxHeight = null;
                    });
                }
                
                // Toggle corrente
                if (!isOpen) {
                    header.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
    }

    /**
     * Inizializza tabs
     */
    function initTabs() {
        const tabContainers = document.querySelectorAll('.tabs');
        
        tabContainers.forEach(container => {
            const tabs = container.querySelectorAll('.tab');
            const contents = container.querySelectorAll('.tab-content');
            
            tabs.forEach((tab, index) => {
                tab.addEventListener('click', () => {
                    // Rimuovi active da tutti
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    // Attiva corrente
                    tab.classList.add('active');
                    if (contents[index]) {
                        contents[index].classList.add('active');
                    }
                    
                    // Track event
                    trackEvent('ui', 'tab_switch', tab.textContent);
                });
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
    }

    /**
     * Chiude modal
     * @param {HTMLElement} modal - Elemento modal
     */
    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ===================================
    // 15. EXPORT E CONDIVISIONE
    // ===================================
    
    /**
     * Esporta risultati analisi
     * @param {string} format - Formato export (json, csv, pdf)
     */
    window.exportResults = function(format) {
        if (!state.analysisResults) {
            showNotification('Nessun risultato da esportare', 'error');
            return;
        }
        
        switch (format) {
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
                console.error('Formato export non supportato:', format);
        }
        
        trackEvent('export', format, state.currentDomain);
    };

    /**
     * Esporta come JSON
     */
    function exportAsJSON() {
        const data = JSON.stringify(state.analysisResults, null, 2);
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        downloadFile(url, `controllo-domini-${state.currentDomain}-${Date.now()}.json`);
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
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
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
                console.log('Errore condivisione:', err);
            }
        } else {
            // Fallback: copia link
            copyToClipboard(url);
            showNotification('Link copiato negli appunti!', 'success');
            trackEvent('share', 'copy_link', state.currentDomain);
        }
    };

    // ===================================
    // INIZIALIZZAZIONE
    // ===================================
    
    // Avvia quando DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Esponi alcune funzioni globalmente per debugging
    window.ControlDomini = {
        state,
        config,
        trackEvent,
        exportResults,
        shareResults
    };

})();
