<?php
require __DIR__ . '/config.php';

$device = $_GET['device_id'] ?? null;
if ($device) {
  $stmt = $pdo->prepare("SELECT * FROM misure_pianta WHERE device_id=? ORDER BY ts DESC LIMIT 1");
  $stmt->execute([$device]);
} else {
  $stmt = $pdo->query("SELECT * FROM misure_pianta ORDER BY ts DESC LIMIT 1");
}
$row = $stmt->fetch();
echo json_encode($row ?: []);
