<?php
/**
 * Sistema de Gestión de Biblioteca
 * API REST - Punto de entrada y enrutador principal
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Carga de clases ────────────────────────────────────────────
require_once __DIR__ . '/api/config/Database.php';
require_once __DIR__ . '/api/models/Usuario.php';
require_once __DIR__ . '/api/models/Libro.php';
require_once __DIR__ . '/api/models/Prestamo.php';
require_once __DIR__ . '/api/models/CodigoQR.php';
require_once __DIR__ . '/api/controllers/AuthController.php';
require_once __DIR__ . '/api/controllers/LibrosController.php';
require_once __DIR__ . '/api/controllers/UsuariosController.php';
require_once __DIR__ . '/api/controllers/PrestamosController.php';
require_once __DIR__ . '/api/controllers/QRController.php';

// ── Extrae método y ruta limpia ────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalizar: extraer solo la parte después de /api (funciona en cualquier subcarpeta de XAMPP)
$apiPos = strpos($uri, '/api');
if ($apiPos !== false) {
    $uri = substr($uri, $apiPos + 4); // +4 = strlen('/api')
} else {
    // Si no hay /api en la URL, calcular ruta relativa al directorio del script
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . trim($uri, '/');

function matchRoute(string $pattern, string $uri, ?int &$id = null): bool
{
    if (str_contains($pattern, '{id}')) {
        $regex = '#^' . str_replace('{id}', '(\d+)', $pattern) . '$#';
        if (preg_match($regex, $uri, $m)) {
            $id = (int) $m[1];
            return true;
        }
        return false;
    }
    return $uri === $pattern;
}

$id = null;

// ── AUTH ───────────────────────────────────────────────────────
if ($method === 'POST' && matchRoute('/login', $uri))
    (new AuthController())->login();
if ($method === 'POST' && matchRoute('/registro', $uri))
    (new AuthController())->registro();

// ── LIBROS ─────────────────────────────────────────────────────
if ($method === 'GET' && matchRoute('/libros', $uri))
    (new LibrosController())->listar();
if ($method === 'GET' && matchRoute('/libros/{id}', $uri, $id))
    (new LibrosController())->obtener($id);
if ($method === 'POST' && matchRoute('/libros', $uri))
    (new LibrosController())->crear();
if ($method === 'PUT' && matchRoute('/libros/{id}', $uri, $id))
    (new LibrosController())->actualizar($id);
if ($method === 'DELETE' && matchRoute('/libros/{id}', $uri, $id))
    (new LibrosController())->eliminar($id);

// ── USUARIOS ───────────────────────────────────────────────────
if ($method === 'GET' && matchRoute('/usuarios', $uri))
    (new UsuariosController())->listar();
if ($method === 'GET' && matchRoute('/usuarios/{id}', $uri, $id))
    (new UsuariosController())->obtener($id);
if ($method === 'POST' && matchRoute('/usuarios', $uri))
    (new UsuariosController())->crear();
if ($method === 'PUT' && matchRoute('/usuarios/{id}', $uri, $id))
    (new UsuariosController())->actualizar($id);

// ── PRÉSTAMOS ──────────────────────────────────────────────────
if ($method === 'GET' && matchRoute('/prestamos', $uri))
    (new PrestamosController())->listar();
if ($method === 'GET' && matchRoute('/prestamos/{id}', $uri, $id))
    (new PrestamosController())->obtener($id);
if ($method === 'POST' && matchRoute('/prestamos', $uri))
    (new PrestamosController())->crear();
if ($method === 'PUT' && matchRoute('/prestamos/{id}/devolver', $uri, $id))
    (new PrestamosController())->devolver($id);

// ── QR ─────────────────────────────────────────────────────────
if ($method === 'GET' && matchRoute('/qr', $uri))
    (new QRController())->listar();
if ($method === 'GET' && matchRoute('/qr/libro/{id}', $uri, $id))
    (new QRController())->qrLibro($id);
if ($method === 'GET' && matchRoute('/qr/usuario/{id}', $uri, $id))
    (new QRController())->qrUsuario($id);
if ($method === 'POST' && matchRoute('/qr/procesar', $uri))
    (new QRController())->procesar();

// ── Health check ───────────────────────────────────────────────
if ($method === 'GET' && matchRoute('/', $uri)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'API Sistema de Biblioteca funcionando',
        'version' => '1.1.0',
        'endpoints' => [
            'POST   /api/login',
            'POST   /api/registro',
            'GET    /api/libros',
            'POST   /api/libros',
            'GET    /api/libros/{id}',
            'PUT    /api/libros/{id}',
            'DELETE /api/libros/{id}',
            'GET    /api/usuarios',
            'POST   /api/usuarios',
            'GET    /api/usuarios/{id}',
            'PUT    /api/usuarios/{id}',
            'GET    /api/prestamos',
            'POST   /api/prestamos',
            'GET    /api/prestamos/{id}',
            'PUT    /api/prestamos/{id}/devolver',
            'GET    /api/qr/libro/{id}',
            'GET    /api/qr/usuario/{id}',
            'POST   /api/qr/procesar',
        ]
    ]);
    exit;
}

// ── 404 ────────────────────────────────────────────────────────
http_response_code(404);
echo json_encode(['success' => false, 'error' => "Ruta no encontrada: {$method} {$uri}"]);