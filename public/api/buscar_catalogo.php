<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';
header('Content-Type: application/json; charset=utf-8');
$tipo = trim($_GET['tipo'] ?? '');
$q = trim($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) { echo json_encode(['success'=>true,'items'=>[]]); exit; }

$map = [
  'periodo' => ['table'=>'ubicacion', 'label'=>'mes', 'extra'=>['zona','distrito']],
  'docente_apoyo' => ['table'=>'docente_apoyo', 'label'=>'nombres', 'extra'=>['cedula']],
  'institucion' => ['table'=>'institucion', 'label'=>'nombre', 'extra'=>['amie']],
  'estudiante' => ['table'=>'estudiante', 'label'=>'nombres', 'extra'=>['identificacion']],
  'docente_tutor' => ['table'=>'docente_tutor', 'label'=>'nombres', 'extra'=>['cedula']],
  'representante' => ['table'=>'representante_legal', 'label'=>'nombres', 'extra'=>['cedula']],
];
if (!isset($map[$tipo])) { echo json_encode(['success'=>false,'error'=>'tipo inválido']); exit; }
$m=$map[$tipo];
$conds = ["{$m['label']} LIKE :q"];
foreach ($m['extra'] as $f) $conds[] = "$f LIKE :q";
$sql = "SELECT id, {$m['label']} as label FROM {$m['table']} WHERE " . implode(' OR ', $conds) . " ORDER BY id DESC LIMIT 20";
$pdo=getPDO();
$stmt=$pdo->prepare($sql);
$stmt->execute([':q'=>"%{$q}%"]);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=>true,'items'=>$rows], JSON_UNESCAPED_UNICODE);