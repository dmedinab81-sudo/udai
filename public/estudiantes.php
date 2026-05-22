<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';

$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM estudiante ORDER BY id DESC");
$estudiantes = $stmt->fetchAll();
?>
<div class="row">
  <div class="col-12">
    <h2>Estudiantes <a href="estudiante_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
    <table class="table table-striped">
      <thead>
        <tr><th>ID</th><th>Tipo ID</th><th>Identificación</th><th>Nombres</th><th>Fecha Nac.</th><th>Edad</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $e): ?>
          <tr>
            <td><?= $e['id'] ?></td>
            <td><?= htmlspecialchars($e['tipo_identificacion']) ?></td>
            <td><?= htmlspecialchars($e['identificacion']) ?></td>
            <td><?= htmlspecialchars($e['nombres']) ?></td>
            <td><?= htmlspecialchars($e['fecha_nacimiento']) ?></td>
            <td><?= htmlspecialchars($e['edad']) ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="estudiante_edit.php?id=<?= $e['id'] ?>">Editar</a>

              <form method="post" action="estudiante_delete.php" style="display:inline" onsubmit="return confirm('Eliminar estudiante?')">
                <?= csrf_input_html() ?>
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
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