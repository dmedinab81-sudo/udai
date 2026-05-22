<?php
header('Content-Type: application/json; charset=utf-8');

$cedula = preg_replace('/\D+/', '', $_GET['cedula'] ?? '');
if (strlen($cedula) !== 10) {
  echo json_encode(['success' => false, 'message' => 'Cédula inválida']);
  exit;
}

$host = 'sais-hglp.saludzona5.gob.ec';
$ip   = '157.100.156.40';
//$url  = "https://{$host}/sais/cliente_datos_udai.php?cedula=" . urlencode($cedula);
$url  = "http://157.100.156.40/sais/cliente_datos_udai.php?cedula=" . urlencode($cedula);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_FOLLOWLOCATION => true,

  // Forzar que ese hostname resuelva a esa IP (mantiene SNI con hostname)
  CURLOPT_RESOLVE => [
    "{$host}:443:{$ip}"
  ],

  CURLOPT_HTTPHEADER => [
    'Accept: application/json',
    'User-Agent: udai.site (RC Proxy resolve)'
  ],

  // Aquí el problema era "hostname mismatch" del cert (cert para IP),
  // así que aunque tengamos SNI correcto, la verificación del nombre falla.
  // Dejamos verificación de certificado pero SIN validar hostname.
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_SSL_VERIFYHOST => 0,
]);

$body  = curl_exec($ch);
$http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errno = curl_errno($ch);
$error = curl_error($ch);
curl_close($ch);

if ($body === false || $http !== 200) {
  echo json_encode([
    'success' => false,
    'message' => 'No se pudo consultar RC (resolve)',
    'http' => $http ?: null,
    'curl_errno' => $errno ?: null,
    'curl_error' => $error ?: null
  ]);
  exit;
}

echo $body;
