<?php
require __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

/* Richiediamo tutti i campi fondamentali, inclusa la specie */
if (!$data || !isset(
    $data['temperatura_c'],
    $data['umidita_aria'],
    $data['umidita_suolo'],
    $data['luce_lux'],
    $data['ts'],
    $data['device_id'],
    $data['species']              // << NUOVO
)) {
  http_response_code(400);
  echo json_encode(["error"=>"Bad payload"]);
  exit;
}

/* Sanitizzazione basilare */
$temperatura = floatval($data['temperatura_c']);
$umid_aria   = floatval($data['umidita_aria']);
$umid_suolo  = intval($data['umidita_suolo']);
$luce        = floatval($data['luce_lux']);
$deviceId    = substr(trim($data['device_id']), 0, 40);
$species     = substr(trim($data['species']),   0, 60);

$ts_input = strtotime($data['ts']);
if ($ts_input === false) { $ts_input = time(); }
$ts = date('Y-m-d H:i:s', $ts_input);

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$stmt = $pdo->prepare("
  INSERT INTO misure_pianta (ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux, device_id, species, ip_client)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$ts, $temperatura, $umid_aria, $umid_suolo, $luce, $deviceId, $species, $ip]);

echo json_encode(["status"=>"ok"]);
