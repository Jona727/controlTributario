<?php
$pageTitle  = 'Usuarios Administradores';
$activePage = 'usuarios';
require __DIR__ . '/layout_header.php';
?>

<div class="alert-info" style="margin-bottom: 1.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    Estas son las cuentas del personal municipal (no comercios). Solo un "Super Administrador" puede crear o desactivar otras cuentas acá.
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:1.5rem;">
    <button class="btn btn-primary" data-modal-open="modal-crear-usuario">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Usuario
    </button>
</div>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th>Código</th><th>Nombre</th><th>CUIT/DNI</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($usuarios)): ?>
    <tr><td colspan="7" class="empty-state"><p>Sin usuarios</p></td></tr>
<?php else: foreach ($usuarios as $u): ?>
    <tr>
        <td style="font-weight:600;color:var(--primary-600);"><?= htmlspecialchars($u['client_code']) ?></td>
        <td style="font-weight:600;"><?= htmlspecialchars($u['business_name']) ?></td>
        <td><?= htmlspecialchars($u['cuit']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
            <span class="status-badge <?= $u['role_name'] === 'super' ? 'status-paid' : 'status-pending' ?>">
                <span class="status-dot"></span><?= $u['role_name'] === 'super' ? 'Super Admin' : 'Admin' ?>
            </span>
        </td>
        <td><?php if ($u['is_active']): ?><span class="status-badge status-paid"><span class="status-dot"></span>Activo</span><?php else: ?><span class="status-badge status-cancelled">Inactivo</span><?php endif; ?></td>
        <td>
            <?php if ((int)$u['id'] !== (int)$userId): ?>
                <form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/usuarios/eliminar/<?= $u['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Desactivar a <?= htmlspecialchars(addslashes($u['business_name'])) ?>?')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <button type="submit" class="icon-btn" title="Desactivar" style="color:var(--danger);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <span style="font-size:0.72rem;color:var(--slate-medium);">(vos)</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>

<!-- Modal Crear Usuario -->
<div class="modal-overlay" id="modal-crear-usuario"><div class="modal">
<div class="modal-header"><h3>Nuevo Usuario Administrador</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/usuarios/crear">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label class="form-label">Código *</label><input type="text" name="client_code" class="form-input" placeholder="EMP-001" required></div>
        <div class="form-group"><label class="form-label">CUIT/DNI *</label><input type="text" name="cuit" class="form-input" required></div>
    </div>
    <div class="form-group"><label class="form-label">Nombre Completo *</label><input type="text" name="business_name" class="form-input" required></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-input"></div>
        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-input" required></div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Rol *</label>
            <select name="role_id" class="form-select" required>
                <option value="2">Administrador</option>
                <option value="1">Super Administrador</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Contraseña *</label>
            <div style="position:relative;">
                <input type="password" id="create-user-password" name="password" class="form-input" required style="padding-right:2.5rem;">
                <button type="button" class="toggle-password" data-target="create-user-password" tabindex="-1" style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--slate-medium); cursor:pointer; padding:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passInput = document.getElementById(targetId);
            if (passInput) {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
            }
        });
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
