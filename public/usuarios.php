<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// Sólo admin puede ver la lista de usuarios
require_admin();

include __DIR__ . '/_header.php';

$pdo = getPDO();
$stmt = $pdo->query("SELECT id, nombre, email, rol, creado_en FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
$current = currentUser();
?>
<div class="row">
  <div class="col-12">
    <h2>Gestión de usuarios
      <a href="register.php" class="btn btn-sm btn-success float-end">Crear usuario</a>
    </h2>

    <table class="table table-striped">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Creado</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['rol']) ?></td>
            <td><?= htmlspecialchars($u['creado_en']) ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="usuario_edit.php?id=<?= $u['id'] ?>">Editar</a>

              <?php if (intval($u['id']) === intval($current['id'])): ?>
                <button class="btn btn-sm btn-secondary" disabled title="No puedes eliminar tu propia cuenta">Eliminar</button>
              <?php else: ?>
                <form method="post" action="usuario_delete.php" style="display:inline" onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['nombre']) ?>?')">
                  <?= csrf_input_html() ?>
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              <?php endif; ?>

            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>