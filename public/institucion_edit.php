<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
$pdo=getPDO();
$id=intval($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM institucion WHERE id=? LIMIT 1"); $stmt->execute([$id]); $r=$stmt->fetch();
if(!$r){ header('Location: institucion.php'); exit; }
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) { set_flash('Error de seguridad: token CSRF inválido.'); header('Location: institucion.php'); exit; }

    $nombre=trim($_POST['nombre']??'');
    $amie=trim($_POST['amie']??'');
    $correo=trim($_POST['correo']??'');
    $telefono=trim($_POST['telefono']??'');
    if($nombre==='') $errors[]='Nombre obligatorio.';
    else {
        $stmt=$pdo->prepare("UPDATE institucion SET nombre=?, amie=?, correo=?, telefono=? WHERE id=?");
        $stmt->execute([$nombre,$amie,$correo,$telefono,$id]);
        set_flash('Institución actualizada.');
        header('Location: institucion.php'); exit;
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row"><div class="col-md-6 mx-auto">
<h2>Editar Institución</h2>
<?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_input_html() ?>
  <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $r['nombre']) ?>"></div>
  <div class="mb-3"><label class="form-label">Amie</label><input class="form-control" name="amie" value="<?= htmlspecialchars($_POST['amie'] ?? $r['amie']) ?>"></div>
  <div class="mb-3"><label class="form-label">Correo</label><input class="form-control" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? $r['correo']) ?>"></div>
  <div class="mb-3"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? $r['telefono']) ?>"></div>
  <button class="btn btn-primary">Guardar</button>
  <a class="btn btn-secondary" href="institucion.php">Cancelar</a>
</form>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>