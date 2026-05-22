<?php
require_once __DIR__ . '/../app/db.php';
include __DIR__ . '/_header.php';
$pdo=getPDO();
$stmt=$pdo->query("SELECT ea.*, e.identificacion as estudiante_ident, e.nombres as estudiante_nom, a.nombre as asignatura_nom FROM estudiante_asignatura ea LEFT JOIN estudiante e ON ea.id_estudiante=e.id LEFT JOIN asignatura a ON ea.id_asignatura=a.id ORDER BY ea.id DESC");
$items=$stmt->fetchAll();
?>
<div class="row"><div class="col-12">
<h2>Estudiante - Asignatura <a href="estudiante_asignatura_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>
<table class="table"><thead><tr><th>ID</th><th>Estudiante</th><th>Asignatura</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($items as $it): ?>
<tr>
  <td><?= $it['id'] ?></td>
  <td><?= htmlspecialchars($it['estudiante_ident'].' - '.$it['estudiante_nom']) ?></td>
  <td><?= htmlspecialchars($it['asignatura_nom']) ?></td>
  <td><?= htmlspecialchars($it['estado']) ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="estudiante_asignatura_edit.php?id=<?= $it['id'] ?>">Editar</a>

    <form method="post" action="estudiante_asignatura_delete.php" style="display:inline" onsubmit="return confirm('Eliminar?')">
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