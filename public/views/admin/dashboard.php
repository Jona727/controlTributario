<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/layout_header.php';
?>

<!-- ═══ Stat Cards ═══ -->
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="stat-label">Total Comercios</div>
        <div class="stat-value"><?= $stats['total_comercios'] ?></div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-label">Facturas Pendientes</div>
        <div class="stat-value"><?= $stats['facturas_pendientes'] ?></div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="stat-label">Facturas Vencidas</div>
        <div class="stat-value"><?= $stats['facturas_vencidas'] ?></div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
        </div>
        <div class="stat-label">Deuda Total</div>
        <div class="stat-value">$ <?= number_format((float)$stats['deuda_total'], 2, ',', '.') ?></div>
    </div>
</div>

<!-- ═══ Navigation Tabs ═══ -->
<div class="tab-container" style="margin-bottom: 1.5rem;">
    <div class="tab-buttons" style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--slate-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
        <button class="btn tab-button active" onclick="switchTab(event, 'tab-recaudacion')" style="border-radius: 4px; padding: 0.5rem 1.25rem; font-weight: 600; text-transform: none; letter-spacing: normal; background-color: var(--slate-medium); color: #ffffff;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px; vertical-align: -2px;"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 14c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4z"/></svg>
            Caja y Recaudación Diaria
        </button>
        <button class="btn tab-button" onclick="switchTab(event, 'tab-deuda')" style="border-radius: 4px; padding: 0.5rem 1.25rem; font-weight: 600; text-transform: none; letter-spacing: normal; background-color: transparent; color: var(--slate-medium); border: 1px solid var(--slate-border);">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px; vertical-align: -2px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            Control de Deudores
        </button>
    </div>

    <!-- ═══ Tab 1: Caja y Recaudación ═══ -->
    <div id="tab-recaudacion" class="tab-content">
        <!-- Cobros Recientes -->
        <div class="card" style="border-radius: 6px; box-shadow: var(--shadow-sm); width: 100%;">
            <div class="card-header" style="padding: 1.25rem 1.5rem;">
                <h3 style="font-size: 1.05rem;">Cobros Recientes en Ventanilla</h3>
                <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/cierre-caja" class="btn btn-ghost btn-sm" style="border-radius: 4px;">Ver Rendición del Día</a>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="padding: 0.85rem 1rem;">Hora/Fecha</th>
                            <th style="padding: 0.85rem 1rem;">Comercio</th>
                            <th style="padding: 0.85rem 1rem;">Nº Recibo</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Mora Cobrada</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Total Cobrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cobrosRecientes)): ?>
                            <tr><td colspan="5" class="empty-state"><p>No se registran cobros recientes hoy.</p></td></tr>
                        <?php else: ?>
                            <?php foreach ($cobrosRecientes as $c): ?>
                                <tr>
                                    <td style="padding: 0.9rem 1rem;"><?= date('d/m/Y H:i', strtotime($c['payment_date'])) ?> hs</td>
                                    <td style="padding: 0.9rem 1rem;">
                                        <div style="font-weight: 600; color: var(--slate-dark);"><?= htmlspecialchars($c['business_name']) ?></div>
                                        <span style="font-size: 0.72rem; color: #6b7280; font-family: monospace;"><?= htmlspecialchars($c['client_code']) ?></span>
                                    </td>
                                    <td style="padding: 0.9rem 1rem; font-weight: 600; color: var(--danger);"><?= htmlspecialchars($c['receipt_number']) ?></td>
                                    <td style="padding: 0.9rem 1rem; text-align: right; color: var(--danger); font-weight: 500;">$ <?= number_format((float)$c['surcharge_paid'], 2, ',', '.') ?></td>
                                    <td style="padding: 0.9rem 1rem; text-align: right; font-weight: bold; color: var(--success);">$ <?= number_format((float)$c['amount_paid'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ Tab 2: Control de Deuda ═══ -->
    <div id="tab-deuda" class="tab-content" style="display: none;">
        <div class="grid-2-1">
            <!-- Ranking de Deudores -->
            <div class="card" style="border-radius: 6px; box-shadow: var(--shadow-sm);">
                <div class="card-header" style="padding: 1.25rem 1.5rem;">
                    <h3 style="font-size: 1.05rem;">Ranking de Comercios con Mayor Mora</h3>
                    <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/deuda" class="btn btn-ghost btn-sm" style="border-radius: 4px;">Ver Auditoría de Deuda</a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="padding: 0.85rem 1rem;">Comercio</th>
                                <th style="padding: 0.85rem 1rem;">CUIT</th>
                                <th style="padding: 0.85rem 1rem; text-align: center;">Facturas Vencidas</th>
                                <th style="padding: 0.85rem 1rem; text-align: right;">Total Adeudado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($morosos)): ?>
                                <tr><td colspan="4" class="empty-state"><p>No se registran comercios en mora activa.</p></td></tr>
                            <?php else: ?>
                                <?php foreach ($morosos as $m): ?>
                                    <tr>
                                        <td style="padding: 0.9rem 1rem; font-weight: 600; color: var(--slate-dark);">
                                            <div><?= htmlspecialchars($m['business_name']) ?></div>
                                            <span style="font-size: 0.72rem; color: #6b7280; font-family: monospace;"><?= htmlspecialchars($m['client_code']) ?></span>
                                        </td>
                                        <td style="padding: 0.9rem 1rem; color: #4b5563;"><?= htmlspecialchars($m['cuit']) ?></td>
                                        <td style="padding: 0.9rem 1rem; text-align: center; font-weight: 600; color: var(--danger);"><?= $m['facturas_vencidas'] ?></td>
                                        <td style="padding: 0.9rem 1rem; text-align: right; font-weight: bold; color: var(--danger);">$ <?= number_format((float)$m['deuda'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen de Cobrabilidad -->
            <div class="card" style="border-radius: 6px; box-shadow: var(--shadow-sm);">
                <div class="card-header" style="padding: 1.25rem 1.5rem;">
                    <h3 style="font-size: 1.05rem;">Cobrabilidad del Mes</h3>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <div style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.35rem; font-weight: 500;">
                            <span>Facturas Vencidas (Mora)</span>
                            <span style="font-weight: bold; color: var(--danger);"><?= $stats['facturas_vencidas'] ?></span>
                        </div>
                        <div style="height: 6px; background-color: #f1f5f9; border-radius: 3px; overflow: hidden;">
                            <div style="width: <?= min(100, ($stats['facturas_vencidas'] > 0 ? 100 : 0)) ?>%; height: 100%; background-color: var(--danger);"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.35rem; font-weight: 500;">
                            <span>Facturas Pendientes</span>
                            <span style="font-weight: bold; color: var(--warning);"><?= $stats['facturas_pendientes'] ?></span>
                        </div>
                        <div style="height: 6px; background-color: #f1f5f9; border-radius: 3px; overflow: hidden;">
                            <div style="width: <?= min(100, ($stats['facturas_pendientes'] > 0 ? 100 : 0)) ?>%; height: 100%; background-color: var(--warning);"></div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--slate-border); padding-top: 1.25rem; margin-top: 1.25rem; text-align: center;">
                        <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--slate-medium); letter-spacing: 0.05em; display: block; margin-bottom: 0.35rem; font-weight: 600;">Recaudación Mensual Consolidada</span>
                        <span style="font-size: 1.4rem; font-weight: bold; color: var(--success);">$ <?= number_format((float)$stats['recaudacion_mes'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script de Navegación por Pestañas -->
<script>
function switchTab(event, tabId) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });

    // Restaurar botones de pestañas
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
        btn.style.backgroundColor = 'transparent';
        btn.style.color = 'var(--slate-medium)';
        btn.style.borderColor = 'var(--slate-border)';
    });

    // Mostrar contenido seleccionado
    document.getElementById(tabId).style.display = 'block';

    // Activar botón seleccionado
    const activeBtn = event.currentTarget;
    activeBtn.classList.add('active');
    activeBtn.style.backgroundColor = 'var(--slate-medium)';
    activeBtn.style.color = '#ffffff';
    activeBtn.style.borderColor = 'var(--slate-medium)';
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
