<?php
// Configurazione DB (mysqli, procedurale, no PDO)
require_once __DIR__ . '/config.php';

// Recupera e decodifica i dati JSON dal body della richiesta
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Controlla che tutti i campi richiesti siano presenti
if (!$data || !isset(
    $data['temperatura_c'],
    $data['umidita_aria'],
    $data['umidita_suolo'],
    $data['luce_lux'],
    $data['ts'],
    $data['device_id'],
    $data['species']
)) {
    http_response_code(400);
    echo json_encode(["error" => "Payload incompleto"]);
    exit;
}

// Sanitizzazione dei dati
$temperatura = floatval($data['temperatura_c']);
$umid_aria   = floatval($data['umidita_aria']);
$umid_suolo  = intval($data['umidita_suolo']);
$luce        = floatval($data['luce_lux']);
$deviceId    = substr(trim($data['device_id']), 0, 40);
$species     = substr(trim($data['species']),   0, 60);

// Parsing del timestamp
$ts_input = strtotime($data['ts']);
$ts = $ts_input !== false ? date('Y-m-d H:i:s', $ts_input) : date('Y-m-d H:i:s');

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Inserimento nel database
$query = "INSERT INTO misure_pianta (ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux, device_id, species, ip_client) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Errore nella query DB"]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sdddiiss", $ts, $temperatura, $umid_aria, $umid_suolo, $luce, $deviceId, $species, $ip);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Inserimento fallito"]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
