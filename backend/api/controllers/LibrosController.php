<?php
require_once __DIR__ . '/../models/Libro.php';

class LibrosController
{

    private Libro $libroModel;

    public function __construct()
    {
        $this->libroModel = new Libro();
    }

    // ── GET /api/libros ────────────────────────────────────────
    public function listar(): void
    {
        $filtros = [
            'titulo' => $_GET['titulo'] ?? '',
            'autor' => $_GET['autor'] ?? '',
            'buscar' => $_GET['buscar'] ?? '',
            'disponible' => $_GET['disponible'] ?? '',
            'pagina' => $_GET['pagina'] ?? 1,
            'limite' => $_GET['limite'] ?? 10,
        ];

        $resultado = $this->libroModel->listar($filtros);

        $this->success([
            'libros' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => (int) $filtros['pagina'],
        ]);
    }

    // ── GET /api/libros/{id} ───────────────────────────────────
    public function obtener(int $id): void
    {
        $libro = $this->libroModel->findById($id);
        if (!$libro) {
            $this->error('Libro no encontrado', 404);
        }
        $this->success($libro);
    }

    // ── POST /api/libros ───────────────────────────────────────
    public function crear(): void
    {
        $this->requireBibliotecario();

        $body = $this->getBody();

        // Validaciones
        $requeridos = ['titulo', 'autor', 'anio'];
        foreach ($requeridos as $campo) {
            if (empty($body[$campo])) {
                $this->error("El campo '{$campo}' es requerido", 400);
            }
        }

        if (!is_numeric($body['anio']) || $body['anio'] < 0 || $body['anio'] > date('Y')) {
            $this->error('El año no es válido', 400);
        }

        try {
            $libro = $this->libroModel->crear($body);
            $this->success($libro, 201);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── PUT /api/libros/{id} ───────────────────────────────────
    public function actualizar(int $id): void
    {
        $this->requireBibliotecario();

        $libro = $this->libroModel->findById($id);
        if (!$libro) {
            $this->error('Libro no encontrado', 404);
        }

        $body = $this->getBody();

        try {
            $actualizado = $this->libroModel->actualizar($id, $body);
            $this->success($actualizado);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── DELETE /api/libros/{id} ────────────────────────────────
    public function eliminar(int $id): void
    {
        $this->requireBibliotecario();

        $libro = $this->libroModel->findById($id);
        if (!$libro) {
            $this->error('Libro no encontrado', 404);
        }

        try {
            $this->libroModel->eliminar($id);
            $this->success(['message' => 'Libro eliminado correctamente']);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── Auth helpers ───────────────────────────────────────────
    private function requireBibliotecario(): void
    {
        $usuario = $this->getTokenUsuario();
        if (!$usuario || $usuario['tipo'] !== 'Bibliotecario') {
            $this->error('Acceso denegado: se requiere rol Bibliotecario', 403);
        }
    }

    private function getTokenUsuario(): ?array
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!$auth || !str_starts_with($auth, 'Bearer '))
            return null;

        $token = substr($auth, 7);
        $decoded = base64_decode($token);
        $partes = explode(':', $decoded);
        if (count($partes) < 2)
            return null;

        return ['id' => (int) $partes[0], 'tipo' => $partes[1]];
    }

    // ── Response helpers ───────────────────────────────────────
    private function getBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function success(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    private function error(string $msg, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}