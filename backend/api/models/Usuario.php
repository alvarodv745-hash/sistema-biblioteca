<?php
require_once __DIR__ . '/../config/Database.php';

class Usuario
{

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Buscar por email (para login) ──────────────────────────
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, password, tipo, activo
             FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── Buscar por ID ──────────────────────────────────────────
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, tipo, activo, created_at
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── Listar todos ───────────────────────────────────────────
    public function listar(array $filtros = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (isset($filtros['activo'])) {
            $where[] = 'activo = ?';
            $params[] = (int) $filtros['activo'];
        }

        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $limite = min(100, max(1, (int) ($filtros['limite'] ?? 10)));
        $offset = ($pagina - 1) * $limite;

        $sql = 'SELECT id, nombre, email, tipo, activo, created_at
                FROM usuarios
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?';

        $params[] = $limite;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        // Total sin paginación
        $sqlCount = 'SELECT COUNT(*) FROM usuarios WHERE ' . implode(' AND ', $where);
        array_pop($params); // quitar offset
        array_pop($params); // quitar limite
        $stmtC = $this->db->prepare($sqlCount);
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        return ['datos' => $datos, 'total' => $total];
    }

    // ── Crear usuario ──────────────────────────────────────────
    public function crear(array $data): array
    {
        // Verificar email único
        $chk = $this->db->prepare('SELECT id FROM usuarios WHERE email = ?');
        $chk->execute([$data['email']]);
        if ($chk->fetch()) {
            throw new RuntimeException('El email ya está registrado', 409);
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password, tipo)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            $hash,
            $data['tipo'] ?? 'Lector'
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    // ── Actualizar usuario ─────────────────────────────────────
    public function actualizar(int $id, array $data): ?array
    {
        $campos = [];
        $params = [];

        if (!empty($data['nombre'])) {
            $campos[] = 'nombre = ?';
            $params[] = $data['nombre'];
        }
        if (!empty($data['email'])) {
            $campos[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (!empty($data['password'])) {
            $campos[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['activo'])) {
            $campos[] = 'activo = ?';
            $params[] = (int) $data['activo'];
        }

        if (empty($campos))
            return $this->findById($id);

        $params[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($params);

        return $this->findById($id);
    }
}