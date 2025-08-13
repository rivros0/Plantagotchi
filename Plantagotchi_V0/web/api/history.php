
<?php
require __DIR__ . '/config.php';

$limit = max(10, min(500, intval($_GET['limit'] ?? 144)));
$device = $_GET['device_id'] ?? null;

if ($device) {
  $stmt = $pdo->prepare("SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux
                         FROM misure_pianta WHERE device_id=? ORDER BY ts DESC LIMIT ?");
  $stmt->bindValue(1, $device, PDO::PARAM_STR);
  $stmt->bindValue(2, $limit, PDO::PARAM_INT);
  $stmt->execute();
} else {
  $stmt = $pdo->prepare("SELECT ts, temperatura_c, umidita_aria, umidita_suolo, luce_lux
                         FROM misure_pianta ORDER BY ts DESC LIMIT ?");
  $stmt->bindValue(1, $limit, PDO::PARAM_INT);
  $stmt->execute();
}

$rows = $stmt->fetchAll();
echo json_encode(array_reverse($rows));
