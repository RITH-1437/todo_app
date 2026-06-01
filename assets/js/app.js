/**
 * app.js — Dashboard-specific logic.
 *
 * Handles:
 *   - Dark / light mode toggling (Tailwind class swaps + Theme.apply)
 *   - Profile dropdown open/close
 *   - Sort-select redirect
 *   - Saving theme preference to the server (profile.php AJAX)
 *
 * Depends on: theme.js (loaded before this file)
 */

// ── Element references ────────────────────────────────────────────────────────
const body               = document.getElementById('body');
const themeToggle        = document.getElementById('theme-toggle');
const themeToggleIcon    = document.getElementById('theme-toggle-icon');
const profileMenuButton  = document.getElementById('profile-menu-button');
const profileDropdown    = document.getElementById('profile-dropdown');
const appTitle           = document.getElementById('app-title');
const welcomeText        = document.getElementById('welcome-text');
const searchInput        = document.getElementById('search-input');
const analyticsCards     = document.querySelectorAll('.analytics-card');
const chartCards         = document.querySelectorAll('.chart-card');
const statLabels         = document.querySelectorAll('.stat-label');
const chartTitles        = document.querySelectorAll('.chart-title');
const taskCards          = document.querySelectorAll('.task-card');
const taskTitles         = document.querySelectorAll('.task-title');
const taskDescriptions   = document.querySelectorAll('.task-description');
const noTasksCard        = document.getElementById('no-tasks');
const noTasksText        = document.querySelector('.no-tasks-text');

// ── Tailwind class groups ─────────────────────────────────────────────────────
const analyticsDarkClasses  = ['bg-white/[0.07]', 'border-white/10', 'shadow-[0_20px_60px_rgba(15,23,42,0.35)]'];
const analyticsLightClasses = ['bg-white', 'border-slate-200', 'shadow-[0_18px_45px_rgba(15,23,42,0.08)]'];
const chartDarkClasses      = ['bg-white/[0.07]', 'border-white/10', 'shadow-[0_24px_80px_rgba(15,23,42,0.42)]'];
const chartLightClasses     = ['bg-white', 'border-slate-200', 'shadow-[0_20px_55px_rgba(15,23,42,0.10)]'];
const searchDarkClasses     = ['bg-slate-900/80', 'text-white', 'placeholder-slate-400', 'border-white/10', 'shadow-[0_18px_45px_rgba(15,23,42,0.24)]', 'hover:shadow-[0_22px_55px_rgba(15,23,42,0.32)]', 'focus:border-blue-400/50', 'focus:ring-blue-500/20', 'focus:shadow-[0_24px_65px_rgba(59,130,246,0.18)]'];
const searchLightClasses    = ['bg-white', 'text-slate-900', 'placeholder-slate-400', 'border-slate-200', 'shadow-[0_14px_35px_rgba(15,23,42,0.08)]', 'hover:shadow-[0_18px_45px_rgba(15,23,42,0.12)]', 'focus:border-blue-400/60', 'focus:ring-blue-500/20', 'focus:shadow-[0_20px_55px_rgba(59,130,246,0.16)]'];
const profileMenuDarkClasses  = ['bg-white/[0.07]', 'border-white/10', 'text-white', 'shadow-[0_18px_45px_rgba(15,23,42,0.20)]'];
const profileMenuLightClasses = ['bg-white', 'border-slate-200', 'text-slate-900', 'shadow-[0_14px_35px_rgba(15,23,42,0.08)]'];
const dropdownDarkClasses     = ['bg-slate-900/95', 'border-white/10', 'text-white', 'shadow-slate-950/40'];
const dropdownLightClasses    = ['bg-white/95', 'border-slate-200', 'text-slate-900', 'shadow-slate-200/80'];

// ── Helpers ───────────────────────────────────────────────────────────────────
function swapClasses(el, remove, add) {
    if (!el) return;
    el.classList.remove(...remove);
    el.classList.add(...add);
}

// ── Theme-specific DOM updates ────────────────────────────────────────────────
function applyAnalyticsTheme(theme) {
    const dark = theme === 'dark';
    analyticsCards.forEach(c => swapClasses(c, dark ? analyticsLightClasses : analyticsDarkClasses, dark ? analyticsDarkClasses : analyticsLightClasses));
    chartCards.forEach(c => swapClasses(c, dark ? chartLightClasses : chartDarkClasses, dark ? chartDarkClasses : chartLightClasses));
    statLabels.forEach(l => { l.classList.remove('text-slate-300', 'text-slate-500'); l.classList.add(dark ? 'text-slate-300' : 'text-slate-500'); });
    chartTitles.forEach(t => { t.classList.remove('text-white', 'text-slate-900'); t.classList.add(dark ? 'text-white' : 'text-slate-900'); });
    if (window.updateTaskChartTheme) window.updateTaskChartTheme(theme);
}

function applySearchTheme(theme) {
    if (!searchInput) return;
    const dark = theme === 'dark';
    swapClasses(searchInput, dark ? searchLightClasses : searchDarkClasses, dark ? searchDarkClasses : searchLightClasses);
}

