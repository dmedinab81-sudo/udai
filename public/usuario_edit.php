<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// Sólo admin puede editar usuarios
require_admin();

$pdo = getPDO();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Usuario no válido.');
    header('Location: usuarios.php'); exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$userRow = $stmt->fetch();
if (!$userRow) {
    set_flash('Usuario no encontrado.');
    header('Location: usuarios.php'); exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        $errors[] = 'Error de seguridad: token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = trim($_POST['rol'] ?? 'user');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || $email === '') {
            $errors[] = 'Nombre y email son obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email no válido.';
        } elseif (!preg_match('/^[A-Za-z0-9._%+\-]+@docentes\.educacion\.edu\.ec$/', $email)) {
            $errors[] = "El email debe ser @docentes.educacion.edu.ec";
        } else {
            // comprobar unicidad del email (si cambió)
            if ($email !== $userRow['email']) {
                $s = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
                $s->execute([$email]);
                if ($s->fetch()) {
                    $errors[] = 'Ya existe otro usuario con ese email.';
                }
            }
        }

        if (empty($errors)) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $id]);
            }
            set_flash('Usuario actualizado.');
            header('Location: usuarios.php'); exit;
        }
    }
}

include __DIR__ . '/_header.php';
?>
<div class="row">
  <div class="col-md-6 mx-auto">
    <h2>Editar usuario</h2>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
      <?= csrf_input_html() ?>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? $userRow['nombre']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control"
               type="email"
               name="email"
               required
               placeholder="usuario@docentes.educacion.edu.ec"
               pattern="^[A-Za-z0-9._%+\-]+@docentes\.educacion\.edu\.ec$"
               title="Solo correos @docentes.educacion.edu.ec"
               value="<?= htmlspecialchars($_POST['email'] ?? $userRow['email']) ?>">
        <small class="text-muted">Solo correos @docentes.educacion.edu.ec</small>
      </div>
      <div class="mb-3">
        <label class="form-label">Rol</label>
        <select class="form-select" name="rol">
          <option value="user" <?= (($_POST['rol'] ?? $userRow['rol']) === 'user') ? 'selected' : '' ?>>Usuario</option>
          <option value="admin" <?= (($_POST['rol'] ?? $userRow['rol']) === 'admin') ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Nueva contraseña (dejar vacío para no cambiar)</label>
        <input class="form-control" type="password" name="password" value="">
      </div>
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-secondary" href="usuarios.php">Cancelar</a>
    </form>

  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
