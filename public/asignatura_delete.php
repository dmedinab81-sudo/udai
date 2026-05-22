<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// aceptar solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed'); echo 'Método no permitido.'; exit;
}

// verificar CSRF
$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) {
    set_flash('Error de seguridad: token CSRF inválido.');
    header('Location: asignaturas.php'); exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM asignatura WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('Asignatura eliminada.');
}
header('Location: asignaturas.php');
exit;