function applyProfileMenuTheme(theme) {
    const dark = theme === 'dark';
    swapClasses(profileMenuButton, dark ? profileMenuLightClasses : profileMenuDarkClasses, dark ? profileMenuDarkClasses : profileMenuLightClasses);
    swapClasses(profileDropdown,   dark ? dropdownLightClasses    : dropdownDarkClasses,   dark ? dropdownDarkClasses    : dropdownLightClasses);
}

// ── Full theme apply (calls all sub-appliers) ─────────────────────────────────
function enableDarkMode(persistPreference = false) {
    if (appTitle) { appTitle.classList.replace('text-gray-800', 'text-white'); }
    if (welcomeText) { welcomeText.classList.remove('text-gray-500'); welcomeText.classList.add('text-slate-400'); }
    applySearchTheme('dark');
    applyAnalyticsTheme('dark');
    applyProfileMenuTheme('dark');

    taskCards.forEach(card => {
        if (!card.classList.contains('overdue-task-card')) {
            card.classList.remove('bg-white');
            card.classList.add('bg-gray-800');
        }
    });
    taskTitles.forEach(t => { if (!t.closest('.overdue-task-card')) t.classList.replace('text-gray-800', 'text-white'); });
    taskDescriptions.forEach(d => d.classList.replace('text-gray-600', 'text-gray-300'));
    if (noTasksCard)  { noTasksCard.classList.remove('bg-white'); noTasksCard.classList.add('bg-gray-800'); }
    if (noTasksText)  noTasksText.classList.replace('text-gray-500', 'text-gray-300');
    if (themeToggleIcon) themeToggleIcon.textContent = '☀️';

    window.Theme?.apply('dark');
    if (persistPreference) saveThemePreference('dark');
}

function disableDarkMode(persistPreference = false) {
    if (appTitle) { appTitle.classList.replace('text-white', 'text-gray-800'); }
    if (welcomeText) { welcomeText.classList.remove('text-gray-300', 'text-slate-400'); welcomeText.classList.add('text-gray-500'); }
    applySearchTheme('light');
    applyAnalyticsTheme('light');
    applyProfileMenuTheme('light');

    taskCards.forEach(card => {
        if (!card.classList.contains('overdue-task-card')) {
            card.classList.remove('bg-gray-800');
            card.classList.add('bg-white');
        }
    });
    taskTitles.forEach(t => { if (!t.closest('.overdue-task-card')) t.classList.replace('text-white', 'text-gray-800'); });
    taskDescriptions.forEach(d => d.classList.replace('text-gray-300', 'text-gray-600'));
    if (noTasksCard)  { noTasksCard.classList.remove('bg-gray-800'); noTasksCard.classList.add('bg-white'); }
    if (noTasksText)  noTasksText.classList.replace('text-gray-300', 'text-gray-500');
    if (themeToggleIcon) themeToggleIcon.textContent = '🌙';

    window.Theme?.apply('light');
    if (persistPreference) saveThemePreference('light');
}

// ── Initial theme load ────────────────────────────────────────────────────────
const initialTheme = body?.dataset.theme || window.todoInitialTheme || window.Theme?.get() || 'dark';
if (initialTheme === 'light') disableDarkMode();
else enableDarkMode();

// ── Theme toggle button ───────────────────────────────────────────────────────
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        // Use data-theme attribute — not CSS class — for reliable detection
        if (body.dataset.theme === 'light') {
            enableDarkMode(true);
        } else {
            disableDarkMode(true);
        }
    });
}

// ── Profile menu dropdown ─────────────────────────────────────────────────────
if (profileMenuButton && profileDropdown) {
    profileMenuButton.addEventListener('click', e => {
        e.stopPropagation();
        profileDropdown.classList.toggle('pointer-events-none');
        profileDropdown.classList.toggle('opacity-0');
        profileDropdown.classList.toggle('translate-y-2');
        profileDropdown.classList.toggle('scale-95');
    });

    document.addEventListener('click', e => {
        if (!profileDropdown.contains(e.target) && !profileMenuButton.contains(e.target)) {
            profileDropdown.classList.add('pointer-events-none', 'opacity-0', 'translate-y-2', 'scale-95');
        }
    });
}

// ── Sort select ───────────────────────────────────────────────────────────────
const sortSelect = document.getElementById('sort-select');
if (sortSelect) {
    sortSelect.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortSelect.value);
        window.location.href = url.toString();
    });
}

// ── Save theme preference to server ──────────────────────────────────────────
async function saveThemePreference(theme) {
    const fd = new FormData();
    fd.append('action', 'update_theme');
    fd.append('theme_preference', theme);

    try {
        const res  = await fetch('profile.php', { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Could not save theme.');
        if (window.showToast) window.showToast(data.message || 'Theme changed.', 'success');
    } catch (err) {
        if (window.showToast) window.showToast(err.message || 'Could not save theme.', 'error');
    }
}
