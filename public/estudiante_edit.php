<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
function validarCedulaEcuatoriana(string $cedula): bool {
    if (!preg_match('/^\d{10}$/', $cedula)) return false;

    $provincia = intval(substr($cedula, 0, 2));
    if ($provincia < 1 || $provincia > 24) return false;

    $tercer = intval($cedula[2]);
    if ($tercer > 5) return false;

    $coef = [2,1,2,1,2,1,2,1,2];
    $suma = 0;

    for ($i = 0; $i < 9; $i++) {
        $val = intval($cedula[$i]) * $coef[$i];
        if ($val >= 10) $val -= 9;
        $suma += $val;
    }

    $verif = (10 - ($suma % 10)) % 10;
    return $verif === intval($cedula[9]);
}

$pdo = getPDO();
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM estudiante WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$e = $stmt->fetch();

if (!$e) {
    header('Location: estudiantes.php'); exit;
}

$errors = [];

// Normalizar tipo_identificacion guardado (por si antes se guardó con acentos)
function normalizar_tipo_identificacion($v) {
    $v = trim((string)$v);
    if ($v === 'CÉDULA DE CIUDADANÍA' || $v === 'CEDULA DE CIUDADANIA') return 'CEDULA_CIUDADANIA';
    if ($v === 'CÓDIGO DEL ESTUDIANTE' || $v === 'CODIGO DEL ESTUDIANTE') return 'CODIGO_ESTUDIANTE';
    if ($v === 'CEDULA_CIUDADANIA' || $v === 'CODIGO_ESTUDIANTE') return $v;
    return $v; // fallback
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) {
        set_flash('Error de seguridad: token CSRF inválido.');
        header('Location: estudiantes.php'); exit;
    }

    $tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
    $identificacion = trim($_POST['identificacion'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $edad = $_POST['edad'] ?? null;
    $nee = trim($_POST['nee'] ?? '');
    $tipo_nee = trim($_POST['tipo_nee'] ?? '');
    $porcentaje_discapacidad = trim($_POST['porcentaje_discapacidad'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $jornada = trim($_POST['jornada'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $grado = trim($_POST['grado'] ?? '');

    // Validaciones
    if (empty($tipo_identificacion) || !in_array($tipo_identificacion, ['CEDULA_CIUDADANIA', 'CODIGO_ESTUDIANTE'])) {
        $errors[] = "Debe seleccionar un tipo de identificación válido.";
    }

    if (empty($identificacion)) {
		$errors[] = "La identificación es obligatoria.";
	} else {
		if ($tipo_identificacion === 'CEDULA_CIUDADANIA') {
			// ✅ Cédula ecuatoriana válida (10 dígitos + verificador)
			if (!validarCedulaEcuatoriana($identificacion)) {
				$errors[] = "La cédula ecuatoriana no es válida.";
			}
		} elseif ($tipo_identificacion === 'CODIGO_ESTUDIANTE') {
			if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $identificacion)) {
				$errors[] = "El código debe contener solo letras y números (máximo 10 caracteres).";
			}
		}
	}


    if (empty($nombres)) {
        $errors[] = "Los nombres son obligatorios.";
    }

    // Calcular edad desde fecha de nacimiento (servidor)
    $edad_calc = null;
    if (!empty($fecha_nacimiento)) {
        $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
        if ($fecha_nac) {
            $hoy = new DateTime();
            $edad_calc = $hoy->diff($fecha_nac)->y;
        } else {
            $errors[] = "Formato de fecha de nacimiento inválido.";
        }
    }

    if (!empty($nee) && !in_array($nee, ['ASOCIADAS_A_LA_DISCAPACIDAD', 'NO_ASOCIADAS_A_LA_DISCAPACIDAD'])) {
        $errors[] = "Tipo de NEE inválido.";
    }

    if (!empty($tipo_nee)) {
        $tipos_validos_asociadas = ['VISUAL', 'AUDITIVA', 'LENGUAJE', 'INTELECTUAL', 'PSICOSOCIAL', 'MULTIPLE', 'CARNET_EXTRANJERO', 'NINGUNA'];
        $tipos_validos_no_asociadas = ['ALTAS_CAPACIDADES', 'DIFICULTADES_ESPECIFICAS_APRENDIZAJE', 'ESTUDIANTES_SITUACION_VULNERABILIDAD'];

        if ($nee === 'ASOCIADAS_A_LA_DISCAPACIDAD' && !in_array($tipo_nee, $tipos_validos_asociadas)) {
            $errors[] = "Tipo NEE inválido para NEE asociadas a la discapacidad.";
        } elseif ($nee === 'NO_ASOCIADAS_A_LA_DISCAPACIDAD' && !in_array($tipo_nee, $tipos_validos_no_asociadas)) {
            $errors[] = "Tipo NEE inválido para NEE no asociadas a la discapacidad.";
        }
    }

	if ($porcentaje_discapacidad !== '' && $porcentaje_discapacidad !== null) {
    if (!ctype_digit((string)$porcentaje_discapacidad)) {
        $errors[] = "El porcentaje de discapacidad debe ser numérico.";
    } else {
        $porcentaje = (int)$porcentaje_discapacidad;
        if ($porcentaje < 0 || $porcentaje > 100) {
            $errors[] = "El porcentaje de discapacidad debe estar entre 0 y 100.";
        }
    }
}


    if (!empty($genero) && !in_array($genero, ['MASCULINO', 'FEMENINO', 'LGTBIQ+'])) {
        $errors[] = "Género inválido.";
    }

    if (!empty($jornada) && !in_array($jornada, ['MATUTINO', 'VESPERTINO', 'NOCTURNA'])) {
        $errors[] = "Jornada inválida.";
    }

    if (!empty($nivel) && !in_array($nivel, ['INICIAL', 'BASICA', 'BACHILLERATO'])) {
        $errors[] = "Nivel inválido.";
    }

    /*if (!empty($grado)) {
        $grados_inicial = ['INICIAL I', 'INICIAL II'];
        $grados_basica = ['1ERO EGB', '2DO EGB', '3ERO EGB', '4TO EGB', '5TO EGB', '6TO EGB', '7MO EGB', '8VO EGB', '9NO EGB', '10MO EGB'];
        $grados_bachillerato = ['1ERO BACHILLERATO', '2DO BACHILLERATO', '3ERO BACHILLERATO'];

        if ($nivel === 'INICIAL' && !in_array($grado, $grados_inicial)) {
            $errors[] = "Grado inválido para nivel Inicial.";
        } elseif ($nivel === 'BASICA' && !in_array($grado, $grados_basica)) {
            $errors[] = "Grado inválido para nivel Básica.";
        } elseif ($nivel === 'BACHILLERATO' && !in_array($grado, $grados_bachillerato)) {
            $errors[] = "Grado inválido para nivel Bachillerato.";
        }
    }*/
	
		if (!empty($grado)) {
		$grados_inicial = ['INICIAL_I', 'INICIAL_II'];

		$grados_basica = [
			'1ERO_EGB','2DO_EGB','3ERO_EGB','4TO_EGB','5TO_EGB',
			'6TO_EGB','7MO_EGB','8VO_EGB','9NO_EGB','10MO_EGB'
		];

		$grados_bachillerato = [
			'1ERO_BACHILLERATO','2DO_BACHILLERATO','3ERO_BACHILLERATO'
		];

		if ($nivel === 'INICIAL' && !in_array($grado, $grados_inicial)) {
			$errors[] = "Grado inválido para nivel Inicial.";
		} elseif ($nivel === 'BASICA' && !in_array($grado, $grados_basica)) {
			$errors[] = "Grado inválido para nivel Básica.";
		} elseif ($nivel === 'BACHILLERATO' && !in_array($grado, $grados_bachillerato)) {
			$errors[] = "Grado inválido para nivel Bachillerato.";
		}
	}


    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE estudiante
               SET tipo_identificacion=?,
                   identificacion=?,
                   nombres=?,
                   fecha_nacimiento=?,
                   edad=?,
                   nee=?,
                   tipo_nee=?,
                   porcentaje_discapacidad=?,
                   genero=?,
                   jornada=?,
                   nivel=?,
                   grado=?
             WHERE id=?
        ");

        $stmt->execute([
            $tipo_identificacion,
            $identificacion,
            $nombres,
            $fecha_nacimiento ?: null,
            $edad_calc ?: null,
            $nee ?: null,
            $tipo_nee ?: null,
            $porcentaje_discapacidad !== '' ? $porcentaje_discapacidad : null,
            $genero ?: null,
            $jornada ?: null,
            $nivel ?: null,
            $grado ?: null,
            $id
        ]);

        set_flash('Estudiante actualizado.');
        header('Location: estudiantes.php'); exit;
    }
}

include __DIR__ . '/_header.php';

// Valores para render (POST si existe, si no DB)
$tipo_identificacion_view = normalizar_tipo_identificacion($_POST['tipo_identificacion'] ?? $e['tipo_identificacion']);
$identificacion_view = $_POST['identificacion'] ?? $e['identificacion'];
$nombres_view = $_POST['nombres'] ?? $e['nombres'];
$fecha_nacimiento_view = $_POST['fecha_nacimiento'] ?? $e['fecha_nacimiento'];
$edad_view = $_POST['edad'] ?? $e['edad'];
$nee_view = $_POST['nee'] ?? $e['nee'];
$tipo_nee_view = $_POST['tipo_nee'] ?? $e['tipo_nee'];
$porcentaje_discapacidad_view = $_POST['porcentaje_discapacidad'] ?? $e['porcentaje_discapacidad'];
$porcentaje_discapacidad_view = ($porcentaje_discapacidad_view === null) ? '' : (string)$porcentaje_discapacidad_view;


$genero_view = $_POST['genero'] ?? $e['genero'];
$jornada_view = $_POST['jornada'] ?? $e['jornada'];
$nivel_view = $_POST['nivel'] ?? $e['nivel'];
$grado_view = $_POST['grado'] ?? $e['grado'];
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <h2>Editar Estudiante</h2>

    <?php foreach ($errors as $er): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($er) ?></div>
    <?php endforeach; ?>

    <form method="post" id="formEstudiante">
      <?= csrf_input_html() ?>

      <div class="mb-3">
        <label class="form-label">Tipo Identificación *</label>
        <select class="form-control" name="tipo_identificacion" id="tipo_identificacion" required>
          <option value="">Seleccione...</option>
          <option value="CEDULA_CIUDADANIA" <?= $tipo_identificacion_view === 'CEDULA_CIUDADANIA' ? 'selected' : '' ?>>CÉDULA DE CIUDADANÍA</option>
          <option value="CODIGO_ESTUDIANTE" <?= $tipo_identificacion_view === 'CODIGO_ESTUDIANTE' ? 'selected' : '' ?>>CÓDIGO DEL ESTUDIANTE</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Identificación *</label>

        <div style="display:flex; gap:8px;">
			<input class="form-control" name="identificacion" id="identificacion" required maxlength="10"
				value="<?= htmlspecialchars($identificacion_view) ?>">	 
          <button type="button" class="btn btn-secondary" id="btn_buscar_rc">Buscar</button>
        </div>

        <small class="form-text text-muted" id="identificacion_hint">Solo números, máximo 10 dígitos</small>
        <div id="rc_estado" style="font-size:12px;margin-top:6px;color:#666;"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Nombres *</label>
        <input class="form-control" name="nombres" id="nombres" required value="<?= htmlspecialchars($nombres_view) ?>">
      </div>

      <div class="mb-3 row">
        <div class="col">
          <label class="form-label">Fecha Nacimiento</label>
          <input class="form-control" type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                 value="<?= htmlspecialchars($fecha_nacimiento_view) ?>">
        </div>
        <div class="col">
          <label class="form-label">Edad</label>
          <input class="form-control" type="number" name="edad" id="edad" readonly
                 value="<?= htmlspecialchars($edad_view) ?>">
          <small class="form-text text-muted">Se calcula automáticamente</small>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">NEE</label>
        <select class="form-control" name="nee" id="nee">
          <option value="">Seleccione...</option>
          <option value="ASOCIADAS_A_LA_DISCAPACIDAD" <?= $nee_view === 'ASOCIADAS_A_LA_DISCAPACIDAD' ? 'selected' : '' ?>>ASOCIADAS A LA DISCAPACIDAD</option>
          <option value="NO_ASOCIADAS_A_LA_DISCAPACIDAD" <?= $nee_view === 'NO_ASOCIADAS_A_LA_DISCAPACIDAD' ? 'selected' : '' ?>>NO ASOCIADAS A LA DISCAPACIDAD</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Tipo NEE</label>
        <select class="form-control" name="tipo_nee" id="tipo_nee">
          <option value="">Seleccione primero NEE...</option>
        </select>
      </div>

      <!--div class="mb-3">
	  <label class="form-label">% Discapacidad</label>

	  <div class="row g-2">
		<div class="col-md-4">
		  <select class="form-control" id="porc_modo">
			<option value="NO_APLICA" <?//= $porcentaje_discapacidad_view === 'NO_APLICA' ? 'selected' : '' ?>>NO APLICA</option>
			<option value="PORCENTAJE" <?//= $porcentaje_discapacidad_view !== 'NO_APLICA' ? 'selected' : '' ?>>PORCENTAJE</option>
		  </select>
		</div>

		<div class="col-md-8">
		  <input class="form-control" type="number"
				 id="porcentaje_discapacidad_num"
				 min="0" max="100"
				 value="<?//= $porcentaje_discapacidad_view !== 'NO_APLICA' ? htmlspecialchars($porcentaje_discapacidad_view) : '' ?>"
				 placeholder="0 a 100">
		  <!-- este es el que se envía al backend -->
		  <!--input type="hidden" name="porcentaje_discapacidad" id="porcentaje_discapacidad"
				 value="<?//= htmlspecialchars($porcentaje_discapacidad_view) ?>">
		  <small class="form-text text-muted">Seleccione NO APLICA o ingrese un número entre 0 y 100</small>
		</div>
	  </div>
	</div-->

	<div class="mb-3">
	  <label class="form-label">% Discapacidad</label>

	  <select class="form-control" name="porcentaje_discapacidad" id="porcentaje_discapacidad">
		<option value="" <?= ($porcentaje_discapacidad_view === '') ? 'selected' : '' ?>>NO APLICA</option>
		<?php
		  $valor = $porcentaje_discapacidad_view; // '' o '0'..'100'
		  for ($i = 0; $i <= 100; $i++) {
			$selected = ($valor !== '' && (int)$valor === $i) ? 'selected' : '';
			echo "<option value=\"$i\" $selected>$i%</option>";
		  }
		?>
	  </select>

	  <small class="form-text text-muted">Seleccione un porcentaje o NO APLICA</small>
	</div>


      <div class="mb-3">
        <label class="form-label">Género</label>
        <select class="form-control" name="genero" id="genero">
          <option value="">Seleccione...</option>
          <option value="MASCULINO" <?= $genero_view === 'MASCULINO' ? 'selected' : '' ?>>MASCULINO</option>
          <option value="FEMENINO" <?= $genero_view === 'FEMENINO' ? 'selected' : '' ?>>FEMENINO</option>
          <option value="LGTBIQ+" <?= $genero_view === 'LGTBIQ+' ? 'selected' : '' ?>>LGTBIQ+</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Jornada</label>
        <select class="form-control" name="jornada">
          <option value="">Seleccione...</option>
          <option value="MATUTINO" <?= $jornada_view === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
          <option value="VESPERTINO" <?= $jornada_view === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
          <option value="NOCTURNA" <?= $jornada_view === 'NOCTURNA' ? 'selected' : '' ?>>NOCTURNA</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Nivel</label>
        <select class="form-control" name="nivel" id="nivel">
          <option value="">Seleccione...</option>
          <option value="INICIAL" <?= $nivel_view === 'INICIAL' ? 'selected' : '' ?>>INICIAL</option>
          <option value="BASICA" <?= $nivel_view === 'BASICA' ? 'selected' : '' ?>>BÁSICA</option>
          <option value="BACHILLERATO" <?= $nivel_view === 'BACHILLERATO' ? 'selected' : '' ?>>BACHILLERATO</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Grado</label>
        <select class="form-control" name="grado" id="grado">
          <option value="">Seleccione primero nivel...</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Guardar</button>
      <a class="btn btn-secondary" href="estudiantes.php">Cancelar</a>
    </form>
  </div>
</div>

<script>
// Cambiar validación de identificación según tipo
/*document.getElementById('tipo_identificacion').addEventListener('change', function() {
  var identificacionInput = document.getElementById('identificacion');
  var hint = document.getElementById('identificacion_hint');

  if (this.value === 'CEDULA_CIUDADANIA') {
    identificacionInput.pattern = '[0-9]{1,10}';
    identificacionInput.title = 'Solo números, máximo 10 dígitos';
    hint.textContent = 'Solo números, máximo 10 dígitos';
  } else if (this.value === 'CODIGO_ESTUDIANTE') {
    identificacionInput.pattern = '[A-Za-z0-9]{1,10}';
    identificacionInput.title = 'Letras y números, máximo 10 caracteres';
    hint.textContent = 'Letras y números, máximo 10 caracteres';
  }
});*/
document.getElementById('tipo_identificacion').addEventListener('change', function() {
  var identificacionInput = document.getElementById('identificacion');
  var hint = document.getElementById('identificacion_hint');

  if (this.value === 'CEDULA_CIUDADANIA') {
    identificacionInput.pattern = '[0-9]{10}';
    identificacionInput.title = 'Solo números, exactamente 10 dígitos';
    hint.textContent = 'Solo números, exactamente 10 dígitos';

    identificacionInput.oninput = function() {
      this.value = this.value.replace(/[^0-9]/g,'').slice(0,10);
    };
  } else if (this.value === 'CODIGO_ESTUDIANTE') {
    identificacionInput.pattern = '[A-Za-z0-9]{1,10}';
    identificacionInput.title = 'Letras y números, máximo 10 caracteres';
    hint.textContent = 'Letras y números, máximo 10 caracteres';

    identificacionInput.oninput = function() {
      this.value = this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,10);
    };
  } else {
    identificacionInput.pattern = '';
    identificacionInput.title = '';
    hint.textContent = '';
  }
});


// Calcular edad automáticamente
document.getElementById('fecha_nacimiento').addEventListener('change', function() {
  if (!this.value) { document.getElementById('edad').value = ''; return; }
  var fechaNac = new Date(this.value);
  var hoy = new Date();
  var edad = hoy.getFullYear() - fechaNac.getFullYear();
  var mes = hoy.getMonth() - fechaNac.getMonth();
  if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) edad--;
  document.getElementById('edad').value = edad >= 0 ? edad : '';
});

