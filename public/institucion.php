<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';
$pdo=getPDO();
$stmt=$pdo->query("SELECT * FROM institucion ORDER BY id DESC");
$items=$stmt->fetchAll();
?>
<div class="row">
  <div class="col-12">
    <h2>Instituciones <a href="institucion_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
    <table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Amie</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= $it['id'] ?></td>
        <td><?= htmlspecialchars($it['nombre']) ?></td>
        <td><?= htmlspecialchars($it['amie']) ?></td>
        <td><?= htmlspecialchars($it['correo']) ?></td>
        <td><?= htmlspecialchars($it['telefono']) ?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="institucion_edit.php?id=<?= $it['id'] ?>">Editar</a>

          <form method="post" action="institucion_delete.php" style="display:inline" onsubmit="return confirm('Eliminar?')">
            <?= csrf_input_html() ?>
            <input type="hidden" name="id" value="<?= $it['id'] ?>">
            <button class="btn btn-sm btn-danger">Eliminar</button>
          </form>

        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>