<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';
$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM docente_apoyo ORDER BY id DESC");
$items = $stmt->fetchAll();
?>
<div class="row">
  <div class="col-12">
    <h2>Docentes Apoyo <a href="docente_apoyo_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Cédula</th><th>Nombres</th><th>Teléfono</th><th>Correo</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($items as $i): ?>
          <tr>
            <td><?= $i['id'] ?></td>
            <td><?= htmlspecialchars($i['cedula']) ?></td>
            <td><?= htmlspecialchars($i['nombres']) ?></td>
            <td><?= htmlspecialchars($i['telefono']) ?></td>
            <td><?= htmlspecialchars($i['correo']) ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="docente_apoyo_edit.php?id=<?= $i['id'] ?>">Editar</a>

              <form method="post" action="docente_apoyo_delete.php" style="display:inline" onsubmit="return confirm('Eliminar?')">
                <?= csrf_input_html() ?>
                <input type="hidden" name="id" value="<?= $i['id'] ?>">
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