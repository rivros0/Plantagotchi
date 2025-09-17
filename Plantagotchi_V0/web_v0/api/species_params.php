<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Connessione DB
$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Leggi tutte le specie
    $stmt = $db->query("SELECT * FROM species_params ORDER BY species ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    // Inserisci o aggiorna specie
    $mode = $input['mode'] ?? 'add';
    $species = trim($input['species']);

    if ($mode === 'add') {
        $stmt = $db->prepare("INSERT INTO species_params 
            (species, temp_min, temp_max, hum_air_min, hum_air_max, soil_min, soil_max, lux_min, lux_max)
            VALUES (:species, :temp_min, :temp_max, :hum_air_min, :hum_air_max, :soil_min, :soil_max, :lux_min, :lux_max)
            ON DUPLICATE KEY UPDATE 
            temp_min = VALUES(temp_min), temp_max = VALUES(temp_max), 
            hum_air_min = VALUES(hum_air_min), hum_air_max = VALUES(hum_air_max),
            soil_min = VALUES(soil_min), soil_max = VALUES(soil_max),
            lux_min = VALUES(lux_min), lux_max = VALUES(lux_max)");
    } elseif ($mode === 'edit') {
        $stmt = $db->prepare("UPDATE species_params SET
            temp_min = :temp_min, temp_max = :temp_max,
            hum_air_min = :hum_air_min, hum_air_max = :hum_air_max,
            soil_min = :soil_min, soil_max = :soil_max,
            lux_min = :lux_min, lux_max = :lux_max
            WHERE species = :species");
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mode']);
        exit;
    }

    $stmt->execute([
        ':species' => $species,
        ':temp_min' => $input['temp_min'],
        ':temp_max' => $input['temp_max'],
        ':hum_air_min' => $input['hum_air_min'],
        ':hum_air_max' => $input['hum_air_max'],
        ':soil_min' => $input['soil_min'],
        ':soil_max' => $input['soil_max'],
        ':lux_min' => $input['lux_min'],
        ':lux_max' => $input['lux_max']
    ]);

    echo json_encode(['status' => 'OK']);
    exit;
}

if ($method === 'DELETE') {
    $species = $input['species'] ?? null;
    if (!$species) {
        http_response_code(400);
        echo json_encode(['error' => 'Specie non specificata']);
        exit;
    }
    $stmt = $db->prepare("DELETE FROM species_params WHERE species = ?");
    $stmt->execute([$species]);

    echo json_encode(['status' => 'Deleted']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non supportato']);
exit;
?>
