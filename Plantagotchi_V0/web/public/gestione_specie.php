<?php
require_once __DIR__ . '/../api/config.php';
session_start();

// Verifica accesso utente
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Aggiungi specie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuova_specie'])) {
    $specie = trim($_POST['species']);
    $temp_min = floatval($_POST['temp_min']);
    $temp_max = floatval($_POST['temp_max']);
    $hum_air_min = floatval($_POST['hum_air_min']);
    $hum_air_max = floatval($_POST['hum_air_max']);
    $soil_min = intval($_POST['soil_min']);
    $soil_max = intval($_POST['soil_max']);
    $lux_min = floatval($_POST['lux_min']);
    $lux_max = floatval($_POST['lux_max']);

    if (!empty($specie)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO species_params (species, temp_min, temp_max, hum_air_min, hum_air_max, soil_min, soil_max, lux_min, lux_max)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sddddii dd', $specie, $temp_min, $temp_max, $hum_air_min, $hum_air_max, $soil_min, $soil_max, $lux_min, $lux_max);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: gestione_specie.php");
        exit;
    }
}

// Elimina specie
if (isset($_GET['elimina'])) {
    $id = intval($_GET['elimina']);
    mysqli_query($conn, "DELETE FROM species_params WHERE id = $id");
    header("Location: gestione_specie.php");
    exit;
}

// Recupera elenco delle specie
$specie_result = mysqli_query($conn, "SELECT * FROM species_params ORDER BY species ASC");
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Gestione Specie</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <h1>🌱 Gestione delle Specie Monitorate</h1>
    <nav>
      <a href="dashboard.php">🔙 Torna alla Dashboard</a>
    </nav>
  </header>

  <main class="container">

    <!-- FORM AGGIUNTA NUOVA SPECIE -->
    <section class="box">
      <h2>➕ Aggiungi nuova specie</h2>
      <form method="post">
        <input type="hidden" name="nuova_specie" value="1">
        <div class="grid-2col">
          <label>Nome specie: <input type="text" name="species" required></label>
          <label>Temperatura min °C: <input type="number" name="temp_min" step="0.1" required></label>
          <label>Temperatura max °C: <input type="number" name="temp_max" step="0.1" required></label>
          <label>Umidità aria min %: <input type="number" name="hum_air_min" step="0.1" required></label>
          <label>Umidità aria max %: <input type="number" name="hum_air_max" step="0.1" required></label>
          <label>Umidità suolo min %: <input type="number" name="soil_min" required></label>
          <label>Umidità suolo max %: <input type="number" name="soil_max" required></label>
          <label>Luce min lux: <input type="number" name="lux_min" step="0.1" required></label>
          <label>Luce max lux: <input type="number" name="lux_max" step="0.1" required></label>
        </div>
        <button type="submit">Salva specie</button>
      </form>
    </section>

    <!-- TABELLA SPECIE REGISTRATE -->
    <section class="box">
      <h2>📋 Elenco specie registrate</h2>
      <table>
        <thead>
          <tr>
            <th>Specie</th>
            <th>🌡️ Temp (min-max)</th>
            <th>💧 Aria % (min-max)</th>
            <th>🌱 Suolo % (min-max)</th>
            <th>☀️ Luce (lux)</th>
            <th>🛠️ Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($s = mysqli_fetch_assoc($specie_result)): ?>
            <tr>
              <td><?= htmlspecialchars($s['species']) ?></td>
              <td><?= $s['temp_min'] ?> - <?= $s['temp_max'] ?></td>
              <td><?= $s['hum_air_min'] ?> - <?= $s['hum_air_max'] ?></td>
              <td><?= $s['soil_min'] ?> - <?= $s['soil_max'] ?></td>
              <td><?= $s['lux_min'] ?> - <?= $s['lux_max'] ?></td>
              <td>
                <a href="modifica_specie.php?id=<?= $s['id'] ?>">✏️ Modifica</a> |
                <a href="gestione_specie.php?elimina=<?= $s['id'] ?>" onclick="return confirm('Confermi eliminazione?')">🗑️ Elimina</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>

  </main>
</body>
</html>
