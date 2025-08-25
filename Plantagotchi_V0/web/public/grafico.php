<?php
session_start();
require_once __DIR__ . '/../api/config.php';

// 1) Accesso riservato: verifica sessione utente
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// 2) Controllo parametro device_id
if (!isset($_GET['device_id'])) {
    echo "Parametro device_id mancante.";
    exit;
}
$device_id = mysqli_real_escape_string($conn, $_GET['device_id']);

// 3) Recupera ultime 100 misurazioni (cronologiche) per la pianta
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
    $misure[] = $row;
}
$misure = array_reverse($misure);  // ordina per data crescente

// 4) Prepara gli array per i grafici (labels e dataset)
$labels   = []; $temp = []; $hum_air = []; $hum_soil = []; $lux = [];
foreach ($misure as $m) {
    $labels[]    = $m['ts'];
    $temp[]      = $m['temperatura_c'];
    $hum_air[]   = $m['umidita_aria'];
    $hum_soil[]  = $m['umidita_suolo'];
    $lux[]       = $m['luce_lux'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grafici – <?= htmlspecialchars($device_id) ?></title>
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
      margin: 30px auto;
    }
    a {
      color: #0077cc;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <h1>Grafici per <?= htmlspecialchars($device_id) ?></h1>
  <a href="dashboard.php">← Torna alla Dashboard</a>

  <!-- 5) Layout dei grafici -->
  <div class="chart-container"><canvas id="chartTemp"></canvas></div>
  <div class="chart-container"><canvas id="chartHum"></canvas></div>
  <div class="chart-container"><canvas id="chartSoil"></canvas></div>
  <div class="chart-container"><canvas id="chartLux"></canvas></div>

  <script>
    // Dati passati da PHP a JavaScript
    const labels = <?= json_encode($labels) ?>;
    const dataTemp = <?= json_encode($temp) ?>;
    const dataHumAir = <?= json_encode($hum_air) ?>;
    const dataSoil = <?= json_encode($hum_soil) ?>;
    const dataLux = <?= json_encode($lux) ?>;

    // Funzione helper per creare un grafico con griglia sull'asse X
    function makeChart(canvasId, label, data, color) {
      new Chart(document.getElementById(canvasId), {
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
            x: {
              display: true,
              title: { display: true, text: 'Data/Ora' },
              grid: { display: true },
              ticks: { autoSkip: true, maxTicksLimit: 10 }
            },
            y: {
              grid: { display: true }
            }
          }
        }
      });
    }

    // 6) Creazione dei grafici con palette distinta
    makeChart('chartTemp', 'Temperatura (°C)', dataTemp, '#e74c3c');
    makeChart('chartHum', 'Umidità Aria (%)', dataHumAir, '#3498db');
    makeChart('chartSoil', 'Umidità Suolo (%)', dataSoil, '#27ae60');
    makeChart('chartLux', 'Luce (lux)', dataLux, '#f39c12');
  </script>
</body>
</html>
