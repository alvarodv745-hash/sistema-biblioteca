<?php
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{

    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    // ── POST /api/login ────────────────────────────────────────
    public function login(): void
    {
        $body = $this->getBody();

        // Validar campos requeridos
        if (empty($body['email']) || empty($body['password'])) {
            $this->error('Email y contraseña son requeridos', 400);
        }

        $usuario = $this->usuarioModel->findByEmail($body['email']);

        if (!$usuario || !password_verify($body['password'], $usuario['password'])) {
            $this->error('Credenciales inválidas', 401);
        }

        if (!(bool) $usuario['activo']) {
            $this->error('Usuario desactivado. Contacta al administrador.', 403);
        }

        // Generar token simple basado en sesión
        $token = base64_encode($usuario['id'] . ':' . $usuario['tipo'] . ':' . time());

        unset($usuario['password']);

        $this->success([
            'usuario' => $usuario,
            'token' => $token
        ]);
    }

    // ── POST /api/registro ─────────────────────────────────────
    public function registro(): void
    {
        $body = $this->getBody();

        // Validaciones
        $requeridos = ['nombre', 'email', 'password'];
        foreach ($requeridos as $campo) {
            if (empty($body[$campo])) {
                $this->error("El campo '{$campo}' es requerido", 400);
            }
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('El email no tiene un formato válido', 400);
        }

        if (strlen($body['password']) < 6) {
            $this->error('La contraseña debe tener al menos 6 caracteres', 400);
        }

        // Solo permitir crear Lectores desde registro público
        $body['tipo'] = 'Lector';

        try {
            $usuario = $this->usuarioModel->crear($body);
            unset($usuario['password']);
            $this->success($usuario, 201);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── Helpers ────────────────────────────────────────────────
    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
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