// Opciones de Tipo NEE según NEE
var opcionesTipoNEE = {
  'ASOCIADAS_A_LA_DISCAPACIDAD': [
    {value: 'VISUAL', text: 'VISUAL'},
    {value: 'AUDITIVA', text: 'AUDITIVA'},
    {value: 'LENGUAJE', text: 'LENGUAJE'},
    {value: 'INTELECTUAL', text: 'INTELECTUAL'},
    {value: 'PSICOSOCIAL', text: 'PSICOSOCIAL'},
    {value: 'MULTIPLE', text: 'MÚLTIPLE'},
    {value: 'CARNET_EXTRANJERO', text: 'CARNET DE EXTRANJERO'},
    {value: 'NINGUNA', text: 'NINGUNA'}
  ],
  'NO_ASOCIADAS_A_LA_DISCAPACIDAD': [
    {value: 'ALTAS_CAPACIDADES', text: 'ALTAS CAPACIDADES'},
    {value: 'DIFICULTADES_ESPECIFICAS_APRENDIZAJE', text: 'DIFICULTADES ESPECÍFICAS DE APRENDIZAJE'},
    {value: 'ESTUDIANTES_SITUACION_VULNERABILIDAD', text: 'ESTUDIANTES EN SITUACIÓN DE VULNERABILIDAD'}
  ]
};

