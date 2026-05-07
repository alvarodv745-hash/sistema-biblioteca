<?php
require_once __DIR__ . '/../models/Prestamo.php';

class PrestamosController
{

    private Prestamo $prestamoModel;

    public function __construct()
    {
        $this->prestamoModel = new Prestamo();
    }

    // ── GET /api/prestamos ─────────────────────────────────────
    public function listar(): void
    {
        $this->requireAuth();

        $tokenUsuario = $this->getTokenUsuario();

        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'pagina' => $_GET['pagina'] ?? 1,
            'limite' => $_GET['limite'] ?? 10,
        ];

        // Un Lector solo ve sus propios préstamos
        if ($tokenUsuario['tipo'] === 'Lector') {
            $filtros['usuario_id'] = $tokenUsuario['id'];
        } else {
            $filtros['usuario_id'] = $_GET['usuario_id'] ?? '';
        }

        // Actualizar retrasados antes de listar
        $this->prestamoModel->actualizarRetrasados();

        $resultado = $this->prestamoModel->listar($filtros);

        $this->success([
            'prestamos' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => (int) $filtros['pagina'],
        ]);
    }

    // ── GET /api/prestamos/{id} ────────────────────────────────
    public function obtener(int $id): void
    {
        $this->requireAuth();

        $prestamo = $this->prestamoModel->findById($id);
        if (!$prestamo) {
            $this->error('Préstamo no encontrado', 404);
        }

        // Un Lector solo puede ver sus propios préstamos
        $tokenUsuario = $this->getTokenUsuario();
        if (
            $tokenUsuario['tipo'] === 'Lector'
            && $prestamo['usuario_id'] != $tokenUsuario['id']
        ) {
            $this->error('Acceso denegado', 403);
        }

        $this->success($prestamo);
    }

    // ── POST /api/prestamos ────────────────────────────────────
    public function crear(): void
    {
        $this->requireBibliotecario();

        $body = $this->getBody();

        $requeridos = ['usuario_id', 'libro_id'];
        foreach ($requeridos as $campo) {
            if (empty($body[$campo])) {
                $this->error("El campo '{$campo}' es requerido", 400);
            }
        }

        try {
            $prestamo = $this->prestamoModel->crear($body);
            $this->success($prestamo, 201);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── PUT /api/prestamos/{id}/devolver ───────────────────────
    public function devolver(int $id): void
    {
        $this->requireBibliotecario();

        $body = $this->getBody();

        try {
            $prestamo = $this->prestamoModel->devolver($id, $body);
            $this->success($prestamo);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── Auth helpers ───────────────────────────────────────────
    private function requireAuth(): void
    {
        if (!$this->getTokenUsuario()) {
            $this->error('Autenticación requerida', 401);
        }
    }

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