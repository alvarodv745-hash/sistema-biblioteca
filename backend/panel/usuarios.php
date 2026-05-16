<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = getPDO();
$msg = $msg_type = '';

// Crear usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $tipo     = $_POST['tipo'] === 'Bibliotecario' ? 'Bibliotecario' : 'Lector';

    if ($nombre && $email && $password) {
        $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $msg = 'Ese email ya está registrado.';
            $msg_type = 'err';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO usuarios (nombre, email, password, tipo) VALUES (?, ?, ?, ?)')
                ->execute([$nombre, $email, $hash, $tipo]);
            $msg = 'Usuario creado correctamente.';
            $msg_type = 'ok';
        }
    } else {
        $msg = 'Nombre, email y contraseña son obligatorios.';
        $msg_type = 'err';
    }
}

// Toggle activo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'toggle') {
    $id = (int) $_POST['id'];
    $pdo->prepare('UPDATE usuarios SET activo = NOT activo WHERE id = ?')->execute([$id]);
    $msg = 'Estado actualizado.';
    $msg_type = 'ok';
}

$usuarios = $pdo->query('SELECT id, nombre, email, tipo, activo, created_at FROM usuarios ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">👥 Usuarios</h1>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Formulario crear -->
<div class="form-card">
    <h3>➕ Nuevo usuario</h3>
    <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="nombre" required>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Contraseña *</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo">
                <option value="Lector">Lector</option>
                <option value="Bibliotecario">Bibliotecario</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:14px">Crear usuario</button>
    </form>
</div>

<!-- Tabla -->
<div class="table-wrap">
    <div class="table-header">
        <h3><?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? 's' : '' ?></h3>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Tipo</th><th>Estado</th><th>Registro</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['tipo'] === 'Bibliotecario' ? 'badge-accent' : '' ?>"><?= $u['tipo'] ?></span></td>
                <td><span class="badge <?= $u['activo'] ? 'badge-ok' : 'badge-error' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td><?= substr($u['created_at'], 0, 10) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="accion" value="toggle">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:var(--sel);color:var(--text)">
                            <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
