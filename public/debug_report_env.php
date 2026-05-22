<?php
// Diagnóstico rápido para reporte de errores (borra este archivo cuando termines).
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h3>Diagnóstico entorno PHP / dependencias</h3>";

echo "<ul>";
echo "<li>PHP version: " . PHP_VERSION . "</li>";
echo "<li>SAPI: " . php_sapi_name() . "</li>";
echo "<li>Memory limit: " . ini_get('memory_limit') . "</li>";
echo "<li>Max execution time: " . ini_get('max_execution_time') . "</li>";
echo "<li>Upload max filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "</ul>";

echo "<h4>Composer / PhpSpreadsheet</h4>";
$vendor = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendor)) {
    echo "<p>vendor/autoload.php: OK</p>";
    // intentar comprobar clase
    require_once $vendor;
    echo "<p>PhpSpreadsheet disponible: " . (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? '<strong>SI</strong>' : '<strong>NO</strong>') . "</p>";
} else {
    echo "<p style='color:orange'>vendor/autoload.php NO encontrado en: " . htmlspecialchars($vendor) . "</p>";
}

echo "<h4>Extensiones PHP</h4>";
$modules = phpinfo(INFO_MODULES) ? true : true; // just to ensure phpinfo available
$needed = ['zip','mbstring','xml','pdo_mysql'];
foreach ($needed as $m) {
    echo "<div>" . $m . ": " . (extension_loaded($m) ? '<strong style=\"color:green\">loaded</strong>' : '<strong style=\"color:red\">MISSING</strong>') . "</div>";
}

echo "<h4>ZipArchive</h4>";
echo class_exists('ZipArchive') ? "<p style='color:green'>ZipArchive: OK</p>" : "<p style='color:red'>ZipArchive NO disponible</p>";

echo "<h4>Permisos y archivos</h4>";
$reportFile = __DIR__ . '/../logs/report_nee_errors.log';
echo "<p>logs/report_nee_errors.log: " . (file_exists($reportFile) ? 'existe' : 'NO existe') . "</p>";
if (file_exists($reportFile)) {
    echo "<pre style='background:#111;color:#fff;padding:8px;max-height:300px;overflow:auto;'>" . htmlspecialchars(implode("\n", array_slice(file($reportFile), -200))) . "</pre>";
}

// Intentar leer los principales logs comunes (si son accesibles desde PHP)
$paths = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/nginx/error.log',
    '/var/log/php7.4-fpm.log',
    '/var/log/php8.1-fpm.log',
    __DIR__ . '/../logs/report_nee_errors.log'
];
echo "<h4>Últimos errores (si se pueden leer)</h4>";
foreach ($paths as $p) {
    if (file_exists($p) && is_readable($p)) {
        echo "<h5>" . htmlspecialchars($p) . "</h5>";
        $lines = array_slice(file($p), -50);
        echo "<pre style='background:#222;color:#fff;padding:8px;max-height:200px;overflow:auto;'>" . htmlspecialchars(implode("", $lines)) . "</pre>";
    }
}

echo "<h4>Comprobación rápida del script report_nee.php</h4>";
$test = __DIR__ . '/report_nee.php';
if (file_exists($test)) {
    echo "<p>report_nee.php: existe</p>";
    echo "<p>Últimas 50 líneas:</p>";
    $lines = array_slice(file($test), -50);
    echo "<pre style='background:#f8f9fa;padding:8px;max-height:300px;overflow:auto;'>" . htmlspecialchars(implode("", $lines)) . "</pre>";
} else {
    echo "<p style='color:red'>report_nee.php NO existe en " . htmlspecialchars($test) . "</p>";
}

echo "<hr><p>Cuando tengas la salida, pégala aquí y la reviso. Recuerda borrar este archivo cuando termines.</p>";
?>