<?php
session_start();
require_once __DIR__ . '/../api/config.php';

// Accesso riservato agli utenti autenticati
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Verifica parametro GET
if (!isset($_GET['device_id'])) {
    echo "Parametro device_id mancante.";
    exit;
}

$device_id = mysqli_real_escape_string($conn, $_GET['device_id']);

// Estrai le ultime 100 misure della pianta
$query = "
    SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux
    FROM misure_pianta
    WHERE device_id = '$device_id'
    ORDER BY ts DESC
    LIMIT 100
";

$result = mysqli_query($conn, $query);
$misure = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Array ordinato cronologicamente (dal più vecchio al più recente)
    $misure[] = $row;
}

$misure = array_reverse($misure); // inverte per grafico (da sinistra a destra)

// Array per JSON
$labels = [];
$temp = [];
$hum_air = [];
$hum_soil = [];
$lux = [];

foreach ($misure as $m) {
    $labels[] = $m['ts'];
    $temp[]   = $m['temperatura_c'];
    $hum_air[]= $m['umidita_aria'];
    $hum_soil[]= $m['umidita_suolo'];
    $lux[]    = $m['luce_lux'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Grafico Pianta <?= htmlspecialchars($device_id) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: sans-serif;
      background: #f9f9f9;
      margin: 20px;
      text-align: center;
    }
    h1 {
      margin-bottom: 10px;
    }
    .chart-container {
      width: 95%;
      max-width: 800px;
      margin: 0 auto 40px auto;
    }
    a {
      display: inline-block;
      margin-bottom: 20px;
      color: #0077cc;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <h1>📈 Grafici per <?= htmlspecialchars($device_id) ?></h1>
  <a href="dashboard.php">← Torna alla Dashboard</a>

  <div class="chart-container">
    <canvas id="chartTemp"></canvas>
  </div>
  <div class="chart-container">
    <canvas id="chartHum"></canvas>
  </div>
  <div class="chart-container">
    <canvas id="chartSoil"></canvas>
  </div>
  <div class="chart-container">
    <canvas id="chartLux"></canvas>
  </div>

  <script>
    const labels = <?= json_encode($labels) ?>;
    const dataTemp = <?= json_encode($temp) ?>;
    const dataHumAir = <?= json_encode($hum_air) ?>;
    const dataSoil = <?= json_encode($hum_soil) ?>;
    const dataLux = <?= json_encode($lux) ?>;

    function makeChart(id, label, data, color) {
      new Chart(document.getElementById(id), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: label,
            data: data,
            borderColor: color,
            backgroundColor: color + '33',
            fill: true,
            tension: 0.3,
            pointRadius: 2
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true }
          },
          scales: {
            x: { display: false }
          }
        }
      });
    }

    makeChart('chartTemp', 'Temperatura (°C)', dataTemp, '#e74c3c');
    makeChart('chartHum', 'Umidità Aria (%)', dataHumAir, '#3498db');
    makeChart('chartSoil', 'Umidità Suolo (%)', dataSoil, '#27ae60');
    makeChart('chartLux', 'Luce (lux)', dataLux, '#f39c12');
  </script>
</body>
</html>
