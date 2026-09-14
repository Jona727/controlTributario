/**
 * Sistema de Toasts — notificaciones flotantes no bloqueantes.
 * Uso: showToast('Mensaje', 'success' | 'error' | 'warning' | 'info')
 */
(function () {
    const ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>'
    };

    function getContainer() {
        let el = document.getElementById('toast-container');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toast-container';
            document.body.appendChild(el);
        }
        return el;
    }

    window.showToast = function (message, type = 'info', duration = 5000) {
        const container = getContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${ICONS[type] || ICONS.info}</span>
            <span class="toast-body">${message}</span>
            <button type="button" class="toast-close" aria-label="Cerrar">&times;</button>
        `;

        const remove = () => {
            if (!toast.isConnected) return;
            toast.classList.add('toast-leaving');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        };

        toast.querySelector('.toast-close').addEventListener('click', remove);
        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(remove, duration);
        }

        return { close: remove };
    };
})();
