<?php
/**
 * importar_instituciones.php
 * Importa instituciones desde un CSV delimitado por ; a la tabla institucion
 * Evita duplicados por AMIE usando ON DUPLICATE KEY UPDATE
 */

require_once __DIR__ . '/../app/db.php'; // ajusta si tu ruta es distinta

// ===== CONFIG =====
$csvPath = __DIR__ . '/uploads/instituciones.csv'; // CSV en la misma carpeta que este script
$delimiter = ';';

// ===== HELPERS =====
function limpiarCampo(?string $s): ?string {
    $s = $s ?? '';
    $s = trim($s);
    // Normalizar espacios raros
    $s = preg_replace('/\s+/', ' ', $s);
    return $s === '' ? null : $s;
}

function limpiarTelefono(?string $t): ?string {
    $t = limpiarCampo($t);
    if ($t === null) return null;
    // deja solo dígitos
    $t = preg_replace('/\D+/', '', $t);
    return $t === '' ? null : $t;
}

function limpiarAmie(?string $a): ?string {
    $a = limpiarCampo($a);
    if ($a === null) return null;
    // Quitar espacios internos y poner mayúsculas
    $a = strtoupper(str_replace(' ', '', $a));
    return $a === '' ? null : $a;
}

// ===== RUN =====
if (!file_exists($csvPath)) {
    http_response_code(500);
    exit("❌ No se encontró el archivo CSV en: {$csvPath}\n");
}

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Inserta o actualiza por UNIQUE(amie)
$sql = "
INSERT INTO institucion (nombre, amie, correo, telefono)
VALUES (:nombre, :amie, :correo, :telefono)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  correo = VALUES(correo),
  telefono = VALUES(telefono)
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

        // El CSV trae: NOMBRE;AMIE;CORREO;TELEFONO
        // Asegurar 4 columnas mínimo
        $row = array_pad($row, 4, null);

        $nombre = limpiarCampo($row[0]);
        $amie = limpiarAmie($row[1]);
        $correo = limpiarCampo($row[2]);
        $telefono = limpiarTelefono($row[3]);

        // Reglas mínimas: AMIE obligatorio para evitar duplicados
        if ($amie === null) {
            $omitidos++;
            continue;
        }

        // Ejecutar insert/update
        $stmt->execute([
            ':nombre' => $nombre,
            ':amie' => $amie,
            ':correo' => $correo,
            ':telefono' => $telefono,
        ]);

        /**
         * Nota:
         * - En MySQL, rowCount() con ON DUPLICATE KEY UPDATE puede devolver:
         *   1 = insert, 2 = update, 0 = no cambios (depende config)
         */
        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc >= 2) $actualizados++;
        else $actualizados++; // si no cambió nada, lo contamos como "procesado"
    }

    $pdo->commit();
    fclose($handle);

    header('Content-Type: text/plain; charset=utf-8');
    echo "✅ Importación finalizada\n";
    echo "Archivo: {$csvPath}\n";
    echo "Filas leídas (sin header): {$total}\n";
    echo "Insertados: {$insertados}\n";
    echo "Actualizados/Procesados: {$actualizados}\n";
    echo "Omitidos (sin AMIE): {$omitidos}\n";

} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);

    http_response_code(500);
    echo "❌ Error en importación: " . $e->getMessage() . "\n";
}
