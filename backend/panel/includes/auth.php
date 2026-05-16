<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['panel_usuario'])) {
    header('Location: index.php');
    exit;
}

// Solo Bibliotecario puede acceder al panel
if ($_SESSION['panel_usuario']['tipo'] !== 'Bibliotecario') {
    session_destroy();
    header('Location: index.php?error=acceso');
    exit;
}
