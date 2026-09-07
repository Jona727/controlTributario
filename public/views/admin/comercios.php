<?php
$pageTitle  = 'Gestión de Comercios';
$activePage = 'comercios';
require __DIR__ . '/layout_header.php';
?>

<?php if (!empty($_SESSION['flash_credentials'])): ?>
    <div class="card" style="border: 2px solid var(--brand-secondary); margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3>Contraseñas temporales generadas</h3>
        </div>
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div class="alert-info" style="margin-bottom: 1rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Estas contraseñas solo se muestran una vez. Copiálas o entregáselas a cada comercio antes de salir de esta pantalla — al recargar, desaparecen.
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Razón Social</th><th>Contraseña Temporal</th></tr></thead>
                    <tbody>
                        <?php foreach ($_SESSION['flash_credentials'] as $cred): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($cred['code']) ?></td>
                                <td><?= htmlspecialchars($cred['name']) ?></td>
                                <td style="font-family:monospace;font-weight:700;color:var(--brand-primary);"><?= htmlspecialchars($cred['password']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['flash_credentials']); ?>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="stat-label">Comercios Activos</div>
        <div class="stat-value"><?= (int) $comerciosStats['activos'] ?></div>
    </div>
    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Con Deuda Pendiente</div>
        <div class="stat-value"><?= (int) $comerciosStats['con_deuda'] ?></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-label">Datos por Validar</div>
        <div class="stat-value"><?= (int) $comerciosStats['por_validar'] ?></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Deuda Total (todos)</div>
        <div class="stat-value">$ <?= number_format((float) $comerciosStats['deuda_total'], 0, ',', '.') ?></div>
    </div>
</div>

<?php $comerciosRevision = array_filter($comercios, fn($c) => !empty($c['needs_data_review'])); ?>
<?php if (!empty($comerciosRevision)): ?>
    <div class="alert-warning" style="align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span><strong><?= count($comerciosRevision) ?> comercio(s)</strong> tienen datos provisorios (importados sin email real y/o con CUIT incompleto) — hay que pedirles que los validen.</span>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="btn-filtrar-revision">Ver solo estos</button>
    </div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.5rem;">
    <p style="font-size:0.85rem; color:var(--gray-500);"><?= count($comercios) ?> comercio(s)</p>
    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
        <div style="position:relative; flex:1 1 200px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:var(--slate-medium);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" class="form-input" placeholder="Buscar comercio..." style="padding-left: 2.25rem; width: 100%; min-width: 160px; font-size: 0.85rem;">
        </div>
        <button class="btn btn-secondary" data-modal-open="modal-importar-csv">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Importar CSV
        </button>
        <button class="btn btn-primary" data-modal-open="modal-crear-comercio">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo
        </button>
    </div>
</div>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th>Comercio</th><th class="col-secondary">CUIT</th><th class="col-secondary">Domicilio</th><th class="col-secondary">Email</th><th class="col-secondary">Tasa Base</th><th>Deuda</th><th>Estado</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($comercios)): ?>
    <tr><td colspan="8" class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        <p>Sin comercios</p>
    </td></tr>