document.getElementById('nee').addEventListener('change', function() {
  var tipoNEESelect = document.getElementById('tipo_nee');
  var valorNEE = this.value;

  tipoNEESelect.innerHTML = '<option value="">Seleccione...</option>';
  if (valorNEE && opcionesTipoNEE[valorNEE]) {
    for (var i = 0; i < opcionesTipoNEE[valorNEE].length; i++) {
      var opcion = opcionesTipoNEE[valorNEE][i];
      var opt = document.createElement('option');
      opt.value = opcion.value;
      opt.textContent = opcion.text;
      tipoNEESelect.appendChild(opt);
    }
  }
});

document.getElementById('nee').addEventListener('change', function () {
  const porcentaje = document.getElementById('porcentaje_discapacidad');

  if (this.value === 'NO_ASOCIADAS_A_LA_DISCAPACIDAD' || this.value === '') {
    porcentaje.value = '';
    porcentaje.disabled = true;
  } else {
    porcentaje.disabled = false;
  }
});

// Opciones de Grado según Nivel
var opcionesGrado = {
  'INICIAL': [
    {value: 'INICIAL_I', text: 'INICIAL I'},
    {value: 'INICIAL_II', text: 'INICIAL II'}
  ],
  'BASICA': [
    {value: '1ERO_EGB', text: '1ERO EGB'},
    {value: '2DO_EGB', text: '2DO EGB'},
    {value: '3ERO_EGB', text: '3ERO EGB'},
    {value: '4TO_EGB', text: '4TO EGB'},
    {value: '5TO_EGB', text: '5TO EGB'},
    {value: '6TO_EGB', text: '6TO EGB'},
    {value: '7MO_EGB', text: '7MO EGB'},
    {value: '8VO_EGB', text: '8VO EGB'},
    {value: '9NO_EGB', text: '9NO EGB'},
    {value: '10MO_EGB', text: '10MO EGB'}
  ],
  'BACHILLERATO': [
    {value: '1ERO_BACHILLERATO', text: '1ERO BACHILLERATO'},
    {value: '2DO_BACHILLERATO', text: '2DO BACHILLERATO'},
    {value: '3ERO_BACHILLERATO', text: '3ERO BACHILLERATO'}
  ]
};


