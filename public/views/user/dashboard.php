<?php
$pageTitle  = 'Mi Estado de Cuenta';
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script>(function(){try{var t=localStorage.getItem('ct-theme');if(t==='light'||t==='dark'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> – Control Tributario Municipal</title>
    <meta name="theme-color" content="#6d1f2b">
    <meta name="app-base-path" content="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>">
    <link rel="manifest" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/icon-192.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto+Slab:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/css/app.css">
    <script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/toast.js" defer></script>
</head>
<body class="app-shell">

<nav class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/logo-pingo-light.png" alt="Logo" style="width: 46px; height: auto; flex-shrink: 0;">
        <h1>Control Tributario<span>Mi Cuenta</span></h1>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Mi cuenta</div>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/user/dashboard" class="nav-link active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Estado de Cuenta
            </a>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar"><?= strtoupper(substr($userName ?? 'U', 0, 2)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName ?? '') ?></div>
                <div class="user-role">Comercio</div>
            </div>
            <button type="button" class="icon-btn" style="width:32px;height:32px;border:none;" title="Cambiar mi contraseña" data-modal-open="modal-cambiar-password">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            </button>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/logout" class="icon-btn" style="width:32px;height:32px;border:none;" title="Salir">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </div>
</nav>

<?php require __DIR__ . '/../partials/change_password_modal.php'; ?>

<div class="main-content">
    <header class="top-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <button class="icon-btn" id="mobile-menu-toggle" style="display:none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <h2><?= $pageTitle ?></h2>
        </div>
        <div class="header-actions">
            <button class="icon-btn theme-toggle" id="theme-toggle" title="Cambiar tema claro/oscuro">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
            </button>
            <div style="position:relative;">
                <button class="icon-btn" id="notif-bell">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span class="badge"><?= $notifCount ?></span><?php endif; ?>
                </button>
                <div class="notif-panel" id="notif-panel">
                    <div class="notif-header" style="display:flex;align-items:center;justify-content:space-between;">
                        <span>Notificaciones</span>
                        <?php if ($notifCount > 0): ?>
                            <button type="button" id="btn-marcar-todas-leidas" style="background:none;border:none;color:var(--brand-primary);font-size:0.68rem;text-transform:none;letter-spacing:0;cursor:pointer;padding:0;">Marcar todas como leídas</button>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($notificaciones)): ?>
                        <div class="empty-state">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
                            <p>Sin notificaciones</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($notificaciones as $n): ?>
                        <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" data-notif-id="<?= $n['id'] ?>" data-read="<?= $n['is_read'] ? '1' : '0' ?>">
                            <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="notif-text"><?= htmlspecialchars($n['message']) ?></div>
                            <div class="notif-time"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['flash_success']) ?>, 'success'));</script>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['flash_error']) ?>, 'error'));</script>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-danger">
                <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                <div class="stat-label">Deuda Pendiente</div>
                <div class="stat-value">$ <?= number_format((float)$deudaTotal,2,',','.') ?></div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-label">Facturas Pendientes</div>
                <div class="stat-value"><?= $facturasPendientes ?></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="stat-label">Facturas Pagadas</div>
                <div class="stat-value"><?= $facturasPagadas ?></div>
            </div>
        </div>

        <!-- Facturas -->
        <div class="card">
            <div class="card-header"><h3>Mis Facturas</h3></div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr><th># Factura</th><th>Período</th><th>Emisión</th><th>Vencimiento</th><th>Importe</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php if (empty($facturas)): ?>
                        <tr><td colspan="7" class="empty-state empty-success">
                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <p>Estás al día — todavía no tenés ninguna factura generada ni deuda pendiente.</p>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($facturas as $f):
                        $sc = match($f['status']){'paid'=>'status-paid','pending'=>'status-pending','overdue'=>'status-overdue',default=>'status-cancelled'};
                        $sl = match($f['status']){'paid'=>'Pagado','pending'=>'Pendiente','overdue'=>'Vencido','cancelled'=>'Cancelado',default=>$f['status']};
                    ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($f['invoice_number']) ?></td>
                            <td><?= htmlspecialchars($f['period'] ?? '–') ?></td>
                            <td><?= date('d/m/Y', strtotime($f['issue_date'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($f['due_date'])) ?></td>
                            <?php 
                            $moraData = \App\Controllers\InvoiceController::calculateMora($f);
                            $displayTotal = ($f['status'] !== 'paid' && $f['status'] !== 'cancelled') ? $moraData['total_amount'] : $f['total_amount'];
                            ?>
                            <td style="font-weight:600;">$ <?= number_format((float)$displayTotal,2,',','.') ?></td>
                            <td><span class="status-badge <?= $sc ?>"><span class="status-dot"></span><?= $sl ?></span></td>
                            <td>
                                <div class="cell-actions" style="display:flex;align-items:center;gap:0.35rem;">
                                    <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/user/facturas/pdf/<?= $f['id'] ?>" class="btn btn-ghost btn-sm" title="Descargar PDF de Boleta" style="padding: 0.35rem 0.6rem;" target="_blank">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>
                                    <?php if ($f['status'] === 'paid' && !empty($f['payment_id'])): ?>
                                        <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/user/facturas/recibo/<?= $f['payment_id'] ?>" class="btn btn-ghost btn-sm" title="Descargar Recibo de Pago" style="padding: 0.35rem 0.6rem;" target="_blank">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/app.js"></script>
</body>
</html>
