<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// Ahora: creación de usuarios SOLO por admin
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        $errors[] = "Error de seguridad: token CSRF inválido.";
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $rol = trim($_POST['rol'] ?? 'user');

        if ($nombre === '' || $email === '' || $password === '') {
            $errors[] = "Todos los campos obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email no válido.";
        } elseif (!preg_match('/^[A-Za-z0-9._%+\-]+@docentes\.educacion\.edu\.ec$/', $email)) {
            $errors[] = "El email debe ser @docentes.educacion.edu.ec";
        } elseif ($password !== $password2) {
            $errors[] = "Las contraseñas no coinciden.";
        } elseif (findUserByEmail($email)) {
            $errors[] = "Ya existe un usuario con ese email.";
        } else {
            if (!registerUser($nombre, $email, $password, $rol)) {
                $errors[] = "No se pudo crear el usuario.";
            } else {
                set_flash('Usuario creado correctamente.');
                header('Location: index.php');
                exit;
            }
        }
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row">
  <div class="col-md-6 mx-auto">
    <h2>Crear usuario (admin)</h2>
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <form method="post">
      <?= csrf_input_html() ?>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
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
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <small class="text-muted">Solo correos @docentes.educacion.edu.ec</small>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input class="form-control" type="password" name="password2" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Rol</label>
        <select class="form-select" name="rol">
          <option value="user" <?= (($_POST['rol'] ?? '') === 'user') ? 'selected' : '' ?>>Usuario</option>
          <option value="admin" <?= (($_POST['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>
      <button class="btn btn-primary">Crear usuario</button>
      <a class="btn btn-secondary" href="index.php">Cancelar</a>
    </form>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
