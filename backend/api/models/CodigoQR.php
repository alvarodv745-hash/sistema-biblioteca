<?php
require_once __DIR__ . '/../config/Database.php';

class CodigoQR
{

    private PDO $db;

    // URL de la API gratuita de generación de QR (sin dependencias Composer)
    private const QR_API = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Generar o recuperar QR de un libro ─────────────────────
    public function generarParaLibro(int $libroId): ?array
    {
        // Verificar que el libro existe
        $stmt = $this->db->prepare('SELECT id, titulo, autor FROM libros WHERE id = ?');
        $stmt->execute([$libroId]);
        $libro = $stmt->fetch();
        if (!$libro)
            return null;

        $contenido = "LIBRO:{$libroId}";

        // Buscar si ya existe el QR para ese libro
        $existente = $this->findByContenido($contenido);
        if ($existente) {
            $existente['url_imagen'] = self::QR_API . urlencode($contenido);
            $existente['libro'] = $libro;
            return $existente;
        }

        // Crear nuevo registro
        $stmt = $this->db->prepare(
            "INSERT INTO codigos_qr (tipo, referencia_id, contenido_qr, imagen_path)
             VALUES ('Libro', ?, ?, ?)"
        );
        $imagePath = "qr_libro_{$libroId}.png";
        $stmt->execute([$libroId, $contenido, $imagePath]);

        return [
            'id' => (int) $this->db->lastInsertId(),
            'tipo' => 'Libro',
            'referencia_id' => $libroId,
            'contenido_qr' => $contenido,
            'imagen_path' => $imagePath,
            'url_imagen' => self::QR_API . urlencode($contenido),
            'libro' => $libro,
        ];
    }

    // ── Generar o recuperar QR de un usuario ───────────────────
    public function generarParaUsuario(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, email FROM usuarios WHERE id = ?');
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();
        if (!$usuario)
            return null;

        $contenido = "USUARIO:{$usuarioId}";

        $existente = $this->findByContenido($contenido);
        if ($existente) {
            $existente['url_imagen'] = self::QR_API . urlencode($contenido);
            $existente['usuario'] = $usuario;
            return $existente;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO codigos_qr (tipo, referencia_id, contenido_qr, imagen_path)
             VALUES ('Usuario', ?, ?, ?)"
        );
        $imagePath = "qr_usuario_{$usuarioId}.png";
        $stmt->execute([$usuarioId, $contenido, $imagePath]);

        return [
            'id' => (int) $this->db->lastInsertId(),
            'tipo' => 'Usuario',
            'referencia_id' => $usuarioId,
            'contenido_qr' => $contenido,
            'imagen_path' => $imagePath,
            'url_imagen' => self::QR_API . urlencode($contenido),
            'usuario' => $usuario,
        ];
    }

    // ── Procesar contenido escaneado ───────────────────────────
    public function procesarEscaneado(string $contenido): array
    {
        $contenido = trim(strtoupper($contenido));

        // Formato: LIBRO:5 o USUARIO:3
        if (!preg_match('/^(LIBRO|USUARIO):(\d+)$/', $contenido, $m)) {
            throw new RuntimeException('QR no reconocido: formato inválido', 400);
        }

        $tipo = $m[1];
        $id = (int) $m[2];

        if ($tipo === 'LIBRO') {
            $stmt = $this->db->prepare(
                'SELECT id, titulo, autor, anio, isbn,
                        cantidad_disponible, disponible
                 FROM libros WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $libro = $stmt->fetch();
            if (!$libro)
                throw new RuntimeException('Libro no encontrado', 404);

            return [
                'tipo' => 'Libro',
                'id' => $id,
                'titulo' => $libro['titulo'],
                'autor' => $libro['autor'],
                'anio' => $libro['anio'],
                'isbn' => $libro['isbn'],
                'disponible' => (bool) $libro['disponible'],
                'cantidad_disponible' => (int) $libro['cantidad_disponible'],
            ];
        }

        // USUARIO
        $stmt = $this->db->prepare(
            'SELECT id, nombre, email, tipo, activo FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        if (!$usuario)
            throw new RuntimeException('Usuario no encontrado', 404);

        return [
            'tipo' => 'Usuario',
            'id' => $id,
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['tipo'],
            'activo' => (bool) $usuario['activo'],
        ];
    }

    // ── Helper: buscar por contenido ───────────────────────────
    private function findByContenido(string $contenido): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM codigos_qr WHERE contenido_qr = ? LIMIT 1'
        );
        $stmt->execute([$contenido]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── Listar todos los QRs generados ─────────────────────────
    public function listar(): array
    {
        $stmt = $this->db->query(
            'SELECT q.*, 
                    CASE q.tipo
                        WHEN \'Libro\'   THEN (SELECT titulo FROM libros   WHERE id = q.referencia_id)
                        WHEN \'Usuario\' THEN (SELECT nombre FROM usuarios WHERE id = q.referencia_id)
                    END AS referencia_nombre
             FROM codigos_qr q
             ORDER BY q.created_at DESC'
        );
        return $stmt->fetchAll();
    }
}