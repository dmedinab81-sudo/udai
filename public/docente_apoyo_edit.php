<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
function validarCedulaEcuatoriana(string $cedula): bool {
    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $provincia = intval(substr($cedula, 0, 2));
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }

    $tercer = intval($cedula[2]);
    if ($tercer > 5) {
        return false;
    }

    $coef = [2,1,2,1,2,1,2,1,2];
    $suma = 0;

    for ($i = 0; $i < 9; $i++) {
        $valor = intval($cedula[$i]) * $coef[$i];
        if ($valor >= 10) $valor -= 9;
        $suma += $valor;
    }

    $verificador = (10 - ($suma % 10)) % 10;

    return $verificador === intval($cedula[9]);
}
$pdo = getPDO();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM docente_apoyo WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { header('Location: docente_apoyo.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        set_flash('Error de seguridad: token CSRF inválido.');
        header('Location: docente_apoyo.php'); exit;
    }

    $cedula = trim($_POST['cedula'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if ($cedula === '' || $nombres === '') {
		$errors[] = "Cédula y nombres obligatorios.";
	} elseif (!validarCedulaEcuatoriana($cedula)) {
		$errors[] = "La cédula ecuatoriana no es válida.";
	}
	// ✅ Teléfono: solo números y exactamente 10 dígitos
	if ($telefono === '') {
		$errors[] = "El teléfono es obligatorio.";
	} elseif (!preg_match('/^\d{10}$/', $telefono)) {
		$errors[] = "El teléfono debe tener exactamente 10 dígitos numéricos.";
	}
    // ✅ solo dominio docentes.educacion.edu.ec
    if ($correo && !preg_match('/^[A-Za-z0-9._%+-]+@docentes\.educacion\.edu\.ec$/', $correo)) {
        $errors[] = "El correo debe ser @docentes.educacion.edu.ec";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE docente_apoyo SET cedula=?, nombres=?, telefono=?, correo=? WHERE id=?");
        $stmt->execute([$cedula,$nombres,$telefono,$correo,$id]);
        set_flash('Docente apoyo actualizado.');
        header('Location: docente_apoyo.php'); exit;
    }
}
include __DIR__ . '/_header.php';
?>
<div class="row"><div class="col-md-6 mx-auto">
<h2>Editar Docente Apoyo</h2>
<?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>

<form method="post">
  <?= csrf_input_html() ?>

  <div class="mb-3">
    <label class="form-label">Cédula</label>
    <div style="display:flex; gap:8px;">
      <input class="form-control"
		   name="cedula"
		   id="cedula"
		   type="text"
		   maxlength="10"
		   pattern="\d{10}"
		   inputmode="numeric"
		   autocomplete="off"
		   required
		   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
		   value="<?= htmlspecialchars($_POST['cedula'] ?? ($row['cedula'] ?? '')) ?>">
      <button type="button" class="btn btn-secondary" id="btn_buscar_rc">Buscar</button>
    </div>
    <div id="rc_estado" style="font-size:12px;margin-top:6px;color:#666;"></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Nombres</label>
    <input class="form-control" name="nombres" id="nombres"
           value="<?= htmlspecialchars($_POST['nombres'] ?? $row['nombres']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Teléfono</label>
    <input class="form-control"
       name="telefono"
       id="telefono"
       type="tel"
       inputmode="numeric"
       autocomplete="tel"
       maxlength="10"
       pattern="[0-9]{10}"
       required
       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
       value="<?= htmlspecialchars($_POST['telefono'] ?? $row['telefono']) ?>"
  </div>

  <div class="mb-3">
    <label class="form-label">Correo institucional</label>
    <input class="form-control" name="correo" type="email"
           placeholder="usuario@docentes.educacion.edu.ec"
           value="<?= htmlspecialchars($_POST['correo'] ?? $row['correo']) ?>">
  </div>

  <button class="btn btn-primary">Guardar</button>
  <a class="btn btn-secondary" href="docente_apoyo.php">Cancelar</a>
</form>
</div></div>

<script>
//var URL_RC = 'https://sais-hglp.saludzona5.gob.ec/sais/cliente_datos_udai.php';
var URL_RC = 'rc_proxy.php'; // o '/rc_proxy.php' según dónde lo pongas
function setEstado(msg) {
  document.getElementById('rc_estado').innerHTML = msg || '';
}
function validarCedulaEC(cedula) {
  if (!/^\d{10}$/.test(cedula)) return false;

  const provincia = parseInt(cedula.substr(0,2), 10);
  if (provincia < 1 || provincia > 24) return false;

  const tercer = parseInt(cedula[2], 10);
  if (tercer > 5) return false;

  const coef = [2,1,2,1,2,1,2,1,2];
  let suma = 0;

  for (let i = 0; i < 9; i++) {
    let val = parseInt(cedula[i], 10) * coef[i];
    if (val >= 10) val -= 9;
    suma += val;
  }

  const verificador = (10 - (suma % 10)) % 10;
  return verificador === parseInt(cedula[9], 10);
}

function buscarRC(cedula, cb) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', URL_RC + '?cedula=' + encodeURIComponent(cedula), true);
  xhr.timeout = 30000;

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try { cb(null, JSON.parse(xhr.responseText)); }
        catch (e) { cb(e); }
      } else cb(new Error('HTTP'));
    }
  };

  xhr.onerror = xhr.ontimeout = function () { cb(new Error('NET')); };
  xhr.send();
}

document.getElementById('btn_buscar_rc').addEventListener('click', function () {
  var cedula = document.getElementById('cedula').value.trim();

    if (!validarCedulaEC(cedula)) {
  setEstado('La cédula ecuatoriana no es válida.');
  return;
}

  setEstado('Buscando...');
  buscarRC(cedula, function (err, data) {
    if (err || !data || !data.success) {
      setEstado('No se pudo consultar (red/timeout).');
      return;
    }
    setEstado('Encontrado ✅');
    document.getElementById('nombres').value = data.nombres || '';
  });
});
</script>

<?php include __DIR__ . '/_footer.php'; ?>
