<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_admin();
@set_time_limit(0);

$CSV_PATH = __DIR__ . '/uploads/estudiantes.csv';

function normalize_header($h) {
    $h = trim($h);
    $h = strtolower($h);
    $h = str_replace([' ', '-', '.'], '_', $h);
    return $h;
}

function quitar_tildes($str) {
    $str = (string)$str;
    $map = array(
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
        'Ü'=>'U','ü'=>'u'
    );
    return strtr($str, $map);
}

function clean($v) {
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

function normalizar_texto($v) {
    $v = trim((string)$v);
    $v = quitar_tildes($v);
    $v = strtoupper($v);
    // Normalizar espacios múltiples y reemplazar separadores por espacio
    $v = preg_replace('/[\s\-_]+/', ' ', $v);
    $v = trim($v);
    return $v;
}

function map_tipo_identificacion($v) {
    $v = normalizar_texto($v);

    // Aceptar variaciones
    if ($v === 'CEDULA DE CIUDADANIA') return 'CEDULA_CIUDADANIA';
    if ($v === 'CEDULA CIUDADANIA') return 'CEDULA_CIUDADANIA';
    if ($v === 'CEDULA') return 'CEDULA_CIUDADANIA';

    if ($v === 'CODIGO DEL ESTUDIANTE') return 'CODIGO_ESTUDIANTE';
    if ($v === 'CODIGO ESTUDIANTE') return 'CODIGO_ESTUDIANTE';
    if ($v === 'CODIGO') return 'CODIGO_ESTUDIANTE';

    // Por si ya viene en formato interno
    if ($v === 'CEDULA_CIUDADANIA') return 'CEDULA_CIUDADANIA';
    if ($v === 'CODIGO_ESTUDIANTE') return 'CODIGO_ESTUDIANTE';

    return null;
}

function map_jornada($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === 'MATUTINA') return 'MATUTINO';
    if ($v === 'VESPERTINA') return 'VESPERTINO';
    if ($v === 'NOCTURNA') return 'NOCTURNA';
    // por si ya viene
    if ($v === 'MATUTINO' || $v === 'VESPERTINO') return $v;
    return null;
}

function map_nivel($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === '') return null;
    if ($v === 'INICIAL' || $v === 'BASICA' || $v === 'BACHILLERATO') return $v;
    return null;
}

function map_grado($nivel, $grado) {
    $nivel = strtoupper(quitar_tildes(trim((string)$nivel)));
    $grado = strtoupper(quitar_tildes(trim((string)$grado)));
    if ($grado === '') return null;

    // reemplazar espacios por _
    $g = str_replace(' ', '_', $grado);

    // casos comunes (bachillerato)
    if ($nivel === 'BACHILLERATO') {
        if ($g === '1ERO_BACHILLERATO') return '1ERO_BACHILLERATO';
        if ($g === '2DO_BACHILLERATO') return '2DO_BACHILLERATO';
        if ($g === '3ERO_BACHILLERATO') return '3ERO_BACHILLERATO';
    }

    // básica con formato "2DO EGB"
    if ($nivel === 'BASICA') {
        // "2DO_EGB" etc
        $g = str_replace('_EGB', '_EGB', $g);
        $valid = array('1ERO_EGB','2DO_EGB','3ERO_EGB','4TO_EGB','5TO_EGB','6TO_EGB','7MO_EGB','8VO_EGB','9NO_EGB','10MO_EGB');
        if (in_array($g, $valid, true)) return $g;
    }

    // inicial: "INICIAL I" -> INICIAL_I
    if ($nivel === 'INICIAL') {
        if ($g === 'INICIAL_I') return 'INICIAL_I';
        if ($g === 'INICIAL_II') return 'INICIAL_II';
    }

    // si ya viene en el formato exacto
    return $g;
}

function map_genero($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === '') return null;
    if ($v === 'MASCULINO' || $v === 'FEMENINO' || $v === 'LGTBIQ+') return $v;
    return null;
}

