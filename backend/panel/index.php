<?php
session_start();
if (isset($_SESSION['panel_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, nombre, email, password, tipo, activo FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            if (!$usuario['activo']) {
                $error = 'Tu cuenta está desactivada.';
            } elseif ($usuario['tipo'] !== 'Bibliotecario') {
                $error = 'Solo los bibliotecarios pueden acceder al panel.';
            } else {
                unset($usuario['password']);
                $_SESSION['panel_usuario'] = $usuario;
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    } else {
        $error = 'Rellena todos los campos.';
    }
}

$url_error = $_GET['error'] ?? '';
if ($url_error === 'acceso') $error = 'Acceso denegado: se requiere rol Bibliotecario.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Panel Biblioteca</title>
    <style>
        :root { --bg:#1e1e2e; --panel:#2a2a3e; --accent:#7c6ff7; --text:#cdd6f4; --text-dim:#6c7086; --entry:#313244; --error:#f38ba8; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: var(--panel); border-radius: 10px; padding: 40px; width: 360px; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo .icon { font-size: 2.5rem; }
        .logo h1 { font-size: 1.3rem; margin-top: 6px; }
        .logo p { font-size: 0.8rem; color: var(--text-dim); margin-top: 2px; }
        label { display: block; font-size: 0.8rem; color: var(--text-dim); margin-bottom: 4px; margin-top: 14px; }
        input { width: 100%; padding: 9px 12px; background: var(--entry); border: 1px solid #45475a; border-radius: 5px; color: var(--text); font-family: inherit; font-size: 0.9rem; }
        input:focus { outline: none; border-color: var(--accent); }
        button { width: 100%; margin-top: 20px; padding: 10px; background: var(--accent); color: #fff; border: none; border-radius: 5px; font-family: inherit; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #9d98f5; }
        .error { background: rgba(243,139,168,0.12); color: var(--error); border-left: 3px solid var(--error); padding: 8px 12px; border-radius: 4px; margin-top: 14px; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="icon">📚</div>
        <h1>Panel de Administración</h1>
        <p>Sistema de Gestión de Biblioteca</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Correo electrónico</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@biblioteca.com') ?>" required autofocus>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <button type="submit">Entrar al panel</button>
    </form>
</div>
</body>
</html>
