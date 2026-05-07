<?php
require_once __DIR__ . '/../config/Database.php';

class Prestamo
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
            'SELECT p.id, p.usuario_id, u.nombre AS usuario_nombre,
                    p.libro_id, l.titulo AS libro_titulo,
                    p.fecha_prestamo, p.fecha_devolucion_prevista,
                    p.fecha_devolucion_real, p.estado, p.notas,
                    CASE
                        WHEN p.estado = \'Activo\'
                             AND NOW() > p.fecha_devolucion_prevista
                        THEN DATEDIFF(NOW(), p.fecha_devolucion_prevista)
                        ELSE 0
                    END AS dias_retraso
             FROM prestamos p
             JOIN usuarios u ON u.id = p.usuario_id
             JOIN libros   l ON l.id = p.libro_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── Listar con filtros ────────────────────────────────────
    public function listar(array $filtros = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'p.usuario_id = ?';
            $params[] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['estado'])) {
            $where[] = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['libro_id'])) {
            $where[] = 'p.libro_id = ?';
            $params[] = (int) $filtros['libro_id'];
        }

        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $limite = min(100, max(1, (int) ($filtros['limite'] ?? 10)));
        $offset = ($pagina - 1) * $limite;

        $sql = 'SELECT p.id, p.usuario_id, u.nombre AS usuario_nombre,
                       p.libro_id, l.titulo AS libro_titulo,
                       p.fecha_prestamo, p.fecha_devolucion_prevista,
                       p.fecha_devolucion_real, p.estado, p.notas,
                       CASE
                           WHEN p.estado = \'Activo\'
                                AND NOW() > p.fecha_devolucion_prevista
                           THEN DATEDIFF(NOW(), p.fecha_devolucion_prevista)
                           ELSE 0
                       END AS dias_retraso
                FROM prestamos p
                JOIN usuarios u ON u.id = p.usuario_id
                JOIN libros   l ON l.id = p.libro_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.fecha_prestamo DESC
                LIMIT ? OFFSET ?';

        $paramsQ = array_merge($params, [$limite, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($paramsQ);
        $datos = $stmt->fetchAll();

        $stmtC = $this->db->prepare(
            'SELECT COUNT(*) FROM prestamos p WHERE ' . implode(' AND ', $where)
        );
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        return ['datos' => $datos, 'total' => $total];
    }

    // ── Crear préstamo ─────────────────────────────────────────
    public function crear(array $data): array
    {
        // Verificar que el libro existe y está disponible
        $stmt = $this->db->prepare(
            'SELECT id, cantidad_disponible FROM libros WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$data['libro_id']]);
        $libro = $stmt->fetch();

        if (!$libro) {
            throw new RuntimeException('Libro no encontrado', 404);
        }
        if ((int) $libro['cantidad_disponible'] < 1) {
            throw new RuntimeException('El libro no está disponible', 400);
        }

        // Verificar que el usuario no tenga ya ese libro prestado
        $chk = $this->db->prepare(
            "SELECT id FROM prestamos
             WHERE usuario_id = ? AND libro_id = ? AND estado = 'Activo' LIMIT 1"
        );
        $chk->execute([$data['usuario_id'], $data['libro_id']]);
        if ($chk->fetch()) {
            throw new RuntimeException('El usuario ya tiene este libro en préstamo', 409);
        }

        $dias = max(1, (int) ($data['dias'] ?? 15));
        $fechaDevolucion = date('Y-m-d H:i:s', strtotime("+{$dias} days"));

        $stmt = $this->db->prepare(
            'INSERT INTO prestamos (usuario_id, libro_id, fecha_devolucion_prevista)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            (int) $data['usuario_id'],
            (int) $data['libro_id'],
            $fechaDevolucion
        ]);

        // El trigger de MySQL actualiza cantidad_disponible automáticamente
        return $this->findById((int) $this->db->lastInsertId());
    }

    // ── Registrar devolución ───────────────────────────────────
    public function devolver(int $id, array $data = []): array
    {
        $prestamo = $this->findById($id);

        if (!$prestamo) {
            throw new RuntimeException('Préstamo no encontrado', 404);
        }
        if ($prestamo['estado'] !== 'Activo') {
            throw new RuntimeException('Este préstamo ya fue devuelto', 409);
        }

        $stmt = $this->db->prepare(
            "UPDATE prestamos
             SET estado = 'Devuelto',
                 fecha_devolucion_real = NOW(),
                 notas = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $data['notas'] ?? null,
            $id
        ]);

        // El trigger de MySQL restaura cantidad_disponible automáticamente
        return $this->findById($id);
    }

    // ── Actualizar estados retrasados ──────────────────────────
    public function actualizarRetrasados(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE prestamos
             SET estado = 'Retrasado'
             WHERE estado = 'Activo'
               AND NOW() > fecha_devolucion_prevista"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}