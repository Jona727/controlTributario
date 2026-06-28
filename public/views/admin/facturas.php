<?php
$pageTitle  = 'Facturación';
$activePage = 'facturas';
require __DIR__ . '/layout_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <p style="font-size:0.85rem; color:var(--gray-500);"><?= count($facturas) ?> factura(s)</p>
    <div style="display:flex; gap:0.5rem; align-items:center;">
        <div style="position:relative;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:var(--slate-medium);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" class="form-input" placeholder="Buscar factura..." style="padding-left: 2.25rem; width: 220px; font-size: 0.85rem;">
        </div>
        <button class="btn btn-success" id="btn-cobrar-lote" style="display:none;" data-modal-open="modal-cobrar-lote">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.25rem;vertical-align:middle;"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Cobrar Seleccionadas (<span id="count-seleccionadas">0</span>)
        </button>
        <button class="btn btn-secondary" data-modal-open="modal-generar-lote">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.25rem;vertical-align:middle;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
            Generar Lote Mensual
        </button>
        <button class="btn btn-primary" data-modal-open="modal-crear-factura">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva Factura
        </button>
    </div>
</div>

<form method="GET" action="" style="display:flex; gap:1rem; margin-bottom:1.5rem; align-items:end; flex-wrap:wrap;">
    <div class="form-group" style="margin-bottom:0; min-width: 250px;">
        <label class="form-label">Filtrar por Comercio</label>
        <select name="user_id" class="form-select">
            <option value="">Todos los comercios</option>
            <?php foreach ($comerciosSelect as $cs): ?>
                <option value="<?= $cs['id'] ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $cs['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cs['client_code'] . ' – ' . $cs['business_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin-bottom:0; width: 150px;">
        <label class="form-label">Período (Ej: 2026-07)</label>
        <input type="text" name="period" class="form-input" placeholder="AAAA-MM" value="<?= htmlspecialchars($_GET['period'] ?? '') ?>">
    </div>
    <div style="display:flex; gap:0.5rem;">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if (!empty($_GET['user_id']) || !empty($_GET['period'])): ?>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas" class="btn btn-ghost">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
<div style="overflow-x:auto;">
<table class="data-table">
<thead><tr>
    <th style="width: 40px; text-align: center;"><input type="checkbox" id="check-all-facturas" class="form-checkbox"></th>
    <th># Factura</th><th>Comercio</th><th>CUIT</th><th>Período</th><th>Emisión</th><th>Vencimiento</th><th>Importe</th><th>Estado</th><th>Acciones</th>
</tr></thead>
<tbody>
<?php if (empty($facturas)): ?>
    <tr><td colspan="9" class="empty-state"><p>Sin facturas</p></td></tr>
<?php else: foreach ($facturas as $f):
    $sc = match($f['status']) { 'paid'=>'status-paid','pending'=>'status-pending','overdue'=>'status-overdue',default=>'status-cancelled' };
    $sl = match($f['status']) { 'paid'=>'Pagado','pending'=>'Pendiente','overdue'=>'Vencido','cancelled'=>'Cancelado',default=>$f['status'] };
