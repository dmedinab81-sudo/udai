<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'docentes' => []]);
    exit;
}

$pdo = getPDO();
$sql = "SELECT id, cedula, nombres, telefono FROM docente_tutor
        WHERE cedula LIKE :q OR nombres LIKE :q
        ORDER BY nombres ASC
        LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute([':q' => "%{$q}%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'docentes' => $rows], JSON_UNESCAPED_UNICODE);
