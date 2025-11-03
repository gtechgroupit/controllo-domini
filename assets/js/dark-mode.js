/**
 * Dark Mode Toggle
 *
 * Handles dark mode toggle with localStorage persistence
 * and system preference detection
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

(function() {
    'use strict';

    const STORAGE_KEY = 'theme';
    const THEME_DARK = 'dark';
    const THEME_LIGHT = 'light';

    class DarkMode {
        constructor() {
            this.currentTheme = this.getStoredTheme() || this.getSystemTheme();
            this.init();
        }

        /**
         * Initialize dark mode
         */
        init() {
            // Apply theme immediately to avoid flash
            this.applyTheme(this.currentTheme);

            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }

        /**
         * Setup dark mode after DOM is ready
         */
        setup() {
            // Create toggle button
            this.createToggleButton();

            // Listen for system theme changes
            this.watchSystemTheme();

            // Listen for storage changes (sync across tabs)
            this.watchStorageChanges();
        }

        /**
         * Get stored theme from localStorage
         */
        getStoredTheme() {
            try {
                return localStorage.getItem(STORAGE_KEY);
            } catch (e) {
                console.warn('localStorage not available:', e);
                return null;
            }
        }

        /**
         * Get system theme preference
         */
        getSystemTheme() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return THEME_DARK;
            }
            return THEME_LIGHT;
        }

        /**
         * Apply theme to document
         */
        applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            this.currentTheme = theme;

            // Store preference
            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch (e) {
                console.warn('Could not save theme preference:', e);
            }

            // Dispatch event for other scripts
            window.dispatchEvent(new CustomEvent('themechange', {
                detail: { theme }
            }));
        }

        /**
         * Toggle between light and dark theme
         */
        toggle() {
            const newTheme = this.currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
            this.applyTheme(newTheme);
        }

        /**
         * Create toggle button
         */
        createToggleButton() {
            // Check if button already exists
            if (document.querySelector('.dark-mode-toggle')) {
                return;
            }

            const button = document.createElement('button');
            button.className = 'dark-mode-toggle';
            button.setAttribute('aria-label', 'Toggle dark mode');
            button.setAttribute('title', 'Toggle dark mode');

            button.innerHTML = `
                <svg class="sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="moon-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            `;

            button.addEventListener('click', () => this.toggle());

            document.body.appendChild(button);
        }

        /**
         * Watch for system theme changes
         */
        watchSystemTheme() {
            if (!window.matchMedia) return;

            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            // Modern browsers
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', (e) => {
                    // Only apply if user hasn't set a preference
                    if (!this.getStoredTheme()) {
                        this.applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                    }
                });
            }
            // Older browsers
            else if (mediaQuery.addListener) {
                mediaQuery.addListener((e) => {
                    if (!this.getStoredTheme()) {
                        this.applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                    }
                });
            }
        }

        /**
         * Watch for storage changes (sync across tabs)
         */
        watchStorageChanges() {
            window.addEventListener('storage', (e) => {
                if (e.key === STORAGE_KEY && e.newValue) {
                    this.applyTheme(e.newValue);
                }
            });
        }

        /**
         * Get current theme
         */
        getCurrentTheme() {
            return this.currentTheme;
        }

        /**
         * Check if dark mode is enabled
         */
        isDark() {
            return this.currentTheme === THEME_DARK;
        }

        /**
         * Force set theme
         */
        setTheme(theme) {
            if (theme === THEME_DARK || theme === THEME_LIGHT) {
                this.applyTheme(theme);
            }
        }
    }

    // Initialize dark mode
    window.darkMode = new DarkMode();

    // Add to window for external access
    window.toggleDarkMode = () => window.darkMode.toggle();
    window.setTheme = (theme) => window.darkMode.setTheme(theme);
    window.isDarkMode = () => window.darkMode.isDark();

    // Add keyboard shortcut (Ctrl/Cmd + Shift + D)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
            e.preventDefault();
            window.darkMode.toggle();
        }
    });

})();

/**
 * Usage examples:
 *
 * // Toggle dark mode
 * window.toggleDarkMode();
 *
 * // Set specific theme
 * window.setTheme('dark');
 * window.setTheme('light');
 *
 * // Check if dark mode is active
 * if (window.isDarkMode()) {
 *     console.log('Dark mode is active');
 * }
 *
 * // Listen for theme changes
 * window.addEventListener('themechange', (e) => {
 *     console.log('Theme changed to:', e.detail.theme);
 * });
 */
