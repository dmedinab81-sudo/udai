<?php
require_once __DIR__ . '/../app/auth.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        $errors[] = "Error de seguridad: token CSRF inválido.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $errors[] = "Email y contraseña obligatorios.";
        } elseif (attemptLogin($email, $password)) {
            set_flash('Bienvenido.');
            // redirigir a la página objetivo si existe
            $after = $_SESSION['after_login'] ?? null;
            if ($after) {
                unset($_SESSION['after_login']);
                header('Location: ' . $after);
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $errors[] = "Credenciales incorrectas.";
        }
    }
}

include __DIR__ . '/_header.php';

// Rutas del logo
$logo_fs_path = __DIR__ . '/assets/logo.png';
$logo_url = BASE_URL . 'assets/logo.png';
?>
<div class="row justify-content-center">
  <div class="col-12 col-md-8">
    <div class="card shadow-sm">
      <div class="row g-0 align-items-center">
        <!-- Columna del logo (izquierda en pantallas md+) -->
        <div class="col-12 col-md-5 d-flex justify-content-center align-items-center p-4" style="background-color: #f8f9fa;">
          <?php if (file_exists($logo_fs_path)): ?>
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars(APP_NAME) ?> logo" class="img-fluid" style="max-height:320px; width:auto;">
          <?php else: ?>
            <div class="text-center">
              <h4 class="mb-0"><?= htmlspecialchars(APP_NAME) ?></h4>
            </div>
          <?php endif; ?>
        </div>

        <!-- Columna del formulario -->
        <div class="col-12 col-md-7 p-4">
          <h3 class="mb-3">Ingresar</h3>

          <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>

          <form method="post" novalidate>
            <?= csrf_input_html() ?>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input class="form-control" type="password" name="password" required>
            </div>
            <div class="d-grid">
              <button class="btn btn-primary">Ingresar</button>
            </div>
          </form>

          <div class="mt-3">
            <small class="text-muted">Si no tienes cuenta, contacta al administrador.</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>