<?php else: foreach ($comercios as $c): ?>
    <tr class="comercio-row" data-needs-review="<?= !empty($c['needs_data_review']) ? '1' : '0' ?>">
        <td>
            <div style="font-weight:600;"><?= htmlspecialchars($c['business_name']) ?></div>
            <div style="font-size:0.72rem;color:var(--primary-600);font-family:monospace;"><?= htmlspecialchars($c['client_code']) ?></div>
        </td>
        <td class="col-secondary"><?= htmlspecialchars($c['cuit']) ?></td>
        <td class="col-secondary"><?= htmlspecialchars($c['address']) ?></td>
        <td class="col-secondary"><?= htmlspecialchars($c['email']) ?></td>
        <td class="col-secondary" style="font-weight:600;">
            <?= (float) $c['base_rate'] > 0 ? '$ ' . number_format((float) $c['base_rate'], 2, ',', '.') : '<span style="color:var(--slate-medium);font-weight:400;">— (otra tasa)</span>' ?>
        </td>
        <td style="font-weight:600;color:<?= $c['deuda_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">$ <?= number_format((float)$c['deuda_pendiente'],2,',','.') ?></td>
        <td>
            <div style="margin-bottom:0.3rem;"><?php if($c['is_active']): ?><span class="status-badge status-paid"><span class="status-dot"></span>Activo</span><?php else: ?><span class="status-badge status-cancelled">Inactivo</span><?php endif; ?></div>
            <?php if (!empty($c['needs_data_review'])): ?>
                <span title="<?= htmlspecialchars($c['data_review_reason'] ?? 'Datos provisorios, requiere validación') ?>" style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.68rem;color:var(--warning);cursor:help;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Por validar
                </span>
            <?php endif; ?>
        </td>
        <td style="white-space: nowrap;">
            <button class="icon-btn" onclick='openEditModal(<?= json_encode($c) ?>)' title="Editar" style="color:var(--primary-600); margin-right: 0.25rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios/eliminar/<?= $c['id'] ?>" style="display:inline;"
                  data-confirm-submit
                  data-confirm-title="Desactivar comercio"
                  data-confirm-message="Vas a desactivar a «<?= htmlspecialchars($c['business_name']) ?>». No va a poder facturarse hasta que lo reactives. ¿Confirmás?"
                  data-confirm-label="Desactivar">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="submit" class="icon-btn" title="Eliminar/Desactivar" style="color:var(--danger);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </button>
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
        Columnas obligatorias: <strong>codigo; razon_social; cuit</strong>. El resto es opcional — <strong>email</strong> y <strong>cuit</strong> pueden venir vacíos o incompletos (por ejemplo, al migrar un padrón de otro sistema): el comercio se importa igual, con datos provisorios, y queda marcado para pedirle que los valide.<br>
        Se genera una contraseña temporal aleatoria por comercio.
    </div>
    <div class="form-group">
        <label class="form-label">Seleccionar Archivo CSV *</label>
        <input type="file" name="csv_file" class="form-input" accept=".csv" required style="padding: 0.5rem;">
    </div>
    <div style="font-size: 0.75rem; background-color: var(--slate-light); border: 1px solid var(--slate-border); padding: 0.75rem; border-radius: 4px;">
        <strong>Ejemplo de formato CSV:</strong><br>
        <code style="font-size: 0.7rem; color: var(--brand-primary);">codigo;razon_social;cuit;domicilio;telefono;email;tasa_base;titular;rubro;fecha_inicio;activo</code><br>
        <code style="font-size: 0.7rem;">COM-010;Tienda Ejemplo;20-98765432-1;Av. Ejemplo 123;0343-412345;tienda@email.com;4500.00;Juan Pérez;Kiosco;2020-07-01;1</code>
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
    <div class="form-row"><div class="form-group"><label class="form-label">Código *</label><input type="text" name="client_code" class="form-input" required></div><div class="form-group"><label class="form-label">CUIT *</label><input type="text" id="create-cuit" name="cuit" class="form-input cuit-mask" placeholder="20-12345678-9" required></div></div>
    <div class="form-group"><label class="form-label">Razón Social *</label><input type="text" name="business_name" class="form-input" required></div>
    <div class="form-group"><label class="form-label">Domicilio *</label><input type="text" name="address" class="form-input" required></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-input"></div><div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-input" required></div></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Tasa Base Fija ($) *</label><input type="number" name="base_rate" class="form-input" step="0.01" value="0.00" required></div>
        <div class="form-group">
            <label class="form-label">Contraseña *</label>
            <div style="position:relative;">
                <input type="password" id="create-password" name="password" class="form-input" required style="padding-right:2.5rem;">
                <button type="button" class="toggle-password" data-target="create-password" tabindex="-1" style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--slate-medium); cursor:pointer; padding:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Crear</button></div>
