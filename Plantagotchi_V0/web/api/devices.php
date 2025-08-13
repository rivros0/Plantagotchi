<?php
// Restituisce la lista dei device_id distinti e l'ultimo timestamp per ciascuno
require __DIR__ . '/config.php';

$stmt = $pdo->query("
  SELECT device_id, MAX(ts) AS last_ts
  FROM misure_pianta
  GROUP BY device_id
  ORDER BY device_id ASC
");

$rows = $stmt->fetchAll();
echo json_encode($rows ?: []);
