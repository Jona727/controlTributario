/**
 * Sistema de Control Tributario Municipal
 * JavaScript Global
 */

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initNotifications();
    initModals();
    initConfirmForms();
    initFlashMessages();
    initMobileMenu();
    initServiceWorker();
    initInstallPrompt();
});

// ─── Tema claro / oscuro ───
function initThemeToggle() {
    const STORAGE_KEY = 'ct-theme';
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    const apply = (theme) => {
        if (theme === 'light' || theme === 'dark') {
            document.documentElement.setAttribute('data-theme', theme);
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    };

    toggle.addEventListener('click', () => {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const current = document.documentElement.getAttribute('data-theme') || (prefersDark ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';
        apply(next);
        try { localStorage.setItem(STORAGE_KEY, next); } catch (e) { /* ignore */ }
    });
}

// ─── Confirmación de acciones destructivas (reemplaza confirm() nativo) ───
function initConfirmForms() {
    const modal = document.getElementById('modal-confirm-generic');
    if (!modal) return;

    const titleEl = modal.querySelector('#confirm-generic-title');
    const msgEl = modal.querySelector('#confirm-generic-message');
    const confirmBtn = modal.querySelector('#confirm-generic-btn');
    let pendingForm = null;

    document.querySelectorAll('form[data-confirm-submit]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            pendingForm = form;
            titleEl.textContent = form.dataset.confirmTitle || 'Confirmar acción';
            msgEl.textContent = form.dataset.confirmMessage || '¿Confirmás esta acción?';
            confirmBtn.textContent = form.dataset.confirmLabel || 'Confirmar';
            modal.classList.add('active');
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (!pendingForm) return;
            modal.classList.remove('active');
            pendingForm.dataset.confirmed = '1';
            if (pendingForm.requestSubmit) {
                pendingForm.requestSubmit();
            } else {
                pendingForm.submit();
            }
            pendingForm = null;
        });
    }
}

// ─── Notificaciones ───
function initNotifications() {
    const bellBtn = document.getElementById('notif-bell');
    const panel   = document.getElementById('notif-panel');

    if (!bellBtn || !panel) return;

    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && e.target !== bellBtn) {
            panel.classList.remove('active');
        }
    });

    const metaBase = document.querySelector('meta[name="app-base-path"]');
    const basePath = metaBase ? metaBase.content : '/tasas_municipales/public';
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaCsrf ? metaCsrf.content : '';

    const updateBadge = () => {
        const remaining = panel.querySelectorAll('.notif-item.unread').length;
        const badge = bellBtn.querySelector('.badge');
        if (remaining === 0 && badge) {
            badge.remove();
        } else if (badge) {
            badge.textContent = remaining;
        }
    };

    panel.querySelectorAll('.notif-item[data-notif-id]').forEach(item => {
        item.addEventListener('click', () => {
            if (item.dataset.read === '1') return;
            fetch(`${basePath}/notificaciones/leer/${item.dataset.notifId}`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => {
                item.classList.remove('unread');
                item.dataset.read = '1';
                updateBadge();
            }).catch(() => {});
        });
    });

    const btnMarcarTodas = document.getElementById('btn-marcar-todas-leidas');
    if (btnMarcarTodas) {
        btnMarcarTodas.addEventListener('click', () => {
            fetch(`${basePath}/notificaciones/leer-todas`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => {
                panel.querySelectorAll('.notif-item.unread').forEach(item => {
                    item.classList.remove('unread');
                    item.dataset.read = '1';
                });
                updateBadge();
                btnMarcarTodas.style.display = 'none';
            }).catch(() => {});
        });
    }
}

// ─── Modales ───
function initModals() {
    // Abrir modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalOpen);
            if (target) target.classList.add('active');
        });
    });

    // Cerrar modal
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.closest('.modal-overlay');
            if (target) target.classList.remove('active');
        });
    });

    // Cerrar al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
}

// ─── Flash Messages (auto-hide) ───
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            setTimeout(() => msg.remove(), 300);
        }, 4000);
    });
}

// ─── Mobile Menu Toggle ───
function initMobileMenu() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024 && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            e.target !== toggle && 
            !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
}

// ─── PWA: banner de instalación automático ───
function initInstallPrompt() {
    // Si ya está instalada (abierta como app), no mostrar nada
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        return;
    }
    // Si el usuario ya la descartó en esta sesión del navegador, no insistir
    if (sessionStorage.getItem('pwa-install-dismissed') === '1') {
        return;
    }

    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallBanner();
    });

    window.addEventListener('appinstalled', () => {
        hideInstallBanner();
    });

    function showInstallBanner() {
        if (document.getElementById('pwa-install-banner')) return;

        const basePath = document.querySelector('meta[name="app-base-path"]')?.content || '';
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:9999;'
            + 'display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;'
            + 'padding-bottom:calc(0.75rem + env(safe-area-inset-bottom, 0px));'
            + 'background:var(--gray-900, #1b2129);color:#fff;'
            + 'box-shadow:0 -2px 12px rgba(0,0,0,0.25);';
        banner.innerHTML = `
            <img src="${basePath}/assets/images/icon-192.png" alt="" style="width:36px;height:36px;border-radius:8px;flex-shrink:0;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:0.88rem;">Instalar Control Tributario</div>
                <div style="font-size:0.76rem;opacity:0.8;">Accedé más rápido desde el ícono en tu pantalla de inicio</div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="pwa-install-btn">Instalar</button>
            <button type="button" class="icon-btn" id="pwa-install-dismiss" title="Cerrar" style="color:#fff;background:transparent;border:none;flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;
        document.body.appendChild(banner);

        document.getElementById('pwa-install-btn').addEventListener('click', async () => {
            hideInstallBanner();
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
        });

        document.getElementById('pwa-install-dismiss').addEventListener('click', () => {
            sessionStorage.setItem('pwa-install-dismissed', '1');
            hideInstallBanner();
        });
    }

    function hideInstallBanner() {
        document.getElementById('pwa-install-banner')?.remove();
    }
}

// ─── PWA Service Worker ───
function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            const metaTag = document.querySelector('meta[name="app-base-path"]');
            const basePath = metaTag ? metaTag.content : '/tasas_municipales/public';
            
            navigator.serviceWorker.register(`${basePath}/sw.js`)
                .then((reg) => {
                    console.log('[PWA] Service Worker registrado con éxito. Scope:', reg.scope);
                })
                .catch((err) => {
                    console.error('[PWA] Error al registrar el Service Worker:', err);
                });
        });
    }
}

// ─── Utilidad: Formatear moneda ───
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(amount);
}

