<?php
// Versión temporal: exporta SOLO CSV (sin PhpSpreadsheet) para descarga inmediata.
// Mantiene filtros (ubicación, docente_apoyo, fecha si existe created_at) y vista HTML si no se pide export.
//
// Reemplaza public/report_nee.php por este archivo temporal. Cuando quieras volver a la versión XLSX,
// sustitúyelo por la versión anterior.

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();

$pdo = getPDO();

// Obtener listas para filtros
try {
    $ubicaciones = $pdo->query("SELECT id, mes, zona, distrito FROM ubicacion ORDER BY mes")->fetchAll();
    $docentes_apoyo = $pdo->query("SELECT id, cedula, nombres FROM docente_apoyo ORDER BY nombres")->fetchAll();
} catch (Exception $e) {
    $ubicaciones = [];
    $docentes_apoyo = [];
}

// Leer filtros desde GET
$id_ubicacion = isset($_GET['id_ubicacion']) && $_GET['id_ubicacion'] !== '' ? intval($_GET['id_ubicacion']) : null;
$id_docente_apoyo = isset($_GET['id_docente_apoyo']) && $_GET['id_docente_apoyo'] !== '' ? intval($_GET['id_docente_apoyo']) : null;
$date_from = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
$export = isset($_GET['export']) && $_GET['export'] == '1';

// Verificar si existe created_at en tabla para filtros y export
$has_created = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM registro_nee LIKE 'created_at'")->fetch();
    if ($cols) $has_created = true;
} catch (Exception $e) {
    // ignore
}

// Construir cláusula WHERE dinámicamente y parámetros
$where = [];
$params = [];

