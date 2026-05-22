<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('HTTP/1.1 405 Method Not Allowed'); echo 'Método no permitido.'; exit; }
$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) { set_flash('Error de seguridad: token CSRF inválido.'); header('Location: docente_apoyo.php'); exit; }
$pdo = getPDO();
$id = intval($_POST['id'] ?? 0);
if($id>0){ $stmt=$pdo->prepare("DELETE FROM docente_apoyo WHERE id = ?"); $stmt->execute([$id]); set_flash('Docente apoyo eliminado.'); }
header('Location: docente_apoyo.php'); exit;