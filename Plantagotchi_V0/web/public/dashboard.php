<?php
require_once __DIR__ . '/../api/config.php';
session_start();

// Verifica accesso
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Recupera l'ultima misurazione per ogni pianta (per device_id)
$piante = [];
$devices_query = mysqli_query($conn, "SELECT DISTINCT device_id FROM misure_pianta");

while ($dev = mysqli_fetch_assoc($devices_query)) {
    $id = mysqli_real_escape_string($conn, $dev['device_id']);

    $res = mysqli_query($conn, "
        SELECT * FROM misure_pianta 
        WHERE device_id = '$id' 
        ORDER BY ts DESC 
        LIMIT 1
    ");

    if ($row = mysqli_fetch_assoc($res)) {
        $piante[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Plantagotchi</title>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      background: #f0f0f0;
      padding: 20px;
    }
    h1 {
      text-align: center;
    }
    .toolbar {
      text-align: center;
      margin-bottom: 20px;
    }
    .popolazione {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }
    .card {
      background: #fff;
      border: 1px solid #ccc;
      box-shadow: 0 0 5px #aaa;
      padding: 15px;
      width: 250px;
      text-align: center;
    }
    .stato {
      font-size: 30px;
      margin-bottom: 10px;
    }
    a {
      text-decoration: none;
      color: blue;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <h1>Plantagotchi - Dashboard</h1>

  <div class="toolbar">
    <a href="gestione_specie.php">Gestione Specie</a> |
    <a href="logout.php">Logout</a>
  </div>

  <div class="popolazione">
    <?php foreach ($piante as $pianta): ?>
      <?php
        // Recupera parametri soglia della specie
        $species = $pianta['species'];
        $params_query = mysqli_query($conn, "SELECT * FROM species_params WHERE species = '" . mysqli_real_escape_string($conn, $species) . "'");
        $parametri = mysqli_fetch_assoc($params_query);

        $allarmi = [];

        if ($parametri) {
            // Valuta la temperatura
            if ($pianta['temperatura_c'] < $parametri['temp_min']) {
                $allarmi[] = "❄️";
            } elseif ($pianta['temperatura_c'] > $parametri['temp_max']) {
                $allarmi[] = "🔥";
            }

            // Valuta umidità dell'aria
            if ($pianta['umidita_aria'] < $parametri['hum_air_min']) {
                $allarmi[] = "🌬️";
            } elseif ($pianta['umidita_aria'] > $parametri['hum_air_max']) {
                $allarmi[] = "💧";
            }

            // Valuta umidità del suolo
            if ($pianta['umidita_suolo'] < $parametri['soil_min']) {
                $allarmi[] = "🌵";
            } elseif ($pianta['umidita_suolo'] > $parametri['soil_max']) {
                $allarmi[] = "🌊";
            }

            // Valuta luce
            if ($pianta['luce_lux'] < $parametri['lux_min']) {
                $allarmi[] = "🌑";
            } elseif ($pianta['luce_lux'] > $parametri['lux_max']) {
                $allarmi[] = "☀️";
            }
        }

        // Se tutto è OK, mostra icona verde
        $stato = empty($allarmi) ? '🟢' : implode(' ', $allarmi);
      ?>
      <div class="card">
        <div class="stato"><?= $stato ?></div>
        <h3><?= htmlspecialchars($species) ?></h3>
        <p><strong>Dispositivo:</strong><br><?= htmlspecialchars($pianta['device_id']) ?></p>
        <p><strong>Ultima lettura:</strong><br><?= htmlspecialchars($pianta['ts']) ?></p>
        <a href="grafico.php?device_id=<?= urlencode($pianta['device_id']) ?>">📈 Vedi grafici</a>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>
