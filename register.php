<?php
// ============================================================
//  CASA DENISE - Registro de nuevos clientes
//  Coloca este archivo en: tu-proyecto/register.php
// ============================================================

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir a su panel
if (estaLogueado()) {
    $destino = $_SESSION['usuario_rol'] === 'admin'
        ? 'dashboard_admin.php'
        : 'dashboard_cliente.php';
    header("Location: $destino");
    exit;
}

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Recoger y limpiar datos ──────────────────────────────
    $nombre   = trim($_POST['nombre']            ?? '');
    $apellido = trim($_POST['apellido']           ?? '');
    $email    = trim($_POST['email']              ?? '');
    $password = trim($_POST['password']           ?? '');
    $password2= trim($_POST['password_confirm']   ?? '');

    // ── Validaciones ─────────────────────────────────────────
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (strlen($nombre) < 2) {
        $errores['nombre'] = 'El nombre debe tener al menos 2 caracteres.';
    }

    if (empty($apellido)) {
        $errores['apellido'] = 'El apellido es obligatorio.';
    }

    if (empty($email)) {
        $errores['email'] = 'El email es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El formato del email no es válido.';
    } else {
        // Comprobar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errores['email'] = 'Este email ya está registrado.';
        }
    }

    if (empty($password)) {
        $errores['password'] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 8) {
        $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errores['password'] = 'La contraseña debe tener al menos una mayúscula.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errores['password'] = 'La contraseña debe tener al menos un número.';
    }

    if (empty($password2)) {
        $errores['password_confirm'] = 'Confirma tu contraseña.';
    } elseif ($password !== $password2) {
        $errores['password_confirm'] = 'Las contraseñas no coinciden.';
    }

    // ── Si no hay errores, insertar usuario ──────────────────
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password_hash, rol)
            VALUES (?, ?, ?, ?, 'cliente')
        ");
        $stmt->execute([$nombre, $apellido, $email, $hash]);

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(91, 14, 31, 0.12);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
        }

        .register-card-header {
            background: linear-gradient(135deg, #5b0e1f 0%, #3b0a14 100%);
            padding: 2rem 2rem 1.8rem;
            text-align: center;
            border-bottom: 3px solid #f5d98b;
        }

        .register-card-header .brand-name { font-size: 2.8rem; line-height: 1; }

        .register-card-body { padding: 2rem 2.5rem 2.5rem; }

        .form-label {
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #5a4b3b;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .form-control {
            border: 1.5px solid #d4a5b0;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-family: "Josefin Sans", sans-serif;
            color: #5a4b3b;
            background: #fdf8f2;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #800020;
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
            background: #fff;
            outline: none;
        }

        /* Campo con error */
        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff8f8;
        }
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        /* Campo correcto */
        .form-control.is-valid {
            border-color: #198754;
            background: #f8fff9;
        }

        .campo-error {
            font-size: 0.78rem;
            color: #dc3545;
            margin-top: 0.3rem;
        }

        /* Indicador de fuerza de contraseña */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all 0.3s;
            background: #e9ecef;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
        }
        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .btn-register {
            background: linear-gradient(135deg, #800020 0%, #5b0e1f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-family: "Josefin Sans", sans-serif;
            font-size: 0.85rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            width: 100%;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-register:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            color: #f5d98b;
        }
        .btn-register:active { transform: translateY(0); }

        .link-login { color: #800020; font-size: 0.85rem; text-decoration: none; }
        .link-login:hover { color: #5b0e1f; text-decoration: underline; }

        /* Mensaje de éxito */
        .exito-box {
            text-align: center;
            padding: 1rem 0;
        }
        .exito-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        /* Toggle contraseña */
        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #a07a8a;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
        }
        .toggle-password:hover { color: #800020; }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-card">

        <!-- Cabecera -->
        <div class="register-card-header">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
            <p class="mt-3 mb-0" style="color:#d4a5b0; font-size:0.82rem; letter-spacing:1px;">
                CREAR NUEVA CUENTA
            </p>
        </div>

        <div class="register-card-body">

            <?php if ($exito): ?>
            <!-- ── Pantalla de éxito ── -->
            <div class="exito-box">
                <div class="exito-icon">🎉</div>
                <h5 class="texto-bordeo mb-2">¡Cuenta creada!</h5>
                <p class="text-muted mb-4" style="font-size:0.9rem;">
                    Tu cuenta ha sido registrada correctamente.<br>
                    Ya puedes iniciar sesión.
                </p>
                <a href="login.php" class="btn-register" style="display:inline-block; width:auto; padding:0.75rem 2rem; text-decoration:none;">
                    Ir al Login
                </a>
            </div>

            <?php else: ?>
            <!-- ── Formulario ── -->
            <form method="POST" action="register.php" novalidate id="formRegistro">

                <!-- Nombre y Apellido en fila -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : (isset($_POST['nombre']) && !isset($errores['nombre']) ? 'is-valid' : '') ?>"
                            placeholder="Ana"
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                            autocomplete="given-name"
                        >
                        <?php if (isset($errores['nombre'])): ?>
                            <div class="campo-error"><?= $errores['nombre'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : (isset($_POST['apellido']) && !isset($errores['apellido']) ? 'is-valid' : '') ?>"
                            placeholder="García"
                            value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>"
                            autocomplete="family-name"
                        >
                        <?php if (isset($errores['apellido'])): ?>
                            <div class="campo-error"><?= $errores['apellido'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control <?= isset($errores['email']) ? 'is-invalid' : (isset($_POST['email']) && !isset($errores['email']) ? 'is-valid' : '') ?>"
                        placeholder="tu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                    >
                    <?php if (isset($errores['email'])): ?>
                        <div class="campo-error"><?= $errores['email'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Contraseña -->
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password"
                            oninput="medirFuerza(this.value)"
                        >
                        <button type="button" class="toggle-password"
                                onclick="togglePass('password', this)">👁</button>
                    </div>
                    <!-- Barra de fuerza -->
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text text-muted" id="strengthText"></div>
                    <?php if (isset($errores['password'])): ?>
                        <div class="campo-error"><?= $errores['password'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Confirmar contraseña -->
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Repetir Contraseña</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control <?= isset($errores['password_confirm']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password"
                                onclick="togglePass('password_confirm', this)">👁</button>
                    </div>
                    <?php if (isset($errores['password_confirm'])): ?>
                        <div class="campo-error"><?= $errores['password_confirm'] ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-register">
                    Crear Cuenta
                </button>

            </form>

            <hr style="border-color: #ead8df; margin: 1.8rem 0 1.4rem;">

            <div class="text-center">
                <span style="font-size:0.85rem; color:#a07a8a;">¿Ya tienes cuenta?</span>
                <a href="login.php" class="link-login ms-2">Inicia sesión</a>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Toggle mostrar/ocultar contraseña
function togglePass(id, btn) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

// Medidor de fuerza de contraseña
function medirFuerza(password) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');

    let puntos = 0;
    if (password.length >= 8)              puntos++;
    if (password.length >= 12)             puntos++;
    if (/[A-Z]/.test(password))            puntos++;
    if (/[0-9]/.test(password))            puntos++;
    if (/[^A-Za-z0-9]/.test(password))    puntos++;

    const niveles = [
        { pct: '0%',   color: 'transparent', label: '' },
        { pct: '25%',  color: '#dc3545',     label: '⚠️ Muy débil' },
        { pct: '50%',  color: '#fd7e14',     label: '🔶 Débil' },
        { pct: '75%',  color: '#ffc107',     label: '🔷 Aceptable' },
        { pct: '90%',  color: '#20c997',     label: '✅ Fuerte' },
        { pct: '100%', color: '#198754',     label: '🔒 Muy fuerte' },
    ];

    const nivel = niveles[Math.min(puntos, 5)];
    bar.style.width       = password.length > 0 ? nivel.pct : '0%';
    bar.style.background  = nivel.color;
    text.textContent      = password.length > 0 ? nivel.label : '';
    text.style.color      = nivel.color;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
