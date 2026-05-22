<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

$pdo = getPDO();
$id = intval($_GET['id'] ?? 0);

// Opciones disponibles
$MESES = [
  'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
  'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'
];

$ZONAS = [
  'ZONA 5'
];

$DISTRITOS = [
  '24D02'
];

// Buscar registro
$stmt = $pdo->prepare("SELECT * FROM ubicacion WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    header('Location: ubicacion.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        set_flash('Error de seguridad: token CSRF inválido.');
        header('Location: ubicacion.php'); exit;
    }

    $mes = trim($_POST['mes'] ?? '');
    $zona = trim($_POST['zona'] ?? '');
    $distrito = trim($_POST['distrito'] ?? '');
    $anio = trim($_POST['anio'] ?? '');

    // Validaciones
    if (!in_array($mes, $MESES, true)) {
        $errors[] = 'Mes inválido.';
    }
    if (!in_array($zona, $ZONAS, true)) {
        $errors[] = 'Zona inválida.';
    }
    if (!in_array($distrito, $DISTRITOS, true)) {
        $errors[] = 'Distrito inválido.';
    }
    if (!preg_match('/^\d{4}$/', $anio)) {
        $errors[] = 'El año debe ser numérico y de 4 dígitos.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE ubicacion SET mes=?, zona=?, distrito=?, anio=? WHERE id=?"
        );
        $stmt->execute([$mes, $zona, $distrito, $anio, $id]);

        set_flash('Ubicación actualizada.');
        header('Location: ubicacion.php');
        exit;
    }
}

include __DIR__ . '/_header.php';
?>

<div class="row"><div class="col-md-6 mx-auto">
<h2>Editar Período</h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post">
  <?= csrf_input_html() ?>

  <!-- MES -->
  <div class="mb-3">
    <label class="form-label">Mes</label>
    <select class="form-select" name="mes" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($MESES as $m): ?>
        <option value="<?= $m ?>"
          <?= (($_POST['mes'] ?? $r['mes']) === $m) ? 'selected' : '' ?>>
          <?= $m ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- ZONA -->
  <div class="mb-3">
    <label class="form-label">Zona</label>
    <select class="form-select" name="zona" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($ZONAS as $z): ?>
        <option value="<?= $z ?>"
          <?= (($_POST['zona'] ?? $r['zona']) === $z) ? 'selected' : '' ?>>
          <?= $z ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- DISTRITO -->
  <div class="mb-3">
    <label class="form-label">Distrito</label>
    <select class="form-select" name="distrito" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($DISTRITOS as $d): ?>
        <option value="<?= $d ?>"
          <?= (($_POST['distrito'] ?? $r['distrito']) === $d) ? 'selected' : '' ?>>
          <?= $d ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- AÑO -->
  <div class="mb-3">
    <label class="form-label">Año</label>
    <input
      type="number"
      class="form-control"
      name="anio"
      required
      min="2000"
      max="2100"
      value="<?= htmlspecialchars($_POST['anio'] ?? $r['anio']) ?>">
  </div>

  <button class="btn btn-primary">Guardar</button>
  <a class="btn btn-secondary" href="ubicacion.php">Cancelar</a>
</form>
</div></div>

<?php include __DIR__ . '/_footer.php'; ?>
