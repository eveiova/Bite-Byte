<?php
require_once 'config.php';

// si ya está logado lo mando fuera
if(isset($_SESSION['id'])){
    $dest = ($_SESSION['rol'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_cliente.php';
    header('Location: '.$dest);
    exit;
}

$err = '';

if(!empty($_GET['redirect']))
    $_SESSION['volver_a'] = basename($_GET['redirect']);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    if(!$email || !$pass){
        $err = 'Por favor, completa todos los campos.';
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $err = 'El formato del email no es válido.';
    } else {
        $q = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $q->execute([$email]);
        $u = $q->fetch();

        if(!$u){
            $err = 'Credenciales incorrectas. Inténtalo de nuevo.';
        } elseif(!$u['activo']){
            $err = 'Tu cuenta está desactivada. Contacta con el laboratorio.';
        } elseif(!password_verify($pass, $u['password_hash'])){
            $err = 'Credenciales incorrectas. Inténtalo de nuevo.';
        } else {
            session_regenerate_id(true);
            $_SESSION['id']     = $u['id'];
            $_SESSION['nombre'] = $u['nombre'];
            $_SESSION['email']  = $u['email'];
            $_SESSION['rol']    = $u['rol'];

            // redirigir donde estaba o al dashboard
            if(isset($_SESSION['volver_a'])){
                $destino = $_SESSION['volver_a'];
                unset($_SESSION['volver_a']);
            } else {
                $destino = ($u['rol'] == 'admin') ? 'dashboard_admin.php' : 'dashboard_cliente.php';
            }
            header("Location: $destino");
            exit;
        }
    }
}

$email_prev = htmlspecialchars($_POST['email'] ?? '');
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
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    <a href="index.php" style="text-decoration:none; color:inherit; display:block">
        <div class="login-card-header">
            <div class="logo-brand-text">
                <div class="brand-name">Casa Denise</div>
                <div class="brand-sub">Laboratorio Dental</div>
            </div>
            <p class="mt-3 mb-0" style="color:#d4a5b0; font-size:.82rem; letter-spacing:1px">ACCESO A TU CUENTA</p>
        </div>
    </a>

    <div class="login-card-body">

        <?php if($err): ?>
            <div class="alert-login mb-4">⚠️ <?= $err ?></div>
        <?php endif ?>

        <form method="POST" action="login.php" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="tu@email.com" value="<?= $email_prev ?>"
                    autocomplete="email" required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password"
                        class="form-control" placeholder="••••••••"
                        autocomplete="current-password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <hr style="border-color:#ead8df; margin:1.8rem 0 1.4rem">

        <div class="text-center">
            <span style="font-size:.85rem; color:#a07a8a">¿Aún no tienes cuenta?</span>
            <a href="register.php" class="link-registro ms-2">Regístrate</a>
        </div>

    </div>
  </div>
</div>

<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
