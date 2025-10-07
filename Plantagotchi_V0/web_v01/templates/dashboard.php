<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

// Recupera l'ultima misurazione per ogni device
$sql = "
    SELECT m.* FROM misure_pianta m
    INNER JOIN (
        SELECT device_id, MAX(ts) AS max_ts
        FROM misure_pianta
        GROUP BY device_id
    ) last
    ON m.device_id = last.device_id AND m.ts = last.max_ts
    ORDER BY m.device_id ASC
";

$result = mysqli_query($conn, $sql);
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
  <link rel="stylesheet" href="/public/style.css">
</head>
<body>
  <header>
    <h1>🌱 Plantagotchi - Dashboard</h1>
    <nav>
      <a href="/public/gestione_specie.php">🌿 Gestione Specie</a>
      <a href="/public/logout.php">🔒 Logout</a>
    </nav>
  </header>

  <main>
    <div class="popolazione">
      <?php foreach ($piante as $pianta): ?>
        <?php
          $species = mysqli_real_escape_string($conn, $pianta['species']);
          $device = $pianta['device_id'];

          $sql_specie = "SELECT * FROM species_params WHERE species = '$species'";
          $resSpecie = mysqli_query($conn, $sql_specie);
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
                  $messaggi[] = "Luce insufficiente";
              } elseif ($pianta['luce_lux'] > $parametri['lux_max']) {
                  $icone[] = "☀️";
                  $messaggi[] = "Troppa luce";
              }
          }

          $stato = empty($icone) ? "🟢" : implode(' ', $icone);
        ?>

        <div class="card">
          <p class="stato"><?= $stato ?></p>
          <p style="font-size: 0.9em; line-height: 1.4;">
            <?php foreach ($messaggi as $msg): ?>
              <?= htmlspecialchars($msg) ?><br>
            <?php endforeach; ?>
          </p>
          <h2><?= htmlspecialchars($species) ?></h2>
          <p><strong>Device:</strong> <?= htmlspecialchars($device) ?></p>
          <p><strong>Ultima lettura:</strong><br><?= htmlspecialchars($pianta['ts']) ?></p>
          <a class="button" href="/public/grafico.php?device_id=<?= urlencode($device) ?>">📈 Vedi grafici</a>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
