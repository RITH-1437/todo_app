/**
 * theme.js — Centralized theme management.
 *
 * Exposes a global `window.Theme` object with:
 *   Theme.get()          → 'dark' | 'light'
 *   Theme.apply(theme)   → sets data-theme attribute, body class, persists to localStorage
 *   Theme.toggle()       → switches theme and returns new value
 *
 * Load this before any page-specific scripts that depend on the current theme.
 */
window.Theme = (function () {
    'use strict';

    const STORAGE_KEY = 'theme';
    const VALID       = ['dark', 'light'];
    const DEFAULT     = 'dark';

    function get() {
        const bodyTheme = document.getElementById('body')?.dataset.theme;
        if (VALID.includes(bodyTheme)) return bodyTheme;

        const stored = localStorage.getItem(STORAGE_KEY);
        return VALID.includes(stored) ? stored : DEFAULT;
    }

    function persist(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            // Private browsing / storage unavailable — silently ignore.
        }
    }

    function apply(theme) {
        if (!VALID.includes(theme)) theme = DEFAULT;

        const body = document.getElementById('body');
        if (body) {
            body.dataset.theme = theme;
            if (theme === 'dark') {
                body.classList.remove('bg-gray-100', 'bg-gray-50');
                body.classList.add('bg-slate-950');
            } else {
                body.classList.remove('bg-slate-950', 'bg-gray-900');
                body.classList.add('bg-gray-100');
            }
        }

        document.documentElement.dataset.theme = theme;
        persist(theme);
    }

    function toggle() {
        const next = get() === 'dark' ? 'light' : 'dark';
        apply(next);
        return next;
    }

    return { get, apply, toggle };
}());
