<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Contribuyente – Municipio de El Pingo</title>
    <meta name="description" content="Acceso al Sistema de Control Tributario Municipal">
    <meta name="theme-color" content="#1b2129">
    <meta name="app-base-path" content="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>">
    <link rel="manifest" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/css/app.css">
    <style>
        /* Estructura específica para la ventana de login ultra premium y centrada */
        .login-fullscreen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(27, 33, 41, 0.5), rgba(27, 33, 41, 0.6)), url('<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/municipal_columns.png');
            background-size: cover;
            background-position: center;
            padding: 1.5rem;
            position: relative;
        }

        .login-card-classic {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(6px);
            border: 1px solid var(--slate-border);
            width: 100%;
            max-width: 440px;
            padding: 3.5rem 2.5rem 2.5rem 2.5rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            position: relative;
            margin-top: 40px; /* Espacio para el logo colgante superior */
        }

        /* Logotipo Colgante Centrado en el Card */
        .login-logo-hanging {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -55px; /* Sobresale hacia arriba */
            background-color: #ffffff;
            padding: 1.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--slate-border);
            text-align: center;
            width: 160px;
            z-index: 10;
        }

        .login-logo-hanging .logo-symbol {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--slate-dark);
            line-height: 1;
            letter-spacing: -0.05em;
            border-bottom: 1px solid var(--slate-border);
            padding-bottom: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .login-logo-hanging .logo-text {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--slate-medium);
            font-weight: 600;
            line-height: 1.2;
        }

        .login-title {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--slate-dark);
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .login-subtitle {
            font-size: 0.8rem;
            color: var(--slate-medium);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 2rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="login-fullscreen">
        <div class="login-card-classic">
            
            <!-- Logotipo Colgante de Inspiración Clásica -->
            <div class="login-logo-hanging">
                <div class="logo-symbol">MT</div>
                <div class="logo-text">DIRECCIÓN DE RENTAS<br><span style="font-size: 0.48rem; color:#8c9ba5;">MUNICIPAL</span></div>
            </div>

            <h2 class="login-title">Acceso Contribuyente</h2>
            <p class="login-subtitle">Control Tributario</p>

            <?php if (!empty($_SESSION['login_error'])): ?>
                <div class="flash-message flash-error" style="padding: 0.75rem 1rem; font-size: 0.8rem; margin-bottom: 1.5rem; text-align: left;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                    <?= htmlspecialchars($_SESSION['login_error']) ?>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off" style="text-align: left;">
                
                <div class="form-group">
                    <label class="form-label" for="cuit">CUIT del Comercio / Contribuyente</label>
                    <input type="text" id="cuit" name="cuit" class="form-input" 
                           placeholder="20-12345678-9" required autofocus>
                    <span style="font-size: 0.7rem; color: var(--slate-medium); display: block; margin-top: 0.25rem;">
                        Ingrese el CUIT con guiones.
                    </span>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label" for="password">Contraseña (Clave Fiscal)</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" id="btn-login" style="width: 100%; padding: 0.75rem;">
                    Ingresar al Panel
                </button>
            </form>

            <div style="font-size: 0.7rem; color: #8c9ba5; margin-top: 2rem; border-top: 1px solid var(--slate-border); padding-top: 1rem;">
                Municipio de El Pingo &copy; <?= date('Y') ?>
            </div>
            
        </div>
    </div>

    <script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/app.js"></script>
</body>
</html>
