/**
 * TSA Legacy — Enterprise Application JavaScript
 * 
 * Features:
 *  - Sidebar (toggle, collapse, mobile overlay, auto-close)
 *  - Theme toggle (dark/light) with persistence
 *  - Loading overlays + skeleton screens
 *  - Premium toast notifications with progress bar
 *  - Keyboard shortcuts (power-user navigation)
 *  - Form validation + double-submit prevention
 *  - AJAX helper with CSRF
 *  - Global search shortcut (Ctrl+K)
 *  - Print utility
 *  - Currency + number formatting
 *  - Accessibility enhancements
 *  - Scroll-to-top button
 *  - Auto-refresh stale data indicator
 */

'use strict';

// ============================================================
// SIDEBAR
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const topNavbar = document.getElementById('topNavbar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function isMobile() { return window.innerWidth < 992; }

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('collapsed');
        if (isMobile()) {
            sidebar.classList.add('show');
            if (sidebarOverlay) sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        } else {
            if (mainContent) mainContent.classList.remove('sidebar-collapsed');
            if (topNavbar) topNavbar.classList.remove('sidebar-collapsed');
        }
        localStorage.setItem('sidebarOpen', '1');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('collapsed');
        if (isMobile()) {
            sidebar.classList.remove('show');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            if (mainContent) mainContent.classList.add('sidebar-collapsed');
            if (topNavbar) topNavbar.classList.add('sidebar-collapsed');
        }
        localStorage.setItem('sidebarOpen', '0');
    }

    function toggleSidebar() {
        if (sidebar && sidebar.classList.contains('collapsed')) openSidebar();
        else closeSidebar();
    }

    // Restore sidebar state on desktop
    if (!isMobile()) {
        const savedState = localStorage.getItem('sidebarOpen');
        if (savedState === '0') closeSidebar();
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // Auto-close sidebar on mobile when nav link is clicked
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function () {
                if (isMobile()) closeSidebar();
            });
        });
    }

    // Responsive: close on resize to desktop
    window.addEventListener('resize', debounce(function () {
        if (!isMobile() && sidebar) {
            sidebar.classList.remove('show');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }, 200));

    // ============================================================
    // THEME TOGGLE (supports both #themeSwitch checkbox and #themeToggle button)
    // ============================================================
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.setAttribute('data-theme', theme); // legacy CSS compat
        localStorage.setItem('theme', theme);
        // Sync checkbox
        const sw = document.getElementById('themeSwitch');
        if (sw) sw.checked = (theme === 'dark');
        // Sync icon button
        const btn = document.getElementById('themeToggle');
        if (btn) { const ico = btn.querySelector('i'); if (ico) ico.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon'; }
        // Notify server (save preference)
        if (window.APP_URL) {
            fetch(window.APP_URL + '/index.php?page=profile&action=save_theme', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'theme=' + encodeURIComponent(theme)
            }).catch(() => {});
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Checkbox switch (#themeSwitch in navbar)
    const themeSwitch = document.getElementById('themeSwitch');
    if (themeSwitch) {
        themeSwitch.addEventListener('change', function () {
            applyTheme(this.checked ? 'dark' : 'light');
        });
    }

    // Button toggle (#themeToggle — alternative button style)
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const cur = document.documentElement.getAttribute('data-bs-theme') || 'light';
            applyTheme(cur === 'dark' ? 'light' : 'dark');
        });
    }

    // ============================================================
    // SCROLL-TO-TOP BUTTON
    // ============================================================
    const scrollBtn = document.createElement('button');
    scrollBtn.id = 'scrollToTop';
    scrollBtn.type = 'button';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    scrollBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollBtn.style.cssText = `
        position:fixed; bottom:80px; right:20px; z-index:1050;
        width:40px; height:40px; border-radius:50%; border:none;
        background:var(--bs-primary); color:#fff;
        box-shadow:0 4px 15px rgba(0,0,0,.2);
        cursor:pointer; opacity:0; transform:translateY(10px);
        transition:all .3s ease; display:flex;
        align-items:center; justify-content:center;
        font-size:14px;
    `;
    document.body.appendChild(scrollBtn);
    window.addEventListener('scroll', debounce(function () {
        if (window.scrollY > 300) {
            scrollBtn.style.opacity = '1';
            scrollBtn.style.transform = 'translateY(0)';
        } else {
            scrollBtn.style.opacity = '0';
            scrollBtn.style.transform = 'translateY(10px)';
        }
    }, 50));
    scrollBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ============================================================
    // KEYBOARD SHORTCUTS
    // ============================================================
    document.addEventListener('keydown', function (e) {
        // Skip if typing in input/textarea
        const tag = document.activeElement?.tagName?.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

        // Ctrl+K / Cmd+K — focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const search = document.querySelector('#globalSearch, [data-search], .search-input, input[type="search"]');
            if (search) { search.focus(); search.select(); }
            return;
        }

        // / — focus search (like GitHub)
        if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
            const search = document.querySelector('#globalSearch, input[type="search"]');
            if (search) { e.preventDefault(); search.focus(); }
            return;
        }

        // Escape — close modals
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const m = bootstrap?.Modal?.getInstance(modal);
                if (m) m.hide();
            }
        }

        // G + S — go to sales
        if (e.key === 'g') {
            window._gKeyPressed = true;
            setTimeout(() => { window._gKeyPressed = false; }, 1000);
        }
        if (window._gKeyPressed) {
            const shortcuts = { 's': '?page=sales', 'p': '?page=purchases', 'd': '?page=dashboard', 'c': '?page=customers', 'r': '?page=reports', 'i': '?page=products' };
            if (shortcuts[e.key]) {
                window._gKeyPressed = false;
                const base = window.location.pathname.replace('/index.php', '');
                window.location.href = base + '/index.php' + shortcuts[e.key];
            }
        }
    });

    // Show keyboard shortcut hint (first visit)
    if (!localStorage.getItem('kbHintShown')) {
        setTimeout(() => {
            showToast('💡 Tip: Press G+S for Sales, G+D for Dashboard, Ctrl+K to search', 'info');
            localStorage.setItem('kbHintShown', '1');
        }, 3000);
    }

    // ============================================================
    // CONFIRM DIALOGS (data-confirm attribute)
    // ============================================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) { e.preventDefault(); e.stopPropagation(); }
        });
    });

    // ============================================================
    // PRINT BUTTONS
    // ============================================================
    document.querySelectorAll('[data-print-target]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-print-target');
            if (targetId) printElement(targetId);
        });
    });

    // ============================================================
    // ENTERPRISE FORM VALIDATION
    // ============================================================
    document.querySelectorAll('form').forEach(form => {
        if (form.hasAttribute('data-confirm')) return;
        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                // Scroll to first invalid field
                const invalid = this.querySelector(':invalid');
                if (invalid) {
                    invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    invalid.focus();
                }
            } else {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.hasAttribute('data-no-disable')) {
                    btn.disabled = true;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                    // Re-enable after 15s in case of network issues
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }, 15000);
                }
            }
            this.classList.add('was-validated');
        });

        // Positive numbers enforcement
        form.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function () {
                const min = parseFloat(this.getAttribute('min') ?? '0');
                if (this.hasAttribute('min') && parseFloat(this.value) < min) this.value = min;
                if ((this.classList.contains('qty') || this.classList.contains('price') || this.classList.contains('tax') || this.classList.contains('disc')) && parseFloat(this.value) < 0) {
                    this.value = 0;
                }
            });
        });
    });

    // ============================================================
    // ACCESSIBILITY IMPROVEMENTS
    // ============================================================
    document.querySelectorAll('.btn-icon, .btn').forEach(btn => {
        if (!btn.hasAttribute('aria-label') && !btn.hasAttribute('title') && btn.innerText.trim() === '') {
            if (btn.querySelector('.fa-eye')) btn.setAttribute('aria-label', 'View details');
            else if (btn.querySelector('.fa-edit, .fa-pen')) btn.setAttribute('aria-label', 'Edit record');
            else if (btn.querySelector('.fa-trash')) btn.setAttribute('aria-label', 'Delete record');
            else if (btn.querySelector('.fa-times, .btn-close')) btn.setAttribute('aria-label', 'Close');
            else if (btn.querySelector('.fa-plus')) btn.setAttribute('aria-label', 'Add item');
            else if (btn.querySelector('.fa-print')) btn.setAttribute('aria-label', 'Print');
            else if (btn.querySelector('.fa-download')) btn.setAttribute('aria-label', 'Download');
        }
        if (!btn.hasAttribute('type') && btn.tagName === 'BUTTON' && !btn.closest('form')) {
            btn.setAttribute('type', 'button');
        }
    });

    // ============================================================
    // SKELETON SCREEN LOADER
    // ============================================================
    // Remove skeleton placeholders when content loads
    document.querySelectorAll('.skeleton-loader').forEach(el => {
        el.classList.remove('skeleton-loader');
    });

    // ============================================================
    // TABLE ROW HOVER HIGHLIGHT
    // ============================================================
    document.querySelectorAll('.table-hover tbody tr').forEach(row => {
        row.addEventListener('click', function (e) {
            // Don't trigger on action buttons
            if (e.target.closest('.btn, a, input, select, button')) return;
            const link = this.querySelector('a:not(.btn)');
            if (link) link.click();
        });
        row.style.cursor = 'pointer';
    });

    // ============================================================
    // AUTO-DISMISS FLASH MESSAGES
    // ============================================================
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // ============================================================
    // COPY TO CLIPBOARD BUTTONS
    // ============================================================
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', function () {
            const text = this.getAttribute('data-copy');
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copied to clipboard!', 'success');
            }).catch(() => {
                // Fallback
                const ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                showToast('Copied!', 'success');
            });
        });
    });

    // ============================================================
    // MOBILE: FULL-WIDTH PRIMARY BUTTONS
    // ============================================================
    document.querySelectorAll('.card-footer button[type="submit"], .modal-footer button, .btn-primary').forEach(btn => {
        if (!btn.classList.contains('btn-sm') && !btn.classList.contains('btn-icon')) {
            btn.classList.add('btn-mobile-full');
        }
    });

    // ============================================================
    // ACTIVE NAV LINK HIGHLIGHT (URL-based)
    // ============================================================
    const currentPage = new URLSearchParams(window.location.search).get('page');
    if (currentPage) {
        document.querySelectorAll('.nav-link').forEach(link => {
            const href = link.getAttribute('href') || '';
            if (href.includes('page=' + currentPage)) {
                link.classList.add('active');
            }
        });
    }

    // ============================================================
    // NUMBER INPUT: Select all on focus
    // ============================================================
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('focus', function () { this.select(); });
    });

    // ============================================================
    // TOOLTIP INITIALIZATION
    // ============================================================
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"], [title]:not([data-bs-toggle])').forEach(el => {
            try { new bootstrap.Tooltip(el, { trigger: 'hover', delay: { show: 200, hide: 0 } }); }
            catch (err) { /* skip */ }
        });
    }

    // ============================================================
    // POPOVER INITIALIZATION
    // ============================================================
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
            try { new bootstrap.Popover(el); } catch (err) { /* skip */ }
        });
    }

    // ============================================================
    // PAGE LOAD PERFORMANCE INDICATOR
    // ============================================================
    const loadTime = performance.now();
    if (loadTime > 2000) {
        console.warn('Slow page load:', Math.round(loadTime) + 'ms');
    }

});