document.getElementById('nivel').addEventListener('change', function() {
  var gradoSelect = document.getElementById('grado');
  var valorNivel = this.value;

  gradoSelect.innerHTML = '<option value="">Seleccione...</option>';
  if (valorNivel && opcionesGrado[valorNivel]) {
    for (var i = 0; i < opcionesGrado[valorNivel].length; i++) {
      var opcion = opcionesGrado[valorNivel][i];
      var opt = document.createElement('option');
      opt.value = opcion.value;
      opt.textContent = opcion.text;
      gradoSelect.appendChild(opt);
    }
  }
});

// ---------- Consulta RC (HTTPS) ----------
//var URL_RC = 'https://sais-hglp.saludzona5.gob.ec/sais/cliente_datos_udai.php';
var URL_RC = 'rc_proxy.php'; // o '/rc_proxy.php' según dónde lo pongas
function setEstado(msg) {
  var el = document.getElementById('rc_estado');
  if (el) el.innerHTML = msg || '';
}

function ddmmyyyyToInputDate(fecha) {
  if (!fecha) return '';
  var p = fecha.split('/');
  if (p.length !== 3) return '';
  return p[2] + '-' + p[1] + '-' + p[0];
}

function buscarRC(cedula, cb) {
  var xhr = new XMLHttpRequest();
  var url = URL_RC + '?cedula=' + encodeURIComponent(cedula);

  xhr.open('GET', url, true);
  xhr.timeout = 30000;

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try { cb(null, JSON.parse(xhr.responseText)); }
        catch (e) { cb(e, null); }
      } else {
        cb(new Error('HTTP ' + xhr.status), null);
      }
    }
  };

  xhr.ontimeout = function () { cb(new Error('TIMEOUT'), null); };
  xhr.onerror = function () { cb(new Error('NETWORK'), null); };

  xhr.send();
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

  const verif = (10 - (suma % 10)) % 10;
  return verif === parseInt(cedula[9], 10);
}

