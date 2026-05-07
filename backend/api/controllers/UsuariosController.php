<?php
require_once __DIR__ . '/../models/Usuario.php';

class UsuariosController
{

    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    // ── GET /api/usuarios ──────────────────────────────────────
    public function listar(): void
    {
        $this->requireBibliotecario();

        $filtros = [
            'tipo' => $_GET['tipo'] ?? '',
            'activo' => $_GET['activo'] ?? '',
            'pagina' => $_GET['pagina'] ?? 1,
            'limite' => $_GET['limite'] ?? 10,
        ];

        $resultado = $this->usuarioModel->listar($filtros);

        $this->success([
            'usuarios' => $resultado['datos'],
            'total' => $resultado['total'],
            'pagina' => (int) $filtros['pagina'],
        ]);
    }

    // ── GET /api/usuarios/{id} ─────────────────────────────────
    public function obtener(int $id): void
    {
        $this->requireAuth();

        // Un Lector solo puede ver su propio perfil
        $tokenUsuario = $this->getTokenUsuario();
        if ($tokenUsuario['tipo'] === 'Lector' && $tokenUsuario['id'] !== $id) {
            $this->error('Acceso denegado', 403);
        }

        $usuario = $this->usuarioModel->findById($id);
        if (!$usuario) {
            $this->error('Usuario no encontrado', 404);
        }

        $this->success($usuario);
    }

    // ── POST /api/usuarios ─────────────────────────────────────
    public function crear(): void
    {
        $this->requireBibliotecario();

        $body = $this->getBody();

        $requeridos = ['nombre', 'email', 'password'];
        foreach ($requeridos as $campo) {
            if (empty($body[$campo])) {
                $this->error("El campo '{$campo}' es requerido", 400);
            }
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Email no válido', 400);
        }

        if (strlen($body['password']) < 6) {
            $this->error('La contraseña debe tener al menos 6 caracteres', 400);
        }

        try {
            $usuario = $this->usuarioModel->crear($body);
            unset($usuario['password']);
            $this->success($usuario, 201);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── PUT /api/usuarios/{id} ─────────────────────────────────
    public function actualizar(int $id): void
    {
        $this->requireAuth();

        $tokenUsuario = $this->getTokenUsuario();

        // Un Lector solo puede editar su propio perfil
        if ($tokenUsuario['tipo'] === 'Lector' && $tokenUsuario['id'] !== $id) {
            $this->error('Acceso denegado', 403);
        }

        // Solo Bibliotecario puede cambiar el tipo o estado activo
        $body = $this->getBody();
        if ($tokenUsuario['tipo'] === 'Lector') {
            unset($body['tipo'], $body['activo']);
        }

        $actualizado = $this->usuarioModel->actualizar($id, $body);
        if (!$actualizado) {
            $this->error('Usuario no encontrado', 404);
        }

        $this->success($actualizado);
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