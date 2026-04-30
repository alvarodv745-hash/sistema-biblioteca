<?php
/**
 * Punto de entrada de la API
 */
header("Content-Type: application/json");
echo json_encode(["status" => "ok", "message" => "API de Biblioteca Funcionando"]);
