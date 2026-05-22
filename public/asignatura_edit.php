<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
$pdo = getPDO();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM asignatura WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$asign = $stmt->fetch();
if (!$asign) {
    header('Location: asignaturas.php');
    exit;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        set_flash('Error de seguridad: token CSRF inválido.');
        header('Location: asignaturas.php'); exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') {
        $errors[] = "El nombre es obligatorio.";
    } else {
        $stmt = $pdo->prepare("UPDATE asignatura SET nombre = ? WHERE id = ?");
        $stmt->execute([$nombre, $id]);
        set_flash('Asignatura actualizada.');
        header('Location: asignaturas.php');
        exit;
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row">
  <div class="col-md-6 mx-auto">
    <h2>Editar Asignatura</h2>
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <form method="post">
      <?= csrf_input_html() ?>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? $asign['nombre']) ?>">
      </div>
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-secondary" href="asignaturas.php">Cancelar</a>
    </form>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>