// ============================================================
// GLOBAL CONSTANTS
// ============================================================
const APP_URL = document.querySelector('link[href*="style"]')?.href.split('/assets/')[0] || '';

// ============================================================
// CSRF TOKEN
// ============================================================
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
    const input = document.querySelector('input[name="_csrf_token"], input[name="csrf_token"]');
    return input ? input.value : '';
}

// ============================================================
// PREMIUM TOAST NOTIFICATION
// ============================================================
function showToast(message, type = 'success', duration = 4500) {
    const alertClass = type === 'error' ? 'danger' : type;
    const icons = { success: 'check-circle', danger: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    const colors = { success: '#198754', danger: '#dc3545', warning: '#fd7e14', info: '#0dcaf0' };
    const iconName = icons[alertClass] || 'info-circle';
    const color = colors[alertClass] || '#0d6efd';

    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.setAttribute('aria-live', 'polite');
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;max-width:380px;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
        background:#fff; color:#212529; border-radius:12px;
        box-shadow:0 8px 30px rgba(0,0,0,.15); padding:14px 16px;
        border-left:4px solid ${color}; display:flex; align-items:flex-start; gap:12px;
        transform:translateX(120%); transition:transform .35s cubic-bezier(.175,.885,.32,1.1);
        position:relative; overflow:hidden;
    `;

    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    if (dark) toast.style.background = '#1e293b'; toast.style.color = '#f1f5f9';

    toast.innerHTML = `
        <i class="fas fa-${iconName}" style="color:${color};margin-top:2px;font-size:16px;flex-shrink:0;"></i>
        <div style="flex:1;font-size:14px;line-height:1.5;">${String(message ?? '')}</div>
        <button type="button" onclick="this.closest('[role]').remove()" style="background:none;border:none;cursor:pointer;color:#6c757d;font-size:16px;padding:0;line-height:1;" aria-label="Close">×</button>
        <div class="toast-progress" style="position:absolute;bottom:0;left:0;height:3px;background:${color};width:100%;transition:width ${duration}ms linear;border-radius:0 0 12px 12px;"></div>
    `;
    toast.setAttribute('role', 'alert');
    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
    });

    // Start progress bar
    requestAnimationFrame(() => {
        const bar = toast.querySelector('.toast-progress');
        if (bar) requestAnimationFrame(() => { bar.style.width = '0'; });
    });

    // Auto dismiss
    const timer = setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, duration);

    toast.addEventListener('mouseenter', () => clearTimeout(timer));
    return toast;
}

// ============================================================
// LOADING OVERLAY
// ============================================================
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('show');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('show');
}

// ============================================================
// SKELETON SCREEN
// ============================================================
function showSkeleton(container, rows = 3) {
    if (!container) return;
    const html = Array.from({ length: rows }, () =>
        `<div style="background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:8px;height:18px;margin-bottom:12px;"></div>`
    ).join('');
    container.innerHTML = `<style>@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>${html}`;
}

// ============================================================
// FORMATTING UTILITIES
// ============================================================
function formatCurrency(amount, symbol = '₹') {
    return symbol + ' ' + parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatNumber(num, decimals = 2) {
    return parseFloat(num || 0).toFixed(decimals);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ============================================================
// DEBOUNCE / THROTTLE
// ============================================================
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function throttle(func, limit = 100) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) { func.apply(this, args); inThrottle = true; setTimeout(() => inThrottle = false, limit); }
    };
}

// ============================================================
// AJAX HELPER (with CSRF)
// ============================================================
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken(),
        },
    };
    const merged = { ...defaults, ...options };
    if (merged.headers) merged.headers = { ...defaults.headers, ...options.headers };
    return fetch(url, merged).then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        return res.json();
    });
}

// ============================================================
// PRINT UTILITY
// ============================================================
function printElement(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head>
        <title>Print</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="${APP_URL}/assets/css/style.css" rel="stylesheet">
        <style>body{background:#fff!important;color:#333!important;padding:0;margin:0;}@media print{body{padding:0;}}</style>
        </head><body>${el.innerHTML}</body></html>`);
    win.document.close();
    setTimeout(() => { win.print(); }, 600);
}

// ============================================================
// GLOBAL SEARCH (Ctrl+K opens modal/bar)
// ============================================================
function openGlobalSearch() {
    const modal = document.getElementById('globalSearchModal');
    if (!modal) return;
    // Use Bootstrap modal if available (loaded from CDN)
    if (typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.show();
        setTimeout(() => { 
            const inp = modal.querySelector('#globalSearchInput'); 
            if (inp) { inp.focus(); inp.value = ''; inp.dispatchEvent(new Event('input')); }
        }, 200);
    } else {
        // Fallback: show manually
        modal.style.display = 'flex';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        const inp = modal.querySelector('#globalSearchInput');
        if (inp) inp.focus();
    }
}

// ============================================================
// FEATURE DETECTION HELPERS
// ============================================================
const supportsClipboard = !!navigator.clipboard;
const supportsTouchEvents = ('ontouchstart' in window);