if ($id_ubicacion) {
    $where[] = "rn.id_ubicacion = ?";
    $params[] = $id_ubicacion;
}
if ($id_docente_apoyo) {
    $where[] = "rn.id_docente_apoyo = ?";
    $params[] = $id_docente_apoyo;
}
if ($date_from && $date_to && $has_created) {
    $where[] = "rn.created_at BETWEEN ? AND ?";
    $params[] = $date_from . ' 00:00:00';
    $params[] = $date_to . ' 23:59:59';
} else {
    if ($date_from && $has_created) { $where[] = "rn.created_at >= ?"; $params[] = $date_from . ' 00:00:00'; }
    if ($date_to && $has_created) { $where[] = "rn.created_at <= ?"; $params[] = $date_to . ' 23:59:59'; }
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Construir SELECT (incluye rn.id y created_at si existe)
$created_select = $has_created ? "rn.created_at AS created_at," : "'' AS created_at,";
$sql = "SELECT
    rn.id AS id,
    {$created_select}
    COALESCE(u.mes, '') AS mes,
    COALESCE(u.zona, '') AS zona,
    COALESCE(u.distrito, '') AS distrito,
    COALESCE(da.cedula, '') AS da_cedula,
    COALESCE(da.nombres, '') AS da_nombres,
    COALESCE(da.telefono, '') AS da_telefono,
    COALESCE(da.correo, '') AS da_correo,
    COALESCE(i.nombre, '') AS institucion_nombre,
    COALESCE(i.amie, '') AS institucion_amie,
    COALESCE(i.correo, '') AS institucion_correo,
    COALESCE(i.telefono, '') AS institucion_telefono,
    COALESCE(e.tipo_identificacion, '') AS estudiante_tipo_identificacion,
    COALESCE(e.identificacion, '') AS estudiante_identificacion,
    COALESCE(e.nombres, '') AS estudiante_nombres,
    COALESCE(e.fecha_nacimiento, '') AS estudiante_fecha_nacimiento,
    COALESCE(e.edad, '') AS estudiante_edad,
    COALESCE(e.nee, '') AS nee,
    COALESCE(e.tipo_nee, '') AS tipo_nee,
    COALESCE(e.porcentaje_discapacidad, '') AS porcentaje_discapacidad,
    COALESCE(e.genero, '') AS genero,
    COALESCE(e.jornada, '') AS jornada,
    COALESCE(e.nivel, '') AS nivel,
    COALESCE(e.grado, '') AS grado,
    COALESCE(rn.docente_tutor, 0) AS rn_docente_tutor,
    COALESCE(rn.lengua_y_literatura, 0) AS rn_lengua_y_literatura,
    COALESCE(rn.matematica, 0) AS rn_matematica,
    COALESCE(rn.ciencias_naturales, 0) AS rn_ciencias_naturales,
    COALESCE(rn.fisica, 0) AS rn_fisica,
    COALESCE(rn.quimica, 0) AS rn_quimica,
    COALESCE(rn.biologia, 0) AS rn_biologia,
    COALESCE(rn.estudios_sociales_historia, 0) AS rn_estudios_sociales_historia,
    COALESCE(rn.ingles, 0) AS rn_ingles,
    COALESCE(rn.educacion_fisica, 0) AS rn_educacion_fisica,
    COALESCE(rn.gestion_para_el_emprendimiento, 0) AS rn_gestion_para_el_emprendimiento,
    COALESCE(rn.filosofia, 0) AS rn_filosofia,
    COALESCE(dt.cedula, '') AS dt_cedula,
    COALESCE(dt.nombres, '') AS dt_nombres,
    COALESCE(dt.telefono, '') AS dt_telefono,
    COALESCE(dt.correo, '') AS dt_correo,
    COALESCE(rl.cedula, '') AS rl_cedula,
    COALESCE(rl.nombres, '') AS rl_nombres,
    COALESCE(rl.telefono, '') AS rl_telefono,
    COALESCE(rl.correo, '') AS rl_correo,
    COALESCE(rn.enlace_gestion, '') AS enlace_gestion,
    COALESCE(rn.observaciones, '') AS observaciones
FROM registro_nee rn
LEFT JOIN ubicacion u ON rn.id_ubicacion = u.id
LEFT JOIN docente_apoyo da ON rn.id_docente_apoyo = da.id
LEFT JOIN institucion i ON rn.id_institucion = i.id
LEFT JOIN estudiante e ON rn.id_estudiante = e.id
LEFT JOIN docente_tutor dt ON rn.id_docente_tutor = dt.id
LEFT JOIN representante_legal rl ON rn.id_representante = rl.id
{$where_sql}
ORDER BY rn.id DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (Exception $e) {
    // Si falla la consulta, mostrar mensaje simple y salir
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "<h3>Error en consulta a la base de datos</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

// Si export solicitado -> generar CSV y salir
if ($export) {
    // Headers CSV (ID, CREATED_AT, luego columnas en el orden solicitado)
    $headers = [
        'ID',
        'CREATED_AT',
        'MES',
        'ZONA',
        'DISTRITO',
        'CEDULA DEL DOCENTE  DE APOYO A LA INCLUSIÓN',
        'APELLIDOS Y NOMBRES DEL DOCENTE DE APOYO A LA INCLUSIÓN',
        'TELEFONO DOCENTE DE APOYO A LA INCLUSIÓN',
        'CORREO ELECTRONICO',
        'NOMBRE INSTITUCION EDUCATIVA',
        'AMIE',
        'CORREO ELECTRONICO ',
        'TELEFONO',
        'TIPO DE IDENTIFICACION',
        'DOCUMENTO DE IDENTIFICACION DEL ESTUDIANTE',
        'APELLIDOS Y NOMBRES DEL ESTUDIANTE',
        'FECHA DE NACIMIENTO',
        'EDAD',
        'NECESIDAD EDUCATIVA ESPECIFICA',
        'TIPO DE NECESIDAD EDUCATIVA ESPECIFICA',
        'PORCENTAJE DE DISCAPACIDAD',
        'GENERO',
        'JORNADA',
        'NIVEL EDUCATIVO',
        'GRADOS DE EDUCACIÓN',
        'DOCENTE TUTOR',
        'LENGUA Y LITERATURA',
        'MATEMÁTICA',
        'CIENCIAS NATURALES',
        'FÍSICA',
        'QUÍMICA',
        'BIOLOGÍA',
        'ESTUDIOS SOCIALES/HISTORIA',
        'INGLÉS',
        'EDUCACIÓN FÍSICA',
        'GESTIÓN PARA EL EMPRENDIMIENTO',
        'FILOSOFÍA',
        'CEDULA DEL DOCENTE TUTOR',
        'APELLIDOS Y NOMBRES DEL DOCENTE TUTOR',
        'TELÉFONO DEL DOCENTE TUTOR',
        'CORREO ELECTRONICO',
        'CEDULA DEL REPRESENTANTE LEGAL',
        'APELLIDOS Y NOMBRES  DEL REPRESENTANTE LEGAL',
        'TELEFONO REPRESENTANTE LEGAL',
        'CORREO ELECTRÓNICO',
        '1 SOLO ENLACE DE GESTIÓN AÑO LECTIVO',
        'OBSERVACIONES'
    ];

    // Preparar salida
    $filename = 'reportes_nee_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    // BOM para Excel UTF-8
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Escribir cabecera con separador ';'
    // fputcsv usa comas por defecto, usamos ';' como separador
    fputcsv($out, $headers, ';');

    // helper booleano
    $b2s = function($v) { return intval($v) ? 'SI' : 'NO'; };

    // Iterar por el cursor del statement para no cargar todo en memoria
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $created_out = ($has_created && !empty($row['created_at'])) ? $row['created_at'] : '';
        $fn = $row['estudiante_fecha_nacimiento'] ?? '';
        $fn_out = $fn;

        $csvRow = [
            $row['id'],
            $created_out,
            $row['mes'],
            $row['zona'],
            $row['distrito'],
            $row['da_cedula'],
            $row['da_nombres'],
            $row['da_telefono'],
            $row['da_correo'],
            $row['institucion_nombre'],
            $row['institucion_amie'],
            $row['institucion_correo'],
            $row['institucion_telefono'],
            $row['estudiante_tipo_identificacion'],
            $row['estudiante_identificacion'],
            $row['estudiante_nombres'],
            $fn_out,
            $row['estudiante_edad'],
            $row['nee'],
            $row['tipo_nee'],
            $row['porcentaje_discapacidad'],
            $row['genero'],
            $row['jornada'],
            $row['nivel'],
            $row['grado'],
            $b2s($row['rn_docente_tutor']),
            $b2s($row['rn_lengua_y_literatura']),
            $b2s($row['rn_matematica']),
            $b2s($row['rn_ciencias_naturales']),
            $b2s($row['rn_fisica']),
            $b2s($row['rn_quimica']),
            $b2s($row['rn_biologia']),
            $b2s($row['rn_estudios_sociales_historia']),
            $b2s($row['rn_ingles']),
            $b2s($row['rn_educacion_fisica']),
            $b2s($row['rn_gestion_para_el_emprendimiento']),
            $b2s($row['rn_filosofia']),
            $row['dt_cedula'],
            $row['dt_nombres'],
            $row['dt_telefono'],
            $row['dt_correo'],
            $row['rl_cedula'],
            $row['rl_nombres'],
            $row['rl_telefono'],
            $row['rl_correo'],
            $row['enlace_gestion'],
            $row['observaciones']
        ];

        // Escribir fila con ';' como delimitador
        fputcsv($out, $csvRow, ';');
    }

    fclose($out);
    exit;
}

// Si no export, mostramos HTML con filtros y tabla reducida
include __DIR__ . '/_header.php';

// Volver a ejecutar la consulta para mostrar la tabla (o podríamos reuse, but statement cursor consumed)
// Ejecutamos de nuevo (limit para vista)
try {
    $stmt2 = $pdo->prepare($sql . " LIMIT 200");
    $stmt2->execute($params);
    $rows_view = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows_view = [];
}
?>
<div class="row">
  <div class="col-12">
    <h2>Reportes — Registros NEE (CSV temporal)</h2>

    <form method="get" class="row gy-2 gx-2 align-items-end mb-3">
      <div class="col-md-3">
        <label class="form-label">Ubicación</label>
        <select name="id_ubicacion" class="form-select">
          <option value="">-- Todas --</option>
          <?php foreach ($ubicaciones as $u): ?>
            <option value="<?= $u['id'] ?>" <?= ($id_ubicacion && $id_ubicacion == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['mes'] . ' / ' . $u['zona'] . ' / ' . $u['distrito']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Docente de apoyo</label>
        <select name="id_docente_apoyo" class="form-select">
          <option value="">-- Todos --</option>
          <?php foreach ($docentes_apoyo as $d): ?>
            <option value="<?= $d['id'] ?>" <?= ($id_docente_apoyo && $id_docente_apoyo == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nombres']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Fecha desde</label>
        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from ?? '') ?>" <?= $has_created ? '' : 'disabled'?>>
      </div>
      <div class="col-md-2">
        <label class="form-label">Fecha hasta</label>
        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to ?? '') ?>" <?= $has_created ? '' : 'disabled'?>>
      </div>

      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">Filtrar</button>
      </div>

      <div class="col-12 mt-2">
        <?php if (!$has_created): ?>
          <div class="alert alert-warning small">Nota: la tabla registro_nee no contiene la columna <code>created_at</code>, por lo tanto el filtro por fecha no está disponible.</div>
        <?php endif; ?>
      </div>
    </form>

    <div class="mb-3">
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-success">Descargar CSV (temporal)</a>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr>
            <th>ID</th><th>Ubicación</th><th>Docente Apoyo</th><th>Institución</th><th>Estudiante</th><th>Asignaturas</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_view)): ?>
            <tr><td colspan="7" class="text-center">No se encontraron registros.</td></tr>
          <?php else: ?>
            <?php foreach ($rows_view as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['id'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['mes'] . ' / ' . $r['zona'] . ' / ' . $r['distrito']) ?></td>
                <td><?= htmlspecialchars($r['da_nombres'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['institucion_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['estudiante_nombres'] ?? '') ?></td>
                <td>
                  <?php
                    $map = [
                      'rn_docente_tutor' => 'Docente Tutor','rn_lengua_y_literatura' => 'Lengua y Literatura','rn_matematica' => 'Matemática',
                      'rn_ciencias_naturales' => 'Ciencias Naturales','rn_fisica' => 'Física','rn_quimica' => 'Química','rn_biologia' => 'Biología',
                      'rn_estudios_sociales_historia' => 'Estudios Sociales / Historia','rn_ingles' => 'Inglés','rn_educacion_fisica' => 'Educación Física',
                      'rn_gestion_para_el_emprendimiento' => 'Gestión para el emprendimiento','rn_filosofia' => 'Filosofía'
                    ];
                    $out = [];
                    foreach ($map as $k => $label) {
                        if (!empty($r[$k])) $out[] = $label;
                    }
                    echo htmlspecialchars(implode(', ', $out));
                  ?>
                </td>
                <td>
                  <a class="btn btn-sm btn-primary" href="registro_nee_edit.php?id=<?= $r['id'] ?? '' ?>">Editar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>