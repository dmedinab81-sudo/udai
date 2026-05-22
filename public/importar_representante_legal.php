<?php
/**
 * Importador CSV -> tabla representante_legal
 * PHP 8
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =========================
   CONFIGURACIÓN BD
   ========================= */
$host = "localhost";
$db   = "system_udai";
$user = "root";
$pass = "adminhglps";

$archivoCSV = __DIR__ . "/uploads/representantes.csv"; // ruta del CSV

/* =========================
   CONEXIÓN
   ========================= */
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=latin1",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* =========================
   VALIDAR CSV
   ========================= */
if (!file_exists($archivoCSV)) {
    die("El archivo CSV no existe.");
}

$handle = fopen($archivoCSV, "r");
if (!$handle) {
    die("No se pudo abrir el archivo CSV.");
}

/* =========================
   PREPARAR INSERT
   ========================= */
$sql = "INSERT INTO representante_legal 
        (cedula, nombres, telefono, correo)
        VALUES (:cedula, :nombres, :telefono, :correo)
        ON DUPLICATE KEY UPDATE
            nombres = VALUES(nombres),
            telefono = VALUES(telefono),
            correo = VALUES(correo)";

$stmt = $pdo->prepare($sql);

/* =========================
   LEER CSV
   ========================= */
$fila = 0;
$insertados = 0;

while (($data = fgetcsv($handle, 0, ";")) !== false) {

    // Saltar encabezado
    if ($fila === 0) {
        $fila++;
        continue;
    }

    if (count($data) < 4) {
        continue;
    }

    $cedula   = trim($data[0]);
    $nombres  = trim($data[1]);
    $telefono = trim($data[2]);
    $correo   = trim($data[3]);

    // Convertir a latin1 si el CSV viene en UTF-8
    $nombres = utf8_decode($nombres);
    $correo  = utf8_decode($correo);

    if ($cedula === "") {
        continue;
    }

    $stmt->execute([
        ":cedula"   => $cedula,
        ":nombres"  => $nombres,
        ":telefono" => $telefono,
        ":correo"   => $correo
    ]);

    $insertados++;
}

fclose($handle);

echo "Proceso finalizado. Registros procesados: $insertados";