</form></div></div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-editar-comercio"><div class="modal">
<div class="modal-header"><h3>Editar Comercio</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" id="form-editar-comercio">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-row"><div class="form-group"><label class="form-label">Código *</label><input type="text" name="client_code" id="edit-client-code" class="form-input" required></div><div class="form-group"><label class="form-label">CUIT *</label><input type="text" name="cuit" id="edit-cuit" class="form-input cuit-mask" required></div></div>
    <div class="form-group"><label class="form-label">Razón Social *</label><input type="text" name="business_name" id="edit-business-name" class="form-input" required></div>
    <div class="form-group"><label class="form-label">Domicilio *</label><input type="text" name="address" id="edit-address" class="form-input" required></div>
    <div class="form-row"><div class="form-group"><label class="form-label">Teléfono</label><input type="text" name="phone" id="edit-phone" class="form-input"></div><div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" id="edit-email" class="form-input" required></div></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Tasa Base Fija ($) *</label><input type="number" name="base_rate" id="edit-base-rate" class="form-input" step="0.01" required></div>
        <div class="form-group">
            <label class="form-label">Nueva Contraseña</label>
            <div style="position:relative;">
                <input type="password" name="password" id="edit-password" class="form-input" placeholder="Dejar en blanco para no cambiar" style="padding-right:2.5rem;">
                <button type="button" class="toggle-password" data-target="edit-password" tabindex="-1" style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--slate-medium); cursor:pointer; padding:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
    </div>
    <div class="form-row"><div class="form-group"><label class="form-label">Titular</label><input type="text" name="owner_name" id="edit-owner-name" class="form-input" placeholder="Persona física dueña del comercio"></div><div class="form-group"><label class="form-label">Rubro</label><input type="text" name="activity_category" id="edit-activity-category" class="form-input"></div></div>
    <label style="display:flex;align-items:center;gap:0.5rem;"><input type="checkbox" name="is_active" id="edit-is-active" value="1"> Activo</label>
    <div id="edit-review-wrap" style="display:none; margin-top:0.75rem; padding:0.75rem; background:var(--alert-warning-bg); border:1px solid var(--alert-warning-border); border-radius:6px;">
        <p id="edit-review-reason" style="font-size:0.78rem; color:var(--alert-warning-text); margin:0 0 0.5rem;"></p>
        <label style="display:flex;align-items:center;gap:0.5rem; font-size:0.85rem;"><input type="checkbox" name="needs_data_review" id="edit-needs-review" value="1"> Sigue pendiente de validar sus datos</label>
    </div>
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
    document.getElementById('edit-owner-name').value = d.owner_name || '';
    document.getElementById('edit-activity-category').value = d.activity_category || '';

    const reviewWrap = document.getElementById('edit-review-wrap');
    if (d.needs_data_review == 1) {
        reviewWrap.style.display = 'block';
        document.getElementById('edit-review-reason').textContent = d.data_review_reason || 'Datos provisorios, requiere validación.';
        document.getElementById('edit-needs-review').checked = true;
    } else {
        reviewWrap.style.display = 'none';
        document.getElementById('edit-needs-review').checked = false;
    }

    document.getElementById('modal-editar-comercio').classList.add('active');
}

function iniciarImportacion(e, form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Importando padrón...';
}

document.addEventListener('DOMContentLoaded', () => {
    // Live Search
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.comercio-row');
    
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // Filtro "Ver solo estos" (comercios con datos pendientes de revisión)
    const btnFiltrarRevision = document.getElementById('btn-filtrar-revision');
    if (btnFiltrarRevision) {
        let soloRevision = false;
        btnFiltrarRevision.addEventListener('click', function() {
            soloRevision = !soloRevision;
            this.textContent = soloRevision ? 'Ver todos' : 'Ver solo estos';
            rows.forEach(row => {
                row.style.display = (!soloRevision || row.dataset.needsReview === '1') ? '' : 'none';
            });
        });
    }

    // CUIT Masks
    document.querySelectorAll('.cuit-mask').forEach(input => {
        input.addEventListener('input', function (e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            
            if (val.length > 2 && val.length <= 10) {
                val = val.substring(0, 2) + '-' + val.substring(2);
            } else if (val.length > 10) {
                val = val.substring(0, 2) + '-' + val.substring(2, 10) + '-' + val.substring(10);
            }
            this.value = val;
        });
    });

    // Toggle Passwords
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passInput = document.getElementById(targetId);
            if(passInput) {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                if (type === 'text') {
                    this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>';
                } else {
                    this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
                }
            }
        });
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
