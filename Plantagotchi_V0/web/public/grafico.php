<?php
require_once __DIR__ . '/../api/config.php';
session_start();

// Verifica accesso
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Verifica parametro
if (!isset($_GET['device_id'])) {
    echo "Dispositivo non specificato.";
    exit;
}

$device_id = mysqli_real_escape_string($conn, $_GET['device_id']);

// Recupera ultime 50 misurazioni
$query = "SELECT * FROM misure_pianta WHERE device_id = '$device_id' ORDER BY ts DESC LIMIT 50";
$result = mysqli_query($conn, $query);

$ts = $temp = $hum = $soil = $lux = [];

while ($row = mysqli_fetch_assoc($result)) {
    array_unshift($ts, $row['ts']);
    array_unshift($temp, $row['temperatura_c']);
    array_unshift($hum, $row['umidita_aria']);
    array_unshift($soil, $row['umidita_suolo']);
    array_unshift($lux, $row['luce_lux']);
}

// Recupera specie associata
$specie_q = mysqli_query($conn, "SELECT species FROM misure_pianta WHERE device_id = '$device_id' ORDER BY ts DESC LIMIT 1");
$specie = ($row = mysqli_fetch_assoc($specie_q)) ? $row['species'] : 'Sconosciuta';

// Recupera parametri della specie
$ref_q = mysqli_query($conn, "SELECT * FROM species_params WHERE species = '" . mysqli_real_escape_string($conn, $specie) . "'");
$ref = mysqli_fetch_assoc($ref_q);
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
  <h1>📈 Grafici per <?= htmlspecialchars($device_id) ?></h1>
  <nav><a href="dashboard.php">🔙 Torna alla Dashboard</a></nav>
</header>

<main class="container">

  <!-- Temperatura -->
  <div class="chart-container">
    <div class="chart-title">🌡️ Temperatura (°C)</div>
    <canvas id="tempChart"></canvas>
  </div>

  <!-- Umidità Aria -->
  <div class="chart-container">
    <div class="chart-title">💧 Umidità Aria (%)</div>
    <canvas id="humChart"></canvas>
  </div>

  <!-- Umidità Suolo -->
  <div class="chart-container">
    <div class="chart-title">🌱 Umidità Suolo (%)</div>
    <canvas id="soilChart"></canvas>
  </div>

  <!-- Luce -->
  <div class="chart-container">
    <div class="chart-title">☀️ Luce (%)</div>
    <canvas id="luxChart"></canvas>
  </div>

</main>

<script>
const labels = <?= json_encode($ts) ?>;

const commonOptions = {
  responsive: true,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      ticks: { font: { size: 14 }, color: '#444' },
      grid: { color: '#ddd' }
    },
    y: {
      ticks: { font: { size: 14 }, color: '#444' },
      grid: { color: '#ddd' }
    }
  }
};

function createChart(id, label, data, color, min=null, max=null) {
  new Chart(document.getElementById(id), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: label,
          data: data,
          borderColor: color,
          backgroundColor: color + '22',
          tension: 0.3,
          fill: true
        },
        ...(min !== null && max !== null ? [
          {
            label: 'Min',
            data: Array(data.length).fill(min),
            borderDash: [4, 4],
            borderColor: '#168d876b',
            pointRadius: 0
          },
          {
            label: 'Max',
            data: Array(data.length).fill(max),
            borderDash: [4, 4],
            borderColor: '#8b0b0bff',
            pointRadius: 0
          }
        ] : [])
      ]
    },
    options: commonOptions
  });
}

// Genera i grafici con parametri di riferimento, se disponibili
createChart("tempChart", "Temperatura", <?= json_encode($temp) ?>, "#e74c3c", <?= $ref['temp_min'] ?? 'null' ?>, <?= $ref['temp_max'] ?? 'null' ?>);
createChart("humChart", "Umidità Aria", <?= json_encode($hum) ?>, "#3498db", <?= $ref['hum_air_min'] ?? 'null' ?>, <?= $ref['hum_air_max'] ?? 'null' ?>);
createChart("soilChart", "Umidità Suolo", <?= json_encode($soil) ?>, "#2ecc71", <?= $ref['soil_min'] ?? 'null' ?>, <?= $ref['soil_max'] ?? 'null' ?>);
createChart("luxChart", "Luce", <?= json_encode($lux) ?>, "#f1c40f", <?= $ref['lux_min'] ?? 'null' ?>, <?= $ref['lux_max'] ?? 'null' ?>);
</script>

</body>
</html>
