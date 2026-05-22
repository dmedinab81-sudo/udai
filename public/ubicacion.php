<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';
$pdo=getPDO();
$stmt=$pdo->query("SELECT * FROM ubicacion ORDER BY id DESC");
$items=$stmt->fetchAll();
?>
<div class="row"><div class="col-12">
<h2>Periodo <a href="ubicacion_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
<table class="table"><thead><tr><th>ID</th><th>Mes</th><th>Año</th><th>Zona</th><th>Distrito</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($items as $it): ?>
<tr>
  <td><?= $it['id'] ?></td>
  <td><?= htmlspecialchars($it['mes']) ?></td>
  <td><?= htmlspecialchars($it['anio']) ?></td>
  <td><?= htmlspecialchars($it['zona']) ?></td>
  <td><?= htmlspecialchars($it['distrito']) ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="ubicacion_edit.php?id=<?= $it['id'] ?>">Editar</a>

    <form method="post" action="ubicacion_delete.php" style="display:inline" onsubmit="return confirm('Eliminar?')">
      <?= csrf_input_html() ?>
      <input type="hidden" name="id" value="<?= $it['id'] ?>">
      <button class="btn btn-sm btn-danger">Eliminar</button>
    </form>

  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>