<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Contribuyente – Municipio de El Pingo</title>
    <meta name="description" content="Acceso al Sistema de Control Tributario Municipal">
    <meta name="theme-color" content="#6d1f2b">
    <meta name="app-base-path" content="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <link rel="manifest" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto+Slab:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/css/app.css">
    <script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/toast.js" defer></script>
    <style>
        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        .login-split {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
        }

        .login-split > * {
            min-width: 0;
        }

        /* ── Panel institucional (izquierda) ── */
        .login-brand-panel {
            position: relative;
            background-color: var(--brand-primary);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 3.5rem;
            overflow: hidden;
        }

        /* Textura sutil: líneas diagonales finas, evocando vías de tren */
        .login-brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                135deg,
                rgba(255,255,255,0.035) 0px,
                rgba(255,255,255,0.035) 1px,
                transparent 1px,
                transparent 14px
            );
            pointer-events: none;
        }

        .login-brand-panel::after {
            content: '';
            position: absolute;
            right: -120px;
            bottom: -120px;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,0.12);
            pointer-events: none;
        }

        .login-brand-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .login-brand-top img {
            width: 52px;
            height: auto;
        }

        .login-brand-top .brand-name {
            font-family: var(--font-heading);
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .login-brand-top .brand-name span {
            display: block;
            font-family: var(--font-body);
            font-size: 0.68rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.7);
            margin-top: 0.15rem;
        }

        .login-brand-mid {
            position: relative;
            z-index: 1;
            max-width: 420px;
        }

        .login-brand-mid h2 {
            font-family: var(--font-heading);
            color: #ffffff;
            font-size: 2rem;
            line-height: 1.25;
            margin-bottom: 1rem;
        }

        .login-brand-mid p {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.78);
            line-height: 1.6;
        }

        .login-brand-bottom {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 2.5rem;
            border-top: 1px solid rgba(255,255,255,0.15);
            padding-top: 1.5rem;
        }

        .login-brand-stat .num {
            font-family: var(--font-heading);
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand-secondary);
        }

        .login-brand-stat .label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.65);
            margin-top: 0.15rem;
        }

        /* ── Panel de formulario (derecha) ── */
        .login-form-panel {
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-form-wrap {
            width: 100%;
            min-width: 0;
            max-width: 360px;
        }

        .login-form-wrap h1 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--slate-dark);
            margin-bottom: 0.35rem;
        }

        .login-form-wrap .login-subtitle {
            font-size: 0.82rem;
            color: var(--slate-medium);
            margin-bottom: 2rem;
        }

        .login-footer-note {
            font-size: 0.7rem;
            color: #a89e94;
            margin-top: 2rem;
            border-top: 1px solid var(--slate-border);
            padding-top: 1rem;
        }

        @media (max-width: 860px) {
            .login-split {
                grid-template-columns: 1fr;
            }
            .login-brand-panel {
                padding: 2.5rem 1.75rem;
                min-height: 260px;
            }
            .login-brand-mid h2 {
                font-size: 1.5rem;
            }
            .login-brand-bottom {
                display: none;
            }
            .login-form-panel {
                padding: 2.5rem 1.75rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-split">

        <!-- Panel institucional -->
        <div class="login-brand-panel anim-in">
            <div class="login-brand-top">
                <img src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/images/logo-pingo-light.png" alt="Logo Municipio de El Pingo">
                <div class="brand-name">
                    Municipio de El Pingo
                    <span>Estación El Pingo</span>
                </div>
            </div>

            <div class="login-brand-mid">
                <h2>Control Tributario Municipal</h2>
                <p>Consultá tu estado de cuenta, generá tus comprobantes y regularizá tus tasas comerciales desde un mismo lugar.</p>
            </div>

            <div class="login-brand-bottom">
                <div class="login-brand-stat">
                    <div class="num">100%</div>
                    <div class="label">Digital</div>
                </div>
                <div class="login-brand-stat">
                    <div class="num">24/7</div>
                    <div class="label">Disponible</div>
                </div>
                <div class="login-brand-stat">
                    <div class="num">Seguro</div>
                    <div class="label">Acceso protegido</div>
                </div>
            </div>
        </div>

        <!-- Panel de formulario -->
        <div class="login-form-panel">
            <div class="login-form-wrap anim-in" style="animation-delay: 0.1s;">
                <h1>Acceso Contribuyente</h1>
                <p class="login-subtitle">Ingresá con tu CUIT y contraseña para continuar.</p>

                <form id="login-form" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                    <div class="form-group">
                        <label class="form-label" for="cuit">CUIT del Comercio / Contribuyente</label>
                        <input type="text" id="cuit" name="cuit" class="form-input"
                               placeholder="20-12345678-9" required autofocus>
                        <span style="font-size: 0.7rem; color: var(--slate-medium); display: block; margin-top: 0.25rem;">
                            Ingrese el CUIT. Los guiones se agregarán automáticamente.
                        </span>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="password">Contraseña (Clave Fiscal)</label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" class="form-input"
                                   placeholder="••••••••" required style="padding-right: 2.5rem;">
                            <button type="button" id="toggle-password" tabindex="-1" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--slate-medium); cursor: pointer; padding: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; font-size: 0.8rem; color: var(--slate-dark); cursor: pointer;">
                            <input type="checkbox" name="remember_me" value="1">
                            Mantener mi sesión iniciada
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-login" style="width: 100%; padding: 0.75rem;">
                        Ingresar al Panel
                    </button>
                </form>

                <div class="login-footer-note">
                    Municipio de El Pingo &copy; <?= date('Y') ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/app.js"></script>
    <script>
        <?php if (!empty($_SESSION['login_error'])): ?>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['login_error']) ?>, 'error'));
        <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('login-form');
            const passInput = document.getElementById('password');
            const toggleBtn = document.getElementById('toggle-password');
            const btnLogin = document.getElementById('btn-login');

            // CUIT Mask
            document.getElementById('cuit').addEventListener('input', function (e) {
                let val = this.value.replace(/\D/g, '');
                if (val.length > 11) val = val.substring(0, 11);

                if (val.length > 2 && val.length <= 10) {
                    val = val.substring(0, 2) + '-' + val.substring(2);
                } else if (val.length > 10) {
                    val = val.substring(0, 2) + '-' + val.substring(2, 10) + '-' + val.substring(10);
                }
                this.value = val;
            });

            // Toggle Password Visibility
            toggleBtn.addEventListener('click', () => {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                if (type === 'text') {
                    toggleBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>';
                } else {
                    toggleBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
                }
            });

            // AJAX Form Submit
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const originalBtnText = btnLogin.innerHTML;
                btnLogin.disabled = true;
                btnLogin.innerHTML = '<span class="spinner" style="border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; width: 1rem; height: 1rem; display: inline-block; animation: spin 1s linear infinite; vertical-align: middle; margin-right: 0.5rem;"></span> Procesando...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/login', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        showToast(result.error || 'Credenciales inválidas.', 'error');
                        btnLogin.disabled = false;
                        btnLogin.innerHTML = originalBtnText;
                        passInput.value = '';
                        passInput.focus();
                    }
                } catch (err) {
                    showToast('Error de conexión. Intente nuevamente.', 'error');
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = originalBtnText;
                }
            });
        });
    </script>
</body>
</html>