?>
    <tr class="factura-row">
        <td style="text-align: center;">
            <?php if ($f['status'] === 'pending' || $f['status'] === 'overdue'): ?>
                <input type="checkbox" class="form-checkbox check-factura" value="<?= $f['id'] ?>" data-user="<?= $f['user_id'] ?>" data-comercio="<?= htmlspecialchars($f['business_name']) ?>" data-monto="<?= floatval($f['subtotal']) ?>">
            <?php endif; ?>
        </td>
        <td style="font-weight:600;"><?= htmlspecialchars($f['invoice_number']) ?></td>
        <td><?= htmlspecialchars($f['business_name']) ?></td>
        <td><?= htmlspecialchars($f['cuit']) ?></td>
        <td><?= htmlspecialchars($f['period'] ?? '–') ?></td>
        <td><?= date('d/m/Y', strtotime($f['issue_date'])) ?></td>
        <td><?= date('d/m/Y', strtotime($f['due_date'])) ?></td>
        <td style="font-weight:600;">$ <?= number_format((float)$f['total_amount'],2,',','.') ?></td>
        <td><span class="status-badge <?= $sc ?>"><span class="status-dot"></span><?= $sl ?></span></td>
        <td>
            <div style="display:flex;align-items:center;gap:0.35rem;">
                <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/pdf/<?= $f['id'] ?>" class="btn btn-ghost btn-sm" title="Descargar PDF de Boleta" style="padding: 0.35rem 0.6rem;" target="_blank">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
                <?php if ($f['status'] === 'paid' && !empty($f['payment_id'])): ?>
                    <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/recibo/<?= $f['payment_id'] ?>" class="btn btn-ghost btn-sm" title="Descargar Recibo de Caja" style="padding: 0.35rem 0.6rem;" target="_blank">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </a>
                    <button class="btn btn-ghost btn-sm btn-revertir" 
                            title="Revertir Pago"
                            style="padding: 0.35rem 0.6rem; color: var(--danger);"
                            data-id="<?= $f['id'] ?>"
                            data-numero="<?= htmlspecialchars($f['invoice_number']) ?>"
                            data-comercio="<?= htmlspecialchars($f['business_name']) ?>"
                            data-modal-open="modal-revertir-factura">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                    </button>
                <?php endif; ?>
                <?php if ($f['status'] !== 'paid' && $f['status'] !== 'cancelled'): 
                    $moraData = \App\Controllers\InvoiceController::calculateMora($f);
                    $btnDisabled = $f['has_older_debt'] ? 'disabled' : '';
                    $btnColor = $f['has_older_debt'] ? 'var(--slate-medium)' : 'var(--success)';
                    $btnTitle = $f['has_older_debt'] ? 'Cobro bloqueado: Deuda anterior impaga' : 'Cobrar en Ventanilla';
                ?>
                    <button class="btn btn-ghost btn-sm btn-caja" 
                            title="<?= $btnTitle ?>"
                            style="padding: 0.35rem 0.6rem; color: <?= $btnColor ?>;"
                            <?= $btnDisabled ?>
                            data-id="<?= $f['id'] ?>"
                            data-numero="<?= htmlspecialchars($f['invoice_number']) ?>"
                            data-periodo="<?= htmlspecialchars($f['period'] ?? '–') ?>"
                            data-comercio="<?= htmlspecialchars($f['business_name']) ?>"
                            data-cuit="<?= htmlspecialchars($f['cuit']) ?>"
                            data-subtotal="<?= floatval($f['subtotal']) ?>"
                            data-mora="<?= floatval($moraData['surcharge']) ?>"
                            data-dias="<?= intval($moraData['dias_mora']) ?>"
                            data-total="<?= floatval($moraData['total_amount']) ?>"
                            data-modal-open="modal-cobrar-factura">
                        <?php if ($f['has_older_debt']): ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        <?php else: ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 14c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4z"/></svg>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>
                <form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/estado/<?= $f['id'] ?>" style="display:inline;margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <select name="status" onchange="this.form.submit()" class="form-select" style="width:auto;padding:0.25rem 0.5rem;font-size:0.75rem;margin:0;">
                        <option value="">Estado...</option>
                        <option value="pending">Pendiente</option>
                        <option value="overdue">Vencido</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </form>
            </div>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>

