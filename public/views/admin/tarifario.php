<?php
$pageTitle  = 'Tarifario por Rubro';
$activePage = 'tarifario';
require __DIR__ . '/layout_header.php';
?>

<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Códigos de Rubro</div>
        <div class="stat-value"><?= (int) $tarifarioStats['total_codigos'] ?></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="stat-label">Comercios con Rubro Asignado</div>
        <div class="stat-value"><?= (int) $tarifarioStats['comercios_con_rubro'] ?></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-label">Comercios sin Rubro Asignado</div>
        <div class="stat-value"><?= (int) $tarifarioStats['comercios_sin_rubro'] ?></div>
    </div>
</div>

<div class="alert-info" style="margin-bottom: 1.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span>Tarifario por rubro del Código Tributario Municipal (Título II, Art. 6°) — es la fuente de la que se toma la Tasa Base de cada comercio, según su código de rubro. Si el municipio actualiza un monto, editalo acá y no hace falta tocar código.</span>
</div>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.5rem;">
    <p style="font-size:0.85rem; color:var(--gray-500);"><?= count($tarifas) ?> código(s) de rubro</p>
    <div style="position:relative; flex:1 1 200px; min-width:0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:var(--slate-medium);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="searchTarifas" class="form-input" placeholder="Buscar por código o rubro..." style="padding-left: 2.25rem; width: 100%; min-width: 160px; font-size: 0.85rem;">
    </div>
</div>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th>Código</th><th>Rubro</th><th class="col-secondary">Categoría</th><th>Cuota / Alícuota</th><th class="col-secondary">Comercios Asignados</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($tarifas)): ?>
    <tr><td colspan="6" class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        <p>Todavía no hay tarifas cargadas.</p>
    </td></tr>
<?php else: foreach ($tarifas as $t): ?>
    <tr class="tarifa-row">
        <td style="font-weight:700; color:var(--primary-600); font-family:monospace;"><?= htmlspecialchars($t['codigo']) ?></td>
        <td><?= htmlspecialchars($t['rubro']) ?></td>
        <td class="col-secondary"><?= htmlspecialchars($t['categoria'] ?? '—') ?></td>
        <td>
            <?php if ($t['cuota_fija'] !== null): ?>
                <span style="font-weight:600;">$ <?= number_format((float) $t['cuota_fija'], 2, ',', '.') ?></span>
                <div style="font-size:0.72rem; color:var(--gray-400);">cuota fija bimestral</div>
            <?php elseif (!empty($t['alicuota'])): ?>
                <span style="font-weight:600; color:var(--warning);"><?= htmlspecialchars($t['alicuota']) ?></span>
            <?php else: ?>
                <span style="color:var(--gray-400);">— (sin definir)</span>
            <?php endif; ?>
        </td>
        <td class="col-secondary" style="text-align:center; font-weight:600;"><?= (int) $t['comercios_asignados'] ?></td>
        <td>
            <button class="icon-btn" onclick='openEditModal(<?= json_encode($t) ?>)' title="Editar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-editar-tarifa"><div class="modal">
<div class="modal-header"><h3>Editar Tarifa</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" id="form-editar-tarifa">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-group"><label class="form-label">Código de Rubro</label><input type="text" id="edit-codigo" class="form-input" disabled></div>
    <div class="form-group"><label class="form-label">Rubro *</label><input type="text" name="rubro" id="edit-rubro" class="form-input" required></div>
    <div class="form-group"><label class="form-label">Categoría</label><input type="text" name="categoria" id="edit-categoria" class="form-input"></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Cuota Fija Bimestral ($)</label><input type="number" name="cuota_fija" id="edit-cuota-fija" class="form-input" step="0.01" placeholder="Dejar vacío si se cobra por alícuota"></div>
        <div class="form-group"><label class="form-label">Alícuota</label><input type="text" name="alicuota" id="edit-alicuota" class="form-input" placeholder="Ej: 6% (solo si no es cuota fija)"></div>
    </div>
    <p style="font-size:0.78rem; color:var(--gray-500); margin:0;">Completá solo uno de los dos: cuota fija o alícuota. La mayoría de los rubros usan cuota fija.</p>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
</form></div></div>

<script>
function openEditModal(t) {
    document.getElementById('form-editar-tarifa').action = '<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/tarifario/editar/' + t.id;
    document.getElementById('edit-codigo').value = t.codigo;
    document.getElementById('edit-rubro').value = t.rubro;
    document.getElementById('edit-categoria').value = t.categoria || '';
    document.getElementById('edit-cuota-fija').value = t.cuota_fija !== null ? t.cuota_fija : '';
    document.getElementById('edit-alicuota').value = t.alicuota || '';
    document.getElementById('modal-editar-tarifa').classList.add('active');
}

document.addEventListener('DOMContentLoaded', () => {
    const searchTarifas = document.getElementById('searchTarifas');
    const rowsTarifas = document.querySelectorAll('.tarifa-row');
    if (searchTarifas) {
        searchTarifas.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            rowsTarifas.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