function map_nee($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === '') return null;
    if ($v === 'ASOCIADAS_A_LA_DISCAPACIDAD') return 'ASOCIADAS_A_LA_DISCAPACIDAD';
    if ($v === 'NO_ASOCIADAS_A_LA_DISCAPACIDAD') return 'NO_ASOCIADAS_A_LA_DISCAPACIDAD';
    return null;
}

function map_tipo_nee($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === '' || $v === 'NO APLICA' || $v === 'NO_APLICA') return null;

    // normalizar espacios a _
    $v = preg_replace('/\s+/', '_', $v);

    // normalizaciones específicas
    if ($v === 'DIFICULTADES_ESPECIFICAS_DE_APRENDIZAJE') return 'DIFICULTADES_ESPECIFICAS_APRENDIZAJE';
    if ($v === 'ALTAS_CAPACIDADES') return 'ALTAS_CAPACIDADES';
    if ($v === 'ESTUDIANTES_EN_SITUACION_DE_VULNERABILIDAD') return 'ESTUDIANTES_SITUACION_VULNERABILIDAD';

    // asociadas
    if ($v === 'FISICA') return 'FISICA';
    if ($v === 'VISUAL') return 'VISUAL';
    if ($v === 'AUDITIVA') return 'AUDITIVA';
    if ($v === 'LENGUAJE') return 'LENGUAJE';
    if ($v === 'INTELECTUAL') return 'INTELECTUAL';
    if ($v === 'PSICOSOCIAL') return 'PSICOSOCIAL';
    if ($v === 'MULTIPLE') return 'MULTIPLE';
    if ($v === 'CARNET_DE_EXTRANJERO') return 'CARNET_EXTRANJERO';
    if ($v === 'NINGUNA') return 'NINGUNA';

    return $v;
}

function map_porcentaje($v) {
    $v = strtoupper(quitar_tildes(trim((string)$v)));
    if ($v === '' || $v === 'NO APLICA' || $v === 'NO_APLICA') return null;
    $v = str_replace('%','', $v);
    $v = trim($v);
    if ($v === '') return null;
    if (!ctype_digit($v)) return null;
    $n = (int)$v;
    if ($n < 0 || $n > 100) return null;
    return $n;
}

function parse_date_to_ymd($s) {
    $s = trim((string)$s);
    if ($s === '') return null;

    // yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

    // d/m/yyyy o dd/mm/yyyy
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
        $p = explode('/', $s);
        $d = str_pad($p[0], 2, '0', STR_PAD_LEFT);
        $m = str_pad($p[1], 2, '0', STR_PAD_LEFT);
        return $p[2] . '-' . $m . '-' . $d;
    }

    // d-m-yyyy o dd-mm-yyyy
    if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $s)) {
        $p = explode('-', $s);
        $d = str_pad($p[0], 2, '0', STR_PAD_LEFT);
        $m = str_pad($p[1], 2, '0', STR_PAD_LEFT);
        return $p[2] . '-' . $m . '-' . $d;
    }

    return null;
}

function calc_edad($ymd) {
    if (!$ymd) return null;
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return null;
    $hoy = new DateTime();
    return $hoy->diff($dt)->y;
}

if (!file_exists($CSV_PATH)) {
    die("No existe el archivo CSV en: " . htmlspecialchars($CSV_PATH));
}

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fh = fopen($CSV_PATH, 'r');
if (!$fh) die("No se pudo abrir el CSV.");

// tu CSV usa ;
$delimiter = ';';

$headers_raw = fgetcsv($fh, 0, $delimiter);
if (!$headers_raw) die("CSV vacío o sin encabezados.");

$headers = [];
foreach ($headers_raw as $i => $h) $headers[normalize_header($h)] = $i;

function getcol($row, $headers, $name) {
    $k = normalize_header($name);
    if (!isset($headers[$k])) return null;
    $idx = $headers[$k];
    return isset($row[$idx]) ? $row[$idx] : null;
}

