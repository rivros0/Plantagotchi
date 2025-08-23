<?php
// Connessione al DB (procedurale)
require __DIR__ . '/config.php';

// Acquisizione del corpo JSON ricevuto
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Verifica che tutti i campi richiesti siano presenti
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
    echo json_encode(["error" => "Payload incompleto."]);
    exit;
}

// Sanitizzazione e parsing
$ts          = date('Y-m-d H:i:s', strtotime($data['ts']));
$temp        = floatval($data['temperatura_c']);
$hum_air     = floatval($data['umidita_aria']);
$hum_soil    = intval($data['umidita_suolo']);
$lux         = floatval($data['luce_lux']);
$device_id   = substr(trim($data['device_id']), 0, 40);
$species     = substr(trim($data['species']), 0, 60);
$ip_client   = $_SERVER['REMOTE_ADDR'] ?? null;

// Preparazione e invio query (con prepared statement)
$stmt = mysqli_prepare($conn, "
  INSERT INTO misure_pianta (ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux, device_id, species, ip_client)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Errore nella preparazione della query."]);
    exit;
}

// Binding dei parametri (t = testo, f = float, i = int, s = string)
mysqli_stmt_bind_param($stmt, "sddidsss", $ts, $temp, $hum_air, $hum_soil, $lux, $device_id, $species, $ip_client);
$ok = mysqli_stmt_execute($stmt);

// Risposta finale
if ($ok) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Errore nell'inserimento dati."]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
