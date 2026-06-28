/**
 * Sistema de Control Tributario Municipal
 * JavaScript Global
 */

document.addEventListener('DOMContentLoaded', () => {
    initNotifications();
    initModals();
    initFlashMessages();
    initMobileMenu();
    initServiceWorker();
});

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

// ─── Utilidad: Confirmar acción ───
function confirmAction(message) {
    return confirm(message);
}
