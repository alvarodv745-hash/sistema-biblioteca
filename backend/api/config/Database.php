<?php
/**
 * Clase de conexión a la base de datos
 * Usa PDO con MySQL y patrón Singleton
 */
class Database
{

    private static ?Database $instance = null;
    private PDO $connection;

    private string $host;
    private string $dbname;
    private string $user;
    private string $pass;
    private string $charset = 'utf8mb4';

    private function __construct()
    {
        // Cargar variables de entorno desde .env si existe
        $envFile = __DIR__ . '/../../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0)
                    continue;
                if (strpos($line, '=') === false)
                    continue;
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }

        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'biblioteca';
        $this->user = $_ENV['DB_USER'] ?? 'root';
        $this->pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error de conexión a la base de datos',
                'detail' => $e->getMessage()
            ]);
            exit;
        }
    }

    /** Obtiene la instancia única (Singleton) */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /** Devuelve el objeto PDO */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Evitar clonación y deserialización del Singleton
    private function __clone()
    {
    }
    public function __wakeup()
    {
    }
}