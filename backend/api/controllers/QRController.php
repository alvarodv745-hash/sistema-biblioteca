<?php
require_once __DIR__ . '/../models/CodigoQR.php';

class QRController
{

    private CodigoQR $qrModel;

    public function __construct()
    {
        $this->qrModel = new CodigoQR();
    }

    // ── GET /api/qr/libro/{id} ─────────────────────────────────
    public function qrLibro(int $id): void
    {
        $this->requireAuth();

        try {
            $qr = $this->qrModel->generarParaLibro($id);
            if (!$qr) {
                $this->error('Libro no encontrado', 404);
            }
            $this->success($qr);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── GET /api/qr/usuario/{id} ───────────────────────────────
    public function qrUsuario(int $id): void
    {
        $this->requireBibliotecario();

        try {
            $qr = $this->qrModel->generarParaUsuario($id);
            if (!$qr) {
                $this->error('Usuario no encontrado', 404);
            }
            $this->success($qr);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── POST /api/qr/procesar ──────────────────────────────────
    // Recibe el texto escaneado del QR y devuelve los datos del objeto
    public function procesar(): void
    {
        $this->requireAuth();

        $body = $this->getBody();
        $contenido = trim($body['contenido'] ?? '');

        if (!$contenido) {
            $this->error('El campo contenido es requerido', 400);
        }

        try {
            $resultado = $this->qrModel->procesarEscaneado($contenido);
            $this->success($resultado);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ── GET /api/qr ────────────────────────────────────────────
    public function listar(): void
    {
        $this->requireBibliotecario();
        $this->success($this->qrModel->listar());
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
        $u = $this->getTokenUsuario();
        if (!$u || $u['tipo'] !== 'Bibliotecario') {
            $this->error('Acceso denegado: se requiere rol Bibliotecario', 403);
        }
    }

    private function getTokenUsuario(): ?array
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!$auth || !str_starts_with($auth, 'Bearer '))
            return null;
        $decoded = base64_decode(substr($auth, 7));
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