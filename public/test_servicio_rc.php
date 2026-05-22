<?php
/**
 * Archivo de diagnóstico completo para el servicio del Registro Civil
 * Guarda como: test_servicio_rc.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Servicio RC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h3 { color: #666; margin-top: 30px; }
        .success { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0; }
        .warning { background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 10px 0; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: bold; }
        .form-group { margin: 20px 0; }
        input[type="text"] { padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; width: 200px; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #4caf50; color: white; }
        .badge-error { background: #f44336; color: white; }
        .badge-warning { background: #ff9800; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 Diagnóstico del Servicio Registro Civil UDAI</h2>
        
        <div class="form-group">
            <form method="GET">
                <label><strong>Ingrese número de cédula:</strong></label><br><br>
                <input type="text" name="cedula" value="<?= htmlspecialchars($cedula) ?>" placeholder="1234567890" maxlength="10" pattern="[0-9]{10}">
                <button type="submit">🔎 Probar Servicio</button>
            </form>
        </div>

<?php
if (empty($cedula)) {
    echo '<div class="info">👆 Ingrese una cédula de 10 dígitos para comenzar el diagnóstico</div>';
    echo '</div></body></html>';
    exit;
}

echo '<hr>';
echo '<div class="info"><strong>📋 Cédula consultada:</strong> ' . htmlspecialchars($cedula) . '</div>';

// Validar formato
if (!preg_match('/^[0-9]{10}$/', $cedula)) {
    echo '<div class="error">❌ <strong>Error:</strong> La cédula debe tener exactamente 10 dígitos numéricos</div>';
    echo '</div></body></html>';
    exit;
}

// URLs a probar
$urls = [
    'proxy_rc.php' => "http://157.100.156.40/sais/proxy_rc.php?cedula=" . urlencode($cedula),
    'cliente_datos_udai.php (JSON)' => "http://157.100.156.40/sais/cliente_datos_udai.php?cedula=" . urlencode($cedula) . "&json=1",
    'cliente_datos_udai.php (HTML)' => "http://157.100.156.40/sais/cliente_datos_udai.php?cedula=" . urlencode($cedula)
];

foreach ($urls as $nombre => $url) {
    echo "<h3>🌐 Probando: <code>$nombre</code></h3>";
    echo "<p><small>URL: <code>" . htmlspecialchars($url) . "</code></small></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $tiempo = round(($endTime - $startTime) * 1000, 2);
    
    $info = curl_getinfo($ch);
    $httpCode = $info['http_code'];
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Mostrar resultados
    echo '<table>';
    echo '<tr><th>Métrica</th><th>Valor</th></tr>';
    echo '<tr><td>Código HTTP</td><td>';
    if ($httpCode == 200) {
        echo '<span class="badge badge-success">' . $httpCode . ' OK</span>';
    } elseif ($httpCode == 0) {
        echo '<span class="badge badge-error">' . $httpCode . ' - No conecta</span>';
    } else {
        echo '<span class="badge badge-warning">' . $httpCode . '</span>';
    }
    echo '</td></tr>';
    echo '<tr><td>Tiempo de respuesta</td><td>' . $tiempo . ' ms</td></tr>';
    echo '<tr><td>Tamaño respuesta</td><td>' . strlen($response) . ' bytes</td></tr>';
    
    if ($curlError) {
        echo '<tr><td>Error CURL</td><td><span class="badge badge-error">' . htmlspecialchars($curlError) . '</span></td></tr>';
    }
    echo '</table>';
    
    if ($httpCode == 200 && $response) {
        // Intentar parsear JSON
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<div class="success">✅ <strong>Respuesta JSON válida</strong></div>';
            echo '<h4>📦 Datos decodificados:</h4>';
            echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            
            if (isset($data['success']) && $data['success'] === true) {
                echo '<h4>✅ Datos encontrados:</h4>';
                echo '<table>';
                echo '<tr><th>Campo</th><th>Valor</th></tr>';
                foreach ($data as $key => $value) {
                    if ($key !== 'success') {
                        echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
                    }
                }
                echo '</table>';
            }
        } else {
            echo '<div class="warning">⚠️ No es JSON válido (Error: ' . json_last_error_msg() . ')</div>';
            echo '<h4>📄 Respuesta RAW (primeros 2000 caracteres):</h4>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 2000)) . '</pre>';
        }
    } elseif (empty($response)) {
        echo '<div class="error">❌ <strong>Respuesta vacía del servidor</strong></div>';
    }
    
    echo '<hr>';
}

// Información del servidor
echo '<h3>ℹ️ Información del Servidor</h3>';
echo '<table>';
echo '<tr><th>Configuración</th><th>Valor</th></tr>';
echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>CURL Version</td><td>' . (function_exists('curl_version') ? curl_version()['version'] : 'No disponible') . '</td></tr>';
echo '<tr><td>allow_url_fopen</td><td>' . (ini_get('allow_url_fopen') ? '✅ Habilitado' : '❌ Deshabilitado') . '</td></tr>';
echo '<tr><td>max_execution_time</td><td>' . ini_get('max_execution_time') . ' segundos</td></tr>';
echo '</table>';

?>
        
        <hr>
        <p>
            <a href="?cedula=<?= htmlspecialchars($cedula) ?>">🔄 Recargar</a> | 
            <a href="<?= $_SERVER['PHP_SELF'] ?>">🆕 Nueva consulta</a>
        </p>
    </div>
</body>
</html>