<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = getPDO();
$msg = $msg_type = '';

// Actualizar retrasados
$pdo->exec("UPDATE prestamos SET estado='Retrasado' WHERE estado='Activo' AND NOW() > fecha_devolucion_prevista");

// Registrar devolución
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'devolver') {
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE prestamos SET estado='Devuelto', fecha_devolucion_real=NOW() WHERE id=? AND estado!='Devuelto'")
        ->execute([$id]);
    $msg = 'Devolución registrada.';
    $msg_type = 'ok';
}

// Nuevo préstamo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $uid  = (int) $_POST['usuario_id'];
    $lid  = (int) $_POST['libro_id'];
    $dias = max(1, (int) ($_POST['dias'] ?? 15));

    $libro = $pdo->prepare('SELECT cantidad_disponible FROM libros WHERE id = ? LIMIT 1');
    $libro->execute([$lid]);
    $libro = $libro->fetch();

    if (!$libro || $libro['cantidad_disponible'] < 1) {
        $msg = 'El libro no está disponible.';
        $msg_type = 'err';
    } else {
        $fecha = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
        $pdo->prepare('INSERT INTO prestamos (usuario_id, libro_id, fecha_devolucion_prevista) VALUES (?, ?, ?)')
            ->execute([$uid, $lid, $fecha]);
        $msg = 'Préstamo registrado.';
        $msg_type = 'ok';
    }
}

// Filtros
$estado = $_GET['estado'] ?? '';
$where  = ['1=1'];
$params = [];
if ($estado) { $where[] = 'p.estado = ?'; $params[] = $estado; }

$stmt = $pdo->prepare(
    "SELECT p.id, u.nombre AS usuario, l.titulo AS libro,
            p.fecha_prestamo, p.fecha_devolucion_prevista, p.fecha_devolucion_real,
            p.estado,
            CASE WHEN p.estado='Activo' AND NOW() > p.fecha_devolucion_prevista
                 THEN DATEDIFF(NOW(), p.fecha_devolucion_prevista) ELSE 0 END AS dias_retraso
     FROM prestamos p
     JOIN usuarios u ON u.id = p.usuario_id
     JOIN libros   l ON l.id = p.libro_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY p.fecha_prestamo DESC"
);
$stmt->execute($params);
$prestamos = $stmt->fetchAll();

// Para el formulario de nuevo préstamo
$usuarios_lista = $pdo->query('SELECT id, nombre FROM usuarios WHERE activo=1 ORDER BY nombre')->fetchAll();
$libros_disp    = $pdo->query('SELECT id, titulo FROM libros WHERE disponible=1 ORDER BY titulo')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">📋 Préstamos</h1>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Nuevo préstamo -->
<div class="form-card">
    <h3>➕ Nuevo préstamo</h3>
    <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="form-group">
            <label>Usuario *</label>
            <select name="usuario_id" required>
                <option value="">— Selecciona usuario —</option>
                <?php foreach ($usuarios_lista as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Libro disponible *</label>
            <select name="libro_id" required>
                <option value="">— Selecciona libro —</option>
                <?php foreach ($libros_disp as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Días de préstamo</label>
            <input type="number" name="dias" value="15" min="1" max="90">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:14px">Registrar préstamo</button>
    </form>
</div>

<!-- Filtro -->
<form method="GET" class="search-bar">
    <select name="estado">
        <option value="">Todos los estados</option>
        <?php foreach (['Activo','Devuelto','Retrasado'] as $e): ?>
            <option value="<?= $e ?>" <?= $estado === $e ? 'selected' : '' ?>><?= $e ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filtrar</button>
    <?php if ($estado): ?>
        <a href="prestamos.php" class="btn" style="background:var(--sel);color:var(--text)">✕ Limpiar</a>
    <?php endif; ?>
</form>

<!-- Tabla -->
<div class="table-wrap">
    <div class="table-header">
        <h3><?= count($prestamos) ?> préstamo<?= count($prestamos) !== 1 ? 's' : '' ?></h3>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Usuario</th><th>Libro</th><th>Préstamo</th><th>Dev. Prevista</th><th>Dev. Real</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($prestamos as $p): ?>
            <?php
                $badge = match($p['estado']) {
                    'Activo'    => 'badge-ok',
                    'Devuelto'  => 'badge-accent',
                    'Retrasado' => 'badge-error',
                    default     => ''
                };
            ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['usuario']) ?></td>
                <td><?= htmlspecialchars($p['libro']) ?></td>
                <td><?= substr($p['fecha_prestamo'], 0, 10) ?></td>
                <td><?= substr($p['fecha_devolucion_prevista'], 0, 10) ?></td>
                <td><?= $p['fecha_devolucion_real'] ? substr($p['fecha_devolucion_real'], 0, 10) : '—' ?></td>
                <td>
                    <span class="badge <?= $badge ?>">
                        <?= $p['estado'] ?>
                        <?= $p['dias_retraso'] > 0 ? " (+{$p['dias_retraso']}d)" : '' ?>
                    </span>
                </td>
                <td>
                    <?php if ($p['estado'] !== 'Devuelto'): ?>
                    <form method="POST">
                        <input type="hidden" name="accion" value="devolver">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:rgba(166,227,161,0.12);color:var(--success)">↩ Devolver</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
