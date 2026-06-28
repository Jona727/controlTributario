<?php
$pageTitle  = 'Gestión de Comercios';
$activePage = 'comercios';
require __DIR__ . '/layout_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <p style="font-size:0.85rem; color:var(--gray-500);"><?= count($comercios) ?> comercio(s)</p>
    <div style="display:flex; gap:0.5rem;">
        <button class="btn btn-secondary" data-modal-open="modal-importar-csv">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Importar CSV
        </button>
        <button class="btn btn-primary" data-modal-open="modal-crear-comercio">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo Comercio
        </button>
    </div>
</div>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th>Código</th><th>Razón Social</th><th>CUIT</th><th>Domicilio</th><th>Email</th><th>Tasa Base</th><th>Deuda</th><th>Estado</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($comercios)): ?>
    <tr><td colspan="8" class="empty-state"><p>Sin comercios</p></td></tr>
<?php else: foreach ($comercios as $c): ?>
    <tr>
        <td style="font-weight:600;color:var(--primary-600);"><?= htmlspecialchars($c['client_code']) ?></td>
        <td style="font-weight:600;"><?= htmlspecialchars($c['business_name']) ?></td>
        <td><?= htmlspecialchars($c['cuit']) ?></td>
        <td><?= htmlspecialchars($c['address']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td style="font-weight:600;">$ <?= number_format((float)$c['base_rate'], 2, ',', '.') ?></td>
        <td style="font-weight:600;color:<?= $c['deuda_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">$ <?= number_format((float)$c['deuda_pendiente'],2,',','.') ?></td>
        <td><?php if($c['is_active']): ?><span class="status-badge status-paid"><span class="status-dot"></span>Activo</span><?php else: ?><span class="status-badge status-cancelled">Inactivo</span><?php endif; ?></td>
        <td>
            <button class="btn btn-ghost btn-sm" onclick='openEditModal(<?= json_encode($c) ?>)'>Editar</button>
            <form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios/eliminar/<?= $c['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Desactivar este comercio?')">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Eliminar</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>

<!-- Modal Importar CSV -->
<div class="modal-overlay" id="modal-importar-csv"><div class="modal">
<div class="modal-header"><h3>Importar Comercios desde CSV</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios/importar" enctype="multipart/form-data" onsubmit="iniciarImportacion(event, this)">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="alert-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        El archivo CSV debe incluir las cabeceras: <strong>codigo; razon_social; cuit; domicilio; telefono; email; tasa_base</strong>.<br>
        Se usará el CUIT del comercio (sin guiones) como contraseña de acceso por defecto.
    </div>
    <div class="form-group">
        <label class="form-label">Seleccionar Archivo CSV *</label>
        <input type="file" name="csv_file" class="form-input" accept=".csv" required style="padding: 0.5rem;">
    </div>
    <div style="font-size: 0.75rem; background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 0.75rem; border-radius: 4px;">
        <strong>Ejemplo de formato CSV:</strong><br>
        <code style="font-size: 0.7rem; color: #1d4ed8;">codigo;razon_social;cuit;domicilio;telefono;email;tasa_base</code><br>
        <code style="font-size: 0.7rem;">COM-010;Tienda Ejemplo;20-98765432-1;Av. Ejemplo 123;0343-412345;tienda@email.com;4500.00</code>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-primary">
        Iniciar Importación
    </button>
</div>
</form></div></div>

<!-- Modal Crear -->
<div class="modal-overlay" id="modal-crear-comercio"><div class="modal">
<div class="modal-header"><h3>Nuevo Comercio</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios/crear">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label class="form-label">Código *</label><input type="text" name="client_code" class="form-input" required></div><div class="form-group"><label class="form-label">CUIT *</label><input type="text" name="cuit" class="form-input" required></div></div>
    <div class="form-group"><label class="form-label">Razón Social *</label><input type="text" name="business_name" class="form-input" required></div>
    <div class="form-group"><label class="form-label">Domicilio *</label><input type="text" name="address" class="form-input" required></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-input"></div><div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-input" required></div></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Tasa Base Fija ($) *</label><input type="number" name="base_rate" class="form-input" step="0.01" value="0.00" required></div><div class="form-group"><label class="form-label">Contraseña *</label><input type="password" name="password" class="form-input" required></div></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
</form></div></div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-editar-comercio"><div class="modal">
<div class="modal-header"><h3>Editar Comercio</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" id="form-editar-comercio">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label class="form-label">Código *</label><input type="text" name="client_code" id="edit-client-code" class="form-input" required></div><div class="form-group"><label class="form-label">CUIT *</label><input type="text" name="cuit" id="edit-cuit" class="form-input" required></div></div>
    <div class="form-group"><label class="form-label">Razón Social *</label><input type="text" name="business_name" id="edit-business-name" class="form-input" required></div>
    <div class="form-group"><label class="form-label">Domicilio *</label><input type="text" name="address" id="edit-address" class="form-input" required></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Teléfono</label><input type="text" name="phone" id="edit-phone" class="form-input"></div><div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" id="edit-email" class="form-input" required></div></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Tasa Base Fija ($) *</label><input type="number" name="base_rate" id="edit-base-rate" class="form-input" step="0.01" required></div><div class="form-group"><label class="form-label">Nueva Contraseña</label><input type="password" name="password" class="form-input"></div></div>
    <label style="display:flex;align-items:center;gap:0.5rem;"><input type="checkbox" name="is_active" id="edit-is-active" value="1"> Activo</label>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>

<script>
function openEditModal(d) {
    document.getElementById('form-editar-comercio').action = '<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios/editar/' + d.id;
    document.getElementById('edit-client-code').value = d.client_code;
    document.getElementById('edit-business-name').value = d.business_name;
    document.getElementById('edit-cuit').value = d.cuit;
    document.getElementById('edit-address').value = d.address;
    document.getElementById('edit-phone').value = d.phone || '';
    document.getElementById('edit-email').value = d.email;
    document.getElementById('edit-base-rate').value = d.base_rate;
    document.getElementById('edit-is-active').checked = d.is_active == 1;
    document.getElementById('modal-editar-comercio').classList.add('active');
}

function iniciarImportacion(e, form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Importando padrón...';
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