function ejecutarBusquedaRC() {
  var tipo = document.getElementById('tipo_identificacion').value;
  var cedula = (document.getElementById('identificacion').value || '').replace(/\s+/g, '');

  if (tipo !== 'CEDULA_CIUDADANIA') { setEstado('Seleccione "CÉDULA DE CIUDADANÍA" para buscar.'); return; }
  if (!validarCedulaEC(cedula)) { setEstado('La cédula ecuatoriana no es válida.'); return; }


  setEstado('Buscando...');
  buscarRC(cedula, function (err, data) {
    if (err || !data || !data.success) { setEstado('No se pudo consultar.'); return; }

    setEstado('Encontrado ✅');
    document.getElementById('nombres').value = data.nombres || '';
    document.getElementById('fecha_nacimiento').value = ddmmyyyyToInputDate(data.fecha_nacimiento);

    // ✅ Autollenar Género según RC (HOMBRE/MUJER)
    if (data.sexo) {
      var sexo = String(data.sexo).toUpperCase().trim();
      var generoSelect = document.querySelector('select[name="genero"]');
      if (generoSelect) {
        if (sexo === 'HOMBRE') generoSelect.value = 'MASCULINO';
        else if (sexo === 'MUJER') generoSelect.value = 'FEMENINO';
      }
    }

    // Recalcular edad
    var f = document.getElementById('fecha_nacimiento');
    if (f) {
      var ev = document.createEvent('Event');
      ev.initEvent('change', true, true);
      f.dispatchEvent(ev);
    }
  });
}

