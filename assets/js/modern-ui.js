/**
 * Controllo Domini - Modern UI JavaScript
 * Version 4.2.1
 * Enhanced user experience and interactions
 */

(function() {
    'use strict';

    // ===================================
    // 1. DOM READY
    // ===================================

    function domReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    // ===================================
    // 2. FORM ENHANCEMENTS
    // ===================================

    function initFormEnhancements() {
        const form = document.getElementById('domainForm');
        const input = document.getElementById('domain');
        const submitBtn = document.getElementById('analyzeBtn');

        if (!form || !input || !submitBtn) return;

        // Auto-focus input on load
        if (window.innerWidth > 768) {
            setTimeout(() => input.focus(), 300);
        }

        // Clean domain input on paste
        input.addEventListener('paste', function(e) {
            setTimeout(() => {
                let value = this.value.trim();
                // Remove http://, https://, www.
                value = value.replace(/^(https?:\/\/)?(www\.)?/, '');
                // Remove trailing slash and paths
                value = value.split('/')[0];
                this.value = value;
            }, 10);
        });

        // Auto-clean domain on input
        input.addEventListener('input', function() {
            let value = this.value;
            // Remove spaces
            value = value.replace(/\s+/g, '');
            if (this.value !== value) {
                this.value = value;
            }
        });

        // Form submission with loading state
        form.addEventListener('submit', function() {
            if (input.value.trim()) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading">⏳</span> Analisi in corso...';

                // Add loading class to form
                form.classList.add('form-loading');
            }
        });

        // Example domain links
        const exampleLinks = document.querySelectorAll('.example-link');
        exampleLinks.forEach(link => {
            link.addEventListener('click', function() {
                const domain = this.getAttribute('data-domain');
                input.value = domain;
                input.focus();

                // Animate input
                input.classList.add('highlight');
                setTimeout(() => input.classList.remove('highlight'), 600);
            });
        });
    }

    // ===================================
    // 3. SCROLL TO TOP BUTTON
    // ===================================

    function initScrollToTop() {
        // Create button if doesn't exist
        let btn = document.querySelector('.scroll-to-top');

        if (!btn) {
            btn = document.createElement('button');
            btn.className = 'scroll-to-top';
            btn.innerHTML = '↑';
            btn.setAttribute('aria-label', 'Scroll to top');
            document.body.appendChild(btn);
        }

        // Show/hide based on scroll position
        function toggleButton() {
            if (window.pageYOffset > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }

        // Scroll to top with smooth animation
        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', toggleButton);
        toggleButton(); // Check initial position
    }

    // ===================================
    // 4. SMOOTH SCROLL FOR ANCHOR LINKS
    // ===================================

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // ===================================
    // 5. STATS COUNTER ANIMATION
    // ===================================

    function initStatsCounter() {
        const stats = document.querySelectorAll('[data-value]');

        const animateValue = (element, start, end, duration) => {
            const range = end - start;
            const increment = range / (duration / 16); // 60fps
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    element.textContent = Math.round(end);
                    clearInterval(timer);
                } else {
                    element.textContent = Math.round(current);
                }
            }, 16);
        };

        // Intersection Observer for stats
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const value = parseFloat(element.getAttribute('data-value'));

                    if (!element.classList.contains('animated')) {
                        element.classList.add('animated');
                        animateValue(element, 0, value, 1000);
                    }

                    observer.unobserve(element);
                }
            });
        }, { threshold: 0.5 });

        stats.forEach(stat => observer.observe(stat));
    }

    // ===================================
    // 6. COPY TO CLIPBOARD
    // ===================================

    function initCopyButtons() {
        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-copy');

                navigator.clipboard.writeText(text).then(() => {
                    const originalText = this.textContent;
                    this.textContent = '✓ Copiato!';
                    this.style.background = 'var(--success)';

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.background = '';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                });
            });
        });
    }

    // ===================================
    // 7. ALERT AUTO-DISMISS
    // ===================================

    function initAlertDismiss() {
        const alerts = document.querySelectorAll('.alert');

        alerts.forEach(alert => {
            // Add close button
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '×';
            closeBtn.className = 'alert-close';
            closeBtn.style.cssText = 'background:none;border:none;font-size:1.5rem;cursor:pointer;margin-left:auto;opacity:0.6;transition:opacity 0.2s;';
            closeBtn.addEventListener('mouseover', () => closeBtn.style.opacity = '1');
            closeBtn.addEventListener('mouseout', () => closeBtn.style.opacity = '0.6');

            closeBtn.addEventListener('click', () => {
                alert.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => alert.remove(), 300);
            });

            alert.appendChild(closeBtn);

            // Auto-dismiss success alerts after 5 seconds
            if (alert.classList.contains('alert-success')) {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.style.animation = 'slideOut 0.3s ease-out forwards';
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            }
        });
    }

    // ===================================
    // 8. CARD HOVER EFFECTS
    // ===================================

    function initCardEffects() {
        const cards = document.querySelectorAll('.card, .stat-card');

        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
        });
    }

    // ===================================
    // 9. LAZY LOAD IMAGES
    // ===================================

    function initLazyLoad() {
        const images = document.querySelectorAll('img[data-src]');

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // ===================================
    // 10. KEYBOARD SHORTCUTS
    // ===================================

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K: Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const input = document.getElementById('domain');
                if (input) {
                    input.focus();
                    input.select();
                }
            }

            // Escape: Clear input
            if (e.key === 'Escape') {
                const input = document.getElementById('domain');
                if (input && document.activeElement === input) {
                    input.value = '';
                }
            }
        });
    }

    // ===================================
    // 11. PERFORMANCE MONITORING
    // ===================================

    function logPerformance() {
        if (window.performance) {
            window.addEventListener('load', () => {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                const connectTime = perfData.responseEnd - perfData.requestStart;

                console.log('📊 Performance Metrics:');
                console.log(`   Page Load Time: ${pageLoadTime}ms`);
                console.log(`   Connect Time: ${connectTime}ms`);
            });
        }
    }

    // ===================================
    // 12. FORM VALIDATION
    // ===================================

    function initFormValidation() {
        const form = document.getElementById('domainForm');
        const input = document.getElementById('domain');

        if (!form || !input) return;

        // Real-time validation
        input.addEventListener('blur', function() {
            const value = this.value.trim();

            if (value && !isValidDomain(value)) {
                this.classList.add('invalid');
                showValidationMessage(this, 'Inserisci un dominio valido (es: esempio.com)');
            } else {
                this.classList.remove('invalid');
                hideValidationMessage(this);
            }
        });

        function isValidDomain(domain) {
            const regex = /^([a-z0-9]+([\-a-z0-9]*[a-z0-9]+)?\.)+[a-z]{2,}$/i;
            return regex.test(domain);
        }

        function showValidationMessage(input, message) {
            hideValidationMessage(input);

            const msg = document.createElement('div');
            msg.className = 'validation-message';
            msg.textContent = message;
            msg.style.cssText = 'color:var(--error);font-size:0.875rem;margin-top:0.5rem;animation:fadeIn 0.3s;';
            input.parentElement.appendChild(msg);
        }

        function hideValidationMessage(input) {
            const msg = input.parentElement.querySelector('.validation-message');
            if (msg) msg.remove();
        }
    }

    // ===================================
    // 13. ADD CSS ANIMATION KEYFRAMES
    // ===================================

    function addAnimations() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes slideOut {
                to { opacity: 0; transform: translateX(20px); }
            }

            @keyframes highlight {
                0%, 100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
                50% { box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3); }
            }

            .form-input.highlight {
                animation: highlight 0.6s ease-out;
            }

            .form-loading .form-input {
                pointer-events: none;
                opacity: 0.6;
            }

            .form-input.invalid {
                border-color: var(--error) !important;
            }
        `;
        document.head.appendChild(style);
    }

    // ===================================
    // INITIALIZE ALL
    // ===================================

    domReady(() => {
        console.log('🚀 Controllo Domini - Modern UI v4.2.1');

        addAnimations();
        initFormEnhancements();
        initScrollToTop();
        initSmoothScroll();
        initStatsCounter();
        initCopyButtons();
        initAlertDismiss();
        initCardEffects();
        initLazyLoad();
        initKeyboardShortcuts();
        initFormValidation();
        logPerformance();

        console.log('✅ All UI enhancements initialized');
    });

})();
