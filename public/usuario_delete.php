<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// Sólo POST y sólo admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed'); echo 'Método no permitido.'; exit;
}
require_admin();

$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) {
    set_flash('Error de seguridad: token CSRF inválido.');
    header('Location: usuarios.php'); exit;
}

$pdo = getPDO();
$id = intval($_POST['id'] ?? 0);
$current = currentUser();

if ($id <= 0) {
    set_flash('Usuario no válido.');
    header('Location: usuarios.php'); exit;
}

// No permitir eliminar tu propia cuenta desde aquí
if (intval($id) === intval($current['id'])) {
    set_flash('No puedes eliminar tu propia cuenta.');
    header('Location: usuarios.php'); exit;
}

// Verificar si el usuario a eliminar es admin
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    set_flash('Usuario no encontrado.');
    header('Location: usuarios.php'); exit;
}

if (strtolower($row['rol']) === 'admin') {
    // contar administradores actuales
    $cstmt = $pdo->query("SELECT COUNT(*) as c FROM usuarios WHERE LOWER(rol) = 'admin'");
    $count = intval($cstmt->fetch()['c'] ?? 0);
    if ($count <= 1) {
        set_flash('No se puede eliminar al último administrador.');
        header('Location: usuarios.php'); exit;
    }
}

// Ejecutar borrado
$dstmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
$dstmt->execute([$id]);
set_flash('Usuario eliminado.');
header('Location: usuarios.php'); exit;