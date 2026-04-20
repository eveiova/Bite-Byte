<?php
// ============================================================
//  CASA DENISE - Login
//  Coloca este archivo en: tu-proyecto/login.php
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validación básica
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';

    } else {
        // Buscar usuario por email
        $stmt = $pdo->prepare("
            SELECT id, nombre, email, password_hash, rol, activo
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            // No revelar si el email existe o no (seguridad)
            $error = 'Credenciales incorrectas. Inténtalo de nuevo.';

        } elseif (!$usuario['activo']) {
            $error = 'Tu cuenta está desactivada. Contacta con el laboratorio.';

        } elseif (!password_verify($password, $usuario['password_hash'])) {
            $error = 'Credenciales incorrectas. Inténtalo de nuevo.';

        } else {
            // Login correcto: regenerar session ID (previene session fixation)
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            // Redirigir a donde intentaba ir (o al panel por defecto)
            $destino = $_SESSION['redirect_after_login']
                ?? ($usuario['rol'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_cliente.php');
            unset($_SESSION['redirect_after_login']);

            header("Location: $destino");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Casa Denise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@700&family=Josefin+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* ── Página de login ── */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(91, 14, 31, 0.12);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
        }

        /* Franja superior decorativa */
        .login-card-header {
            background: linear-gradient(135deg, #5b0e1f 0%, #3b0a14 100%);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            border-bottom: 3px solid #f5d98b;
        }

        .login-card-header .brand-name {
            font-size: 3rem;
            line-height: 1;
        }

        .login-card-header .brand-sub {
            font-size: 0.65rem;
        }

        .login-card-body {
            padding: 2.2rem 2.5rem 2.5rem;
        }

        /* Campo de formulario */
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

        /* Botón submit */
        .btn-login {
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

        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            color: #f5d98b;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Enlace registro */
        .link-registro {
            color: #800020;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .link-registro:hover {
            color: #5b0e1f;
            text-decoration: underline;
        }

        /* Alerta de error */
        .alert-login {
            background: #fdf0f2;
            border: 1.5px solid #d4a5b0;
            border-left: 4px solid #800020;
            border-radius: 10px;
            color: #5b0e1f;
            font-size: 0.88rem;
            padding: 0.75rem 1rem;
        }

        /* Toggle mostrar contraseña */
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

<div class="login-wrapper">
    <div class="login-card">

        <!-- Cabecera con la marca -->
        <div class="login-card-header">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
            <p class="mt-3 mb-0" style="color:#d4a5b0; font-size:0.82rem; letter-spacing:1px;">
                ACCESO A TU CUENTA
            </p>
        </div>

        <!-- Formulario -->
        <div class="login-card-body">

            <?php if ($error): ?>
                <div class="alert-login mb-4">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="tu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Mostrar contraseña"
                        >👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Entrar
                </button>

            </form>

            <hr style="border-color: #ead8df; margin: 1.8rem 0 1.4rem;">

            <div class="text-center">
                <span style="font-size:0.85rem; color:#a07a8a;">¿Aún no tienes cuenta?</span>
                <a href="register.php" class="link-registro ms-2">Regístrate</a>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const btn   = document.querySelector('.toggle-password');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
