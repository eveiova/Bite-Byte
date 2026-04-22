<?php
require_once 'config.php';

if (isset($_SESSION['id'])) {
    header('Location: dashboard_cliente.php');
    exit;
}

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password_confirm'] ?? '');

    if (empty($nombre) || strlen($nombre) < 2) {
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

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol) VALUES (?, ?, ?, ?, 'cliente')");
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
    <script src="js/script.js?v=1"></script>
</head>
<body>

<div class="register-wrapper">
    <div class="register-card">
        <a href="index.php" style="text-decoration: none; color: inherit; display: block;">
        <div class="register-card-header">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
            <p class="mt-3 mb-0" style="color:#d4a5b0; font-size:0.82rem; letter-spacing:1px;">
                CREAR NUEVA CUENTA
            </p>
        </div>
        </a>
        <div class="register-card-body">

            <?php if ($exito): ?>
            <div class="exito-box">
                <div class="exito-icon">🎉</div>
                <h5 class="texto-bordeo mb-2">¡Cuenta creada!</h5>
                <p class="text-muted mb-4" style="font-size:0.9rem;">
                    Tu cuenta ha sido registrada correctamente.<br>Ya puedes iniciar sesión.
                </p>
                <a href="login.php" class="btn-register" style="display:inline-block; width:auto; padding:0.75rem 2rem; text-decoration:none;">
                    Ir al Login
                </a>
            </div>

            <?php else: ?>
            <form method="POST" action="register.php" novalidate>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" id="nombre" name="nombre"
                            class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : (isset($_POST['nombre']) && !isset($errores['nombre']) ? 'is-valid' : '') ?>"
                            placeholder="Ana"
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                            autocomplete="given-name">
                        <?php if (isset($errores['nombre'])): ?>
                            <div class="campo-error"><?= $errores['nombre'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" id="apellido" name="apellido"
                            class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : (isset($_POST['apellido']) && !isset($errores['apellido']) ? 'is-valid' : '') ?>"
                            placeholder="García"
                            value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>"
                            autocomplete="family-name">
                        <?php if (isset($errores['apellido'])): ?>
                            <div class="campo-error"><?= $errores['apellido'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                        class="form-control <?= isset($errores['email']) ? 'is-invalid' : (isset($_POST['email']) && !isset($errores['email']) ? 'is-valid' : '') ?>"
                        placeholder="tu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email">
                    <?php if (isset($errores['email'])): ?>
                        <div class="campo-error"><?= $errores['email'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password"
                            class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>"
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password"
                            oninput="medirFuerza(this.value)">
                        <button type="button" class="toggle-password" onclick="togglePass('password', this)">👁</button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text text-muted" id="strengthText"></div>
                    <?php if (isset($errores['password'])): ?>
                        <div class="campo-error"><?= $errores['password'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Repetir Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm"
                            class="form-control <?= isset($errores['password_confirm']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••"
                            autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePass('password_confirm', this)">👁</button>
                    </div>
                    <?php if (isset($errores['password_confirm'])): ?>
                        <div class="campo-error"><?= $errores['password_confirm'] ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-register">Crear Cuenta</button>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
