<?php
require_once __DIR__ . '/../config/Database.php';

class Libro
{

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Buscar por ID ──────────────────────────────────────────
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, titulo, autor, anio, isbn, descripcion,
                    cantidad_total, cantidad_disponible, disponible, created_at
             FROM libros WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── Listar con filtros y búsqueda ─────────────────────────
    public function listar(array $filtros = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['titulo'])) {
            $where[] = 'titulo LIKE ?';
            $params[] = '%' . $filtros['titulo'] . '%';
        }
        if (!empty($filtros['autor'])) {
            $where[] = 'autor LIKE ?';
            $params[] = '%' . $filtros['autor'] . '%';
        }
        if (isset($filtros['disponible']) && $filtros['disponible'] !== '') {
            $where[] = 'disponible = ?';
            $params[] = (int) $filtros['disponible'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?)';
            $term = '%' . $filtros['buscar'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $limite = min(100, max(1, (int) ($filtros['limite'] ?? 10)));
        $offset = ($pagina - 1) * $limite;

        $sql = 'SELECT id, titulo, autor, anio, isbn, descripcion,
                       cantidad_total, cantidad_disponible, disponible, created_at
                FROM libros
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY titulo ASC
                LIMIT ? OFFSET ?';

        $paramsQ = array_merge($params, [$limite, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($paramsQ);
        $datos = $stmt->fetchAll();

        // Total
        $stmtC = $this->db->prepare(
            'SELECT COUNT(*) FROM libros WHERE ' . implode(' AND ', $where)
        );
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        return ['datos' => $datos, 'total' => $total];
    }

    // ── Crear libro ────────────────────────────────────────────
    public function crear(array $data): array
    {
        // Verificar ISBN único (si se provee)
        if (!empty($data['isbn'])) {
            $chk = $this->db->prepare('SELECT id FROM libros WHERE isbn = ?');
            $chk->execute([$data['isbn']]);
            if ($chk->fetch()) {
                throw new RuntimeException('El ISBN ya existe', 409);
            }
        }

        $cantidad = max(1, (int) ($data['cantidad_total'] ?? 1));

        $stmt = $this->db->prepare(
            'INSERT INTO libros (titulo, autor, anio, isbn, descripcion,
                                 cantidad_total, cantidad_disponible)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['titulo'],
            $data['autor'],
            (int) $data['anio'],
            $data['isbn'] ?? null,
            $data['descripcion'] ?? null,
            $cantidad,
            $cantidad
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    // ── Actualizar libro ───────────────────────────────────────
    public function actualizar(int $id, array $data): ?array
    {
        $campos = [];
        $params = [];

        $permitidos = ['titulo', 'autor', 'anio', 'isbn', 'descripcion', 'cantidad_total', 'cantidad_disponible'];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = ?";
                $params[] = $data[$campo];
            }
        }

        if (empty($campos))
            return $this->findById($id);

        $params[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE libros SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($params);

        return $this->findById($id);
    }

    // ── Eliminar libro ─────────────────────────────────────────
    public function eliminar(int $id): bool
    {
        // No eliminar si tiene préstamos activos
        $chk = $this->db->prepare(
            "SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND estado = 'Activo'"
        );
        $chk->execute([$id]);
        if ((int) $chk->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar: el libro tiene préstamos activos', 409);
        }

        $stmt = $this->db->prepare('DELETE FROM libros WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}