<?php
// File: /public/grafico.php
require_once __DIR__ . '/../config/config.php';
session_start();

// Controllo accesso
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Recupera il device_id dalla query string
if (!isset($_GET['device_id'])) {
    echo "Device non specificato.";
    exit;
}

$device_id = trim($_GET['device_id']);

// Recupera tutte le misurazioni per quel dispositivo
$query = "
    SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux, species
    FROM misure_pianta
    WHERE device_id = ?
    ORDER BY ts ASC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $device_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$misure = [];
$species = '';
while ($row = mysqli_fetch_assoc($result)) {
    $misure[] = $row;
    $species = $row['species']; // Stessa specie per tutte le misure di quel device
}
mysqli_stmt_close($stmt);

// Recupera i parametri soglia della specie
$thresholds = [];
if (!empty($species)) {
    $q = "SELECT * FROM species_params WHERE species = ?";
    $stmt = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($stmt, 's', $species);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $thresholds = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Grafici - <?= htmlspecialchars($device_id) ?></title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <h1>📊 Grafici per <?= htmlspecialchars($device_id) ?></h1>
    <nav><a href="dashboard.php">🔙 Torna alla Dashboard</a></nav>
  </header>

  <main class="container">
    <?php
      function creaGrafico($titolo, $campo, $unit, $color, $min=null, $max=null) {
          global $misure;
          echo "<div class='chart-container'>";
          echo "<div class='chart-title'>$titolo ($unit)</div>";
          echo "<canvas id='$campo'></canvas>";
          echo "<script>
              const labels_$campo = [";
          foreach ($misure as $m) {
              echo '"' . date('d/m H:i', strtotime($m['ts'])) . '",';
          }
          echo "];

              const data_$campo = [";
          foreach ($misure as $m) {
              echo $m[$campo] . ',';
          }
          echo "];

              const config_$campo = {
                  type: 'line',
                  data: {
                      labels: labels_$campo,
                      datasets: [{
                          label: '$titolo',
                          data: data_$campo,
                          borderColor: '$color',
                          backgroundColor: '$color' + '33',
                          fill: true,
                          tension: 0.3
                      }]
                  },
                  options: {
                      responsive: true,
                      scales: {
                          x: {
                              grid: { display: true },
                              ticks: { font: { size: 14 } }
                          },
                          y: {
                              suggestedMin: $min,
                              suggestedMax: $max,
                              ticks: { font: { size: 14 } }
                          }
                      },
                      plugins: {
                          legend: { display: false },
                          title: { display: false }
                      }
                  }
              };
              new Chart(document.getElementById('$campo'), config_$campo);
          </script>";
          echo "</div>";
      }

      creaGrafico("Temperatura", "temperatura_c", "°C", "#f94144", $thresholds['temp_min'] ?? null, $thresholds['temp_max'] ?? null);
      creaGrafico("Umidità Aria", "umidita_aria", "%", "#577590", $thresholds['hum_air_min'] ?? null, $thresholds['hum_air_max'] ?? null);
      creaGrafico("Umidità Suolo", "umidita_suolo", "%", "#43aa8b", $thresholds['soil_min'] ?? null, $thresholds['soil_max'] ?? null);
      creaGrafico("Luce", "luce_lux", "lux", "#f9c74f", $thresholds['lux_min'] ?? null, $thresholds['lux_max'] ?? null);
    ?>
  </main>
</body>
</html>
