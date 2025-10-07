<?php
// File: /src/api/get_thresholds.php
require_once __DIR__ . '/../../config/config.php';

// Imposta intestazioni per risposta JSON
header('Content-Type: application/json');

// Verifica presenza parametro species
if (!isset($_GET['species'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametro species mancante']);
    exit;
}

$species = trim($_GET['species']);

// Prepara query per recuperare i parametri della specie
$stmt = mysqli_prepare($conn, "
    SELECT temp_min, temp_max, hum_air_min, hum_air_max,
           soil_min, soil_max, lux_min, lux_max
    FROM species_params
    WHERE species = ?
");

mysqli_stmt_bind_param($stmt, 's', $species);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Verifica esito
if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Specie non trovata']);
}

mysqli_stmt_close($stmt);
?>