<!-- Modal Crear Factura -->
<div class="modal-overlay" id="modal-crear-factura"><div class="modal">
<div class="modal-header"><h3>Nueva Factura</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/crear">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="form-group">
        <label class="form-label">Comercio *</label>
        <select name="user_id" class="form-select" required>
            <option value="">Seleccionar comercio...</option>
            <?php foreach ($comerciosSelect as $cs): ?>
                <option value="<?= $cs['id'] ?>"><?= htmlspecialchars($cs['client_code'] . ' – ' . $cs['business_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Nº Factura *</label><input type="text" name="invoice_number" class="form-input" placeholder="F-2025-0010" required></div>
        <div class="form-group"><label class="form-label">Período</label><input type="text" name="period" class="form-input" placeholder="2025-06"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Fecha Emisión *</label><input type="date" name="issue_date" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Fecha Vencimiento *</label><input type="date" name="due_date" class="form-input" required></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Monto *</label><input type="number" name="total_amount" class="form-input" step="0.01" required></div>
        <div class="form-group"><label class="form-label">Recargo</label><input type="number" name="surcharge" class="form-input" step="0.01" value="0"></div>
    </div>
    <div class="form-group"><label class="form-label">Descripción</label><input type="text" name="item_description" class="form-input" placeholder="Tasa de Seguridad e Higiene"></div>
    <div class="form-group"><label class="form-label">Notas</label><textarea name="notes" class="form-textarea" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button><button type="submit" class="btn btn-primary">Crear Factura</button></div>
</form></div></div>

<!-- Modal Generar Lote Mensual -->
<div class="modal-overlay" id="modal-generar-lote"><div class="modal">
<div class="modal-header"><h3>Facturación Masiva (Generar Lote)</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form method="POST" action="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/generar-lote" onsubmit="confirmarLote(event, this)">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
<div class="modal-body">
    <div class="alert-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Se generará una boleta para cada comercio activo usando su Tasa Base Fija configurada.
    </div>
    <div class="form-group">
        <label class="form-label">Período Fiscal *</label>
        <input type="text" name="period" class="form-input" placeholder="2026-07" required>
        <span style="font-size:0.7rem; color:var(--gray-500);">Formato recomendado: AAAA-MM</span>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Fecha Emisión *</label><input type="date" name="issue_date" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Fecha Vencimiento *</label><input type="date" name="due_date" class="form-input" required></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-primary">
        Generar Facturas
    </button>
</div>
</form></div></div>

<!-- Modal Registrar Cobro en Ventanilla -->
<div class="modal-overlay" id="modal-cobrar-factura"><div class="modal">
<div class="modal-header"><h3>Cobro en Ventanilla (Efectivo)</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form id="form-cobrar-factura" method="POST" action="">
<div class="modal-body">
    <div class="alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 14c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4z"/></svg>
        El cobro se procesará en efectivo por ventanilla. Se generará e imprimirá el recibo oficial.
    </div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; font-size: 0.85rem;">
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);">Comercio:</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--brand-primary);" id="caja-comercio"></td></tr>
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);">CUIT:</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--brand-primary);" id="caja-cuit"></td></tr>
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);">Nº de Factura:</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--brand-primary);" id="caja-factura"></td></tr>
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);">Período:</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--brand-primary);" id="caja-periodo"></td></tr>
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);">Monto Base (Tasa):</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--brand-primary);" id="caja-subtotal"></td></tr>
        <tr style="border-bottom: 1px solid var(--slate-border);"><td style="padding: 0.5rem 0; color: var(--slate-medium);" id="caja-mora-label">Recargo por Mora:</td><td style="padding: 0.5rem 0; font-weight: 600; text-align: right; color: var(--danger);" id="caja-mora"></td></tr>
        <tr style="border-bottom: 2px solid var(--slate-border); background-color: var(--slate-light);"><td style="padding: 0.75rem 0.5rem; font-weight: 700; color: var(--brand-primary);">TOTAL A COBRAR:</td><td style="padding: 0.75rem 0.5rem; font-weight: 700; text-align: right; font-size: 1.1rem; color: var(--success);" id="caja-total"></td></tr>
    </table>
    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Contraseña de Seguridad *</label>
        <input type="password" name="admin_password" class="form-input" placeholder="Tu contraseña de administrador" required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Confirmar y Emitir Recibo
    </button>
</div>
</form></div></div>

<!-- Modal Revertir Pago -->
<div class="modal-overlay" id="modal-revertir-factura"><div class="modal">
<div class="modal-header"><h3>Revertir Pago de Boleta</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form id="form-revertir-factura" method="POST" action="">
<div class="modal-body">
    <div class="alert-info" style="background-color: var(--danger-light); color: var(--danger); border-color: #fca5a5;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <strong>¡Atención!</strong> Estás a punto de anular un pago registrado. La boleta volverá a estado "Pendiente", se borrará el recibo oficial y esto quedará registrado en auditoría.
    </div>
    <p style="font-size: 0.85rem; margin-bottom: 1rem;">
        Comercio: <strong id="revert-comercio"></strong><br>
        Factura: <strong id="revert-factura"></strong>
    </p>
    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Contraseña de Seguridad *</label>
        <input type="password" name="admin_password" class="form-input" placeholder="Tu contraseña de administrador" required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-danger">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
        Revertir Pago
    </button>
