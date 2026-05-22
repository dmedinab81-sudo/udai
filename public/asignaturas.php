<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';

$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM asignatura ORDER BY id DESC");
$asignaturas = $stmt->fetchAll();
?>
<div class="row">
  <div class="col-12">
    <h2>Asignaturas <a href="asignatura_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
    <table class="table table-striped">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($asignaturas as $a): ?>
          <tr>
            <td><?= $a['id'] ?></td>
            <td><?= htmlspecialchars($a['nombre']) ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="asignatura_edit.php?id=<?= $a['id'] ?>">Editar</a>

              <form method="post" action="asignatura_delete.php" style="display:inline" onsubmit="return confirm('Eliminar asignatura?')">
                <?= csrf_input_html() ?>
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn btn-sm btn-danger">Eliminar</button>
              </form>

            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>