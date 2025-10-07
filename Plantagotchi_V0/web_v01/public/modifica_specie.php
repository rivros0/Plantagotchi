<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Verifica accesso utente
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Verifica presenza ID
if (!isset($_GET['id'])) {
    echo "ID mancante.";
    exit;
}

$id = intval($_GET['id']);

// === Recupero dati specie ===
$query = "SELECT * FROM species_params WHERE id = $id";
$result = mysqli_query($conn, $query);
$specie = mysqli_fetch_assoc($result);

if (!$specie) {
    echo "Specie non trovata.";
    exit;
}

// === Aggiornamento ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_modifiche'])) {
    $species = trim($_POST['specie']);
    $temp_min = floatval($_POST['temp_min']);
    $temp_max = floatval($_POST['temp_max']);
    $hum_air_min = floatval($_POST['hum_air_min']);
    $hum_air_max = floatval($_POST['hum_air_max']);
    $soil_min = intval($_POST['soil_min']);
    $soil_max = intval($_POST['soil_max']);
    $lux_min = floatval($_POST['lux_min']);
    $lux_max = floatval($_POST['lux_max']);

    $stmt = mysqli_prepare($conn, "UPDATE species_params SET species=?, temp_min=?, temp_max=?, hum_air_min=?, hum_air_max=?, soil_min=?, soil_max=?, lux_min=?, lux_max=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sddddiiddi', $species, $temp_min, $temp_max, $hum_air_min, $hum_air_max, $soil_min, $soil_max, $lux_min, $lux_max, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: gestione_specie.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Modifica Specie</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <h1>✏️ Modifica Parametri Specie</h1>
    <nav>
      <a href="gestione_specie.php">🔙 Torna alla Gestione</a>
    </nav>
  </header>

  <main class="container">
    <section class="box">
      <form method="post">
        <table>
          <tr>
            <td><label for="specie">Nome specie:</label></td>
            <td colspan="2"><input type="text" name="specie" id="specie" value="<?= htmlspecialchars($specie['species']) ?>" required></td>
          </tr>
          <tr>
            <td>Temperatura (°C)</td>
            <td><input type="number" name="temp_min" step="0.1" value="<?= $specie['temp_min'] ?>" required></td>
            <td><input type="number" name="temp_max" step="0.1" value="<?= $specie['temp_max'] ?>" required></td>
          </tr>
          <tr>
            <td>Umidità aria (%)</td>
            <td><input type="number" name="hum_air_min" step="1" value="<?= $specie['hum_air_min'] ?>" required></td>
            <td><input type="number" name="hum_air_max" step="1" value="<?= $specie['hum_air_max'] ?>" required></td>
          </tr>
          <tr>
            <td>Umidità suolo (%)</td>
            <td><input type="number" name="soil_min" step="1" value="<?= $specie['soil_min'] ?>" required></td>
            <td><input type="number" name="soil_max" step="1" value="<?= $specie['soil_max'] ?>" required></td>
          </tr>
          <tr>
            <td>Luce (lux)</td>
            <td><input type="number" name="lux_min" step="1" value="<?= $specie['lux_min'] ?>" required></td>
            <td><input type="number" name="lux_max" step="1" value="<?= $specie['lux_max'] ?>" required></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:center">
              <button type="submit" name="salva_modifiche">💾 Salva modifiche</button>
            </td>
          </tr>
        </table>
      </form>
    </section>
  </main>

</body>
</html>
