<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = getPDO();

// Estadísticas
$total_libros      = $pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();
$libros_disp       = $pdo->query('SELECT COUNT(*) FROM libros WHERE disponible = 1')->fetchColumn();
$total_usuarios    = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$prestamos_activos = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo'")->fetchColumn();
$prestamos_retraso = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Retrasado'")->fetchColumn();

// Préstamos recientes
$stmt = $pdo->query(
    "SELECT p.id, u.nombre AS usuario, l.titulo AS libro,
            p.fecha_prestamo, p.fecha_devolucion_prevista, p.estado,
            CASE WHEN p.estado='Activo' AND NOW() > p.fecha_devolucion_prevista
                 THEN DATEDIFF(NOW(), p.fecha_devolucion_prevista) ELSE 0 END AS dias_retraso
     FROM prestamos p
     JOIN usuarios u ON u.id = p.usuario_id
     JOIN libros l   ON l.id = p.libro_id
     ORDER BY p.fecha_prestamo DESC
     LIMIT 10"
);
$recientes = $stmt->fetchAll();

$page = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">🏠 Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Libros</div>
        <div class="value"><?= $total_libros ?></div>
        <div class="sub"><?= $libros_disp ?> disponibles</div>
    </div>
    <div class="stat-card">
        <div class="label">Usuarios Registrados</div>
        <div class="value"><?= $total_usuarios ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Préstamos Activos</div>
        <div class="value"><?= $prestamos_activos ?></div>
    </div>
    <div class="stat-card">
        <div class="label">⚠️ Con Retraso</div>
        <div class="value" style="color:var(--error)"><?= $prestamos_retraso ?></div>
    </div>
</div>

<div class="table-wrap">
    <div class="table-header">
        <h3>Préstamos Recientes</h3>
        <a href="prestamos.php" class="btn btn-primary btn-sm">Ver todos</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Usuario</th><th>Libro</th>
                <th>Préstamo</th><th>Devolución Prevista</th><th>Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recientes as $p): ?>
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
                <td>
                    <span class="badge <?= $badge ?>">
                        <?= $p['estado'] ?>
                        <?= $p['dias_retraso'] > 0 ? " (+{$p['dias_retraso']}d)" : '' ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
