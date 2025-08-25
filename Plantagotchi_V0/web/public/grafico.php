<?php
require_once __DIR__ . '/../api/config.php';
session_start();

// Controllo accesso
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Recupera il device_id dalla query string
$deviceId = $_GET['device_id'] ?? null;
if (!$deviceId) {
    die("Device non specificato.");
}

// Sanifica device_id e recupera dati dal DB
$deviceIdSql = mysqli_real_escape_string($conn, $deviceId);
$query = "
    SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux
    FROM misure_pianta
    WHERE device_id = '$deviceIdSql'
    ORDER BY ts ASC
";

$result = mysqli_query($conn, $query);

$labels = [];
$temp = [];
$hum = [];
$soil = [];
$lux = [];

while ($row = mysqli_fetch_assoc($result)) {
    // etichetta temporale in formato "gg/mm HH:MM"
    $labels[] = date("d/m H:i", strtotime($row['ts']));
    $temp[] = $row['temperatura_c'];
    $hum[] = $row['umidita_aria'];
    $soil[] = $row['umidita_suolo'];
    $lux[] = $row['luce_lux'];
}

// Conversione in JSON per uso JS
$jsonLabels = json_encode($labels);
$jsonTemp = json_encode($temp);
$jsonHum = json_encode($hum);
$jsonSoil = json_encode($soil);
$jsonLux = json_encode($lux);
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>📈 Grafici - <?= htmlspecialchars($deviceId) ?></title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <header>
    <h1>📈 Andamento Pianta - <?= htmlspecialchars($deviceId) ?></h1>
    <nav>
      <a href="dashboard.php">🔙 Torna alla dashboard</a>
    </nav>
  </header>

  <main>
    <div class="grafico-box">
      <canvas id="graficoTemp"></canvas>
      <canvas id="graficoHum"></canvas>
      <canvas id="graficoSoil"></canvas>
      <canvas id="graficoLux"></canvas>
    </div>
  </main>

  <script>
    // Dati ricevuti dal backend PHP
    const labels = <?= $jsonLabels ?>;
    const dataTemp = <?= $jsonTemp ?>;
    const dataHum = <?= $jsonHum ?>;
    const dataSoil = <?= $jsonSoil ?>;
    const dataLux = <?= $jsonLux ?>;

    // Funzione generica per creare un grafico
    function creaGrafico(id, label, data, colore) {
      new Chart(document.getElementById(id), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: label,
            data: data,
            borderColor: colore,
            borderWidth: 2,
            fill: false,
            tension: 0.3
          }]
        },
        options: {
          scales: {
            x: {
              ticks: {
                maxTicksLimit: 10,
                callback: (value, index, ticks) => labels[index] // formato leggibile
              }
            },
            y: {
              beginAtZero: false
            }
          },
          responsive: true,
          plugins: {
            legend: {
              display: true
            },
            title: {
              display: false
            }
          }
        }
      });
    }

    // Creazione dei singoli grafici
    creaGrafico("graficoTemp", "Temperatura (°C)", dataTemp, "#f44336");
    creaGrafico("graficoHum", "Umidità Aria (%)", dataHum, "#2196f3");
    creaGrafico("graficoSoil", "Umidità Suolo (%)", dataSoil, "#4caf50");
    creaGrafico("graficoLux", "Luce (lux)", dataLux, "#ff9800");
  </script>

</body>
</html>
