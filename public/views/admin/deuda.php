<?php
$pageTitle  = 'Deuda & Indicadores Globales';
$activePage = 'deuda';
require __DIR__ . '/layout_header.php';
?>

<!-- Include Chart.js CDN for professional interactive charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ═══ Stat Cards ═══ -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
        </div>
        <div class="stat-label">Deuda Exigible Vencida</div>
        <div class="stat-value">$ <?= number_format((float)$stats['vencido'], 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-label">Deuda No Vencida (Pendiente)</div>
        <div class="stat-value">$ <?= number_format((float)$stats['pendiente'], 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 4 12 14.01 9 11.01"/><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
            </svg>
        </div>
        <div class="stat-label">Tasa Cobrada Acumulada</div>
        <div class="stat-value">$ <?= number_format((float)$stats['pagado'], 2, ',', '.') ?></div>
    </div>

    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
        </div>
        <div class="stat-label">Índice de Cobrabilidad</div>
        <div class="stat-value"><?= $stats['cobrabilidad'] ?>%</div>
    </div>
</div>

<!-- ═══ Charts & Analytics Section ═══ -->
<div class="grid-1-1" style="margin-bottom: 2rem;">
    <!-- Recaudación mensual vs Deuda -->
    <div class="card">
        <div class="card-header">
            <h3>Evolución de Recaudación y Deuda</h3>
        </div>
        <div class="card-body">
            <canvas id="historicalChart" style="max-height: 240px; width: 100%;"></canvas>
        </div>
    </div>

    <!-- Composición de cartera tributaria -->
    <div class="card">
        <div class="card-header">
            <h3>Distribución de Estado de Cuentas</h3>
        </div>
        <div class="card-body" style="display: flex; align-items: center; justify-content: center; gap: 2rem;">
            <div style="width: 50%; max-width: 180px;">
                <canvas id="distributionChart" style="max-height: 180px;"></canvas>
            </div>
            <div style="width: 50%; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="width: 12px; height: 12px; border-radius: 3px; background-color: var(--success); display: inline-block;"></span>
                    <span>Pagado ($ <?= number_format((float)$stats['pagado'], 0, ',', '.') ?>)</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="width: 12px; height: 12px; border-radius: 3px; background-color: var(--warning); display: inline-block;"></span>
                    <span>Pendiente ($ <?= number_format((float)$stats['pendiente'], 0, ',', '.') ?>)</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="width: 12px; height: 12px; border-radius: 3px; background-color: var(--danger); display: inline-block;"></span>
                    <span>Vencido ($ <?= number_format((float)$stats['vencido'], 0, ',', '.') ?>)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Ranking de Deudores ═══ -->
<div class="card">
    <div class="card-header">
        <h3>Ranking y Seguimiento de Deudores</h3>
        <p style="font-size:0.75rem; color:var(--gray-400);">Comercios con obligaciones pendientes, ordenados de mayor a menor deuda</p>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ranking</th>
                    <th>Comercio</th>
                    <th>CUIT</th>
                    <th>Facturas Impagas</th>
                    <th>Monto Vencido</th>
                    <th>Monto Pendiente</th>
                    <th style="font-weight: bold; color: var(--danger);">Deuda Total</th>
                    <th>Contacto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deudores)): ?>
                    <tr><td colspan="9" class="empty-state"><p>No se registran comercios deudores en el sistema.</p></td></tr>
                <?php else: $i = 1; foreach ($deudores as $d): ?>
                    <tr>
                        <td style="font-weight: bold; text-align: center; color: var(--gray-400); width: 60px;"># <?= $i++ ?></td>
                        <td>
                            <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($d['business_name']) ?></div>
                            <div style="font-size: 0.72rem; color: var(--primary-600);"><?= htmlspecialchars($d['client_code']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($d['cuit']) ?></td>
                        <td style="text-align: center; font-weight: 600;"><?= $d['total_facturas'] ?></td>
                        <td style="color: var(--danger);">$ <?= number_format((float)$d['monto_vencido'], 2, ',', '.') ?></td>
                        <td style="color: var(--warning); font-weight: 500;">$ <?= number_format((float)$d['monto_pendiente'], 2, ',', '.') ?></td>
                        <td style="font-weight: 700; color: var(--danger); font-size: 0.9rem;">$ <?= number_format((float)$d['deuda_total'], 2, ',', '.') ?></td>
                        <td>
                            <div style="font-size: 0.75rem;">✉ <?= htmlspecialchars($d['email']) ?></div>
                            <?php if (!empty($d['phone'])): ?>
                                <div style="font-size: 0.75rem; color: var(--gray-500);">☎ <?= htmlspecialchars($d['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="mailto:<?= $d['email'] ?>?subject=Recordatorio%20de%20Pago%20-%20Tasas%20Municipales&body=Estimado%20Contribuyente%20de%20<?= rawurlencode($d['business_name']) ?>%2C%20le%20escribimos%20desde%20la%20Municipalidad%20para%20recordarle%20que%20registra%20una%20deuda%20de%20%24<?= number_format((float)$d['deuda_total'], 2, ',', '.') ?>%20en%20concepto%20de%20Tasas%20de%20Seguridad%20e%20Higiene.%20Por%20favor%20ingrese%20al%20sistema%20para%20regularizar." 
                               class="btn btn-ghost btn-sm" 
                               style="padding: 0.35rem 0.6rem; color: var(--primary-600); border-color: var(--primary-200);"
                               title="Enviar Notificación de Cobro">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                Reclamar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Preparar los gráficos interactivos
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gráfico Histórico
    const histCtx = document.getElementById('historicalChart').getContext('2d');
    
    // Preparar datos desde PHP
    const labels = [];
    const dataPagado = [];
    const dataPendiente = [];

    <?php if (!empty($historico)): ?>
        <?php foreach ($historico as $h): ?>
            labels.push("<?= date('M Y', strtotime($h['mes'] . '-01')) ?>");
            dataPagado.push(<?= (float)$h['pagado'] ?>);
            dataPendiente.push(<?= (float)$h['pendiente'] ?>);
        <?php endforeach; ?>
    <?php else: ?>
        labels.push("Sin datos");
        dataPagado.push(0);
        dataPendiente.push(0);
    <?php endif; ?>

    new Chart(histCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Recaudado ($)',
                    data: dataPagado,
                    backgroundColor: '#10b981', // green
                    borderRadius: 4
                },
                {
                    label: 'Pendiente/Vencido ($)',
                    data: dataPendiente,
                    backgroundColor: '#f59e0b', // orange
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            },
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 2. Gráfico de Composición (Donut)
    const distCtx = document.getElementById('distributionChart').getContext('2d');
    new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pagado', 'Pendiente', 'Vencido'],
            datasets: [{
                data: [
                    <?= (float)$stats['pagado'] ?>,
                    <?= (float)$stats['pendiente'] ?>,
                    <?= (float)$stats['vencido'] ?>
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
