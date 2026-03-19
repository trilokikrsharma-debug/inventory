/**
 * InvenBill Pro - Main Application JavaScript
 * 
 * Handles sidebar, theme toggle, common interactions,
 * and utility functions used across all pages.
 */

// ============================================================
// SIDEBAR FUNCTIONALITY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const topNavbar = document.getElementById('topNavbar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Sidebar toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                // Desktop: collapse sidebar
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
                topNavbar.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            } else {
                // Mobile: show/hide sidebar
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    // Close sidebar on overlay click (mobile)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Restore sidebar state
    if (window.innerWidth >= 992 && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
        topNavbar.classList.add('sidebar-collapsed');
    }

    // ============================================================
    // THEME TOGGLE (Dark/Light Mode)
    // ============================================================
    const themeSwitch = document.getElementById('themeSwitch');
    if (themeSwitch) {
        themeSwitch.addEventListener('change', function () {
            const mode = this.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', mode);

            const csrfToken = getCsrfToken();
            const payload = new URLSearchParams();
            payload.set('theme_mode', mode);
            if (csrfToken) {
                payload.set('_csrf_token', csrfToken);
            }

            // Save to server
            fetch(APP_URL + '/index.php?page=profile&action=updateTheme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: payload.toString(),
                credentials: 'same-origin'
            });

            localStorage.setItem('theme', mode);
        });

        // Restore theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            themeSwitch.checked = savedTheme === 'dark';
        }
    }

    // ============================================================
    // FULLSCREEN TOGGLE
    // ============================================================
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                this.querySelector('i').classList.replace('fa-expand', 'fa-compress');
            } else {
                document.exitFullscreen();
                this.querySelector('i').classList.replace('fa-compress', 'fa-expand');
            }
        });
    }

    // ============================================================
    // AUTO-DISMISS FLASH MESSAGES
    // ============================================================
    const flashContainer = document.getElementById('flashContainer');
    if (flashContainer) {
        setTimeout(() => {
            flashContainer.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 4000);
    }

    // ============================================================
    // CONFIRM DIALOGS (SweetAlert2 with native fallback)
    // ============================================================
    // Confirm for anchor tags (GET)
    document.querySelectorAll('a[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (typeof Swal !== 'undefined') {
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, proceed'
                }).then(result => {
                    if (result.isConfirmed) window.location.href = this.href;
                });
            } else if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Confirm for POST forms (delete, convert, etc.)
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            const isConvert = msg.toLowerCase().includes('convert');

            if (typeof Swal !== 'undefined') {
                e.preventDefault();
                Swal.fire({
                    title: isConvert ? 'Convert to Sale?' : 'Are you sure?',
                    text: msg,
                    icon: isConvert ? 'warning' : 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isConvert ? '#198754' : '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: isConvert ? '<i class="fas fa-check me-1"></i> Yes, convert' : 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    focusCancel: true
                }).then(result => {
                    if (result.isConfirmed) {
                        // Disable button + show spinner to prevent double submit
                        const btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.disabled = true;
                            const origHTML = btn.innerHTML;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (btn.textContent.trim() ? 'Processing...' : '');
                        }
                        form.submit();
                    }
                });
            } else if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // TOOLTIP INITIALIZATION
    // ============================================================
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // ============================================================
    // TABLE ROW CLICK (if data-href attribute exists)
    // ============================================================
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON' && !e.target.closest('.action-btns')) {
                window.location.href = this.dataset.href;
            }
        });
    });

    // ============================================================
    // PRINT BUTTONS (CSP-safe data attributes)
    // ============================================================
    document.querySelectorAll('[data-print-target]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-print-target');
            if (targetId) {
                printElement(targetId);
            }
        });
    });

    // ============================================================
    // ENTERPRISE FORM VALIDATION
    // ============================================================
    document.querySelectorAll('form').forEach(form => {
        // Skip forms with data-confirm — they have their own handler above
        if (form.hasAttribute('data-confirm')) return;
        // Prevent double submit
        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                }
            }
            this.classList.add('was-validated');
        });

        // Block negative numbers on inputs
        form.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function () {
                if (this.hasAttribute('min') && parseFloat(this.value) < parseFloat(this.getAttribute('min'))) {
                    this.value = this.getAttribute('min');
                }
                // Specifically for financial/quantity bounds if min not set explicitly
                if (this.classList.contains('qty') || this.classList.contains('price') || this.classList.contains('tax') || this.classList.contains('disc')) {
                    if (parseFloat(this.value) < 0) this.value = 0;
                }
            });
        });
    });

    // ============================================================
    // ACCESSIBILITY & MOBILE UI TWEAKS
    // ============================================================
    document.querySelectorAll('.btn-icon, .btn:not(:empty)').forEach(btn => {
        if (!btn.hasAttribute('aria-label') && !btn.hasAttribute('title') && btn.innerText.trim() === '') {
            if (btn.querySelector('.fa-eye')) btn.setAttribute('aria-label', 'View details');
            else if (btn.querySelector('.fa-edit')) btn.setAttribute('aria-label', 'Edit record');
            else if (btn.querySelector('.fa-trash')) btn.setAttribute('aria-label', 'Delete record');
            else if (btn.querySelector('.fa-times') || btn.classList.contains('btn-close')) btn.setAttribute('aria-label', 'Close dialog');
            else if (btn.querySelector('.fa-plus')) btn.setAttribute('aria-label', 'Add item');
        }
        if (!btn.hasAttribute('type') && btn.tagName === 'BUTTON' && !btn.closest('form')) {
            btn.setAttribute('type', 'button');
        }
    });

    // Make main action buttons full-width when on small screens dynamically
    document.querySelectorAll('.card-footer button[type="submit"], .modal-footer button, .btn-primary').forEach(btn => {
        if (!btn.classList.contains('btn-sm') && !btn.classList.contains('btn-icon')) {
            btn.classList.add('btn-mobile-full');
        }
    });

});

