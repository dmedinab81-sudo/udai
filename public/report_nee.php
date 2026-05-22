<?php
/**
 * report_nee.php (corregido)
 * Corrige uso de métodos incompatibles con algunas versiones de PhpSpreadsheet
 * - Reemplaza setCellValueByColumnAndRow / getStyleByColumnAndRow / getColumnDimensionByColumn
 *   por llamadas compatibles usando Coordinate::stringFromColumnIndex()
 *
 * Exporta ZIP con reportes_nee.xlsx y reportes_nee.csv (o CSV/XLSX directo como fallback).
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

// intentar cargar PhpSpreadsheet (si está instalado via composer)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
$hasPhpSpreadsheet = file_exists($vendorAutoload);
if ($hasPhpSpreadsheet) {
    require_once $vendorAutoload;
}

require_login();

$pdo = getPDO();

// logger helper
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$logFile = $logDir . '/report_nee_errors.log';
function log_error($msg) {
    global $logFile;
    @file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND);
}

// Obtener listas para filtros (silencioso en caso de error)
try {
    $ubicaciones = $pdo->query("SELECT id, mes, zona, distrito FROM ubicacion ORDER BY mes")->fetchAll(PDO::FETCH_ASSOC);
    $docentes_apoyo = $pdo->query("SELECT id, cedula, nombres FROM docente_apoyo ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    log_error("Error fetching filter lists: " . $e->getMessage());
    $ubicaciones = [];
    $docentes_apoyo = [];
}

// Leer filtros desde GET
$id_ubicacion = isset($_GET['id_ubicacion']) && $_GET['id_ubicacion'] !== '' ? intval($_GET['id_ubicacion']) : null;
$id_docente_apoyo = isset($_GET['id_docente_apoyo']) && $_GET['id_docente_apoyo'] !== '' ? intval($_GET['id_docente_apoyo']) : null;
$date_from = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
$export = isset($_GET['export']) && $_GET['export'] == '1';

// Verificar si existe created_at en la tabla registro_nee
$has_created = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM registro_nee LIKE 'created_at'")->fetch();
    if ($cols) $has_created = true;
} catch (Throwable $e) {
    $has_created = false;
}

// Construir cláusula WHERE y parámetros
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
    if ($date_to && $has_created)   { $where[] = "rn.created_at <= ?"; $params[] = $date_to . ' 23:59:59'; }
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// SELECT incluyendo created_at si existe
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
} catch (Throwable $e) {
    log_error("Query error: " . $e->getMessage());
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "<h3>Error en consulta a la base de datos</h3><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

// Si se solicita export -> generar archivos
if ($export) {
    try {
        // Encabezados (incluyen ID y CREATED_AT al inicio)
        $headers = [
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

        // Helper booleano
        $b2s = function($v) { return intval($v) ? 'SI' : 'NO'; };
		
		// Helper porcentaje discapacidad
		$porc_out = function($v) {
			$v = trim((string)($v ?? ''));
			if ($v === '') return 'NO APLICA';
			// opcional: si quieres que salga "50%" en vez de "50"
			if (ctype_digit($v)) return $v . '%';
			return $v; // fallback
		};


        // Si PhpSpreadsheet no está disponible, generar solo CSV (fast fallback)
        if (!$hasPhpSpreadsheet) {
            // CSV directo
            $filename = 'reportes_nee_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers, ';');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $created_out = ($has_created && !empty($row['created_at'])) ? $row['created_at'] : '';
                $fn = $row['estudiante_fecha_nacimiento'] ?? '';
                $fn_out = $fn;
                $csvRow = [
                    $row['mes'],$row['zona'],$row['distrito'],$row['da_cedula'],$row['da_nombres'],$row['da_telefono'],$row['da_correo'],
                    $row['institucion_nombre'],$row['institucion_amie'],$row['institucion_correo'],$row['institucion_telefono'],
                    $row['estudiante_tipo_identificacion'],$row['estudiante_identificacion'],$row['estudiante_nombres'],$fn_out,$row['estudiante_edad'],
                    $row['nee'],$row['tipo_nee'],$porc_out($row['porcentaje_discapacidad']),$row['genero'],$row['jornada'],$row['nivel'],$row['grado'],
                    $b2s($row['rn_docente_tutor']),$b2s($row['rn_lengua_y_literatura']),$b2s($row['rn_matematica']),$b2s($row['rn_ciencias_naturales']),
                    $b2s($row['rn_fisica']),$b2s($row['rn_quimica']),$b2s($row['rn_biologia']),$b2s($row['rn_estudios_sociales_historia']),
                    $b2s($row['rn_ingles']),$b2s($row['rn_educacion_fisica']),$b2s($row['rn_gestion_para_el_emprendimiento']),$b2s($row['rn_filosofia']),
                    $row['dt_cedula'],$row['dt_nombres'],$row['dt_telefono'],$row['dt_correo'],
                    $row['rl_cedula'],$row['rl_nombres'],$row['rl_telefono'],$row['rl_correo'],
                    $row['enlace_gestion'],$row['observaciones']
                ];
                fputcsv($out, $csvRow, ';');
            }
            fclose($out);
            exit;
        }

        // PhpSpreadsheet está disponible -> crear XLSX
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reportes NEE');

        // Escribir encabezados y dar formato negrita por celda (compatible)
        $colIndex = 1;
        foreach ($headers as $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '1', $h);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $colIndex++;
        }

        // Freeze pane y autofiltro
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Escribir filas
        $rowIndex = 2;
        // Rewind statement pointer (we used it earlier). Re-execute for streaming if necessary.
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute($params);

        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $col = 1;
            $created_out = ($has_created && !empty($row['created_at'])) ? $row['created_at'] : '';
            // formatear fecha de nacimiento si posible
            $fn = $row['estudiante_fecha_nacimiento'] ?? '';
            $fn_out = $fn;
            if (!empty($fn)) {
                $d = date_create($fn);
                if ($d) $fn_out = $d->format('j/n/Y');
            }

            $values = [
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
                $porc_out($row['porcentaje_discapacidad']),
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

            foreach ($values as $v) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colLetter . $rowIndex, (string)($v ?? ''));
                $col++;
            }
            $rowIndex++;
        }

        // Auto-size columns
        $highestColumnIndex = count($headers);
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Wrap text for observations column (last column)
        $obsColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex);
        $sheet->getStyle($obsColLetter . '1:' . $obsColLetter . $rowIndex)->getAlignment()->setWrapText(true);

        // Generar CSV en memoria con mismo orden y BOM
        $csvStream = fopen('php://temp', 'w+');
        fwrite($csvStream, "\xEF\xBB\xBF");
        fputcsv($csvStream, $headers, ';');

        // Re-execute once more to stream rows for CSV
        $stmt3 = $pdo->prepare($sql);
        $stmt3->execute($params);
        while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $created_out = ($has_created && !empty($row['created_at'])) ? $row['created_at'] : '';
            $fn = $row['estudiante_fecha_nacimiento'] ?? '';
            $fn_out = $fn;
            if (!empty($fn)) {
                $d = date_create($fn);
                if ($d) $fn_out = $d->format('j/n/Y');
            }
            $csvValues = [
                $row['mes'],$row['zona'],$row['distrito'],$row['da_cedula'],$row['da_nombres'],$row['da_telefono'],$row['da_correo'],
                $row['institucion_nombre'],$row['institucion_amie'],$row['institucion_correo'],$row['institucion_telefono'],
                $row['estudiante_tipo_identificacion'],$row['estudiante_identificacion'],$row['estudiante_nombres'],$fn_out,$row['estudiante_edad'],
                $row['nee'],$row['tipo_nee'],$porc_out($row['porcentaje_discapacidad']),$row['genero'],$row['jornada'],$row['nivel'],$row['grado'],
                $b2s($row['rn_docente_tutor']),$b2s($row['rn_lengua_y_literatura']),$b2s($row['rn_matematica']),$b2s($row['rn_ciencias_naturales']),
                $b2s($row['rn_fisica']),$b2s($row['rn_quimica']),$b2s($row['rn_biologia']),$b2s($row['rn_estudios_sociales_historia']),
                $b2s($row['rn_ingles']),$b2s($row['rn_educacion_fisica']),$b2s($row['rn_gestion_para_el_emprendimiento']),$b2s($row['rn_filosofia']),
                $row['dt_cedula'],$row['dt_nombres'],$row['dt_telefono'],$row['dt_correo'],
                $row['rl_cedula'],$row['rl_nombres'],$row['rl_telefono'],$row['rl_correo'],
                $row['enlace_gestion'],$row['observaciones']
            ];
            fputcsv($csvStream, $csvValues, ';');
        }
        rewind($csvStream);

        // Generar XLSX en memoria
        $xlsxWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $xlsxTemp = fopen('php://temp', 'w+');
        $xlsxWriter->save($xlsxTemp);
        rewind($xlsxTemp);

        // Si ZipArchive disponible, empaquetar; si no, enviar XLSX directamente
        if (class_exists('ZipArchive')) {
            $zipTemp = tempnam(sys_get_temp_dir(), 'repzip_');
            $zip = new ZipArchive();
            if ($zip->open($zipTemp, ZipArchive::CREATE) !== true) {
                log_error("ZipArchive::open failed; sending XLSX directly");
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="reportes_nee.xlsx"');
                rewind($xlsxTemp);
                fpassthru($xlsxTemp);
                exit;
            }
            $zip->addFromString('reportes_nee.xlsx', stream_get_contents($xlsxTemp));
            $zip->addFromString('reportes_nee.csv', stream_get_contents($csvStream));
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="reportes_nee_' . date('Ymd_His') . '.zip"');
            header('Content-Length: ' . filesize($zipTemp));
            readfile($zipTemp);
            @unlink($zipTemp);
            exit;
        } else {
            // enviar XLSX directamente
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="reportes_nee.xlsx"');
            rewind($xlsxTemp);
            fpassthru($xlsxTemp);
            exit;
        }

    } catch (Throwable $e) {
        log_error("Export error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        echo "<h3>Error al generar exportación</h3>";
        echo "<p>Se produjo un error al generar el archivo. Revisa logs/report_nee_errors.log para más detalles.</p>";
        exit;
    }
}

// Si no se solicitó export, mostrar interfaz HTML
include __DIR__ . '/_header.php';

// Re-ejecutar consulta para mostrar preview (limitado)
try {
    $stmtView = $pdo->prepare($sql . " LIMIT 200");
    $stmtView->execute($params);
    $rows_view = $stmtView->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows_view = [];
}
?>
<div class="row">
  <div class="col-12">
    <h2>Reportes — Registros NEE</h2>

    <form method="get" class="row gy-2 gx-2 align-items-end mb-3">
      <div class="col-md-3">
        <label class="form-label">Período</label>
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
          <div class="alert alert-warning small">Nota: la tabla registro_nee no contiene la columna <code>created_at</code>, por lo tanto el filtro por fecha no está disponible. Para habilitarlo añade <code>created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP</code>.</div>
        <?php endif; ?>
      </div>
    </form>

    <div class="mb-3">
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-success">Exportar XLSX + CSV (ZIP)</a>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr>
            <th>ID</th><th>Período</th><th>Docente Apoyo</th><th>Institución</th><th>Estudiante</th><th>Asignaturas</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_view)): ?>
            <tr><td colspan="7" class="text-center">No se encontraron registros.</td></tr>
          <?php else: foreach ($rows_view as $r): ?>
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
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>