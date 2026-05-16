<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pdo = getPDO();
$msg = $msg_type = '';

// Crear libro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $anio      = (int) ($_POST['anio'] ?? 0);
    $isbn      = trim($_POST['isbn'] ?? '') ?: null;
    $desc      = trim($_POST['descripcion'] ?? '') ?: null;
    $cantidad  = max(1, (int) ($_POST['cantidad_total'] ?? 1));

    if ($titulo && $autor && $anio > 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO libros (titulo, autor, anio, isbn, descripcion, cantidad_total, cantidad_disponible)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$titulo, $autor, $anio, $isbn, $desc, $cantidad, $cantidad]);
        $msg = 'Libro creado correctamente.';
        $msg_type = 'ok';
    } else {
        $msg = 'Los campos Título, Autor y Año son obligatorios.';
        $msg_type = 'err';
    }
}

// Eliminar libro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $id = (int) $_POST['id'];
    // Verificar préstamos activos
    $chk = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND estado = 'Activo'");
    $chk->execute([$id]);
    if ((int) $chk->fetchColumn() > 0) {
        $msg = 'No se puede eliminar: el libro tiene préstamos activos.';
        $msg_type = 'err';
    } else {
        $pdo->prepare('DELETE FROM libros WHERE id = ?')->execute([$id]);
        $msg = 'Libro eliminado correctamente.';
        $msg_type = 'ok';
    }
}

// Listado con búsqueda
$buscar = trim($_GET['buscar'] ?? '');
$disp   = $_GET['disponible'] ?? '';

$where  = ['1=1'];
$params = [];

if ($buscar) {
    $where[] = '(titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?)';
    $term = "%$buscar%";
    $params = array_merge($params, [$term, $term, $term]);
}
if ($disp !== '') {
    $where[] = 'disponible = ?';
    $params[] = (int) $disp;
}

$sql = 'SELECT * FROM libros WHERE ' . implode(' AND ', $where) . ' ORDER BY titulo ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$libros = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">📚 Libros</h1>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Formulario crear -->
<div class="form-card">
    <h3>➕ Añadir nuevo libro</h3>
    <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required>
        </div>
        <div class="form-group">
            <label>Autor *</label>
            <input type="text" name="autor" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Año *</label>
                <input type="number" name="anio" min="1000" max="<?= date('Y') ?>" required>
            </div>
            <div class="form-group">
                <label>Cantidad total</label>
                <input type="number" name="cantidad_total" min="1" value="1">
            </div>
        </div>
        <div class="form-group">
            <label>ISBN</label>
            <input type="text" name="isbn" placeholder="Opcional">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" rows="2" style="resize:vertical"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:14px">Añadir libro</button>
    </form>
</div>

<!-- Buscador -->
<form method="GET" class="search-bar">
    <input type="text" name="buscar" placeholder="🔍  Buscar por título, autor o ISBN…" value="<?= htmlspecialchars($buscar) ?>">
    <select name="disponible">
        <option value="">Todos</option>
        <option value="1" <?= $disp === '1' ? 'selected' : '' ?>>Disponibles</option>
        <option value="0" <?= $disp === '0' ? 'selected' : '' ?>>No disponibles</option>
    </select>
    <button type="submit" class="btn btn-primary">Filtrar</button>
</form>

<!-- Tabla -->
<div class="table-wrap">
    <div class="table-header">
        <h3><?= count($libros) ?> libro<?= count($libros) !== 1 ? 's' : '' ?></h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Título</th><th>Autor</th>
                <th>Año</th><th>ISBN</th><th>Disponibles</th><th>Total</th><th>Estado</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($libros as $l): ?>
            <tr>
                <td><?= $l['id'] ?></td>
                <td><?= htmlspecialchars($l['titulo']) ?></td>
                <td><?= htmlspecialchars($l['autor']) ?></td>
                <td><?= $l['anio'] ?></td>
                <td><?= htmlspecialchars($l['isbn'] ?? '—') ?></td>
                <td><?= $l['cantidad_disponible'] ?></td>
                <td><?= $l['cantidad_total'] ?></td>
                <td>
                    <span class="badge <?= $l['disponible'] ? 'badge-ok' : 'badge-error' ?>">
                        <?= $l['disponible'] ? 'Disponible' : 'No disponible' ?>
                    </span>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($l['titulo'])) ?>»?')">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
