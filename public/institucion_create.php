<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
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
        $pdo=getPDO();
        try {
            $stmt=$pdo->prepare("INSERT INTO institucion (nombre,amie,correo,telefono) VALUES (?,?,?,?)");
            $stmt->execute([$nombre,$amie,$correo,$telefono]);
            set_flash('Institución creada.');
            header('Location: institucion.php'); exit;
        } catch (PDOException $ex) { $errors[]=$ex->getMessage(); }
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row"><div class="col-md-6 mx-auto">
<h2>Crear Institución</h2>
<?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_input_html() ?>
  <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"></div>
  <div class="mb-3"><label class="form-label">Amie</label><input class="form-control" name="amie" value="<?= htmlspecialchars($_POST['amie'] ?? '') ?>"></div>
  <div class="mb-3"><label class="form-label">Correo</label><input class="form-control" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"></div>
  <div class="mb-3"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"></div>
  <button class="btn btn-primary">Crear</button>
  <a class="btn btn-secondary" href="institucion.php">Cancelar</a>
</form>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>