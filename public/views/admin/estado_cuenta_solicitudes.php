<?php
$pageTitle  = 'Solicitudes de Estado de Cuenta';
$activePage = 'estado-cuenta';
require __DIR__ . '/layout_header.php';
?>

<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        </div>
        <div class="stat-label">Pendientes de responder</div>
        <div class="stat-value"><?= count($pendientes) ?></div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-label">Respondidas</div>
        <div class="stat-value"><?= count($solicitudes) - count($pendientes) ?></div>
    </div>
</div>

<div class="alert-info" style="margin-bottom:1.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    Un comercio con deuda no ve el detalle de sus facturas — solo un aviso genérico y este botón. Cuando pide su estado de cuenta, conciliá acá a mano contra el sistema anterior y respondé con la situación real: se le envía como notificación.
</div>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th>Comercio</th><th>Solicitado</th><th>Estado</th><th class="col-secondary">Respuesta</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($solicitudes)): ?>
    <tr><td colspan="5" class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        <p>Todavía no hay solicitudes de estado de cuenta.</p>
    </td></tr>
<?php else: foreach ($solicitudes as $s): ?>
    <tr>
        <td>
            <div style="font-weight:600;"><?= htmlspecialchars($s['owner_name'] ?: $s['business_name']) ?></div>
            <div style="font-size:0.72rem;color:var(--primary-600);font-family:monospace;"><?= htmlspecialchars($s['client_code']) ?></div>
        </td>
        <td><?= date('d/m/Y H:i', strtotime($s['requested_at'])) ?></td>
        <td>
            <?php if ($s['status'] === 'pending'): ?>
                <span class="status-badge status-pending"><span class="status-dot"></span>Pendiente</span>
            <?php else: ?>
                <span class="status-badge status-paid"><span class="status-dot"></span>Respondida</span>
            <?php endif; ?>
        </td>
        <td class="col-secondary" style="max-width:320px;">
            <?php if (!empty($s['response_message'])): ?>
                <span style="font-size:0.8rem;"><?= htmlspecialchars($s['response_message']) ?></span>
                <div style="font-size:0.7rem;color:var(--gray-500);margin-top:0.25rem;">
                    <?= htmlspecialchars($s['resolved_by_name'] ?? '') ?> — <?= date('d/m/Y H:i', strtotime($s['resolved_at'])) ?>
                </div>
            <?php else: ?>
                <span style="color:var(--slate-medium);">—</span>
            <?php endif; ?>
        </td>
        <td style="white-space: nowrap;">
            <button class="btn btn-secondary btn-sm" onclick='openResponderModal(<?= json_encode(['id' => $s['id'], 'nombre' => $s['owner_name'] ?: $s['business_name'], 'codigo' => $s['client_code']]) ?>)'>
                <?= $s['status'] === 'pending' ? 'Responder' : 'Volver a responder' ?>
            </button>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>

<!-- Modal Responder -->
<div class="modal-overlay" id="modal-responder-solicitud"><div class="modal">
<div class="modal-header"><h3>Responder Estado de Cuenta</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" id="form-responder-solicitud">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <p style="margin:0 0 1rem;">Comercio: <strong id="responder-comercio"></strong></p>
    <div class="form-group">
        <label class="form-label">Estado de cuenta real (conciliado con el sistema anterior) *</label>
        <textarea name="response_message" id="responder-mensaje" class="form-input" rows="5" required
                  placeholder="Ej: Al día. Verificamos contra el sistema anterior y no registrás deuda pendiente."></textarea>
    </div>
    <p style="font-size:0.78rem; color:var(--gray-500); margin:0.5rem 0 0;">Este texto le llega al comercio como notificación tal cual lo escribas.</p>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Enviar respuesta</button></div>
</form></div></div>

<script>
function openResponderModal(d) {
    document.getElementById('form-responder-solicitud').action = '<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/estado-cuenta/responder/' + d.id;
    document.getElementById('responder-comercio').textContent = d.nombre + ' (' + d.codigo + ')';
    document.getElementById('responder-mensaje').value = '';
    document.getElementById('modal-responder-solicitud').classList.add('active');
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
