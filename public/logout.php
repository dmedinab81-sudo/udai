<?php
require_once __DIR__ . '/../app/auth.php';

// Aceptar sólo POST para logout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Bad request: redirigir o mostrar error
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Método no permitido.';
    exit;
}

// Verificar token CSRF
$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) {
    // Opcional: registrar intento fallido
    set_flash('Error de seguridad: token CSRF inválido.');
    header('Location: index.php');
    exit;
}

// Ejecutar logout y redirigir
logout();
set_flash('Has cerrado sesión correctamente.');
header('Location: index.php');
exit;