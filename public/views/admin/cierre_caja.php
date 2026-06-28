<?php
$pageTitle  = 'Rendición y Cierre de Caja';
$activePage = 'cierre-caja';
require __DIR__ . '/layout_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <p style="font-size:0.85rem; color:var(--gray-500);">Resumen de recaudación para el día de hoy: <strong><?= date('d/m/Y') ?></strong></p>
    <?php if (!empty($cobros)): ?>
        <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/cierre-caja/pdf" class="btn btn-danger" target="_blank">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem; vertical-align: middle;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Exportar Rendición (PDF)
        </a>
    <?php endif; ?>
</div>

<!-- ═══ Stat Cards ═══ -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 14c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4z"/></svg>
        </div>
        <div class="stat-label">Subtotal (Tasa Base)</div>
        <div class="stat-value">$ <?= number_format($totalBase, 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-label">Recargo por Mora Cobrado</div>
        <div class="stat-value">$ <?= number_format($totalMora, 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="stat-label">Total Recaudado en Mano</div>
        <div class="stat-value" style="color: var(--success);">$ <?= number_format($totalPaid, 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="stat-label">Transacciones Realizadas</div>
        <div class="stat-value"><?= count($cobros) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Detalle de Transacciones del Día</h3>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Comercio</th>
                    <th>CUIT</th>
                    <th>Nº Recibo</th>
                    <th>Nº Factura</th>
                    <th>Período</th>
                    <th>Importe Base</th>
                    <th>Mora Cobrada</th>
                    <th>Total Cobrado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cobros)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <p>No se registran transacciones de cobro en ventanilla para el día de hoy.</p>
                        </td>
                    </tr>
                <?php else: foreach ($cobros as $c): ?>
                    <tr>
                        <td><?= date('H:i:s', strtotime($c['payment_date'])) ?> hs</td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($c['business_name']) ?></div>
                            <div style="font-size: 0.72rem; color: var(--primary-600);"><?= htmlspecialchars($c['client_code']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($c['cuit']) ?></td>
                        <td style="font-weight: 600; color: var(--danger);"><?= htmlspecialchars($c['receipt_number']) ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($c['invoice_number']) ?></td>
                        <td><?= htmlspecialchars($c['period'] ?? '–') ?></td>
                        <td>$ <?= number_format(floatval($c['amount_paid']) - floatval($c['surcharge_paid']), 2, ',', '.') ?></td>
                        <td style="color: var(--danger-600); font-weight: 500;">$ <?= number_format(floatval($c['surcharge_paid']), 2, ',', '.') ?></td>
                        <td style="font-weight: bold; color: var(--success);">$ <?= number_format(floatval($c['amount_paid']), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