// ============================================================
// GLOBAL VARIABLES & CONSTANTS
// ============================================================
const APP_URL = document.querySelector('link[href*="style.css"]')?.href.split('/assets/')[0] || '';

/**
 * Resolve the CSRF token from the injected meta tag or a hidden form field.
 */
function getCsrfToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.getAttribute('content')) {
        return metaToken.getAttribute('content');
    }

    const inputToken = document.querySelector('input[name="_csrf_token"], input[name="csrf_token"]');
    return inputToken ? inputToken.value : '';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Format number as currency
 */
function formatCurrency(amount, symbol = '₹') {
    return symbol + ' ' + parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format number
 */
function formatNumber(num, decimals = 2) {
    return parseFloat(num || 0).toFixed(decimals);
}

/**
 * Show loading overlay
 */
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const alertClass = type === 'error' ? 'danger' : type;
    const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };

    let container = document.getElementById('flashContainer');
    if (!container) {
        container = document.createElement('div');
        container.className = 'alert-container';
        container.id = 'flashContainer';
        document.body.appendChild(container);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${alertClass} alert-dismissible fade show`;
    const icon = document.createElement('i');
    icon.className = `fas fa-${icons[type] || 'info-circle'} me-2`;
    alert.appendChild(icon);
    alert.appendChild(document.createTextNode(String(message ?? '')));

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close btn-close-sm';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    closeBtn.setAttribute('aria-label', 'Close');
    alert.appendChild(closeBtn);
    container.appendChild(alert);

    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s, transform 0.5s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(100%)';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
}

/**
 * Debounce function
 */
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * AJAX helper
 */
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    };
    return fetch(url, { ...defaults, ...options })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        });
}

/**
 * Print a specific element
 */
function printElement(elementId) {
    const printContent = document.getElementById(elementId);
    if (!printContent) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="${APP_URL}/assets/css/style.css" rel="stylesheet">
            <style>
                body { background: #fff !important; color: #333 !important; padding: 0; margin: 0; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>${printContent.innerHTML}</body>
        </html>
    `);
    printWindow.document.close();
    setTimeout(() => { printWindow.print(); }, 500);
}
