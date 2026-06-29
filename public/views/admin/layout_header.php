<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Admin' ?> – Control Tributario Municipal</title>
    <meta name="description" content="Panel de Administración del Sistema de Control Tributario Municipal">
    <meta name="theme-color" content="#1b2129">
    <meta name="app-base-path" content="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <link rel="manifest" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/css/app.css">
</head>
<body>

<!-- ═══ Sidebar ═══ -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">MT</div>
        <h1>
            Control Tributario
            <span>Panel de Administración</span>
        </h1>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/dashboard" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Gestión</div>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/comercios" class="nav-link <?= ($activePage ?? '') === 'comercios' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Comercios
            </a>

            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/facturas" class="nav-link <?= ($activePage ?? '') === 'facturas' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                </svg>
                Facturación
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Reportes</div>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/deuda" class="nav-link <?= ($activePage ?? '') === 'deuda' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="16"/>
                </svg>
                Indicadores
            </a>

            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/admin/cierre-caja" class="nav-link <?= ($activePage ?? '') === 'cierre-caja' ? 'active' : '' ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 14c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4z"/>
                </svg>
                Cierre de Caja
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar"><?= strtoupper(substr($userName ?? 'A', 0, 2)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName ?? 'Admin') ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole ?? 'admin') ?></div>
            </div>
            <a href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/logout" class="icon-btn" title="Cerrar sesión" style="width:32px;height:32px;border:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>
</nav>

<!-- ═══ Main Content ═══ -->
<div class="main-content">
    <!-- Top Header -->
    <header class="top-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <button class="icon-btn" id="mobile-menu-toggle" style="display:none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <h2><?= $pageTitle ?? 'Dashboard' ?></h2>
        </div>

        <div class="header-actions">
            <?php if (($activePage ?? '') !== 'dashboard'): ?>
                <!-- Botón de acción contextual se inyecta en cada vista -->
            <?php endif; ?>

            <div style="position:relative;">
                <button class="icon-btn" id="notif-bell">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>
                    </svg>
                    <?php if (($notifCount ?? 0) > 0): ?>
                        <span class="badge"><?= $notifCount ?></span>
                    <?php endif; ?>
                </button>

                <div class="notif-panel" id="notif-panel">
                    <div class="notif-header">Notificaciones</div>
                    <div style="max-height:300px;overflow-y:auto;">
                        <?php if (empty($notificaciones ?? [])): ?>
                            <div class="empty-state" style="padding:1.5rem;">
                                <p>Sin notificaciones</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash-message flash-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash-message flash-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- El contenido de cada página se inyecta aquí -->
