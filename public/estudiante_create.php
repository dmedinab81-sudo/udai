<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
function validarCedulaEcuatoriana(string $cedula): bool {
    if (!preg_match('/^\d{10}$/', $cedula)) return false;

    $provincia = intval(substr($cedula, 0, 2));
    if ($provincia < 1 || $provincia > 24) return false;

    $tercer = intval($cedula[2]);
    if ($tercer > 7) return false;

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
$errors = [];
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

    /*if (empty($identificacion)) {
        $errors[] = "La identificación es obligatoria.";
    } else {
        if ($tipo_identificacion === 'CEDULA_CIUDADANIA') {
            if (!preg_match('/^[0-9]{1,10}$/', $identificacion)) {
                $errors[] = "La cédula debe contener solo números (máximo 10 dígitos).";
            }
        } elseif ($tipo_identificacion === 'CODIGO_ESTUDIANTE') {
            if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $identificacion)) {
                $errors[] = "El código debe contener solo letras y números (máximo 10 caracteres).";
            }
        }
    }*/
	if (empty($identificacion)) {
		$errors[] = "La identificación es obligatoria.";
	} else {
		if ($tipo_identificacion === 'CEDULA_CIUDADANIA') {
			// ✅ Exactamente 10 dígitos y válida como cédula ecuatoriana
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

    // Calcular edad desde fecha de nacimiento
    $edad = null;
    if (!empty($fecha_nacimiento)) {
        $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
        if ($fecha_nac) {
            $hoy = new DateTime();
            $edad = $hoy->diff($fecha_nac)->y;
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
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO estudiante (tipo_identificacion, identificacion, nombres, fecha_nacimiento, edad, nee, tipo_nee, porcentaje_discapacidad, genero, jornada, nivel, grado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$tipo_identificacion, $identificacion, $nombres, $fecha_nacimiento ?: null, $edad ?: null, $nee ?: null, $tipo_nee ?: null, $porcentaje_discapacidad ?: null, $genero ?: null, $jornada ?: null, $nivel ?: null, $grado ?: null]);
        set_flash('Estudiante creado.');
        header('Location: estudiantes.php');
        exit;
    }
}

include __DIR__ . '/_header.php';
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <h2>Crear Estudiante</h2>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post" id="formEstudiante">
      <?= csrf_input_html() ?>

      <div class="mb-3">
        <label class="form-label">Tipo Identificación *</label>
        <select class="form-control" name="tipo_identificacion" id="tipo_identificacion" required>
          <option value="">Seleccione...</option>
          <option value="CEDULA_CIUDADANIA" <?= ($_POST['tipo_identificacion'] ?? '') === 'CEDULA_CIUDADANIA' ? 'selected' : '' ?>>CÉDULA DE CIUDADANÍA</option>
          <option value="CODIGO_ESTUDIANTE" <?= ($_POST['tipo_identificacion'] ?? '') === 'CODIGO_ESTUDIANTE' ? 'selected' : '' ?>>CÓDIGO DEL ESTUDIANTE</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Identificación *</label>

        <div style="display:flex; gap:8px;">
         <input class="form-control" name="identificacion" id="identificacion" required maxlength="10"
			   value="<?= htmlspecialchars($_POST['identificacion'] ?? '') ?>"
			   oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,10)">
          <button type="button" class="btn btn-secondary" id="btn_buscar_rc">Buscar</button>
        </div>

        <small class="form-text text-muted" id="identificacion_hint">Solo números, máximo 10 dígitos</small>
        <div id="rc_estado" style="font-size:12px;margin-top:6px;color:#666;"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Nombres *</label>
        <input class="form-control" name="nombres" id="nombres" required value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>">
      </div>

      <div class="mb-3 row">
        <div class="col">
          <label class="form-label">Fecha Nacimiento</label>
          <input class="form-control" type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="col">
          <label class="form-label">Edad</label>
          <input class="form-control" type="number" name="edad" id="edad" readonly value="<?= htmlspecialchars($_POST['edad'] ?? '') ?>">
          <small class="form-text text-muted">Se calcula automáticamente</small>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">NEE</label>
        <select class="form-control" name="nee" id="nee">
          <option value="">Seleccione...</option>
          <option value="ASOCIADAS_A_LA_DISCAPACIDAD" <?= ($_POST['nee'] ?? '') === 'ASOCIADAS_A_LA_DISCAPACIDAD' ? 'selected' : '' ?>>ASOCIADAS A LA DISCAPACIDAD</option>
          <option value="NO_ASOCIADAS_A_LA_DISCAPACIDAD" <?= ($_POST['nee'] ?? '') === 'NO_ASOCIADAS_A_LA_DISCAPACIDAD' ? 'selected' : '' ?>>NO ASOCIADAS A LA DISCAPACIDAD</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Tipo NEE</label>
        <select class="form-control" name="tipo_nee" id="tipo_nee">
          <option value="">Seleccione primero NEE...</option>
        </select>
      </div>

		<div class="mb-3">
		  <label class="form-label">% Discapacidad</label>

		  <select class="form-control" name="porcentaje_discapacidad" id="porcentaje_discapacidad">
			<option value="">NO APLICA</option>
			<?php
			  $valor = $_POST['porcentaje_discapacidad'] ?? '';
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
        <select class="form-control" name="genero">
          <option value="">Seleccione...</option>
          <option value="MASCULINO" <?= ($_POST['genero'] ?? '') === 'MASCULINO' ? 'selected' : '' ?>>MASCULINO</option>
          <option value="FEMENINO" <?= ($_POST['genero'] ?? '') === 'FEMENINO' ? 'selected' : '' ?>>FEMENINO</option>
          <option value="LGTBIQ+" <?= ($_POST['genero'] ?? '') === 'LGTBIQ+' ? 'selected' : '' ?>>LGTBIQ+</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Jornada</label>
        <select class="form-control" name="jornada">
          <option value="">Seleccione...</option>
          <option value="MATUTINO" <?= ($_POST['jornada'] ?? '') === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
          <option value="VESPERTINO" <?= ($_POST['jornada'] ?? '') === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
          <option value="NOCTURNA" <?= ($_POST['jornada'] ?? '') === 'NOCTURNA' ? 'selected' : '' ?>>NOCTURNA</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Nivel</label>
        <select class="form-control" name="nivel" id="nivel">
          <option value="">Seleccione...</option>
          <option value="INICIAL" <?= ($_POST['nivel'] ?? '') === 'INICIAL' ? 'selected' : '' ?>>INICIAL</option>
          <option value="BASICA" <?= ($_POST['nivel'] ?? '') === 'BASICA' ? 'selected' : '' ?>>BÁSICA</option>
          <option value="BACHILLERATO" <?= ($_POST['nivel'] ?? '') === 'BACHILLERATO' ? 'selected' : '' ?>>BACHILLERATO</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Grado</label>
        <select class="form-control" name="grado" id="grado">
          <option value="">Seleccione primero nivel...</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Crear</button>
      <a class="btn btn-secondary" href="estudiantes.php">Cancelar</a>
    </form>
  </div>
</div>

<script>
// Cambiar validación de identificación según tipo
document.getElementById('tipo_identificacion').addEventListener('change', function() {
  var identificacionInput = document.getElementById('identificacion');
  var hint = document.getElementById('identificacion_hint');

 /* if (this.value === 'CEDULA_CIUDADANIA') {
    identificacionInput.pattern = '[0-9]{1,10}';
    identificacionInput.title = 'Solo números, máximo 10 dígitos';
    hint.textContent = 'Solo números, máximo 10 dígitos';
  } else if (this.value === 'CODIGO_ESTUDIANTE') {
    identificacionInput.pattern = '[A-Za-z0-9]{1,10}';
    identificacionInput.title = 'Letras y números, máximo 10 caracteres';
    hint.textContent = 'Letras y números, máximo 10 caracteres';
  }*/
  if (this.value === 'CEDULA_CIUDADANIA') {
  identificacionInput.pattern = '[0-9]{10}';
  identificacionInput.title = 'Solo números, exactamente 10 dígitos';
  hint.textContent = 'Solo números, exactamente 10 dígitos';

  identificacionInput.oninput = function() {
    this.value = this.value.replace(/[^0-9]/g,'').slice(0,10);
  }
} else if (this.value === 'CODIGO_ESTUDIANTE') {
  identificacionInput.pattern = '[A-Za-z0-9]{1,10}';
  identificacionInput.title = 'Letras y números, máximo 10 caracteres';
  hint.textContent = 'Letras y números, máximo 10 caracteres';

  identificacionInput.oninput = function() {
    this.value = this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,10);
  }
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
  if (tercer > 7) return false;

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
	
	
	var f = document.getElementById('fecha_nacimiento');
    if (f) {
      var ev = document.createEvent('Event');
      ev.initEvent('change', true, true);
      f.dispatchEvent(ev);
    }
  });
}

document.getElementById('btn_buscar_rc').addEventListener('click', ejecutarBusquedaRC);

// Inicializar
window.addEventListener('DOMContentLoaded', function() {
  document.getElementById('tipo_identificacion').dispatchEvent(new Event('change'));

  var neeSelect = document.getElementById('nee');
  if (neeSelect.value) {
    neeSelect.dispatchEvent(new Event('change'));
    var tipoNEEValue = '<?= $_POST['tipo_nee'] ?? '' ?>';
    if (tipoNEEValue) {
      setTimeout(function() { document.getElementById('tipo_nee').value = tipoNEEValue; }, 10);
    }
  }

  var nivelSelect = document.getElementById('nivel');
  if (nivelSelect.value) {
    nivelSelect.dispatchEvent(new Event('change'));
    var gradoValue = '<?= $_POST['grado'] ?? '' ?>';
    if (gradoValue) {
      setTimeout(function() { document.getElementById('grado').value = gradoValue; }, 10);
    }
  }

  var fechaNacInput = document.getElementById('fecha_nacimiento');
  if (fechaNacInput.value) fechaNacInput.dispatchEvent(new Event('change'));
});
</script>

<?php include __DIR__ . '/_footer.php'; ?>
