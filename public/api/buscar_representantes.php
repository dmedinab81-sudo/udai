<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['success' => false, 'representantes' => []]);
    exit;
}

$pdo = getPDO();

// Buscar por cédula o nombres
$stmt = $pdo->prepare("
    SELECT id, cedula, nombres, telefono, correo 
    FROM representante_legal 
    WHERE cedula LIKE ? OR nombres LIKE ?
    ORDER BY nombres ASC
    LIMIT 10
");

$query = '%' . $q . '%';
$stmt->execute([$query, $query]);
$representantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'representantes' => $representantes
]);
?>
