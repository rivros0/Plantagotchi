<?php
// Otteniamo i parametri di riferimento per la specie
$species = $pianta['species'];
$parametri_query = mysqli_query($conn, "SELECT * FROM species_params WHERE species = '" . mysqli_real_escape_string($conn, $species) . "'");
$parametri = mysqli_fetch_assoc($parametri_query);

// Stato sintetico con emoji
$allarmi = [];

// Temperatura
if ($parametri) {
  if ($pianta['temperatura_c'] < $parametri['temp_min']) {
    $allarmi[] = "❄️";
  } elseif ($pianta['temperatura_c'] > $parametri['temp_max']) {
    $allarmi[] = "🔥";
  }

  // Umidità aria
  if ($pianta['umidita_aria'] < $parametri['hum_air_min']) {
    $allarmi[] = "🌬️";
  } elseif ($pianta['umidita_aria'] > $parametri['hum_air_max']) {
    $allarmi[] = "💧";
  }

  // Umidità suolo
  if ($pianta['umidita_suolo'] < $parametri['soil_min']) {
    $allarmi[] = "🌵";
  } elseif ($pianta['umidita_suolo'] > $parametri['soil_max']) {
    $allarmi[] = "🌊";
  }

  // Luce
  if ($pianta['luce_lux'] < $parametri['lux_min']) {
    $allarmi[] = "🌑";
  } elseif ($pianta['luce_lux'] > $parametri['lux_max']) {
    $allarmi[] = "☀️";
  }
}

$stato = empty($allarmi) ? '🟢' : implode(' ', $allarmi);
?>

<div class="card">
  <div class="stato"><?= $stato ?></div>
  <h3><?= htmlspecialchars($pianta['species']) ?></h3>
  <p>Ultima rilevazione:<br><strong><?= htmlspecialchars($pianta['ts']) ?></strong></p>
  <a href="grafico.php?device_id=<?= urlencode($pianta['device_id']) ?>">Vedi dettagli</a>
</div>
