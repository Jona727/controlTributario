<!-- Modal Cambiar Mi Contraseña (compartido entre admin y comercio) -->
<div class="modal-overlay" id="modal-cambiar-password"><div class="modal">
<div class="modal-header"><h3>Cambiar mi Contraseña</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/perfil/password">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-group">
        <label class="form-label">Contraseña Actual *</label>
        <input type="password" name="current_password" class="form-input" required>
    </div>
    <div class="form-group">
        <label class="form-label">Nueva Contraseña *</label>
        <input type="password" name="new_password" class="form-input" minlength="6" required>
        <span style="font-size:0.7rem;color:var(--slate-medium);display:block;margin-top:0.25rem;">Mínimo 6 caracteres.</span>
    </div>
    <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Confirmar Nueva Contraseña *</label>
        <input type="password" name="new_password_confirm" class="form-input" minlength="6" required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-primary">Guardar</button>
</div>
</form></div></div>
