/**
 * Logger Utility - Controllo Domini
 * Version: 5.0.0
 *
 * Professional logging system with debug/production modes
 * Logs only in development, silent in production
 */

(function() {
    'use strict';

    /**
     * Detect environment
     * @returns {string} 'development' or 'production'
     */
    function detectEnvironment() {
        // Check hostname
        const hostname = window.location.hostname;

        if (hostname === 'localhost' ||
            hostname === '127.0.0.1' ||
            hostname.startsWith('192.168.') ||
            hostname.startsWith('10.') ||
            hostname.endsWith('.local')) {
            return 'development';
        }

        // Check for debug query parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('debug')) {
            return 'development';
        }

        // Check localStorage override
        if (localStorage.getItem('debug_mode') === 'true') {
            return 'development';
        }

        return 'production';
    }

    /**
     * Logger class with environment-aware methods
     */
    class Logger {
        constructor() {
            this.env = detectEnvironment();
            this.isDevelopment = this.env === 'development';
            this.logHistory = [];
            this.maxHistorySize = 100;
        }

        /**
         * Log regular message (development only)
         */
        log(...args) {
            if (this.isDevelopment) {
                console.log(...args);
                this._addToHistory('log', args);
            }
        }

        /**
         * Log info message (development only)
         */
        info(...args) {
            if (this.isDevelopment) {
                console.info(...args);
                this._addToHistory('info', args);
            }
        }

        /**
         * Log warning message (development only)
         */
        warn(...args) {
            if (this.isDevelopment) {
                console.warn(...args);
                this._addToHistory('warn', args);
            }
        }

        /**
         * Log error message (ALWAYS logged, even in production)
         */
        error(...args) {
            console.error(...args);
            this._addToHistory('error', args);

            // In production, send to error tracking service
            if (!this.isDevelopment) {
                this._sendToErrorTracking(args);
            }
        }

        /**
         * Log debug message (development only)
         */
        debug(...args) {
            if (this.isDevelopment) {
                console.debug(...args);
                this._addToHistory('debug', args);
            }
        }

        /**
         * Start performance timer
         */
        time(label) {
            if (this.isDevelopment) {
                console.time(label);
            }
        }

        /**
         * End performance timer
         */
        timeEnd(label) {
            if (this.isDevelopment) {
                console.timeEnd(label);
            }
        }

        /**
         * Log table (development only)
         */
        table(data) {
            if (this.isDevelopment) {
                console.table(data);
            }
        }

        /**
         * Group logs (development only)
         */
        group(label) {
            if (this.isDevelopment) {
                console.group(label);
            }
        }

        /**
         * Group logs collapsed (development only)
         */
        groupCollapsed(label) {
            if (this.isDevelopment) {
                console.groupCollapsed(label);
            }
        }

        /**
         * End log group
         */
        groupEnd() {
            if (this.isDevelopment) {
                console.groupEnd();
            }
        }

        /**
         * Assert condition (development only)
         */
        assert(condition, ...args) {
            if (this.isDevelopment) {
                console.assert(condition, ...args);
            }
        }

        /**
         * Clear console (development only)
         */
        clear() {
            if (this.isDevelopment) {
                console.clear();
            }
        }

        /**
         * Add to log history
         * @private
         */
        _addToHistory(level, args) {
            this.logHistory.push({
                level,
                message: args,
                timestamp: new Date().toISOString(),
                url: window.location.href
            });

            // Keep history size limited
            if (this.logHistory.length > this.maxHistorySize) {
                this.logHistory.shift();
            }
        }

        /**
         * Send error to tracking service
         * @private
         */
        _sendToErrorTracking(args) {
            try {
                // Could integrate with Sentry, LogRocket, etc.
                const errorData = {
                    message: args.join(' '),
                    url: window.location.href,
                    userAgent: navigator.userAgent,
                    timestamp: new Date().toISOString()
                };

                // Example: send to analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'exception', {
                        description: errorData.message,
                        fatal: false
                    });
                }
            } catch (e) {
                // Fail silently - don't break app if tracking fails
            }
        }

        /**
         * Get log history (useful for support/debugging)
         */
        getHistory() {
            return this.logHistory;
        }

        /**
         * Export logs for support
         */
        exportLogs() {
            const data = {
                environment: this.env,
                userAgent: navigator.userAgent,
                url: window.location.href,
                timestamp: new Date().toISOString(),
                logs: this.logHistory
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json'
            });

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `logs-${Date.now()}.json`;
            a.click();

            URL.revokeObjectURL(url);
        }

        /**
         * Enable debug mode temporarily
         */
        enableDebug() {
            localStorage.setItem('debug_mode', 'true');
            this.env = 'development';
            this.isDevelopment = true;
            this.info('Debug mode enabled');
        }

        /**
         * Disable debug mode
         */
        disableDebug() {
            localStorage.removeItem('debug_mode');
            this.env = detectEnvironment();
            this.isDevelopment = this.env === 'development';
        }

        /**
         * Get current environment
         */
        getEnvironment() {
            return this.env;
        }
    }

    // Create global instance
    const logger = new Logger();

    // Expose to window
    window.Logger = logger;

    // Show environment info (development only)
    if (logger.isDevelopment) {
        logger.group('🚀 Controllo Domini - Logger Initialized');
        logger.info('Environment:', logger.env);
        logger.info('Debug Mode:', logger.isDevelopment);
        logger.info('Commands:');
        logger.info('  Logger.enableDebug()  - Enable debug mode');
        logger.info('  Logger.disableDebug() - Disable debug mode');
        logger.info('  Logger.exportLogs()   - Export logs for support');
        logger.info('  Logger.getHistory()   - View log history');
        logger.groupEnd();
    }

})();
