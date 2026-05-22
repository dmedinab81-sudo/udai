<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

$errors = [];

// Opciones disponibles (por ahora fijas)
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

    // Validaciones básicas
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
        $errors[] = 'Año inválido (usa 4 dígitos).';
    }
	
    if (empty($errors)) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO ubicacion (mes,zona,distrito,anio) VALUES (?,?,?,?)");
        $stmt->execute([$mes, $zona, $distrito, $anio]);

        set_flash('Ubicación creada.');
        header('Location: ubicacion.php'); exit;
    } else {
        // si tienes set_flash para error, puedes usarlo; si no, se muestran abajo
        // set_flash(implode(' ', $errors));
    }
}

include __DIR__ . '/_header.php';
?>
<div class="row"><div class="col-md-6 mx-auto">
<h2>Crear Período</h2>

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

  <div class="mb-3">
    <label class="form-label">Mes</label>
    <select class="form-select" name="mes" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($MESES as $m): ?>
        <option value="<?= htmlspecialchars($m) ?>" <?= (($_POST['mes'] ?? '') === $m) ? 'selected' : '' ?>>
          <?= htmlspecialchars($m) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Zona</label>
    <select class="form-select" name="zona" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($ZONAS as $z): ?>
        <option value="<?= htmlspecialchars($z) ?>" <?= (($_POST['zona'] ?? '') === $z) ? 'selected' : '' ?>>
          <?= htmlspecialchars($z) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Distrito</label>
    <select class="form-select" name="distrito" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($DISTRITOS as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= (($_POST['distrito'] ?? '') === $d) ? 'selected' : '' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

	<div class="mb-3">
	  <label class="form-label">Año</label>
	  <input
		type="number"
		class="form-control"
		name="anio"
		required
		min="2000"
		max="2100"
		value="<?= htmlspecialchars($_POST['anio'] ?? '') ?>"
		placeholder="Ej: 2026">
	</div>

  <button class="btn btn-primary">Crear</button>
  <a class="btn btn-secondary" href="ubicacion.php">Cancelar</a>
</form>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>
