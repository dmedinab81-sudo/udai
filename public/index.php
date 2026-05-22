<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
include __DIR__ . '/_header.php';

$pdo = null;
$counts = [
    'institucion' => 0,
    'estudiantes' => 0,
    'registros_nee' => 0,
    'usuarios' => 0
];
$recent_registros = [];

try {
    $pdo = getPDO();

    // Obtener conteos
    $tables = [
        'institucion' => 'SELECT COUNT(*) as c FROM institucion',
        'estudiantes' => 'SELECT COUNT(*) as c FROM estudiante',
        'registros_nee' => 'SELECT COUNT(*) as c FROM registro_nee',
        'usuarios' => 'SELECT COUNT(*) as c FROM usuarios'
    ];
    foreach ($tables as $key => $sql) {
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $counts[$key] = $row ? intval($row['c']) : 0;
    }

    // Últimos 5 registros NEE (si existen)
    $sql = "SELECT rn.id, rn.enlace_gestion, rn.observaciones,
                   u.mes AS ubicacion, da.nombres AS docente_apoyo, i.nombre AS institucion,
                   e.nombres AS estudiante, dt.nombres AS docente_tutor, rl.nombres AS representante
            FROM registro_nee rn
            LEFT JOIN ubicacion u ON rn.id_ubicacion = u.id
            LEFT JOIN docente_apoyo da ON rn.id_docente_apoyo = da.id
            LEFT JOIN institucion i ON rn.id_institucion = i.id
            LEFT JOIN estudiante e ON rn.id_estudiante = e.id
            LEFT JOIN docente_tutor dt ON rn.id_docente_tutor = dt.id
            LEFT JOIN representante_legal rl ON rn.id_representante = rl.id
            ORDER BY rn.id DESC
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $recent_registros = $stmt->fetchAll();

} catch (PDOException $ex) {
    // En caso de error de conexión, mostramos mensaje y dejamos counts en 0
    ?>
    <div class="alert alert-danger">
      Error al conectar con la base de datos: <?= htmlspecialchars($ex->getMessage()) ?>
    </div>
    <?php
}

// Preparar logo (si existe)
$logo_fs_path = __DIR__ . '/assets/logo.png';
$logo_url = BASE_URL . 'assets/logo.png';
?>

<div class="row">
  <div class="col-md-8 mx-auto text-center">
    

    <h1>Panel de control - <?= htmlspecialchars(APP_NAME) ?></h1>
    <p class="lead">Resumen rápido de la aplicación.</p>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-primary mb-3">
      <div class="card-body">
        <h5 class="card-title">Instituciones</h5>
        <p class="card-text display-6"><?= $counts['institucion'] ?></p>
        <a href="<?= BASE_URL ?>institucion.php" class="btn btn-light btn-sm">Ver instituciones</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success mb-3">
      <div class="card-body">
        <h5 class="card-title">Estudiantes</h5>
        <p class="card-text display-6"><?= $counts['estudiantes'] ?></p>
        <a href="<?= BASE_URL ?>estudiantes.php" class="btn btn-light btn-sm">Ver estudiantes</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning mb-3">
      <div class="card-body">
        <h5 class="card-title">Registros NEE</h5>
        <p class="card-text display-6"><?= $counts['registros_nee'] ?></p>
        <a href="<?= BASE_URL ?>registro_nee.php" class="btn btn-light btn-sm">Ver registros</a>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-secondary mb-3">
      <div class="card-body">
        <h5 class="card-title">Usuarios</h5>
        <p class="card-text display-6"><?= $counts['usuarios'] ?></p>
        <a href="<?= BASE_URL ?>usuarios.php" class="btn btn-light btn-sm">Gestionar usuarios</a>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4">
    <div class="list-group">
      <a href="<?= BASE_URL ?>estudiantes.php" class="list-group-item list-group-item-action">Estudiantes</a>
     <a href="<?= BASE_URL ?>docente_tutor.php" class="list-group-item list-group-item-action">Docente Tutor</a>
      <a href="<?= BASE_URL ?>docente_apoyo.php" class="list-group-item list-group-item-action">Docente Apoyo</a>
      <a href="<?= BASE_URL ?>institucion.php" class="list-group-item list-group-item-action">Instituciones</a>
      <a href="<?= BASE_URL ?>ubicacion.php" class="list-group-item list-group-item-action">Período</a>
      <a href="<?= BASE_URL ?>representante_legal.php" class="list-group-item list-group-item-action">Representantes Legales</a>
      <a href="<?= BASE_URL ?>registro_nee.php" class="list-group-item list-group-item-action">Registros NEE</a>
    </div>
  </div>

  <div class="col-md-8">
    <h4>Últimos registros NEE</h4>
    <?php if (empty($recent_registros)): ?>
      <div class="alert alert-info">No hay registros todavía.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-sm">
          <thead>
            <tr>
              <th>ID</th>
              <th>Estudiante</th>
              <th>Institución</th>
              <th>Período</th>
              <th>Docente Apoyo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_registros as $r): ?>
              <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['estudiante'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['institucion'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['ubicacion'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['docente_apoyo'] ?? '-') ?></td>
                <td>
                  <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>registro_nee_edit.php?id=<?= $r['id'] ?>">Editar</a>
                  <form method="post" action="<?= BASE_URL ?>registro_nee_delete.php" class="d-inline" onsubmit="return confirm('Eliminar registro?')">
                    <?= csrf_input_html() ?>
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>