document.getElementById('btn_buscar_rc').addEventListener('click', ejecutarBusquedaRC);

// Inicializar selects al cargar (por si hay datos en DB/POST)
window.addEventListener('DOMContentLoaded', function() {
  document.getElementById('tipo_identificacion').dispatchEvent(new Event('change'));

  // Inicializar Tipo NEE si ya hay valor
  var neeSelect = document.getElementById('nee');
  if (neeSelect.value) {
    neeSelect.dispatchEvent(new Event('change'));
    var tipoNEEValue = '<?= htmlspecialchars($tipo_nee_view) ?>';
    if (tipoNEEValue) {
      setTimeout(function() { document.getElementById('tipo_nee').value = tipoNEEValue; }, 10);
    }
  }

  // Inicializar Grado si ya hay nivel
  var nivelSelect = document.getElementById('nivel');
  if (nivelSelect.value) {
    nivelSelect.dispatchEvent(new Event('change'));
    var gradoValue = '<?= htmlspecialchars($grado_view) ?>';
    if (gradoValue) {
      setTimeout(function() { document.getElementById('grado').value = gradoValue; }, 10);
    }
  }

  // Calcular edad si hay fecha
  var fechaNacInput = document.getElementById('fecha_nacimiento');
  if (fechaNacInput.value) fechaNacInput.dispatchEvent(new Event('change'));
});

function syncPorcentajeDiscapacidad() {
  var modo = document.getElementById('porc_modo');
  var num  = document.getElementById('porcentaje_discapacidad_num');
  var hid  = document.getElementById('porcentaje_discapacidad');

  if (!modo || !num || !hid) return;

  if (modo.value === 'NO_APLICA') {
    num.value = '';
    num.disabled = true;
    hid.value = 'NO_APLICA';
  } else {
    num.disabled = false;
    // si aún no hay valor, deja vacío
    hid.value = (num.value || '').trim();
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var modo = document.getElementById('porc_modo');
  var num  = document.getElementById('porcentaje_discapacidad_num');

  if (modo) modo.addEventListener('change', syncPorcentajeDiscapacidad);
  if (num) num.addEventListener('input', syncPorcentajeDiscapacidad);

  // Inicializar estado
  syncPorcentajeDiscapacidad();
});

</script>

<?php include __DIR__ . '/_footer.php'; ?>
