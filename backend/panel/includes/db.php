<?php
// Reutiliza la conexión existente del proyecto
require_once __DIR__ . '/../../api/config/Database.php';

function getPDO(): PDO {
    return Database::getInstance()->getConnection();
}