$stChk = $pdo->prepare("SELECT id FROM estudiante WHERE tipo_identificacion=? AND identificacion=? LIMIT 1");
$stIns = $pdo->prepare("INSERT INTO estudiante
(tipo_identificacion, identificacion, nombres, fecha_nacimiento, edad, nee, tipo_nee, porcentaje_discapacidad, genero, jornada, nivel, grado)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

$insertados = 0;
$omitidos = 0;
$errores = [];

$linea = 1;
$pdo->beginTransaction();

try {
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        $linea++;

        $tipo_identificacion = map_tipo_identificacion(getcol($row,$headers,'tipo_identificacion'));
        $identificacion = trim((string)getcol($row,$headers,'identificacion'));
        $nombres = trim((string)getcol($row,$headers,'nombres'));

        $fecha_nacimiento = parse_date_to_ymd((string)getcol($row,$headers,'fecha_nacimiento'));
        $edad = calc_edad($fecha_nacimiento);

        $nee = map_nee(getcol($row,$headers,'nee'));
        $tipo_nee = map_tipo_nee(getcol($row,$headers,'tipo_nee'));
        $porcentaje = map_porcentaje(getcol($row,$headers,'porcentaje_discapacidad'));

        $genero = map_genero(getcol($row,$headers,'genero'));
        $jornada = map_jornada(getcol($row,$headers,'jornada'));
        $nivel = map_nivel(getcol($row,$headers,'nivel'));
        $grado = map_grado($nivel, getcol($row,$headers,'grado'));

        // Validaciones mínimas (no reventar)
        $rowErrors = [];

        if (!$tipo_identificacion) $rowErrors[] = "tipo_identificacion inválido";
        if ($identificacion === '') $rowErrors[] = "identificacion vacía";
        if ($tipo_identificacion === 'CEDULA_CIUDADANIA' && !preg_match('/^\d{10}$/', $identificacion)) {
            $rowErrors[] = "cédula inválida (debe 10 dígitos)";
        }
        if ($nombres === '') $rowErrors[] = "nombres vacío";

        // nivel/grado coherencia
        if ($nivel && $grado) {
            $ok = true;
            if ($nivel === 'INICIAL' && !in_array($grado, ['INICIAL_I','INICIAL_II'], true)) $ok=false;
            if ($nivel === 'BASICA' && !in_array($grado, ['1ERO_EGB', '2DO_EGB','3ERO_EGB','4TO_EGB','5TO_EGB','6TO_EGB','7MO_EGB','8VO_EGB','9NO_EGB','10MO_EGB'], true)) $ok=false;
            if ($nivel === 'BACHILLERATO' && !in_array($grado, ['1ERO_BACHILLERATO','2DO_BACHILLERATO','3ERO_BACHILLERATO'], true)) $ok=false;
            if (!$ok) $rowErrors[] = "grado inválido para nivel ($nivel/$grado)";
        }

        if (!empty($rowErrors)) {
            $errores[] = "Línea $linea: " . implode(', ', $rowErrors) . " (identificación=$identificacion)";
            $omitidos++;
            continue;
        }

        // Evitar duplicados
        $stChk->execute([$tipo_identificacion, $identificacion]);
        if ($stChk->fetch()) { $omitidos++; continue; }

        $stIns->execute([
            $tipo_identificacion,
            $identificacion,
            $nombres,
            $fecha_nacimiento,
            $edad,
            $nee,
            $tipo_nee,
            $porcentaje,
            $genero,
            $jornada,
            $nivel,
            $grado
        ]);

        $insertados++;
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    fclose($fh);
    die("ERROR IMPORTANDO: " . htmlspecialchars($e->getMessage()));
}

fclose($fh);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Importación Estudiantes</title></head>
<body style="font-family: Arial, sans-serif; padding:20px;">
  <h2>Importación completada</h2>
  <p><b>Insertados:</b> <?= (int)$insertados ?></p>
  <p><b>Omitidos:</b> <?= (int)$omitidos ?></p>

  <?php if (!empty($errores)): ?>
    <h3>Errores (primeros 200)</h3>
    <pre style="background:#f6f6f6; padding:10px; white-space:pre-wrap;"><?=
      htmlspecialchars(implode("\n", array_slice($errores, 0, 200)))
    ?></pre>
  <?php endif; ?>

  <p>CSV leído desde: <code><?= htmlspecialchars($CSV_PATH) ?></code></p>
</body>
</html>
