<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
$pdo=getPDO();
$id=intval($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM estudiante_asignatura WHERE id=? LIMIT 1"); $stmt->execute([$id]); $row=$stmt->fetch();
if(!$row){ header('Location: estudiante_asignatura.php'); exit; }
$estudiantes=$pdo->query("SELECT id, identificacion, nombres FROM estudiante ORDER BY nombres")->fetchAll();
$asignaturas=$pdo->query("SELECT id, nombre FROM asignatura ORDER BY nombre")->fetchAll();
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) { set_flash('Error de seguridad: token CSRF inválido.'); header('Location: estudiante_asignatura.php'); exit; }

    $id_estudiante=intval($_POST['id_estudiante']??0);
    $id_asignatura=intval($_POST['id_asignatura']??0);
    $estado=trim($_POST['estado']??'');
    if(!$id_estudiante||!$id_asignatura) $errors[]='Seleccione estudiante y asignatura.';
    else {
        $stmt=$pdo->prepare("UPDATE estudiante_asignatura SET id_estudiante=?, id_asignatura=?, estado=? WHERE id=?");
        $stmt->execute([$id_estudiante,$id_asignatura,$estado,$id]);
        set_flash('Asignación actualizada.');
        header('Location: estudiante_asignatura.php'); exit;
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row"><div class="col-md-6 mx-auto">
<h2>Editar Asignación</h2>
<?php foreach($errors as $er):?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_input_html() ?>
  <div class="mb-3"><label class="form-label">Estudiante</label>
    <select class="form-select" name="id_estudiante">
      <?php foreach($estudiantes as $es): ?>
        <option value="<?= $es['id'] ?>" <?= (($row['id_estudiante']==$es['id'])|| (($_POST['id_estudiante']??'')==$es['id']))?'selected':'' ?>><?= htmlspecialchars($es['identificacion'].' - '.$es['nombres']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Asignatura</label>
    <select class="form-select" name="id_asignatura">
      <?php foreach($asignaturas as $a): ?>
        <option value="<?= $a['id'] ?>" <?= (($row['id_asignatura']==$a['id'])|| (($_POST['id_asignatura']??'')==$a['id']))?'selected':'' ?>><?= htmlspecialchars($a['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Estado</label><input class="form-control" name="estado" value="<?= htmlspecialchars($_POST['estado'] ?? $row['estado']) ?>"></div>
  <button class="btn btn-primary">Guardar</button>
  <a class="btn btn-secondary" href="estudiante_asignatura.php">Cancelar</a>
</form>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>