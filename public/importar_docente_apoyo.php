<?php
/**
 * importar_docente_apoyo.php
 * Importa docentes de apoyo desde un CSV delimitado por ; a la tabla docente_apoyo
 * Evita duplicados por UNIQUE(cedula) usando ON DUPLICATE KEY UPDATE
 */

require_once __DIR__ . '/../app/db.php'; // ajusta si tu ruta es distinta

// ===== CONFIG =====
$csvPath   = __DIR__ . '/uploads/docente_apoyo.csv'; // cambia si tu archivo tiene otro nombre
$delimiter = ';';

// ===== HELPERS =====
function limpiarCampo(?string $s): ?string {
    $s = $s ?? '';
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s === '' ? null : $s;
}

function limpiarCedula(?string $c): ?string {
    $c = limpiarCampo($c);
    if ($c === null) return null;
    // solo dígitos
    $c = preg_replace('/\D+/', '', $c);
    // exigir 10 dígitos (cédula)
    if (strlen($c) !== 10) return null;
    return $c;
}

function limpiarTelefono(?string $t): ?string {
    $t = limpiarCampo($t);
    if ($t === null) return null;
    // solo dígitos
    $t = preg_replace('/\D+/', '', $t);
    return $t === '' ? null : $t;
}

function limpiarCorreo(?string $mail): ?string {
    $mail = limpiarCampo($mail);
    if ($mail === null) return null;

    // quitar espacios y normalizar
    $mail = strtolower(str_replace(' ', '', $mail));

    // opcional: validar dominio institucional
    if (!preg_match('/^[A-Za-z0-9._%+\-]+@docentes\.educacion\.edu\.ec$/', $mail)) {
        // Si quieres permitir cualquier correo, cambia esto por: return $mail;
        return null;
    }
    return $mail;
}

// ===== RUN =====
if (!file_exists($csvPath)) {
    http_response_code(500);
    exit("❌ No se encontró el CSV en: {$csvPath}\n");
}

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Inserta o actualiza por UNIQUE(cedula)
$sql = "
INSERT INTO docente_apoyo (cedula, nombres, telefono, correo)
VALUES (:cedula, :nombres, :telefono, :correo)
ON DUPLICATE KEY UPDATE
  nombres = VALUES(nombres),
  telefono = VALUES(telefono),
  correo = VALUES(correo)
";

$stmt = $pdo->prepare($sql);

$handle = fopen($csvPath, 'r');
if (!$handle) {
    http_response_code(500);
    exit("❌ No se pudo abrir el CSV.\n");
}

// Leer encabezado (primera línea)
$header = fgetcsv($handle, 0, $delimiter);
if ($header === false) {
    fclose($handle);
    exit("❌ El CSV está vacío.\n");
}

$total = 0;
$insertados = 0;
$actualizados = 0;
$omitidos = 0;

$pdo->beginTransaction();
try {
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $total++;

        // CSV: CEDULA;NOMBRES;TELEFONO;CORREO
        $row = array_pad($row, 4, null);

        $cedula   = limpiarCedula($row[0]);
        $nombres  = limpiarCampo($row[1]);
        $telefono = limpiarTelefono($row[2]);
        $correo   = limpiarCorreo($row[3]);

        // cédula obligatoria para deduplicar
        if ($cedula === null) {
            $omitidos++;
            continue;
        }

        $stmt->execute([
            ':cedula'   => $cedula,
            ':nombres'  => $nombres,
            ':telefono' => $telefono,
            ':correo'   => $correo,
        ]);

        /**
         * En MySQL con ON DUPLICATE KEY UPDATE:
         * 1 = insert, 2 = update, 0 = no cambios (depende config)
         */
        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc >= 2) $actualizados++;
        else $actualizados++;
    }

    $pdo->commit();
    fclose($handle);

    header('Content-Type: text/plain; charset=utf-8');
    echo "✅ Importación finalizada\n";
    echo "Archivo: {$csvPath}\n";
    echo "Filas leídas (sin header): {$total}\n";
    echo "Insertados: {$insertados}\n";
    echo "Actualizados/Procesados: {$actualizados}\n";
    echo "Omitidos (cédula inválida/no 10 dígitos): {$omitidos}\n";

} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);

    http_response_code(500);
    echo "❌ Error en importación: " . $e->getMessage() . "\n";
}
