<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

$user = currentUser();

// Páginas públicas permitidas sin autenticación
$public_pages = [
    'login.php',
    'logout.php',
    'favicon.ico'
];

// Determinar página actual
$current_script = basename($_SERVER['SCRIPT_NAME']);

// Si no está autenticado y la página actual NO es pública, redirigir al login
if (!isAuthenticated() && !in_array($current_script, $public_pages, true)) {
    // Guardar página destino opcional para redirigir después de login
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'];
    set_flash('Debes iniciar sesión para acceder al sistema.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Helper para marcar la opción activa en el navbar
function nav_active(string $needle): string {
    $current = basename($_SERVER['SCRIPT_NAME']);
    return strpos($current, $needle) !== false ? 'active' : '';
}

// Mostrar y limpiar mensajes flash (si se usan)
$flash = get_flash();
$isAdmin = is_admin();

// Preparar rutas de logo (versión pequeña y fallback)
$logo_sm_fs = __DIR__ . '/assets/logo-sm.png';
$logo_fs = __DIR__ . '/assets/logo.png';
$logo_sm_url = BASE_URL . 'assets/logo-sm.png';
$logo_url = BASE_URL . 'assets/logo.png';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Ajustes para el logo en el navbar */
    .navbar-brand img {
      height: 36px;
      width: auto;
      object-fit: contain;
      display: inline-block;
      vertical-align: middle;
    }
    .brand-text {
      vertical-align: middle;
      display: inline-block;
      margin-left: .5rem;
      font-weight: 600;
      color: white;
    }
    @media (max-width: 575.98px) {
      .brand-text { display: none; }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php">
      <?php if (file_exists($logo_sm_fs)): ?>
        <img src="<?= htmlspecialchars($logo_sm_url) ?>" alt="<?= htmlspecialchars(APP_NAME) ?> logo">
      <?php elseif (file_exists($logo_fs)): ?>
        <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars(APP_NAME) ?> logo">
      <?php endif; ?>
      <span class="brand-text"><?= htmlspecialchars(APP_NAME) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= nav_active('index.php') ?>" href="<?= BASE_URL ?>index.php">Inicio</a>
        </li>
        <?php if (isAuthenticated()): ?>

          <li class="nav-item">
            <a class="nav-link <?= nav_active('estudiantes') ?>" href="<?= BASE_URL ?>estudiantes.php">Estudiantes</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= (nav_active('docente') || nav_active('representante') || nav_active('institucion') || nav_active('ubicacion') || nav_active('estudiante_asignatura')) ? 'active' : '' ?>"
               href="#" id="dropdownEntities" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Entidades
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdownEntities">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>docente_tutor.php">Docente Tutor</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>docente_apoyo.php">Docente Apoyo</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>representante_legal.php">Representantes</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>institucion.php">Instituciones</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>ubicacion.php">Periodo</a></li>
              </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('registro_nee') ?>" href="<?= BASE_URL ?>registro_nee.php">Registros NEE</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('report_nee') || nav_active('report_nee.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>report_nee.php">Reportes</a>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($user): ?>
          <?php if ($isAdmin): ?>
            <li class="nav-item">
              <a class="nav-link <?= nav_active('usuarios.php') || nav_active('register.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>usuarios.php">Usuarios</a>
            </li>
          <?php endif; ?>

          <li class="nav-item d-flex align-items-center me-2">
            <span class="nav-link disabled" style="opacity: .9;"><?= htmlspecialchars($user['nombre']) ?></span>
          </li>
          <?php if (!empty($user['rol'])): ?>
            <li class="nav-item d-flex align-items-center me-2">
              <span class="badge bg-light text-dark"><?= htmlspecialchars($user['rol']) ?></span>
            </li>
          <?php endif; ?>

          <!-- Logout vía POST (mejor que GET) con token CSRF -->
          <li class="nav-item">
            <form id="logoutForm" method="post" action="<?= BASE_URL ?>logout.php" class="d-inline">
              <?= csrf_input_html() ?>
              <button type="submit" class="btn btn-outline-light btn-sm">Salir</button>
            </form>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('login.php') ?>" href="<?= BASE_URL ?>login.php">Ingresar</a>
          </li>
          <!-- registro público deshabilitado -->
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <?php if ($flash): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>