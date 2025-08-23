<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$q = "SELECT species, MAX(ts) AS last_ts FROM misure_pianta GROUP BY species ORDER BY species";
$res = $db->query($q);
$piante = [];
while ($row = $res->fetch_assoc()) {
    $piante[] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Plantagotchi</title>
</head>
<body>
  <div><h1>Plantagotchi - Dashboard</h1></div>

  <div id="toolbar">
    <a href="gestione_specie.php">Gestione specie</a> |
    <a href="gestione_parametri.php">Gestione parametri</a> |
    <a href="logout.php">Logout</a>
  </div>

  <div id="popolazione">
    <h2>Piante monitorate</h2>
    <?php foreach ($piante as $p): ?>
      <div style="border:1px solid #ccc; padding:1em; margin:1em; display:inline-block;">
        <strong><?php echo htmlspecialchars($p['species']); ?></strong><br>
        Ultimo dato: <?php echo $p['last_ts']; ?><br>
        <span style="color:green;">● OK</span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
