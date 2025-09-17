<?php
require_once __DIR__ . '/../api/config.php';
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Recupera l'ultima misurazione per ciascun device_id
$query = "
    SELECT m.*
    FROM misure_pianta m
    INNER JOIN (
        SELECT device_id, MAX(ts) AS max_ts
        FROM misure_pianta
        GROUP BY device_id
    ) AS last
    ON m.device_id = last.device_id AND m.ts = last.max_ts
    ORDER BY m.device_id ASC
";

$result = mysqli_query($conn, $query);
$piante = [];

while ($row = mysqli_fetch_assoc($result)) {
    $piante[] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Plantagotchi</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <h1>🌱 Plantagotchi - Dashboard</h1>
    <nav>
      <a href="gestione_specie.php">🌿 Gestione Specie</a>
      <a href="logout.php">🔒 Logout</a>
    </nav>
  </header>

  <main>
  <div class="popolazione">
    <?php foreach ($piante as $pianta): ?>
      <?php
        $species = $pianta['species'];
        $device = $pianta['device_id'];
        $querySpecie = "SELECT * FROM species_params WHERE species = '" . mysqli_real_escape_string($conn, $species) . "'";
        $resSpecie = mysqli_query($conn, $querySpecie);
        $parametri = mysqli_fetch_assoc($resSpecie);

        $icone = [];
        $messaggi = [];

        if ($parametri) {
            // Temperatura
            if ($pianta['temperatura_c'] < $parametri['temp_min']) {
                $icone[] = "❄️";
                $messaggi[] = "Temperatura troppo bassa";
            } elseif ($pianta['temperatura_c'] > $parametri['temp_max']) {
                $icone[] = "🔥";
                $messaggi[] = "Temperatura troppo alta";
            }

            // Umidità aria
            if ($pianta['umidita_aria'] < $parametri['hum_air_min']) {
                $icone[] = "🌬️";
                $messaggi[] = "Aria troppo secca";
            } elseif ($pianta['umidita_aria'] > $parametri['hum_air_max']) {
                $icone[] = "💧";
                $messaggi[] = "Aria troppo umida";
            }

            // Umidità suolo
            if ($pianta['umidita_suolo'] < $parametri['soil_min']) {
                $icone[] = "🌵";
                $messaggi[] = "Terreno troppo secco";
            } elseif ($pianta['umidita_suolo'] > $parametri['soil_max']) {
                $icone[] = "🌊";
                $messaggi[] = "Terreno troppo bagnato";
            }

            // Luce
            if ($pianta['luce_lux'] < $parametri['lux_min']) {
                $icone[] = "🌑";
                $messaggi[] = "Poca luce";
            } elseif ($pianta['luce_lux'] > $parametri['lux_max']) {
                $icone[] = "☀️";
                $messaggi[] = "Troppa luce";
            }
        }

        $stato = empty($icone) ? "🟢" : implode(' ', $icone);
      ?>

      <div class="card">
        <!-- Paragrafo con le icone -->
        <p class="stato"><?= $stato ?></p>

        <!-- Avvisi su righe separate -->
        <?php if (!empty($messaggi)): ?>
          <div class="messaggi-warning">
            <?php foreach ($messaggi as $msg): ?>
              <p><?= htmlspecialchars($msg) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>


        <!-- Informazioni principali -->
        <h2><?= htmlspecialchars($species) ?></h2>
        <p><strong>Device:</strong> <?= htmlspecialchars($device) ?></p>
        <p><strong>Ultima lettura:</strong><br><?= htmlspecialchars($pianta['ts']) ?></p>
        <a class="button" href="grafico.php?device_id=<?= urlencode($device) ?>">📈 Vedi grafici</a>
      </div>
    <?php endforeach; ?>
  </div>
</main>


</body>
</html>
