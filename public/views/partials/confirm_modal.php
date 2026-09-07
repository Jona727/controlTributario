<?php
/**
 * Modal de confirmación genérico. Los formularios que agreguen el atributo
 * data-confirm-submit (más data-confirm-title / data-confirm-message /
 * data-confirm-label) abren este modal en vez de disparar el confirm()
 * nativo del navegador. La lógica de apertura vive en app.js (initConfirmForms).
 */
?>
<div class="modal-overlay" id="modal-confirm-generic"><div class="modal modal-sm">
<div class="modal-header"><h3 id="confirm-generic-title">Confirmar acción</h3><button class="modal-close" type="button" data-modal-close>&times;</button></div>
<div class="modal-body">
    <div class="alert-warning" style="margin-bottom:0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span id="confirm-generic-message">¿Confirmás esta acción?</span>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="button" class="btn btn-danger" id="confirm-generic-btn">Confirmar</button>
</div>
</div></div>
