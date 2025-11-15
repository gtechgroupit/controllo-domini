/**
 * Advanced Enhancements - Controllo Domini v4.2.1
 *
 * Features:
 * - Lazy Loading Images
 * - Error Handling with Retry Logic
 * - Keyboard Shortcuts
 * - Touch Gestures for Mobile
 * - Service Worker Registration
 *
 * @package ControlDomini
 * @version 4.2.1
 */

(function() {
    'use strict';

    // ========================================
    // 1. LAZY LOADING IMAGES
    // ========================================

    const lazyLoadImages = () => {
        // Feature detection
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;

                        // Load image
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }

                        // Load srcset if present
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }

                        // Add loaded class for fade-in effect
                        img.classList.add('lazy-loaded');

                        // Stop observing this image
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px', // Start loading 50px before entering viewport
                threshold: 0.01
            });

            // Observe all images with data-src
            document.querySelectorAll('img[data-src], img[data-srcset]').forEach(img => {
                imageObserver.observe(img);
            });

            console.log('✅ Lazy loading initialized');
        } else {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('img[data-src]').forEach(img => {
                if (img.dataset.src) img.src = img.dataset.src;
                if (img.dataset.srcset) img.srcset = img.dataset.srcset;
            });
        }
    };

    // ========================================
    // 2. ERROR HANDLING WITH RETRY LOGIC
    // ========================================

    const ErrorHandler = {
        maxRetries: 3,
        retryDelay: 2000, // 2 seconds

        /**
         * Show user-friendly error toast
         */
        showError(message, options = {}) {
            const {
                type = 'error',
                duration = 5000,
                actions = []
            } = options;

            // Remove existing toasts
            document.querySelectorAll('.error-toast').forEach(t => t.remove());

            // Create toast
            const toast = document.createElement('div');
            toast.className = `error-toast error-toast-${type}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');

            let actionsHtml = '';
            if (actions.length > 0) {
                actionsHtml = '<div class="toast-actions">';
                actions.forEach(action => {
                    actionsHtml += `<button class="toast-btn" data-action="${action.id}">${action.label}</button>`;
                });
                actionsHtml += '</div>';
            }

            toast.innerHTML = `
                <div class="toast-icon">
                    ${type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : 'ℹ️'}
                </div>
                <div class="toast-content">
                    <div class="toast-message">${message}</div>
                    ${actionsHtml}
                </div>
                <button class="toast-close" aria-label="Close notification">×</button>
            `;

            document.body.appendChild(toast);

            // Add event listeners to action buttons
            toast.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = actions.find(a => a.id === e.target.dataset.action);
                    if (action && action.callback) action.callback();
                    toast.remove();
                });
            });

            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.add('toast-hiding');
                setTimeout(() => toast.remove(), 300);
            });

            // Auto-hide after duration
            if (duration > 0) {
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.classList.add('toast-hiding');
                        setTimeout(() => toast.remove(), 300);
                    }
                }, duration);
            }

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.add('toast-visible');
            });
        },

        /**
         * Retry failed requests with exponential backoff
         */
        async retryRequest(fn, retries = this.maxRetries) {
            try {
                return await fn();
            } catch (error) {
                if (retries > 0) {
                    console.log(`Retrying... (${this.maxRetries - retries + 1}/${this.maxRetries})`);
                    await this.delay(this.retryDelay * (this.maxRetries - retries + 1));
                    return this.retryRequest(fn, retries - 1);
                }
                throw error;
            }
        },

        /**
         * Delay helper
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * Handle fetch errors with user-friendly messages
         */
        handleFetchError(error, context = 'Operation') {
            let message = `${context} failed. `;

            if (!navigator.onLine) {
                message += 'Please check your internet connection.';
            } else if (error.message.includes('timeout')) {
                message += 'The request timed out. Please try again.';
            } else if (error.message.includes('429')) {
                message += 'Too many requests. Please wait a moment.';
            } else if (error.message.includes('500')) {
                message += 'Server error. We\'re working to fix this.';
            } else {
                message += 'Please try again later.';
            }

            this.showError(message, {
                type: 'error',
                actions: [{
                    id: 'retry',
                    label: 'Retry',
                    callback: () => window.location.reload()
                }]
            });
        }
    };

    // Make ErrorHandler globally available
    window.ErrorHandler = ErrorHandler;

    // ========================================
    // 3. KEYBOARD SHORTCUTS
    // ========================================

    const KeyboardShortcuts = {
        shortcuts: {
            // Focus domain search input
            '/': () => {
                const domainInput = document.querySelector('input[name="domain"]');
                if (domainInput) {
                    domainInput.focus();
                    domainInput.select();
                }
            },

            // Submit form
            'ctrl+enter': () => {
                const form = document.getElementById('domainForm');
                if (form) form.submit();
            },

            // Clear form
            'ctrl+k': () => {
                const form = document.getElementById('domainForm');
                if (form) {
                    form.reset();
                    const domainInput = document.querySelector('input[name="domain"]');
                    if (domainInput) domainInput.focus();
                }
            },

            // Copy results
            'ctrl+c': (e) => {
                if (!window.getSelection().toString() && document.querySelector('.analysis-results')) {
                    e.preventDefault();
                    this.copyResults();
                }
            },

            // Show help
            '?': () => {
                this.showHelp();
            },

            // Escape to close modals
            'escape': () => {
                document.querySelectorAll('.modal, .toast').forEach(el => {
                    el.remove();
                });
            }
        },

        init() {
            document.addEventListener('keydown', (e) => {
                // Don't trigger shortcuts when typing in inputs
                if (e.target.matches('input, textarea, select')) {
                    // Except for '/' which focuses search
                    if (e.key !== '/') return;
                }

                const key = this.getKeyCombo(e);
                const handler = this.shortcuts[key];

                if (handler) {
                    e.preventDefault();
                    handler(e);
                }
            });

            console.log('⌨️ Keyboard shortcuts enabled (Press ? for help)');
        },

        getKeyCombo(e) {
            const parts = [];
            if (e.ctrlKey) parts.push('ctrl');
            if (e.altKey) parts.push('alt');
            if (e.shiftKey) parts.push('shift');
            parts.push(e.key.toLowerCase());
            return parts.join('+');
        },

        copyResults() {
            const results = document.querySelector('.analysis-results');
            if (!results) return;

            // Extract text content
            const text = results.innerText;

            // Copy to clipboard
            navigator.clipboard.writeText(text).then(() => {
                ErrorHandler.showError('Results copied to clipboard!', {
                    type: 'success',
                    duration: 2000
                });
            }).catch(() => {
                ErrorHandler.showError('Failed to copy results', {
                    type: 'error'
                });
            });
        },

        showHelp() {
            const helpHtml = `
                <div class="modal keyboard-help-modal" role="dialog" aria-labelledby="help-title">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="help-title">Keyboard Shortcuts</h2>
                            <button class="modal-close" aria-label="Close">×</button>
                        </div>
                        <div class="modal-body">
                            <table class="shortcuts-table">
                                <tr>
                                    <td><kbd>/</kbd></td>
                                    <td>Focus search input</td>
                                </tr>
                                <tr>
                                    <td><kbd>Ctrl</kbd> + <kbd>Enter</kbd></td>
                                    <td>Submit form</td>
                                </tr>
                                <tr>
                                    <td><kbd>Ctrl</kbd> + <kbd>K</kbd></td>
                                    <td>Clear form</td>
                                </tr>
                                <tr>
                                    <td><kbd>Ctrl</kbd> + <kbd>C</kbd></td>
                                    <td>Copy results</td>
                                </tr>
                                <tr>
                                    <td><kbd>?</kbd></td>
                                    <td>Show this help</td>
                                </tr>
                                <tr>
                                    <td><kbd>Esc</kbd></td>
                                    <td>Close modals</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="modal-backdrop"></div>
                </div>
            `;

            const div = document.createElement('div');
            div.innerHTML = helpHtml;
            const modal = div.firstElementChild;
            document.body.appendChild(modal);

            // Close handlers
            const close = () => {
                modal.classList.add('modal-hiding');
                setTimeout(() => modal.remove(), 300);
            };

            modal.querySelector('.modal-close').addEventListener('click', close);
            modal.querySelector('.modal-backdrop').addEventListener('click', close);

            // Animate in
            requestAnimationFrame(() => {
                modal.classList.add('modal-visible');
            });
        }
    };

    // ========================================
    // 4. TOUCH GESTURES FOR MOBILE
    // ========================================

    const TouchGestures = {
        init() {
            if (!('ontouchstart' in window)) return;

            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;

            const minSwipeDistance = 50;

            document.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });

            document.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                this.handleGesture();
            }, { passive: true });

            console.log('👆 Touch gestures enabled');
        },

        handleGesture() {
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;

            // Horizontal swipe (left/right)
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
                if (deltaX > 0) {
                    this.onSwipeRight();
                } else {
                    this.onSwipeLeft();
                }
            }

            // Vertical swipe (up/down)
            else if (Math.abs(deltaY) > minSwipeDistance) {
                if (deltaY > 0) {
                    this.onSwipeDown();
                } else {
                    this.onSwipeUp();
                }
            }
        },

        onSwipeLeft() {
            // Navigate forward if history available
            console.log('Swipe left detected');
        },

        onSwipeRight() {
            // Navigate back
            if (window.history.length > 1) {
                window.history.back();
            }
        },

        onSwipeDown() {
            // Refresh page
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        onSwipeUp() {
            // Scroll to bottom
            console.log('Swipe up detected');
        }
    };

    // ========================================
    // 5. SERVICE WORKER REGISTRATION (PWA)
    // ========================================

    const registerServiceWorker = () => {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('✅ Service Worker registered:', registration.scope);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available
                                    ErrorHandler.showError('New version available!', {
                                        type: 'info',
                                        duration: 0,
                                        actions: [{
                                            id: 'update',
                                            label: 'Update Now',
                                            callback: () => window.location.reload()
                                        }]
                                    });
                                }
                            });
                        });
                    })
                    .catch(err => {
                        console.log('Service Worker registration failed:', err);
                    });
            });
        }
    };

    // ========================================
    // 6. NETWORK STATUS MONITORING
    // ========================================

    const NetworkMonitor = {
        init() {
            window.addEventListener('online', () => {
                ErrorHandler.showError('Back online!', {
                    type: 'success',
                    duration: 3000
                });
            });

            window.addEventListener('offline', () => {
                ErrorHandler.showError('No internet connection. Some features may not work.', {
                    type: 'warning',
                    duration: 0
                });
            });

            // Check initial status
            if (!navigator.onLine) {
                ErrorHandler.showError('You are currently offline', {
                    type: 'warning',
                    duration: 0
                });
            }
        }
    };

    // ========================================
    // 7. PERFORMANCE MONITORING
    // ========================================

    const PerformanceMonitor = {
        init() {
            if ('PerformanceObserver' in window) {
                // Monitor Long Tasks (> 50ms)
                try {
                    const observer = new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            if (entry.duration > 50) {
                                console.warn('Long task detected:', entry.duration.toFixed(2) + 'ms');
                            }
                        }
                    });
                    observer.observe({ entryTypes: ['longtask'] });
                } catch (e) {
                    // PerformanceObserver not supported for longtask
                }

                // Log page load performance
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        if (perfData) {
                            console.log('📊 Page Performance:');
                            console.log('  DNS:', (perfData.domainLookupEnd - perfData.domainLookupStart).toFixed(2) + 'ms');
                            console.log('  TCP:', (perfData.connectEnd - perfData.connectStart).toFixed(2) + 'ms');
                            console.log('  TTFB:', (perfData.responseStart - perfData.requestStart).toFixed(2) + 'ms');
                            console.log('  Download:', (perfData.responseEnd - perfData.responseStart).toFixed(2) + 'ms');
                            console.log('  DOM Ready:', (perfData.domContentLoadedEventEnd - perfData.fetchStart).toFixed(2) + 'ms');
                            console.log('  Load Complete:', (perfData.loadEventEnd - perfData.fetchStart).toFixed(2) + 'ms');
                        }
                    }, 0);
                });
            }
        }
    };

    // ========================================
    // INITIALIZATION
    // ========================================

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('🚀 Initializing enhancements...');

        lazyLoadImages();
        KeyboardShortcuts.init();
        TouchGestures.init();
        NetworkMonitor.init();
        PerformanceMonitor.init();
        registerServiceWorker();

        console.log('✅ All enhancements loaded');
    }

})();