</div>
</div>
</form></div></div>

<!-- Modal Registrar Cobro en Lote -->
<div class="modal-overlay" id="modal-cobrar-lote"><div class="modal">
<div class="modal-header"><h3>Cobro en Lote (Efectivo)</h3><button class="modal-close" data-modal-close>&times;</button></div>
<form id="form-cobrar-lote" method="POST" action="">
<div class="modal-body">
    <div class="alert-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Se registrará el cobro de <strong id="lote-count-text"></strong> facturas para <strong id="lote-comercio-text"></strong>.
    </div>
    
    <div id="lote-facturas-list" style="max-height: 150px; overflow-y: auto; margin-bottom: 1rem; border: 1px solid var(--slate-border); border-radius: 4px; padding: 0.5rem; font-size: 0.85rem; background: var(--slate-light);">
    </div>

    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label">Contraseña de Seguridad *</label>
        <input type="password" name="admin_password" class="form-input" placeholder="Tu contraseña de administrador" required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
    <button type="submit" class="btn btn-success" id="btn-submit-lote">
        Confirmar y Emitir Recibo Único
    </button>
</div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Live Search
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.factura-row');
    
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    const formatCurrency = (val) => '$ ' + parseFloat(val).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    const btnsCaja = document.querySelectorAll('.btn-caja');
    const formCaja = document.getElementById('form-cobrar-factura');
    
    btnsCaja.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('caja-comercio').textContent = btn.dataset.comercio;
            document.getElementById('caja-cuit').textContent = btn.dataset.cuit;
            document.getElementById('caja-factura').textContent = btn.dataset.numero;
            document.getElementById('caja-periodo').textContent = btn.dataset.periodo;
            
            const subtotal = parseFloat(btn.dataset.subtotal);
            const mora = parseFloat(btn.dataset.mora);
            const dias = parseInt(btn.dataset.dias);
            const total = parseFloat(btn.dataset.total);
            
            document.getElementById('caja-subtotal').textContent = formatCurrency(subtotal);
            document.getElementById('caja-mora-label').textContent = `Recargo por Mora (${dias} días de atraso):`;
            document.getElementById('caja-mora').textContent = formatCurrency(mora);
            document.getElementById('caja-total').textContent = formatCurrency(total);
            
            formCaja.action = `<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/pagar/${btn.dataset.id}`;
            formCaja.querySelector('input[name="admin_password"]').value = '';
        });
    });
    
    const btnsRevertir = document.querySelectorAll('.btn-revertir');
    const formRevertir = document.getElementById('form-revertir-factura');
    
    btnsRevertir.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('revert-comercio').textContent = btn.dataset.comercio;
            document.getElementById('revert-factura').textContent = btn.dataset.numero;
            formRevertir.action = `<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/revertir/${btn.dataset.id}`;
            formRevertir.querySelector('input[name="admin_password"]').value = '';
        });
    });

    if (formCaja) {
        formCaja.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = formCaja.querySelector('button[type="submit"]');
            const passInput = formCaja.querySelector('input[name="admin_password"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Procesando...';
            
            try {
                const response = await fetch(formCaja.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ admin_password: passInput.value })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Abrir PDF de recibo en una pestaña nueva
                    window.open(`<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/recibo/${result.payment_id}`, '_blank');
                    
                    // Recargar la ventana para actualizar listas
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.error || 'No se pudo procesar el pago.'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Confirmar y Emitir Recibo
                    `;
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error inesperado al procesar el pago.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Confirmar y Emitir Recibo
                `;
            }
        });
    }

    if (formRevertir) {
        formRevertir.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = formRevertir.querySelector('button[type="submit"]');
            const passInput = formRevertir.querySelector('input[name="admin_password"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Procesando...';
            
            try {
                const response = await fetch(formRevertir.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ admin_password: passInput.value })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.error || 'No se pudo revertir el pago.'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                        Revertir Pago
                    `;
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error inesperado.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>
                    Revertir Pago
                `;
            }
        });
    }
        });
    }

    // --- LOGICA DE COBRO EN LOTE ---
    const checkAll = document.getElementById('check-all-facturas');
    const checkFacturas = document.querySelectorAll('.check-factura');
    const btnCobrarLote = document.getElementById('btn-cobrar-lote');
    const countSeleccionadas = document.getElementById('count-seleccionadas');
    const formCobrarLote = document.getElementById('form-cobrar-lote');

    function updateCobrarLoteBtn() {
        const checked = document.querySelectorAll('.check-factura:checked');
        countSeleccionadas.textContent = checked.length;
        if (checked.length > 1) {
            btnCobrarLote.style.display = 'inline-flex';
        } else {
            btnCobrarLote.style.display = 'none';
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', (e) => {
            checkFacturas.forEach(cb => {
                // Solo checkear si están visibles (podrían estar filtradas, aunque la paginación no se hace en frontend, pero por si acaso)
                cb.checked = e.target.checked;
            });
            updateCobrarLoteBtn();
        });
    }

    checkFacturas.forEach(cb => {
        cb.addEventListener('change', () => {
            if (!cb.checked && checkAll) checkAll.checked = false;
            updateCobrarLoteBtn();
        });
    });

    if (btnCobrarLote) {
        btnCobrarLote.addEventListener('click', (e) => {
            const checked = document.querySelectorAll('.check-factura:checked');
            if (checked.length < 2) {
                e.preventDefault();
                alert('Debe seleccionar al menos 2 facturas para el cobro en lote.');
                return;
            }

            // Validar que todas sean del mismo comercio
            let firstUser = null;
            let firstComercioName = null;
            let isValid = true;
            let listHtml = '';
            
            checked.forEach(cb => {
                if (firstUser === null) {
                    firstUser = cb.dataset.user;
                    firstComercioName = cb.dataset.comercio;
                } else if (firstUser !== cb.dataset.user) {
                    isValid = false;
                }
                const tr = cb.closest('tr');
                const num = tr.cells[1].textContent.trim();
                const per = tr.cells[4].textContent.trim();
                listHtml += `<div>- Factura <strong>${num}</strong> (Vto: ${per})</div>`;
            });

            if (!isValid) {
                e.preventDefault();
                alert('Solo puede seleccionar facturas de un mismo comercio para realizar un pago en lote.');
                e.stopPropagation();
                
                // Cerrar modal automáticamente ya que se abriría por el data-modal-open
                const modal = document.getElementById('modal-cobrar-lote');
                if (modal) modal.classList.remove('active');
                return;
            }

            document.getElementById('lote-count-text').textContent = checked.length;
            document.getElementById('lote-comercio-text').textContent = firstComercioName;
            document.getElementById('lote-facturas-list').innerHTML = listHtml;
            formCobrarLote.action = `<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/pagar-lote`;
        });
    }

    if (formCobrarLote) {
        formCobrarLote.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const checked = document.querySelectorAll('.check-factura:checked');
            const invoiceIds = Array.from(checked).map(cb => parseInt(cb.value));
            const submitBtn = formCobrarLote.querySelector('button[type="submit"]');
            const passInput = formCobrarLote.querySelector('input[name="admin_password"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Procesando...';
            
            try {
                const response = await fetch(formCobrarLote.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ 
                        admin_password: passInput.value,
                        invoice_ids: invoiceIds
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.open(`<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas/recibo/${result.payment_id}`, '_blank');
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.error || 'No se pudo procesar el pago en lote.'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Confirmar y Emitir Recibo Único';
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error inesperado al procesar el pago en lote.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirmar y Emitir Recibo Único';
            }
        });
    }

});

function confirmarLote(e, form) {
    if (!confirm("¿Está seguro de generar la facturación en lote para todos los comercios activos? Este proceso no se puede deshacer de forma automática.")) {
        e.preventDefault();
        return;
    }
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Procesando lote...';
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
